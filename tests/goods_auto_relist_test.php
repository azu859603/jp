<?php
/**
 * 流拍自动上架（php think goods:auto-relist）
 *  - 范围：会员属性 = 自营店铺 的全部卖家，不由后台指定卖家 ID
 *  - 后台设置的拍卖时长是一个区间「最短 ~ 最长」，最长填 0 表示关闭
 *  - 每件商品在区间内各自随机一个时长，截拍时间 = 上架时间 + 随机时长；上架时清空旧出价
 *  - 老配置只有 auto_relist_hours 时按「旧值 ~ 旧值+6」兼容，页面也这样回显
 *  - settle 只负责结算为流拍，上架由本命令独立完成
 */
$root = 'D:/phpstudy_pro/WWW/jp'; $php = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0, $selfShop = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_self_shop,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,$seller," . ($seller ? 1 : 0) . ",0,$selfShop,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$qa    = mk('19999990180', 'QA自营卖家A', 1, 1);
$qa2   = mk('19999990183', 'QA自营卖家B', 1, 1);
$other = mk('19999990181', 'QA非自营卖家', 1, 0);
$buyer = mk('19999990182', 'QA买家');
$cat = (int)$pdo->query('select id from category limit 1')->fetchColumn();
function mkGoods($seller, $title, $status, $end) { global $pdo, $T, $cat; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($seller,$cat,'$title','','[]',100,10,0,$status,$T-7200,$end,0,$T,$T)"); return (int)$pdo->lastInsertId(); }
$g1 = mkGoods($qa, 'QA流拍1', 3, $T - 3600);
$g2 = mkGoods($qa, 'QA流拍2', 3, $T - 1800);
$g3 = mkGoods($qa, 'QA拍卖中', 1, $T + 7200);
$g4 = mkGoods($other, 'QA非自营流拍', 3, $T - 3600);
$g5 = mkGoods($qa, 'QA待结算', 1, $T - 60);
$g6 = mkGoods($qa2, 'QA自营B流拍', 3, $T - 3600);
$pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g1,$buyer,110,0,2,0,$T)");
$ids = "$g1,$g2,$g3,$g4,$g5,$g6";
// 区间设置：setting 表里没有这两行时要能插入
function setv($n, $v) { global $pdo, $T; $pdo->exec("insert into setting(name,value,create_time) values('$n','$v',$T) on duplicate key update value='$v'"); }
function delv($n) { global $pdo; $pdo->exec("delete from setting where name='$n'"); }
function setRange($min, $max) { setv('auto_relist_hours_min', $min); setv('auto_relist_hours_max', $max); }
function run($cmd) { global $php, $root; return shell_exec("cd /d " . str_replace('/', '\\', $root) . " && \"$php\" think $cmd 2>&1"); }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
// 关键隔离：脚本按「自营店铺」取全部卖家，测试期间先把库里真实的自营会员临时置 0，
// 否则一旦启用时长，真实商品会被批量重新上架。finally 里原样还原。
$realSelf = $pdo->query('select id from user where is_self_shop=1')->fetchAll(PDO::FETCH_COLUMN);
$realSelf = array_values(array_diff(array_map('intval', $realSelf), [$qa, $qa2]));
if ($realSelf) { $pdo->exec('update user set is_self_shop=0 where id in (' . implode(',', $realSelf) . ')'); }
// 原有设置，结束时还原
$origHours = $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn();
$origMin   = $pdo->query("select value from setting where name='auto_relist_hours_min'")->fetchColumn();
$origMax   = $pdo->query("select value from setting where name='auto_relist_hours_max'")->fetchColumn();

$asid = md5('ar' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
try {
    echo "== 关闭 ==\n";
    setRange('0', '0');
    $o = run('goods:auto-relist'); ok('最长时长为 0：命令提示未开启', strpos($o, '未开启') !== false, $o);
    ok('  流拍商品未被上架', g($g1)['status'] == 3 && g($g2)['status'] == 3, '');
    setRange('5', '0');
    $o = run('goods:auto-relist'); ok('只填最短、最长为 0 也是关闭', strpos($o, '未开启') !== false && g($g1)['status'] == 3, $o);

    echo "== 区间上架 ==\n";
    setRange('2', '5');
    $pdo->exec("update user set is_self_shop=0 where id in ($qa,$qa2)");
    $o = run('goods:auto-relist'); ok('没有自营店铺会员：提示并跳过', strpos($o, '没有「自营店铺」会员') !== false && g($g1)['status'] == 3, $o);
    $pdo->exec("update user set is_self_shop=1 where id in ($qa,$qa2)");

    $t0 = time();
    $o = run('goods:auto-relist');
    ok('两个自营卖家的 3 件流拍全部上架', strpos($o, '自营店铺卖家 2 个') !== false && strpos($o, '自动上架 3 件') !== false && strpos($o, (string)$g6) !== false, $o);
    ok('  输出里带区间与截拍时间段', strpos($o, '拍卖时长 2 ~ 5 小时') !== false && preg_match('/截拍 [\d\- :]+ ~ [\d\- :]+ 之间随机/u', (string)$o) === 1, $o);
    $x1 = g($g1); $x2 = g($g2); $x6 = g($g6);
    $d1 = $x1['end_time'] - $x1['start_time']; $d2 = $x2['end_time'] - $x2['start_time']; $d6 = $x6['end_time'] - $x6['start_time'];
    $inRange = function ($d) { return $d >= 2 * 3600 && $d <= 5 * 3600; };
    ok('  都变为拍卖中，每件时长都落在 2~5 小时内', $x1['status'] == 1 && $x2['status'] == 1 && $x6['status'] == 1
        && $inRange($d1) && $inRange($d2) && $inRange($d6), json_encode([$d1, $d2, $d6]));
    ok('  起拍时间就是上架时刻', abs($x1['start_time'] - $t0) <= 60, $x1['start_time'] - $t0);
    ok('  旧出价记录清空，出价数 / 得标人 / 成交价归零', (int)$pdo->query("select count(*) from bid_record where goods_id=$g1")->fetchColumn() == 0 && $x1['bid_count'] == 0 && $x1['winner_id'] == 0 && $x1['final_price'] == 0);
    ok('  拍卖中的商品未受影响', g($g3)['end_time'] == $T + 7200 && g($g3)['status'] == 1);
    ok('  非自营卖家的流拍商品未上架', g($g4)['status'] == 3);
    ok('  操作日志记录区间', (int)$pdo->query("select count(*) from admin_log where action like '%自动上架流拍商品：自营店铺卖家 2 个，3 件，拍卖时长 2 ~ 5 小时%'")->fetchColumn() == 1);
    ok('  心跳与日志文件存在', file_exists("$root/runtime/auto_relist.heartbeat") && preg_match('/ids=[0-9,]*\b' . $g1 . '\b/', (string)file_get_contents("$root/runtime/log/auto_relist.log")));
    $o = run('goods:auto-relist'); ok('再次执行：没有流拍商品', strpos($o, '没有流拍商品') !== false, $o);

    echo "== 固定时长（两个值一样） ==\n";
    $o = run('settle'); ok('settle 只结算为流拍，不顺带上架', strpos($o, "ids=$g5") !== false && g($g5)['status'] == 3, $o);
    setRange('3', '3');
    $o = run('goods:auto-relist'); $x5 = g($g5);
    ok('两个值相同时是固定时长 3 小时', strpos($o, '拍卖时长 3 小时') !== false && $x5['status'] == 1 && abs(($x5['end_time'] - $x5['start_time']) - 3 * 3600) <= 60, $o);

    echo "== 最短大于最长时按最长兜底 ==\n";
    $g7 = mkGoods($qa, 'QA流拍3', 3, $T - 3600); $ids .= ",$g7";
    setv('auto_relist_hours_min', '9'); setv('auto_relist_hours_max', '4');
    $o = run('goods:auto-relist'); $x7 = g($g7);
    ok('脚本按 0~4 小时处理，不会算出负数时长', $x7['status'] == 1 && ($x7['end_time'] - $x7['start_time']) >= 0 && ($x7['end_time'] - $x7['start_time']) <= 4 * 3600, $o);

    echo "== 老配置兼容 ==\n";
    delv('auto_relist_hours_min'); delv('auto_relist_hours_max'); setv('auto_relist_hours', '8');
    $g8 = mkGoods($qa, 'QA流拍4', 3, $T - 3600); $ids .= ",$g8";
    $o = run('goods:auto-relist'); $x8 = g($g8);
    ok('只有旧字段时按「旧值 ~ 旧值+6」跑', strpos($o, '拍卖时长 8 ~ 14 小时') !== false
        && ($x8['end_time'] - $x8['start_time']) >= 8 * 3600 && ($x8['end_time'] - $x8['start_time']) <= 14 * 3600, $o);
    [$c, $b] = req($asid, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页把旧值回显成区间 8 ~ 14', $c == 200 && strpos($b, 'name="auto_relist_hours_min" value="8"') !== false && strpos($b, 'name="auto_relist_hours_max" value="14"') !== false, "HTTP $c");
    setv('auto_relist_hours', '0');

    echo "== 后台设置 ==\n";
    setRange('2', '6');
    [$c, $b] = req($asid, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页是两个输入框，旧的单值输入框已移除', $c == 200 && strpos($b, 'name="auto_relist_hours_min" value="2"') !== false
        && strpos($b, 'name="auto_relist_hours_max" value="6"') !== false && strpos($b, 'name="auto_relist_hours"') === false, "HTTP $c");
    ok('说明写明按区间随机', strpos($b, '各自随机') !== false && strpos($b, '后面那个填 0 表示不自动上架') !== false);
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_hours_min' => '-1', 'auto_relist_hours_max' => '12']);
    ok('负数保存后归零', ($j['code'] ?? 0) == 1 && $pdo->query("select value from setting where name='auto_relist_hours_min'")->fetchColumn() === '0', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_hours_min' => '10', 'auto_relist_hours_max' => '4']);
    ok('最短大于最长时保存被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '最短时长不能大于最长时长') !== false
        && $pdo->query("select value from setting where name='auto_relist_hours_max'")->fetchColumn() === '12', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_hours_min' => '3', 'auto_relist_hours_max' => '24']);
    ok('正常保存 3 ~ 24', ($j['code'] ?? 0) == 1 && $pdo->query("select value from setting where name='auto_relist_hours_min'")->fetchColumn() === '3'
        && $pdo->query("select value from setting where name='auto_relist_hours_max'")->fetchColumn() === '24');
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['site_name' => $pdo->query("select value from setting where name='site_name'")->fetchColumn()]);
    ok('只提交其它字段时不影响该设置', $pdo->query("select value from setting where name='auto_relist_hours_max'")->fetchColumn() === '24');
} finally {
    foreach ([['auto_relist_hours', $origHours], ['auto_relist_hours_min', $origMin], ['auto_relist_hours_max', $origMax]] as [$n, $v]) {
        if ($v === false) { delv($n); } else { setv($n, $v); }
    }
    if ($realSelf) { $pdo->exec('update user set is_self_shop=1 where id in (' . implode(',', $realSelf) . ')'); }   // 还原真实的自营会员
    $pdo->exec("delete from bid_record where goods_id in ($ids)");
    $pdo->exec("delete from goods where id in ($ids)");
    $pdo->exec("delete from admin_log where action like '%自动上架流拍商品%' and create_time>=$T");
    $pdo->exec("delete from user where id in ($qa,$qa2,$other,$buyer)");
    @unlink("$root/runtime/session/sess_$asid");
    echo "[cleanup] done，设置与自营店铺会员已还原\n";
}

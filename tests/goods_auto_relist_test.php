<?php
/**
 * 流拍自动上架（php think goods:auto-relist）
 *  - 范围改为「会员属性 = 自营店铺」的全部卖家，不再由后台指定单个卖家 ID
 *  - 后台只保留「流拍自动上架拍卖时长」，0 表示关闭
 *  - 截拍时间 = 上架时间 + 时长 + 每件随机 0~6 小时；上架时清空旧出价
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
function setv($n, $v) { global $pdo; $pdo->exec("update setting set value='$v' where name='$n'"); }
function run($cmd) { global $php, $root; return shell_exec("cd /d " . str_replace('/', '\\', $root) . " && \"$php\" think $cmd 2>&1"); }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
// 关键隔离：脚本现在按「自营店铺」取全部卖家，测试期间先把库里真实的自营会员临时置 0，
// 否则一旦启用时长，真实商品会被批量重新上架。finally 里原样还原。
$realSelf = $pdo->query('select id from user where is_self_shop=1')->fetchAll(PDO::FETCH_COLUMN);
$realSelf = array_values(array_diff(array_map('intval', $realSelf), [$qa, $qa2]));
if ($realSelf) { $pdo->exec('update user set is_self_shop=0 where id in (' . implode(',', $realSelf) . ')'); }

$asid = md5('ar' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
try {
    setv('auto_relist_hours', '0');
    $o = run('goods:auto-relist'); ok('时长为 0：命令提示未开启', strpos($o, '未开启') !== false, $o);
    ok('  流拍商品未被上架', g($g1)['status'] == 3 && g($g2)['status'] == 3, '');

    setv('auto_relist_hours', '2.5');
    $pdo->exec("update user set is_self_shop=0 where id in ($qa,$qa2)");
    $o = run('goods:auto-relist'); ok('没有自营店铺会员：提示并跳过', strpos($o, '没有「自营店铺」会员') !== false && g($g1)['status'] == 3, $o);
    $pdo->exec("update user set is_self_shop=1 where id in ($qa,$qa2)");

    $o = run('goods:auto-relist');
    ok('两个自营卖家的 3 件流拍全部上架', strpos($o, '自营店铺卖家 2 个') !== false && strpos($o, '自动上架 3 件') !== false && strpos($o, (string)$g6) !== false, $o);
    $x1 = g($g1); $x2 = g($g2); $x6 = g($g6);
    $d1 = $x1['end_time'] - $x1['start_time']; $d2 = $x2['end_time'] - $x2['start_time'];
    ok('  都变为拍卖中，截拍 = 上架时间 + 2.5 小时 + 各自随机 0~6 小时', $x1['status'] == 1 && $x2['status'] == 1 && $x6['status'] == 1 && $d1 >= 9000 && $d1 <= 9000 + 21600 && $d2 >= 9000 && $d2 <= 9000 + 21600, json_encode([$d1, $d2]));
    ok('  旧出价记录清空，出价数 / 得标人 / 成交价归零', (int)$pdo->query("select count(*) from bid_record where goods_id=$g1")->fetchColumn() == 0 && $x1['bid_count'] == 0 && $x1['winner_id'] == 0 && $x1['final_price'] == 0);
    ok('  拍卖中的商品未受影响', g($g3)['end_time'] == $T + 7200 && g($g3)['status'] == 1);
    ok('  非自营卖家的流拍商品未上架', g($g4)['status'] == 3);
    ok('  写入后台操作日志（按自营卖家数记录）', (int)$pdo->query("select count(*) from admin_log where action like '%自动上架流拍商品：自营店铺卖家 2 个，3 件%'")->fetchColumn() == 1);
    ok('  心跳与日志文件存在', file_exists("$root/runtime/auto_relist.heartbeat") && preg_match('/ids=[0-9,]*\b' . $g1 . '\b/', (string)file_get_contents("$root/runtime/log/auto_relist.log")));
    $o = run('goods:auto-relist'); ok('再次执行：没有流拍商品', strpos($o, '没有流拍商品') !== false, $o);

    $o = run('settle'); $x5 = g($g5);
    ok('settle 只结算为流拍，不顺带上架', strpos($o, "ids=$g5") !== false && $x5['status'] == 3, $o . ' status=' . $x5['status']);
    $o = run('goods:auto-relist'); $x5 = g($g5);
    ok('goods:auto-relist 独立上架刚流拍的商品', strpos($o, "ids=$g5") !== false && $x5['status'] == 1 && $x5['end_time'] - $x5['start_time'] >= 9000 && $x5['end_time'] - $x5['start_time'] <= 9000 + 21600, $o);

    echo "== 后台设置 ==\n";
    [$c, $b] = req($asid, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页只剩拍卖时长，卖家 ID 输入框已移除', $c == 200 && strpos($b, 'name="auto_relist_hours" value="2.5"') !== false && strpos($b, 'auto_relist_seller_id') === false, "HTTP $c");
    ok('说明改为按「自营店铺」', strpos($b, '「自营店铺」卖家的流拍商品自动重新上架') !== false && strpos($b, '店铺属性') !== false);
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_hours' => '-1']);
    ok('负数保存后归零', ($j['code'] ?? 0) == 1 && $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn() === '0', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_hours' => '24']);
    ok('正常保存 24', ($j['code'] ?? 0) == 1 && $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn() === '24');
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['site_name' => $pdo->query("select value from setting where name='site_name'")->fetchColumn()]);
    ok('只提交其它字段时不影响该设置', $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn() === '24');
} finally {
    setv('auto_relist_hours', '0');
    if ($realSelf) { $pdo->exec('update user set is_self_shop=1 where id in (' . implode(',', $realSelf) . ')'); }   // 还原真实的自营会员
    $pdo->exec("delete from bid_record where goods_id in ($ids)");
    $pdo->exec("delete from goods where id in ($ids)");
    $pdo->exec("delete from admin_log where action like '%自动上架流拍商品%' and create_time>=$T");
    $pdo->exec("delete from user where id in ($qa,$qa2,$other,$buyer)");
    @unlink("$root/runtime/session/sess_$asid");
    echo "[cleanup] done，设置已还原为 0 小时（关闭）\n";
}

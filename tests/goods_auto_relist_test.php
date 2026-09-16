<?php
$root = 'D:/phpstudy_pro/WWW/jp'; $php = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,$seller,$seller,0,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$qa = mk('19999990180', 'QA自动上架卖家', 1); $other = mk('19999990181', 'QA其他卖家', 1); $buyer = mk('19999990182', 'QA买家');
$cat = (int)$pdo->query('select id from category limit 1')->fetchColumn();
function mkGoods($seller, $title, $status, $end) { global $pdo, $T, $cat; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($seller,$cat,'$title','','[]',100,10,0,$status,$T-7200,$end,0,$T,$T)"); return (int)$pdo->lastInsertId(); }
$g1 = mkGoods($qa, 'QA流拍1', 3, $T - 3600); $g2 = mkGoods($qa, 'QA流拍2', 3, $T - 1800); $g3 = mkGoods($qa, 'QA拍卖中', 1, $T + 7200); $g4 = mkGoods($other, 'QA他人流拍', 3, $T - 3600); $g5 = mkGoods($qa, 'QA待结算', 1, $T - 60);
$pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g1,$buyer,110,0,2,0,$T)");
$ids = "$g1,$g2,$g3,$g4,$g5";
function setv($n, $v) { global $pdo; $pdo->exec("update setting set value='$v' where name='$n'"); }
function run($cmd) { global $php, $root; return shell_exec("cd /d " . str_replace('/', '\\', $root) . " && \"$php\" think $cmd 2>&1"); }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
$asid = md5('ar' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
// 清掉 Redis 里的 settle 兜底锁无关；site_settings() 每个进程独立读库，命令行每次都是新进程
try {
    setv('auto_relist_seller_id', $qa); setv('auto_relist_hours', '0');
    $o = run('goods:auto-relist'); ok('时长为 0：命令提示未开启', strpos($o, '未开启') !== false, $o);
    ok('  流拍商品未被上架', g($g1)['status'] == 3 && g($g2)['status'] == 3, '');
    setv('auto_relist_hours', '2.5');
    $o = run('goods:auto-relist'); ok('时长 2.5 小时：命令上架 2 件', strpos($o, '自动上架 2 件') !== false && strpos($o, "ids=$g1,$g2") !== false, $o);
    $x1 = g($g1); $x2 = g($g2); $now = time();
    $d1 = $x1['end_time'] - $x1['start_time']; $d2 = $x2['end_time'] - $x2['start_time'];
    ok('  两件都变为拍卖中，截拍 = 上架时间 + 2.5 小时 + 各自随机 0~6 小时', $x1['status'] == 1 && $x2['status'] == 1 && $d1 >= 9000 && $d1 <= 9000 + 21600 && $d2 >= 9000 && $d2 <= 9000 + 21600 && $x1['start_time'] >= $T && $x1['start_time'] <= $now + 5, json_encode([$x1['status'], $x1['start_time'] - $T, $x1['end_time'] - $x1['start_time']]));
    ok('  旧出价记录清空，出价数 / 得标人 / 成交价归零', (int)$pdo->query("select count(*) from bid_record where goods_id=$g1")->fetchColumn() == 0 && $x1['bid_count'] == 0 && $x1['winner_id'] == 0 && $x1['final_price'] == 0);
    ok('  拍卖中的商品未受影响', g($g3)['end_time'] == $T + 7200 && g($g3)['status'] == 1);
    ok('  其他卖家的流拍商品未上架', g($g4)['status'] == 3);
    ok('  写入后台操作日志', (int)$pdo->query("select count(*) from admin_log where action like '%自动上架流拍商品：卖家 {$qa}，2 件%'")->fetchColumn() == 1);
    ok('  心跳与日志文件存在', file_exists("$root/runtime/auto_relist.heartbeat") && strpos((string)file_get_contents("$root/runtime/log/auto_relist.log"), "ids=$g1,$g2") !== false);
    $o = run('goods:auto-relist'); ok('再次执行：没有流拍商品', strpos($o, '没有流拍商品') !== false, $o);
    // 命令各自独立：settle 只把到期商品结算为流拍；goods:auto-relist 才负责上架
    $o = run('settle'); $x5 = g($g5);
    ok('settle 只结算为流拍，不再顺带上架', strpos($o, '自动上架卖家') === false && strpos($o, "ids=$g5") !== false && $x5['status'] == 3, $o . ' status=' . $x5['status']);
    $o = run('goods:auto-relist'); $x5 = g($g5);
    ok('goods:auto-relist 独立上架刚流拍的商品', strpos($o, "ids=$g5") !== false && $x5['status'] == 1 && $x5['end_time'] - $x5['start_time'] >= 9000 && $x5['end_time'] - $x5['start_time'] <= 9000 + 21600, $o . ' | ' . json_encode([$x5['status'], $x5['end_time'] - $x5['start_time']]));
    ok('  auto_relist 日志文件记录了这次上架', strpos((string)file_get_contents("$root/runtime/log/auto_relist.log"), "ids=$g5") !== false);
    // 后台设置页
    [$c, $b] = req($asid, 'GET', '/admin1314/setting/index', null, false); ok('设置页含两个新字段并回显当前值', $c == 200 && strpos($b, 'name="auto_relist_seller_id" value="' . $qa . '"') !== false && strpos($b, 'name="auto_relist_hours" value="2.5"') !== false, "HTTP $c");
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_seller_id' => '-5', 'auto_relist_hours' => '-1']); ok('负数保存后归零', ($j['code'] ?? 0) == 1 && $pdo->query("select value from setting where name='auto_relist_seller_id'")->fetchColumn() === '0' && $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn() === '0', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['auto_relist_seller_id' => '1', 'auto_relist_hours' => '24']); ok('正常保存 1 / 24', ($j['code'] ?? 0) == 1 && $pdo->query("select value from setting where name='auto_relist_seller_id'")->fetchColumn() === '1' && $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn() === '24', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/setting/index', ['site_name' => $pdo->query("select value from setting where name='site_name'")->fetchColumn()]); ok('只提交其它字段时不影响新设置', $pdo->query("select value from setting where name='auto_relist_hours'")->fetchColumn() === '24');
} finally {
    setv('auto_relist_seller_id', '1'); setv('auto_relist_hours', '0');
    $pdo->exec("delete from bid_record where goods_id in ($ids)"); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from admin_log where action like '%卖家 {$qa}，%' or action like '%卖家 {$qa} 的%'");
    $pdo->exec("delete from user where id in ($qa,$other,$buyer)"); @unlink("$root/runtime/session/sess_$asid"); echo "[cleanup] done, settings restored to seller 1 / 0 hours\n";
}

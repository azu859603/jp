<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0, $virtual = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,1000,$seller,$seller,0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$seller = mk('19999990270', 'QA首手卖家', 1); $b1 = mk('19999990271', 'QA首手买家1'); $b2 = mk('19999990272', 'QA首手买家2'); $v = mk('19999990273', 'QA首手虚拟', 0, 1);
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($seller,$cat,'QA首手拍品','','[]',100,10,0,1,$T-60,$T+7200,$T,$T)"); $g = (int)$pdo->lastInsertId();
function sess($id, $key = 'user') { global $root, $pdo, $T; $sid = md5('fb' . $key . $id . $T); if ($key === 'admin') { $u = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); } else { $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); } file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
$s1 = sess($b1); $s2 = sess($b2); $sa = sess(1, 'admin');
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
try {
    [$c, $b] = req($s1, 'GET', "/goods/detail?id=$g&lang=zh-cn", null, false); ok('无出价时详情页最低出价 = 起拍价 100', $c == 200 && strpos($b, 'id="bidMin">100.00') !== false, "HTTP $c");
    [, , $j] = req($s1, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 90]); ok('低于起拍价被拒', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($s1, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 105]); ok('不按加价幅度的 105 被拒', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($s1, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 100]); ok('第一手直接出起拍价 100 成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($s2, 'GET', "/goods/detail?id=$g&lang=zh-cn", null, false); ok('有出价后详情页最低出价 = 110', strpos($b, 'id="bidMin">110.00') !== false, "HTTP $c");
    [, , $j] = req($s2, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 100]); ok('等于当前价 100 被拒', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($s2, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 120]); ok('买家2 出 120（两个幅度）成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    // 后台手动出价与搜索提示
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($seller,$cat,'QA首手拍品2','','[]',200,50,0,1,$T-60,$T+7200,$T,$T)"); $g2 = (int)$pdo->lastInsertId();
    [, , $j] = req($sa, 'GET', '/admin1314/bid/searchGoods?kw=' . $g2); $row = null; foreach ($j['data'] ?? [] as $x) if ((int)$x['id'] === $g2) $row = $x; ok('后台拍品搜索返回 has_bid=0、当前价 200', $row && $row['has_bid'] == 0 && (float)$row['top_price'] == 200, json_encode($row, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $g2, 'user_id' => $v, 'price' => 200]); ok('后台手动出价第一手出起拍价 200 成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $g2, 'user_id' => $b1, 'price' => 200]); ok('后台再出 200 被拒（最低 250）', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '250') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', '/admin1314/bid/searchGoods?kw=' . $g2); $row = null; foreach ($j['data'] ?? [] as $x) if ((int)$x['id'] === $g2) $row = $x; ok('  有出价后 has_bid=1、当前价 200', $row && $row['has_bid'] == 1 && (float)$row['top_price'] == 200, json_encode($row, JSON_UNESCAPED_UNICODE));
    // 自动出价校验：无出价时上限 = 起拍价即可
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($seller,$cat,'QA首手拍品3','','[]',300,10,0,1,$T-60,$T+7200,$T,$T)"); $g3 = (int)$pdo->lastInsertId();
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $g3, 'interval_min' => 5, 'max_price' => 299, 'stop_hours' => 0]); ok('自动出价上限 299 低于起拍价被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '起拍价') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $g3, 'interval_min' => 5, 'max_price' => 300, 'stop_hours' => 0]); $t = (int)($j['id'] ?? 0); ok('自动出价上限 = 起拍价 300 可创建（只够第一手）', ($j['code'] ?? 0) == 1 && $t > 0, json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $ids = implode(',', array_filter([$g, $g2 ?? 0, $g3 ?? 0]));
    $pdo->exec("delete from auto_bid where goods_id in ($ids)"); $pdo->exec("delete from bid_record where goods_id in ($ids)"); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from browse_history where goods_id in ($ids)");
    $pdo->exec("delete from balance_log where user_id in ($b1,$b2,$v)"); $pdo->exec("delete from sys_message where user_id in ($b1,$b2,$v)"); $pdo->exec("delete from admin_log where action like '%QA首手%'");
    $pdo->exec("delete from user where id in ($seller,$b1,$b2,$v)"); @unlink("$root/runtime/session/sess_$s1"); @unlink("$root/runtime/session/sess_$s2"); @unlink("$root/runtime/session/sess_$sa"); echo "[cleanup] done\n";
}

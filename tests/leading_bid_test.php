<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0, $virtual = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,1000,$seller,$seller,0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$seller = mk('19999990280', 'QA领先卖家', 1); $b1 = mk('19999990281', 'QA领先买家1'); $b2 = mk('19999990282', 'QA领先买家2'); $v = mk('19999990283', 'QA领先虚拟', 0, 1);
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($seller,$cat,'QA领先拍品','','[]',100,10,0,1,$T-60,$T+7200,$T,$T)"); $g = (int)$pdo->lastInsertId();
function sess($id, $key = 'user') { global $root, $pdo, $T; $sid = md5('ld' . $key . $id . $T); if ($key === 'admin') { $u = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); } else { $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); } file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
$s1 = sess($b1); $s2 = sess($b2); $sa = sess(1, 'admin');
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
try {
    [, , $j] = req($s1, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 100]); ok('买家1 第一手 100 成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($s1, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 110]); ok('买家1 领先时再出 110 被拒并提示', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '最高出价者') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($s1, 'GET', "/goods/detail?id=$g&lang=zh-cn", null, false); ok('买家1 详情页按钮为「您当前领先」且不可点', $c == 200 && strpos($b, 'go-bid leading') !== false && strpos($b, '您当前领先') !== false && strpos($b, 'javascript:openBid();') === false, "HTTP $c");
    [$c, $b] = req($s1, 'GET', "/goods/detail?id=$g&lang=en-us", null, false); ok('  英文界面显示 You are leading', strpos($b, 'You are leading') !== false, "HTTP $c");
    [$c, $b] = req($s2, 'GET', "/goods/detail?id=$g&lang=zh-cn", null, false); ok('买家2 详情页仍是「去出价」', $c == 200 && strpos($b, 'javascript:openBid();') !== false && strpos($b, 'go-bid leading') === false, "HTTP $c");
    [$c, $b] = req('', 'GET', "/goods/detail?id=$g&lang=zh-cn", null, false); ok('未登录访客详情页是「去出价」', $c == 200 && strpos($b, 'javascript:openBid();') !== false, "HTTP $c");
    [, , $j] = req($s2, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 110]); ok('买家2 出 110 成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($s1, 'GET', "/goods/detail?id=$g&lang=zh-cn", null, false); ok('买家1 被超过后按钮恢复「去出价」', strpos($b, 'javascript:openBid();') !== false && strpos($b, 'go-bid leading') === false, "HTTP $c");
    [, , $j] = req($s1, 'POST', '/goods/bid', ['goods_id' => $g, 'price' => 120]); ok('买家1 被超过后可再出 120', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $g, 'user_id' => $b1, 'price' => 130]); ok('后台不能给当前最高价者再加价', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '最高出价者') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $g, 'user_id' => $v, 'price' => 130]); ok('后台给其他买家加价 130 成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('  出价记录共 4 条', (int)$pdo->query("select count(*) from bid_record where goods_id=$g")->fetchColumn() == 4);
    $css = file_get_contents("$root/public/static/m.css"); $lay = file_get_contents("$root/app/index/view/layout.html"); ok('样式已加且版本号已更新', strpos($css, '.go-bid.leading') !== false && strpos($lay, 'm.css?v=20260915a') !== false && strpos($lay, 'i18n.js?v=20260915a') !== false);
} finally {
    $pdo->exec("delete from bid_record where goods_id=$g"); $pdo->exec("delete from goods where id=$g"); $pdo->exec("delete from browse_history where goods_id=$g");
    $pdo->exec("delete from balance_log where user_id in ($b1,$b2,$v)"); $pdo->exec("delete from sys_message where user_id in ($b1,$b2,$v)"); $pdo->exec("delete from admin_log where action like '%QA领先%'");
    $pdo->exec("delete from user where id in ($seller,$b1,$b2,$v)"); @unlink("$root/runtime/session/sess_$s1"); @unlink("$root/runtime/session/sess_$s2"); @unlink("$root/runtime/session/sess_$sa"); echo "[cleanup] done\n";
}

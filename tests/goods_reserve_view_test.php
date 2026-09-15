<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,$seller,$seller,0,1,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$seller = mk('19999990240', 'QA保留价卖家', 1); $buyer = mk('19999990241', 'QA买家');
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,reserve_price,deposit,status,start_time,end_time,create_time,update_time) values($seller,$cat,'QA保留价展示',  '','[]',100,10,888.5,0,1,$T-60,$T+7200,$T,$T)"); $g1 = (int)$pdo->lastInsertId();
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,reserve_price,deposit,status,start_time,end_time,create_time,update_time) values($seller,$cat,'QA无保留价展示','','[]',100,10,0,0,1,$T-60,$T+7200,$T,$T)"); $g2 = (int)$pdo->lastInsertId();
function sess($id) { global $root, $pdo, $T; $sid = md5('rv' . $id . $T); $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u])); return $sid; }
$ss = sess($seller); $bs = sess($buyer);
function page($sid, $p) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']); $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
try {
    [$c, $b] = page($ss, "/goods/detail?id=$g1&lang=zh-cn"); ok('卖家本人看详情：显示保留价 888.50 与「仅您可见」', $c == 200 && strpos($b, '888.50') !== false && strpos($b, '仅您可见') !== false, "HTTP $c");
    [$c, $b] = page($ss, "/goods/detail?id=$g2&lang=zh-cn"); ok('卖家本人看无保留价的拍品：不显示保留价行', $c == 200 && strpos($b, '仅您可见') === false, "HTTP $c");
    [$c, $b] = page($bs, "/goods/detail?id=$g1&lang=zh-cn"); ok('买家看详情：看不到保留价', $c == 200 && strpos($b, '888.50') === false && strpos($b, '仅您可见') === false, "HTTP $c");
    [$c, $b] = page('', "/goods/detail?id=$g1&lang=zh-cn"); ok('未登录看详情：看不到保留价', $c == 200 && strpos($b, '888.50') === false, "HTTP $c");
    [$c, $b] = page($ss, "/goods/detail?id=$g1&lang=en-us"); ok('英文界面：Reserve price / Only visible to you', $c == 200 && strpos($b, 'Reserve price') !== false && strpos($b, 'Only visible to you') !== false, "HTTP $c");
    [$c, $b] = page($ss, "/seller/goods_list?lang=zh-cn"); ok('我的商品列表：设保留价的显示「保留价 ¥888.50」，未设的不显示', $c == 200 && strpos($b, '保留价 ¥888.50') !== false && substr_count($b, '保留价 ¥') == 1, "HTTP $c");
    [$c, $b] = page($ss, "/seller/goods_list?lang=zh-tw"); ok('繁体列表显示「保留價」', $c == 200 && strpos($b, '保留價 ¥888.50') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from goods where id in ($g1,$g2)"); $pdo->exec("delete from browse_history where goods_id in ($g1,$g2)"); $pdo->exec("delete from user where id in ($seller,$buyer)");
    @unlink("$root/runtime/session/sess_$ss"); @unlink("$root/runtime/session/sess_$bs"); echo "[cleanup] done\n";
}

<?php
/**
 * 我的出价记录：拍品因未达保留价流拍时，对应行显示「流拍」标签 + 红字「尚未达到卖家预期价格」
 *  - 未达保留价流拍：标签「流拍」、有备注
 *  - 无保留价流拍（例如卖家下架后置为流拍）：标签「流拍」、无备注
 *  - 拍卖中：仍显示 领先 / 出局，无备注
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function mk($m, $nick) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,1000,1,1,0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function mkGoods($title, $seller, $status, $reserve) { global $pdo, $T; $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('$title','','','',1,$seller,100,10,$reserve,0," . ($T - 7200) . "," . ($status == 1 ? $T + 7200 : $T - 60) . ",$status,1,0,$T,$T)"); return (int)$pdo->lastInsertId(); }
function bid($g, $u, $price, $status) { global $pdo, $T; $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g,$u,$price,0,$status,0,$T)"); }
function sess($id) { global $root, $pdo; $sid = md5('br' . $id . microtime(true)); $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u])); return $sid; }
function req($sid, $p) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b]; }
function row($html, $gid) { return preg_match('#<a class="bid-row[^"]*" href="/goods/detail\?id=' . $gid . '">(.*?)</a>#s', $html, $m) ? $m[1] : ''; }

$S = mk('19999990530', 'QA保留价卖家'); $B = mk('19999990531', 'QA保留价买家');
$gFail   = mkGoods('QARN未达保留价', $S, 3, 1000);   // 流拍：最高 500 < 保留价 1000
$gFail0  = mkGoods('QARN无保留价流拍', $S, 3, 0);    // 流拍但没有保留价（不显示备注）
$gLive   = mkGoods('QARN拍卖中', $S, 1, 1000);       // 拍卖中：即便低于保留价也不提示
bid($gFail, $B, 500, 2); bid($gFail0, $B, 200, 2); bid($gLive, $B, 300, 0);
$sid = sess($B);

try {
    [$c, $html] = req($sid, '/user/bids');
    ok('出价记录页 200', $c == 200 && strpos($html, 'QARN未达保留价') !== false, "HTTP $c");
    $r = row($html, $gFail);
    ok('未达保留价流拍：标签「流拍」+ 红字备注', strpos($r, 'fail-tag') !== false && strpos($r, '>流拍<') !== false && strpos($r, 'class="b-note">尚未达到卖家预期价格<') !== false && strpos($r, 'lead-tag') === false, $r);
    $r = row($html, $gFail0);
    ok('无保留价流拍：只有「流拍」标签，无备注', strpos($r, '>流拍<') !== false && strpos($r, 'b-note') === false, $r);
    $r = row($html, $gLive);
    ok('拍卖中：显示「领先」，无备注', strpos($r, 'lead-tag') !== false && strpos($r, 'b-note') === false && strpos($r, 'fail-tag') === false, $r);
    [$c, $html] = req($sid, '/user/bids?lang=en-us');
    $r = row($html, $gFail);
    ok('英文：No Bid 标签 + Seller\'s reserve price was not met', strpos($r, '>No Bid<') !== false && strpos($r, "Seller's reserve price was not met") !== false, $r);
} finally {
    $pdo->exec("delete from bid_record where goods_id in ($gFail,$gFail0,$gLive)");
    $pdo->exec("delete from goods where id in ($gFail,$gFail0,$gLive)");
    $pdo->exec("delete from user where id in ($S,$B)");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

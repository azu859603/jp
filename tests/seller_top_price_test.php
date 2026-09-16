<?php
/**
 * 卖家「我的商品」列表：显示当前最高竞拍价
 *  - 竞拍中且有出价：显示「当前最高价 ¥最高有效出价」
 *  - 竞拍中无出价：显示「暂无出价」
 *  - 已成交：显示「成交价 ¥final_price」
 *  - 已流拍 / 已下架：不显示最高价行
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkUser($m, $nick, $seller = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,$seller,$seller,0,0,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($title, $sellerId, $status, $final = 0) {
    global $pdo, $T;
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,final_price,bid_count,view_count,create_time,update_time) values('$title','','','',1,$sellerId,100,10,0,0," . ($T - 3600) . "," . ($T + 86400) . ",$status,$final,0,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkBid($goodsId, $userId, $price, $status = 0) {
    global $pdo, $T;
    $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($goodsId,$userId,$price,0,$status,0,$T)");
    $pdo->exec("update goods set bid_count=bid_count+1 where id=$goodsId");
}
/** 取某件拍品卡片的 HTML 片段 */
function card($html, $goodsId) {
    $pos = strpos($html, 'ID: ' . $goodsId . '<');
    if ($pos === false) return '';
    $end = strpos($html, '<div class="gmanage">', $pos);
    return $end === false ? substr($html, $pos) : substr($html, $pos, $end - $pos);
}

$seller = mkUser('19999990396', 'QA最高价卖家', 1);
$b1     = mkUser('19999990397', 'QA买家甲');
$b2     = mkUser('19999990398', 'QA买家乙');
$gBid   = mkGoods('QATOP有出价', $seller, 1);
$gNone  = mkGoods('QATOP无出价', $seller, 1);
$gSold  = mkGoods('QATOP已成交', $seller, 2, 560);
$gFail  = mkGoods('QATOP已流拍', $seller, 3);
mkBid($gBid, $b1, 120);
mkBid($gBid, $b2, 150);
mkBid($gBid, $b1, 130);          // 乱序插入，最高应为 150
mkBid($gFail, $b1, 200, 2);      // 流拍退回的出价（status=2），不应计入
mkBid($gSold, $b2, 560, 1);      // 成交出价（status=1）

$sid = md5('top' . $T);
$u = $pdo->query("select * from user where id=$seller")->fetch(PDO::FETCH_ASSOC);
unset($u['password']);
file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u]));

try {
    $ch = curl_init('http://localhost/seller/goods_list');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    ok('我的商品列表可访问', $code == 200 && strpos($html, 'QATOP有出价') !== false, "HTTP $code");

    $c = card($html, $gBid);
    ok('有出价：显示当前最高价 ¥150.00（取最高有效出价，非最后一条）', strpos($c, '当前最高价') !== false && strpos($c, '¥150.00') !== false && strpos($c, '¥130.00') === false, mb_substr($c, 0, 300));
    ok('有出价：起拍价仍显示', strpos($c, '起拍价') !== false && strpos($c, '¥100.00') !== false, '');
    $c = card($html, $gNone);
    ok('无出价：显示「暂无出价」', strpos($c, '暂无出价') !== false && strpos($c, '当前最高价') === false, mb_substr($c, 0, 300));
    $c = card($html, $gSold);
    ok('已成交：显示成交价 ¥560.00', strpos($c, '成交价') !== false && strpos($c, '¥560.00') !== false, mb_substr($c, 0, 300));
    $c = card($html, $gFail);
    ok('已流拍：不显示最高价，退回的出价不计入', strpos($c, '当前最高价') === false && strpos($c, '¥200.00') === false, mb_substr($c, 0, 300));

    // 竞拍中 tab 也一致
    $ch = curl_init('http://localhost/seller/goods_list?status=1');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
    $html2 = curl_exec($ch);
    curl_close($ch);
    ok('竞拍中 tab 同样显示当前最高价', strpos(card($html2, $gBid), '¥150.00') !== false && strpos(card($html2, $gNone), '暂无出价') !== false, '');
} finally {
    $pdo->exec("delete from bid_record where goods_id in ($gBid,$gNone,$gSold,$gFail)");
    $pdo->exec("delete from goods where id in ($gBid,$gNone,$gSold,$gFail)");
    $pdo->exec("delete from user where id in ($seller,$b1,$b2)");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

<?php
/**
 * 卖家订单列表：每个订单显示「最高竞拍价 ¥X · N 次出价」
 *  - 成交订单：最高价 = 得标出价（含退回的落败出价一起计数）
 *  - 拍品没有出价记录：显示「暂无出价记录」
 *  - 同一拍品被取消后重新上架：显示新一轮的当前最高价
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
function mkGoods($title, $sellerId, $status) {
    global $pdo, $T;
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('$title','','','',1,$sellerId,100,10,0,0," . ($T - 3600) . "," . ($T + 86400) . ",$status,0,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkBid($goodsId, $userId, $price, $status) {
    global $pdo, $T;
    $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($goodsId,$userId,$price,0,$status," . ($status == 1 ? 1 : 0) . ",$T)");
}
function mkOrder($no, $goodsId, $buyer, $seller, $price, $orderStatus) {
    global $pdo, $T;
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('$no',$goodsId,'QA订单拍品','',$seller,$buyer,$price,10,0,0,0,0,1,$T,$orderStatus,'QA','13900000000','QA地址',$T,$T)");
    return (int)$pdo->lastInsertId();
}
function card($html, $no) {
    $pos = strpos($html, $no);
    if ($pos === false) return '';
    $end = strpos($html, '<div class="order-card">', $pos);
    return $end === false ? substr($html, $pos) : substr($html, $pos, $end - $pos);
}

$S  = mkUser('19999990407', 'QA订单卖家', 1);
$b1 = mkUser('19999990408', 'QA出价甲');
$b2 = mkUser('19999990409', 'QA出价乙');
$g1 = mkGoods('QA成交拍品', $S, 2);
$g2 = mkGoods('QA无出价拍品', $S, 2);
$g3 = mkGoods('QA重拍拍品', $S, 1);
mkBid($g1, $b1, 120, 2);   // 落败，已退回
mkBid($g1, $b2, 150, 1);   // 得标
mkBid($g1, $b1, 130, 2);   // 落败
mkBid($g3, $b1, 300, 0);   // 重新上架后的新一轮出价（进行中）
mkBid($g3, $b2, 320, 0);
$o1 = mkOrder('QASO0001', $g1, $b2, $S, 150, 1);
$o2 = mkOrder('QASO0002', $g2, $b1, $S, 100, 1);
$o3 = mkOrder('QASO0003', $g3, $b1, $S, 200, 4);   // 已取消的旧订单，拍品已重新上架

$sid = md5('so' . $T);
$u = $pdo->query("select * from user where id=$S")->fetch(PDO::FETCH_ASSOC);
unset($u['password']);
file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u]));

try {
    $ch = curl_init('http://localhost/seller/orders');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    ok('卖家订单页可访问', $code == 200 && strpos($html, 'QASO0001') !== false, "HTTP $code");

    $c = card($html, 'QASO0001');
    ok('成交订单：显示起拍价 ¥100.00，不再显示最高竞拍价/出价次数', strpos($c, '起拍价') !== false && strpos($c, '¥100.00') !== false && strpos($c, '最高竞拍价') === false && strpos($c, '次出价') === false, mb_substr($c, 0, 400));
    ok('成交价 ¥150.00 带「成交价」标签', strpos($c, '成交价') !== false && strpos($c, '150.00') !== false && strpos($c, 'oprice') !== false, '');
    $c = card($html, 'QASO0002');
    ok('无出价记录的拍品同样显示起拍价', strpos($c, '起拍价') !== false && strpos($c, '暂无出价记录') === false, mb_substr($c, 0, 400));
    $c = card($html, 'QASO0003');
    ok('拍品重新上架后的旧订单仍显示起拍价，不显示新一轮最高价', strpos($c, '起拍价') !== false && strpos($c, '¥320.00') === false, mb_substr($c, 0, 400));

    $ch = curl_init('http://localhost/seller/orders?order_status=1');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
    $html2 = curl_exec($ch);
    curl_close($ch);
    $c2 = card($html2, 'QASO0001'); ok('待发货 tab 同样显示成交价 / 起拍价', strpos($c2, '150.00') !== false && strpos($c2, '起拍价') !== false, '');
} finally {
    $pdo->exec("delete from `order` where id in ($o1,$o2,$o3)");
    $pdo->exec("delete from bid_record where goods_id in ($g1,$g2,$g3)");
    $pdo->exec("delete from goods where id in ($g1,$g2,$g3)");
    $pdo->exec("delete from user where id in ($S,$b1,$b2)");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

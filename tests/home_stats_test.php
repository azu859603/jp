<?php
/**
 * 首页数据条的「累计成交」只统计订单状态为「已完成」(order_status=3) 的订单
 *  - 待付款(0) / 待发货(1) / 待收货(2) / 已取消(4) / 售后中(5) 都不计入
 *  - 商品状态是「已成交」但订单还没走完，也不计入
 *  - 页面显示的数字 = 已完成订单数 + 15746（模板里的展示基数）
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();
$BASE = 15746;   // 模板 {$stats.deals+15746} 里的展示基数

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function home() {
    $ch = curl_init('http://localhost/');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, (string)$b];
}
// 页面上「累计成交」那格的数字
function shownDeals($html) {
    return preg_match('/<b>(\d+)<\/b><span>累计成交<\/span>/u', $html, $m) ? (int)$m[1] : null;
}
function mkUser($m, $nick) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,1,1,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function mkGoods($seller, $title, $status) { global $pdo, $T; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($seller,1,'$title','','[]',100,10,0,$status," . ($T - 7200) . "," . ($T - 60) . ",0,$T,$T)"); return (int)$pdo->lastInsertId(); }
function mkOrder($gid, $seller, $buyer, $orderStatus, $payStatus = 1) {
    global $pdo, $T;
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,pay_status,pay_time,order_status,create_time,update_time) values('QHS" . $gid . $T . "',$gid,'QHS订单','',$seller,$buyer,100,$payStatus," . ($payStatus ? $T : 0) . ",$orderStatus,$T,$T)");
    return (int)$pdo->lastInsertId();
}

$seller = mkUser('19999994401', 'QA首页卖家');
$buyer  = mkUser('19999994402', 'QA首页买家');
$uids   = "$seller,$buyer";
// 库里原有的已完成订单数，作为基准
$before = (int)$pdo->query("select count(*) from `order` where order_status=3")->fetchColumn();

$gids = [];
$oids = [];
try {
    echo "== 基准 ==\n";
    [$c, $h] = home();
    ok('首页可访问', $c == 200, "HTTP $c");
    ok('累计成交 = 已完成订单数 + ' . $BASE, shownDeals($h) === $before + $BASE, shownDeals($h) . ' vs ' . ($before + $BASE));

    echo "== 各种订单状态 ==\n";
    // 五个不该计入的状态各造一单，商品都标成「已成交」
    foreach ([0 => '待付款', 1 => '待发货', 2 => '待收货', 4 => '已取消', 5 => '售后中'] as $st => $name) {
        $g = mkGoods($seller, "QHS商品$st", 2); $gids[] = $g;
        $oids[] = mkOrder($g, $seller, $buyer, $st, $st === 0 || $st === 4 ? 0 : 1);
    }
    [$c, $h] = home();
    ok('待付款 / 待发货 / 待收货 / 已取消 / 售后中都不计入', shownDeals($h) === $before + $BASE, shownDeals($h) . ' vs ' . ($before + $BASE));
    ok('  商品状态是「已成交」也不算，看的是订单状态', (int)$pdo->query("select count(*) from goods where id in (" . implode(',', $gids) . ") and status=2")->fetchColumn() === 5);

    $g = mkGoods($seller, 'QHS商品已完成', 2); $gids[] = $g;
    $oids[] = mkOrder($g, $seller, $buyer, 3);
    [$c, $h] = home();
    ok('新增一单已完成 → 累计成交 +1', shownDeals($h) === $before + 1 + $BASE, shownDeals($h) . ' vs ' . ($before + 1 + $BASE));

    $g2 = mkGoods($seller, 'QHS商品已完成2', 2); $gids[] = $g2;
    $oids[] = mkOrder($g2, $seller, $buyer, 3);
    [$c, $h] = home();
    ok('再加一单已完成 → 累计成交 +2', shownDeals($h) === $before + 2 + $BASE, shownDeals($h) . ' vs ' . ($before + 2 + $BASE));

    echo "== 状态流转 ==\n";
    $pdo->exec("update `order` set order_status=5 where id=" . end($oids));
    [$c, $h] = home();
    ok('已完成的订单转入售后 → 累计成交 -1', shownDeals($h) === $before + 1 + $BASE, shownDeals($h) . ' vs ' . ($before + 1 + $BASE));
    $pdo->exec("update `order` set order_status=3 where id=" . end($oids));
    [$c, $h] = home();
    ok('售后处理完回到已完成 → 累计成交 +1', shownDeals($h) === $before + 2 + $BASE, shownDeals($h) . ' vs ' . ($before + 2 + $BASE));

    echo "== 其它两格不受影响 ==\n";
    $hot = (int)$pdo->query("select count(*) from goods where status=1 and start_time<=$T and end_time>$T")->fetchColumn();
    $mem = (int)$pdo->query("select count(*) from user")->fetchColumn();
    ok('在拍拍品仍按原口径（拍卖中且在时间窗内）', strpos($h, '<b>' . $hot . '</b><span>在拍拍品</span>') !== false, "hot=$hot");
    ok('注册会员仍是总会员数 + 32712', strpos($h, '<b>' . ($mem + 32712) . '</b><span>注册会员</span>') !== false, 'members=' . ($mem + 32712));
} finally {
    if ($oids) { $pdo->exec("delete from `order` where id in (" . implode(',', $oids) . ")"); }
    if ($gids) { $pdo->exec("delete from goods where id in (" . implode(',', $gids) . ")"); }
    $pdo->exec("delete from user where id in ($uids)");
    echo "[cleanup] done\n";
}

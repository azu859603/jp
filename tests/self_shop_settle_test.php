<?php
/**
 * 「自营店铺」卖家拍品的结算与流拍清理（php think settle）
 *
 * 结算规则：
 *  - 没有出价 / 最高价低于保留价 → 流拍（原有规则）
 *  - 最高出价者是虚拟买家        → 也按流拍处理，不生成订单
 *  - 最高出价者是真实买家        → 正常成交，生成待付款订单
 *  - 虚拟最高、真实次高          → 仍然流拍，不让次高中标
 *  - 非自营卖家的拍品不受影响：虚拟买家照样能中标
 * 流拍清理（只对自营卖家）：
 *  - 冻结的保证金先退回，然后把出价记录、自动出价任务、未付款订单全删掉，商品统计字段归零
 *  - 订单被取消导致商品回到流拍时，同样清理
 *  - 已付款的订单不会被删（防止误删真实成交的对账数据）
 *  - 非自营卖家的流拍商品保持原样：出价记录标为流拍而不是删除，任务也留着
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function run() { global $php, $root; return (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think settle 2>&1'); }
function mkUser($m, $nick, $selfShop = 0, $virtual = 0, $balance = 0, $freeze = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,is_self_shop,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,$balance,$freeze,1,1,0,$virtual,$selfShop,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($seller, $title, $reserve = 0) {
    global $pdo, $T;
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,reserve_price,deposit,status,start_time,end_time,order_id,winner_id,bid_count,create_time,update_time) values($seller,1,'$title','','[]',100,10,$reserve,0,1," . ($T - 7200) . "," . ($T - 60) . ",0,0,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function bid($gid, $uid, $price, $deposit = 0) { global $pdo, $T; $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($gid,$uid,$price,$deposit,0,0," . ($T - 600) . ")"); return (int)$pdo->lastInsertId(); }
function mkTask($gid) { global $pdo, $T; $pdo->exec("insert into auto_bid(goods_id,interval_min,max_price,stop_hours,status,next_time,last_time,bid_count,creator_type,creator_id,create_time,update_time) values($gid,5,9999,0,1," . ($T + 3600) . ",0,0,'admin',1,$T,$T)"); return (int)$pdo->lastInsertId(); }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
function orderOf($gid) { global $pdo; return $pdo->query("select * from `order` where goods_id=$gid order by id desc limit 1")->fetch(PDO::FETCH_ASSOC) ?: null; }
function bidStatus($id) { global $pdo; $v = $pdo->query("select status from bid_record where id=$id")->fetchColumn(); return $v === false ? null : (int)$v; }
function bidCount($gid) { global $pdo; return (int)$pdo->query("select count(*) from bid_record where goods_id=$gid")->fetchColumn(); }
function taskCount($gid) { global $pdo; return (int)$pdo->query("select count(*) from auto_bid where goods_id=$gid")->fetchColumn(); }
function orderCount($gid) { global $pdo; return (int)$pdo->query("select count(*) from `order` where goods_id=$gid")->fetchColumn(); }
function money($uid) { global $pdo; return $pdo->query("select balance,freeze_balance from user where id=$uid")->fetch(PDO::FETCH_ASSOC); }
function req($sid, $p, $d) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($d),
        CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost', 'Referer: http://localhost/'],
        CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch); curl_close($ch);
    return json_decode($b, true);
}

$ss  = mkUser('19999994301', 'QA自营卖家', 1);           // 自营店铺
$ns  = mkUser('19999994302', 'QA普通卖家', 0);           // 非自营
$vb  = mkUser('19999994303', 'QA虚拟买家', 0, 1);
$vb2 = mkUser('19999994304', 'QA虚拟买家2', 0, 1);
$rb  = mkUser('19999994305', 'QA真实买家', 0, 0, 0, 50); // 冻结 50 保证金
$rb2 = mkUser('19999994306', 'QA真实买家2', 0, 0);
$uids = "$ss,$ns,$vb,$vb2,$rb,$rb2";

$gA = mkGoods($ss, 'QSS自营-虚拟最高');
$gB = mkGoods($ss, 'QSS自营-真实最高');
$gC = mkGoods($ss, 'QSS自营-虚拟最高真实次高');
$gD = mkGoods($ns, 'QSS非自营-虚拟最高');
$gE = mkGoods($ss, 'QSS自营-无出价');
$gF = mkGoods($ss, 'QSS自营-低于保留价', 500);
$gH = mkGoods($ns, 'QSS非自营-低于保留价', 500);
$gI = mkGoods($ss, 'QSS自营-有已付款订单');
$gids = "$gA,$gB,$gC,$gD,$gE,$gF,$gH,$gI";

bid($gA, $vb, 120);
bid($gB, $rb2, 150);
$bidLow = bid($gC, $rb, 130, 50);   // 真实买家，冻结 50 保证金
$bidTop = bid($gC, $vb2, 140);      // 虚拟买家出价更高
bid($gD, $vb, 160);
bid($gF, $rb2, 200);                // 低于保留价 500
$bidH = bid($gH, $rb2, 200);        // 非自营，低于保留价 500
bid($gI, $vb, 120);
// 三件商品都挂上自动出价任务，验证流拍时任务是否被清掉
mkTask($gA); mkTask($gC); mkTask($gD); mkTask($gH); mkTask($gI);
// gI 上挂一张「已付款」的历史订单（正常业务不会出现，用来验证删除时的保护）
$pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,pay_status,pay_time,order_status,create_time,update_time) values('QSSPAID$T',$gI,'QSS自营-有已付款订单','',$ss,$rb2,120,1,$T,1,$T,$T)");
$paidOrderId = (int)$pdo->lastInsertId();

$asid = md5('qsettle' . $T);
$admin = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC);
unset($admin['password']);
file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $admin]));

try {
    echo "== 结算前 ==\n";
    ok('8 件都在拍卖中且已到截拍时间', (int)$pdo->query("select count(*) from goods where id in ($gids) and status=1 and end_time<=$T")->fetchColumn() === 8);

    $o = run();
    echo "== 自营店铺卖家：结算规则 ==\n";
    ok('虚拟买家最高 → 流拍，不生成订单', (int)g($gA)['status'] === 3 && orderOf($gA) === null && (int)g($gA)['winner_id'] === 0 && (float)g($gA)['final_price'] == 0, $o);
    ok('真实买家最高 → 成交，生成待付款订单', (int)g($gB)['status'] === 2 && orderOf($gB) && (int)orderOf($gB)['buyer_id'] === $rb2
        && (int)orderOf($gB)['pay_status'] === 0 && (float)orderOf($gB)['price'] == 150 && (int)g($gB)['winner_id'] === $rb2, $o);
    ok('虚拟最高 + 真实次高 → 仍然流拍，不让次高中标', (int)g($gC)['status'] === 3 && orderOf($gC) === null && (int)g($gC)['winner_id'] === 0, $o);
    $m = money($rb);
    ok('  真实买家的 50 保证金先退回了再删记录', (float)$m['balance'] == 50 && (float)$m['freeze_balance'] == 0, json_encode($m));
    ok('无人出价 → 流拍', (int)g($gE)['status'] === 3 && orderOf($gE) === null, $o);
    ok('真实买家最高但低于保留价 → 流拍（原有规则不变）', (int)g($gF)['status'] === 3 && orderOf($gF) === null, $o);

    echo "== 自营店铺卖家：流拍清理 ==\n";
    ok('虚拟中标流拍：出价记录删光', bidCount($gA) === 0 && bidCount($gC) === 0 && bidStatus($bidTop) === null && bidStatus($bidLow) === null,
        bidCount($gA) . ' / ' . bidCount($gC));
    ok('  自动出价任务删光', taskCount($gA) === 0 && taskCount($gC) === 0, taskCount($gA) . ' / ' . taskCount($gC));
    ok('  商品统计字段归零', (int)g($gA)['bid_count'] === 0 && (int)g($gA)['winner_id'] === 0 && (float)g($gA)['final_price'] == 0 && (int)g($gA)['order_id'] === 0);
    ok('低于保留价流拍：出价记录也删光', bidCount($gF) === 0);
    ok('成交的商品不清理：出价与订单都在', (int)g($gB)['status'] === 2 && bidCount($gB) === 1 && orderCount($gB) === 1);

    echo "== 已付款订单的保护 ==\n";
    ok('商品流拍了，但已付款的订单保留', (int)g($gI)['status'] === 3 && orderCount($gI) === 1
        && (int)$pdo->query("select pay_status from `order` where id=$paidOrderId")->fetchColumn() === 1);
    ok('  该商品的出价与任务照样清掉', bidCount($gI) === 0 && taskCount($gI) === 0);

    echo "== 非自营卖家：不清理 ==\n";
    ok('虚拟买家最高 → 照常成交', (int)g($gD)['status'] === 2 && orderOf($gD) && (int)orderOf($gD)['buyer_id'] === $vb && (int)g($gD)['winner_id'] === $vb, $o);
    ok('低于保留价流拍：出价记录保留并标为流拍（2）', (int)g($gH)['status'] === 3 && bidCount($gH) === 1 && bidStatus($bidH) === 2);
    ok('  自动出价任务也保留', taskCount($gH) === 1 && taskCount($gD) === 1);

    echo "== 命令输出 ==\n";
    ok('统计里成交 2 件、流拍 6 件', strpos($o, '成交 2') !== false && strpos($o, '流拍 6') !== false, $o);
    ok('单独提示自营拍品虚拟中标转流拍 3 件', strpos($o, '其中自营拍品虚拟买家中标转流拍 3') !== false, $o);

    echo "== 订单取消 → 商品回流拍时同样清理 ==\n";
    $ordB = orderOf($gB);
    mkTask($gB);
    $j = req($asid, '/admin1314/order/cancel', ['id' => $ordB['id']]);
    ok('后台取消未付款订单成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('  商品回到流拍', (int)g($gB)['status'] === 3 && (int)g($gB)['winner_id'] === 0 && (int)g($gB)['order_id'] === 0);
    ok('  自营卖家：出价记录 / 任务 / 这张已取消的订单都删掉', bidCount($gB) === 0 && taskCount($gB) === 0 && orderCount($gB) === 0);

    echo "== 非自营卖家的订单取消：只取消不删 ==\n";
    $ordD = orderOf($gD);
    $j = req($asid, '/admin1314/order/cancel', ['id' => $ordD['id']]);
    ok('取消成功，商品回流拍', ($j['code'] ?? 0) == 1 && (int)g($gD)['status'] === 3, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('  订单与出价记录都还在，订单状态为已取消', orderCount($gD) === 1 && bidCount($gD) === 1
        && (int)$pdo->query("select order_status from `order` where id={$ordD['id']}")->fetchColumn() === 4);
    ok('  自动出价任务保留', taskCount($gD) === 1);

    echo "== 卖家改成非自营后 ==\n";
    $gG = mkGoods($ss, 'QSS改非自营后-虚拟最高'); $gids .= ",$gG";
    bid($gG, $vb, 120);
    mkTask($gG);
    $pdo->exec("update user set is_self_shop=0 where id=$ss");
    $o = run();
    ok('同一个卖家改为非自营，虚拟买家又能中标', (int)g($gG)['status'] === 2 && orderOf($gG) && (int)orderOf($gG)['buyer_id'] === $vb, $o);
    ok('  输出里没有虚拟转流拍的提示', strpos($o, '虚拟买家中标转流拍') === false, $o);
} finally {
    $pdo->exec("delete from `order` where goods_id in ($gids)");
    $pdo->exec("delete from auto_bid where goods_id in ($gids)");
    $pdo->exec("delete from bid_record where goods_id in ($gids)");
    $pdo->exec("delete from goods where id in ($gids)");
    $pdo->exec("delete from balance_log where user_id in ($uids)");
    $pdo->exec("delete from sys_message where user_id in ($uids)");
    $pdo->exec("delete from admin_log where action like '%QSSPAID%' or action like '%取消未付款订单%' and create_time>=$T");
    $pdo->exec("delete from user where id in ($uids)");
    @unlink("$root/runtime/session/sess_$asid");
    echo "[cleanup] done\n";
}

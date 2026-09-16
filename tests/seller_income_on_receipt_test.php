<?php
/**
 * 卖家成交款改为「买家确认收货后」入账
 *  - 付款时卖家不入账（income_paid=0）
 *  - 买家确认收货 / 后台标记完成 / 代理后台标记完成 → 入账一次（income_paid=1、流水、站内信、total_sell+1）
 *  - 历史已入账订单（income_paid=1）确认收货不会二次入账
 *  - 后台取消未入账订单：只退买家，不扣卖家
 *  - 售后同意退款：已入账的才扣回卖家
 *  - 后台订单详情显示入账状态
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkUser($m, $nick, $pid = 0, $agent = 0, $balance = 1000) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,total_sell,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,$balance,0,1,1,$agent,0,2,0,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkOrder($no, $buyer, $seller, $payStatus, $orderStatus, $incomePaid = 0, $price = 200, $income = 180) {
    global $pdo, $T;
    $commission = $price - $income;
    $payTime = $payStatus == 1 ? $T : 0;
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('$no',0,'QA入账商品','',$seller,$buyer,$price,10,$commission,$income,$incomePaid,0,$payStatus,$payTime,$orderStatus,'QA收货人','13900000000','QA地址',$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $key = 'user') {
    global $root, $pdo;
    $sid = md5('inc' . $key . $id . microtime(true));
    $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u]));
    return $sid;
}
function req($sid, $m, $p, $d = null, $ajax = true) {
    $head = $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function u($id) { global $pdo; return $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function o($id) { global $pdo; return $pdo->query("select * from `order` where id=$id")->fetch(PDO::FETCH_ASSOC); }
function incomeLogs($uid) { global $pdo; return (int)$pdo->query("select count(*) from balance_log where user_id=$uid and type='income' and remark like '拍卖成交收入：%'")->fetchColumn(); }
function msgs($uid, $title) { global $pdo; return (int)$pdo->query("select count(*) from sys_message where user_id=$uid and title='$title'")->fetchColumn(); }

$S   = mkUser('19999990400', 'QA入账卖家');
$B   = mkUser('19999990401', 'QA入账买家');
$AG  = mkUser('19999990402', 'QA入账代理', 0, 1);
$S2  = mkUser('19999990403', 'QA团队卖家', $AG);
$B2  = mkUser('19999990404', 'QA团队买家', $AG);
$uids = "$S,$B,$AG,$S2,$B2";
$pdo->exec("insert into user_address(user_id,name,mobile,province,city,district,address,is_default,create_time) values($B,'QA收货人','13900000000','省','市','区','街道1号',1,$T)");
$addr = (int)$pdo->lastInsertId();

$o1 = mkOrder('QAINC0001', $B, $S, 0, 0);          // 待付款 → 走前台支付
$o2 = mkOrder('QAINC0002', $B, $S, 1, 2);          // 待收货 → 后台标记完成
$o3 = mkOrder('QAINC0003', $B2, $S2, 1, 2);        // 待收货（团队）→ 代理标记完成
$o4 = mkOrder('QAINC0004', $B, $S, 1, 2, 1);       // 历史订单：付款时已入账（income_paid=1）
$o5 = mkOrder('QAINC0005', $B, $S, 1, 1);          // 待发货 → 后台取消
$o6 = mkOrder('QAINC0006', $B, $S, 1, 3, 1);       // 已完成、已入账 → 售后退款
$oids = "$o1,$o2,$o3,$o4,$o5,$o6";
$pdo->exec("insert into after_sale(order_id,order_no,user_id,seller_id,goods_id,goods_title,price,reason,status,admin_note,create_time,handle_time) values($o6,'QAINC0006',$B,$S,0,'QA入账商品',200,'QA售后理由',0,'',$T,0)");
$as6 = (int)$pdo->lastInsertId();

$sb = sess($B); $sa = sess(0, 'admin'); $sag = sess($AG);

try {
    echo "== 付款时不入账 ==\n";
    [, , $j] = req($sb, 'POST', '/order/pay?id=' . $o1, ['address_id' => $addr]);
    $x = o($o1);
    ok('买家付款成功', ($j['code'] ?? 0) == 1 && (int)$x['pay_status'] === 1 && (int)$x['order_status'] === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('买家扣款 200', (float)u($B)['balance'] == 800, u($B)['balance']);
    ok('卖家余额未变、income_paid=0、无入账流水', (float)u($S)['balance'] == 1000 && (int)$x['income_paid'] === 0 && incomeLogs($S) === 0, json_encode([u($S)['balance'], $x['income_paid']]));

    echo "== 买家确认收货后入账 ==\n";
    $pdo->exec("update `order` set order_status=2, ship_company='顺丰', ship_no='SF1', ship_time=$T where id=$o1");
    [, , $j] = req($sb, 'POST', '/order/confirm', ['id' => $o1]);
    $x = o($o1); $s = u($S);
    ok('确认收货成功，订单完成', ($j['code'] ?? 0) == 1 && (int)$x['order_status'] === 3, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('卖家入账 180（成交价 200 − 佣金 20）', (float)$s['balance'] == 1180 && (int)$x['income_paid'] === 1, json_encode([$s['balance'], $x['income_paid']]));
    ok('写入成交收入流水与到账通知，total_sell+1', incomeLogs($S) === 1 && msgs($S, '成交款到账通知') === 1 && (int)$s['total_sell'] === 1, json_encode([incomeLogs($S), msgs($S, '成交款到账通知'), $s['total_sell']]));
    [, , $j] = req($sb, 'POST', '/order/confirm', ['id' => $o1]);
    ok('重复确认收货被拒，不二次入账', ($j['code'] ?? 1) == 0 && (float)u($S)['balance'] == 1180 && incomeLogs($S) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 后台标记完成入账 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/order/finish', ['id' => $o2]);
    $x = o($o2);
    ok('后台标记完成', ($j['code'] ?? 0) == 1 && (int)$x['order_status'] === 3 && (int)$x['income_paid'] === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('卖家再入账 180 → 1360', (float)u($S)['balance'] == 1360 && incomeLogs($S) === 2, u($S)['balance']);
    [, , $j] = req($sa, 'POST', '/admin1314/order/finish', ['id' => $o2]);
    ok('重复标记完成被拒', ($j['code'] ?? 1) == 0 && (float)u($S)['balance'] == 1360, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 代理后台标记完成入账 ==\n";
    [, , $j] = req($sag, 'POST', '/agent/order/finish', ['id' => $o3]);
    $x = o($o3);
    ok('代理标记完成，团队卖家入账 180', ($j['code'] ?? 0) == 1 && (int)$x['order_status'] === 3 && (int)$x['income_paid'] === 1 && (float)u($S2)['balance'] == 1180 && incomeLogs($S2) === 1, json_encode([$j, u($S2)['balance']], JSON_UNESCAPED_UNICODE));

    echo "== 历史订单不二次入账 ==\n";
    [, , $j] = req($sb, 'POST', '/order/confirm', ['id' => $o4]);
    ok('已入账（income_paid=1）的历史订单确认收货：完成但不再加钱', ($j['code'] ?? 0) == 1 && (int)o($o4)['order_status'] === 3 && (float)u($S)['balance'] == 1360 && incomeLogs($S) === 2, json_encode([$j, u($S)['balance']], JSON_UNESCAPED_UNICODE));

    echo "== 后台取消未入账订单 ==\n";
    $bBefore = (float)u($B)['balance'];
    [, , $j] = req($sa, 'POST', '/admin1314/order/cancel', ['id' => $o5]);
    $x = o($o5);
    ok('取消成功：订单已取消 / 已退款', ($j['code'] ?? 0) == 1 && (int)$x['order_status'] === 4 && (int)$x['pay_status'] === 2, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('买家全额退回 +200', (float)u($B)['balance'] == $bBefore + 200, u($B)['balance']);
    ok('卖家未入账 → 不扣回、无扣回流水', (float)u($S)['balance'] == 1360 && (int)$pdo->query("select count(*) from balance_log where user_id=$S and remark like '订单取消扣回成交收入：%'")->fetchColumn() === 0, u($S)['balance']);
    ok('返回文案不再提「卖家收入已扣回」', strpos((string)($j['msg'] ?? ''), '扣回') === false, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('卖家通知说明未入账无需扣回', (int)$pdo->query("select count(*) from sys_message where user_id=$S and title='订单取消通知' and content like '%尚未入账%'")->fetchColumn() === 1, '');

    echo "== 售后退款：已入账才扣回 ==\n";
    $bBefore = (float)u($B)['balance'];
    [, , $j] = req($sa, 'POST', '/admin1314/after_sale/handle', ['id' => $as6, 'action' => 'agree', 'note' => 'QA']);
    ok('同意退款：买家 +200，卖家已入账故扣回 180', ($j['code'] ?? 0) == 1 && (float)u($B)['balance'] == $bBefore + 200 && (float)u($S)['balance'] == 1180, json_encode([$j, u($B)['balance'], u($S)['balance']], JSON_UNESCAPED_UNICODE));

    echo "== 订单详情入账状态 ==\n";
    [, $html] = req($sa, 'GET', '/admin1314/order/detail?id=' . $o1, null, false);
    ok('已入账订单显示「已入账」', strpos($html, '已入账') !== false, '');
    $o7 = mkOrder('QAINC0007', $B, $S, 1, 1); $oids .= ",$o7";
    [, $html] = req($sa, 'GET', '/admin1314/order/detail?id=' . $o7, null, false);
    ok('未入账订单显示「待买家确认收货后入账」', strpos($html, '待买家确认收货后入账') !== false, '');
} finally {
    $pdo->exec("delete from after_sale where id=$as6");
    $pdo->exec("delete from `order` where id in ($oids)");
    $pdo->exec("delete from balance_log where user_id in ($uids)");
    $pdo->exec("delete from sys_message where user_id in ($uids)");
    $pdo->exec("delete from user_address where id=$addr");
    $pdo->exec("delete from user where id in ($uids)");
    foreach ([$sb, $sa, $sag] as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

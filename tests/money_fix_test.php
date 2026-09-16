<?php
$root = 'D:/phpstudy_pro/WWW/jp'; $php = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0, $virtual = 0, $bal = 1000) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,$bal,$seller,$seller,0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$seller = mk('19999990300', 'QA资金卖家', 1); $b1 = mk('19999990301', 'QA资金买家1'); $b2 = mk('19999990302', 'QA资金买家2'); $v1 = mk('12999990303', 'QA资金虚拟1', 0, 1); $v2 = mk('12999990304', 'QA资金虚拟2', 0, 1);
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
function mkGoods($s, $t, $end, $dep = 0) { global $pdo, $T, $cat; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($s,$cat,'$t','','[]',100,10,$dep,1,$T-600,$end,$T,$T)"); return (int)$pdo->lastInsertId(); }
function bid($g, $u, $p, $dep = 0) { global $pdo, $T; $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g,$u,$p,$dep,0,0,$T)"); $pdo->exec("update goods set bid_count=bid_count+1 where id=$g"); }
function sess($id, $key = 'user') { global $root, $pdo, $T; $sid = md5('mf' . $key . $id . $T); if ($key === 'admin') { $u = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); } else { $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); } file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
$sa = sess(1, 'admin'); $s1 = sess($b1);
function req($sid, $m, $p, $d = null) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); curl_close($ch); return json_decode($b, true); }
function run($cmd) { global $php, $root; return (string)shell_exec("cd /d " . str_replace('/', '\\', $root) . " && \"$php\" think $cmd 2>&1"); }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
function u($id) { global $pdo; return $pdo->query("select balance,freeze_balance from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
$goods = [];
try {
    echo "== 结算：虚拟会员不中标 ==\n";
    $gA = mkGoods($seller, 'QA结算A', $T - 30, 10); $goods[] = $gA;
    bid($gA, $b1, 100, 10); $pdo->exec("update user set balance=990,freeze_balance=10 where id=$b1");
    bid($gA, $b2, 110, 10); $pdo->exec("update user set balance=990,freeze_balance=10 where id=$b2");
    bid($gA, $v1, 120); bid($gA, $v2, 130);
    $o = run('settle'); $xa = g($gA); $order = $pdo->query("select * from `order` where goods_id=$gA")->fetch(PDO::FETCH_ASSOC);
    ok('最高价者（虚拟会员 130）中标，虚拟会员与真实会员规则一致', $xa['status'] == 2 && $xa['winner_id'] == $v2 && (float)$xa['final_price'] == 130 && $order && $order['buyer_id'] == $v2 && (float)$order['price'] == 130, $o . json_encode([$xa['status'], $xa['winner_id'], $xa['final_price']]));
    ok('  其余出价记录标记为退回，得标记录正确', (int)$pdo->query("select count(*) from bid_record where goods_id=$gA and user_id in ($v1,$b1,$b2) and status=2")->fetchColumn() == 3 && (int)$pdo->query("select count(*) from bid_record where goods_id=$gA and user_id=$v2 and is_winner=1")->fetchColumn() == 1);
    ok('  未中标的买家1、买家2 保证金都退回', (float)u($b1)['balance'] == 1000 && (float)u($b1)['freeze_balance'] == 0 && (float)u($b2)['balance'] == 1000 && (float)u($b2)['freeze_balance'] == 0);
    $o = run('settle'); ok('  再次结算无重复处理', (int)$pdo->query("select count(*) from `order` where goods_id=$gA")->fetchColumn() == 1 && (float)u($b1)['balance'] == 1000);
    $gB = mkGoods($seller, 'QA结算B', $T - 30); $goods[] = $gB; bid($gB, $v1, 100); bid($gB, $v2, 110);
    run('settle'); ok('只有虚拟出价时同样成交，虚拟会员中标', g($gB)['status'] == 2 && g($gB)['winner_id'] == $v2 && (int)$pdo->query("select count(*) from `order` where goods_id=$gB")->fetchColumn() == 1);

    echo "== 后台取消订单 ==\n";
    // 已付款：买家2 支付订单（走前台支付）
    $s2 = sess($v2);
    $pdo->exec("insert into user_address(user_id,name,mobile,province,city,district,address,is_default,create_time) values($v2,'收货人','13800000000','省','市','区','街道1号',1,$T)"); $addr = (int)$pdo->lastInsertId();
    $r = req($s2, 'POST', '/order/pay?id=' . $order['id'], ['address_id' => $addr]); ok('中标的虚拟会员支付订单成功', ($r['code'] ?? 0) == 1, json_encode($r, JSON_UNESCAPED_UNICODE));
    $r2 = req($s2, 'POST', '/order/pay?id=' . $order['id'], ['address_id' => $addr]); ok('  重复支付被拒', ($r2['code'] ?? 1) == 0, json_encode($r2, JSON_UNESCAPED_UNICODE));
    $sellerBal = (float)u($seller)['balance']; $buyerBal = (float)u($v2)['balance']; $od = $pdo->query("select * from `order` where id={$order['id']}")->fetch(PDO::FETCH_ASSOC);
    ok('  付款后卖家尚未入账（买家确认收货后才到账）', abs($sellerBal - 1000) < 0.01 && (int)$od['income_paid'] === 0, "seller=$sellerBal income_paid={$od['income_paid']}");
    $r = req($sa, 'POST', '/admin1314/order/cancel', ['id' => $order['id']]); $od2 = $pdo->query("select * from `order` where id={$order['id']}")->fetch(PDO::FETCH_ASSOC);
    ok('后台取消已付款订单：买家全额退回、卖家未入账不扣回、商品下架', ($r['code'] ?? 0) == 1 && $od2['order_status'] == 4 && $od2['pay_status'] == 2 && abs((float)u($v2)['balance'] - ($buyerBal + 130)) < 0.01 && abs((float)u($seller)['balance'] - 1000) < 0.01 && g($gA)['status'] == 4 && g($gA)['winner_id'] == 0, json_encode([$r, $od2['order_status'], $od2['pay_status'], u($v2), u($seller), g($gA)['status']], JSON_UNESCAPED_UNICODE));
    ok('  未入账不产生扣回流水，双方站内信已写', (int)$pdo->query("select count(*) from balance_log where user_id=$seller and remark like '订单取消扣回成交收入：%'")->fetchColumn() == 0 && (int)$pdo->query("select count(*) from sys_message where user_id in ($v2,$seller) and title='订单取消通知'")->fetchColumn() == 2);
    $r = req($sa, 'POST', '/admin1314/order/cancel', ['id' => $order['id']]); ok('  再次取消被拒', ($r['code'] ?? 1) == 0, json_encode($r, JSON_UNESCAPED_UNICODE));
    // 未付款：走 cancel_unpaid_order
    $gC = mkGoods($seller, 'QA结算C', $T - 30, 10); $goods[] = $gC; bid($gC, $b1, 100, 10); $pdo->exec("update user set balance=990,freeze_balance=10 where id=$b1");
    run('settle'); $oc = $pdo->query("select * from `order` where goods_id=$gC")->fetch(PDO::FETCH_ASSOC); ok('C 成交生成待付款订单', $oc && $oc['pay_status'] == 0, json_encode($oc));
    $mode = $pdo->query("select value from setting where name='order_timeout_deposit'")->fetchColumn();
    $r = req($sa, 'POST', '/admin1314/order/cancel', ['id' => $oc['id']]); $oc2 = $pdo->query("select * from `order` where id={$oc['id']}")->fetch(PDO::FETCH_ASSOC);
    ok("后台取消未付款订单：订单已取消、商品回到流拍、保证金按设置（{$mode}）处理", ($r['code'] ?? 0) == 1 && $oc2['order_status'] == 4 && g($gC)['status'] == 3 && (float)u($b1)['freeze_balance'] == 0, json_encode([$r, $oc2['order_status'], g($gC)['status'], u($b1)], JSON_UNESCAPED_UNICODE));
    echo "== 买家主动取消备注 ==\n";
    $gD = mkGoods($seller, 'QA结算D', $T - 30, 10); $goods[] = $gD; bid($gD, $b1, 100, 10); $pdo->exec("update user set balance=990,freeze_balance=10 where id=$b1");
    run('settle'); $od = $pdo->query("select * from `order` where goods_id=$gD")->fetch(PDO::FETCH_ASSOC);
    $r = req($s1, 'POST', '/order/cancel', ['id' => $od['id']]); $rm = $pdo->query("select remark from balance_log where user_id=$b1 order by id desc limit 1")->fetchColumn();
    ok('买家主动取消：流水备注写「买家取消订单」而非「超时」', ($r['code'] ?? 0) == 1 && ($mode === 'refund_buyer' ? strpos($rm, '订单取消，保证金退回') !== false : strpos($rm, '买家取消订单') !== false), json_encode([$r, $rm], JSON_UNESCAPED_UNICODE));
    echo "== 后台手动出价给真实买家冻结保证金 ==\n";
    $gE = mkGoods($seller, 'QA结算E', $T + 7200, 50); $goods[] = $gE;
    $pdo->exec("update user set balance=30,freeze_balance=0 where id=$b2");
    $r = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $gE, 'user_id' => $b2, 'price' => 100]); ok('真实买家余额不足保证金时后台出价被拒', ($r['code'] ?? 1) == 0 && strpos($r['msg'] ?? '', '保证金') !== false, json_encode($r, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update user set balance=1000,freeze_balance=0 where id=$b2");
    $r = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $gE, 'user_id' => $b2, 'price' => 100]); $bd = $pdo->query("select deposit from bid_record where goods_id=$gE and user_id=$b2")->fetchColumn();
    ok('后台给真实买家出价：冻结保证金 50', ($r['code'] ?? 0) == 1 && (float)$bd == 50 && (float)u($b2)['balance'] == 950 && (float)u($b2)['freeze_balance'] == 50, json_encode([$r, $bd, u($b2)], JSON_UNESCAPED_UNICODE));
    $r = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $gE, 'user_id' => $v1, 'price' => 110]); $bd = $pdo->query("select deposit from bid_record where goods_id=$gE and user_id=$v1")->fetchColumn();
    ok('后台给虚拟会员出价：不冻结', ($r['code'] ?? 0) == 1 && (float)$bd == 0, json_encode([$r, $bd], JSON_UNESCAPED_UNICODE));
    $r = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $gE, 'user_id' => $seller, 'price' => 120]); ok('后台不能给卖家本人出价', ($r['code'] ?? 1) == 0 && strpos($r['msg'] ?? '', '卖家') !== false, json_encode($r, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update goods set end_time=$T+100, delay_seconds=600 where id=$gE");
    $r = req($sa, 'POST', '/admin1314/bid/add', ['goods_id' => $gE, 'user_id' => $b1, 'price' => 120]); $e = (int)g($gE)['end_time'];
    ok('后台出价触发延时规则', ($r['code'] ?? 0) == 1 && $e >= $T + 590, json_encode([$r, $e - $T], JSON_UNESCAPED_UNICODE));
} finally {
    $ids = implode(',', $goods ?: [0]);
    $pdo->exec("delete from after_sale where goods_id in ($ids)"); $pdo->exec("delete from `order` where goods_id in ($ids)"); $pdo->exec("delete from bid_record where goods_id in ($ids)"); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from browse_history where goods_id in ($ids)");
    $pdo->exec("delete from balance_log where user_id in ($seller,$b1,$b2,$v1,$v2)"); $pdo->exec("delete from sys_message where user_id in ($seller,$b1,$b2,$v1,$v2)"); $pdo->exec("delete from user_address where user_id in ($b1,$b2,$v2)"); $pdo->exec("delete from admin_log where action like '%QA结算%'");
    $pdo->exec("delete from user where id in ($seller,$b1,$b2,$v1,$v2)"); foreach (glob("$root/runtime/session/sess_" . substr(md5('mf'), 0, 0) . '*') as $f) {} @unlink("$root/runtime/session/sess_$sa"); @unlink("$root/runtime/session/sess_$s1"); if (isset($s2)) @unlink("$root/runtime/session/sess_$s2"); echo "[cleanup] done\n";
}

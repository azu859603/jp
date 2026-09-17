<?php
/**
 * 后台 / 代理后台「完成支付」（代买家用余额支付待付款订单）
 *  - 弹窗信息：应付、买家余额、默认收货地址
 *  - 余额不足 → 失败且订单/余额不变
 *  - 余额充足 → 扣余额、抵扣冻结保证金、写流水、订单转待发货并写入收货信息、操作日志
 *  - 非待付款订单不能操作；代理只能操作团队卖家的订单
 *  - 列表页带「完成支付」按钮
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0, $balance = 0, $freeze = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,$balance,$freeze,$seller,$seller,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('ap' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function mkOrder($no, $g, $seller, $buyer, $price, $deposit) { global $pdo, $T; $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('$no',$g,'QA代付拍品','',$seller,$buyer,$price,10," . ($price * 0.1) . "," . ($price * 0.9) . ",0,$deposit,0,0,0,'','','',$T,$T)"); return (int)$pdo->lastInsertId(); }
function order($id) { global $pdo; return $pdo->query("select * from `order` where id=$id")->fetch(PDO::FETCH_ASSOC); }
function user($id) { global $pdo; return $pdo->query("select balance,freeze_balance,total_buy from user where id=$id")->fetch(PDO::FETCH_ASSOC); }

$AG = mk('19999990600', 'QA代付代理', 0, 1);
$S  = mk('19999990601', 'QA代付卖家', $AG, 0, 1);
$S2 = mk('19999990602', 'QA团队外卖家', 0, 0, 1);
$B  = mk('19999990603', 'QA代付买家', 0, 0, 0, 100, 50);       // 余额 100，冻结 50
$pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('QA代付拍品','','','',1,$S,100,10,0,50,$T-7200,$T-60,2,1,0,$T,$T)");
$G = (int)$pdo->lastInsertId();
$pdo->exec("insert into user_address(user_id,name,mobile,province,city,district,address,is_default,create_time,update_time) values($B,'张三','13900001111','广东省','深圳市','南山区','科技园1号',1,$T,$T)");
$O1 = mkOrder('QAPAY0001', $G, $S, $B, 500, 50);   // 应付 450，需扣余额 450 > 100 → 余额不足
$O2 = mkOrder('QAPAY0002', $G, $S, $B, 120, 50);   // 应付 70，冻结抵 50，扣余额 70 ≤ 100 → 成功
$O3 = mkOrder('QAPAY0003', $G, $S2, $B, 60, 0);    // 团队外卖家：代理不可操作
// 虚拟会员买家：余额 0、冻结 20（模拟后台代出价冻结的保证金）
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('12999990604','x','QA虚拟买家','qa0604',0,1,0,20,0,0,0,1,0,$T,$T,$T)");
$V  = (int)$pdo->lastInsertId();
$O4 = mkOrder('QAPAY0004', $G, $S, $V, 300, 20);   // 虚拟买家：不真实扣款
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 弹窗信息 ==\n";
    [, , $j] = req($sa, 'GET', "/admin1314/order/payInfo?id=$O2");
    $d = $j['data'] ?? [];
    ok('后台 payInfo：应付 70、需扣余额 70、买家余额 100、默认地址带出', ($j['code'] ?? 0) == 1 && $d['pay_amount'] == '70.00' && $d['balance_need'] == '70.00' && $d['buyer_balance'] == '100.00' && $d['enough'] && $d['ship_name'] === '张三' && strpos($d['ship_address'], '南山区') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', "/admin1314/order/payInfo?id=$O1");
    ok('后台 payInfo：应付 450 → 标记余额不足', ($j['code'] ?? 0) == 1 && empty($j['data']['enough']), json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 余额不足 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/order/pay', ['id' => $O1, 'ship_name' => '张三', 'ship_mobile' => '13900001111', 'ship_address' => 'X']);
    ok('余额不足被拒，订单与余额不变', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '余额不足') !== false && order($O1)['pay_status'] == 0 && user($B)['balance'] == 100, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/order/pay', ['id' => $O2, 'ship_name' => '', 'ship_mobile' => '', 'ship_address' => '']);
    ok('收货信息为空被拒', ($j['code'] ?? 1) == 0 && order($O2)['pay_status'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 代理后台 ==\n";
    [, , $j] = req($sg, 'POST', '/agent/order/pay', ['id' => $O3, 'ship_name' => '张三', 'ship_mobile' => '13900001111', 'ship_address' => 'X']);
    ok('代理不能操作团队外卖家的订单', ($j['code'] ?? 1) != 1 && order($O3)['pay_status'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/order/pay', ['id' => $O2, 'ship_name' => '张三', 'ship_mobile' => '13900001111', 'ship_address' => '广东省 深圳市 南山区 科技园1号']);
    $o = order($O2); $u = user($B);
    ok('代理完成支付：订单转待发货、已支付、收货信息写入', ($j['code'] ?? 0) == 1 && $o['pay_status'] == 1 && $o['order_status'] == 1 && $o['pay_time'] > 0 && $o['ship_name'] === '张三' && strpos($o['ship_address'], '科技园') !== false, json_encode([$j, $o['pay_status'], $o['order_status']], JSON_UNESCAPED_UNICODE));
    ok('买家余额 100→30、冻结 50→0、total_buy +1', $u['balance'] == 30 && $u['freeze_balance'] == 0 && $u['total_buy'] == 1, json_encode($u));
    $log = $pdo->query("select type,amount,balance,remark from balance_log where user_id=$B order by id desc limit 1")->fetch(PDO::FETCH_ASSOC);
    ok('流水：pay -70，余额 30，备注含订单号与「代理代付」', $log && $log['type'] === 'pay' && (float)$log['amount'] == -70 && (float)$log['balance'] == 30 && strpos($log['remark'], 'QAPAY0002') !== false && strpos($log['remark'], '代理代付') !== false, json_encode($log, JSON_UNESCAPED_UNICODE));
    ok('代理操作日志已记录（agent_log）', (int)$pdo->query("select count(*) from agent_log where action like '%完成支付%QAPAY0002%'")->fetchColumn() === 1);
    [, , $j] = req($sa, 'POST', '/admin1314/order/pay', ['id' => $O2, 'ship_name' => '张三', 'ship_mobile' => '13900001111', 'ship_address' => 'X']);
    ok('已支付的订单再点完成支付被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '待付款') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 虚拟会员买家：不真实扣款 ==\n";
    [, , $j] = req($sa, 'GET', "/admin1314/order/payInfo?id=$O4");
    ok('payInfo 标记 is_virtual=1 且 enough=true（余额 0 也可完成）', ($j['code'] ?? 0) == 1 && (int)$j['data']['is_virtual'] === 1 && !empty($j['data']['enough']), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/order/pay', ['id' => $O4, 'ship_name' => '', 'ship_mobile' => '', 'ship_address' => '']);
    ok('虚拟会员不填地址同样被拒（卖家发货要看到地址）', ($j['code'] ?? 1) == 0 && order($O4)['pay_status'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/order/pay', ['id' => $O4, 'ship_name' => '王五', 'ship_mobile' => '13700003333', 'ship_address' => '北京市 朝阳区 建国路 8 号']);
    $o = order($O4); $u = user($V);
    ok('虚拟会员填地址后完成支付（未扣款）：订单已支付/待发货，收货信息为填写值', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '未扣款') !== false && $o['pay_status'] == 1 && $o['order_status'] == 1 && $o['ship_name'] === '王五' && strpos($o['ship_address'], '建国路') !== false, json_encode([$j, $o['pay_status'], $o['order_status'], $o['ship_address']], JSON_UNESCAPED_UNICODE));
    ok('虚拟会员余额不变（0）、冻结的 20 解冻回余额、total_buy +1', $u['balance'] == 20 && $u['freeze_balance'] == 0 && $u['total_buy'] == 1, json_encode($u));
    $log = $pdo->query("select type,amount,remark from balance_log where user_id=$V order by id desc limit 1")->fetch(PDO::FETCH_ASSOC);
    ok('虚拟会员流水只有保证金解冻，没有 pay 扣款', $log && $log['type'] === 'refund' && (float)$log['amount'] == 20 && strpos($log['remark'], '虚拟会员完成支付') !== false && (int)$pdo->query("select count(*) from balance_log where user_id=$V and type='pay'")->fetchColumn() === 0, json_encode($log, JSON_UNESCAPED_UNICODE));

    echo "== 列表页 ==\n";
    foreach ([['主后台', $sa, '/admin1314'], ['代理后台', $sg, '/agent']] as [$tag, $sid, $pre]) {
        [$c, $h] = req($sid, 'GET', "$pre/order/index", null, false);
        ok("$tag 列表页含「完成支付」按钮、弹窗与虚拟会员标注", $c == 200 && strpos($h, 'openPay(') !== false && strpos($h, 'id="payMask"') !== false && strpos($h, ">完成支付</a>") !== false && strpos($h, '（虚拟会员）') !== false, "HTTP $c");
        [, , $j] = req($sid, 'GET', "$pre/order/index?page=1&limit=50&keyword=QAPAY0001");
        ok("$tag 列表数据含待付款订单的 pay_status=0，真实买家 buyer_virtual=0", isset($j['data'][0]) && (int)$j['data'][0]['pay_status'] === 0 && (int)$j['data'][0]['buyer_virtual'] === 0, json_encode($j['data'][0] ?? null, JSON_UNESCAPED_UNICODE));
        [, , $j] = req($sid, 'GET', "$pre/order/index?page=1&limit=50&keyword=QAPAY0004");
        ok("$tag 虚拟买家订单 buyer_virtual=1", isset($j['data'][0]) && (int)$j['data'][0]['buyer_virtual'] === 1, json_encode($j['data'][0] ?? null, JSON_UNESCAPED_UNICODE));
    }
} finally {
    $pdo->exec("delete from `order` where id in ($O1,$O2,$O3,$O4)");
    $pdo->exec("delete from balance_log where user_id in ($B,$V)");
    $pdo->exec("delete from admin_log where action like '%QAPAY000%'");
    $pdo->exec("delete from agent_log where action like '%QAPAY000%'");
    $pdo->exec("delete from user_address where user_id=$B");
    $pdo->exec("delete from goods where id=$G");
    $pdo->exec("delete from user where id in ($AG,$S,$S2,$B,$V)");
    foreach ([$sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

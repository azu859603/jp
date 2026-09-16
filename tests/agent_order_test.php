<?php
/**
 * 代理后台 - 订单管理 / 售后管理 测试
 * 覆盖：数据范围隔离、代发货权限、状态流转、售后退款资金流向
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function mk($m, $nick, $pid = 0, $agent = 0, $balance = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,$balance,0,1,1,$agent,0,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkOrder($no, $buyer, $seller, $payStatus, $orderStatus, $price = 100, $income = 90, $incomePaid = 0) {
    global $pdo, $T;
    $commission = $price - $income;
    $payTime = $payStatus == 1 ? $T : 0;
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('$no',0,'QA代理订单商品','',$seller,$buyer,$price,10,$commission,$income,$incomePaid,10,$payStatus,$payTime,$orderStatus,'QA收货人','13900000000','QA地址',$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkSale($orderId, $no, $buyer, $seller, $price) {
    global $pdo, $T;
    $pdo->exec("insert into after_sale(order_id,order_no,user_id,seller_id,goods_id,goods_title,price,reason,status,admin_note,create_time,handle_time) values($orderId,'$no',$buyer,$seller,0,'QA代理订单商品',$price,'QA售后理由',0,'',$T,0)");
    return (int)$pdo->lastInsertId();
}
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function u($id) { global $pdo; return $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function o($id) { global $pdo; return $pdo->query("select * from `order` where id=$id")->fetch(PDO::FETCH_ASSOC); }
function sale($id) { global $pdo; return $pdo->query("select * from after_sale where id=$id")->fetch(PDO::FETCH_ASSOC); }
function idsOf($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
function req($m, $p, $d = null, $ajax = true) {
    global $gsid;
    $head = $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'];
    if ($m === 'POST') {
        $head[] = 'Origin: http://localhost';
        $head[] = 'Referer: http://localhost/agent/order/index';
    }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 0,
        CURLOPT_HTTPHEADER     => $head,
        CURLOPT_COOKIE         => 'PHPSESSID=' . $gsid,
    ]);
    if ($m === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d));
    }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}

$ag = mk('19999990360', 'QA代理订单', 0, 1);
$b1 = mk('19999990361', 'QA团队买家', $ag);
$s1 = mk('19999990362', 'QA团队卖家', $ag, 0, 500);
$b2 = mk('19999990363', 'QA外部买家');
$s2 = mk('19999990364', 'QA外部卖家', 0, 0, 500);
$s3 = mk('19999990365', 'QA穷卖家', $ag, 0, 0);
$uids = "$ag,$b1,$s1,$b2,$s2,$s3";

$o1 = mkOrder('QAAG0001', $b1, $s1, 1, 1);           // 团队买家 + 团队卖家，待发货
$o2 = mkOrder('QAAG0002', $b1, $s2, 1, 1);           // 团队买家 + 外部卖家，待发货
$o3 = mkOrder('QAAG0003', $b2, $s2, 1, 1);           // 完全团队外
$o4 = mkOrder('QAAG0004', $b1, $s1, 1, 3, 100, 90, 1); // 已完成、成交款已入账，用于售后
$o5 = mkOrder('QAAG0005', $b2, $s2, 1, 3, 100, 90, 1); // 团队外已完成、已入账，用于售后
$o6 = mkOrder('QAAG0006', $b1, $s3, 1, 3, 100, 90, 1); // 已入账但卖家余额不足
$oids = "$o1,$o2,$o3,$o4,$o5,$o6";

$a1 = mkSale($o4, 'QAAG0004', $b1, $s1, 100);
$a2 = mkSale($o5, 'QAAG0005', $b2, $s2, 100);
$a3 = mkSale($o6, 'QAAG0006', $b1, $s3, 100);
$a4 = mkSale($o2, 'QAAG0002', $b1, $s2, 100);   // 团队买家在外部店铺的售后：不属于本代理
$aids = "$a1,$a2,$a3,$a4";

$gsid = md5('agord' . $T);
$gu = $pdo->query("select * from user where id=$ag")->fetch(PDO::FETCH_ASSOC);
unset($gu['password']);
file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));

try {
    echo "== 菜单与页面 ==\n";
    [$c, $b] = req('GET', '/agent/order/index', null, false);
    ok('订单列表页可访问且左侧含菜单', $c == 200 && strpos($b, '订单管理') !== false && strpos($b, '/agent/after_sale/index') !== false, "HTTP $c");
    [$c, $b] = req('GET', '/agent/after_sale/index', null, false);
    ok('售后管理页可访问', $c == 200 && strpos($b, '售后管理') !== false, "HTTP $c");

    echo "== 订单列表数据范围 ==\n";
    [, , $j] = req('GET', '/agent/order/index?page=1&limit=50&keyword=QAAG');
    $r = idsOf($j);
    ok('只显示团队卖家的订单（团队买家在外部店铺的订单不含）', in_array($o1, $r) && !in_array($o2, $r) && !in_array($o3, $r) && !in_array($o5, $r), json_encode($r));
    $row1 = null;
    foreach (($j['data'] ?? []) as $x) {
        if ($x['id'] == $o1) $row1 = $x;
    }
    ok('列表返回买卖双方手机号', $row1 && $row1['buyer_mobile'] == '19999990361' && $row1['seller_mobile'] == '19999990362', json_encode($row1, JSON_UNESCAPED_UNICODE));

    echo "== 订单搜索与筛选 ==\n";
    [, , $j] = req('GET', '/agent/order/index?page=1&limit=50&keyword=19999990362');
    ok('按卖家手机号搜索', in_array($o1, idsOf($j)) && !in_array($o2, idsOf($j)), json_encode(idsOf($j)));
    [, , $j] = req('GET', '/agent/order/index?page=1&limit=50&keyword=QAAG&order_status=3');
    $r = idsOf($j);
    ok('按订单状态筛选', in_array($o4, $r) && in_array($o6, $r) && !in_array($o1, $r), json_encode($r));

    echo "== 订单详情越权 ==\n";
    [$c, $b] = req('GET', '/agent/order/detail?id=' . $o3, null, false);
    ok('团队外订单详情被拦截（302）', $c == 302 && strpos((string)$b, 'QAAG0003') === false, "HTTP $c");
    [$c, $b] = req('GET', '/agent/order/detail?id=' . $o1, null, false);
    ok('团队订单详情可访问并显示发货按钮', $c == 200 && strpos($b, 'QAAG0001') !== false && strpos($b, '代卖家发货') !== false, "HTTP $c");
    [$c, $b] = req('GET', '/agent/order/detail?id=' . $o2, null, false);
    ok('团队买家在外部店铺的订单详情被拦截（302）', $c == 302, "HTTP $c");

    echo "== 代发货 ==\n";
    [, , $j] = req('POST', '/agent/order/ship', ['id' => $o3, 'company' => '顺丰', 'ship_no' => 'SF001']);
    ok('团队外订单不能发货', ($j['code'] ?? 1) == 0 && o($o3)['order_status'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/ship', ['id' => $o2, 'company' => '顺丰', 'ship_no' => 'SF002']);
    ok('团队买家在外部店铺的订单不能代发货（不属于团队）', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '不属于') !== false && o($o2)['order_status'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/ship', ['id' => $o1, 'company' => '', 'ship_no' => 'SF003']);
    ok('快递公司为空被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/ship', ['id' => $o4, 'company' => '顺丰', 'ship_no' => 'SF004']);
    ok('非待发货状态不能发货', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/ship', ['id' => $o1, 'company' => '顺丰速运', 'ship_no' => 'SF0001']);
    $x = o($o1);
    ok('团队卖家订单发货成功', ($j['code'] ?? 0) == 1 && $x['order_status'] == 2 && $x['ship_no'] == 'SF0001' && $x['ship_company'] == '顺丰速运', json_encode([$j, $x['order_status']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/ship', ['id' => $o1, 'company' => '顺丰速运', 'ship_no' => 'SF0002']);
    ok('重复发货被拒', ($j['code'] ?? 1) == 0 && o($o1)['ship_no'] == 'SF0001', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, $b] = req('GET', '/agent/order/ship?id=' . $o1);
    ok('GET 方式调用发货被拒', strpos((string)$b, '请求方式错误') !== false, mb_substr((string)$b, 0, 120));

    echo "== 标记完成 ==\n";
    [, , $j] = req('POST', '/agent/order/finish', ['id' => $o3]);
    ok('团队外订单不能标记完成', ($j['code'] ?? 1) == 0 && o($o3)['order_status'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/finish', ['id' => $o4]);
    ok('非待收货订单不能标记完成', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/finish', ['id' => $o1]);
    $x = o($o1);
    ok('待收货订单标记完成成功', ($j['code'] ?? 0) == 1 && $x['order_status'] == 3 && $x['finish_time'] > 0, json_encode([$j, $x['order_status']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/order/finish', ['id' => $o1]);
    ok('重复标记完成被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 售后列表数据范围 ==\n";
    [, , $j] = req('GET', '/agent/after_sale/index?page=1&limit=50&keyword=QAAG');
    $r = idsOf($j);
    ok('只显示团队卖家的售后单（团队买家在外部店铺的不含）', in_array($a1, $r) && in_array($a3, $r) && !in_array($a2, $r) && !in_array($a4, $r), json_encode($r));
    [, , $jx] = req('POST', '/agent/after_sale/handle', ['id' => $a4, 'action' => 'agree', 'note' => 'QA']);
    ok('团队买家在外部店铺的售后不能处理', ($jx['code'] ?? 1) == 0 && sale($a4)['status'] == 0, json_encode($jx, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('GET', '/agent/after_sale/index?page=1&limit=50&status=1');
    ok('状态筛选生效（此时尚无已同意）', !in_array($a1, idsOf($j)), json_encode(idsOf($j)));
    [$c, $b] = req('GET', '/agent/after_sale/detail?id=' . $a2, null, false);
    ok('团队外售后详情被拦截（302）', $c == 302, "HTTP $c");
    [$c, $b] = req('GET', '/agent/after_sale/detail?id=' . $a1, null, false);
    ok('团队售后详情可访问', $c == 200 && strpos($b, 'QA售后理由') !== false && strpos($b, '同意退款') !== false, "HTTP $c");

    echo "== 售后处理 ==\n";
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a2, 'action' => 'agree', 'note' => 'QA']);
    ok('团队外售后不能处理', ($j['code'] ?? 1) == 0 && sale($a2)['status'] == 0 && (float)u($b2)['balance'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a1, 'action' => 'reject', 'note' => '']);
    ok('驳回未填备注被拒', ($j['code'] ?? 1) == 0 && sale($a1)['status'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a1, 'action' => 'xxx']);
    ok('非法操作类型被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a3, 'action' => 'agree', 'note' => 'QA']);
    ok('卖家余额不足时拒绝退款且不动账', ($j['code'] ?? 1) == 0 && sale($a3)['status'] == 0 && (float)u($b1)['balance'] == 0 && (float)u($s3)['balance'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    $b1Before = (float)u($b1)['balance'];
    $s1Before = (float)u($s1)['balance'];
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a1, 'action' => 'agree', 'note' => 'QA同意退款']);
    $x  = sale($a1);
    $od = o($o4);
    ok('同意退款成功', ($j['code'] ?? 0) == 1 && $x['status'] == 1 && $x['admin_note'] == 'QA同意退款' && $x['handle_time'] > 0, json_encode([$j, $x['status']], JSON_UNESCAPED_UNICODE));
    ok('买家余额 +100', (float)u($b1)['balance'] == $b1Before + 100, u($b1)['balance']);
    ok('卖家余额 -90（扣回成交收入）', (float)u($s1)['balance'] == $s1Before - 90, u($s1)['balance']);
    ok('订单标记为已退款', $od['pay_status'] == 2 && $od['order_status'] == 3, json_encode([$od['pay_status'], $od['order_status']]));
    $logs = $pdo->query("select user_id,type,amount from balance_log where user_id in ($b1,$s1) and type='refund'")->fetchAll(PDO::FETCH_ASSOC);
    ok('写入买卖双方退款流水', count($logs) == 2, json_encode($logs, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a1, 'action' => 'agree', 'note' => 'QA']);
    ok('重复处理被拒且不重复退款', ($j['code'] ?? 1) == 0 && (float)u($b1)['balance'] == $b1Before + 100, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 驳回流程 ==\n";
    $b1Now = (float)u($b1)['balance'];
    [, , $j] = req('POST', '/agent/after_sale/handle', ['id' => $a3, 'action' => 'reject', 'note' => 'QA驳回原因']);
    $x = sale($a3);
    ok('驳回成功且不发生资金变动', ($j['code'] ?? 0) == 1 && $x['status'] == 2 && $x['admin_note'] == 'QA驳回原因' && (float)u($b1)['balance'] == $b1Now, json_encode([$j, $x['status']], JSON_UNESCAPED_UNICODE));
    ok('驳回后订单恢复已完成', o($o6)['order_status'] == 3, o($o6)['order_status']);
} finally {
    $pdo->exec("delete from after_sale where id in ($aids)");
    $pdo->exec("delete from `order` where id in ($oids)");
    $pdo->exec("delete from balance_log where user_id in ($uids)");
    $pdo->exec("delete from sys_message where user_id in ($uids)");
    $pdo->exec("delete from user where id in ($uids)");
    @unlink("$root/runtime/session/sess_$gsid");
    echo "[cleanup] done\n";
}

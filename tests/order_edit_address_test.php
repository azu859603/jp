<?php
/**
 * 后台 / 代理后台订单列表「修改地址」：
 *  - 只有已支付且待发货 / 待收货的订单能改；未付款、已完成、已取消一律拒绝
 *  - 改完写操作日志（含修改前 → 修改后）；不给卖家发站内信
 *  - 代理只能改自己团队卖家的订单
 *  - 空值、超长、无变化都会被拒
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('ea' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function addr($id) { global $pdo; $r = $pdo->query("select ship_name,ship_mobile,ship_address from `order` where id=$id")->fetch(PDO::FETCH_ASSOC); return $r ? implode(' ', $r) : ''; }

$pdo->exec("delete from user where mobile in ('19999990810','19999990811','19999990812','19999990813')");
$AG = mk('19999990810', 'QA改址代理', 0, 1);
$S  = mk('19999990811', 'QA改址卖家', $AG, 0, 1);
$B  = mk('19999990812', 'QA改址买家', $AG);
$SX = mk('19999990813', 'QA改址外部卖家', 0, 0, 1);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$O = [];
// [订单号后缀 => [卖家, pay_status, order_status]]
foreach ([1 => [$S, 1, 1], 2 => [$S, 1, 2], 3 => [$S, 0, 0], 4 => [$S, 1, 3], 5 => [$S, 1, 4], 6 => [$SX, 1, 1]] as $i => [$sid, $pay, $ost]) {
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($sid,$cat,'QA改址拍品$i','','[]',100,10,0,2,$T-7200,$T-3600,$T,$T)");
    $g = (int)$pdo->lastInsertId();
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,seller_id,buyer_id,price,deposit,seller_income,pay_status,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QAADDR000$i',$g,'QA改址拍品$i',$sid,$B,120,0,120,$pay,$ost,'旧收货人','13900001111','旧地址',$T,$T)");
    $O[$i] = (int)$pdo->lastInsertId();
}
$new = ['ship_name' => '新收货人', 'ship_mobile' => '13900002222', 'ship_address' => '上海市浦东新区世纪大道1号'];
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 页面 ==\n";
    foreach ([['主后台', $sa, '/admin1314/order/index'], ['代理后台', $sg, '/agent/order/index']] as [$tag, $sid, $url]) {
        [$c, $h] = req($sid, 'GET', $url, null, false);
        ok("$tag 列表有「修改地址」按钮与弹窗", $c == 200 && strpos($h, 'id="addrMask"') !== false && strpos($h, 'onclick="openAddr(') !== false
            && strpos($h, "o.pay_status == 1 && (o.order_status == 1 || o.order_status == 2)") !== false, "HTTP $c");
    }

    echo "== 主后台：待发货订单 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/order/editAddress', ['id' => $O[1]] + $new);
    ok('修改成功，库里已是新地址', ($j['code'] ?? 0) == 1 && addr($O[1]) === '新收货人 13900002222 上海市浦东新区世纪大道1号', json_encode($j, JSON_UNESCAPED_UNICODE) . ' ' . addr($O[1]));
    $log = (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('操作日志含订单号与修改前后', strpos($log, '修改订单收货地址：QAADDR0001') === 0 && strpos($log, '旧收货人 13900001111 旧地址 → 新收货人') !== false, $log);
    ok('不给卖家发站内信', (int)$pdo->query("select count(*) from sys_message where user_id=$S")->fetchColumn() === 0);
    [, , $j] = req($sa, 'POST', '/admin1314/order/editAddress', ['id' => $O[1]] + $new);
    ok('重复提交同样的地址被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '没有变化') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/order/editAddress', ['id' => $O[1], 'ship_name' => '', 'ship_mobile' => '13900003333', 'ship_address' => 'x']);
    ok('收货人为空被拒，地址不变', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '请填写') !== false && addr($O[1]) === '新收货人 13900002222 上海市浦东新区世纪大道1号', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/order/editAddress', ['id' => $O[1], 'ship_name' => '张三', 'ship_mobile' => '13900003333', 'ship_address' => str_repeat('长', 256)]);
    ok('地址超长被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '长度') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 主后台：待收货订单 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/order/editAddress', ['id' => $O[2]] + $new);
    ok('待收货（已发货）也能改', ($j['code'] ?? 0) == 1 && addr($O[2]) === '新收货人 13900002222 上海市浦东新区世纪大道1号', json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('已发货订单同样不发站内信', (int)$pdo->query("select count(*) from sys_message where user_id=$S")->fetchColumn() === 0);

    echo "== 不可修改的状态 ==\n";
    foreach ([3 => '未支付', 4 => '已完成', 5 => '已取消'] as $i => $what) {
        [, , $j] = req($sa, 'POST', '/admin1314/order/editAddress', ['id' => $O[$i]] + $new);
        ok("$what 的订单被拒且地址不变", ($j['code'] ?? 1) == 0 && addr($O[$i]) === '旧收货人 13900001111 旧地址', json_encode($j, JSON_UNESCAPED_UNICODE) . ' ' . addr($O[$i]));
    }

    echo "== 代理后台 ==\n";
    [, , $j] = req($sg, 'POST', '/agent/order/editAddress', ['id' => $O[1], 'ship_name' => '代理改的人', 'ship_mobile' => '13900004444', 'ship_address' => '北京市朝阳区']);
    ok('代理可改团队卖家的订单', ($j['code'] ?? 0) == 1 && addr($O[1]) === '代理改的人 13900004444 北京市朝阳区', json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('代理日志已记录', (int)$pdo->query("select count(*) from agent_log where agent_id=$AG and action like '修改订单收货地址：QAADDR0001%'")->fetchColumn() === 1);
    [, , $j] = req($sg, 'POST', '/agent/order/editAddress', ['id' => $O[6]] + $new);
    ok('代理改非团队订单被拒，地址不变', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '不属于您的团队') !== false && addr($O[6]) === '旧收货人 13900001111 旧地址', json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 卖家侧 ==\n";
    $ss = sess($S);
    [$c, $h] = req($ss, 'GET', '/seller/orders', null, false);
    ok('卖家订单页显示最新收货人，电话仍脱敏', $c == 200 && strpos($h, '代理改的人') !== false && strpos($h, '139****4444') !== false && strpos($h, '13900004444') === false, "HTTP $c");
} finally {
    $pdo->exec("delete from `order` where order_no like 'QAADDR%'");
    $pdo->exec("delete from goods where seller_id in ($S,$SX)");
    $pdo->exec("delete from sys_message where user_id in ($S,$B,$SX)");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from admin_log where action like '%QAADDR%'");
    $pdo->exec("delete from user where id in ($AG,$S,$B,$SX)");
    foreach ([$sa, $sg, $ss ?? ''] as $s) if ($s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

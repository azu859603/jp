<?php
/**
 * 主后台「卖家权限 → 卖家可见买家收货信息」开关（setting.seller_see_address，默认 1 开启）：
 *  - 开启：卖家发货弹窗显示收货人 / 脱敏电话 / 地址
 *  - 关闭：收货信息完全不下发到卖家页面（查看源码也看不到），弹窗里整块不渲染
 *  - 两种情况下卖家都能正常填快递单号发货
 *  - 后台 / 代理后台不受影响，照常能看到完整收货信息
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('sa' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function getSet($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }

$pdo->exec("delete from user where mobile like '199999913%'");
$AG = mk('19999991301', 'QA可见代理', 0, 1);
$S  = mk('19999991302', 'QA可见卖家', $AG, 0, 1);
$B  = mk('19999991303', 'QA可见买家', $AG);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$NAME = 'QA收货人张三'; $MOBILE = '13912345678'; $MASKED = '139****5678'; $ADDR = 'QA上海市浦东新区世纪大道999号';
$O = [];
foreach ([1, 2] as $i) {
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($S,$cat,'QA可见拍品$i','','[]',100,10,0,2,$T-7200,$T-3600,$T,$T)");
    $g = (int)$pdo->lastInsertId();
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,seller_id,buyer_id,price,deposit,seller_income,pay_status,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QASEE000$i',$g,'QA可见拍品$i',$S,$B,120,0,120,1,1,'$NAME','$MOBILE','$ADDR',$T,$T)");
    $O[$i] = (int)$pdo->lastInsertId();
}
$bak = getSet('seller_see_address');
$ss = sess($S); $sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 默认（未配置即开启）==\n";
    $pdo->exec("delete from setting where name='seller_see_address'");
    [$c, $h] = req($ss, 'GET', '/seller/orders', null, false);
    ok('卖家发货弹窗显示收货人与地址，电话脱敏', $c == 200 && strpos($h, $NAME) !== false && strpos($h, $ADDR) !== false && strpos($h, $MASKED) !== false && strpos($h, $MOBILE) === false, "HTTP $c");
    ok('按钮带 data 属性、弹窗有三行收货信息', strpos($h, 'data-name="' . $NAME . '"') !== false && strpos($h, 'id="saAddress"') !== false && strpos($h, '由平台统一处理') === false);
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页有开关且默认选中「开启」', $c == 200 && preg_match('/name="seller_see_address" id="ssa1" value="1" checked/', $h), "HTTP $c");

    echo "== 后台关闭 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['seller_see_address' => '0']);
    ok('设置保存为关闭', ($j['code'] ?? 0) == 1 && getSet('seller_see_address') === '0', json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($ss, 'GET', '/seller/orders', null, false);
    ok('页面源码里完全没有收货人 / 电话 / 地址（含脱敏号）', $c == 200 && strpos($h, $NAME) === false && strpos($h, $ADDR) === false && strpos($h, $MOBILE) === false && strpos($h, $MASKED) === false, "HTTP $c");
    ok('按钮不再带 data 属性，弹窗里整块收货信息都不渲染', strpos($h, 'data-name=') === false && strpos($h, 'data-address=') === false && strpos($h, '由平台统一处理') === false && strpos($h, 'id="saAddress"') === false && strpos($h, '<div class="ship-addr"') === false);
    [, , $j] = req($ss, 'POST', '/seller/ship', ['id' => $O[1], 'ship_company' => '顺丰', 'ship_no' => 'SF10001']);
    $r = $pdo->query("select order_status,ship_no from `order` where id={$O[1]}")->fetch(PDO::FETCH_ASSOC);
    ok('关闭后卖家仍可正常发货', ($j['code'] ?? 0) == 1 && (int)$r['order_status'] === 2 && $r['ship_no'] === 'SF10001', json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode($r));
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页回显「关闭」', $c == 200 && preg_match('/name="seller_see_address" id="ssa0" value="0" checked/', $h), "HTTP $c");

    echo "== 后台 / 代理后台不受影响 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/order/index?page=1&limit=50&keyword=QASEE0002');
    $row = ($j['data'] ?? [])[0] ?? null;
    ok('主后台订单接口仍返回完整收货信息', $row && ($row['ship_name'] ?? '') === $NAME && ($row['ship_mobile'] ?? '') === $MOBILE && ($row['ship_address'] ?? '') === $ADDR, json_encode($row, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/order/editAddress', ['id' => $O[2], 'ship_name' => '新人', 'ship_mobile' => '13900008888', 'ship_address' => '北京']);
    ok('代理后台仍可修改收货地址', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 重新开启 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['seller_see_address' => 'abc']);
    ok('非法值按关闭保存', ($j['code'] ?? 0) == 1 && getSet('seller_see_address') === '0', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['seller_see_address' => '1']);
    ok('保存为开启', ($j['code'] ?? 0) == 1 && getSet('seller_see_address') === '1', json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($ss, 'GET', '/seller/orders', null, false);
    ok('开启后卖家又能看到收货信息（订单2已被代理改过）', $c == 200 && strpos($h, '新人') !== false && strpos($h, '北京') !== false && strpos($h, '139****8888') !== false, "HTTP $c");
} finally {
    if ($bak === null) { $pdo->exec("delete from setting where name='seller_see_address'"); }
    else { $st = $pdo->prepare('update setting set value=? where name=?'); $st->execute([$bak, 'seller_see_address']); }
    $pdo->exec("delete from `order` where order_no like 'QASEE%'");
    $pdo->exec("delete from goods where seller_id=$S");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from sys_message where user_id in ($S,$B)");
    $pdo->exec("delete from user where id in ($AG,$S,$B)");
    foreach ([$ss, $sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

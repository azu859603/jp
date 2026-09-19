<?php
/**
 * 后台 / 代理后台：已成交的商品也可以删除（单个 + 批量，不再自动跳过）。
 *  - 删除后商品行和出价记录消失，对应订单保留（订单表自带标题 / 封面 / 保证金）
 *  - 订单后续流程不受影响：买家订单页、卖家订单页能打开；未付款订单仍可「完成支付」
 *  - 操作日志标注「已成交」
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0, $bal = 0, $freeze = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,$bal,$freeze,$seller," . ($seller ? 1 : 0) . ",$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('ds' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function cnt($sql) { global $pdo; return (int)$pdo->query($sql)->fetchColumn(); }

$pdo->exec("delete from user where mobile in ('19999990700','19999990701','19999990702')");
$AG = mk('19999990700', 'QA删成交代理', 0, 1);
$S  = mk('19999990701', 'QA删成交卖家', $AG, 0, 1);
$B  = mk('19999990702', 'QA删成交买家', $AG, 0, 0, 500, 50);   // 余额 500，冻结 50（未付款订单的保证金）
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$G = []; $O = [];
foreach ([1 => [0, 0, 50], 2 => [1, 1, 0], 3 => [1, 3, 0]] as $i => [$pay, $ost, $dep]) {
    // 1：成交未付款（保证金 50 冻结中）  2：已付款待发货  3：已完成
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,bid_count,final_price,winner_id,start_time,end_time,create_time,update_time) values($S,$cat,'QA删成交拍品$i','/uploads/qa/$i.jpg','[]',100,10,$dep,2,1,120,$B,$T-7200,$T-3600,$T,$T)");
    $G[$i] = (int)$pdo->lastInsertId();
    $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values({$G[$i]},$B,120,$dep,1,1,$T-4000)");
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,deposit,seller_income,pay_status,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QADELSOLD000$i',{$G[$i]},'QA删成交拍品$i','/uploads/qa/$i.jpg',$S,$B,120,$dep,120,$pay,$ost,'张三','13900001111','上海',$T,$T)");
    $O[$i] = (int)$pdo->lastInsertId();
    $pdo->exec("update goods set order_id={$O[$i]} where id={$G[$i]}");
}
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($S,$cat,'QA删成交-流拍品','','[]',100,10,0,3,$T-7200,$T-3600,$T,$T)");
$GF = (int)$pdo->lastInsertId();
$sa = sess(0, 'admin'); $sg = sess($AG); $sb = sess($B); $ss = sess($S);
try {
    echo "== 页面 ==\n";
    foreach ([['主后台', $sa, '/admin1314/goods/index'], ['代理后台', $sg, '/agent/goods/index']] as [$tag, $sid, $url]) {
        [$c, $h] = req($sid, 'GET', $url, null, false);
        ok("$tag 列表：已成交也有删除按钮，批量提示不再说「自动跳过」", $c == 200 && strpos($h, "onclick=\"del(' + g.id + ',' + (g.status == 2 ? 1 : 0) + ')\"") !== false && strpos($h, '自动跳过') === false && strpos($h, '对应订单保留') !== false, "HTTP $c");
    }

    echo "== 主后台单个删除（成交未付款）==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/goods/delete', ['id' => $G[1]]);
    ok('删除成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('商品和出价记录已删除，订单保留', cnt("select count(*) from goods where id={$G[1]}") === 0 && cnt("select count(*) from bid_record where goods_id={$G[1]}") === 0 && cnt("select count(*) from `order` where id={$O[1]}") === 1);
    $u = $pdo->query("select balance,freeze_balance from user where id=$B")->fetch(PDO::FETCH_ASSOC);
    ok('买家冻结的保证金不受影响（仍冻结，等支付时抵扣）', (float)$u['balance'] == 500 && (float)$u['freeze_balance'] == 50, json_encode($u));
    $log = (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('操作日志标注已成交', strpos($log, '删除商品：QA删成交拍品1（已成交，订单保留）') !== false, $log);
    [, , $j] = req($sa, 'POST', '/admin1314/order/pay', ['id' => $O[1], 'ship_name' => '张三', 'ship_mobile' => '13900001111', 'ship_address' => '上海市']);
    $u = $pdo->query("select balance,freeze_balance from user where id=$B")->fetch(PDO::FETCH_ASSOC);
    ok('商品删除后订单仍可「完成支付」，保证金正常抵扣（500-70=430，冻结清零）', ($j['code'] ?? 0) == 1 && (float)$u['balance'] == 430 && (float)$u['freeze_balance'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode($u));

    echo "== 代理后台批量删除（含已付款、已完成、流拍）==\n";
    [, , $j] = req($sg, 'POST', '/agent/goods/delete', ['ids' => "{$G[2]},{$G[3]},$GF"]);
    ok('批量删除 3 个，全部删除，不再跳过已成交', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '3 个') !== false && strpos($j['msg'] ?? '', '含已成交 2 个') !== false && strpos($j['msg'] ?? '', '跳过') === false, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('三个商品都已删除，两张订单保留', cnt("select count(*) from goods where id in ({$G[2]},{$G[3]},$GF)") === 0 && cnt("select count(*) from `order` where id in ({$O[2]},{$O[3]})") === 2);
    ok('代理日志标注含已成交', cnt("select count(*) from agent_log where agent_id=$AG and action like '批量删除产品：%含已成交 2 个%'") === 1);

    echo "== 订单后续流程 ==\n";
    [$c, $h] = req($sb, 'GET', '/order/list', null, false);
    ok('买家订单页正常，仍显示商品标题', $c == 200 && strpos($h, 'QA删成交拍品2') !== false && strpos($h, 'QA删成交拍品3') !== false, "HTTP $c");
    [$c, $h] = req($ss, 'GET', '/seller/orders', null, false);
    ok('卖家订单页正常', $c == 200 && strpos($h, 'QA删成交拍品2') !== false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/order/ship', ['id' => $O[2], 'company' => '顺丰', 'ship_no' => 'SF0001']);
    ok('商品删除后订单仍可发货', ($j['code'] ?? 0) == 1 && cnt("select order_status from `order` where id={$O[2]}") === 2, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sa, 'GET', '/admin1314/order/detail?id=' . $O[3], null, false);
    ok('主后台订单详情正常', $c == 200 && strpos($h, 'QADELSOLD0003') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from `order` where order_no like 'QADELSOLD%'");
    $pdo->exec("delete from bid_record where user_id=$B");
    $pdo->exec("delete from goods where seller_id=$S");
    $pdo->exec("delete from balance_log where user_id in ($B,$S)");
    $pdo->exec("delete from sys_message where user_id in ($B,$S)");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from admin_log where action like '%QA删成交%' or action like '%QADELSOLD%'");
    $pdo->exec("delete from user where id in ($AG,$S,$B)");
    foreach ([$sa, $sg, $sb, $ss] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

<?php
/**
 * 已有出价的拍卖中商品，后台 / 代理后台编辑：
 *  - 加价幅度可以修改（对之后的出价生效）
 *  - 起拍价 / 保证金 / 保留价仍锁定，提交值被忽略
 *  - 编辑接口返回 price_locked=1，页面提示不再包含「加价幅度」
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('ru' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function g($id) { global $pdo; return $pdo->query("select start_price,raise_price,deposit,reserve_price,bid_count,status from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }

$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$AG = mk('19999990610', 'QA加价代理', 0, 1);
$S  = mk('19999990611', 'QA加价卖家', $AG, 0, 1);
$B  = mk('19999990612', 'QA加价买家', $AG);
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,reserve_price,status,bid_count,start_time,end_time,create_time,update_time) values($S,$cat,'QA加价幅度拍品','','[]',100,10,20,0,1,1,$T-60,$T+7200,$T,$T)");
$G = (int)$pdo->lastInsertId();
$pdo->exec("insert into bid_record(goods_id,user_id,price,status,is_winner,create_time) values($G,$B,100,0,0,$T)");
$sa = sess(0, 'admin'); $sg = sess($AG);
$base = ['title' => 'QA加价幅度拍品', 'category_id' => $cat, 'content' => 'x', 'cover' => '/uploads/qa.jpg', 'images' => '/uploads/qa.jpg', 'reserve_price' => 500, 'end_time' => date('Y-m-d\TH:i', $T + 7200), 'delay_seconds' => 0];
try {
    echo "== 主后台 ==\n";
    [, , $j] = req($sa, 'GET', "/admin1314/goods/edit?id=$G");
    ok('编辑接口 price_locked=1', ($j['data']['price_locked'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', "/admin1314/goods/edit?id=$G", $base + ['start_price' => 999, 'raise_price' => 25, 'deposit' => 888]);
    $r = g($G);
    ok('加价幅度 10→25 已更新', ($j['code'] ?? 0) == 1 && (float)$r['raise_price'] == 25, json_encode($j, JSON_UNESCAPED_UNICODE) . ' ' . json_encode($r));
    ok('起拍价 / 保证金 / 保留价保持不变', (float)$r['start_price'] == 100 && (float)$r['deposit'] == 20 && (float)$r['reserve_price'] == 0, json_encode($r));
    ok('返回提示不再说加价幅度未变更', strpos($j['msg'] ?? '', '加价幅度') === false && strpos($j['msg'] ?? '', '起拍价') !== false, $j['msg'] ?? '');
    [, , $j] = req($sa, 'POST', "/admin1314/goods/edit?id=$G", $base + ['start_price' => 100, 'raise_price' => 0, 'deposit' => 20]);
    ok('加价幅度 0 仍被校验拒绝', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '加价幅度') !== false && (float)g($G)['raise_price'] == 25, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sa, 'GET', '/admin1314/goods/index', null, false);
    ok('列表页锁定提示不含加价幅度不可修改', $c == 200 && strpos($h, '起拍价 / 保证金不可修改') !== false && strpos($h, '加价幅度 / 保证金不可修改') === false, "HTTP $c");

    echo "== 前台出价按新幅度 ==\n";
    $sb = sess($B);
    [$c, $h] = req($sb, 'GET', "/goods/detail?id=$G", null, false);
    ok('商品详情下一口价按 100+25', $c == 200 && strpos($h, 'id="bidMin">125.00') !== false, "HTTP $c");

    echo "== 代理后台 ==\n";
    [, , $j] = req($sg, 'GET', "/agent/goods/edit?id=$G");
    ok('代理编辑接口 price_locked=1', ($j['data']['price_locked'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', "/agent/goods/edit?id=$G", $base + ['start_price' => 777, 'raise_price' => 40, 'deposit' => 666]);
    $r = g($G);
    ok('代理改加价幅度 25→40 已更新', ($j['code'] ?? 0) == 1 && (float)$r['raise_price'] == 40, json_encode($j, JSON_UNESCAPED_UNICODE) . ' ' . json_encode($r));
    ok('代理提交的起拍价 / 保证金 / 保留价被忽略', (float)$r['start_price'] == 100 && (float)$r['deposit'] == 20 && (float)$r['reserve_price'] == 0, json_encode($r));
    [$c, $h] = req($sg, 'GET', '/agent/goods/index', null, false);
    ok('代理列表页锁定提示不含加价幅度不可修改', $c == 200 && strpos($h, '起拍价 / 保证金不可修改') !== false && strpos($h, '加价幅度 / 保证金不可修改') === false, "HTTP $c");
} finally {
    $pdo->exec("delete from bid_record where goods_id=$G");
    $pdo->exec("delete from goods where id=$G");
    $pdo->exec("delete from user where id in ($AG,$S,$B)");
    foreach ([$sa, $sg, $sb ?? ''] as $s) if ($s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

<?php
/**
 * 余额调整上限：
 *  - 后台 / 代理后台「余额调整」单次最多添加 10,000,000，超过给出提示
 *  - 调整后余额不能超过金额列上限 99,999,999.99（DECIMAL(10,2)），超过给出提示而不是数据库报错
 *  - 前台个人中心完整显示大额余额（自动缩小字号，不截断）
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,0,0,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('bb' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function bal($id) { global $pdo; return (float)$pdo->query("select balance from user where id=$id")->fetchColumn(); }

$AG = mk('19999990590', 'QA大额代理', 0, 1); $U = mk('19999990591', 'QA大额会员', $AG);
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 主后台 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 10000001, 'remark' => 'QA']);
    ok('单次加 10,000,001 被拒（最多 10,000,000）', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '10,000,000') !== false && bal($U) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 10000000, 'remark' => 'QA']);
    ok('单次加 10,000,000 成功', ($j['code'] ?? 0) == 1 && bal($U) == 10000000, json_encode($j, JSON_UNESCAPED_UNICODE) . ' bal=' . bal($U));
    echo "== 代理后台 ==\n";
    [, , $j] = req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => 20000000, 'remark' => 'QA']);
    ok('代理单次加 20,000,000 被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '10,000,000') !== false && bal($U) == 10000000, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => 10000000, 'remark' => 'QA']);
    ok('代理单次加 10,000,000 成功（累计 2 千万）', ($j['code'] ?? 0) == 1 && bal($U) == 20000000, json_encode($j, JSON_UNESCAPED_UNICODE) . ' bal=' . bal($U));
    echo "== 列上限 ==\n";
    $pdo->exec("update user set balance=99000000 where id=$U");
    [, , $j] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 5000000, 'remark' => 'QA']);
    ok('余额 9,900 万再加 500 万会超过 99,999,999.99 → 友好提示，不报 SQL 错', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '系统上限') !== false && bal($U) == 99000000, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 前台 ==\n";
    $su = sess($U);
    [$c, $h] = req($su, 'GET', '/user/center', null, false);
    ok('个人中心完整显示 ¥99,000,000.00（缩小字号，不截断）', $c == 200 && strpos($h, '<b class="n3"><small>¥</small>99,000,000.00</b>') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from balance_log where user_id=$U");
    $pdo->exec("delete from user where id in ($U,$AG)");
    foreach ([$sa, $sg, $su ?? ''] as $s) if ($s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

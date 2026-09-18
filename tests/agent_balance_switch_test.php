<?php
/**
 * 主后台「代理后台权限 → 代理调整会员余额」开关（setting.agent_balance_adjust，默认 1）：
 *  - 开启：代理「我的会员」列表显示「余额」按钮，adjustBalance 可用
 *  - 关闭：按钮不渲染（JS 变量为 0），adjustBalance 接口拒绝
 *  - 设置页有开关并可保存，保存后立即生效
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,100,0,0,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('bs' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function setting($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }
function bal($id) { global $pdo; return (float)$pdo->query("select balance from user where id=$id")->fetchColumn(); }

$pdo->exec("delete from user where mobile in ('19999990650','19999990651')");
$AG = mk('19999990650', 'QA开关代理', 0, 1);
$U  = mk('19999990651', 'QA开关会员', $AG);
$bak = setting('agent_balance_adjust');
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 默认开启 ==\n";
    $pdo->exec("delete from setting where name='agent_balance_adjust'");
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页有开关且默认选中「开启」', $c == 200 && strpos($h, '代理后台权限') !== false && preg_match('/name="agent_balance_adjust" id="aba1" value="1" checked/', $h), "HTTP $c");
    [$c, $h] = req($sg, 'GET', '/agent/member/index', null, false);
    ok('未配置时代理列表页 JS 变量为 1', $c == 200 && strpos($h, 'var BALANCE_ADJUST = 1 == 1') !== false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => 10, 'remark' => 'QA']);
    ok('未配置时代理可调整余额', ($j['code'] ?? 0) == 1 && bal($U) == 110, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 主后台关闭 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['agent_balance_adjust' => '0']);
    ok('设置保存为关闭', ($j['code'] ?? 0) == 1 && setting('agent_balance_adjust') === '0', json_encode($j, JSON_UNESCAPED_UNICODE) . ' v=' . setting('agent_balance_adjust'));
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页回显「关闭」', $c == 200 && preg_match('/name="agent_balance_adjust" id="aba0" value="0" checked/', $h), "HTTP $c");
    [$c, $h] = req($sg, 'GET', '/agent/member/index', null, false);
    ok('代理列表页 JS 变量为 0（按钮不渲染）', $c == 200 && strpos($h, 'var BALANCE_ADJUST = 0 == 1') !== false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => 10, 'remark' => 'QA']);
    ok('关闭后接口拒绝，余额不变', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '关闭') !== false && bal($U) == 110, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 5, 'remark' => 'QA']);
    [, , $j2] = req($sg, 'POST', '/agent/member/add', ['account' => '19999990652', 'password' => 'pass1234', 'balance' => 500]);
    ok('关闭后代理添加会员不能带初始余额', ($j2['code'] ?? 1) == 0 && strpos($j2['msg'] ?? '', '初始余额只能为 0') !== false && !$pdo->query("select id from user where mobile='19999990652'")->fetchColumn(), json_encode($j2, JSON_UNESCAPED_UNICODE));
    [, , $j2] = req($sg, 'POST', '/agent/member/batchAddVirtual', ['count' => 1, 'prefix' => 'QA开关虚', 'password' => 'pass1234', 'balance' => 100000]);
    ok('关闭后批量虚拟会员不能带初始余额', ($j2['code'] ?? 1) == 0 && strpos($j2['msg'] ?? '', '初始余额只能为 0') !== false, json_encode($j2, JSON_UNESCAPED_UNICODE));
    [, , $j2] = req($sg, 'POST', '/agent/member/add', ['account' => '19999990652', 'password' => 'pass1234', 'balance' => 0]);
    ok('关闭后初始余额为 0 仍可添加会员', ($j2['code'] ?? 0) == 1, json_encode($j2, JSON_UNESCAPED_UNICODE));
    ok('主后台调整余额不受影响', ($j['code'] ?? 0) == 1 && bal($U) == 115, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 重新开启 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['agent_balance_adjust' => 'abc']);
    ok('非法值保存为关闭（只认 1）', ($j['code'] ?? 0) == 1 && setting('agent_balance_adjust') === '0', json_encode($j, JSON_UNESCAPED_UNICODE) . ' v=' . setting('agent_balance_adjust'));
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['agent_balance_adjust' => '1']);
    ok('设置保存为开启', ($j['code'] ?? 0) == 1 && setting('agent_balance_adjust') === '1', json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sg, 'GET', '/agent/member/index', null, false);
    ok('代理列表页恢复显示', $c == 200 && strpos($h, 'var BALANCE_ADJUST = 1 == 1') !== false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => -15, 'remark' => 'QA']);
    ok('开启后代理可调整余额', ($j['code'] ?? 0) == 1 && bal($U) == 100, json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    if ($bak === null) { $pdo->exec("delete from setting where name='agent_balance_adjust'"); }
    else { $st = $pdo->prepare('update setting set value=? where name=?'); $st->execute([$bak, 'agent_balance_adjust']); }
    $pdo->exec("delete from balance_log where user_id=$U");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from admin_log where action like '%19999990651%'");
    $pdo->exec("delete from user where id in ($AG,$U) or mobile='19999990652' or nickname like 'QA开关虚%'");
    foreach ([$sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

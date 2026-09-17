<?php
/**
 * 被禁用会员在前台能看到禁用原因（后台填写的禁用备注）：
 *  - 登录时：接口返回 disabled=1 + reason，msg 含原因；登录页 JS 展示红色提示框
 *  - 登录期间被禁用：踢出后打开登录页展示「您的账号已被禁用 + 原因」，只展示一次
 *  - 多语言
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
// 清掉登录防爆破计数（其它测试脚本连续跑会把 IP 计数累加到锁定阈值）
function clearLoginFail() { try { $r = new Redis(); $r->connect('127.0.0.1', 6379, 1); foreach ($r->keys('jp:login_fail_*') as $k) $r->del($k); } catch (\Throwable $e) {} }
function sessData(array $data) { global $root; $sid = md5('dn' . json_encode($data) . microtime(true)); file_put_contents("$root/runtime/session/sess_$sid", serialize($data)); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}

$M = '19999990630';
$pdo->exec("delete from user where mobile='$M'");
$hash = password_hash('123456', PASSWORD_DEFAULT);
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,status_remark,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$M','$hash','QA禁用告知','990630',0,0,'恶意出价，多次拍下不付款',0,0,0,0,0,2,$T,$T,$T)");
$U = (int)$pdo->lastInsertId();
$sids = [];
clearLoginFail();
try {
    echo "== 登录时告知 ==\n";
    $s1 = $sids[] = sessData(['user_captcha' => 'ab12']);
    [, , $j] = req($s1, 'POST', '/user/doLogin', ['mobile' => $M, 'password' => '123456', 'captcha' => 'ab12']);
    ok('禁用账号登录：返回 disabled=1 与原因', ($j['code'] ?? 1) == 0 && ($j['disabled'] ?? 0) == 1 && ($j['reason'] ?? '') === '恶意出价，多次拍下不付款', json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('msg 含「账号已被禁用」+ 原因 + 联系客服', strpos($j['msg'] ?? '', '账号已被禁用') !== false && strpos($j['msg'] ?? '', '原因：恶意出价') !== false && strpos($j['msg'] ?? '', '联系客服') !== false, $j['msg'] ?? '');
    clearLoginFail();
    $s2 = $sids[] = sessData(['user_captcha' => 'ab12']);
    [, , $j] = req($s2, 'POST', '/user/doLogin?lang=en-us', ['mobile' => $M, 'password' => '123456', 'captcha' => 'ab12']);
    ok('英文 msg', strpos($j['msg'] ?? '', 'Account disabled, reason: 恶意出价') !== false && strpos($j['msg'] ?? '', 'contact customer service') !== false, $j['msg'] ?? '');
    $pdo->exec("update user set status_remark='' where id=$U");
    clearLoginFail();
    $s3 = $sids[] = sessData(['user_captcha' => 'ab12']);
    [, , $j] = req($s3, 'POST', '/user/doLogin', ['mobile' => $M, 'password' => '123456', 'captcha' => 'ab12']);
    ok('无备注时 msg 不带「原因：」', ($j['disabled'] ?? 0) == 1 && strpos($j['msg'] ?? '', '原因') === false && strpos($j['msg'] ?? '', '联系客服') !== false, $j['msg'] ?? '');
    $pdo->exec("update user set status_remark='恶意出价，多次拍下不付款' where id=$U");
    [$c, $h] = req('', 'GET', '/user/login', null, false);
    ok('登录页带隐藏提示框与 JS', $c == 200 && strpos($h, 'id="disabledBox" style="display:none;"') !== false && strpos($h, 'function showDisabled') !== false, "HTTP $c");

    echo "== 登录期间被禁用 ==\n";
    $pdo->exec("update user set status=1 where id=$U");
    $u = $pdo->query("select * from user where id=$U")->fetch(PDO::FETCH_ASSOC); unset($u['password']);
    $s4 = $sids[] = sessData(['user' => $u]);
    [$c, $h] = req($s4, 'GET', '/user/center', null, false);
    ok('正常状态可进个人中心', $c == 200 && strpos($h, 'class="mine2"') !== false, "HTTP $c");
    $pdo->exec("update user set status=0 where id=$U");
    [$c] = req($s4, 'GET', '/user/center', null, false);
    ok('被禁用后访问个人中心被踢出', $c == 302, "HTTP $c");
    [$c, $h] = req($s4, 'GET', '/user/login', null, false);
    ok('登录页展示禁用提示与原因', $c == 200 && strpos($h, 'id="disabledBox">') !== false && strpos($h, '您的账号已被禁用') !== false && strpos($h, '禁用原因：恶意出价，多次拍下不付款') !== false, "HTTP $c");
    [$c, $h] = req($s4, 'GET', '/user/login', null, false);
    ok('提示只展示一次，再次打开不显示', $c == 200 && strpos($h, 'id="disabledBox" style="display:none;"') !== false, "HTTP $c");
    [$c, $h] = req($s4, 'GET', '/user/center', null, false);
    [$c, $h] = req($s4, 'GET', '/user/login?lang=zh-tw', null, false);
    ok('繁体提示', $c == 200 && strpos($h, '您的賬號已被禁用') !== false && strpos($h, '如有疑問請聯繫客服') !== false, "HTTP $c");
    [$c, $h] = req('', 'GET', '/user/login?lang=zh-cn', null, false);

    echo "== 备注 XSS ==\n";
    $pdo->exec("update user set status=1, status_remark='<script>alert(1)</script>' where id=$U");
    $u = $pdo->query("select * from user where id=$U")->fetch(PDO::FETCH_ASSOC); unset($u['password']);
    $s5 = $sids[] = sessData(['user' => $u]);
    req($s5, 'GET', '/user/center', null, false);
    $pdo->exec("update user set status=0 where id=$U");
    req($s5, 'GET', '/user/center', null, false);
    [$c, $h] = req($s5, 'GET', '/user/login', null, false);
    ok('备注中的 HTML 被转义', $c == 200 && strpos($h, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false && strpos($h, '<script>alert(1)</script>') === false, "HTTP $c");
} finally {
    $pdo->exec("delete from user where id=$U");
    clearLoginFail();
    foreach ($sids as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

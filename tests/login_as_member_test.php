<?php
/**
 * 主后台「登录会员」：生成一次性令牌 → 前台凭令牌以该会员身份登录
 *  - 只有后台已登录才能生成；禁用会员不能登录；写后台日志
 *  - 令牌 60 秒内一次有效：用过 / 伪造 / 过期都跳回登录页
 *  - 登录后前台会话是该会员，且会话 ID 已更换
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function newSid($tag) { return md5($tag . microtime(true) . mt_rand()); }
function sessData($sid) { global $root; clearstatcache(); $f = "$root/runtime/session/sess_$sid"; return is_file($f) ? (@unserialize(file_get_contents($f)) ?: []) : []; }
function mk($m, $nick, $status = 1) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,$status,0,0,0,0,0,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function req($sid, $m, $p, $d = null, $ajax = true) {
    $head = $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $raw = (string)curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $c  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $headers = substr($raw, 0, $hs); $body = substr($raw, $hs);
    preg_match('/Set-Cookie:\s*PHPSESSID=([a-f0-9]+)/i', $headers, $mm);
    preg_match('/^Location:\s*(.+)$/im', $headers, $ml);
    return [$c, $body, json_decode($body, true), $mm[1] ?? $sid, trim($ml[1] ?? '')];
}

$M  = mk('19999990480', 'QA被登录会员');
$D  = mk('19999990481', 'QA禁用会员', 0);
$sa = newSid('adm');
$admin = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC);
unset($admin['password']);
file_put_contents("$root/runtime/session/sess_$sa", serialize(['admin' => $admin]));
$sids = [$sa];
try {
    echo "== 页面 ==\n";
    [$c, $html] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('会员列表有「登录会员」按钮和脚本', $c == 200 && strpos($html, '>登录会员</a>') !== false && strpos($html, "ajaxPost('/admin1314/member/loginAs'") !== false && strpos($html, "window.open('', '_blank')") !== false, "HTTP $c");

    echo "== 生成令牌 ==\n";
    [, , $j] = req(newSid('nobody'), 'POST', '/admin1314/member/loginAs', ['id' => $M]);
    ok('未登录后台不能生成令牌', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/loginAs', ['id' => $D]);
    ok('禁用会员不能登录', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '禁用') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/loginAs', ['id' => 0]);
    ok('会员不存在被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/loginAs', ['id' => $M]);
    $url = (string)($j['url'] ?? '');
    ok('生成一次性登录链接', ($j['code'] ?? 0) == 1 && preg_match('#^/user/loginAs\?token=[a-f0-9]{32}$#', $url) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('后台日志已记录', (int)$pdo->query("select count(*) from admin_log where action like '以会员身份登录前台：19999990480%'")->fetchColumn() === 1, '');

    echo "== 前台凭令牌登录 ==\n";
    $g = newSid('front'); $sids[] = $g;
    [$c, , , $newSid, $loc] = req($g, 'GET', $url, null, false);
    $sids[] = $newSid;
    ok('跳转到个人中心', $c == 302 && strpos($loc, '/user/center') !== false, "HTTP $c loc=$loc");
    $sd = sessData($newSid);
    ok('新会话已是该会员，且会话 ID 已更换', $newSid !== $g && !empty($sd['user']) && (int)$sd['user']['id'] === $M && (int)($sd['login_as_admin'] ?? 0) === (int)$admin['id'], json_encode([$newSid !== $g, $sd['user']['id'] ?? null, $sd['login_as_admin'] ?? null]));
    [$c, $body] = req($newSid, 'GET', '/user/center', null, false);
    ok('用新会话访问个人中心为登录态', $c == 200 && strpos($body, 'QA被登录会员') !== false, "HTTP $c");

    echo "== 令牌一次性 / 伪造 ==\n";
    $g2 = newSid('again'); $sids[] = $g2;
    // 失败时前台用统一的错误页（200 + 自动跳转登录页）或 302，两种都算拒绝；关键是不能产生登录态
    // 错误页里的跳转地址是 json_encode 过的（"\/user\/login"），两种写法都匹配
    $rejected = function ($c, $body, $loc) { return (strpos($loc, '/user/login') !== false) || ($c == 200 && (strpos($body, '/user/login') !== false || strpos($body, 'user\\/login') !== false) && (strpos($body, '失效') !== false || strpos($body, '无效') !== false)); };
    [$c, $body, , $s2, $loc] = req($g2, 'GET', $url, null, false);
    $sids[] = $s2;
    ok('同一令牌再用一次失效（回登录页，不产生登录态）', $rejected($c, $body, $loc) && empty(sessData($s2)['user']), "HTTP $c loc=$loc");
    $g3 = newSid('fake'); $sids[] = $g3;
    [$c, $body, , $s3, $loc] = req($g3, 'GET', '/user/loginAs?token=' . str_repeat('0', 32), null, false);
    $sids[] = $s3;
    ok('伪造令牌无效', $rejected($c, $body, $loc) && empty(sessData($s3)['user']), "HTTP $c loc=$loc");
    [$c, $body, , $s4, $loc] = req(newSid('bad'), 'GET', '/user/loginAs?token=abc', null, false);
    $sids[] = $s4;
    ok('格式错误的令牌无效', $rejected($c, $body, $loc) && empty(sessData($s4)['user']), "HTTP $c loc=$loc");
} finally {
    $pdo->exec("delete from admin_log where action like '以会员身份登录前台：1999999048%'");
    $pdo->exec("delete from user where id in ($M,$D)");
    foreach (array_unique($sids) as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

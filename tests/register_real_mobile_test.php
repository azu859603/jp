<?php
/**
 * 手机号规则
 *  - 前台注册：只接受真实号段（13~19 开头的 11 位），12 开头被拒
 *  - 前台登录：12 开头的虚拟会员可以正常登录
 *  - 主后台 / 代理后台添加会员：仍可添加 12 开头的号码
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function newSid($tag) { return md5($tag . microtime(true) . mt_rand()); }
function sessData($sid) { global $root; $f = "$root/runtime/session/sess_$sid"; return is_file($f) ? (@unserialize(file_get_contents($f)) ?: []) : []; }
function sess($id, $key = 'user') {
    global $root, $pdo;
    $sid = newSid('rm' . $key . $id);
    $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u]));
    return $sid;
}
function req($sid, $m, $p, $d = null, $ajax = true) {
    $head = $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
/** 发请求并返回响应里 Set-Cookie 下发的新 PHPSESSID（没有下发则返回原 sid） */
function reqSid($sid, $m, $p, $d = null) {
    $head = ['X-Requested-With: XMLHttpRequest'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $raw = (string)curl_exec($ch);
    $hs  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $c   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $headers = substr($raw, 0, $hs); $body = substr($raw, $hs);
    $newSid = preg_match('/Set-Cookie:\s*PHPSESSID=([a-f0-9]+)/i', $headers, $mm) ? $mm[1] : $sid;
    return [$c, $body, json_decode($body, true), $newSid];
}
function captcha($sid) { req($sid, 'GET', '/user/captcha'); return (string)(sessData($sid)['user_captcha'] ?? ''); }
function userByMobile($m) { global $pdo; return $pdo->query("select * from user where mobile='$m'")->fetch(PDO::FETCH_ASSOC) ?: null; }

$invite  = (string)$pdo->query("select invite_code from user where id=1")->fetchColumn();
$mobiles = ['12999990430', '13999990431', '10999990432', '12999990433', '12999990434', '12999990435'];
$sids    = [];
$agent   = 0;
try {
    echo "== 前台注册 ==\n";
    $sid = newSid('reg1'); $sids[] = $sid;
    [, , $j] = req($sid, 'POST', '/user/doRegister', ['mobile' => '12999990430', 'password' => 'qa123456', 'password2' => 'qa123456', 'invite_code' => $invite, 'captcha' => captcha($sid)]);
    ok('12 开头的号码不能注册', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '真实手机号') !== false && userByMobile('12999990430') === null, json_encode($j, JSON_UNESCAPED_UNICODE));
    $sid = newSid('reg2'); $sids[] = $sid;
    [, , $j] = req($sid, 'POST', '/user/doRegister', ['mobile' => '10999990432', 'password' => 'qa123456', 'password2' => 'qa123456', 'invite_code' => $invite, 'captcha' => captcha($sid)]);
    ok('10 开头也不能注册', ($j['code'] ?? 1) == 0 && userByMobile('10999990432') === null, json_encode($j, JSON_UNESCAPED_UNICODE));
    $sid = newSid('reg3'); $sids[] = $sid;
    [, , $j] = req($sid, 'POST', '/user/doRegister', ['mobile' => '13999990431', 'password' => 'qa123456', 'password2' => 'qa123456', 'invite_code' => $invite, 'captcha' => captcha($sid)]);
    ok('13 开头的真实号码可以注册', ($j['code'] ?? 0) == 1 && userByMobile('13999990431') !== null, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $html] = req(newSid('page'), 'GET', '/user/register', null, false);
    ok('注册页前端校验同步为真实号段', $c == 200 && strpos($html, '/^1[3-9]\\d{9}$/.test(mobile)') !== false, "HTTP $c");

    echo "== 前台登录虚拟会员 ==\n";
    $hash = password_hash('qa123456', PASSWORD_DEFAULT);
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('12999990433','$hash','QA虚拟登录','990433',0,1,0,0,0,0,1,0,$T,$T,$T)");
    $sid = newSid('login'); $sids[] = $sid;
    [, , $j, $nsid] = reqSid($sid, 'POST', '/user/doLogin', ['mobile' => '12999990433', 'password' => 'qa123456', 'captcha' => captcha($sid)]);
    $sids[] = $nsid;
    ok('12 开头的虚拟会员可以从前台登录', ($j['code'] ?? 0) == 1 && !empty(sessData($nsid)['user']) && (string)sessData($nsid)['user']['mobile'] === '12999990433', json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('登录后会话 ID 已更换', $nsid !== $sid, "old=$sid new=$nsid");

    echo "== 后台 / 代理后台添加会员 ==\n";
    $sa = sess(0, 'admin'); $sids[] = $sa;
    [, , $j] = req($sa, 'POST', '/admin1314/member/add', ['mobile' => '12999990434', 'password' => 'qa123456', 'nickname' => 'QA后台12号']);
    ok('主后台可以添加 12 开头的会员', ($j['code'] ?? 0) == 1 && userByMobile('12999990434') !== null, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990436','x','QA号段代理','990436',0,1,0,0,0,1,0,2,$T,$T,$T)");
    $agent = (int)$pdo->lastInsertId();
    $sag = sess($agent); $sids[] = $sag;
    [, , $j] = req($sag, 'POST', '/agent/member/add', ['mobile' => '12999990435', 'password' => 'qa123456', 'nickname' => 'QA代理12号']);
    ok('代理后台可以添加 12 开头的会员', ($j['code'] ?? 0) == 1 && userByMobile('12999990435') !== null && (int)userByMobile('12999990435')['pid'] === $agent, json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $in = "'" . implode("','", $mobiles) . "'";
    $ids = $pdo->query("select id from user where mobile in ($in)")->fetchAll(PDO::FETCH_COLUMN);
    if ($agent) $ids[] = $agent;
    if ($ids) { $idIn = implode(',', $ids); $pdo->exec("delete from balance_log where user_id in ($idIn)"); $pdo->exec("delete from sys_message where user_id in ($idIn)"); $pdo->exec("delete from user where id in ($idIn)"); }
    $pdo->exec("delete from admin_log where action like '%QA后台12号%'");
    foreach ($sids as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

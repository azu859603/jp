<?php
/**
 * 图形验证码测试：前台 / 主后台 / 代理端 全部为 4 位纯数字，且能正常登录
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }

/** 取一次验证码图片，返回 [sid, 图片二进制, 会话里的验证码] */
function grabCaptcha($url, $sessKey) {
    global $root;
    $sid = md5(uniqid('cap', true));
    $ch = curl_init('http://localhost' . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $img  = curl_exec($ch);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    $file = "$root/runtime/session/sess_$sid";
    $code = '';
    if (is_file($file)) {
        $data = @unserialize(file_get_contents($file));
        $code = (string)($data[$sessKey] ?? '');
    }
    return [$sid, $img, $code, $type];
}

function post($url, $data, $sid) {
    $ch = curl_init('http://localhost' . $url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_COOKIE         => 'PHPSESSID=' . $sid,
        CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost', 'Referer: http://localhost/'],
    ]);
    $b = curl_exec($ch);
    curl_close($ch);
    return [$b, json_decode($b, true)];
}

$sids = [];
$uid  = 0;
try {
    $targets = [
        ['前台',   '/user/captcha',            'user_captcha'],
        ['主后台', '/admin1314/login/captcha', 'admin_captcha'],
        ['代理端', '/agent/login/captcha',     'agent_captcha'],
    ];

    echo "== 验证码格式 ==\n";
    foreach ($targets as [$name, $url, $key]) {
        $allDigit = true;
        $len4     = true;
        $codes    = [];
        for ($i = 0; $i < 20; $i++) {
            [$sid, $img, $code, $type] = grabCaptcha($url, $key);
            $sids[] = $sid;
            $codes[] = $code;
            if (!preg_match('/^\d+$/', $code)) $allDigit = false;
            if (strlen($code) !== 4) $len4 = false;
            if ($i === 0) {
                ok("$name 验证码返回 PNG 图片", strpos((string)$type, 'image/png') !== false && strncmp((string)$img, "\x89PNG", 4) === 0, $type);
            }
        }
        ok("$name 验证码长度为 4 位", $len4, implode(',', $codes));
        ok("$name 验证码为纯数字（20 次抽样）", $allDigit, implode(',', $codes));
        ok("$name 验证码随机（20 次不全相同）", count(array_unique($codes)) > 1, implode(',', $codes));
    }

    echo "== 用正确的数字验证码登录 ==\n";
    // 前台会员
    $pwd  = 'qa123456';
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990370','$hash','QA验证码','990370',0,1,0,0,0,0,0,0,$T,$T,$T)");
    $uid = (int)$pdo->lastInsertId();

    [$sid, , $code] = grabCaptcha('/user/captcha', 'user_captcha');
    $sids[] = $sid;
    [, $j] = post('/user/doLogin', ['mobile' => '19999990370', 'password' => $pwd, 'captcha' => $code], $sid);
    ok('前台用数字验证码登录成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE) . " code=$code");

    [$sid, , $code] = grabCaptcha('/user/captcha', 'user_captcha');
    $sids[] = $sid;
    $wrong = $code === '9999' ? '1111' : '9999';
    [, $j] = post('/user/doLogin', ['mobile' => '19999990370', 'password' => $pwd, 'captcha' => $wrong], $sid);
    ok('前台错误验证码被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    [$sid, , $code] = grabCaptcha('/user/captcha', 'user_captcha');
    $sids[] = $sid;
    post('/user/doLogin', ['mobile' => '19999990370', 'password' => 'wrongpwd', 'captcha' => $code], $sid);
    [, $j] = post('/user/doLogin', ['mobile' => '19999990370', 'password' => $pwd, 'captcha' => $code], $sid);
    ok('验证码一次性生效（不可重放）', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 后台 / 代理端错误验证码 ==\n";
    [$sid, , $code] = grabCaptcha('/admin1314/login/captcha', 'admin_captcha');
    $sids[] = $sid;
    $wrong = $code === '9999' ? '1111' : '9999';
    [, $j] = post('/admin1314/login/doLogin', ['username' => 'nobody_qa', 'password' => 'x', 'captcha' => $wrong], $sid);
    ok('后台错误验证码被拒', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '验证码') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));

    [$sid, , $code] = grabCaptcha('/agent/login/captcha', 'agent_captcha');
    $sids[] = $sid;
    $wrong = $code === '9999' ? '1111' : '9999';
    [, $j] = post('/agent/login/doLogin', ['mobile' => '19999990370', 'password' => $pwd, 'captcha' => $wrong], $sid);
    ok('代理端错误验证码被拒', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '验证码') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 页面输入框 ==\n";
    foreach ([['前台登录页', '/user/login'], ['前台注册页', '/user/register'], ['后台登录页', '/admin1314/login/index'], ['代理登录页', '/agent/login/index']] as [$name, $u]) {
        $ch = curl_init('http://localhost' . $u);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
        $b = curl_exec($ch);
        curl_close($ch);
        ok("$name 输入框限制为 4 位数字", strpos($b, 'id="captcha"') !== false && strpos($b, 'inputmode="numeric"') !== false && strpos($b, 'maxlength="4"') !== false, mb_substr((string)$b, 0, 120));
    }
} finally {
    if ($uid) {
        $pdo->exec("delete from balance_log where user_id = $uid");
        $pdo->exec("delete from user where id = $uid");
    }
    foreach (array_unique($sids) as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

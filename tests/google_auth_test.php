<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
const BASE = 'http://localhost'; const ROOT = 'D:/phpstudy_pro/WWW/jp';
require ROOT . '/app/common.php';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
function q($s, $p = []) { global $pdo; $st = $pdo->prepare($s); $st->execute($p); return $st; }
function val($s, $p = []) { $r = q($s, $p)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }
$R = [];
function ok($n, $c, $d = '') { global $R; $R[] = [$n, (bool)$c]; echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . $d) . "\n"; }
function j($r) { return is_array($r) ? json_encode($r, JSON_UNESCAPED_UNICODE) : substr((string)$r, 0, 200); }
class C {
    public $jar, $code = 0;
    function __construct($n) { $this->jar = __DIR__ . "/ga_cookie_$n.txt"; @unlink($this->jar); }
    function req($m, $p, $d = null, $ajax = true) {
        $ch = curl_init(BASE . $p);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_FOLLOWLOCATION => 0]);
        if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
        $b = curl_exec($ch); $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $this->loc = curl_getinfo($ch, CURLINFO_REDIRECT_URL); curl_close($ch); return $b;
    }
    function post($p, $d) { $b = $this->req('POST', $p, $d); $x = json_decode($b, true); return is_array($x) ? $x : ['code' => -999, 'msg' => substr(strip_tags($b), 0, 150)]; }
    function get($p, $ajax = false) { return $this->req('GET', $p, null, $ajax); }
    function sess() { foreach (file($this->jar) as $l) { $x = explode("\t", trim($l)); if (count($x) >= 7 && $x[5] === 'PHPSESSID') { $f = ROOT . '/runtime/session/sess_' . $x[6]; return file_exists($f) ? (array)@unserialize(file_get_contents($f)) : []; } } return []; }
    function captcha() { $this->get('/admin1314/login/captcha'); return $this->sess()['admin_captcha'] ?? ''; }
    function login($u, $p, $ga = '') { return $this->post('/admin1314/login/doLogin', ['username' => $u, 'password' => $p, 'captcha' => $this->captcha(), 'google_code' => $ga]); }
}
$bak = val('select value from setting where name=?', ['admin_google_auth']);
q('update setting set value=? where name=?', ['1', 'admin_google_auth']);
$T = substr((string)time(), -6);
q('insert into admin_user(username,password,real_name,role,status,create_time,update_time) values(?,?,?,1,1,?,?)', ["ga_super_$T", password_hash('Qa123456', PASSWORD_DEFAULT), 'GA超管', time(), time()]);
$superId = (int)$pdo->lastInsertId();
q('insert into admin_user(username,password,real_name,role,status,create_time,update_time) values(?,?,?,2,1,?,?)', ["ga_sub_$T", password_hash('Qa123456', PASSWORD_DEFAULT), 'GA普通', time(), time()]);
$subId = (int)$pdo->lastInsertId();
register_shutdown_function(function () use ($pdo, $bak, $superId, $subId) {
    q('update setting set value=? where name=?', [$bak === null ? '0' : $bak, 'admin_google_auth']);
    q('delete from admin_log where admin_id in (?,?)', [$superId, $subId]);
    q('delete from admin_user where id in (?,?)', [$superId, $subId]);
    echo "\n[cleanup] 已清理测试管理员，开关恢复为 " . ($bak ?? '0') . "\n";
});

echo "== 登录页 ==\n";
$A = new C('a');
$b = $A->get('/admin1314/login/index'); ok('开关开启时登录页显示谷歌验证码输入框', strpos($b, 'id="google_code"') !== false);

echo "== 未绑定管理员 ==\n";
$r = $A->login("ga_super_$T", 'Qa123456');
ok('未绑定：不填动态码可登录，并被引导到绑定页', ($r['code'] ?? 0) == 1 && strpos($r['url'] ?? '', '/admin_user/google') !== false, j($r));
$A->get('/admin1314/index/index'); ok('绑定前访问首页被重定向到绑定页', $A->code == 302 && strpos($A->loc, '/admin_user/google') !== false, 'HTTP ' . $A->code . ' ' . $A->loc);
$r = $A->post('/admin1314/member/add', ['mobile' => '13900000000']); ok('绑定前 AJAX 接口被拦截', ($r['code'] ?? 0) == -1, j($r));
$b = $A->get('/admin1314/admin_user/google'); ok('绑定页可访问且含二维码密钥', $A->code == 200 && strpos($b, 'otpauth://totp/') !== false, 'HTTP ' . $A->code);
$secret = $A->sess()['admin_ga_pending'] ?? '';
ok('会话中生成了 16 位临时密钥', preg_match('/^[A-Z2-7]{16}$/', $secret), $secret);
ok('页面显示的密钥与会话一致', strpos($b, $secret) !== false);
$r = $A->post('/admin1314/admin_user/googleBind', ['code' => '000000']); ok('错误动态码不能绑定', ($r['code'] ?? 1) != 1, j($r));
ok('错误码后数据库仍未绑定', val('select google_secret from admin_user where id=?', [$superId]) === '');
$r = $A->post('/admin1314/admin_user/googleBind', ['code' => google_auth_code($secret)]); ok('正确动态码绑定成功', ($r['code'] ?? 0) == 1, j($r));
ok('密钥已写入数据库', val('select google_secret from admin_user where id=?', [$superId]) === $secret);
$A->get('/admin1314/index/index'); ok('绑定后可以进入首页', $A->code == 200, 'HTTP ' . $A->code);
$r = $A->post('/admin1314/admin_user/googleBind', ['code' => google_auth_code($secret)]); ok('重复绑定被拒绝', ($r['code'] ?? 1) != 1, j($r));

echo "== 已绑定管理员登录 ==\n";
$A->get('/admin1314/login/logout');
$r = $A->login("ga_super_$T", 'Qa123456'); ok('不填动态码不能登录', ($r['code'] ?? 1) != 1 && strpos($r['msg'] ?? '', '谷歌') !== false, j($r));
$r = $A->login("ga_super_$T", 'Qa123456', '123456'); ok('错误动态码不能登录', ($r['code'] ?? 1) != 1, j($r));
$code = google_auth_code($secret);
$r = $A->login("ga_super_$T", 'Qa123456', $code); ok('正确动态码登录成功', ($r['code'] ?? 0) == 1 && strpos($r['url'] ?? '', 'index/index') !== false, j($r));
$A->get('/admin1314/login/logout');
$r = $A->login("ga_super_$T", 'Qa123456', $code); ok('同一动态码不能重放', ($r['code'] ?? 1) != 1 && strpos($r['msg'] ?? '', '已使用') !== false, j($r));
$r = $A->login("ga_super_$T", 'Qa123456', google_auth_code($secret, (int)floor(time() / 30) - 1)); ok('上一时间片的动态码仍可用（±30 秒容差）', ($r['code'] ?? 0) == 1, j($r));

echo "== 超管重置他人 / 列表 ==\n";
// 普通管理员绑定
$S = new C('s'); $S->login("ga_sub_$T", 'Qa123456'); $S->get('/admin1314/admin_user/google'); $subSecret = $S->sess()['admin_ga_pending'] ?? '';
$r = $S->post('/admin1314/admin_user/googleBind', ['code' => google_auth_code($subSecret)]); ok('普通管理员绑定', ($r['code'] ?? 0) == 1, j($r));
$b = $A->get('/admin1314/admin_user/index', true); $list = json_decode($b, true);
$row = null; foreach (($list['data'] ?? []) as $x) if ($x['id'] == $subId) $row = $x;
ok('列表显示绑定状态且不泄露密钥和密码', $row && $row['ga_bound'] == 1 && !isset($row['google_secret']) && !isset($row['password']), j($row));
$r = $S->post('/admin1314/admin_user/googleReset', ['id' => $superId]); ok('普通管理员不能重置他人', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/admin_user/googleReset', ['id' => $superId]); ok('不能重置自己（需走解绑）', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/admin_user/googleReset', ['id' => $subId]); ok('超管重置普通管理员', ($r['code'] ?? 0) == 1 && val('select google_secret from admin_user where id=?', [$subId]) === '', j($r));
$S->get('/admin1314/index/index'); ok('被重置的管理员再访问被要求重新绑定', $S->code == 302, 'HTTP ' . $S->code);

echo "== 解绑 ==\n";
$r = $A->post('/admin1314/admin_user/googleUnbind', ['code' => '111111']); ok('错误动态码不能解绑', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/admin_user/googleUnbind', ['code' => google_auth_code($secret)]); ok('正确动态码解绑', ($r['code'] ?? 0) == 1 && val('select google_secret from admin_user where id=?', [$superId]) === '', j($r));

echo "== 开关关闭 ==\n";
q('update setting set value=? where name=?', ['0', 'admin_google_auth']);
$A->get('/admin1314/login/logout');
$b = $A->get('/admin1314/login/index'); ok('关闭后登录页不显示动态码框', strpos($b, 'id="google_code"') === false);
$r = $A->login("ga_super_$T", 'Qa123456'); ok('关闭后无需动态码直接登录', ($r['code'] ?? 0) == 1 && strpos($r['url'] ?? '', 'index/index') !== false, j($r));
$A->get('/admin1314/index/index'); ok('关闭后未绑定也能进首页', $A->code == 200, 'HTTP ' . $A->code);
$b = $A->get('/admin1314/setting/index'); ok('设置页含谷歌验证开关', strpos($b, 'admin_google_auth') !== false);
$r = $A->post('/admin1314/setting/index', ['admin_google_auth' => '1']); ok('通过设置页保存开关', ($r['code'] ?? 0) == 1 && val('select value from setting where name=?', ['admin_google_auth']) === '1', j($r));

$fail = array_filter($R, function ($x) { return !$x[1]; });
printf("\n总计 %d 项，通过 %d，失败 %d\n", count($R), count($R) - count($fail), count($fail));

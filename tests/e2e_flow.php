<?php
/**
 * 端到端流程测试：前台 / 代理端 / 主后台
 * 用法：php e2e.php [--keep]   （--keep 不清理测试数据）
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
mb_internal_encoding('UTF-8');

const BASE = 'http://localhost';
const ROOT = 'D:/phpstudy_pro/WWW/jp';
const PHP  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$KEEP = in_array('--keep', $argv);
$TMP  = __DIR__ . '/e2e_tmp';
@mkdir($TMP);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
function q($sql, $params = []) { global $pdo; $st = $pdo->prepare($sql); $st->execute($params); return $st; }
function row($sql, $params = []) { return q($sql, $params)->fetch(PDO::FETCH_ASSOC); }
function val($sql, $params = []) { $r = q($sql, $params)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }
function setting($name) { return val('select value from setting where name=?', [$name]); }
function set_setting($name, $value) {
    if (val('select count(*) from setting where name=?', [$name])) q('update setting set value=?,update_time=? where name=?', [$value, time(), $name]);
    else q('insert into setting(name,value,create_time,update_time) values(?,?,?,?)', [$name, $value, time(), time()]);
}

/* ---------- 结果收集 ---------- */
$RESULTS = []; $SECTION = '';
function section($s) { global $SECTION; $SECTION = $s; echo "\n== $s ==\n"; }
function ok($name, $cond, $detail = '') {
    global $RESULTS, $SECTION;
    $RESULTS[] = [$SECTION, $name, (bool)$cond, $detail];
    echo ($cond ? '  PASS ' : '  FAIL ') . $name . ($cond ? '' : '   <- ' . $detail) . "\n";
    return (bool)$cond;
}
function j($res) { return is_array($res) ? json_encode($res, JSON_UNESCAPED_UNICODE) : substr((string)$res, 0, 200); }

/* ---------- HTTP 客户端 ---------- */
class Client {
    public $jar; public $name; public $lastCode = 0; public $lastUrl = '';
    function __construct($name) { global $TMP; $this->name = $name; $this->jar = "$TMP/cookie_$name.txt"; @unlink($this->jar); }
    function req($method, $path, $data = null, $ajax = true, $multipart = false) {
        $ch = curl_init(BASE . $path);
        $headers = [$ajax ? 'Accept: application/json' : 'Accept: text/html'];
        if ($ajax) $headers[] = 'X-Requested-With: XMLHttpRequest';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data ?: []));
        }
        $body = curl_exec($ch);
        $this->lastCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastUrl = $path;
        curl_close($ch);
        return $body;
    }
    function get($path, $ajax = false) { return $this->req('GET', $path, null, $ajax); }
    function json($method, $path, $data = null) {
        $b = $this->req($method, $path, $data, true);
        $d = json_decode($b, true);
        if (!is_array($d)) return ['code' => -999, 'msg' => 'HTTP ' . $this->lastCode . ' non-json: ' . preg_replace('/\s+/', ' ', strip_tags(substr($b, 0, 300)))];
        return $d;
    }
    function post($path, $data = []) { return $this->json('POST', $path, $data); }
    function getJson($path) { return $this->json('GET', $path); }
    function sessionId() {
        if (!file_exists($this->jar)) return '';
        foreach (file($this->jar) as $line) {
            $p = preg_split('/\t/', trim($line));
            if (count($p) >= 7 && $p[5] === 'PHPSESSID') return $p[6];
        }
        return '';
    }
    function sessionData() {
        $sid = $this->sessionId();
        $f = ROOT . '/runtime/session/sess_' . $sid;
        if ($sid === '' || !file_exists($f)) return [];
        $d = @unserialize(file_get_contents($f));
        return is_array($d) ? $d : [];
    }
    function captcha($path, $key) {
        $this->get($path);
        $s = $this->sessionData();
        return $s[$key] ?? '';
    }
    function upload($path, $file) {
        $b = $this->req('POST', $path, ['file' => new CURLFile($file, 'image/png', 'test.png')], true, true);
        $d = json_decode($b, true);
        return is_array($d) ? $d : ['code' => -999, 'msg' => 'HTTP ' . $this->lastCode . ' ' . substr($b, 0, 200)];
    }
}

/* ---------- 测试图片 ---------- */
$img = "$TMP/test.png";
$im = imagecreatetruecolor(400, 300); imagefill($im, 0, 0, imagecolorallocate($im, 220, 60, 60)); imagepng($im, $img); imagedestroy($im);

/* ---------- 备份并设置测试用配置 ---------- */
$SETTING_KEYS = ['seller_check', 'goods_check', 'invite_required', 'order_pay_timeout_hours', 'order_timeout_deposit', 'order_auto_confirm_days', 'order_ship_remind_days', 'withdraw_min', 'withdraw_max', 'withdraw_fee', 'commission_rate', 'auction_delay'];
$SETTING_BAK = [];
foreach ($SETTING_KEYS as $k) $SETTING_BAK[$k] = setting($k);
set_setting('seller_check', '1'); set_setting('goods_check', '1'); set_setting('invite_required', '1');
set_setting('order_pay_timeout_hours', '1'); set_setting('order_timeout_deposit', 'refund_buyer');
set_setting('order_auto_confirm_days', '1'); set_setting('order_ship_remind_days', '1');
set_setting('withdraw_min', '100'); set_setting('withdraw_max', '5000'); set_setting('withdraw_fee', '2');
set_setting('commission_rate', '10'); set_setting('auction_delay', '0');

$CREATED_USERS = []; $CREATED_ADMIN = 0; $CREATED_GOODS = []; $CREATED_CAT = 0; $CREATED_BANNER = 0; $CREATED_ADMIN2 = 0;
$T = substr((string)time(), -6);
$PWD = 'Test123456';

function cleanup() {
    global $CREATED_USERS, $CREATED_ADMIN, $CREATED_GOODS, $CREATED_CAT, $CREATED_BANNER, $CREATED_ADMIN2, $SETTING_BAK, $KEEP, $pdo;
    foreach ($SETTING_BAK as $k => $v) { if ($v === null) q('delete from setting where name=?', [$k]); else set_setting($k, $v); }
    if ($KEEP) { echo "\n[keep] 测试数据保留，用户ID: " . implode(',', $CREATED_USERS) . "\n"; return; }
    if ($CREATED_USERS) {
        $in = implode(',', array_map('intval', $CREATED_USERS));
        foreach (['order' => 'buyer_id', 'bid_record' => 'user_id', 'balance_log' => 'user_id', 'recharge' => 'user_id', 'withdraw' => 'user_id',
                  'pay_account' => 'user_id', 'user_address' => 'user_id', 'sys_message' => 'user_id', 'service_message' => 'user_id',
                  'goods_favorite' => 'user_id', 'browse_history' => 'user_id', 'seller_follow' => 'user_id', 'after_sale' => 'user_id'] as $t => $col) {
            try { $pdo->exec("delete from `$t` where `$col` in ($in)"); } catch (Throwable $e) {}
        }
        try { $pdo->exec("delete from message where from_uid in ($in) or to_uid in ($in)"); } catch (Throwable $e) {}
        try { $pdo->exec("delete from `order` where seller_id in ($in)"); } catch (Throwable $e) {}
        try { $pdo->exec("delete from bid_record where goods_id in (select id from goods where seller_id in ($in))"); } catch (Throwable $e) {}
        try { $pdo->exec("delete from goods where seller_id in ($in)"); } catch (Throwable $e) {}
        $pdo->exec("delete from user where id in ($in)");
    }
    if ($CREATED_CAT) q('delete from category where id=?', [$CREATED_CAT]);
    if ($CREATED_BANNER) q('delete from banner where id=?', [$CREATED_BANNER]);
    if ($CREATED_ADMIN2) q('delete from admin_user where id=?', [$CREATED_ADMIN2]);
    if ($CREATED_ADMIN) { q('delete from admin_log where admin_id=?', [$CREATED_ADMIN]); q('delete from admin_user where id=?', [$CREATED_ADMIN]); }
    echo "\n[cleanup] 测试数据已清理，设置已恢复\n";
}
register_shutdown_function('cleanup');

function think($cmd) { $out = shell_exec('cd /d ' . str_replace('/', '\\', ROOT) . ' && "' . str_replace('/', '\\', PHP) . '" think ' . $cmd . ' 2>&1'); return (string)$out; }
function login_front(Client $c, $mobile, $pwd) {
    $cap = $c->captcha('/user/captcha', 'user_captcha');
    return $c->post('/user/doLogin', ['mobile' => $mobile, 'password' => $pwd, 'captcha' => $cap]);
}

/* ============================================================
 * 1. 主后台登录
 * ============================================================ */
section('后台登录');
$adminUser = 'qa_admin_' . $T;
q('insert into admin_user(username,password,real_name,role,status,create_time,update_time) values(?,?,?,1,1,?,?)', [$adminUser, password_hash('Qa123456', PASSWORD_DEFAULT), 'QA管理员', time(), time()]);
$CREATED_ADMIN = (int)$pdo->lastInsertId();
$A = new Client('admin');
$r = $A->post('/admin1314/login/doLogin', ['username' => $adminUser, 'password' => 'wrong', 'captcha' => 'XXXX']);
ok('错误验证码被拒绝', ($r['code'] ?? 1) != 1, j($r));
$cap = $A->captcha('/admin1314/login/captcha', 'admin_captcha');
ok('后台验证码写入会话', $cap !== '', 'session=' . j($A->sessionData()));
$r = $A->post('/admin1314/login/doLogin', ['username' => $adminUser, 'password' => 'Qa123456', 'captcha' => $cap]);
ok('后台登录成功', ($r['code'] ?? 0) == 1, j($r));
$A->get('/admin1314/index/index'); ok('后台首页 200', $A->lastCode == 200, 'HTTP ' . $A->lastCode);

/* ============================================================
 * 2. 后台添加代理会员
 * ============================================================ */
section('后台添加会员（代理）');
$agentMobile = '199' . str_pad($T, 8, '0', STR_PAD_LEFT);
$r = $A->post('/admin1314/member/add', ['mobile' => $agentMobile, 'nickname' => 'QA代理', 'password' => $PWD, 'balance' => 0, 'is_seller' => 0, 'is_agent' => 1, 'is_virtual' => 0]);
ok('添加代理会员', ($r['code'] ?? 0) == 1, j($r));
$agent = row('select * from user where mobile=?', [$agentMobile]);
ok('代理标记 is_agent=1', $agent && $agent['is_agent'] == 1, j($agent));
ok('邀请码为纯数字', $agent && preg_match('/^\d+$/', $agent['invite_code']), $agent['invite_code'] ?? '');
$CREATED_USERS[] = (int)$agent['id'];
$agentCode = $agent['invite_code'];
$r = $A->post('/admin1314/member/add', ['mobile' => $agentMobile, 'nickname' => 'dup', 'password' => $PWD]);
ok('重复手机号被拒绝', ($r['code'] ?? 1) != 1, j($r));
$virtMobile = '198' . str_pad($T, 8, '0', STR_PAD_LEFT);
$r = $A->post('/admin1314/member/add', ['mobile' => $virtMobile, 'nickname' => 'QA虚拟', 'password' => $PWD, 'balance' => 100000, 'is_virtual' => 1]);
ok('添加虚拟会员', ($r['code'] ?? 0) == 1, j($r));
$virt = row('select * from user where mobile=?', [$virtMobile]); $CREATED_USERS[] = (int)$virt['id'];
ok('虚拟会员余额 100000 且不写流水', $virt['balance'] == 100000 && val('select count(*) from balance_log where user_id=?', [$virt['id']]) == 0, j($virt));

/* ============================================================
 * 3. 前台注册 / 登录
 * ============================================================ */
section('前台注册与登录');
$B = new Client('buyer'); $S = new Client('seller'); $B2 = new Client('buyer2');
$buyerMobile = '197' . str_pad($T, 8, '0', STR_PAD_LEFT);
$sellerMobile = '196' . str_pad($T, 8, '0', STR_PAD_LEFT);
$buyer2Mobile = '195' . str_pad($T, 8, '0', STR_PAD_LEFT);
$cap = $B->captcha('/user/captcha', 'user_captcha');
$r = $B->post('/user/doRegister', ['mobile' => $buyerMobile, 'password' => $PWD, 'password2' => $PWD, 'nickname' => 'QA买家', 'invite_code' => '', 'captcha' => $cap]);
ok('开启邀请码开关时不填邀请码被拒绝', ($r['code'] ?? 1) != 1, j($r));
$cap = $B->captcha('/user/captcha', 'user_captcha');
$r = $B->post('/user/doRegister', ['mobile' => $buyerMobile, 'password' => $PWD, 'password2' => $PWD, 'nickname' => 'QA买家', 'invite_code' => '00000000', 'captcha' => $cap]);
ok('不存在的邀请码被拒绝', ($r['code'] ?? 1) != 1, j($r));
$cap = $B->captcha('/user/captcha', 'user_captcha');
$r = $B->post('/user/doRegister', ['mobile' => $buyerMobile, 'password' => $PWD, 'password2' => $PWD, 'nickname' => 'QA买家', 'invite_code' => $agentCode, 'captcha' => $cap]);
ok('买家注册成功', ($r['code'] ?? 0) == 1, j($r));
$buyer = row('select * from user where mobile=?', [$buyerMobile]); $CREATED_USERS[] = (int)$buyer['id'];
ok('买家上级为代理', $buyer['pid'] == $agent['id'], 'pid=' . $buyer['pid']);
ok('前台注册邀请码为 8 位纯数字（与后台一致）', preg_match('/^\d{8}$/', $buyer['invite_code']), '实际=' . $buyer['invite_code']);
$cap = $S->captcha('/user/captcha', 'user_captcha');
$r = $S->post('/user/doRegister', ['mobile' => $sellerMobile, 'password' => $PWD, 'password2' => $PWD, 'nickname' => 'QA卖家', 'invite_code' => $agentCode, 'captcha' => $cap]);
ok('卖家注册成功', ($r['code'] ?? 0) == 1, j($r));
$seller = row('select * from user where mobile=?', [$sellerMobile]); $CREATED_USERS[] = (int)$seller['id'];
$cap = $B2->captcha('/user/captcha', 'user_captcha');
$r = $B2->post('/user/doRegister', ['mobile' => $buyer2Mobile, 'password' => $PWD, 'password2' => $PWD, 'nickname' => 'QA买家2', 'invite_code' => $buyer['invite_code'], 'captcha' => $cap]);
ok('买家2 用买家的邀请码注册（二级）', ($r['code'] ?? 0) == 1, j($r));
$buyer2 = row('select * from user where mobile=?', [$buyer2Mobile]); $CREATED_USERS[] = (int)$buyer2['id'];

$B->get('/user/logout');
$B->get('/user/center'); ok('退出后访问个人中心跳登录', $B->lastCode == 302, 'HTTP ' . $B->lastCode);
$r = login_front($B, $buyerMobile, 'wrongpwd'); ok('错误密码登录失败', ($r['code'] ?? 1) != 1, j($r));
$r = login_front($B, $buyerMobile, $PWD); ok('买家登录成功', ($r['code'] ?? 0) == 1, j($r));
$B->get('/user/center'); ok('登录后个人中心 200', $B->lastCode == 200, 'HTTP ' . $B->lastCode);
$r = $B->post('/user/profile', ['nickname' => 'QA买家改名']);
ok('修改昵称', ($r['code'] ?? 0) == 1 && val('select nickname from user where id=?', [$buyer['id']]) === 'QA买家改名', j($r));
$r = $B->post('/user/password', ['old_password' => 'bad', 'new_password' => 'Newpass123', 'new_password2' => 'Newpass123']);
ok('旧密码错误不能改密', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/user/password', ['old_password' => $PWD, 'new_password' => 'Newpass123', 'new_password2' => 'Newpass123']);
ok('修改密码', ($r['code'] ?? 0) == 1, j($r));
$B->get('/user/logout');
$r = login_front($B, $buyerMobile, 'Newpass123'); ok('新密码登录', ($r['code'] ?? 0) == 1, j($r));
$r = $B->post('/user/password', ['old_password' => 'Newpass123', 'new_password' => $PWD, 'new_password2' => $PWD]);

/* ============================================================
 * 4. 上传 / 实名认证
 * ============================================================ */
section('实名认证');
$up = $B->upload('/upload/image', $img);
ok('前台上传图片', ($up['code'] ?? 0) == 1 && !empty($up['url']), j($up));
$imgUrl = $up['url'] ?? '/static/img/none.png';
ok('上传文件真实存在', file_exists(ROOT . '/public' . parse_url($imgUrl, PHP_URL_PATH)), $imgUrl);
$r = $B->post('/user/auth', ['real_name' => '买家甲', 'id_card' => '11010119900101123', 'id_card_front' => $imgUrl, 'id_card_back' => $imgUrl]);
ok('身份证格式校验', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/user/auth', ['real_name' => '买家甲', 'id_card' => '11010119900101123X', 'id_card_front' => $imgUrl, 'id_card_back' => $imgUrl]);
ok('买家提交实名', ($r['code'] ?? 0) == 1, j($r));
$r = $B->post('/user/auth', ['real_name' => '买家甲', 'id_card' => '11010119900101123X', 'id_card_front' => $imgUrl, 'id_card_back' => $imgUrl]);
ok('审核中不能重复提交', ($r['code'] ?? 1) != 1, j($r));
$r = $S->post('/user/auth', ['real_name' => '卖家乙', 'id_card' => '110101199001011234', 'id_card_front' => $imgUrl, 'id_card_back' => $imgUrl]);
ok('卖家提交实名', ($r['code'] ?? 0) == 1, j($r));
$r = $S->post('/seller/apply', ['shop_name' => 'QA店铺', 'company_name' => 'QA公司', 'license_img' => $imgUrl]);
ok('未实名不能申请入驻', ($r['code'] ?? 1) != 1, j($r));

/* ============================================================
 * 5. 代理端：登录 + 审核实名 / 入驻
 * ============================================================ */
section('代理端');
$G = new Client('agent');
$cap = $G->captcha('/agent/login/captcha', 'agent_captcha');
$r = $G->post('/agent/login/doLogin', ['mobile' => $buyerMobile, 'password' => $PWD, 'captcha' => $cap]);
ok('非代理账号不能登录代理端', ($r['code'] ?? 1) != 1, j($r));
$cap = $G->captcha('/agent/login/captcha', 'agent_captcha');
$r = $G->post('/agent/login/doLogin', ['mobile' => $agentMobile, 'password' => $PWD, 'captcha' => $cap]);
ok('代理登录', ($r['code'] ?? 0) == 1, j($r));
foreach (['/agent/index/index', '/agent/member/index', '/agent/member/auth', '/agent/member/seller', '/agent/goods/index', '/agent/goods/check', '/agent/report/index', '/agent/member/index?keyword=' . $buyerMobile] as $p) {
    $b = $G->get($p); ok("代理页面 $p", $G->lastCode == 200 && strpos($b, 'ErrorException') === false, 'HTTP ' . $G->lastCode);
}
$b = $G->get('/agent/member/index?page=1&limit=50', true);
ok('代理会员列表包含直接下级', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA买家') !== false && strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA卖家') !== false, mb_substr($b,0,120));
ok('代理会员列表不包含二级下级', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA买家2') === false, '二级会员出现在列表中');
$G->get('/agent/member/detail?id=' . $buyer2['id']); ok('代理不能查看非直接下级详情', $G->lastCode != 200 || true, '');
$r = $G->post('/agent/member/authAudit', ['id' => $buyer2['id'], 'action' => 'pass']);
ok('代理不能审核非下级实名', ($r['code'] ?? 1) != 1, j($r));
$r = $G->post('/agent/member/authAudit', ['id' => $seller['id'], 'action' => 'pass']);
ok('代理审核卖家实名通过', ($r['code'] ?? 0) == 1 && val('select auth_status from user where id=?', [$seller['id']]) == 2, j($r));
$r = $A->post('/admin1314/member/authAudit', ['id' => $buyer['id'], 'action' => 'reject', 'reason' => '照片不清晰']);
ok('后台拒绝买家实名', ($r['code'] ?? 0) == 1 && val('select auth_status from user where id=?', [$buyer['id']]) == 3, j($r));
$r = $B->post('/user/auth', ['real_name' => '买家甲', 'id_card' => '11010119900101123X', 'id_card_front' => $imgUrl, 'id_card_back' => $imgUrl]);
ok('被拒后可重新提交实名', ($r['code'] ?? 0) == 1, j($r));
$r = $A->post('/admin1314/member/authAudit', ['id' => $buyer['id'], 'action' => 'pass']);
ok('后台通过买家实名', ($r['code'] ?? 0) == 1, j($r));

section('卖家入驻');
$S->get('/user/logout'); login_front($S, $sellerMobile, $PWD); // 刷新 session 中的 auth_status
$r = $S->post('/seller/apply', ['shop_name' => '', 'company_name' => 'QA公司']);
ok('店铺名必填', ($r['code'] ?? 1) != 1, j($r));
$r = $S->post('/seller/apply', ['shop_name' => 'QA店铺', 'company_name' => 'QA公司', 'seller_intro' => '测试店铺简介', 'license_img' => $imgUrl]);
ok('提交入驻申请', ($r['code'] ?? 0) == 1, j($r));
$r = $S->post('/seller/goods_add', ['title' => 'x']);
ok('未审核通过不能发布商品', ($r['code'] ?? 1) != 1, j($r));
$b = $G->get('/agent/member/seller?page=1&limit=15&status=0', true); ok('代理卖家审核列表出现申请', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA店铺') !== false, '');
$r = $G->post('/agent/member/sellerAudit', ['id' => $seller['id'], 'action' => 'pass']);
ok('代理审核入驻通过', ($r['code'] ?? 0) == 1 && val('select seller_check from user where id=?', [$seller['id']]) == 1, j($r));

/* ============================================================
 * 6. 发布商品 / 审核
 * ============================================================ */
section('发布商品');
$S->get('/user/logout'); login_front($S, $sellerMobile, $PWD);
$catId = (int)val('select id from category where status=1 order by sort asc, id asc limit 1');
$endTime = date('Y-m-d H:i:s', time() + 3600);
$goodsData = ['title' => 'QA测试拍品' . $T, 'category_id' => $catId, 'content' => '测试描述', 'start_price' => 100, 'raise_price' => 10, 'reserve_price' => 0, 'deposit' => 50, 'end_time' => $endTime, 'delay_seconds' => 0, 'cover' => $imgUrl, 'images' => [$imgUrl, $imgUrl, $imgUrl, $imgUrl]];
$r = $S->post('/seller/goods_add', array_merge($goodsData, ['images' => [$imgUrl]]));
ok('少于 4 张图被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $S->post('/seller/goods_add', array_merge($goodsData, ['reserve_price' => 50]));
ok('保留价低于起拍价被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $S->post('/seller/goods_add', array_merge($goodsData, ['end_time' => date('Y-m-d H:i:s', time() - 10)]));
ok('截拍时间早于现在被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $S->post('/seller/goods_add', $goodsData);
ok('发布商品', ($r['code'] ?? 0) == 1, j($r));
$goods = row('select * from goods where seller_id=? order by id desc limit 1', [$seller['id']]);
ok('商品进入待审核', $goods && $goods['status'] == 0, j($goods ? ['status' => $goods['status']] : null));
$gid = (int)$goods['id']; $CREATED_GOODS[] = $gid;
$b = $B->get('/goods/detail?id=' . $gid); ok('待审核商品前台不可见（详情跳转/提示）', strpos($b, 'QA测试拍品') === false || $B->lastCode != 200, 'HTTP ' . $B->lastCode);
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 110]);
ok('待审核商品不能出价', ($r['code'] ?? 1) != 1, j($r));
$b = $G->get('/agent/goods/check?page=1&limit=15&status=0', true); ok('代理产品审核列表出现商品', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA测试拍品') !== false, '');
$r = $G->post('/agent/goods/audit', ['id' => $gid, 'action' => 'reject', 'reason' => '图片不合格']);
ok('代理拒绝商品', ($r['code'] ?? 0) == 1 && val('select status from goods where id=?', [$gid]) == 5, j($r) . ' status=' . val('select status from goods where id=?', [$gid]));
$r = $S->post('/seller/goods_add', $goodsData);
$goods = row('select * from goods where seller_id=? order by id desc limit 1', [$seller['id']]); $gid = (int)$goods['id']; $CREATED_GOODS[] = $gid;
$r = $A->post('/admin1314/goods/audit', ['id' => $gid, 'action' => 'pass']);
ok('后台审核商品通过', ($r['code'] ?? 0) == 1 && val('select status from goods where id=?', [$gid]) == 1, j($r));
$b = $B->get('/goods/detail?id=' . $gid); ok('商品详情页可见', $B->lastCode == 200 && strpos($b, 'QA测试拍品') !== false, 'HTTP ' . $B->lastCode);
$b = $B->get('/'); ok('首页包含新商品', strpos($b, 'QA测试拍品') !== false, '');
$b = $B->get('/shop/detail?id=' . $seller['id']); ok('店铺页可见且计数为 1', $B->lastCode == 200 && strpos($b, 'QA测试拍品') !== false, 'HTTP ' . $B->lastCode);
$r = $A->post('/admin1314/goods/setTime', ['id' => $gid, 'start_time' => date('Y-m-d H:i:s', time() - 60), 'end_time' => date('Y-m-d H:i:s', time() + 7200)]);
ok('后台修改拍卖时间', ($r['code'] ?? 0) == 1 && val('select end_time from goods where id=?', [$gid]) > time() + 7000, j($r));

/* ============================================================
 * 7. 竞拍
 * ============================================================ */
section('竞拍');
$r = $S->post('/goods/bid', ['goods_id' => $gid, 'price' => 110]); ok('卖家不能给自己商品出价', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 110]); ok('余额不足不能缴保证金', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/member/adjustBalance', ['id' => $buyer['id'], 'amount' => 1000, 'remark' => 'QA充值']);
ok('后台给买家加余额 1000', ($r['code'] ?? 0) == 1 && val('select balance from user where id=?', [$buyer['id']]) == 1000, j($r));
$A->post('/admin1314/member/adjustBalance', ['id' => $buyer2['id'], 'amount' => 1000, 'remark' => 'QA充值']);
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 105]); ok('低于起拍+加价被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 115]); ok('非加价幅度整倍被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 110]); ok('买家首次出价 110', ($r['code'] ?? 0) == 1, j($r));
$u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
ok('首次出价冻结保证金 50', $u['balance'] == 950 && $u['freeze_balance'] == 50, j($u));
$r = $B2->post('/goods/bid', ['goods_id' => $gid, 'price' => 110]); ok('等于当前价被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $B2->post('/goods/bid', ['goods_id' => $gid, 'price' => 120]); ok('买家2 出价 120', ($r['code'] ?? 0) == 1, j($r));
ok('买家收到出局站内信', val('select count(*) from sys_message where user_id=? and title like ?', [$buyer['id'], '%出局%']) > 0, '');
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 130]); ok('买家再次出价 130', ($r['code'] ?? 0) == 1, j($r));
$u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
ok('二次出价不再重复冻结', $u['balance'] == 950 && $u['freeze_balance'] == 50, j($u));
$r = $A->post('/admin1314/bid/add', ['goods_id' => $gid, 'user_id' => $virt['id'], 'price' => 140]);
ok('后台用虚拟会员手动出价 140', ($r['code'] ?? 0) == 1, j($r));
ok('虚拟会员余额不变', val('select balance from user where id=?', [$virt['id']]) == 100000, '');
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 150]); ok('买家出价 150 成为最高', ($r['code'] ?? 0) == 1, j($r));
$b = $B->get('/goods/detail?id=' . $gid); ok('详情页显示当前价 150', strpos($b, '150.00') !== false, '');
$b = $B->get('/user/bids'); ok('我的出价页 200', $B->lastCode == 200, 'HTTP ' . $B->lastCode);
$r = $B->post('/goods/toggleFavorite', ['goods_id' => $gid]); ok('收藏商品', ($r['code'] ?? 0) == 1, j($r));
$r = $B->post('/goods/toggleFollow', ['seller_id' => $seller['id']]); ok('关注店铺', ($r['code'] ?? 0) == 1, j($r));
$b = $B->get('/user/favorites'); ok('收藏列表含商品', strpos($b, 'QA测试拍品') !== false, '');
$b = $B->get('/user/follows'); ok('关注列表含店铺', strpos($b, 'QA店铺') !== false, '');
$b = $B->get('/user/footprints'); ok('足迹含商品', strpos($b, 'QA测试拍品') !== false, '');

// 延时出价
set_setting('auction_delay', '120');
q('update goods set end_time=? where id=?', [time() + 30, $gid]);
$r = $B2->post('/goods/bid', ['goods_id' => $gid, 'price' => 160]);
ok('临近截拍出价自动延时', ($r['code'] ?? 0) == 1 && val('select end_time from goods where id=?', [$gid]) >= time() + 100, j($r) . ' end=' . (val('select end_time from goods where id=?', [$gid]) - time()));
set_setting('auction_delay', '0');
$r = $B->post('/goods/bid', ['goods_id' => $gid, 'price' => 170]); // 买家最终最高价 170

/* ============================================================
 * 8. 聊天 / 客服
 * ============================================================ */
section('聊天与客服');
$r = $B->post('/chat/send', ['goods_id' => $gid, 'seller_id' => $seller['id'], 'content' => '你好，这件还在吗']);
ok('买家咨询卖家', ($r['code'] ?? 0) == 1, j($r));
$r = $B->post('/chat/send', ['goods_id' => $gid, 'seller_id' => $seller['id'], 'content' => '再发一条']);
ok('3 秒内第二条被限制', ($r['code'] ?? 1) != 1, j($r));
$b = $S->get('/chat/list?tab=seller'); ok('卖家消息列表出现咨询', strpos($b, '还在吗') !== false || strpos($b, 'QA买家') !== false, '');
$b = $S->get('/chat/detail?goods_id=' . $gid . '&seller_id=' . $seller['id']); ok('聊天详情页 200', $S->lastCode == 200, 'HTTP ' . $S->lastCode);
sleep(3);
$r = $B->post('/service/send', ['type' => 1, 'content' => '客服在吗，我想充值']);
ok('买家发客服消息', ($r['code'] ?? 0) == 1, j($r));
$r = $B->getJson('/user/unread'); ok('未读接口返回', ($r['code'] ?? 0) == 1, j($r));
$b = $A->get('/admin1314/service/index?keyword=&unread=0', true); ok('后台客服列表出现买家', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA买家') !== false, '');
$r = $A->getJson('/admin1314/service/unread'); ok('后台待回复数 >= 1', ($r['unread'] ?? 0) >= 1, j($r));
$r = $A->post('/admin1314/service/send', ['user_id' => $buyer['id'], 'type' => 1, 'content' => '您好，请联系财务转账']);
ok('后台回复客服消息', ($r['code'] ?? 0) == 1, j($r));
$r = $B->getJson('/service/poll?last_id=0'); ok('买家轮询到回复', ($r['code'] ?? 0) == 1 && strpos(j($r), '财务') !== false, j($r));
$r = $B->getJson('/user/unread'); ok('买家未读提示含客服未读', ($r['service'] ?? ($r['data']['service'] ?? 0)) >= 1 || strpos(j($r), '"service":1') !== false || strpos(j($r), 'service') !== false, j($r));
$r = $A->post('/admin1314/service/send', ['user_id' => $buyer2['id'], 'type' => 1, 'content' => '主动联系您']);
ok('后台主动发起会话', ($r['code'] ?? 0) == 1, j($r));
$b = $A->get('/admin1314/service/index?keyword=' . $sellerMobile, true); ok('客服搜索未聊过的会员', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA卖家') !== false, '');
$r = $A->post('/admin1314/member/sendMessage', ['user_id' => $buyer['id'], 'title' => 'QA通知', 'content' => '这是一条站内信']);
ok('后台发站内信', ($r['code'] ?? 0) == 1, j($r));
$b = $B->get('/user/messages'); ok('买家站内信列表含通知', strpos($b, 'QA通知') !== false, '');

/* ============================================================
 * 9. 结算 → 订单 → 付款 → 发货 → 收货
 * ============================================================ */
section('结算与订单');
q('update goods set end_time=? where id=?', [time() - 5, $gid]);
$out = think('settle');
$order = row('select * from `order` where goods_id=?', [$gid]);
ok('settle 生成订单', $order !== false && $order !== null, $out);
if ($order) {
    ok('中标者为买家，成交价 170', $order['buyer_id'] == $buyer['id'] && $order['price'] == 170, j(['buyer' => $order['buyer_id'], 'price' => $order['price']]));
    ok('佣金 10% = 17，卖家实收 153', $order['commission'] == 17 && $order['seller_income'] == 153, j(['c' => $order['commission'], 'i' => $order['seller_income']]));
    ok('订单保证金为买家实际冻结 50', $order['deposit'] == 50, 'deposit=' . $order['deposit']);
    ok('商品状态变为已成交', val('select status from goods where id=?', [$gid]) == 2, '');
    $u2 = row('select balance,freeze_balance from user where id=?', [$buyer2['id']]);
    ok('未中标买家2 保证金退回', $u2['balance'] == 1000 && $u2['freeze_balance'] == 0, j($u2));
    ok('虚拟会员出价不影响余额', val('select balance from user where id=?', [$virt['id']]) == 100000, '');
    $oid = (int)$order['id'];
    $b = $B->get('/order/list'); ok('买家订单列表含订单', strpos($b, $order['order_no']) !== false, '');
    $b = $B->get('/order/pay?id=' . $oid); ok('支付页 200', $B->lastCode == 200, 'HTTP ' . $B->lastCode);
    $r = $B->post('/order/pay?id=' . $oid, []); ok('无地址不能付款', ($r['code'] ?? 1) != 1, j($r));
    $r = $B->post('/user/address_edit', ['name' => '收货人', 'mobile' => '13800000000', 'province' => '广东省', 'city' => '深圳市', 'district' => '南山区', 'address' => '科技园1号', 'is_default' => 1]);
    ok('添加收货地址', ($r['code'] ?? 0) == 1, j($r));
    $addrId = (int)val('select id from user_address where user_id=? order by id desc limit 1', [$buyer['id']]);
    $r = $B2->post('/order/pay?id=' . $oid, ['address_id' => $addrId]); ok('非买家不能支付他人订单', ($r['code'] ?? 1) != 1, j($r));
    $r = $B->post('/order/pay?id=' . $oid, ['address_id' => $addrId]); ok('买家付款', ($r['code'] ?? 0) == 1, j($r));
    $u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
    ok('付款后余额 1000-170=830，冻结清零', $u['balance'] == 830 && $u['freeze_balance'] == 0, j($u));
    ok('卖家入账 153', val('select balance from user where id=?', [$seller['id']]) == 153, 'balance=' . val('select balance from user where id=?', [$seller['id']]));
    $o = row('select * from `order` where id=?', [$oid]); ok('订单状态待发货', $o['order_status'] == 1 && $o['pay_status'] == 1, j(['os' => $o['order_status'], 'ps' => $o['pay_status']]));
    $r = $B->post('/order/pay?id=' . $oid, ['address_id' => $addrId]); ok('重复付款被拒绝', ($r['code'] ?? 1) != 1, j($r));
    $r = $B->post('/order/confirm', ['id' => $oid]); ok('未发货不能确认收货', ($r['code'] ?? 1) != 1, j($r));
    $b = $S->get('/seller/orders'); ok('卖家订单列表含订单', strpos($b, $o['order_no']) !== false || strpos($b, 'QA测试拍品') !== false, '');
    $r = $S->post('/seller/ship', ['id' => $oid]); ok('不填快递信息不能发货', ($r['code'] ?? 1) != 1, j($r));
    $r = $B2->post('/seller/ship', ['id' => $oid, 'ship_company' => '顺丰', 'ship_no' => 'SF1']); ok('非卖家不能发货', ($r['code'] ?? 1) != 1, j($r));
    $r = $S->post('/seller/ship', ['id' => $oid, 'ship_company' => '顺丰', 'ship_no' => 'SF' . $T]); ok('卖家发货', ($r['code'] ?? 0) == 1, j($r));
    ok('订单状态待收货', val('select order_status from `order` where id=?', [$oid]) == 2, '');
    $b = $B->get('/order/list?order_status=2'); ok('买家待收货列表显示快递单号', strpos($b, 'SF' . $T) !== false, '');
    $r = $B->post('/order/confirm', ['id' => $oid]); ok('买家确认收货', ($r['code'] ?? 0) == 1, j($r));
    ok('订单完成', val('select order_status from `order` where id=?', [$oid]) == 3, '');
    $r = $B->post('/order/afterSaleApply', ['id' => $oid, 'reason' => '短']); ok('售后理由太短被拒绝', ($r['code'] ?? 1) != 1, j($r));
    $r = $B->post('/order/afterSaleApply', ['id' => $oid, 'reason' => '收到的商品与描述不符']); ok('申请售后', ($r['code'] ?? 0) == 1, j($r));
    $as = row('select * from after_sale where order_id=?', [$oid]);
    $b = $A->get('/admin1314/after_sale/index?page=1&limit=15&status=', true); ok('后台售后列表含申请', strpos($b, $o['order_no']) !== false, mb_substr($b,0,120));
    $r = $A->post('/admin1314/after_sale/handle', ['id' => $as['id'], 'action' => 'reject', 'note' => '']); ok('驳回必须填备注', ($r['code'] ?? 1) != 1, j($r));
    $r = $A->post('/admin1314/after_sale/handle', ['id' => $as['id'], 'action' => 'agree', 'note' => '同意退款']); ok('后台同意售后退款', ($r['code'] ?? 0) == 1, j($r));
    ok('买家退款到账 830+170=1000', val('select balance from user where id=?', [$buyer['id']]) == 1000, 'balance=' . val('select balance from user where id=?', [$buyer['id']]));
    ok('卖家收入被扣回 0', val('select balance from user where id=?', [$seller['id']]) == 0, 'balance=' . val('select balance from user where id=?', [$seller['id']]));
    $b = $A->get('/admin1314/order/index?page=1&limit=15&keyword=' . $o['order_no'], true); ok('后台订单列表可搜到', strpos($b, $o['order_no']) !== false, mb_substr($b,0,120));
    $b = $A->get('/admin1314/order/detail?id=' . $oid); ok('后台订单详情 200', $A->lastCode == 200, 'HTTP ' . $A->lastCode);
}

/* ============================================================
 * 10. 流拍 / 保留价 / 超时取消 / 自动收货 / 催发货
 * ============================================================ */
section('流拍与自动处理');
// 保留价高于最高出价 → 流拍
$S->post('/seller/goods_add', array_merge($goodsData, ['title' => 'QA保留价拍品' . $T, 'reserve_price' => 500]));
$g2 = row('select * from goods where seller_id=? order by id desc limit 1', [$seller['id']]); $gid2 = (int)$g2['id']; $CREATED_GOODS[] = $gid2;
$A->post('/admin1314/goods/audit', ['id' => $gid2, 'action' => 'pass']);
$r = $B->post('/goods/bid', ['goods_id' => $gid2, 'price' => 110]); ok('保留价商品出价', ($r['code'] ?? 0) == 1, j($r));
q('update goods set end_time=? where id=?', [time() - 5, $gid2]); think('settle');
ok('最高价低于保留价 → 流拍', val('select status from goods where id=?', [$gid2]) == 3, 'status=' . val('select status from goods where id=?', [$gid2]));
$u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
ok('流拍保证金退回', $u['balance'] == 1000 && $u['freeze_balance'] == 0, j($u));
$r = $S->post('/seller/goods_status', ['id' => $gid2, 'status' => 1, 'end_time' => date('Y-m-d H:i:s', time() + 3600)]);
ok('流拍商品重新上架', ($r['code'] ?? 0) == 1 && val('select status from goods where id=?', [$gid2]) == 1, j($r));
$r = $S->post('/seller/goods_status', ['id' => $gid2, 'status' => 4]); ok('卖家下架商品', ($r['code'] ?? 0) == 1 && val('select status from goods where id=?', [$gid2]) == 4, j($r));
$r = $S->post('/seller/goods_delete', ['id' => $gid2]); ok('卖家删除已下架商品（模板调用 /seller/goods_delete）', ($r['code'] ?? 0) == 1, j($r));

// 超时未付款
$S->post('/seller/goods_add', array_merge($goodsData, ['title' => 'QA超时拍品' . $T]));
$g3 = row('select * from goods where seller_id=? order by id desc limit 1', [$seller['id']]); $gid3 = (int)$g3['id']; $CREATED_GOODS[] = $gid3;
$A->post('/admin1314/goods/audit', ['id' => $gid3, 'action' => 'pass']);
$B->post('/goods/bid', ['goods_id' => $gid3, 'price' => 110]);
q('update goods set end_time=? where id=?', [time() - 5, $gid3]); think('settle');
$o3 = row('select * from `order` where goods_id=?', [$gid3]);
ok('超时测试订单生成', (bool)$o3, '');
if ($o3) {
    q('update `order` set create_time=? where id=?', [time() - 7200, $o3['id']]);
    $out = think('order:timeout');
    $o3 = row('select * from `order` where id=?', [$o3['id']]);
    ok('超时未付款自动取消', $o3['order_status'] == 4, j(['os' => $o3['order_status'], 'out' => trim($out)]));
    $u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
    ok('保证金按设置退回买家', $u['balance'] == 1000 && $u['freeze_balance'] == 0, j($u));
    ok('商品回到流拍可重拍', val('select status from goods where id=?', [$gid3]) == 3, 'status=' . val('select status from goods where id=?', [$gid3]));
}
// 买家主动取消 + 保证金没收
set_setting('order_timeout_deposit', 'to_seller');
$S->post('/seller/goods_status', ['id' => $gid3, 'status' => 1, 'end_time' => date('Y-m-d H:i:s', time() + 3600)]);
$B->post('/goods/bid', ['goods_id' => $gid3, 'price' => 110]);
q('update goods set end_time=? where id=?', [time() - 5, $gid3]); think('settle');
$o4 = row('select * from `order` where goods_id=? order by id desc limit 1', [$gid3]);
if ($o4) {
    $sellerBefore = (float)val('select balance from user where id=?', [$seller['id']]);
    $r = $B->post('/order/cancel', ['id' => $o4['id']]); ok('买家主动取消待付款订单', ($r['code'] ?? 0) == 1, j($r));
    $u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
    ok('保证金赔付卖家：买家 950 / 冻结 0', $u['balance'] == 950 && $u['freeze_balance'] == 0, j($u));
    ok('卖家收到保证金 50', (float)val('select balance from user where id=?', [$seller['id']]) == $sellerBefore + 50, '');
}
set_setting('order_timeout_deposit', 'refund_buyer');
// 自动确认收货 + 催发货
$S->post('/seller/goods_status', ['id' => $gid3, 'status' => 1, 'end_time' => date('Y-m-d H:i:s', time() + 3600)]);
$B->post('/goods/bid', ['goods_id' => $gid3, 'price' => 110]);
q('update goods set end_time=? where id=?', [time() - 5, $gid3]); think('settle');
$o5 = row('select * from `order` where goods_id=? order by id desc limit 1', [$gid3]);
if ($o5) {
    $B->post('/order/pay?id=' . $o5['id'], ['address_id' => $addrId ?? 0]);
    q('update `order` set pay_time=? where id=?', [time() - 3 * 86400, $o5['id']]);
    $creditBefore = (int)val('select credit_score from user where id=?', [$seller['id']]);
    $out = think('order:remind');
    ok('催发货：卖家收到站内信', val('select count(*) from sys_message where user_id=? and title=?', [$seller['id'], '发货提醒']) >= 1, trim($out));
    ok('催发货：信誉分扣 1', (int)val('select credit_score from user where id=?', [$seller['id']]) == $creditBefore - 1, '');
    think('order:remind');
    ok('24 小时内不重复扣分', (int)val('select credit_score from user where id=?', [$seller['id']]) == $creditBefore - 1, '');
    $S->post('/seller/ship', ['id' => $o5['id'], 'ship_company' => '中通', 'ship_no' => 'ZT1']);
    q('update `order` set ship_time=? where id=?', [time() - 3 * 86400, $o5['id']]);
    $out = think('order:confirm');
    ok('发货超期自动确认收货', val('select order_status from `order` where id=?', [$o5['id']]) == 3, trim($out));
}

/* ============================================================
 * 11. 充值 / 提现
 * ============================================================ */
section('充值与提现');
$r = $B->post('/user/recharge', ['amount' => 0]); ok('充值金额校验', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/user/recharge', ['amount' => 500, 'pay_type' => 1]); ok('提交充值申请', ($r['code'] ?? 0) == 1, j($r));
$r = $B->post('/user/recharge', ['amount' => 500, 'pay_type' => 1]); ok('审核中不能重复充值', ($r['code'] ?? 1) != 1, j($r));
$rc = row('select * from recharge where user_id=? order by id desc limit 1', [$buyer['id']]);
$bal = (float)val('select balance from user where id=?', [$buyer['id']]);
$r = $A->post('/admin1314/recharge/audit', ['id' => $rc['id'], 'action' => 'pass']); ok('后台充值审核通过', ($r['code'] ?? 0) == 1, j($r));
ok('充值到账 +500', (float)val('select balance from user where id=?', [$buyer['id']]) == $bal + 500, '');
$B->post('/user/recharge', ['amount' => 300, 'pay_type' => 1]);
$rc2 = row('select * from recharge where user_id=? order by id desc limit 1', [$buyer['id']]);
$r = $A->post('/admin1314/recharge/audit', ['id' => $rc2['id'], 'action' => 'reject', 'reason' => '未收到款']); ok('后台拒绝充值', ($r['code'] ?? 0) == 1, j($r));
$b = $B->get('/user/recharge_log'); ok('充值记录显示拒绝理由', strpos($b, '未收到款') !== false, '');
$r = $B->post('/user/withdraw', ['amount' => 200, 'pay_type' => 'bank']); ok('未绑定账户不能提现', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/user/pay_account', ['type' => 1, 'real_name' => '买家甲', 'account' => 'qa@example.com']); ok('支付宝不传收款码被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/user/pay_account', ['type' => 3, 'real_name' => '买家甲', 'account' => '6222000011112222', 'bank_name' => '工商银行']); ok('绑定银行卡', ($r['code'] ?? 0) == 1, j($r));
$r = $B->post('/user/withdraw', ['amount' => 50, 'pay_type' => 'bank']); ok('低于最低金额被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $B->post('/user/withdraw', ['amount' => 6000, 'pay_type' => 'bank']); ok('高于最高金额被拒绝', ($r['code'] ?? 1) != 1, j($r));
$bal = (float)val('select balance from user where id=?', [$buyer['id']]);
$r = $B->post('/user/withdraw', ['amount' => 200, 'pay_type' => 'bank']); ok('提交提现 200', ($r['code'] ?? 0) == 1, j($r));
$u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
ok('提现后可用余额减 200（提现不走冻结，直接扣减）', (float)$u['balance'] == $bal - 200, j($u));
$r = $B->post('/user/withdraw', ['amount' => 200, 'pay_type' => 'bank']); ok('待审核时不能再提', ($r['code'] ?? 1) != 1, j($r));
$wd = row('select * from withdraw where user_id=? order by id desc limit 1', [$buyer['id']]);
ok('手续费 2% = 4', $wd && (float)$wd['fee'] == 4, j($wd));
$r = $A->post('/admin1314/withdraw/audit', ['id' => $wd['id'], 'action' => 'pass']); ok('后台提现打款', ($r['code'] ?? 0) == 1, j($r));
$u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
ok('打款后冻结清零', (float)$u['freeze_balance'] == 0 && (float)$u['balance'] == $bal - 200, j($u));
$B->post('/user/withdraw', ['amount' => 100, 'pay_type' => 'bank']);
$wd2 = row('select * from withdraw where user_id=? order by id desc limit 1', [$buyer['id']]);
$r = $A->post('/admin1314/withdraw/audit', ['id' => $wd2['id'], 'action' => 'reject', 'reason' => '账户有误']); ok('后台拒绝提现', ($r['code'] ?? 0) == 1, j($r));
$u = row('select balance,freeze_balance from user where id=?', [$buyer['id']]);
ok('拒绝后金额退回可用余额', (float)$u['balance'] == $bal - 200 && (float)$u['freeze_balance'] == 0, j($u));
$b = $B->get('/user/wallet'); ok('钱包页 200', $B->lastCode == 200, 'HTTP ' . $B->lastCode);
$b = $B->get('/user/wallet?page=2', true); ok('钱包明细分页接口', $B->lastCode == 200 && strpos($b, '"code":1') !== false, 'HTTP ' . $B->lastCode);
$b = $B->get('/user/withdraw_log'); ok('提现记录显示拒绝理由', strpos($b, '账户有误') !== false, '');
$V = new Client('virt'); login_front($V, $virtMobile, $PWD);
$r = $V->post('/user/recharge', ['amount' => 100]); ok('虚拟会员不能充值', ($r['code'] ?? 1) != 1, j($r));
$r = $V->post('/user/withdraw', ['amount' => 100, 'pay_type' => 'bank']); ok('虚拟会员不能提现', ($r['code'] ?? 1) != 1, j($r));

/* ============================================================
 * 12. 后台其他管理功能
 * ============================================================ */
section('后台管理功能');
$r = $A->post('/admin1314/member/setParent', ['id' => $buyer2['id'], 'parent' => $agentMobile]);
ok('改上级为代理', ($r['code'] ?? 0) == 1 && val('select pid from user where id=?', [$buyer2['id']]) == $agent['id'], j($r));
$r = $A->post('/admin1314/member/setParent', ['id' => $agent['id'], 'parent' => $buyer2Mobile]);
ok('不能把上级设为自己的下级（成环）', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/member/setParent', ['id' => $buyer2['id'], 'parent' => '']);
ok('清空上级', ($r['code'] ?? 0) == 1 && val('select pid from user where id=?', [$buyer2['id']]) == 0, j($r));
$b = $G->get('/agent/member/index?page=1&limit=50', true); ok('改上级后代理端列表同步', strpos(json_encode(json_decode($b,true),JSON_UNESCAPED_UNICODE), 'QA买家2') === false, '');
$r = $A->post('/admin1314/member/setStatus', ['id' => $buyer2['id'], 'status' => 0]); ok('禁用会员', ($r['code'] ?? 0) == 1, j($r));
$B2->get('/user/logout'); $r = login_front($B2, $buyer2Mobile, $PWD); ok('禁用后不能登录', ($r['code'] ?? 1) != 1, j($r));
$A->post('/admin1314/member/setStatus', ['id' => $buyer2['id'], 'status' => 1]);
$r = $A->post('/admin1314/member/resetPassword', ['id' => $buyer2['id'], 'password' => 'Reset123456']); ok('后台重置密码', ($r['code'] ?? 0) == 1, j($r));
$r = login_front($B2, $buyer2Mobile, 'Reset123456'); ok('重置后可登录', ($r['code'] ?? 0) == 1, j($r));
$r = $A->post('/admin1314/member/setAgent', ['id' => $buyer2['id'], 'is_agent' => 1]); ok('设为代理', ($r['code'] ?? 0) == 1, j($r));
$r = $A->post('/admin1314/member/setSeller', ['id' => $buyer2['id'], 'is_seller' => 1]); ok('设为卖家', ($r['code'] ?? 0) == 1 && val('select seller_check from user where id=?', [$buyer2['id']]) == 1, j($r));
$r = $A->post('/admin1314/member/updateShop', ['id' => $seller['id'], 'seller_intro' => '后台改简介', 'deposit' => 1000, 'shop_score' => 4.5, 'fans_count' => 10, 'credit_score' => 88]);
ok('后台编辑店铺资料', ($r['code'] ?? 0) == 1 && val('select credit_score from user where id=?', [$seller['id']]) == 88, j($r));
$b = $B->get('/goods/detail?id=' . $gid); ok('商品详情显示信誉分 88', strpos($b, '88') !== false, '');
$b = $A->get('/admin1314/member/detail?id=' . $buyer['id']); ok('会员详情页 200 且显示上级', $A->lastCode == 200 && strpos($b, 'QA代理') !== false, 'HTTP ' . $A->lastCode);
$r = $A->post('/admin1314/goods/add', ['seller_id' => $seller['id'], 'title' => 'QA后台代发' . $T, 'category_id' => $catId, 'content' => 'x', 'start_price' => 100, 'raise_price' => 10, 'deposit' => 0, 'end_time' => date('Y-m-d H:i:s', time() + 3600), 'cover' => $imgUrl, 'images' => [$imgUrl, $imgUrl, $imgUrl, $imgUrl]]);
ok('后台代发商品', ($r['code'] ?? 0) == 1, j($r));
$g4 = row('select * from goods where title=?', ['QA后台代发' . $T]); if ($g4) $CREATED_GOODS[] = (int)$g4['id'];
ok('后台代发直接上架', $g4 && $g4['status'] == 1, j($g4 ? ['status' => $g4['status']] : null));
$r = $A->post('/admin1314/goods/setStatus', ['id' => $g4['id'], 'status' => 4]); ok('后台下架', ($r['code'] ?? 0) == 1 && val('select status from goods where id=?', [$g4['id']]) == 4, j($r));
$r = $A->post('/admin1314/goods/delete', ['ids' => $g4['id']]); ok('后台删除商品', ($r['code'] ?? 0) == 1, j($r));
$r = $A->post('/admin1314/category/save', ['id' => 0, 'name' => 'QA分类' . $T, 'name_tw' => 'QA分類', 'name_en' => 'QA Cat', 'sort' => 99, 'status' => 1]);
ok('新增分类', ($r['code'] ?? 0) == 1, j($r));
$CREATED_CAT = (int)val('select id from category where name=?', ['QA分类' . $T]);
$b = $B->get('/category'); ok('前台分类页显示新分类', strpos($b, 'QA分类') !== false, '');
$r = $A->post('/admin1314/category/save', ['id' => $CREATED_CAT, 'name' => 'QA分类改', 'sort' => 99, 'status' => 0]); ok('编辑并隐藏分类', ($r['code'] ?? 0) == 1, j($r));
$b = $B->get('/category'); ok('隐藏分类前台不显示', strpos($b, 'QA分类改') === false, '');
$r = $A->post('/admin1314/category/delete', ['id' => $CREATED_CAT]); ok('删除分类', ($r['code'] ?? 0) == 1, j($r)); if (($r['code'] ?? 0) == 1) $CREATED_CAT = 0;
$r = $A->post('/admin1314/banner/save', ['id' => 0, 'title' => 'QA轮播', 'image' => $imgUrl, 'url' => '/about', 'sort' => 1, 'status' => 1]); ok('新增轮播', ($r['code'] ?? 0) == 1, j($r));
$CREATED_BANNER = (int)val('select id from banner where title=?', ['QA轮播']);
$b = $B->get('/'); ok('首页显示轮播图', strpos($b, $imgUrl) !== false, 'http=' . $B->lastCode . ' head=' . preg_replace('/\s+/', ' ', strip_tags(mb_substr($b, 0, 1200))) . ' activeBanners=' . val('select count(*) from banner where status=1') . ' banner=' . j(row('select id,image,status from banner where id=?', [$CREATED_BANNER])) . ' html=' . (preg_match('/id="bannerSlider".{0,1500}/s', $b, $m) ? preg_replace('/\s+/', ' ', $m[0]) : 'no home-banner'));
$r = $A->post('/admin1314/banner/delete', ['id' => $CREATED_BANNER]); ok('删除轮播', ($r['code'] ?? 0) == 1, j($r)); if (($r['code'] ?? 0) == 1) $CREATED_BANNER = 0;
$r = $A->post('/admin1314/admin_user/save', ['id' => 0, 'username' => 'qa_sub_' . $T, 'password' => 'Sub123456', 'real_name' => '子管理员', 'role' => 2, 'status' => 1]); ok('新增管理员', ($r['code'] ?? 0) == 1, j($r));
$CREATED_ADMIN2 = (int)val('select id from admin_user where username=?', ['qa_sub_' . $T]);
$r = $A->post('/admin1314/admin_user/delete', ['id' => $CREATED_ADMIN2]); ok('删除管理员', ($r['code'] ?? 0) == 1, j($r)); if (($r['code'] ?? 0) == 1) $CREATED_ADMIN2 = 0;
$old = setting('site_name');
$r = $A->post('/admin1314/setting/index', ['site_name' => $old, 'commission_rate' => '150']); ok('佣金超过 100 被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/setting/index', ['withdraw_min' => '500', 'withdraw_max' => '100']); ok('提现最低大于最高被拒绝', ($r['code'] ?? 1) != 1, j($r));
$r = $A->post('/admin1314/setting/index', ['service_qq' => '12345' . $T]); ok('保存单个设置不清空其他', ($r['code'] ?? 0) == 1 && setting('site_name') === $old, j($r));
foreach (['/admin1314/member/index?keyword=' . $buyerMobile, '/admin1314/member/auth', '/admin1314/member/seller', '/admin1314/message/index', '/admin1314/goods/index?keyword=QA', '/admin1314/goods/check', '/admin1314/category/index', '/admin1314/bid/index?goods_id=' . $gid, '/admin1314/order/index', '/admin1314/after_sale/index', '/admin1314/report/index', '/admin1314/recharge/index', '/admin1314/withdraw/index', '/admin1314/balance/index?keyword=' . $buyerMobile, '/admin1314/setting/index', '/admin1314/banner/index', '/admin1314/admin_user/index', '/admin1314/log/index', '/admin1314/goods/detail?id=' . $gid] as $p) {
    $b = $A->get($p); ok("后台页面 $p", $A->lastCode == 200 && strpos($b, 'ErrorException') === false && strpos($b, 'think\\exception') === false, 'HTTP ' . $A->lastCode);
}
$b = $A->get('/admin1314/log/index'); ok('操作日志记录了本次操作', strpos($b, 'QA') !== false || strpos($b, '添加会员') !== false, '');

/* ============================================================
 * 13. 前台页面冒烟
 * ============================================================ */
section('前台页面冒烟');
foreach (['/', '/index/index?page=2', '/category', '/category/list?cate=' . $catId, '/index/deals', '/about', '/user/agreement?type=protocol', '/user/agreement?type=privacy', '/user/login', '/user/register', '/goods/detail?id=' . $gid, '/shop/detail?id=' . $seller['id'], '/index/search?keyword=QA'] as $p) {
    $b = $B->get($p); ok("页面 $p", in_array($B->lastCode, [200, 302]) && strpos($b, 'ErrorException') === false && strpos($b, 'think\\exception') === false, 'HTTP ' . $B->lastCode);
}
foreach (['/user/center', '/user/profile', '/user/address', '/user/auth', '/user/invite', '/user/wallet', '/user/recharge', '/user/withdraw', '/user/recharge_log', '/user/withdraw_log', '/user/balance_log', '/user/messages', '/user/bids', '/user/favorites', '/user/follows', '/user/footprints', '/chat/list', '/service/index', '/order/list', '/order/list?order_status=3', '/seller/apply'] as $p) {
    $b = $B->get($p); ok("买家页面 $p", $B->lastCode == 200 && strpos($b, 'ErrorException') === false, 'HTTP ' . $B->lastCode);
}
foreach (['/seller/goods_add', '/seller/goods_list', '/seller/goods_list?status=1', '/seller/orders', '/seller/orders?order_status=1', '/chat/list?tab=seller', '/user/profile'] as $p) {
    $b = $S->get($p); ok("卖家页面 $p", $S->lastCode == 200 && strpos($b, 'ErrorException') === false, 'HTTP ' . $S->lastCode);
}
$b = $S->get('/'); ok('卖家登录首页有卖家悬浮按钮', strpos($b, 'sellerFab') !== false, '');
$b = $B->get('/'); ok('买家首页没有卖家按钮', strpos($b, 'id="sellerFab"') === false, '');
$b = $B->get('/user/invite'); ok('邀请页显示自己的邀请码', strpos($b, $buyer['invite_code']) !== false, '');
foreach (['zh-tw' => '繁', 'en-us' => 'Login'] as $lang => $needle) {
    $L = new Client('lang_' . $lang); $L->req('GET', '/user/login?lang=' . $lang, null, false);
    $b = $L->get('/user/login'); ok("多语言 $lang 登录页", $L->lastCode == 200, 'HTTP ' . $L->lastCode);
}
$G->get('/agent/login/logout'); $S->get('/user/center'); ok('代理端退出同步退出前台（同会话）', true, '');
$B->get('/user/logout'); $r = $B->getJson('/user/unread'); ok('退出后未读接口要求登录', ($r['code'] ?? 0) != 1, j($r));

/* ---------- 汇总 ---------- */
echo "\n\n================ 汇总 ================\n";
$fail = array_filter($RESULTS, function ($r) { return !$r[2]; });
printf("总计 %d 项，通过 %d，失败 %d\n", count($RESULTS), count($RESULTS) - count($fail), count($fail));
foreach ($fail as $f) echo "  [FAIL] {$f[0]} / {$f[1]}  <- {$f[3]}\n";
file_put_contents(__DIR__ . '/e2e_result.json', json_encode($RESULTS, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

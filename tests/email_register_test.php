<?php
/**
 * 邮箱注册 / 登录（主后台「注册与审核 › 注册方式」：mobile / email，setting.register_mode）：
 *  - 开关只决定新会员用什么注册；登录始终手机号、邮箱都认，老会员不受影响，可来回切换
 *  - user.email 唯一、mobile 可空；虚拟列 account = IFNULL(mobile, email)
 *  - 前台：注册页 / 登录页随开关切换；个人中心账号脱敏 ab***@domain
 *  - 主后台 / 代理后台：列表、搜索、详情、日志都按「账号」显示；添加会员手机号邮箱都行；批量虚拟会员跟随开关
 *  - 代理可用邮箱登录代理后台
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 320)) . "\n"; }
function clearLimits() { try { $r = new Redis(); $r->connect('127.0.0.1', 6379, 1); foreach (array_merge($r->keys('jp:login_fail_*'), $r->keys('jp:agent_login_fail_*'), $r->keys('jp:reg_ip_*')) as $k) $r->del($k); } catch (\Throwable $e) {} }
function sessData(array $data) { global $root, $SIDS; $sid = md5('em' . json_encode($data) . microtime(true)); file_put_contents("$root/runtime/session/sess_$sid", serialize($data)); $SIDS[] = $sid; return $sid; }
function adminSess() { global $pdo; $u = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($u['password']); return sessData(['admin' => $u]); }
function userSess($id) { global $pdo; $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); return sessData(['user' => $u]); }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function getSet($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }
function putSet($name, $value) { global $pdo, $T; if (getSet($name) === null) { $pdo->prepare('insert into setting(name,value,create_time,update_time) values(?,?,?,?)')->execute([$name, $value, $T, $T]); } else { $pdo->prepare('update setting set value=? where name=?')->execute([$value, $name]); } }
function urow($where) { global $pdo; return $pdo->query("select * from user where $where")->fetch(PDO::FETCH_ASSOC); }
function reg($account, $invite, $extra = []) { clearLimits(); $s = sessData(['user_captcha' => 'ab12']); [, , $j] = req($s, 'POST', '/user/doRegister', $extra + ['account' => $account, 'password' => 'pass1234', 'password2' => 'pass1234', 'invite_code' => $invite, 'captcha' => 'ab12']); return [$j, $s]; }
function login($account, $pwd = 'pass1234') { clearLimits(); $s = sessData(['user_captcha' => 'ab12']); [, , $j] = req($s, 'POST', '/user/doLogin', ['account' => $account, 'password' => $pwd, 'captcha' => 'ab12']); return [$j, $s]; }
function inList($list, $id) { foreach ((array)$list as $u) if ((int)$u['id'] === (int)$id) return $u; return null; }

$SIDS = [];
$bak = ['register_mode' => getSet('register_mode'), 'invite_required' => getSet('invite_required')];
$E1 = 'qa.email1@example.com'; $E2 = 'qa.email2@example.com'; $E3 = 'qa.admin.add@example.com'; $E4 = 'qa.agent.add@example.com';
$cleanup = function () use ($pdo, $E1, $E2, $E3, $E4) {
    $ids = $pdo->query("select id from user where email in ('$E1','$E2','$E3','$E4') or email like '%@virtual.local' and nickname like 'QA邮虚%' or mobile in ('19999990670','19999990671') or nickname like 'QA邮虚%'")->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) { $in = implode(',', array_map('intval', $ids)); foreach (['balance_log' => 'user_id', 'agent_log' => 'agent_id'] as $t => $c) $pdo->exec("delete from $t where $c in ($in)"); $pdo->exec("delete from goods where seller_id in ($in)"); $pdo->exec("delete from user where id in ($in)"); }
    $pdo->exec("delete from admin_log where action like '%qa.email%' or action like '%qa.admin.add%' or action like '%QA邮虚%' or action like '%virtual.local%'");
};
$cleanup();
$hash = password_hash('pass1234', PASSWORD_DEFAULT);
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990670','$hash','QA老手机号会员','990670',0,1,0,0,0,1,0,2,$T,$T,$T)");
$OLD = (int)$pdo->lastInsertId();
$sa = adminSess();
try {
    echo "== 表结构 ==\n";
    $r = urow("id=$OLD");
    ok('老会员：mobile 有值、email 为 NULL、虚拟列 account = 手机号', $r['mobile'] === '19999990670' && $r['email'] === null && $r['account'] === '19999990670', json_encode($r, JSON_UNESCAPED_UNICODE));

    echo "== 手机号模式（默认）==\n";
    putSet('register_mode', 'mobile'); putSet('invite_required', '1');
    [$c, $h] = req('', 'GET', '/user/register', null, false);
    ok('注册页是手机号输入框', $c == 200 && strpos($h, "var REG_MODE = 'mobile'") !== false && strpos($h, 'type="tel" id="mobile"') !== false, "HTTP $c");
    [$j] = reg($E1, '990670');
    ok('手机号模式下用邮箱注册被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '手机号') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 后台切到邮箱 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['register_mode' => 'email']);
    ok('设置保存为 email', ($j['code'] ?? 0) == 1 && getSet('register_mode') === 'email', json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页回显「邮箱」', $c == 200 && preg_match('/name="register_mode" id="rm_e" value="email" checked/', $h), "HTTP $c");
    [$c, $h] = req('', 'GET', '/user/register', null, false);
    ok('注册页变为邮箱输入框', $c == 200 && strpos($h, "var REG_MODE = 'email'") !== false && strpos($h, 'type="email" id="mobile"') !== false, "HTTP $c");
    [$c, $h] = req('', 'GET', '/user/login', null, false);
    ok('登录页输入框不限 11 位，标签为「账号」', $c == 200 && strpos($h, 'maxlength="100"') !== false && strpos($h, '请输入账号') !== false && strpos($h, '>账号</label>') !== false, "HTTP $c");

    echo "== 邮箱注册 ==\n";
    [$j] = reg('not-an-email', '990670');
    ok('非法邮箱被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '邮箱') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = reg('13912345678', '990670');
    ok('邮箱模式下用手机号注册被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '邮箱') !== false && !urow("mobile='13912345678'"), json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = reg("a'b@example.com", '990670');
    ok('含特殊字符的邮箱被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j, $s1] = reg('QA.Email1@Example.COM', '990670');
    $u1 = urow("email='$E1'");
    ok('邮箱注册成功：转小写入库，mobile 为 NULL，account = 邮箱，上级 = 手机号老会员', ($j['code'] ?? 0) == 1 && $u1 && $u1['mobile'] === null && $u1['account'] === $E1 && (int)$u1['pid'] === $OLD, json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode($u1, JSON_UNESCAPED_UNICODE));
    ok('默认昵称取邮箱 @ 前的部分', $u1 && $u1['nickname'] === '用户qa.email1', $u1['nickname'] ?? '');
    // 注册成功后会话 ID 会重新生成，这里用伪造的已登录会话看页面
    [$c, $h] = req(userSess($u1['id']), 'GET', '/user/center', null, false);
    ok('个人中心账号脱敏为 qa***@example.com', $c == 200 && strpos($h, 'qa***@example.com') !== false && strpos($h, $E1) === false, "HTTP $c");
    [$j] = reg($E1, '990670');
    ok('重复邮箱被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '已注册') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = reg($E2, $u1['invite_code']);
    $u2 = urow("email='$E2'");
    ok('第二个邮箱会员也能注册（mobile 都是 NULL 不冲突），可填邮箱会员的邀请码', ($j['code'] ?? 0) == 1 && $u2 && (int)$u2['pid'] === (int)$u1['id'], json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 登录两种都认 ==\n";
    [$j] = login('QA.EMAIL1@example.com');
    ok('邮箱会员用邮箱登录（大小写不敏感）', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = login('19999990670');
    ok('邮箱模式下，老手机号会员照常用手机号登录', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = login($E1, 'wrong-pass');
    ok('密码错误提示「账号或密码错误」', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '账号或密码错误') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    clearLimits(); $s = sessData(['user_captcha' => 'ab12']);
    [, , $j] = req($s, 'POST', '/user/doLogin', ['mobile' => '19999990670', 'password' => 'pass1234', 'captcha' => 'ab12']);
    ok('旧参数名 mobile 仍兼容', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 主后台 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?keyword=' . urlencode('qa.email1'));
    $row = inList($j['data'] ?? [], $u1['id']);
    ok('会员列表按邮箱搜索命中，账号列显示邮箱，上级显示手机号', $row && $row['mobile'] === $E1 && ($row['parent_mobile'] ?? '') === '19999990670', mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 300));
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?keyword=19999990670');
    ok('按手机号搜索老会员仍然有效', inList($j['data'] ?? [], $OLD) !== null, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    [$c, $h] = req($sa, 'GET', '/admin1314/member/detail?id=' . $u1['id'], null, false);
    ok('会员详情显示邮箱账号与「邮箱注册」标记', $c == 200 && strpos($h, $E1) !== false && strpos($h, '邮箱注册') !== false && strpos($h, '登录账号') !== false, "HTTP $c");
    [, , $j] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $u1['id'], 'amount' => 66, 'remark' => 'QA']);
    $log = $pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('操作日志里记录的是邮箱账号', ($j['code'] ?? 0) == 1 && strpos((string)$log, $E1) !== false, $log);
    [, , $j] = req($sa, 'GET', '/admin1314/balance/index?keyword=' . urlencode('qa.email1'));
    ok('余额流水按邮箱搜索命中，账号列是邮箱', ($j['count'] ?? 0) >= 1 && ($j['data'][0]['mobile'] ?? '') === $E1, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 300));
    $cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
    $pdo->exec("update user set is_seller=1, seller_check=1 where id={$u1['id']}");
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,reserve_price,status,start_time,end_time,create_time,update_time) values({$u1['id']},$cat,'QA邮箱卖家拍品','','[]',100,10,0,0,1,$T-60,$T+7200,$T,$T)");
    [, , $j] = req($sa, 'GET', '/admin1314/goods/index?seller_kw=' . urlencode('qa.email1'));
    ok('商品列表按卖家邮箱搜索命中，卖家账号列是邮箱', ($j['count'] ?? 0) >= 1 && ($j['data'][0]['seller_mobile'] ?? '') === $E1, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 300));
    [, , $j] = req($sa, 'POST', '/admin1314/member/add', ['account' => 'QA.Admin.Add@example.com', 'nickname' => '', 'password' => 'pass1234', 'balance' => 0]);
    $u3 = urow("email='$E3'");
    ok('主后台添加邮箱会员', ($j['code'] ?? 0) == 1 && $u3 && $u3['mobile'] === null, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/add', ['account' => $E3, 'password' => 'pass1234']);
    ok('重复邮箱添加被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '邮箱已注册') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/add', ['account' => 'abc', 'password' => 'pass1234']);
    ok('非法账号被拒（邮箱模式提示邮箱格式）', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '邮箱格式') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/add', ['account' => '19999990671', 'password' => 'pass1234', 'nickname' => 'QA邮虚手机']);
    ok('邮箱模式下后台仍可添加手机号会员', ($j['code'] ?? 0) == 1 && urow("mobile='19999990671'"), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 2, 'prefix' => 'QA邮虚', 'password' => 'pass1234', 'balance' => 10]);
    $vs = $pdo->query("select mobile,email,account,is_virtual from user where nickname like 'QA邮虚%' and is_virtual=1")->fetchAll(PDO::FETCH_ASSOC);
    ok('邮箱模式批量虚拟会员：生成 @virtual.local 邮箱，mobile 为 NULL', ($j['code'] ?? 0) == 1 && count($vs) === 2 && $vs[0]['mobile'] === null && substr($vs[0]['email'], -14) === '@virtual.local', json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode($vs));
    [$c, $h] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('会员列表页表头为「账号」，添加表单接受手机号或邮箱', $c == 200 && strpos($h, '<th>账号</th>') !== false && strpos($h, '手机号或邮箱') !== false && strpos($h, 'account: mobile') !== false, "HTTP $c");

    echo "== 代理后台 ==\n";
    $pdo->exec("update user set is_agent=1 where id={$u1['id']}");
    clearLimits(); $s = sessData(['agent_captcha' => 'ab12']);
    [, , $j] = req($s, 'POST', '/agent/login/doLogin', ['mobile' => $E1, 'password' => 'pass1234', 'captcha' => 'ab12']);
    ok('代理用邮箱登录代理后台', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $s = userSess($u1['id']);   // 登录后会话 ID 会重新生成，后续请求用伪造的代理会话
    [, , $j] = req($s, 'GET', '/agent/member/index?keyword=' . urlencode('qa.email2'));
    $row = inList($j['data'] ?? [], $u2['id']);
    ok('代理会员列表按邮箱搜索团队会员，账号列是邮箱', $row && $row['mobile'] === $E2, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 300));
    [$c, $h] = req($s, 'GET', '/agent/member/detail?id=' . $u2['id'], null, false);
    ok('代理会员详情显示邮箱账号', $c == 200 && strpos($h, $E2) !== false, "HTTP $c");
    [, , $j] = req($s, 'POST', '/agent/member/add', ['account' => $E4, 'password' => 'pass1234', 'nickname' => '']);
    $u4 = urow("email='$E4'");
    ok('代理添加邮箱会员，归入自己团队', ($j['code'] ?? 0) == 1 && $u4 && (int)$u4['pid'] === (int)$u1['id'], json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('代理日志记录邮箱账号', (int)$pdo->query("select count(*) from agent_log where agent_id={$u1['id']} and action like '%$E4%'")->fetchColumn() === 1);
    clearLimits(); $s = sessData(['agent_captcha' => 'ab12']);
    [, , $j] = req($s, 'POST', '/agent/login/doLogin', ['mobile' => '19999990670', 'password' => 'pass1234', 'captcha' => 'ab12']);
    ok('手机号代理照常登录代理后台', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 切回手机号 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['register_mode' => 'whatever']);
    ok('非法值按手机号保存', ($j['code'] ?? 0) == 1 && getSet('register_mode') === 'mobile', json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = login($E1);
    ok('切回手机号后，邮箱会员照常用邮箱登录', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$j] = reg('qa.email9@example.com', '990670');
    ok('切回后邮箱不能再注册', ($j['code'] ?? 1) == 0 && !urow("email='qa.email9@example.com'"), json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    foreach ($bak as $k => $v) { if ($v === null) $pdo->prepare('delete from setting where name=?')->execute([$k]); else putSet($k, $v); }
    $cleanup();
    clearLimits();
    foreach ($SIDS as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

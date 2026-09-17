<?php
/**
 * 会员提现开关（user.can_withdraw，默认 1 开启）：
 *  - 主后台 / 代理后台「编辑会员」可开启 / 关闭；列表接口和详情页展示
 *  - 关闭后前台不能提交提现申请（POST 拦截 + 页面提示 + 按钮禁用），开启后恢复
 *  - 多语言文案齐全
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,500,0,0,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('ws' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true, $lang = '') {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => ($sid ? 'PHPSESSID=' . $sid : '') . ($lang ? '; think_lang=' . $lang : '')]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function cw($id) { global $pdo; return (int)$pdo->query("select can_withdraw from user where id=$id")->fetchColumn(); }
function inList($list, $id) { foreach ((array)$list as $u) if ((int)$u['id'] === $id) return $u; return null; }

$pdo->exec("delete from user where mobile in ('19999990620','19999990621')");
$AG = mk('19999990620', 'QA提现代理', 0, 1);
$U  = mk('19999990621', 'QA提现会员', $AG);
$pdo->exec("insert into pay_account(user_id,type,real_name,account,bank_name,bank_branch,qr_code,create_time,update_time) values($U,3,'QA','6222000000000001','QA银行','','',$T,$T)");
$edit = ['password' => '', 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 0];
$sa = sess(0, 'admin'); $sg = sess($AG); $su = sess($U);
try {
    echo "== 默认值 ==\n";
    ok('新会员默认提现开启', cw($U) === 1, cw($U));
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?keyword=19999990621');
    $u = inList($j['data'] ?? ($j['list'] ?? []), $U);
    ok('主后台列表接口带 can_withdraw=1', $u && (int)$u['can_withdraw'] === 1, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));

    echo "== 主后台关闭 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', $edit + ['id' => $U, 'parent' => '19999990620', 'can_withdraw' => 0]);
    ok('编辑保存关闭提现', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '关闭提现') !== false && cw($U) === 0, json_encode($j, JSON_UNESCAPED_UNICODE) . ' cw=' . cw($U));
    $log = $pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('操作日志记录关闭提现', strpos((string)$log, '19999990621') !== false && strpos((string)$log, '关闭提现') !== false, $log);
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', $edit + ['id' => $U, 'parent' => '19999990620', 'can_withdraw' => 0]);
    ok('重复提交相同状态提示无修改', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '没有需要修改') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sa, 'GET', "/admin1314/member/detail?id=$U", null, false);
    ok('主后台详情显示提现已关闭', $c == 200 && strpos($h, '提现开关') !== false && strpos($h, '已关闭') !== false, "HTTP $c");
    [$c, $h] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('主后台编辑弹窗带提现开关', $c == 200 && strpos($h, 'name="editWithdraw"') !== false && strpos($h, '提现已关闭') !== false, "HTTP $c");

    echo "== 前台被拦截 ==\n";
    [, , $j] = req($su, 'POST', '/user/withdraw', ['amount' => 100, 'pay_type' => 'bank']);
    ok('关闭后提交提现被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '提现功能已关闭') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('余额未被冻结、无提现记录', (float)$pdo->query("select balance from user where id=$U")->fetchColumn() == 500 && (int)$pdo->query("select count(*) from withdraw where user_id=$U")->fetchColumn() === 0);
    [$c, $h] = req($su, 'GET', '/user/withdraw', null, false);
    ok('前台提现页显示关闭提示、JS 标记', $c == 200 && strpos($h, 'wd-off') !== false && strpos($h, '您的提现功能已关闭，请联系客服') !== false && strpos($h, 'var withdrawOff = 1') !== false, "HTTP $c");
    [$c, $h] = req($su, 'GET', '/user/withdraw?lang=en-us', null, false);
    ok('英文提示', $c == 200 && strpos($h, 'Withdrawals are disabled for your account') !== false, "HTTP $c");
    [$c, $h] = req($su, 'GET', '/user/withdraw?lang=zh-tw', null, false);
    ok('繁体提示', $c == 200 && strpos($h, '您的提現功能已關閉') !== false, "HTTP $c");
    [$c, $h] = req($su, 'GET', '/user/withdraw?lang=zh-cn', null, false);

    echo "== 代理后台开启 ==\n";
    [, , $j] = req($sg, 'GET', '/agent/member/index?keyword=19999990621');
    $u = inList($j['data'] ?? ($j['list'] ?? []), $U);
    ok('代理列表接口带 can_withdraw=0', $u && (int)$u['can_withdraw'] === 0, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    [$c, $h] = req($sg, 'GET', "/agent/member/detail?id=$U", null, false);
    ok('代理详情显示提现已关闭', $c == 200 && strpos($h, '提现已关闭') !== false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/member/editSave', $edit + ['id' => $U, 'can_withdraw' => 1]);
    ok('代理编辑开启提现', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '开启提现') !== false && cw($U) === 1, json_encode($j, JSON_UNESCAPED_UNICODE) . ' cw=' . cw($U));
    [, , $j] = req($su, 'POST', '/user/withdraw', ['amount' => 100, 'pay_type' => 'bank']);
    ok('开启后可正常提交提现', ($j['code'] ?? 0) == 1 && (int)$pdo->query("select count(*) from withdraw where user_id=$U and status=0")->fetchColumn() === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($su, 'GET', '/user/withdraw', null, false);
    ok('开启后页面无关闭提示', $c == 200 && strpos($h, 'class="wd-pending wd-off"') === false && strpos($h, 'var withdrawOff = 0') !== false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/member/editSave', $edit + ['id' => $U]);
    ok('不传 can_withdraw 时保持原值', ($j['code'] ?? 0) == 1 && cw($U) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("delete from withdraw where user_id=$U");
    $pdo->exec("delete from balance_log where user_id=$U");
    $pdo->exec("delete from pay_account where user_id=$U");
    $pdo->exec("delete from admin_log where action like '%19999990621%'");
    $pdo->exec("delete from user where id in ($U,$AG)");
    foreach ([$sa, $sg, $su] as $s) if ($s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

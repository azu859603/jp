<?php
/**
 * 前台提现账户绑定后能否自行修改，由主后台「提现规则 › 会员自行修改提现账户」开关控制（setting.pay_account_editable，默认 0 关闭）。
 * 关闭时：
 *  - 未绑定的方式可以绑定；同一方式再次提交被拒「如需修改请联系客服」，库里数据不变
 *  - 其它未绑定的方式不受影响
 *  - 绑定页：已绑定时只读、无保存按钮、显示联系客服；提现页链接改为「查看绑定」
 *  - 客服通过主后台 / 代理后台仍可修改或删除；删除后会员可重新绑定
 * 开启时：会员可随时修改，页面恢复可编辑
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,100,0,0,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('pl' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function getSet($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }
function pa($uid, $type) { global $pdo; return $pdo->query("select * from pay_account where user_id=$uid and type=$type")->fetch(PDO::FETCH_ASSOC); }

$pdo->exec("delete from user where mobile in ('19999990660','19999990661')");
$AG = mk('19999990660', 'QA绑定代理', 0, 1);
$U  = mk('19999990661', 'QA绑定会员', $AG);
$su = sess($U); $sa = sess(0, 'admin'); $sg = sess($AG);
$bakSet = getSet('pay_account_editable');
$pdo->exec("delete from setting where name='pay_account_editable'");   // 未配置 = 默认关闭
$bank = ['type' => 3, 'real_name' => '张三', 'account' => '6222000099990001', 'bank_name' => '工商银行', 'bank_branch' => '北京分行'];
try {
    echo "== 首次绑定 ==\n";
    [, , $j] = req($su, 'POST', '/user/pay_account', $bank);
    ok('未绑定时可以绑定银行卡', ($j['code'] ?? 0) == 1 && (pa($U, 3)['account'] ?? '') === '6222000099990001', json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 再次提交被拒 ==\n";
    [, , $j] = req($su, 'POST', '/user/pay_account', ['account' => '6222000099990002', 'real_name' => '李四'] + $bank);
    $row = pa($U, 3);
    ok('同一方式再次提交被拒，提示联系客服', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '联系客服') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('库里的卡号和姓名没有被改', $row['account'] === '6222000099990001' && $row['real_name'] === '张三', json_encode($row, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($su, 'POST', '/user/pay_account?lang=en-us', $bank);
    ok('英文提示', strpos($j['msg'] ?? '', 'contact customer service') !== false, $j['msg'] ?? '');
    [, , $j] = req($su, 'POST', '/user/pay_account?lang=zh-cn', ['type' => 4, 'account' => 'T' . str_repeat('A', 33)]);
    ok('其它未绑定的方式（USDT）仍可绑定', ($j['code'] ?? 0) == 1 && pa($U, 4), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($su, 'POST', '/user/pay_account', ['type' => 4, 'account' => 'T' . str_repeat('B', 33)]);
    ok('USDT 绑定后同样不可再改', ($j['code'] ?? 1) == 0 && pa($U, 4)['account'] === 'T' . str_repeat('A', 33), json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 页面 ==\n";
    [$c, $h] = req($su, 'GET', '/user/pay_account?type=3', null, false);
    ok('绑定页含只读逻辑、联系客服入口与新提示', $c == 200 && strpos($h, 'id="paLocked"') !== false && strpos($h, 'href="/service/index"') !== false && strpos($h, '绑定后不可自行修改') !== false && strpos($h, '绑定信息支持随时修改') === false && strpos($h, 'var PA_EDITABLE = 0 == 1') !== false, "HTTP $c");
    [$c, $h] = req($su, 'GET', '/user/withdraw', null, false);
    ok('提现页按开关显示「查看绑定」', $c == 200 && strpos($h, "PA_EDITABLE ? t('修改绑定') : t('查看绑定')") !== false && strpos($h, 'var PA_EDITABLE = 0 == 1') !== false, "HTTP $c");

    echo "== 客服（后台）可改 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/savePayAccount', ['user_id' => $U, 'type' => 3, 'real_name' => '张三', 'account' => '6222000099990003', 'bank_name' => '建设银行', 'bank_branch' => '上海分行']);
    ok('主后台可以修改会员的绑定', ($j['code'] ?? 0) == 1 && pa($U, 3)['account'] === '6222000099990003', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/member/savePayAccount', ['user_id' => $U, 'type' => 3, 'real_name' => '张三', 'account' => '6222000099990004', 'bank_name' => '建设银行', 'bank_branch' => '上海分行']);
    ok('代理后台可以修改团队会员的绑定', ($j['code'] ?? 0) == 1 && pa($U, 3)['account'] === '6222000099990004', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/deletePayAccount', ['user_id' => $U, 'type' => 3]);
    ok('主后台删除绑定', ($j['code'] ?? 0) == 1 && !pa($U, 3), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($su, 'POST', '/user/pay_account', ['account' => '6222000099990005'] + $bank);
    ok('客服删除后会员可重新绑定', ($j['code'] ?? 0) == 1 && pa($U, 3)['account'] === '6222000099990005', json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 后台开启「会员自行修改提现账户」 ==\n";
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页有开关，未配置时默认选中「关闭」', $c == 200 && preg_match('/name="pay_account_editable" id="pae0" value="0" checked/', $h), "HTTP $c");
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['pay_account_editable' => '1']);
    ok('保存为开启', ($j['code'] ?? 0) == 1 && getSet('pay_account_editable') === '1', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($su, 'POST', '/user/pay_account', ['account' => '6222000099990006', 'bank_name' => '农业银行'] + $bank);
    $row = pa($U, 3);
    ok('开启后会员可以修改已绑定的银行卡', ($j['code'] ?? 0) == 1 && $row['account'] === '6222000099990006' && $row['bank_name'] === '农业银行', json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode($row, JSON_UNESCAPED_UNICODE));
    ok('修改是更新原记录，不会多出一条', (int)$pdo->query("select count(*) from pay_account where user_id=$U and type=3")->fetchColumn() === 1);
    [$c, $h] = req($su, 'GET', '/user/pay_account?type=3', null, false);
    ok('绑定页变为可编辑（JS 变量 1，提示「支持随时修改」）', $c == 200 && strpos($h, 'var PA_EDITABLE = 1 == 1') !== false && strpos($h, '绑定信息支持随时修改') !== false && strpos($h, '绑定后不可自行修改') === false, "HTTP $c");
    [$c, $h] = req($su, 'GET', '/user/withdraw', null, false);
    ok('提现页 JS 变量为 1（显示「修改绑定」）', $c == 200 && strpos($h, 'var PA_EDITABLE = 1 == 1') !== false, "HTTP $c");

    echo "== 再关闭 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['pay_account_editable' => 'x']);
    ok('非法值按关闭保存', ($j['code'] ?? 0) == 1 && getSet('pay_account_editable') === '0', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($su, 'POST', '/user/pay_account', ['account' => '6222000099990007'] + $bank);
    ok('关闭后立即恢复锁定', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '联系客服') !== false && pa($U, 3)['account'] === '6222000099990006', json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    if ($bakSet === null) { $pdo->exec("delete from setting where name='pay_account_editable'"); } else { $pdo->prepare('update setting set value=? where name=?')->execute([$bakSet, 'pay_account_editable']); }
    $pdo->exec("delete from pay_account where user_id=$U");
    $pdo->exec("delete from admin_log where action like '%19999990661%'");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from user where id in ($U,$AG)");
    foreach ([$su, $sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

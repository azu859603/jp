<?php
/**
 * 会员「编辑」弹窗：重置密码 / 上级 / 卖家 / 代理 / 虚拟会员 一次保存
 *  - 主后台 editSave：各字段生效、留空不改、上级校验（不存在 / 自己 / 成环 / 清空 / 未变化）
 *  - 代理后台 editSave：只能改团队会员、不能改上级（传了也忽略）
 *  - 页面：旧的四个按钮消失、出现「编辑」按钮和弹窗
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0, $virtual = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller,$seller,$agent,$virtual,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $key = 'user') {
    global $root, $pdo;
    $sid = md5('me' . $key . $id . microtime(true));
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
function u($id) { global $pdo; return $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); }

$AG  = mk('19999990470', 'QA编辑代理', 0, 1);
$M   = mk('19999990471', 'QA编辑会员', $AG);          // 代理的下级
$P2  = mk('19999990472', 'QA新上级');
$OUT = mk('19999990473', 'QA外部会员');
$CH  = mk('19999990474', 'QA会员的下级', $M);          // M 的下级，用于成环校验
$ids = "$AG,$M,$P2,$OUT,$CH";
$sa  = sess(0, 'admin');
$sag = sess($AG);

try {
    echo "== 页面 ==\n";
    [$c, $html] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('主后台会员列表：有「编辑」按钮和弹窗，旧的四个按钮 / 弹窗已移除', $c == 200 && strpos($html, "onclick=\"openEdit(' + u.id + ')\">编辑</a>") !== false && strpos($html, 'id="editMask"') !== false && strpos($html, 'id="editParent"') !== false && strpos($html, 'name="editVirtual"') !== false
        && strpos($html, '重置密码</a>') === false && strpos($html, '改上级</a>') === false && strpos($html, '设为卖家') === false && strpos($html, '设为代理</a>') === false && strpos($html, 'id="pwdMask"') === false && strpos($html, 'id="parentMask"') === false, "HTTP $c");
    [$c, $html] = req($sag, 'GET', '/agent/member/index', null, false);
    ok('代理后台会员列表：有「编辑」按钮和弹窗，且没有上级输入框', $c == 200 && strpos($html, "onclick=\"openEdit(' + u.id + ')\">编辑</a>") !== false && strpos($html, 'id="editMask"') !== false && strpos($html, 'name="editVirtual"') !== false && strpos($html, 'id="editParent"') === false, "HTTP $c");
    [, , $j] = req($sag, 'GET', '/agent/member/index?page=1&limit=50&keyword=19999990471');
    ok('代理列表接口返回 is_agent（弹窗回填用）', isset($j['data'][0]['is_agent']), json_encode($j['data'][0] ?? null, JSON_UNESCAPED_UNICODE));

    echo "== 上级搜索 ==\n";
    [$c, $html] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('弹窗里的上级输入框带搜索下拉', strpos($html, 'id="parentList"') !== false && strpos($html, "searchUser?scene=parent&exclude=") !== false && strpos($html, '<label>上级（账号）</label>') !== false, '');
    $idsOf = function ($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); };
    [, , $j] = req($sa, 'GET', '/admin1314/member/searchUser?scene=parent&exclude=' . $M . '&kw=19999990');
    $r = $idsOf($j);
    ok('按手机号片段搜索：列出其它会员、排除自己', in_array($AG, $r) && in_array($P2, $r) && !in_array($M, $r), json_encode($r));
    [, , $j] = req($sa, 'GET', '/admin1314/member/searchUser?scene=parent&exclude=' . $M . '&kw=QA新上级');
    ok('按昵称不再匹配（只按手机号搜索）', !in_array($P2, $idsOf($j)), json_encode($idsOf($j)));
    [, , $j] = req($sa, 'GET', '/admin1314/member/searchUser?scene=parent&exclude=' . $M . '&kw=' . $AG);
    ok('按会员 ID 不再匹配', !in_array($AG, $idsOf($j)), json_encode($idsOf($j)));
    [, , $j] = req($sa, 'GET', '/admin1314/member/searchUser?scene=parent&exclude=' . $M . '&kw=19999990470');
    ok('按完整手机号搜索并返回代理标记', $idsOf($j) === [$AG] && (int)($j['data'][0]['is_agent'] ?? 0) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update user set status=0 where id=$OUT");
    [, , $j] = req($sa, 'GET', '/admin1314/member/searchUser?scene=parent&kw=19999990473');
    ok('禁用会员不出现在上级候选里', !in_array($OUT, $idsOf($j)), json_encode($idsOf($j)));
    $pdo->exec("update user set status=1 where id=$OUT");
    [, , $j] = req($sa, 'GET', '/admin1314/member/searchUser?scene=auth&kw=19999990');
    ok('原有 auth 场景不受影响（只列未实名）', empty($idsOf($j)) || !in_array($M, $idsOf($j)), json_encode($idsOf($j)));

    echo "== 主后台 editSave ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'password' => '', 'parent' => '19999990470', 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 0]);
    ok('全部不变 → 提示没有需要修改的内容', ($j['code'] ?? 0) == 1 && strpos((string)$j['msg'], '没有需要修改') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'password' => '12345', 'parent' => '19999990470', 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 0]);
    ok('密码不足 6 位被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'password' => 'newpass66', 'parent' => '19999990470', 'is_seller' => 1, 'is_agent' => 1, 'is_virtual' => 1]);
    $x = u($M);
    ok('一次保存：密码 + 卖家 + 代理 + 虚拟会员', ($j['code'] ?? 0) == 1 && password_verify('newpass66', $x['password']) && (int)$x['is_seller'] === 1 && (int)$x['seller_check'] === 1 && (int)$x['is_agent'] === 1 && (int)$x['agent_time'] > 0 && (int)$x['is_virtual'] === 1 && (int)$x['pid'] === $AG, json_encode([$j, $x['is_seller'], $x['seller_check'], $x['is_agent'], $x['is_virtual'], $x['pid']], JSON_UNESCAPED_UNICODE));
    ok('保存提示列出改动项', strpos((string)$j['msg'], '重置密码') !== false && strpos((string)$j['msg'], '设为卖家') !== false && strpos((string)$j['msg'], '设为虚拟会员') !== false, $j['msg'] ?? '');
    ok('后台日志已记录', (int)$pdo->query("select count(*) from admin_log where action like '编辑会员 19999990471：%设为虚拟会员%'")->fetchColumn() === 1, '');
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'password' => '', 'parent' => '19999990470', 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 0]);
    $x = u($M);
    ok('取消卖家 / 代理 / 虚拟会员，密码留空不变', ($j['code'] ?? 0) == 1 && (int)$x['is_seller'] === 0 && (int)$x['seller_check'] === 0 && (int)$x['is_agent'] === 0 && (int)$x['agent_time'] === 0 && (int)$x['is_virtual'] === 0 && password_verify('newpass66', $x['password']), json_encode([$j, $x['is_seller'], $x['seller_check'], $x['is_agent'], $x['is_virtual']], JSON_UNESCAPED_UNICODE));

    echo "== 主后台：上级 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => '19999990999']);
    ok('上级不存在被拒', ($j['code'] ?? 1) == 0 && (int)u($M)['pid'] === $AG, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => '19999990471']);
    ok('不能把自己设为上级', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => '19999990474']);
    ok('不能把自己的下级设为上级（成环）', ($j['code'] ?? 1) == 0 && (int)u($M)['pid'] === $AG, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => (string)$P2]);
    ok('按会员 ID 换上级', ($j['code'] ?? 0) == 1 && (int)u($M)['pid'] === $P2, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => substr('19999990470', -6)]);
    ok('按邀请码换回原上级', ($j['code'] ?? 0) == 1 && (int)u($M)['pid'] === $AG, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => '']);
    ok('上级留空即清空', ($j['code'] ?? 0) == 1 && (int)u($M)['pid'] === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $M, 'parent' => '19999990470']);
    ok('再设回代理为上级', ($j['code'] ?? 0) == 1 && (int)u($M)['pid'] === $AG, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => 0]);
    ok('会员不存在被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 代理后台 editSave ==\n";
    [, , $j] = req($sag, 'POST', '/agent/member/editSave', ['id' => $OUT, 'is_virtual' => 1]);
    ok('不能编辑团队外会员', ($j['code'] ?? 1) == 0 && (int)u($OUT)['is_virtual'] === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sag, 'POST', '/agent/member/editSave', ['id' => $M, 'password' => 'agentpw88', 'is_seller' => 1, 'is_agent' => 1, 'is_virtual' => 1, 'parent' => (string)$P2]);
    $x = u($M);
    ok('代理端一次保存密码 / 卖家 / 代理 / 虚拟会员', ($j['code'] ?? 0) == 1 && password_verify('agentpw88', $x['password']) && (int)$x['is_seller'] === 1 && (int)$x['is_agent'] === 1 && (int)$x['is_virtual'] === 1, json_encode([$j, $x['is_seller'], $x['is_agent'], $x['is_virtual']], JSON_UNESCAPED_UNICODE));
    ok('代理端传了 parent 也不会改上级', (int)$x['pid'] === $AG, 'pid=' . $x['pid']);
    [, , $j] = req($sag, 'POST', '/agent/member/editSave', ['id' => $M, 'password' => '123', 'is_virtual' => 0]);
    ok('代理端密码不足 6 位被拒', ($j['code'] ?? 1) == 0 && (int)u($M)['is_virtual'] === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sag, 'POST', '/agent/member/editSave', ['id' => $M, 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 0]);
    $x = u($M);
    ok('代理端取消三项', ($j['code'] ?? 0) == 1 && (int)$x['is_seller'] === 0 && (int)$x['is_agent'] === 0 && (int)$x['is_virtual'] === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("delete from admin_log where action like '编辑会员 1999999047%'");
    $pdo->exec("delete from user where id in ($ids)");
    foreach ([$sa, $sag] as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

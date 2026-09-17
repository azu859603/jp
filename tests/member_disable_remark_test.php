<?php
/**
 * 会员禁用备注：
 *  - 主后台 / 代理后台禁用会员必须填写备注，备注保存在 user.status_remark
 *  - 列表接口和详情页展示备注；启用后备注清空
 *  - 代理只能禁用自己团队的会员；主后台操作写入 admin_log
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,0,0,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('dr' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function row($id) { global $pdo; return $pdo->query("select status,status_remark from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function inList($list, $id) { foreach ((array)$list as $u) if ((int)$u['id'] === $id) return $u; return null; }

$AG = mk('19999990600', 'QA禁用代理', 0, 1);
$U  = mk('19999990601', 'QA禁用会员', $AG);
$X  = mk('19999990602', 'QA他人会员', 0);
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 主后台 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/setStatus', ['id' => $U, 'status' => 0]);
    $r = row($U);
    ok('不填备注禁用被拒，状态不变', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '备注') !== false && $r['status'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/setStatus', ['id' => $U, 'status' => 0, 'remark' => '  恶意出价QA  ']);
    $r = row($U);
    ok('填写备注后禁用成功，备注入库（去首尾空格）', ($j['code'] ?? 0) == 1 && $r['status'] == 0 && $r['status_remark'] === '恶意出价QA', json_encode($j, JSON_UNESCAPED_UNICODE) . ' ' . json_encode($r, JSON_UNESCAPED_UNICODE));
    $log = $pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('操作日志包含备注', strpos((string)$log, '禁用会员：19999990601，备注：恶意出价QA') !== false, $log);
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?keyword=19999990601');
    $u = inList($j['data'] ?? ($j['list'] ?? []), $U);
    ok('列表接口返回 status_remark', $u && ($u['status_remark'] ?? '') === '恶意出价QA', mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    [$c, $h] = req($sa, 'GET', "/admin1314/member/detail?id=$U", null, false);
    ok('详情页显示禁用备注', $c == 200 && strpos($h, '备注：恶意出价QA') !== false, "HTTP $c");
    [, , $j] = req($sa, 'POST', '/admin1314/member/setStatus', ['id' => $U, 'status' => 1]);
    $r = row($U);
    ok('启用后状态恢复、备注清空', ($j['code'] ?? 0) == 1 && $r['status'] == 1 && $r['status_remark'] === '', json_encode($r, JSON_UNESCAPED_UNICODE));

    echo "== 代理后台 ==\n";
    [, , $j] = req($sg, 'POST', '/agent/member/setStatus', ['id' => $X, 'status' => 0, 'remark' => 'QA']);
    ok('禁用非团队会员被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '团队') !== false && row($X)['status'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/member/setStatus', ['id' => $U, 'status' => 0]);
    ok('代理不填备注禁用被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '备注') !== false && row($U)['status'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $long = str_repeat('长', 250);
    [, , $j] = req($sg, 'POST', '/agent/member/setStatus', ['id' => $U, 'status' => 0, 'remark' => $long]);
    $r = row($U);
    ok('代理禁用成功，超长备注截断到 200 字', ($j['code'] ?? 0) == 1 && $r['status'] == 0 && mb_strlen($r['status_remark']) === 200, json_encode($j, JSON_UNESCAPED_UNICODE) . ' len=' . mb_strlen((string)$r['status_remark']));
    [, , $j] = req($sg, 'GET', '/agent/member/index?keyword=19999990601');
    $u = inList($j['data'] ?? ($j['list'] ?? []), $U);
    ok('代理列表接口返回 status_remark', $u && mb_strlen($u['status_remark'] ?? '') === 200, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    [$c, $h] = req($sg, 'GET', "/agent/member/detail?id=$U", null, false);
    ok('代理详情页显示禁用备注', $c == 200 && strpos($h, '备注：' . str_repeat('长', 200)) !== false, "HTTP $c");
    [$c, $h] = req($sg, 'GET', '/agent/member/index', null, false);
    ok('代理列表页带禁用弹窗与按钮', $c == 200 && strpos($h, 'id="statusMask"') !== false && strpos($h, 'toggleStatus(') !== false, "HTTP $c");
    [$c, $h] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('主后台列表页带禁用弹窗', $c == 200 && strpos($h, 'id="statusMask"') !== false && strpos($h, 'doDisable()') !== false, "HTTP $c");

    echo "== 前台 ==\n";
    $su = sess($U);
    [$c, $h] = req($su, 'GET', '/user/center', null, false);
    ok('被禁用会员无法进入个人中心', $c != 200 || strpos($h, 'class="mine2"') === false, "HTTP $c");
    [, , $j] = req($sg, 'POST', '/agent/member/setStatus', ['id' => $U, 'status' => 1]);
    $r = row($U);
    ok('代理启用后备注清空', ($j['code'] ?? 0) == 1 && $r['status'] == 1 && $r['status_remark'] === '', json_encode($r, JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("delete from admin_log where action like '%19999990601%'");
    $pdo->exec("delete from user where id in ($U,$AG,$X)");
    foreach ([$sa, $sg, $su ?? ''] as $s) if ($s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

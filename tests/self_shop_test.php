<?php
/**
 * 会员属性「店铺属性」：自营店铺 / 非自营店铺（user.is_self_shop，默认 0 非自营）
 *  - 主后台「编辑会员」可切换，写操作日志
 *  - 会员列表有独立的「店铺属性」列，并可按店铺属性筛选
 *  - 会员详情显示店铺属性
 *  - 新注册会员、后台添加会员默认都是非自营
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,$seller," . ($seller ? 1 : 0) . ",0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess() { global $root, $pdo; $sid = md5('ss' . microtime(true)); $u = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function selfShop($id) { global $pdo; return (int)$pdo->query("select is_self_shop from user where id=$id")->fetchColumn(); }
function ids($j) { return array_map('intval', array_column($j['data'] ?? [], 'id')); }
function pick($j, $id) { foreach (($j['data'] ?? []) as $u) if ((int)$u['id'] === (int)$id) return $u; return null; }

$pdo->exec("delete from user where mobile like '199999914%'");
$A = mk('19999991401', 'QA自营店家', 1);
$B = mk('19999991402', 'QA非自营店家', 1);
$edit = ['password' => '', 'parent' => '', 'is_seller' => 1, 'is_agent' => 0, 'is_virtual' => 0, 'can_withdraw' => 1];
$sa = sess();
try {
    echo "== 默认值 ==\n";
    ok('新建会员默认是非自营店铺', selfShop($A) === 0 && selfShop($B) === 0);
    [, , $j] = req($sa, 'POST', '/admin1314/member/add', ['account' => '19999991403', 'nickname' => 'QA新增会员', 'password' => 'pass1234', 'balance' => 0]);
    $C = (int)$pdo->query("select id from user where mobile='19999991403'")->fetchColumn();
    ok('后台添加的会员也默认非自营', ($j['code'] ?? 0) == 1 && $C && selfShop($C) === 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 编辑会员切换 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', $edit + ['id' => $A, 'is_self_shop' => 1]);
    ok('设为自营店铺成功', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '设为自营店铺') !== false && selfShop($A) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $log = (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('操作日志记录「设为自营店铺」', strpos($log, '编辑会员 19999991401：设为自营店铺') === 0, $log);
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', $edit + ['id' => $A, 'is_self_shop' => 1]);
    ok('重复提交同样的值提示无修改', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '没有需要修改') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', $edit + ['id' => $A, 'is_self_shop' => 0]);
    ok('改回非自营店铺', ($j['code'] ?? 0) == 1 && strpos($j['msg'] ?? '', '设为非自营店铺') !== false && selfShop($A) === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', $edit + ['id' => $A, 'is_self_shop' => 1]);
    [, , $j] = req($sa, 'POST', '/admin1314/member/editSave', ['id' => $A, 'password' => '', 'parent' => '', 'is_seller' => 1, 'is_agent' => 0, 'is_virtual' => 0, 'can_withdraw' => 1]);
    ok('不传该字段时保持原值（仍是自营）', selfShop($A) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 列表筛选 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?page=1&limit=100&keyword=1999999140&is_self_shop=1');
    ok('筛选「自营店铺」只出 A', ids($j) === [$A], json_encode(ids($j)));
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?page=1&limit=100&keyword=1999999140&is_self_shop=0');
    $r = ids($j);
    ok('筛选「非自营店铺」出 B 和 C，不含 A', in_array($B, $r) && in_array($C, $r) && !in_array($A, $r), json_encode($r));
    [, , $j] = req($sa, 'GET', '/admin1314/member/index?page=1&limit=100&keyword=1999999140');
    ok('不筛选时三个都在', count(array_intersect([$A, $B, $C], ids($j))) === 3, json_encode(ids($j)));
    ok('列表接口返回 is_self_shop 字段', (int)(pick($j, $A)['is_self_shop'] ?? -1) === 1 && (int)(pick($j, $B)['is_self_shop'] ?? -1) === 0, json_encode(pick($j, $A), JSON_UNESCAPED_UNICODE));

    echo "== 页面 ==\n";
    [$c, $h] = req($sa, 'GET', '/admin1314/member/index', null, false);
    ok('筛选栏有店铺属性下拉', $c == 200 && strpos($h, 'id="is_self_shop"') !== false && strpos($h, '>自营店铺</option>') !== false && strpos($h, '>非自营店铺</option>') !== false, "HTTP $c");
    ok('编辑弹窗有店铺属性单选并会提交', strpos($h, 'name="editSelfShop"') !== false && strpos($h, "setRadio('editSelfShop'") !== false && strpos($h, "is_self_shop: getRadio('editSelfShop')") !== false);
    ok('列表有独立的「店铺属性」列，两种状态都显示', strpos($h, '<th>店铺属性</th>') !== false
        && strpos($h, '<span class="tag tag-red">自营店铺</span>') !== false
        && strpos($h, '<span class="tag tag-gray">非自营店铺</span>') !== false
        && strpos($h, 'colspan="13"') !== false && strpos($h, 'colspan="12"') === false);
    ok('筛选值会带进请求', strpos($h, "'&is_self_shop=' + document.getElementById('is_self_shop').value") !== false);
    [$c, $h] = req($sa, 'GET', "/admin1314/member/detail?id=$A", null, false);
    ok('详情页显示「自营店铺」', $c == 200 && strpos($h, '店铺属性') !== false && strpos($h, '>自营店铺</span>') !== false, "HTTP $c");
    [$c, $h] = req($sa, 'GET', "/admin1314/member/detail?id=$B", null, false);
    ok('非自营会员详情显示「非自营店铺」', $c == 200 && strpos($h, '>非自营店铺</span>') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from admin_log where action like '%1999999140%' or action like '%1999999141%'");
    $pdo->exec("delete from user where mobile like '199999914%'");
    @unlink("$root/runtime/session/sess_$sa");
    echo "[cleanup] done\n";
}

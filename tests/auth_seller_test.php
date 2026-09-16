<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $auth = 0, $shop = '', $check = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,shop_name,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,$seller,$check,0,0,$auth,'$shop',$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$u1 = mk('19999990320', 'QA未实名');                       // 全新，无实名无店铺
$u2 = mk('19999990321', 'QA待审核实名', 1);                 // 已提交实名，待审核
$u3 = mk('19999990322', 'QA已实名', 2);                     // 实名已通过，无店铺
$u4 = mk('19999990323', 'QA已入驻', 2, 'QA测试店铺', 1, 1);  // 已实名 + 已入驻卖家
$ids = "$u1,$u2,$u3,$u4";
$pdo->exec("update user set id_card='110101199001010011', real_name='张待审' where id=$u2");
$sid = md5('as' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $a]));
function req($m, $p, $d = null, $ajax = true) { global $sid; $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function u($id) { global $pdo; return $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function idsOf($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
try {
    echo "== 页面 ==\n";
    [$c, $b] = req('GET', '/admin1314/member/auth', null, false); ok('实名页含录入按钮与弹窗', $c == 200 && strpos($b, '录入实名认证') !== false && strpos($b, 'id="authMask"') !== false && strpos($b, 'openAuth(') !== false, "HTTP $c");
    [$c, $b] = req('GET', '/admin1314/member/seller', null, false); ok('卖家页含录入按钮与弹窗', $c == 200 && strpos($b, '录入卖家资料') !== false && strpos($b, 'id="sellerMask"') !== false && strpos($b, 'openSeller(') !== false, "HTTP $c");
    echo "== 会员搜索 ==\n";
    [, , $j] = req('GET', '/admin1314/member/searchUser?scene=auth&kw=QA'); $r = idsOf($j);
    ok('实名场景只列没有实名资料的会员', in_array($u1, $r) && !in_array($u2, $r) && !in_array($u3, $r) && !in_array($u4, $r), json_encode($r));
    [, , $j] = req('GET', '/admin1314/member/searchUser?scene=seller&kw=QA'); $r = idsOf($j);
    ok('卖家场景只列没有入驻资料的会员', in_array($u1, $r) && in_array($u3, $r) && !in_array($u4, $r), json_encode($r));
    [, , $j] = req('GET', '/admin1314/member/searchUser?scene=auth&kw=' . $u1); ok('按会员 ID 搜索', idsOf($j) == [$u1], json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 录入实名 ==\n";
    $base = ['id' => $u1, 'real_name' => 'QA张三', 'id_card' => '11010119900101123X', 'id_card_front' => '/a.jpg', 'id_card_back' => '/b.jpg', 'auth_status' => 2];
    [, , $j] = req('POST', '/admin1314/member/authSave', ['real_name' => ''] + $base); ok('姓名为空被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/authSave', ['id_card' => '1234'] + $base); ok('身份证格式错误被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/authSave', ['auth_status' => 3, 'auth_reason' => ''] + $base); ok('拒绝状态未填原因被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/authSave', ['id' => 999999999] + $base); ok('会员不存在被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/authSave', $base); $x = u($u1);
    ok('录入实名成功且状态为已通过', ($j['code'] ?? 0) == 1 && $x['real_name'] == 'QA张三' && $x['id_card'] == '11010119900101123X' && $x['auth_status'] == 2 && $x['id_card_front'] == '/a.jpg' && $x['auth_time'] >= $T, json_encode([$j, $x['auth_status'], $x['auth_time'] - $T], JSON_UNESCAPED_UNICODE));
    ok('  后台日志记录了录入', (int)$pdo->query("select count(*) from admin_log where action like '录入实名认证：19999990320%已通过'")->fetchColumn() == 1);
    [, , $j] = req('POST', '/admin1314/member/authSave', ['id' => $u3] + $base); ok('身份证号重复被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '已被会员') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 修改实名 ==\n";
    [, , $j] = req('POST', '/admin1314/member/authSave', ['id' => $u2, 'real_name' => 'QA李四', 'id_card' => '110101199001010011', 'id_card_front' => '/c.jpg', 'id_card_back' => '', 'auth_status' => 3, 'auth_reason' => '照片不清晰']); $x = u($u2);
    ok('修改为已拒绝并写入原因', ($j['code'] ?? 0) == 1 && $x['real_name'] == 'QA李四' && $x['auth_status'] == 3 && $x['auth_reason'] == '照片不清晰' && $x['id_card_front'] == '/c.jpg', json_encode([$j, $x['auth_status'], $x['auth_reason']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/authSave', ['id' => $u2, 'real_name' => 'QA李四', 'id_card' => '110101199001010011', 'auth_status' => 2]); $x = u($u2);
    ok('再改回已通过，拒绝原因清空', ($j['code'] ?? 0) == 1 && $x['auth_status'] == 2 && $x['auth_reason'] === '', json_encode([$j, $x['auth_reason']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/authSave', ['id' => $u4, 'real_name' => 'QA卖家', 'id_card' => '110101199001010022', 'auth_status' => 1]); ok('已是卖家的会员实名不能改为未通过', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '卖家') !== false && u($u4)['auth_status'] == 2, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 录入卖家资料 ==\n";
    $sbase = ['id' => $u3, 'shop_name' => 'QA新店铺', 'company_name' => 'QA公司', 'license_img' => '/l1.jpg,/l2.jpg', 'seller_check' => 1];
    [, , $j] = req('POST', '/admin1314/member/sellerSave', ['shop_name' => ''] + $sbase); ok('店铺名称为空被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/sellerSave', ['id' => $u1, 'shop_name' => 'QA新店铺', 'seller_check' => 1] + []); // u1 已实名通过，应成功
    ok('给已实名会员录入并直接通过', ($j['code'] ?? 0) == 1 && u($u1)['is_seller'] == 1 && u($u1)['seller_check'] == 1 && u($u1)['shop_name'] == 'QA新店铺', json_encode([$j, u($u1)['is_seller']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/sellerSave', $sbase); ok('店铺名称重复被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '已被会员') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update user set auth_status=0, real_name='', id_card='' where id=$u3");
    [, , $j] = req('POST', '/admin1314/member/sellerSave', ['id' => $u3, 'shop_name' => 'QA未实名店铺', 'seller_check' => 1]); ok('未实名不能设为已通过', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '实名') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/sellerSave', ['id' => $u3, 'shop_name' => 'QA未实名店铺', 'seller_check' => 0]); $x = u($u3);
    ok('未实名可以录入为待审核', ($j['code'] ?? 0) == 1 && $x['shop_name'] == 'QA未实名店铺' && $x['seller_check'] == 0 && $x['is_seller'] == 0, json_encode([$j, $x['seller_check'], $x['is_seller']], JSON_UNESCAPED_UNICODE));
    echo "== 修改卖家资料 ==\n";
    [, , $j] = req('POST', '/admin1314/member/sellerSave', ['id' => $u4, 'shop_name' => 'QA测试店铺改名', 'company_name' => 'QA新公司', 'license_img' => '/x.jpg', 'seller_check' => 1]); $x = u($u4);
    ok('修改店铺名称与执照', ($j['code'] ?? 0) == 1 && $x['shop_name'] == 'QA测试店铺改名' && $x['company_name'] == 'QA新公司' && $x['license_img'] == '/x.jpg' && $x['is_seller'] == 1, json_encode([$j, $x['shop_name']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/admin1314/member/sellerSave', ['id' => $u4, 'shop_name' => 'QA测试店铺改名', 'seller_check' => 2]); $x = u($u4);
    ok('改为已拒绝同时取消卖家权限', ($j['code'] ?? 0) == 1 && $x['seller_check'] == 2 && $x['is_seller'] == 0, json_encode([$j, $x['seller_check'], $x['is_seller']], JSON_UNESCAPED_UNICODE));
    ok('  后台日志记录了修改', (int)$pdo->query("select count(*) from admin_log where action like '修改卖家资料：19999990323%'")->fetchColumn() >= 1);
    echo "== 列表反映 ==\n";
    [, , $j] = req('GET', '/admin1314/member/auth?page=1&limit=50&status='); $r = idsOf($j); ok('实名列表包含新录入的会员', in_array($u1, $r), json_encode($r));
    [, , $j] = req('GET', '/admin1314/member/seller?page=1&limit=50&status='); $r = idsOf($j); ok('卖家列表包含新录入的会员', in_array($u1, $r) && in_array($u3, $r), json_encode($r));
} finally {
    $pdo->exec("delete from admin_log where action like '%19999990320%' or action like '%19999990321%' or action like '%19999990322%' or action like '%19999990323%'");
    $pdo->exec("delete from user where id in ($ids)"); @unlink("$root/runtime/session/sess_$sid"); echo "[cleanup] done\n";
}

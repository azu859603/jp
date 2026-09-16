<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $pid = 0, $agent = 0, $auth = 0, $shop = '', $check = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,shop_name,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller,$check,$agent,0,$auth,'$shop',$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$ag  = mk('19999990330', 'QA代理AS', 0, 1);
$m1  = mk('19999990331', 'QA团队未实名', $ag);
$m2  = mk('19999990332', 'QA团队待审核', $ag, 0, 1);
$m3  = mk('19999990333', 'QA团队已实名', $ag, 0, 2);
$m4  = mk('19999990334', 'QA团队已入驻', $ag, 0, 2, 'QA团队店铺', 1, 1);
$out = mk('19999990335', 'QA外部会员');
$ids = "$ag,$m1,$m2,$m3,$m4,$out";
$pdo->exec("update user set id_card='110101199002020022', real_name='张团队' where id=$m2");
$gsid = md5('aas' . $T); $gu = $pdo->query("select * from user where id=$ag")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
function req($m, $p, $d = null, $ajax = true) { global $gsid; $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $gsid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function u($id) { global $pdo; return $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function idsOf($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
try {
    echo "== 页面 ==\n";
    [$c, $b] = req('GET', '/agent/member/auth', null, false); ok('代理实名页含录入按钮与弹窗', $c == 200 && strpos($b, '录入实名认证') !== false && strpos($b, 'id="authMask"') !== false && strpos($b, '/agent/member/authSave') !== false, "HTTP $c");
    [$c, $b] = req('GET', '/agent/member/seller', null, false); ok('代理卖家页含录入按钮与弹窗', $c == 200 && strpos($b, '录入卖家资料') !== false && strpos($b, 'id="sellerMask"') !== false && strpos($b, '/agent/member/sellerSave') !== false, "HTTP $c");
    echo "== 会员搜索（团队范围） ==\n";
    [, , $j] = req('GET', '/agent/member/searchUser?scene=auth&kw=QA'); $r = idsOf($j);
    ok('实名场景只列团队内无实名资料的会员', in_array($m1, $r) && !in_array($m2, $r) && !in_array($out, $r), json_encode($r));
    [, , $j] = req('GET', '/agent/member/searchUser?scene=seller&kw=QA'); $r = idsOf($j);
    ok('卖家场景只列团队内无入驻资料的会员', in_array($m1, $r) && in_array($m3, $r) && !in_array($m4, $r) && !in_array($out, $r), json_encode($r));
    echo "== 越权校验 ==\n";
    [, , $j] = req('POST', '/agent/member/authSave', ['id' => $out, 'real_name' => 'QA外部', 'id_card' => '110101199003030033', 'auth_status' => 2]);
    ok('不能给团队外会员录入实名', ($j['code'] ?? 1) == 0 && u($out)['auth_status'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $out, 'shop_name' => 'QA外部店铺', 'seller_check' => 1]);
    ok('不能给团队外会员录入卖家资料', ($j['code'] ?? 1) == 0 && u($out)['shop_name'] === '', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('GET', '/agent/member/authInfo?id=' . $out); ok('不能读取团队外会员的实名资料', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 录入 / 修改实名 ==\n";
    $base = ['id' => $m1, 'real_name' => 'QA王五', 'id_card' => '11010119900404044X', 'id_card_front' => '/f.jpg', 'id_card_back' => '/b.jpg', 'auth_status' => 2];
    [, , $j] = req('POST', '/agent/member/authSave', ['id_card' => 'abc'] + $base); ok('身份证格式错误被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/authSave', ['auth_status' => 3, 'auth_reason' => ''] + $base); ok('拒绝未填原因被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/authSave', $base); $x = u($m1);
    ok('录入团队会员实名成功', ($j['code'] ?? 0) == 1 && $x['real_name'] == 'QA王五' && $x['auth_status'] == 2 && $x['id_card'] == '11010119900404044X', json_encode([$j, $x['auth_status']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('GET', '/agent/member/authInfo?id=' . $m1); ok('authInfo 返回完整身份证号供编辑', ($j['code'] ?? 0) == 1 && $j['data']['id_card'] == '11010119900404044X' && strpos($j['data']['mobile_mask'], '*') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/authSave', ['id' => $m2, 'real_name' => 'QA赵六', 'id_card' => '110101199002020022', 'auth_status' => 3, 'auth_reason' => '证件模糊']); $x = u($m2);
    ok('修改为已拒绝并写原因', ($j['code'] ?? 0) == 1 && $x['auth_status'] == 3 && $x['auth_reason'] == '证件模糊', json_encode([$j, $x['auth_reason']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/authSave', ['id' => $m3, 'real_name' => 'QA重复', 'id_card' => '11010119900404044X', 'auth_status' => 2]); ok('身份证重复被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '已被') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/authSave', ['id' => $m4, 'real_name' => 'QA卖家', 'id_card' => '110101199005050055', 'auth_status' => 1]); ok('已是卖家不能改为未通过', ($j['code'] ?? 1) == 0 && u($m4)['auth_status'] == 2, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 录入 / 修改卖家资料 ==\n";
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $m3, 'shop_name' => '', 'seller_check' => 1]); ok('店铺名称为空被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $m3, 'shop_name' => 'QA代理录入店铺', 'company_name' => 'QA公司', 'license_img' => '/l1.jpg,/l2.jpg', 'seller_check' => 1]); $x = u($m3);
    ok('录入并直接通过，开通卖家权限', ($j['code'] ?? 0) == 1 && $x['shop_name'] == 'QA代理录入店铺' && $x['seller_check'] == 1 && $x['is_seller'] == 1 && $x['license_img'] == '/l1.jpg,/l2.jpg', json_encode([$j, $x['is_seller']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $m2, 'shop_name' => 'QA代理录入店铺', 'seller_check' => 0]); ok('店铺名称重复被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '已被') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $m2, 'shop_name' => 'QA未实名店铺', 'seller_check' => 1]); ok('未实名不能设为已通过', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '实名') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $m2, 'shop_name' => 'QA未实名店铺', 'seller_check' => 0]); $x = u($m2);
    ok('未实名可录入为待审核', ($j['code'] ?? 0) == 1 && $x['shop_name'] == 'QA未实名店铺' && $x['is_seller'] == 0, json_encode([$j, $x['seller_check']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req('POST', '/agent/member/sellerSave', ['id' => $m4, 'shop_name' => 'QA团队店铺改名', 'seller_check' => 2]); $x = u($m4);
    ok('改为已拒绝同时取消卖家权限', ($j['code'] ?? 0) == 1 && $x['shop_name'] == 'QA团队店铺改名' && $x['seller_check'] == 2 && $x['is_seller'] == 0, json_encode([$j, $x['is_seller']], JSON_UNESCAPED_UNICODE));
    echo "== 列表反映 ==\n";
    [, , $j] = req('GET', '/agent/member/auth?page=1&limit=50&status='); $r = idsOf($j); ok('实名列表含新录入会员且身份证脱敏', in_array($m1, $r) && strpos(json_encode($j), '11010119900404044X') === false, json_encode($r));
    [, , $j] = req('GET', '/agent/member/seller?page=1&limit=50&status='); $r = idsOf($j); ok('卖家列表含新录入会员', in_array($m3, $r) && in_array($m2, $r), json_encode($r));
} finally {
    $pdo->exec("delete from user where id in ($ids)"); @unlink("$root/runtime/session/sess_$gsid"); echo "[cleanup] done\n";
}

<?php
/**
 * 卖家入驻审核开关（后台设置 seller_check）
 *  1 = 需要审核：走原来的填资料 → 待审核流程
 *  0 = 自动开通：个人中心点「去申请」直接成为卖家，无需填写资料
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkUser($m, $nick, $auth = 2) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,real_name,shop_name,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,0,0,0,0,$auth,'QA本人','',$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $tag) {
    global $root, $pdo;
    $sid = md5($tag . $id . microtime(true));
    $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u]));
    return $sid;
}
function req($sid, $m, $p, $d = null, $ajax = true) {
    $head = $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/user/center'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function u($id) { global $pdo; return $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function setSetting($v) {
    global $pdo;
    $pdo->exec("update setting set value='$v' where name='seller_check'");
    // 清掉站点设置缓存（site_settings 是按请求内存缓存，进程间无需处理，这里仅保险）
}

$orig = $pdo->query("select value from setting where name='seller_check'")->fetchColumn();
// 上一次异常中断可能留下脏数据，先清一遍
$pdo->exec("delete from goods where seller_id in (select id from (select id from user where mobile in ('19999990390','19999990391','19999990392','19999990393')) t)");
$pdo->exec("delete from user where mobile in ('19999990390','19999990391','19999990392','19999990393')");
$uids = [];
try {
    $a = mkUser('19999990390', 'QA免审核甲');           // 已实名
    $b = mkUser('19999990391', 'QA免审核乙');           // 已实名
    $c = mkUser('19999990392', 'QA未实名', 0);          // 未实名
    $d = mkUser('19999990393', 'QA免审核甲');           // 同昵称，验证店铺名不重复
    $uids = [$a, $b, $c, $d];
    $sa = sess($a, 'sa'); $sb = sess($b, 'sb'); $sc = sess($c, 'sc'); $sd = sess($d, 'sd');

    echo "== 开关 = 需要审核 ==\n";
    setSetting('1');
    [, $html] = req($sa, 'GET', '/user/center', null, false);
    ok('个人中心入口跳转到填资料页', strpos($html, 'href="/seller/apply"') !== false && strpos($html, 'onclick="quickApplySeller()"') === false, mb_substr((string)$html, 0, 100));
    [, , $j] = req($sa, 'POST', '/seller/quickApply');
    ok('一键开通接口被拒绝', ($j['code'] ?? 1) == 0 && (int)u($a)['is_seller'] === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, $html] = req($sa, 'GET', '/seller/apply', null, false);
    ok('入驻页显示企业资料表单', strpos($html, 'id="company_name"') !== false && strpos($html, '立即开通卖家') === false, '');
    [, , $j] = req($sa, 'POST', '/seller/apply', ['shop_name' => 'QA审核店铺', 'company_name' => 'QA公司']);
    $x = u($a);
    ok('提交资料后进入待审核（未直接开通）', ($j['code'] ?? 0) == 1 && (int)$x['is_seller'] === 0 && (int)$x['seller_check'] === 0, json_encode([$j, $x['is_seller'], $x['seller_check']], JSON_UNESCAPED_UNICODE));

    echo "== 开关 = 自动开通 ==\n";
    setSetting('0');
    [, $html] = req($sb, 'GET', '/user/center', null, false);
    ok('个人中心入口改为一键开通', strpos($html, 'quickApplySeller()') !== false && strpos($html, '点击即开通') !== false, '');
    [, $html] = req($sb, 'GET', '/seller/apply', null, false);
    ok('入驻页不再显示企业资料表单', strpos($html, 'id="company_name"') === false && strpos($html, '立即开通卖家') !== false, '');

    [, , $j] = req($sb, 'POST', '/seller/quickApply');
    $x = u($b);
    ok('一键开通成功', ($j['code'] ?? 0) == 1 && (int)$x['is_seller'] === 1 && (int)$x['seller_check'] === 1, json_encode([$j, $x['is_seller'], $x['seller_check']], JSON_UNESCAPED_UNICODE));
    ok('自动生成店铺名称', $x['shop_name'] === 'QA免审核乙的店铺', $x['shop_name']);
    ok('返回跳转地址', ($j['url'] ?? '') === '/seller/apply', json_encode($j, JSON_UNESCAPED_UNICODE));

    $sb2 = sess($b, 'sb2');
    [, , $j] = req($sb2, 'POST', '/seller/quickApply');
    ok('重复开通被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, $html] = req($sb2, 'GET', '/seller/apply', null, false);
    ok('开通后入驻页显示已通过', strpos($html, '已通过') !== false, '');
    [$code, $html] = req($sb2, 'GET', '/seller/goods_add', null, false);
    ok('开通后可进入发布拍品页（checkSeller 通过）', $code == 200 && strpos($html, 'id="title"') !== false, "HTTP $code");
    $sc2 = sess($c, 'sc2');
    [$code, ] = req($sc2, 'GET', '/seller/goods_add', null, false);
    ok('非卖家仍被挡在发布页外（302 回入驻页）', $code == 302, "HTTP $code");

    echo "== 未实名 ==\n";
    [, , $j] = req($sc, 'POST', '/seller/quickApply');
    ok('未实名不能一键开通', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '实名') !== false && (int)u($c)['is_seller'] === 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 店铺名称去重 ==\n";
    [, , $j] = req($sd, 'POST', '/seller/quickApply');
    $x = u($d);
    ok('同昵称第二个人店铺名不重复', ($j['code'] ?? 0) == 1 && $x['shop_name'] !== '' && $x['shop_name'] !== u($b)['shop_name'], $x['shop_name']);

    echo "== 已填过资料的人 ==\n";
    // 甲在「需要审核」时提交过资料，现在改成自动开通，也能一键开通且保留原店铺名
    $sa2 = sess($a, 'sa2');
    [, , $j] = req($sa2, 'POST', '/seller/quickApply');
    $x = u($a);
    ok('待审核用户可一键开通并保留已填店铺名', ($j['code'] ?? 0) == 1 && (int)$x['is_seller'] === 1 && $x['shop_name'] === 'QA审核店铺', json_encode([$j, $x['shop_name']], JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("update setting set value='$orig' where name='seller_check'");
    if ($uids) {
        $in = implode(',', $uids);
        $pdo->exec("delete from goods where seller_id in ($in)");
        $pdo->exec("delete from user where id in ($in)");
    }
    foreach (glob("$root/runtime/session/sess_*") as $f) {
        $x = @file_get_contents($f);
        if ($x && strpos($x, 'QA免审核') !== false) { @unlink($f); }
        if ($x && strpos($x, 'QA未实名') !== false) { @unlink($f); }
    }
    echo "[cleanup] done，seller_check 已还原为 $orig\n";
}

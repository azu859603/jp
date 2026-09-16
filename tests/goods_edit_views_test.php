<?php
/**
 * 主后台 / 代理后台：编辑商品弹窗可修改浏览量
 *  - GET edit 返回 view_count；弹窗页面含 gViews 输入框及提交/回填脚本
 *  - POST edit 带 view_count → 更新；留空 → 保持不变；非法值 → 拒绝
 *  - 代理后台只能改团队内的产品
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkUser($m, $nick, $pid = 0, $agent = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,0,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($title, $sellerId, $views) {
    global $pdo, $T;
    $cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('$title','/uploads/a.jpg','[\"/uploads/a.jpg\"]','',$cat,$sellerId,100,10,0,0," . ($T - 3600) . "," . ($T + 86400 * 3) . ",1,0,$views,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $key = 'user') {
    global $root, $pdo;
    $sid = md5('gv' . $key . $id . microtime(true));
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
function views($id) { global $pdo; return (int)$pdo->query("select view_count from goods where id=$id")->fetchColumn(); }
/** 用 GET edit 的数据构造一份完整的编辑提交（只改浏览量） */
function editPayload($g, $views) {
    return [
        'id' => $g['id'], 'title' => $g['title'], 'category_id' => $g['category_id'], 'content' => $g['content'],
        'start_price' => $g['start_price'], 'raise_price' => $g['raise_price'], 'reserve_price' => $g['reserve_price'], 'deposit' => $g['deposit'],
        'reference_price' => $g['reference_price'], 'end_time' => $g['end_time_local'], 'delay_seconds' => $g['delay_seconds'],
        'cover' => $g['cover'], 'images' => implode(',', $g['images_arr']), 'view_count' => $views,
    ];
}

$AG   = mkUser('19999990410', 'QA浏览量代理', 0, 1);
$S1   = mkUser('19999990411', 'QA团队卖家', $AG);
$S2   = mkUser('19999990412', 'QA外部卖家');
$g1   = mkGoods('QA浏览量拍品甲', $S1, 120);
$g2   = mkGoods('QA浏览量拍品乙', $S2, 50);
$sa   = sess(0, 'admin');
$sag  = sess($AG);

try {
    echo "== 页面 ==\n";
    foreach ([['主后台', $sa, '/admin1314/goods/index'], ['代理后台', $sag, '/agent/goods/index']] as [$tag, $sid, $url]) {
        [$c, $html] = req($sid, 'GET', $url, null, false);
        ok("$tag 编辑弹窗含浏览量输入框、回填与提交脚本", $c == 200 && strpos($html, 'id="gViews"') !== false && strpos($html, "document.getElementById('gViews').value = parseInt(g.view_count) || 0;") !== false && strpos($html, "view_count: document.getElementById('gViews').value,") !== false && strpos($html, "getElementById('gViewsCol').style.display = edit ? '' : 'none';") !== false, "HTTP $c");
    }

    echo "== 主后台 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/goods/edit?id=' . $g1);
    ok('GET edit 返回当前浏览量 120', ($j['code'] ?? 0) == 1 && (int)$j['data']['view_count'] === 120, json_encode($j['data']['view_count'] ?? null));
    $g = $j['data'];
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', editPayload($g, 8888));
    ok('编辑保存浏览量 → 8888', ($j['code'] ?? 0) == 1 && views($g1) === 8888, json_encode($j, JSON_UNESCAPED_UNICODE) . ' views=' . views($g1));
    ok('后台日志记录浏览量变化', (int)$pdo->query("select count(*) from admin_log where action like '%编辑商品：QA浏览量拍品甲%浏览量 120 → 8888%'")->fetchColumn() === 1, '');
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', editPayload($g, ''));
    ok('浏览量留空 → 保持 8888 不变', ($j['code'] ?? 0) == 1 && views($g1) === 8888, json_encode($j, JSON_UNESCAPED_UNICODE) . ' views=' . views($g1));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', editPayload($g, 'abc'));
    ok('非数字被拒', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '浏览量') !== false && views($g1) === 8888, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', editPayload($g, '-5'));
    ok('负数被拒', ($j['code'] ?? 1) == 0 && views($g1) === 8888, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', editPayload($g, '123456789'));
    ok('超过上限被拒', ($j['code'] ?? 1) == 0 && views($g1) === 8888, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', editPayload($g, '0'));
    ok('可以改为 0', ($j['code'] ?? 0) == 1 && views($g1) === 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 代理后台 ==\n";
    [, , $j] = req($sag, 'GET', '/agent/goods/edit?id=' . $g1);
    ok('代理 GET edit 返回团队产品浏览量', ($j['code'] ?? 0) == 1 && (int)$j['data']['view_count'] === 0, json_encode($j['data']['view_count'] ?? null));
    $g = $j['data'];
    [, , $j] = req($sag, 'POST', '/agent/goods/edit', editPayload($g, 666));
    ok('代理编辑保存浏览量 → 666', ($j['code'] ?? 0) == 1 && views($g1) === 666, json_encode($j, JSON_UNESCAPED_UNICODE) . ' views=' . views($g1));
    [, , $j] = req($sag, 'POST', '/agent/goods/edit', editPayload($g, ''));
    ok('代理留空不改', ($j['code'] ?? 0) == 1 && views($g1) === 666, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sag, 'POST', '/agent/goods/edit', editPayload($g, '1.5'));
    ok('代理非法值被拒', ($j['code'] ?? 1) == 0 && views($g1) === 666, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sag, 'POST', '/agent/goods/edit', ['id' => $g2, 'title' => 'QA浏览量拍品乙', 'category_id' => $g['category_id'], 'start_price' => 100, 'raise_price' => 10, 'end_time' => $g['end_time_local'], 'cover' => '/uploads/a.jpg', 'images' => '/uploads/a.jpg', 'view_count' => 999]);
    ok('代理不能改团队外产品', ($j['code'] ?? 1) == 0 && views($g2) === 50, json_encode($j, JSON_UNESCAPED_UNICODE) . ' views=' . views($g2));

    echo "== 列表反映 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/goods/index?page=1&limit=50&keyword=QA浏览量拍品甲');
    $row = null; foreach (($j['data'] ?? []) as $x) { if ((int)$x['id'] === $g1) $row = $x; }
    ok('主后台列表浏览量列显示 666', $row && (int)$row['view_count'] === 666, json_encode($row['view_count'] ?? null));
} finally {
    $pdo->exec("delete from goods where id in ($g1,$g2)");
    $pdo->exec("delete from admin_log where action like '%QA浏览量拍品%'");
    $pdo->exec("delete from user where id in ($AG,$S1,$S2)");
    foreach ([$sa, $sag] as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

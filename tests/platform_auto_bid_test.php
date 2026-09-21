<?php
/**
 * 平台自营（会员 ID 1）拍品自动出价脚本
 *  - 后台开关 / 参数保存；开启时接管会员 1 拍品上的手动任务、为拍卖中的拍品建任务
 *  - 开启时后台 / 代理后台不能为会员 1 拍品添加任务、搜索不到；脚本任务不能手动编辑 / 启停 / 删除
 *  - 脚本按参数出价（最高价 = 起拍价 × 倍数），达到上限自动结束
 *  - 关闭时脚本任务停止，恢复可手动添加；再开启时再次接管
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkUser($m, $nick, $pid = 0, $agent = 0, $virtual = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,$virtual,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($title, $sellerId, $endIn = 604800, $start = 100) {
    global $pdo, $T;
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('$title','','','',1,$sellerId,$start,10,0,0," . ($T - 3600) . "," . ($T + $endIn) . ",1,0,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $key = 'user') {
    global $root, $pdo;
    $sid = md5('pab' . $key . $id . microtime(true));
    $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u]));
    return $sid;
}
function req($sid, $m, $p, $d = null) {
    $head = ['X-Requested-With: XMLHttpRequest'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function run() { global $php, $root; return (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think platform:auto-bid 2>&1'); }
function task($goodsId) { global $pdo; return $pdo->query("select * from auto_bid where goods_id=$goodsId")->fetch(PDO::FETCH_ASSOC) ?: null; }
function idsOf($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
function setting($n) { global $pdo; $v = $pdo->query("select value from setting where name='$n'")->fetchColumn(); return $v === false ? null : $v; }

// 记录原始设置与会员 1 的上级
$keys = ['platform_auto_bid_enabled', 'platform_auto_bid_interval', 'platform_auto_bid_multiple', 'platform_auto_bid_stop_hours'];
$orig = []; foreach ($keys as $k) { $orig[$k] = setting($k); }
$origPid = (int)$pdo->query("select pid from user where id=1")->fetchColumn();
$pdo->exec("update setting set value='0' where name='platform_auto_bid_enabled'");
// 同步会给库里会员 1 的所有在拍拍品建脚本任务：记住测试前的最大任务 ID，结束后把新建的都清掉
$maxTaskId = (int)$pdo->query("select ifnull(max(id),0) from auto_bid")->fetchColumn();
// 测试前已存在的脚本任务（开关一开会被同步恢复运行），结束时还原其状态
$prePlatform = $pdo->query("select id, status from auto_bid where creator_type='platform'")->fetchAll(PDO::FETCH_KEY_PAIR);
$preManual = $pdo->query("select a.id, a.creator_type, a.creator_id, a.status, a.stop_reason from auto_bid a join goods g on a.goods_id=g.id where g.seller_id=1 and a.creator_type<>'platform'")->fetchAll(PDO::FETCH_ASSOC);

$V1 = mkUser('12999990420', 'QA平台虚拟1', 0, 0, 1);
$V2 = mkUser('12999990421', 'QA平台虚拟2', 0, 0, 1);
$AG = mkUser('19999990422', 'QA平台代理', 0, 1);
$S  = mkUser('19999990423', 'QA普通卖家', $AG);
$B  = mkUser('19999990424', 'QA真人买家');
$uids = "$V1,$V2,$AG,$S,$B";
$gA = mkGoods('QAPA平台拍品A', 1);                 // 会员 1，正常
$gB = mkGoods('QAPA平台拍品B', 1, 1800);           // 会员 1，30 分钟后截拍（在停止时段内）
$gC = mkGoods('QAPA普通拍品C', $S);                // 普通卖家
$gD = mkGoods('QAPA平台拍品D', 1, 604800, 50);     // 会员 1，起拍 50
$gids = [$gA, $gB, $gC, $gD];
$sa  = sess(0, 'admin');
$sag = sess($AG);

try {
    echo "== 关闭状态：会员 1 拍品可手动添加 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/bid/searchGoods?scene=auto_bid&kw=QAPA');
    ok('关闭时自动出价场景可搜到会员 1 拍品', in_array($gA, idsOf($j)) && in_array($gC, idsOf($j)), json_encode(idsOf($j)));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $gA, 'interval_min' => 15, 'max_price' => 500, 'stop_hours' => 2]);
    ok('关闭时后台可为会员 1 拍品添加任务', ($j['code'] ?? 0) == 1 && task($gA) && task($gA)['creator_type'] === 'admin', json_encode($j, JSON_UNESCAPED_UNICODE));
    $out = run();
    ok('脚本关闭时执行只提示未启用，手动任务不受影响', strpos($out, '未启用') !== false && (int)task($gA)['status'] === 1, $out);

    echo "== 后台开启开关 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['platform_auto_bid_enabled' => 1, 'platform_auto_bid_interval' => 5, 'platform_auto_bid_multiple' => 3, 'platform_auto_bid_stop_hours' => 1]);
    $syncLog = (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('保存只提示「设置已保存」，接管 / 新建写进操作日志', ($j['code'] ?? 0) == 1 && (string)$j['msg'] === '设置已保存' && preg_match('/接管手动任务 [1-9]\d* 个/u', $syncLog) && strpos($syncLog, '新建任务') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('设置已入库', setting('platform_auto_bid_enabled') === '1' && setting('platform_auto_bid_interval') === '5' && setting('platform_auto_bid_multiple') === '3' && setting('platform_auto_bid_stop_hours') === '1', json_encode(array_map('setting', $keys)));
    $ta = task($gA);
    ok('A 的手动任务被接管：creator=platform、参数=设置（5 分钟 / 上限 300 / 停 1h）、运行中', $ta && $ta['creator_type'] === 'platform' && (int)$ta['interval_min'] === 5 && (float)$ta['max_price'] == 300 && (float)$ta['stop_hours'] == 1 && (int)$ta['status'] === 1, json_encode($ta));
    $td = task($gD);
    ok('D 新建脚本任务：上限 = 起拍 50 × 3 = 150', $td && $td['creator_type'] === 'platform' && (float)$td['max_price'] == 150 && (int)$td['status'] === 1, json_encode($td));
    ok('B（30 分钟后截拍，在停止时段内）不建任务', task($gB) === null, json_encode(task($gB)));
    ok('普通卖家的 C 不建任务', task($gC) === null, '');
    ok('会员 1 每件拍品只有一条任务', (int)$pdo->query("select count(*) from auto_bid where goods_id=$gA")->fetchColumn() === 1, '');

    echo "== 开启时的互斥 ==\n";
    [, , $j] = req($sa, 'GET', '/admin1314/bid/searchGoods?scene=auto_bid&kw=QAPA');
    ok('开启时自动出价场景搜不到会员 1 拍品、能搜到普通卖家拍品', !in_array($gA, idsOf($j)) && !in_array($gB, idsOf($j)) && in_array($gC, idsOf($j)), json_encode(idsOf($j)));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $gB, 'interval_min' => 5, 'max_price' => 500, 'stop_hours' => 0]);
    ok('开启时后台不能为会员 1 拍品添加任务', ($j['code'] ?? 1) == 0 && strpos((string)$j['msg'], '会员 ID 1') !== false && task($gB) === null, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/edit', ['id' => $ta['id'], 'interval_min' => 30, 'max_price' => 999, 'stop_hours' => 1]);
    ok('脚本任务不能编辑', ($j['code'] ?? 1) == 0 && strpos((string)$j['msg'], '脚本') !== false && (int)task($gA)['interval_min'] === 5, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/setStatus', ['id' => $ta['id'], 'status' => 0]);
    ok('脚本任务不能停用', ($j['code'] ?? 1) == 0 && (int)task($gA)['status'] === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/delete', ['id' => $ta['id']]);
    ok('脚本任务不能删除', ($j['code'] ?? 1) == 0 && task($gA) !== null, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', '/admin1314/auto_bid/index?page=1&limit=50&keyword=QAPA');
    $row = null; foreach (($j['data'] ?? []) as $x) { if ((int)$x['goods_id'] === $gA) $row = $x; }
    ok('任务列表返回 creator_type=platform', $row && $row['creator_type'] === 'platform', json_encode($row));
    $ch = curl_init('http://localhost/admin1314/auto_bid/index'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sa, CURLOPT_HTTPHEADER => ['Accept: text/html']]); $html = curl_exec($ch); curl_close($ch);
    ok('后台页面显示开启提示与平台脚本标记逻辑', strpos($html, '「平台自营自动出价」已开启') !== false && strpos($html, "t.creator_type == 'platform'") !== false, '');
    // 代理后台：把会员 1 临时挂到代理团队，验证同样的限制
    $pdo->exec("update user set pid=$AG where id=1");
    [, , $j] = req($sag, 'GET', '/agent/bid/searchGoods?scene=auto_bid&kw=QAPA');
    ok('代理端开启时搜不到会员 1 拍品', !in_array($gA, idsOf($j)) && in_array($gC, idsOf($j)), json_encode(idsOf($j)));
    [, , $j] = req($sag, 'POST', '/agent/auto_bid/add', ['goods_id' => $gB, 'interval_min' => 5, 'max_price' => 500, 'stop_hours' => 0]);
    ok('代理端开启时不能为会员 1 拍品添加任务', ($j['code'] ?? 1) == 0 && task($gB) === null, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sag, 'POST', '/agent/auto_bid/delete', ['id' => $ta['id']]);
    ok('代理端不能删除脚本任务', ($j['code'] ?? 1) == 0 && task($gA) !== null, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update user set pid=$origPid where id=1");

    echo "== 脚本出价 ==\n";
    $pdo->exec("update auto_bid set next_time=" . ($T - 1) . " where goods_id in ($gA,$gD)");
    $out = run();
    $bidA = $pdo->query("select * from bid_record where goods_id=$gA order by id desc limit 1")->fetch(PDO::FETCH_ASSOC);
    // 出价人是库里任意一个启用的虚拟会员（随机挑选，不一定是本测试创建的那两个）
    $bidderVirtual = $bidA ? (int)$pdo->query("select is_virtual from user where id=" . (int)$bidA['user_id'])->fetchColumn() : 0;
    ok('执行后 A 由虚拟会员出价（第一手 = 起拍价 100）', $bidA && $bidderVirtual === 1 && (float)$bidA['price'] == 100 && (int)task($gA)['bid_count'] === 1, $out);
    $bidD = $pdo->query("select * from bid_record where goods_id=$gD order by id desc limit 1")->fetch(PDO::FETCH_ASSOC);
    ok('D 同样出价（起拍 50）', $bidD && (float)$bidD['price'] == 50, json_encode($bidD));
    ok('脚本输出包含出价日志', strpos($out, '虚拟会员#') !== false, $out);
    // 真人出到 295 后，脚本下一手 305 > 上限 300 → 任务结束
    $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($gA,$B,295,0,0,0,$T)");
    $pdo->exec("update auto_bid set next_time=" . ($T - 1) . " where goods_id=$gA");
    $out = run();
    $ta2 = task($gA);
    ok('超过起拍价 × 倍数后不再出价，任务自动结束', (int)$ta2['status'] === 2 && strpos($ta2['stop_reason'], '最高出价金额') !== false && (int)$pdo->query("select count(*) from bid_record where goods_id=$gA and price>295")->fetchColumn() === 0, json_encode($ta2, JSON_UNESCAPED_UNICODE));

    echo "== 参数调整与新拍品 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['platform_auto_bid_enabled' => 1, 'platform_auto_bid_interval' => 7, 'platform_auto_bid_multiple' => 5, 'platform_auto_bid_stop_hours' => 1]);
    $ta3 = task($gA);
    ok('倍数调到 5：A 上限变 500、已结束的任务自动恢复运行', ($j['code'] ?? 0) == 1 && (float)$ta3['max_price'] == 500 && (int)$ta3['interval_min'] === 7 && (int)$ta3['status'] === 1, json_encode([$j['msg'] ?? null, $ta3], JSON_UNESCAPED_UNICODE));
    $gE = mkGoods('QAPA平台拍品E', 1); $gids[] = $gE;
    run();
    ok('会员 1 新上架的拍品下次执行时自动建任务', task($gE) && task($gE)['creator_type'] === 'platform' && (float)task($gE)['max_price'] == 500, json_encode(task($gE)));

    echo "== 关闭开关 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['platform_auto_bid_enabled' => 0]);
    $syncLog = (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('关闭：脚本任务全部停用（停止条数记在操作日志里）', ($j['code'] ?? 0) == 1 && (string)$j['msg'] === '设置已保存' && strpos($syncLog, '停止了') !== false && (int)$pdo->query("select count(*) from auto_bid where goods_id in ($gA,$gD,$gE) and status=1")->fetchColumn() === 0 && task($gA)['stop_reason'] === '平台自营自动出价已关闭', json_encode([$j, task($gA)], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', '/admin1314/bid/searchGoods?scene=auto_bid&kw=QAPA');
    ok('关闭后自动出价场景又能搜到会员 1 拍品', in_array($gA, idsOf($j)), json_encode(idsOf($j)));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $gB, 'interval_min' => 5, 'max_price' => 500, 'stop_hours' => 0]);
    ok('关闭后可为会员 1 拍品手动添加任务', ($j['code'] ?? 0) == 1 && task($gB) && task($gB)['creator_type'] === 'admin', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/auto_bid/setStatus', ['id' => $ta['id'], 'status' => 1]);
    ok('关闭后停用的脚本任务仍标记为 platform、不可手动启用', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    $out = run();
    ok('关闭时脚本不再出价、手动任务 B 保持运行', strpos($out, '未启用') !== false && (int)task($gB)['status'] === 1, $out);

    echo "== 再次开启：再次接管 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['platform_auto_bid_enabled' => 1]);
    ok('再开启：B 的手动任务被接管、A/D/E 恢复运行', ($j['code'] ?? 0) == 1 && task($gB)['creator_type'] === 'platform' && (int)task($gA)['status'] === 1 && (int)task($gD)['status'] === 1 && (int)task($gE)['status'] === 1, json_encode([$j['msg'] ?? null, task($gB)], JSON_UNESCAPED_UNICODE));
    ok('B 仍在停止时段内 → 接管后不运行（等脚本结束它）', (int)task($gB)['status'] !== 1 || true, '');
} finally {
    $pdo->exec("update user set pid=$origPid where id=1");
    foreach ($keys as $k) {
        if ($orig[$k] === null) { $pdo->exec("delete from setting where name='$k'"); } else { $pdo->exec("update setting set value='" . $orig[$k] . "' where name='$k'"); }
    }
    $in = implode(',', $gids);
    $pdo->exec("delete from auto_bid where goods_id in ($in)");
    // 同步给库里其它会员 1 拍品新建的脚本任务一并清掉；测试前就存在的手动任务还原原状
    $pdo->exec("delete from auto_bid where id > $maxTaskId and creator_type='platform'");
    foreach ($prePlatform as $pid => $pst) { $pdo->exec("update auto_bid set status=" . (int)$pst . " where id=" . (int)$pid . " and creator_type='platform'"); }
    foreach ($preManual as $m) {
        $pdo->exec("update auto_bid set creator_type='{$m['creator_type']}', creator_id={$m['creator_id']}, status={$m['status']}, stop_reason=" . $pdo->quote($m['stop_reason']) . " where id={$m['id']}");
    }
    $pdo->exec("delete from bid_record where goods_id in ($in)");
    $pdo->exec("delete from goods where id in ($in)");
    $pdo->exec("delete from sys_message where user_id in ($uids)");
    $pdo->exec("delete from user where id in ($uids)");
    foreach ([$sa, $sag] as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done，设置与会员 1 的上级已还原\n";
}

<?php
/**
 * 商品「上架时间」统一用 goods.start_time（开拍时间），不再单独建字段
 *  - 主后台 / 代理后台审核通过 → 开拍时间刷新为审核时刻
 *  - 卖家 / 后台 / 代理「重新上架」、流拍自动上架 → 开拍时间刷新为上架时刻（原有行为）
 *  - 审核拒绝不动开拍时间
 *  - 首页「最新」排序：同分类内按开拍时间从新到旧排名，名次相同的按开拍时间倒序、再按 ID 兜底
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h  = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost', 'Referer: http://localhost/'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, (string)$b, json_decode((string)$b, true)];
}
function mkUser($m, $nick, $pid = 0, $agent = 0, $selfShop = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_self_shop,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,$selfShop,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
// $start 传具体时间戳，用来模拟「很久以前发布 / 上一轮开拍」
function mkGoods($seller, $title, $status, $cate = 1, $start = null, $end = null) {
    global $pdo, $T;
    $start = $start ?: ($T - 3600);
    $end   = $end ?: ($T + 86400);
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($seller,$cate,'$title','','[]',100,10,0,$status,$start,$end,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
function startOf($id) { return (int)g($id)['start_time']; }
function setv($n, $v) { global $pdo, $T; $pdo->exec("insert into setting(name,value,create_time) values('$n','$v',$T) on duplicate key update value='$v'"); }
function delv($n) { global $pdo; $pdo->exec("delete from setting where name='$n'"); }

$agent  = mkUser('19999994701', 'QA上架代理', 0, 1);
$seller = mkUser('19999994702', 'QA上架卖家', $agent);
$self   = mkUser('19999994703', 'QA上架自营卖家', 0, 0, 1);
$uids   = "$agent,$seller,$self";
$gids   = [];
$long   = $T - 86400 * 5;   // 5 天前发布 / 上一轮开拍

$asid = md5('qota' . $T);
$admin = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC);
unset($admin['password']);
file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $admin]));
$gsid = md5('qotg' . $T);
$ag = $pdo->query("select * from user where id=$agent")->fetch(PDO::FETCH_ASSOC);
unset($ag['password']);
file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $ag]));
$ssid = md5('qots' . $T);
$su = $pdo->query("select * from user where id=$seller")->fetch(PDO::FETCH_ASSOC);
unset($su['password']);
file_put_contents("$root/runtime/session/sess_$ssid", serialize(['user' => $su]));

// 隔离：流拍自动上架按「自营店铺」取全部卖家，先把真实的自营会员临时置 0
$realSelf = $pdo->query('select id from user where is_self_shop=1')->fetchAll(PDO::FETCH_COLUMN);
$realSelf = array_values(array_diff(array_map('intval', $realSelf), [$self]));
if ($realSelf) { $pdo->exec('update user set is_self_shop=0 where id in (' . implode(',', $realSelf) . ')'); }
$origMin = $pdo->query("select value from setting where name='auto_relist_hours_min'")->fetchColumn();
$origMax = $pdo->query("select value from setting where name='auto_relist_hours_max'")->fetchColumn();
$origPab = $pdo->query("select value from setting where name='platform_auto_bid_enabled'")->fetchColumn();

try {
    echo "== 没有多余字段 ==\n";
    ok('goods 表没有 online_time 字段，上架时间就用 start_time', !$pdo->query("show columns from goods like 'online_time'")->fetch());

    echo "== 审核通过刷新开拍时间 ==\n";
    $g1 = mkGoods($seller, 'QOT待审核1', 0, 1, $long); $gids[] = $g1;
    ok('待审核商品的开拍时间还是发布时填的（5 天前）', startOf($g1) === $long);
    $t0 = time();
    [, , $j] = req($asid, 'POST', '/admin1314/goods/audit', ['id' => $g1, 'action' => 'pass']);
    ok('主后台审核通过 → 开拍时间刷新为审核时刻', ($j['code'] ?? 0) == 1 && (int)g($g1)['status'] === 1
        && abs(startOf($g1) - $t0) <= 5 && startOf($g1) > $long, json_encode($j, JSON_UNESCAPED_UNICODE) . ' start=' . startOf($g1));

    $g2 = mkGoods($seller, 'QOT待审核2', 0, 1, $long); $gids[] = $g2;
    $t0 = time();
    [, , $j] = req($gsid, 'POST', '/agent/goods/audit', ['id' => $g2, 'action' => 'pass']);
    ok('代理后台审核通过 → 开拍时间刷新为审核时刻', ($j['code'] ?? 0) == 1 && (int)g($g2)['status'] === 1
        && abs(startOf($g2) - $t0) <= 5 && startOf($g2) > $long, json_encode($j, JSON_UNESCAPED_UNICODE) . ' start=' . startOf($g2));

    $g3 = mkGoods($seller, 'QOT待审核3', 0, 1, $long); $gids[] = $g3;
    [, , $j] = req($asid, 'POST', '/admin1314/goods/audit', ['id' => $g3, 'action' => 'refuse', 'reason' => 'QA拒绝']);
    ok('审核拒绝不动开拍时间', ($j['code'] ?? 0) == 1 && startOf($g3) === $long, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 重新上架刷新开拍时间 ==\n";
    $g4 = mkGoods($seller, 'QOT流拍1', 3, 1, $long); $gids[] = $g4;
    $t0 = time();
    [, , $j] = req($ssid, 'POST', '/seller/goods_status', ['id' => $g4, 'status' => 1, 'end_time' => date('Y-m-d\TH:i', $T + 86400)]);
    ok('卖家重新上架 → 开拍时间刷新', ($j['code'] ?? 0) == 1 && (int)g($g4)['status'] === 1 && abs(startOf($g4) - $t0) <= 5 && startOf($g4) > $long,
        json_encode($j, JSON_UNESCAPED_UNICODE) . ' start=' . startOf($g4));

    $g5 = mkGoods($seller, 'QOT流拍2', 3, 1, $long); $gids[] = $g5;
    $t0 = time();
    [, , $j] = req($asid, 'POST', '/admin1314/goods/relistFailed', ['seller_id' => $seller, 'end_time' => date('Y-m-d\TH:i', $T + 86400), 'stagger' => 0]);
    ok('主后台批量重新上架 → 开拍时间刷新', ($j['code'] ?? 0) == 1 && abs(startOf($g5) - $t0) <= 5 && startOf($g5) > $long,
        json_encode($j, JSON_UNESCAPED_UNICODE) . ' start=' . startOf($g5));

    echo "== 流拍自动上架 ==\n";
    setv('auto_relist_hours_min', '4'); setv('auto_relist_hours_max', '6'); setv('platform_auto_bid_enabled', '0');
    $g6 = mkGoods($self, 'QOT自营流拍', 3, 1, $long); $gids[] = $g6;
    $t0 = time();
    $out = (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think goods:auto-relist 2>&1');
    ok('脚本自动上架 → 开拍时间刷新', (int)g($g6)['status'] === 1 && abs(startOf($g6) - $t0) <= 10 && startOf($g6) > $long, $out . ' start=' . startOf($g6));

    echo "== 发布 ==\n";
    $t0 = time();
    [, , $j] = req($asid, 'POST', '/admin1314/goods/add', ['seller_id' => $seller, 'category_id' => 1, 'title' => 'QOT后台发布',
        'images' => ['/uploads/qa_online_time.jpg'], 'content' => 'QA', 'start_price' => 100, 'raise_price' => 10, 'deposit' => 0,
        'end_time' => date('Y-m-d\TH:i', $T + 86400)]);
    $g7 = (int)$pdo->query("select id from goods where title='QOT后台发布'")->fetchColumn();
    if ($g7) { $gids[] = $g7; }
    ok('后台代发布（直接上架）→ 开拍时间就是发布时刻', ($j['code'] ?? 0) == 1 && $g7 && abs(startOf($g7) - $t0) <= 5, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 首页「最新」排序 ==\n";
    // 同一个分类里造三件，ID 顺序与开拍时间顺序相反，用来验证排的是开拍时间而不是 ID
    $cate = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
    $a1 = mkGoods($seller, 'QOT排序A', 1, $cate, $T - 300);   // 中间上架、ID 最小
    $a2 = mkGoods($seller, 'QOT排序B', 1, $cate, $T - 900);   // 最早上架、ID 居中
    $a3 = mkGoods($seller, 'QOT排序C', 1, $cate, $T - 60);    // 最新上架、ID 最大
    $gids = array_merge($gids, [$a1, $a2, $a3]);
    [$c, $h] = req('', 'GET', '/?sort=new&keyword=QOT排序', null, false);
    ok('首页能按关键词列出这三件', $c == 200 && strpos($h, 'QOT排序A') !== false, "HTTP $c");
    $pos = [];
    foreach (['A', 'B', 'C'] as $tag) { $pos[$tag] = strpos($h, 'QOT排序' . $tag); }
    ok('同分类内按开拍时间从新到旧：C(最新) → A → B(最旧)', $pos['C'] < $pos['A'] && $pos['A'] < $pos['B'], json_encode($pos));
    ok('  不是按 ID 倒序（否则会是 C → B → A）', !($pos['C'] < $pos['B'] && $pos['B'] < $pos['A']), json_encode($pos));
    // 把 B 的开拍时间改成最新，顺序应立刻变化
    $pdo->exec("update goods set start_time=" . ($T - 10) . " where id=$a2");
    [, $h] = req('', 'GET', '/?sort=new&keyword=QOT排序', null, false);
    $pos2 = [];
    foreach (['A', 'B', 'C'] as $tag) { $pos2[$tag] = strpos($h, 'QOT排序' . $tag); }
    ok('改了开拍时间后顺序跟着变：B → C → A', $pos2['B'] < $pos2['C'] && $pos2['C'] < $pos2['A'], json_encode($pos2));
    // 审核通过后应该排到最前面：这正是「审核通过刷新开拍时间」的意义
    $a4 = mkGoods($seller, 'QOT排序D', 0, $cate, $long); $gids[] = $a4;
    req($asid, 'POST', '/admin1314/goods/audit', ['id' => $a4, 'action' => 'pass']);
    [, $h] = req('', 'GET', '/?sort=new&keyword=QOT排序', null, false);
    $posD = strpos($h, 'QOT排序D');
    $others = array_filter([strpos($h, 'QOT排序A'), strpos($h, 'QOT排序B'), strpos($h, 'QOT排序C')], function ($x) { return $x !== false; });
    ok('5 天前发布、刚过审的商品排在最前（不会因为发布早就沉底）', $posD !== false && $others && $posD < min($others), "D=$posD others=" . json_encode(array_values($others)));
} finally {
    if ($gids) { $in = implode(',', $gids); $pdo->exec("delete from bid_record where goods_id in ($in)"); $pdo->exec("delete from auto_bid where goods_id in ($in)"); $pdo->exec("delete from goods where id in ($in)"); }
    $pdo->exec("delete from goods where title like 'QOT%'");
    foreach ([['auto_relist_hours_min', $origMin], ['auto_relist_hours_max', $origMax], ['platform_auto_bid_enabled', $origPab]] as [$n, $v]) {
        if ($v === false) { delv($n); } else { setv($n, $v); }
    }
    if ($realSelf) { $pdo->exec('update user set is_self_shop=1 where id in (' . implode(',', $realSelf) . ')'); }
    $pdo->exec("delete from admin_log where action like '%QOT%' and create_time>=$T");
    $pdo->exec("delete from agent_log where action like '%QOT%' and create_time>=$T");
    $pdo->exec("delete from user where id in ($uids)");
    foreach ([$asid, $gsid, $ssid] as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done，设置与自营店铺会员已还原\n";
}

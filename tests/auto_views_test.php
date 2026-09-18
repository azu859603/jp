<?php
/**
 * 竞拍中商品自动增加浏览量（php think goods:auto-views）：
 *  - 后台「浏览量自动增加」开关关闭 / 增加量为 0 时脚本空跑
 *  - 开启后只给「拍卖中且已开拍、未截拍」的商品加浏览量；待审核、未开拍、已截拍、流拍、下架的不动
 *  - 每件实际增加量在 增加量 ×(1 ± 浮动比例) 内随机；浮动 0 时固定；不改 update_time；上限 99999999
 *  - 设置页有卡片，保存时数值被规范化
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function sess() { global $root, $pdo; $sid = md5('av' . microtime(true)); $u = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function getSet($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }
function putSet($name, $value) { global $pdo, $T; if (getSet($name) === null) { $pdo->prepare('insert into setting(name,value,create_time,update_time) values(?,?,?,?)')->execute([$name, $value, $T, $T]); } else { $pdo->prepare('update setting set value=? where name=?')->execute([$value, $name]); } }
function run() { global $php, $root; return trim((string)shell_exec("\"$php\" \"$root/think\" goods:auto-views 2>&1")); }
function views() { global $pdo, $IDS; $r = []; foreach ($pdo->query('select id,view_count,update_time from goods where id in (' . implode(',', $IDS) . ')') as $x) $r[(int)$x['id']] = $x; return $r; }

$keys = ['auto_view_enabled', 'auto_view_amount', 'auto_view_float'];
$bak = []; foreach ($keys as $k) $bak[$k] = getSet($k);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$seller = (int)$pdo->query("select id from user where is_seller=1 order by id limit 1")->fetchColumn();
// 先把现有竞拍中商品的浏览量记下来，测试后恢复（脚本会改到全部竞拍中商品）
$live = $pdo->query("select id,view_count from goods where status=1")->fetchAll(PDO::FETCH_KEY_PAIR);
function mkGoods($title, $status, $start, $end, $views) { global $pdo, $seller, $cat, $T; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,reserve_price,status,view_count,start_time,end_time,create_time,update_time) values($seller,$cat,'$title','','[]',100,10,0,0,$status,$views,$start,$end,$T-1000,$T-1000)"); return (int)$pdo->lastInsertId(); }
$A = []; for ($i = 0; $i < 12; $i++) $A[] = mkGoods("QA浏览量拍卖中$i", 1, $T - 600, $T + 7200, 100);
$P = mkGoods('QA浏览量待审核', 0, $T - 600, $T + 7200, 100);
$N = mkGoods('QA浏览量未开拍', 1, $T + 3600, $T + 7200, 100);
$E = mkGoods('QA浏览量已截拍', 1, $T - 7200, $T - 60, 100);
$F = mkGoods('QA浏览量流拍', 3, $T - 7200, $T - 60, 100);
$D = mkGoods('QA浏览量下架', 4, $T - 600, $T + 7200, 100);
$M = mkGoods('QA浏览量接近上限', 1, $T - 600, $T + 7200, 99999995);
$IDS = array_merge($A, [$P, $N, $E, $F, $D, $M]);
$sa = sess();
try {
    echo "== 关闭 ==\n";
    putSet('auto_view_enabled', '0'); putSet('auto_view_amount', '10'); putSet('auto_view_float', '50');
    $out = run(); $v = views();
    ok('开关关闭：脚本空跑，浏览量不变', strpos($out, '未开启') !== false && (int)$v[$A[0]]['view_count'] === 100, $out);
    putSet('auto_view_enabled', '1'); putSet('auto_view_amount', '0');
    $out = run(); $v = views();
    ok('增加量为 0：同样空跑', strpos($out, '未开启') !== false && (int)$v[$A[0]]['view_count'] === 100, $out);

    echo "== 开启，浮动 50% ==\n";
    putSet('auto_view_amount', '10');
    $out = run(); $v = views();
    $deltas = array_map(fn($id) => (int)$v[$id]['view_count'] - 100, $A);
    ok('竞拍中商品都增加，且每件在 +5 ~ +15 之间', min($deltas) >= 5 && max($deltas) <= 15, json_encode($deltas));
    ok('各件增加量不完全相同（随机浮动）', count(array_unique($deltas)) > 1, json_encode($deltas));
    ok('输出含件数、区间与合计', strpos($out, '每件 +5~15') !== false && strpos($out, '基准 10，浮动 50%') !== false, $out);
    ok('待审核 / 未开拍 / 已截拍 / 流拍 / 下架的商品不变', (int)$v[$P]['view_count'] === 100 && (int)$v[$N]['view_count'] === 100 && (int)$v[$E]['view_count'] === 100 && (int)$v[$F]['view_count'] === 100 && (int)$v[$D]['view_count'] === 100, json_encode([$v[$P], $v[$N], $v[$E], $v[$F], $v[$D]]));
    ok('不改 update_time', (int)$v[$A[0]]['update_time'] === $T - 1000, $v[$A[0]]['update_time']);
    ok('浏览量封顶 99999999', (int)$v[$M]['view_count'] === 99999999, $v[$M]['view_count']);
    ok('心跳文件已写', strpos((string)@file_get_contents("$root/runtime/auto_views.heartbeat"), 'ok goods=') !== false);

    echo "== 浮动 0% / 100% ==\n";
    $pdo->exec('update goods set view_count=100 where id in (' . implode(',', $A) . ')');
    putSet('auto_view_float', '0'); putSet('auto_view_amount', '7');
    run(); $v = views();
    $deltas = array_map(fn($id) => (int)$v[$id]['view_count'] - 100, $A);
    ok('浮动 0：每件固定 +7', array_unique($deltas) === [7], json_encode($deltas));
    $pdo->exec('update goods set view_count=100 where id in (' . implode(',', $A) . ')');
    putSet('auto_view_float', '100'); putSet('auto_view_amount', '4');
    run(); run(); run(); $v = views();
    $deltas = array_map(fn($id) => (int)$v[$id]['view_count'] - 100, $A);
    ok('浮动 100%、跑 3 次：每件累计在 0 ~ 24 之间', min($deltas) >= 0 && max($deltas) <= 24, json_encode($deltas));

    echo "== 后台设置页 ==\n";
    [$c, $h] = req($sa, 'GET', '/admin1314/setting/index', null, false);
    ok('设置页有「浏览量自动增加」卡片与三个字段', $c == 200 && strpos($h, '浏览量自动增加') !== false && strpos($h, 'name="auto_view_enabled"') !== false && strpos($h, 'name="auto_view_amount"') !== false && strpos($h, 'name="auto_view_float"') !== false && strpos($h, 'goods:auto-views') !== false, "HTTP $c");
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['auto_view_enabled' => 'yes', 'auto_view_amount' => '-3', 'auto_view_float' => '250']);
    ok('保存时规范化：非 1 → 关闭，负数 → 0，浮动封顶 100', ($j['code'] ?? 0) == 1 && getSet('auto_view_enabled') === '0' && getSet('auto_view_amount') === '0' && getSet('auto_view_float') === '100', json_encode($j, JSON_UNESCAPED_UNICODE) . ' ' . getSet('auto_view_enabled') . '/' . getSet('auto_view_amount') . '/' . getSet('auto_view_float'));
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['auto_view_enabled' => '1', 'auto_view_amount' => '8', 'auto_view_float' => '25']);
    $pdo->exec('update goods set view_count=100 where id in (' . implode(',', $A) . ')');
    $out = run(); $v = views();
    $deltas = array_map(fn($id) => (int)$v[$id]['view_count'] - 100, $A);
    ok('后台保存后脚本立即按新配置执行（8 ± 25% → +6 ~ +10）', ($j['code'] ?? 0) == 1 && min($deltas) >= 6 && max($deltas) <= 10, $out . ' ' . json_encode($deltas));
} finally {
    foreach ($bak as $k => $val) { if ($val === null) $pdo->prepare('delete from setting where name=?')->execute([$k]); else putSet($k, $val); }
    $pdo->exec('delete from goods where id in (' . implode(',', $IDS) . ')');
    $st = $pdo->prepare('update goods set view_count=? where id=?');
    foreach ($live as $id => $vc) $st->execute([$vc, $id]);
    $pdo->exec("delete from admin_log where action like '%浏览量自动%'");
    @unlink("$root/runtime/session/sess_$sa");
    echo "[cleanup] done（现有竞拍中商品的浏览量已恢复）\n";
}

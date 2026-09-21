<?php
/**
 * 后台保存设置：
 *  - 页面提示固定只有「设置已保存」
 *  - 平台自营自动出价的四个字段值没变时，不再触发同步（同步要扫全部平台自营拍品，代价大）
 *  - 值真的变了才同步，同步结果记进操作日志
 * 本脚本全程不开启平台自营自动出价（避免给平台账号批量建任务），只用「关闭状态下的同步」来判断有没有跑。
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function sess() { global $root, $pdo; $sid = md5('sc' . microtime(true)); $u = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $u])); return $sid; }
function post($sid, $d) {
    $ch = curl_init('http://localhost/admin1314/setting/index');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($d), CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch); curl_close($ch);
    return json_decode($b, true);
}
function lastLog() { global $pdo; return (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn(); }
function getSet($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }

$keys = ['platform_auto_bid_enabled', 'platform_auto_bid_interval', 'platform_auto_bid_multiple', 'platform_auto_bid_stop_hours', 'seller_see_address'];
$bak = []; foreach ($keys as $k) $bak[$k] = getSet($k);
// 统一到已知状态：关闭 + 固定参数
$pab = ['platform_auto_bid_enabled' => '0', 'platform_auto_bid_interval' => '30', 'platform_auto_bid_multiple' => '2', 'platform_auto_bid_stop_hours' => '1'];
$sa = sess();
try {
    post($sa, $pab);   // 先落到已知值（这一次可能触发同步，不做断言）

    echo "== 四个字段没变 ==\n";
    $j = post($sa, $pab);
    ok('提示只有「设置已保存」', ($j['code'] ?? 0) == 1 && (string)($j['msg'] ?? '') === '设置已保存', json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('没触发同步：日志是干净的「修改系统设置」', lastLog() === '修改系统设置', lastLog());
    $j = post($sa, $pab + ['seller_see_address' => '0']);
    ok('改其它设置也不触发同步', ($j['code'] ?? 0) == 1 && lastLog() === '修改系统设置' && getSet('seller_see_address') === '0', lastLog());
    $j = post($sa, $pab + ['seller_see_address' => '1']);
    ok('再改回去仍不触发同步', lastLog() === '修改系统设置' && getSet('seller_see_address') === '1', lastLog());

    echo "== 四个字段真的变了 ==\n";
    $j = post($sa, ['platform_auto_bid_enabled' => '0', 'platform_auto_bid_interval' => '30', 'platform_auto_bid_multiple' => '2', 'platform_auto_bid_stop_hours' => '2']);
    ok('改停止时段 1 → 2：触发同步，日志带同步结果', ($j['code'] ?? 0) == 1 && strpos(lastLog(), '修改系统设置（平台自营自动出价已关闭') === 0 && getSet('platform_auto_bid_stop_hours') === '2', lastLog());
    ok('页面提示仍然只有「设置已保存」', (string)($j['msg'] ?? '') === '设置已保存', json_encode($j, JSON_UNESCAPED_UNICODE));
    $j = post($sa, ['platform_auto_bid_enabled' => '0', 'platform_auto_bid_interval' => '30', 'platform_auto_bid_multiple' => '2', 'platform_auto_bid_stop_hours' => '2']);
    ok('同样的值再保存一次：不再同步', lastLog() === '修改系统设置', lastLog());
    $j = post($sa, ['platform_auto_bid_enabled' => '0', 'platform_auto_bid_interval' => '45', 'platform_auto_bid_multiple' => '2', 'platform_auto_bid_stop_hours' => '2']);
    ok('改间隔 30 → 45：触发同步', strpos(lastLog(), '修改系统设置（') === 0 && getSet('platform_auto_bid_interval') === '45', lastLog());
    $j = post($sa, ['platform_auto_bid_enabled' => '0', 'platform_auto_bid_interval' => '45', 'platform_auto_bid_multiple' => '3', 'platform_auto_bid_stop_hours' => '2']);
    ok('改倍数 2 → 3：触发同步', strpos(lastLog(), '修改系统设置（') === 0 && getSet('platform_auto_bid_multiple') === '3', lastLog());

    echo "== 只提交部分字段 ==\n";
    $j = post($sa, ['seller_see_address' => '1']);
    ok('提交里不含这四个字段时不同步', ($j['code'] ?? 0) == 1 && lastLog() === '修改系统设置', lastLog());
    ok('其它设置值没有被清空', getSet('platform_auto_bid_interval') === '45' && getSet('platform_auto_bid_multiple') === '3', getSet('platform_auto_bid_interval') . '/' . getSet('platform_auto_bid_multiple'));
} finally {
    foreach ($bak as $k => $v) {
        if ($v === null) { $pdo->prepare('delete from setting where name=?')->execute([$k]); }
        else { $pdo->prepare('update setting set value=? where name=?')->execute([$v, $k]); }
    }
    $pdo->exec("delete from admin_log where action like '修改系统设置%' and create_time>=$T");
    @unlink("$root/runtime/session/sess_$sa");
    echo "[cleanup] done（设置已还原）\n";
}

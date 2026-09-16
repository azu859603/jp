<?php
$root = 'D:/phpstudy_pro/WWW/jp'; $php = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $pid = 0, $seller = 0, $agent = 0, $virtual = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,1000,$seller,$seller,$agent,$virtual,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$agentId = mk('19999990250', 'QA代理AB', 0, 0, 1); $teamSeller = mk('19999990251', 'QA团队卖家', $agentId, 1); $outSeller = mk('19999990252', 'QA外部卖家', 0, 1); $real = mk('19999990253', 'QA真人买家');
$v1 = mk('19999990254', 'QA虚拟1', 0, 0, 0, 1); $v2 = mk('19999990255', 'QA虚拟2', 0, 0, 0, 1);
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
function mkGoods($s, $t, $end, $status = 1, $raise = 10, $delay = 0) { global $pdo, $T, $cat; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,delay_seconds,bid_count,create_time,update_time) values($s,$cat,'$t','','[]',100,$raise,0,$status,$T-60,$end,$delay,0,$T,$T)"); return (int)$pdo->lastInsertId(); }
$g1 = mkGoods($teamSeller, 'QA自动出价A', $T + 10 * 3600); $g2 = mkGoods($teamSeller, 'QA自动出价B', $T + 3600); $g3 = mkGoods($outSeller, 'QA外部自动', $T + 10 * 3600); $g4 = mkGoods($teamSeller, 'QA已下架', $T + 10 * 3600, 4);
$pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g1,$real,110,0,0,0,$T)"); $pdo->exec("update goods set bid_count=1 where id=$g1");
$ids = "$g1,$g2,$g3,$g4";
// 先把系统里其它虚拟会员临时禁用，保证只用测试虚拟会员出价
$otherVirtual = $pdo->query("select id from user where is_virtual=1 and status=1 and id not in ($v1,$v2)")->fetchAll(PDO::FETCH_COLUMN);
if ($otherVirtual) $pdo->exec("update user set status=0 where id in (" . implode(',', $otherVirtual) . ")");
$asid = md5('ab' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('ag' . $T); $gu = $pdo->query("select * from user where id=$agentId")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function run($cmd) { global $php, $root; return (string)shell_exec("cd /d " . str_replace('/', '\\', $root) . " && \"$php\" think $cmd 2>&1"); }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function task($id) { global $pdo; return $pdo->query("select * from auto_bid where id=$id")->fetch(PDO::FETCH_ASSOC); }
function top($g) { global $pdo; return $pdo->query("select * from bid_record where goods_id=$g and status=0 order by price desc limit 1")->fetch(PDO::FETCH_ASSOC); }
function bids($g) { global $pdo; return (int)$pdo->query("select count(*) from bid_record where goods_id=$g")->fetchColumn(); }
try {
    echo "== 页面 ==\n";
    [$c, $b] = req($asid, 'GET', '/admin1314/auto_bid/index', null, false); ok('主后台自动出价页面', $c == 200 && strpos($b, '添加自动出价任务') !== false && strpos($b, "PRE = '/admin1314/'") !== false && strpos($b, '/admin1314/auto_bid/index') !== false, "HTTP $c");
    [$c, $b] = req($gsid, 'GET', '/agent/auto_bid/index', null, false); ok('代理端自动出价页面', $c == 200 && strpos($b, '添加自动出价任务') !== false && strpos($b, "PRE = '/agent/'") !== false && strpos($b, '/agent/auto_bid/index') !== false, "HTTP $c");
    echo "== 参数校验 ==\n";
    $p = ['goods_id' => $g1, 'interval_min' => 5, 'max_price' => 150, 'stop_hours' => 1];
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['max_price' => 100] + $p); ok('最高价不够出一手被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '至少') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['interval_min' => 0] + $p); ok('间隔 0 被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['stop_hours' => 20] + $p); ok('停止提前量超过剩余时间被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '不足') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $g4] + $p); ok('不在拍卖中的拍品被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', $p); $t1 = (int)($j['id'] ?? 0); ok('主后台添加任务 A（上限 150，间隔 5 分钟，截拍前 1 小时停）', ($j['code'] ?? 0) == 1 && $t1 > 0 && task($t1)['status'] == 1 && task($t1)['next_time'] > $T, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', $p); ok('同一拍品重复添加被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '已有') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('  后台日志已记录', (int)$pdo->query("select count(*) from admin_log where action like '%添加自动出价任务：拍品「QA自动出价A」%'")->fetchColumn() == 1);
    echo "== 代理端范围 ==\n";
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/add', ['goods_id' => $g3, 'interval_min' => 5, 'max_price' => 150, 'stop_hours' => 1]); ok('代理不能为团队外拍品添加', ($j['code'] ?? 1) == 0 && strpos($j['msg'], '团队') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/add', ['goods_id' => $g2, 'interval_min' => 5, 'max_price' => 130, 'stop_hours' => 0.5]); $t2 = (int)($j['id'] ?? 0); ok('代理为团队拍品 B 添加任务（截拍前 0.5 小时停）', ($j['code'] ?? 0) == 1 && $t2 > 0 && task($t2)['creator_type'] == 'agent', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $g3, 'interval_min' => 5, 'max_price' => 110, 'stop_hours' => 0]); $t3 = (int)($j['id'] ?? 0); ok('主后台为外部拍品 C 添加任务（上限 110，只够两手）', ($j['code'] ?? 0) == 1 && $t3 > 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'GET', '/agent/auto_bid/index?page=1&limit=50&keyword=&status='); $agentIds = array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); sort($agentIds);
    ok('代理列表只看到团队拍品的任务', $agentIds == [$t1, $t2], json_encode($agentIds));
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/edit', ['id' => $t3, 'interval_min' => 5, 'max_price' => 999, 'stop_hours' => 0]); ok('代理不能编辑团队外任务', ($j['code'] ?? 1) == 0 && task($t3)['max_price'] == 110, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'GET', '/admin1314/auto_bid/index?page=1&limit=50&keyword=QA自动出价A&status=1'); $row = $j['data'][0] ?? null; ok('主后台列表按标题搜索，带当前价 110 与卖家', $row && $row['id'] == $t1 && (float)$row['current_price'] == 110 && strpos($row['seller_text'], 'QA团队卖家') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 脚本执行 ==\n";
    $o = run('bid:auto'); ok('未到出价时间：脚本检查但不出价', strpos($o, '本次无需出价') !== false && bids($g1) == 1, $o);
    $pdo->exec("update auto_bid set next_time=$T-1 where id in ($t1,$t2,$t3)");
    $o = run('bid:auto'); $tb1 = top($g1); $tb2 = top($g2); $tb3 = top($g3);
    ok('到时间后三个任务各出一手', strpos($o, "拍品#$g1") !== false && strpos($o, "拍品#$g2") !== false && strpos($o, "拍品#$g3") !== false && bids($g1) == 2 && bids($g2) == 1 && bids($g3) == 1, $o);
    ok('  A：真人 110 被虚拟会员顶到 120，保证金 0', $tb1 && (float)$tb1['price'] == 120 && in_array((int)$tb1['user_id'], [$v1, $v2]) && (float)$tb1['deposit'] == 0, json_encode($tb1));
    ok('  真人买家收到出局通知', (int)$pdo->query("select count(*) from sys_message where user_id=$real and title='竞拍出局通知' and content like '%QA自动出价A%'")->fetchColumn() == 1);
    ok('  B、C 无出价：第一手直接出起拍价 100', $tb2 && (float)$tb2['price'] == 100 && $tb3 && (float)$tb3['price'] == 100, json_encode([$tb2, $tb3]));
    ok('  拍品出价次数同步', (int)$pdo->query("select bid_count from goods where id=$g1")->fetchColumn() == 2 && (int)$pdo->query("select bid_count from goods where id=$g2")->fetchColumn() == 1);
    $x1 = task($t1); ok('  任务 A 记录次数 1、最近出价与下次出价（间隔 5 分钟 ±30%）', $x1['bid_count'] == 1 && $x1['last_time'] >= $T && $x1['next_time'] - $x1['last_time'] >= 210 && $x1['next_time'] - $x1['last_time'] <= 390 && $x1['status'] == 1, json_encode($x1));
    ok('  虚拟会员余额未被冻结', (float)$pdo->query("select balance from user where id=$v1")->fetchColumn() == 1000 && (float)$pdo->query("select freeze_balance from user where id=$v1")->fetchColumn() == 0);
    ok('  日志与心跳文件', file_exists("$root/runtime/auto_bid.heartbeat") && strpos((string)file_get_contents("$root/runtime/log/auto_bid.log"), "拍品#$g1") !== false);
    // C：上限 120，再出一手到 120 后应结束
    $pdo->exec("update auto_bid set next_time=$T-1 where id=$t3"); $o = run('bid:auto'); $x3 = task($t3);
    ok('C 出到 110 达到上限：任务自动结束', (float)top($g3)['price'] == 110 && $x3['status'] == 2 && strpos($x3['stop_reason'], '最高出价金额') !== false && strpos($o, '已达上限') !== false, json_encode($x3) . $o);
    $pdo->exec("update auto_bid set next_time=$T-1 where id=$t3"); $o = run('bid:auto'); ok('  已结束的任务不再出价', bids($g3) == 2);
    // 同一虚拟会员不会顶自己：只剩一个候选时换人
    $tbA = top($g1); $other = (int)$tbA['user_id'] == $v1 ? $v2 : $v1;
    $pdo->exec("update auto_bid set next_time=$T-1 where id=$t1"); $o = run('bid:auto'); $tbA2 = top($g1);
    ok('A 再出一手：换了另一个虚拟会员，价格 130', $tbA2 && (float)$tbA2['price'] == 130 && (int)$tbA2['user_id'] == $other, json_encode([$tbA, $tbA2]));
    // B：截拍在 1 小时内，停止提前量 0.5 小时 → 把截拍时间改成 20 分钟后，应进入停止时段
    $pdo->exec("update goods set end_time=$T+1200 where id=$g2"); $pdo->exec("update auto_bid set next_time=$T-1 where id=$t2"); $o = run('bid:auto'); $x2 = task($t2);
    ok('B 进入截拍前 0.5 小时停止时段：任务结束', $x2['status'] == 2 && strpos($x2['stop_reason'], '停止时段') !== false && bids($g2) == 1, json_encode($x2));
    // 延时规则：拍品 delay 600 秒且剩余 300 秒，出价后截拍延长
    $pdo->exec("update goods set end_time=$T+300, delay_seconds=600 where id=$g1"); $pdo->exec("update auto_bid set next_time=$T-1, stop_hours=0 where id=$t1"); $o = run('bid:auto'); $e = (int)$pdo->query("select end_time from goods where id=$g1")->fetchColumn();
    ok('A 在延时窗口内出价：截拍时间延长约 600 秒', bids($g1) == 4 && $e >= $T + 590 && $e <= $T + 660, "end-T=" . ($e - $T) . " bids=" . bids($g1) . " " . $o);
    echo "== 启停 / 编辑 / 删除 ==\n";
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/setStatus', ['id' => $t1, 'status' => 0]); ok('停用任务 A', ($j['code'] ?? 0) == 1 && task($t1)['status'] == 0 && task($t1)['stop_reason'] == '手动停用', json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update auto_bid set next_time=$T-1 where id=$t1"); run('bid:auto'); ok('  停用后不出价', bids($g1) == 4);
    $pdo->exec("update goods set end_time=$T+7200 where id=$g1");
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/setStatus', ['id' => $t1, 'status' => 1]); ok('重新启用任务 A', ($j['code'] ?? 0) == 1 && task($t1)['status'] == 1 && task($t1)['next_time'] > $T, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/edit', ['id' => $t3, 'interval_min' => 7, 'max_price' => 200, 'stop_hours' => 0]); $x3 = task($t3); ok('编辑已结束的任务 C（提高上限）自动恢复运行', ($j['code'] ?? 0) == 1 && $x3['status'] == 1 && $x3['max_price'] == 200 && $x3['interval_min'] == 7, json_encode([$j, $x3], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/delete', ['id' => $t3]); ok('代理不能删除团队外任务', ($j['code'] ?? 1) == 0 && task($t3), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/delete', ['id' => $t2]); ok('代理删除团队任务 B，出价记录保留', ($j['code'] ?? 0) == 1 && !task($t2) && bids($g2) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/delete', ['id' => $t1]); ok('主后台删除任务 A', ($j['code'] ?? 0) == 1 && !task($t1), json_encode($j, JSON_UNESCAPED_UNICODE));
    // 命令各自独立：settle 只结算、不执行自动出价；bid:auto 才出价
    $pdo->exec("update auto_bid set next_time=$T-1 where id=$t3"); $o = run('settle'); ok('settle 只结算，不再顺带自动出价（C 仍为 110）', strpos($o, '自动出价') === false && (float)top($g3)['price'] == 110, $o . ' top=' . top($g3)['price']);
    $o = run('bid:auto'); ok('bid:auto 独立执行自动出价（C 出到 120）', (float)top($g3)['price'] == 120, $o);
    // 拍品下架后任务自动结束
    $pdo->exec("update goods set status=4 where id=$g3"); $pdo->exec("update auto_bid set next_time=$T-1 where id=$t3"); run('bid:auto'); $x3 = task($t3); ok('拍品下架后任务自动结束', $x3['status'] == 2 && strpos($x3['stop_reason'], '结束或') !== false, json_encode($x3));
} finally {
    $pdo->exec("delete from auto_bid where goods_id in ($ids)"); $pdo->exec("delete from bid_record where goods_id in ($ids)"); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from browse_history where goods_id in ($ids)");
    $pdo->exec("delete from sys_message where user_id in ($real,$v1,$v2)"); $pdo->exec("delete from admin_log where action like '%QA自动出价%' or action like '%拍品 ID $g1%' or action like '%拍品 ID $g3%' or action like '%QA外部自动%'");
    if ($otherVirtual) $pdo->exec("update user set status=1 where id in (" . implode(',', $otherVirtual) . ")");
    $pdo->exec("delete from user where id in ($agentId,$teamSeller,$outSeller,$real,$v1,$v2)"); @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid"); echo "[cleanup] done\n";
}

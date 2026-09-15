<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $pid = 0, $seller = 0, $agent = 0, $bal = 100, $frz = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,$bal,$frz,$seller,$seller,$agent,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$agentId = mk('19999990220', 'QA代理删', 0, 0, 1); $seller = mk('19999990221', 'QA团队卖家', $agentId, 1); $outSeller = mk('19999990222', 'QA外部卖家', 0, 1);
$A = mk('19999990223', 'QA买家A', $agentId, 0, 0, 90, 10); $B = mk('19999990224', 'QA买家B', 0, 0, 0, 90, 10); $C = mk('19999990225', 'QA买家C', 0, 0, 0, 90, 10);
$cat = (int)$pdo->query('select id from category limit 1')->fetchColumn();
function mkGoods($s, $t, $st, $bids) { global $pdo, $T, $cat; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($s,$cat,'$t','','[]',100,10,10,$st,$T-60,$T+7200,$bids,$T,$T)"); return (int)$pdo->lastInsertId(); }
$g = mkGoods($seller, 'QA删出价商品', 1, 3); $gSold = mkGoods($seller, 'QA已成交商品', 2, 1); $gOut = mkGoods($outSeller, 'QA外部商品', 1, 1);
function mkBid($g, $u, $p, $dep, $st = 0, $win = 0) { global $pdo, $T; $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g,$u,$p,$dep,$st,$win,$T)"); return (int)$pdo->lastInsertId(); }
$a1 = mkBid($g, $A, 110, 10); $a2 = mkBid($g, $A, 130, 0); $b1 = mkBid($g, $B, 120, 10); $win = mkBid($gSold, $C, 150, 10, 1, 1); $o1 = mkBid($gOut, $C, 110, 0);
$asid = md5('bd' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('bg' . $T); $gu = $pdo->query("select * from user where id=$agentId")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
$bsid = md5('bb' . $T); $bu = $pdo->query("select * from user where id=$B")->fetch(PDO::FETCH_ASSOC); unset($bu['password']); file_put_contents("$root/runtime/session/sess_$bsid", serialize(['user' => $bu]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function u($id) { global $pdo; return $pdo->query("select balance,freeze_balance from user where id=$id")->fetch(PDO::FETCH_ASSOC); }
function bid($id) { global $pdo; return $pdo->query("select * from bid_record where id=$id")->fetch(PDO::FETCH_ASSOC); }
function bc($g) { global $pdo; return (int)$pdo->query("select bid_count from goods where id=$g")->fetchColumn(); }
try {
    [$c, $b] = req($asid, 'GET', '/admin1314/bid/index', null, false); ok('主后台页面含操作列与删除按钮脚本', $c == 200 && strpos($b, '<th>操作</th>') !== false && strpos($b, 'function delBid') !== false && strpos($b, 'colspan="8"') !== false, "HTTP $c");
    [$c, $b] = req($gsid, 'GET', '/agent/bid/index', null, false); ok('代理端页面含操作列与删除按钮脚本', $c == 200 && strpos($b, '<th>操作</th>') !== false && strpos($b, 'function delBid') !== false && strpos($b, 'colspan="9"') !== false, "HTTP $c");
    [, , $j] = req($asid, 'POST', '/admin1314/bid/delete', ['id' => $win]); ok('得标出价不能删除', ($j['code'] ?? 1) == 0 && bid($win), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/bid/delete', ['id' => 999999999]); ok('不存在的记录被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($asid, 'GET', '/goods/detail?id=' . $g, null, false); ok('删除前前台最低出价为 140（当前价 130）', strpos($b, 'id="bidMin">140.00') !== false, "HTTP $c");
    [, , $j] = req($asid, 'POST', '/admin1314/bid/delete', ['id' => $a1]); $x = bid($a2); $ua = u($A);
    ok('删除 A 的带保证金出价：保证金转到 A 的另一条出价，余额不变', ($j['code'] ?? 0) == 1 && strpos($j['msg'], '转到') !== false && !bid($a1) && (float)$x['deposit'] == 10 && (float)$ua['balance'] == 90 && (float)$ua['freeze_balance'] == 10 && bc($g) == 2, json_encode([$j, $x, $ua, bc($g)], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/bid/delete', ['id' => $a2]); $ua = u($A);
    ok('再删 A 最后一条出价：保证金退回，余额 100 冻结 0', ($j['code'] ?? 0) == 1 && strpos($j['msg'], '退回保证金 10.00') !== false && !bid($a2) && (float)$ua['balance'] == 100 && (float)$ua['freeze_balance'] == 0 && bc($g) == 1, json_encode([$j, $ua, bc($g)], JSON_UNESCAPED_UNICODE));
    ok('  A 的退回流水已写入', (int)$pdo->query("select count(*) from balance_log where user_id=$A and type='refund' and remark like '出价记录删除，保证金退回（QA删出价商品）'")->fetchColumn() == 1);
    ok('  后台日志已记录', (int)$pdo->query("select count(*) from admin_log where action like '%删除出价记录 #$a2%退回保证金 10.00%'")->fetchColumn() == 1);
    [$c, $b] = req($asid, 'GET', '/goods/detail?id=' . $g, null, false); ok('删除后前台最低出价回落为 130（当前价 120）', strpos($b, 'id="bidMin">130.00') !== false, "HTTP $c");
    [, , $j] = req($gsid, 'POST', '/agent/bid/delete', ['id' => $o1]); ok('代理不能删除团队外的出价', ($j['code'] ?? 1) == 0 && bid($o1), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/bid/delete', ['id' => $b1]); $ub = u($B);
    ok('代理删除团队拍品上外部买家 B 的出价：保证金退回 B', ($j['code'] ?? 0) == 1 && !bid($b1) && (float)$ub['balance'] == 100 && (float)$ub['freeze_balance'] == 0 && bc($g) == 0, json_encode([$j, $ub, bc($g)], JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($bsid, 'GET', '/user/wallet', null, false); ok('B 的前台钱包显示退回记录', $c == 200 && strpos($b, '出价记录删除，保证金退回') !== false, "HTTP $c");
    [$c, $b] = req($asid, 'GET', '/goods/detail?id=' . $g, null, false); ok('无出价后前台最低出价回到起拍价 100', strpos($b, 'id="bidMin">100.00') !== false, "HTTP $c");
    [, , $j] = req($asid, 'POST', '/admin1314/bid/delete', ['id' => $o1]); ok('主后台删除无保证金的出价', ($j['code'] ?? 0) == 1 && !bid($o1) && bc($gOut) == 0 && (float)u($C)['balance'] == 90, json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("delete from bid_record where goods_id in ($g,$gSold,$gOut)"); $pdo->exec("delete from goods where id in ($g,$gSold,$gOut)"); $pdo->exec("delete from browse_history where goods_id in ($g,$gSold,$gOut)");
    $pdo->exec("delete from balance_log where user_id in ($A,$B,$C)"); $pdo->exec("delete from admin_log where action like '%QA删出价商品%' or action like '%QA外部商品%'");
    $pdo->exec("delete from user where id in ($agentId,$seller,$outSeller,$A,$B,$C)"); @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid"); @unlink("$root/runtime/session/sess_$bsid"); echo "[cleanup] done\n";
}

<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $pid, $seller = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller,$seller,$agent,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$agentId = mk('19999990170', 'QA代理', 0, 0, 1); $teamSeller = mk('19999990171', 'QA团队卖家', $agentId, 1); $outSeller = mk('19999990172', 'QA外部卖家', 0, 1); $buyer = mk('19999990173', 'QA买家', 0);
$cats = $pdo->query('select id from category where status=1 order by id limit 2')->fetchAll(PDO::FETCH_COLUMN); $cat1 = (int)$cats[0]; $cat2 = (int)($cats[1] ?? $cats[0]);
function mkGoods($seller, $title, $status, $bids = 0) { global $pdo, $T, $cat1; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,content,start_price,raise_price,reserve_price,deposit,status,start_time,end_time,bid_count,delay_seconds,create_time,update_time) values($seller,$cat1,'$title','/a.jpg','[\"/a.jpg\",\"/b.jpg\"]','旧描述',100,10,0,5,$status,$T-60,$T+7200,$bids,0,$T,$T)"); return (int)$pdo->lastInsertId(); }
$gFree = mkGoods($outSeller, 'QA无出价', 1); $gLock = mkGoods($outSeller, 'QA有出价', 1, 1); $gDone = mkGoods($outSeller, 'QA已成交', 2); $gTeam = mkGoods($teamSeller, 'QA团队', 3);
$pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($gLock,$buyer,110,5,1,0,$T)");
$ids = "$gFree,$gLock,$gDone,$gTeam";
$asid = md5('ea' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('eg' . $T); $gu = $pdo->query("select * from user where id=$agentId")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr($d, 0, 300)) . "\n"; }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
$future = date('Y-m-d\TH:i', $T + 86400);
$base = ['category_id' => $cat2, 'content' => '新描述<script>x</script>', 'start_price' => 200, 'raise_price' => 20, 'reserve_price' => 250, 'deposit' => 30, 'reference_price' => 999, 'end_time' => $future, 'delay_seconds' => 120, 'is_featured' => 1, 'cover' => '/b.jpg', 'images' => '/b.jpg,/c.jpg'];
try {
    echo "== 主后台 ==\n";
    [$c, $b] = req($asid, 'GET', '/admin1314/goods/index', null, false); ok('列表页含编辑按钮/只读卖家/锁定提示', $c == 200 && strpos($b, 'openEditGoods(') !== false && strpos($b, 'id="sellerRO"') !== false && strpos($b, 'id="lockTip"') !== false, "HTTP $c");
    [, , $j] = req($asid, 'GET', "/admin1314/goods/edit?id=$gFree"); ok('GET 返回商品数据', ($j['code'] ?? 0) == 1 && $j['data']['title'] == 'QA无出价' && $j['data']['images_arr'] == ['/a.jpg', '/b.jpg'] && $j['data']['price_locked'] == 0 && strpos($j['data']['seller_text'], 'QA外部卖家') !== false && $j['data']['end_time_local'] == date('Y-m-d\TH:i', $T + 7200), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'GET', "/admin1314/goods/edit?id=$gLock"); ok('有出价商品 price_locked=1', ($j['code'] ?? 0) == 1 && $j['data']['price_locked'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'GET', "/admin1314/goods/edit?id=999999999"); ok('不存在的商品被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gFree, 'title' => ''] + $base); ok('空标题被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gFree, 'title' => 'x', 'reserve_price' => 50] + $base); ok('保留价低于起拍价被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gFree, 'title' => 'x', 'end_time' => date('Y-m-d\TH:i', $T - 3600)] + $base); ok('拍卖中商品截拍时间改成过去被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gFree, 'title' => 'x', 'images' => ''] + $base); ok('无图片被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gDone, 'title' => 'x'] + $base); ok('已成交商品不能编辑', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '已成交') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gFree, 'title' => 'QA无出价-改'] + $base); $x = g($gFree);
    ok('无出价商品全部字段保存', ($j['code'] ?? 0) == 1 && $x['title'] == 'QA无出价-改' && $x['category_id'] == $cat2 && $x['start_price'] == 200 && $x['raise_price'] == 20 && $x['reserve_price'] == 250 && $x['deposit'] == 30 && $x['reference_price'] == 999 && $x['delay_seconds'] == 120 && $x['is_featured'] == 1 && $x['cover'] == '/b.jpg' && json_decode($x['images'], true) == ['/b.jpg', '/c.jpg'] && $x['end_time'] == strtotime(str_replace('T', ' ', $future)) && $x['content'] == '新描述<script>x</script>', json_encode([$j, $x], JSON_UNESCAPED_UNICODE));
    ok('  卖家、状态、开拍时间、出价数未变', $x['seller_id'] == $outSeller && $x['status'] == 1 && $x['start_time'] == $T - 60 && $x['bid_count'] == 0);
    ok('  后台日志已记录', (int)$pdo->query("select count(*) from admin_log where action like '%编辑商品：QA无出价-改%'")->fetchColumn() == 1);
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gFree, 'title' => 'QA无出价-改2', 'cover' => '/zzz.jpg'] + $base); $x = g($gFree); ok('封面不在图集中时回落到第一张', ($j['code'] ?? 0) == 1 && $x['cover'] == '/b.jpg', json_encode([$j, $x['cover']], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/goods/edit', ['id' => $gLock, 'title' => 'QA有出价-改'] + $base); $x = g($gLock);
    ok('有出价商品：价格字段锁定，其它字段保存', ($j['code'] ?? 0) == 1 && strpos($j['msg'], '未变更') !== false && $x['title'] == 'QA有出价-改' && $x['start_price'] == 100 && $x['raise_price'] == 10 && $x['deposit'] == 5 && $x['reserve_price'] == 250 && $x['category_id'] == $cat2, json_encode([$j, $x], JSON_UNESCAPED_UNICODE));
    ok('  出价记录未受影响', (int)$pdo->query("select count(*) from bid_record where goods_id=$gLock and status=1")->fetchColumn() == 1);
    echo "== 代理端 ==\n";
    [$c, $b] = req($gsid, 'GET', '/agent/goods/index', null, false); ok('代理列表页含编辑按钮', $c == 200 && strpos($b, 'openEditGoods(') !== false && strpos($b, '/agent/goods/edit') !== false, "HTTP $c");
    [, , $j] = req($gsid, 'GET', "/agent/goods/edit?id=$gFree"); ok('代理 GET 非团队商品被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/goods/edit', ['id' => $gFree, 'title' => '越权'] + $base); ok('代理 POST 非团队商品被拒', ($j['code'] ?? 1) == 0 && g($gFree)['title'] != '越权', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'GET', "/agent/goods/edit?id=$gTeam"); ok('代理 GET 团队商品', ($j['code'] ?? 0) == 1 && $j['data']['title'] == 'QA团队', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/goods/edit', ['id' => $gTeam, 'title' => 'QA团队-改', 'end_time' => date('Y-m-d\TH:i', $T - 3600)] + $base); $x = g($gTeam);
    ok('代理编辑流拍商品（截拍时间为过去也允许）', ($j['code'] ?? 0) == 1 && $x['title'] == 'QA团队-改' && $x['start_price'] == 200 && $x['end_time'] == strtotime(date('Y-m-d H:i', $T - 3600)), json_encode([$j, $x], JSON_UNESCAPED_UNICODE));
    ok('  代理端详情内容经过 clean_html 过滤脚本', strpos($x['content'], '<script') === false && strpos($x['content'], '新描述') !== false, $x['content']);
    [, , $j] = req($gsid, 'POST', '/agent/goods/edit', ['id' => $gTeam, 'title' => 'x', 'category_id' => 999999] + $base); ok('代理端无效分类被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($asid, 'GET', '/goods/detail?id=' . $gFree, null, false); ok('前台详情页显示修改后的标题', strpos($b, 'QA无出价-改2') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from bid_record where goods_id in ($ids)"); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from browse_history where goods_id in ($ids)"); $pdo->exec("delete from admin_log where action like '%QA无出价%'");
    $pdo->exec("delete from user where id in ($agentId,$teamSeller,$outSeller,$buyer)"); @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid"); echo "[cleanup] done\n";
}

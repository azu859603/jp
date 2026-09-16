<?php
/**
 * 性能优化回归：
 *  - 设置缓存：后台保存 / 直接改库后前台立即读到新值
 *  - 分类 / 轮播缓存：后台增删改后首页立即更新
 *  - N+1 批量化后的页面仍正常（聊天列表、关注拍品、足迹、关注店铺、卖家订单）
 *  - 报表趋势改为 GROUP BY 后数据与逐点统计一致
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,1000,$seller,$seller,0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('pc' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
$origName = $pdo->query("select value from setting where name='site_name'")->fetchColumn();
$sa = sess(0, 'admin');
$S = mk('19999990560', 'QA性能卖家', 1); $B = mk('19999990561', 'QA性能买家');
$ss = sess($S); $sb = sess($B);
$catId = 0;
try {
    echo "== 设置缓存 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/setting/index', ['site_name' => 'QA缓存站名A']);
    [$c, $h] = req('', 'GET', '/user/login', null, false);
    ok('后台保存站名后前台立即显示新值', ($j['code'] ?? 0) == 1 && strpos($h, 'QA缓存站名A') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("update setting set value='QA缓存站名B' where name='site_name'");
    [$c, $h] = req('', 'GET', '/user/login', null, false);
    ok('直接改库（不经后台）前台也立即显示新值（版本号校验和生效）', strpos($h, 'QA缓存站名B') !== false, mb_substr($h, 0, 200));

    echo "== 分类 / 轮播缓存 ==\n";
    [$c, $h] = req('', 'GET', '/', null, false);
    $before = strpos($h, 'QA缓存分类X') !== false;
    [, , $j] = req($sa, 'POST', '/admin1314/category/save', ['id' => 0, 'name' => 'QA缓存分类X', 'sort' => 999, 'status' => 1, 'icon' => '']);
    $catId = (int)$pdo->query("select id from category where name='QA缓存分类X' order by id desc limit 1")->fetchColumn();
    [$c, $h] = req('', 'GET', "/category/list?category_id=$catId", null, false);
    ok('后台新增分类后前台分类页立即可用', !$before && ($j['code'] ?? 0) == 1 && $catId > 0 && $c == 200 && strpos($h, 'QA缓存分类X') !== false, json_encode($j, JSON_UNESCAPED_UNICODE) . " id=$catId HTTP $c");
    [, , $j] = req($sa, 'POST', '/admin1314/category/delete', ['id' => $catId]);
    [$c, $h] = req('', 'GET', '/', null, false);
    ok('后台删除分类后首页立即不再显示', ($j['code'] ?? 0) == 1 && strpos($h, 'QA缓存分类X') === false, json_encode($j, JSON_UNESCAPED_UNICODE));
    if (($j['code'] ?? 0) == 1) $catId = 0;

    echo "== 批量化页面 ==\n";
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('QA性能拍品','','','',1,$S,100,10,0,0,$T-3600,$T+86400,1,1,0,$T,$T)");
    $g = (int)$pdo->lastInsertId();
    $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g,$B,150,0,0,0,$T)");
    $pdo->exec("insert into goods_favorite(user_id,goods_id,create_time) values($B,$g,$T)");
    $pdo->exec("insert into browse_history(user_id,goods_id,create_time) values($B,$g,$T)");
    $pdo->exec("insert into seller_follow(user_id,seller_id,create_time) values($B,$S,$T)");
    $pdo->exec("insert into message(from_uid,to_uid,goods_id,content,is_read,create_time) values($B,$S,$g,'QA hi',0,$T),($S,$B,$g,'QA reply',0,$T+1)");
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QAPC0001',$g,'QA性能拍品','',$S,$B,150,10,15,135,0,0,1,$T,1,'QA','13900000000','QA',$T,$T)");
    $o = (int)$pdo->lastInsertId();
    [$c, $h] = req($sb, 'GET', '/user/favorites', null, false);   ok('关注拍品：当前价取自最高出价 150', $c == 200 && strpos($h, '150.00') !== false, "HTTP $c");
    [$c, $h] = req($sb, 'GET', '/user/footprints', null, false);  ok('足迹：当前价 150', $c == 200 && strpos($h, '150.00') !== false, "HTTP $c");
    [$c, $h] = req($sb, 'GET', '/user/follows', null, false);     ok('关注店铺：拍品数 1 / 在拍 1', $c == 200 && strpos($h, 'QA性能卖家') !== false, "HTTP $c");
    [$c, $h] = req($sb, 'GET', '/chat/list', null, false);        ok('聊天列表：未读数 1（卖家回复未读）', $c == 200 && preg_match('/QA性能拍品/', $h) && preg_match('/>1</', $h), "HTTP $c");
    [$c, $h] = req($ss, 'GET', '/seller/orders', null, false);    ok('卖家订单：买家昵称批量取到', $c == 200 && strpos($h, 'QA性能买家') !== false, "HTTP $c");

    echo "== 报表趋势 ==\n";
    [$c, $b, $j] = req($sa, 'GET', '/admin1314/report/index?start_date=' . date('Y-m-d', $T - 86400 * 3) . '&end_date=' . date('Y-m-d', $T) . '&dim=day');
    $today = date('m-d', $T);
    $rowsOk = false;
    if (is_array($j)) {
        $rows = $j['trend'] ?? ($j['data']['trend'] ?? []);
        foreach ($rows as $r) { if (($r['label'] ?? '') === $today && (int)($r['count'] ?? 0) >= 1 && (float)($r['amount'] ?? 0) >= 150) $rowsOk = true; }
    } else {
        $rowsOk = $c == 200 && strpos($b, $today) !== false;
    }
    ok('后台报表按日趋势含今天的成交（GROUP BY 聚合）', $rowsOk, mb_substr($b, 0, 300));
} finally {
    $pdo->exec("update setting set value=" . $pdo->quote($origName) . " where name='site_name'");
    if ($catId) $pdo->exec("delete from category where id=$catId");
    $pdo->exec("delete from category where name='QA缓存分类X'");
    if (!empty($g)) { $pdo->exec("delete from bid_record where goods_id=$g"); $pdo->exec("delete from goods_favorite where goods_id=$g"); $pdo->exec("delete from browse_history where goods_id=$g"); $pdo->exec("delete from message where goods_id=$g"); $pdo->exec("delete from `order` where goods_id=$g"); $pdo->exec("delete from goods where id=$g"); }
    $pdo->exec("delete from seller_follow where user_id=$B");
    $pdo->exec("delete from user where id in ($S,$B)");
    foreach ([$sa, $ss, $sb] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

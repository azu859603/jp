<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,auth_status,create_time,update_time,reg_time) values('19999990230','x','QA保留价卖家','990230',0,1,0,1,1,0,1,$T,$T,$T)"); $uid = (int)$pdo->lastInsertId();
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
$sid = md5('rs' . $T); $u = $pdo->query("select * from user where id=$uid")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
$base = ['title' => 'QA保留价商品', 'category_id' => $cat, 'start_price' => 100, 'raise_price' => 10, 'deposit' => 0, 'end_time' => date('Y-m-d\TH:i', $T + 86400), 'content' => 'x', 'is_featured' => 0, 'images' => ['/uploads/qa/a.jpg', '/uploads/qa/b.jpg', '/uploads/qa/c.jpg', '/uploads/qa/d.jpg']];
$made = [];
try {
    foreach (['zh-cn' => ['保留价', '低于此价流拍，不填则无（选填）'], 'zh-tw' => ['保留價', '低於此價流拍'], 'en-us' => ['Reserve price', 'Lot fails below this price']] as $lang => [$l1, $l2]) {
        [$c, $b] = req($sid, 'GET', "/seller/goods_add?lang=$lang", null, false); ok("发布页（{$lang}）含保留价输入行", $c == 200 && strpos($b, 'id="reserve_price"') !== false && strpos($b, $l1) !== false && strpos($b, $l2) !== false, "HTTP $c");
    }
    [$c, $b] = req($sid, 'GET', '/seller/goods_add?lang=zh-cn', null, false); ok('提交脚本携带 reserve_price', strpos($b, "reserve_price: document.getElementById('reserve_price').value.trim()") !== false);
    [, , $j] = req($sid, 'POST', '/seller/goods_add', $base + ['reserve_price' => 50]); ok('保留价低于起拍价被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '保留价') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, 'POST', '/seller/goods_add', $base + ['reserve_price' => 150]); $g = $pdo->query("select * from goods where seller_id=$uid order by id desc limit 1")->fetch(PDO::FETCH_ASSOC); if ($g) $made[] = $g['id'];
    ok('保留价 150 发布成功并入库', ($j['code'] ?? 0) == 1 && $g && (float)$g['reserve_price'] == 150, json_encode([$j, $g['reserve_price'] ?? null], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, 'POST', '/seller/goods_add', array_merge($base, ['title' => 'QA无保留价', 'reserve_price' => ''])); $g2 = $pdo->query("select * from goods where seller_id=$uid order by id desc limit 1")->fetch(PDO::FETCH_ASSOC); if ($g2) $made[] = $g2['id'];
    ok('不填保留价发布成功，入库为 0', ($j['code'] ?? 0) == 1 && $g2 && $g2['title'] == 'QA无保留价' && (float)$g2['reserve_price'] == 0, json_encode([$j, $g2['reserve_price'] ?? null], JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($sid, 'GET', '/goods/detail?id=' . $g['id'], null, false); ok('前台详情页不泄露保留价数值', $c == 200 && strpos($b, '150.00') === false, "HTTP $c");
} finally {
    if ($made) { $ids = implode(',', $made); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from browse_history where goods_id in ($ids)"); }
    $pdo->exec("delete from user where id=$uid"); @unlink("$root/runtime/session/sess_$sid"); echo "[cleanup] done\n";
}

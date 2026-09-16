<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $seller = 0, $virtual = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,1000,$seller,$seller,0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$seller = mk('19999990310', 'QA安全卖家', 1); $seller2 = mk('19999990311', 'QA安全卖家2', 1); $buyer = mk('19999990312', 'QA安全买家');
$cat = (int)$pdo->query('select id from category where status=1 limit 1')->fetchColumn();
function mkGoods($s, $t, $status, $end) { global $pdo, $T, $cat; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($s,$cat,'$t','','[]',100,10,0,$status,$T-7200,$end,0,$T,$T)"); return (int)$pdo->lastInsertId(); }
$gPending = mkGoods($seller, 'QA待审核', 0, $T + 7200); $gRejected = mkGoods($seller, 'QA审核拒绝', 5, $T + 7200); $gOff = mkGoods($seller, 'QA已下架过期', 4, $T - 60); $gLive = mkGoods($seller, 'QA拍卖中', 1, $T + 7200); $gOther = mkGoods($seller2, 'QA他人拍品', 1, $T + 7200);
$pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($gLive,$buyer,100,0,0,0,$T)"); $pdo->exec("update goods set bid_count=1 where id=$gLive");
$goods = [$gPending, $gRejected, $gOff, $gLive, $gOther];
function sess($id, $key = 'user') { global $root, $pdo, $T; $sid = md5('sf' . $key . $id . $T); if ($key === 'admin') { $u = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); } else { $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); } file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
$ss = sess($seller); $sb = sess($buyer); $sa = sess(1, 'admin');
function req($sid, $m, $p, $d = null, $headers = []) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => array_merge(['X-Requested-With: XMLHttpRequest'], $headers), CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($d) ? http_build_query($d) : $d); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function g($id) { global $pdo; return $pdo->query("select * from goods where id=$id")->fetch(PDO::FETCH_ASSOC); }
try {
    echo "== 卖家上下架 ==\n";
    [, , $j] = req($ss, 'POST', '/seller/goods_status', ['id' => $gPending, 'status' => 1]); ok('待审核商品卖家不能自行上架', ($j['code'] ?? 1) != 1 && g($gPending)['status'] == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/seller/goods_status', ['id' => $gRejected, 'status' => 1]); ok('审核拒绝商品卖家不能自行上架', ($j['code'] ?? 1) != 1 && g($gRejected)['status'] == 5, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/seller/goods_status', ['id' => $gOff, 'status' => 1]); $x = g($gOff); ok('下架且已过期的商品重新上架：按原时长顺延截拍', ($j['code'] ?? 0) == 1 && $x['status'] == 1 && $x['end_time'] > $T + 3000 && $x['start_time'] >= $T, json_encode([$j, $x['end_time'] - $T], JSON_UNESCAPED_UNICODE));
    echo "== 后台编辑已有出价的商品 ==\n";
    $base = ['id' => 0, 'title' => 'QA拍卖中', 'category_id' => $cat, 'content' => 'x', 'start_price' => 100, 'raise_price' => 10, 'reserve_price' => 500, 'deposit' => 0, 'reference_price' => 0, 'delay_seconds' => 0, 'cover' => '/a.jpg', 'images' => '/a.jpg'];
    [, , $j] = req($sa, 'GET', "/admin1314/goods/edit?id=$gLive"); $taskId = 0;
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', ['id' => 0] + $base); // 需要任务 id，用 edit 需要 auto_bid? 不是；直接用 goods edit
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', array_merge($base, ['id' => 0]));
    // 上面两次是无效 id，仅确认不会误改；下面用真实 id
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', array_merge($base, ['id' => $gLive, 'end_time' => date('Y-m-d\TH:i', $T + 3600)])); $x = g($gLive); ok('已有出价：截拍时间可以提前（只要晚于当前时间 1 分钟）', ($j['code'] ?? 0) == 1 && abs($x['end_time'] - ($T + 3600)) < 60, json_encode([$j, $x['end_time'] - $T], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', array_merge($base, ['id' => $gLive, 'end_time' => date('Y-m-d\TH:i', $T + 30)])); ok('已有出价：截拍时间提前到 1 分钟内仍被拒', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', '1分钟') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/edit', array_merge($base, ['id' => $gLive, 'end_time' => date('Y-m-d\TH:i', $T + 10800)])); $x = g($gLive); ok('已有出价：延后截拍可以，保留价保持不变', ($j['code'] ?? 0) == 1 && (float)$x['reserve_price'] == 0 && $x['end_time'] >= $T + 10700, json_encode([$j, $x['reserve_price'], $x['end_time'] - $T], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/setTime', ['id' => $gLive, 'start_time' => date('Y-m-d\TH:i', $T - 60), 'end_time' => date('Y-m-d\TH:i', $T + 3600)]); $x = g($gLive); ok('已有出价：改时间接口可提前截拍，开拍时间保持不变', ($j['code'] ?? 0) == 1 && abs($x['end_time'] - ($T + 3600)) < 60 && (int)$x['start_time'] == $T - 7200, json_encode([$j, $x['start_time'] - $T, $x['end_time'] - $T], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/setTime', ['id' => $gLive, 'start_time' => date('Y-m-d\TH:i', $T - 60), 'end_time' => date('Y-m-d\TH:i', $T + 30)]); ok('已有出价：改时间接口提前到 1 分钟内被拒', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 出价记录接口 ==\n";
    [$c, $b, $j] = req('', 'GET', "/goods/bids?goods_id=$gLive"); ok('未登录取出价记录：不含手机号字段', $c == 200 && strpos($b, '19999990312') === false && !isset($j['data'][0]['mobile']), mb_substr($b, 0, 200));
    $v0 = (int)g($gLive)['view_count'];
    [$c, $b] = req('', 'GET', "/goods/detail?id=$gLive", null, ['X-Requested-With: none']); ok('商品详情页不含出价人手机号', $c == 200 && strpos($b, '19999990312') === false, "HTTP $c");
    echo "== 浏览量防刷 ==\n";
    req('', 'GET', "/goods/detail?id=$gLive"); req('', 'GET', "/goods/detail?id=$gLive"); req('', 'GET', "/goods/detail?id=$gLive");
    ok('同一访客连续访问 4 次浏览量只加 1', (int)g($gLive)['view_count'] == $v0 + 1, 'view_count=' . g($gLive)['view_count'] . " v0=$v0");
    echo "== 注册 ==\n";
    [, , $j] = req('', 'POST', '/user/doRegister', ['mobile' => '13999990399', 'password' => '123456', 'password2' => '123456', 'invite_code' => '', 'captcha' => '']); ok('不带验证码注册被拒', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', '验证码') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req('', 'GET', '/user/register', null, ['X-Requested-With: none']); ok('注册页含验证码输入框', $c == 200 && strpos($b, 'id="captcha"') !== false && strpos($b, '/user/captcha') !== false, "HTTP $c");
    echo "== 站内聊天 ==\n";
    [, , $j] = req($sb, 'POST', '/chat/send', ['goods_id' => $gLive, 'seller_id' => $seller2, 'content' => 'hi']); ok('向非该商品卖家发消息被拒', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sb, 'POST', '/chat/send', ['goods_id' => 999999999, 'seller_id' => $seller, 'content' => 'hi']); ok('不存在的商品被拒', ($j['code'] ?? 1) != 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sb, 'POST', '/chat/send', ['goods_id' => $gLive, 'seller_id' => $seller, 'content' => 'QA咨询']); ok('向商品卖家发消息成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/chat/send', ['goods_id' => $gLive, 'seller_id' => $buyer, 'content' => 'QA回复']); ok('卖家回复已咨询的买家成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    echo "== 上传 ==\n";
    $tmp = sys_get_temp_dir() . '/qa_fake_' . $T . '.png'; file_put_contents($tmp, '<?php echo 1;');
    $ch = curl_init('http://localhost/upload/image'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => ['file' => new CURLFile($tmp, 'image/png', 'a.png')], CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'], CURLOPT_COOKIE => 'PHPSESSID=' . $sb]); $b = curl_exec($ch); $j = json_decode($b, true); @unlink($tmp);
    ok('伪装成 png 的脚本文件上传被拒', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', '图片') !== false, $b);
    $tmp2 = sys_get_temp_dir() . '/qa_real_' . $T . '.png'; $im = imagecreatetruecolor(10, 10); imagepng($im, $tmp2); imagedestroy($im);
    $ch = curl_init('http://localhost/upload/image'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => ['file' => new CURLFile($tmp2, 'image/png', 'a.png')], CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'], CURLOPT_COOKIE => 'PHPSESSID=' . $sb]); $b = curl_exec($ch); $j = json_decode($b, true); @unlink($tmp2);
    ok('真实图片上传成功，文件名不可预测', ($j['code'] ?? 0) == 1 && preg_match('#/uploads/\d{8}/\d{6}_[0-9a-f]{10}\.png$#', $j['url'] ?? ''), $b);
    if (!empty($j['url'])) @unlink($root . '/public' . $j['url']);
    echo "== 跨站请求 ==\n";
    [$c, $b, $j] = req($sb, 'POST', '/chat/send', ['goods_id' => $gLive, 'seller_id' => $seller, 'content' => 'x'], ['Origin: http://evil.example']); ok('带外站 Origin 的 POST 被拒（403）', $c == 403, "HTTP $c " . mb_substr($b, 0, 80));
    [$c, $b, $j] = req($sb, 'POST', '/chat/send', ['goods_id' => $gLive, 'seller_id' => $seller, 'content' => 'x'], ['Referer: http://evil.example/page']); ok('带外站 Referer 的 POST 被拒（403）', $c == 403, "HTTP $c");
    sleep(3);
    [$c, $b, $j] = req($sb, 'POST', '/chat/send', ['goods_id' => $gLive, 'seller_id' => $seller, 'content' => 'QA同站'], ['Origin: http://localhost']); ok('同站 Origin 的 POST 正常', $c == 200 && ($j['code'] ?? 0) == 1, "HTTP $c " . mb_substr($b, 0, 80));
    echo "== 后台登录验证码一次性 ==\n";
    $sid = md5('cap' . $T); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin_captcha' => 'AB12']));
    [, , $j] = req($sid, 'POST', '/admin1314/login/doLogin', ['username' => 'nobody_' . $T, 'password' => 'x', 'captcha' => 'ab12']); ok('验证码正确但密码错误：提示密码错误', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', '密码') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, 'POST', '/admin1314/login/doLogin', ['username' => 'nobody_' . $T, 'password' => 'x', 'captcha' => 'ab12']); ok('同一验证码再次使用被拒', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', '验证码') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    @unlink("$root/runtime/session/sess_$sid");
} finally {
    $ids = implode(',', $goods);
    $pdo->exec("delete from message where goods_id in ($ids)"); $pdo->exec("delete from bid_record where goods_id in ($ids)"); $pdo->exec("delete from goods where id in ($ids)"); $pdo->exec("delete from browse_history where goods_id in ($ids)");
    $pdo->exec("delete from admin_log where action like '%QA拍卖中%' or action like '%QA已下架过期%'"); $pdo->exec("delete from user where id in ($seller,$seller2,$buyer)");
    @unlink("$root/runtime/session/sess_$ss"); @unlink("$root/runtime/session/sess_$sb"); @unlink("$root/runtime/session/sess_$sa"); echo "[cleanup] done\n";
}

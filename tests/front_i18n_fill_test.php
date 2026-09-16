<?php
/**
 * 前台多语言补齐：本轮新包 lang() 的文案与新增词条在英文 / 繁体环境下生效
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,1000,$seller,$seller,0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id) { global $root, $pdo; $sid = md5('fi' . $id . microtime(true)); $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u])); return $sid; }
function req($sid, $m, $p, $d = null) {
    $ch = curl_init('http://localhost' . $p);
    $h = $m === 'POST' ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost', 'Referer: http://localhost/'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}

$S = mk('19999990550', 'QA多语言卖家', 1); $B = mk('19999990551', 'QA多语言买家');
$pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,refuse_reason,create_time,update_time) values('QAI18N Rejected','','','',1,$S,100,10,0,0,$T,$T+86400,5,0,0,'',$T,$T)");
$gRej = (int)$pdo->lastInsertId();
$pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QAI18N0001',$gRej,'QAI18N Rejected','',$S,$B,100,10,10,90,1,0,1,$T,3,'QA','13900000000','QA',$T,$T)");
$order = (int)$pdo->lastInsertId();
$ss = sess($S); $sb = sess($B);

try {
    echo "== 英文页面 ==\n";
    [$c, $h] = req($ss, 'GET', '/seller/goods_list?lang=en-us');
    ok('卖家商品列表：审核未通过 → Review failed: No reason provided', $c == 200 && strpos($h, 'Review failed: No reason provided') !== false && strpos($h, '审核未通过') === false, "HTTP $c");
    [$c, $h] = req($ss, 'GET', '/seller/orders?lang=en-us');
    ok('卖家订单：买家： → Buyer: ', $c == 200 && strpos($h, 'Buyer: ') !== false && strpos($h, '买家：') === false, "HTTP $c");
    [$c, $h] = req('', 'GET', '/search?keyword=QAI18Nzzz&lang=en-us');
    ok('搜索无结果：No results for "…"', $c == 200 && strpos($h, 'No results for') !== false && strpos($h, '没有找到') === false, "HTTP $c");
    [$c, $h] = req($sb, 'GET', '/user/center?lang=en-us');
    ok('个人中心：账号行 / 实名标签翻译，无中文等级名残留', $c == 200 && strpos($h, 'Account: ') !== false && preg_match('/Verified|Not verified/', $h) && strpos($h, '学徒') === false && strpos($h, '白丁') === false, "HTTP $c");
    [$c, $h] = req('', 'GET', '/user/register?lang=en-us');
    ok('注册页：邀请码标签翻译（脚本里的 toast 字面量由 JS 字典运行时翻译）', $c == 200 && strpos($h, 'for="invite">Invite code<') !== false && !preg_match('/<label[^>]*>[^<]*邀请码/u', $h), "HTTP $c");
    [$c, $h] = req($sb, 'GET', '/user/footprints?lang=en-us');
    ok('足迹页：浏览于 → Viewed on（无记录时页面仍 200）', $c == 200 && strpos($h, '浏览于') === false, "HTTP $c");

    echo "== 英文接口返回 ==\n";
    [, , $j] = req($sb, 'POST', '/order/afterSaleApply?lang=en-us', ['id' => $order, 'reason' => 'abc']);
    ok('售后理由过短 → Please enter a reason (at least 5 characters)', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', 'Please enter a reason') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sb, 'POST', '/user/password?lang=en-us', ['old_password' => 'wrong', 'new_password' => '123456', 'new_password2' => '123456']);
    ok('原密码错误 → Current password is incorrect', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', 'Current password is incorrect') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/seller/quickApply?lang=en-us', []);
    ok('已是卖家再申请 → You are already a seller', ($j['code'] ?? 1) != 1 && strpos($j['msg'] ?? '', 'already a seller') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 繁体 ==\n";
    [$c, $h] = req($ss, 'GET', '/seller/goods_list?lang=zh-tw');
    ok('繁体：審核未通過：未填寫原因', strpos($h, '審核未通過：未填寫原因') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from `order` where id=$order");
    $pdo->exec("delete from goods where id=$gRej");
    $pdo->exec("delete from user where id in ($S,$B)");
    foreach ([$ss, $sb] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

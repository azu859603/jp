<?php
/**
 * 信誉分不再自动扣：催发货（php think order:remind）只发站内信，不动 credit_score
 *  - 付款超期未发货 → 卖家收到「发货提醒」，信誉分原样不变，站内信里没有「已扣 1 分」
 *  - 24 小时内不重复提醒
 *  - 信誉分只能在主后台 / 代理后台「编辑店铺资料」手动改（范围 0~999）
 * 隔离：催发货脚本会扫全库待发货订单，测试期间把「付款后 N 天」临时设成 3650 天，
 *       只有本测试付款时间在 4000 天前的订单会命中，真实订单不会被提醒。
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function req($sid, $m, $p, $d = null) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost', 'Referer: http://localhost/'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); curl_close($ch);
    return json_decode((string)$b, true);
}
function run() { global $php, $root; return (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think order:remind 2>&1'); }
function credit($id) { global $pdo; return (int)$pdo->query("select credit_score from user where id=$id")->fetchColumn(); }
function remindCount($id) { global $pdo; return (int)$pdo->query("select count(*) from sys_message where user_id=$id and title='发货提醒'")->fetchColumn(); }
function setv($n, $v) { global $pdo, $T; $pdo->exec("insert into setting(name,value,create_time) values('$n','$v',$T) on duplicate key update value='$v'"); }

$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,auth_status,credit_score,create_time,update_time,reg_time) values('19999995001','x','QA信誉卖家','995001',0,1,0,1,1,0,2,100,$T,$T,$T)");
$S = (int)$pdo->lastInsertId();
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,auth_status,create_time,update_time,reg_time) values('19999995002','x','QA信誉买家','995002',0,1,0,0,0,0,2,$T,$T,$T)");
$B = (int)$pdo->lastInsertId();
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($S,1,'QCR信誉商品','','[]',100,10,0,2," . ($T - 7200) . "," . ($T - 60) . ",1,$T,$T)");
$G = (int)$pdo->lastInsertId();
$paid = $T - 4000 * 86400;
$pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,pay_status,pay_time,order_status,create_time,update_time) values('QCR$T',$G,'QCR信誉商品','',$S,$B,100,1,$paid,1,$paid,$paid)");
$O = (int)$pdo->lastInsertId();

$origDays = $pdo->query("select value from setting where name='order_ship_remind_days'")->fetchColumn();
setv('order_ship_remind_days', '3650');
$asid = md5('qcr' . $T); $a = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($a['password']); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));

try {
    echo "== 催发货不扣分 ==\n";
    ok('卖家初始信誉分 100', credit($S) === 100);
    $o = run();
    ok('脚本发出了发货提醒', remindCount($S) === 1, $o);
    ok('  信誉分原样不变', credit($S) === 100, credit($S));
    $msg = (string)$pdo->query("select content from sys_message where user_id=$S and title='发货提醒' order by id desc limit 1")->fetchColumn();
    ok('  站内信里没有扣分字样', strpos($msg, '信誉分') === false && strpos($msg, '已扣') === false && strpos($msg, '未发货，请尽快处理') !== false, $msg);
    run();
    ok('再跑一次：24 小时内不重复提醒，分数仍是 100', remindCount($S) === 1 && credit($S) === 100);

    echo "== 只能后台手动改 ==\n";
    $j = req($asid, 'POST', '/admin1314/member/updateShop', ['id' => $S, 'seller_intro' => '', 'deposit' => 0, 'shop_score' => 5, 'fans_count' => 0, 'credit_score' => 88]);
    ok('主后台把信誉分改成 88', ($j['code'] ?? 0) == 1 && credit($S) === 88, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('  操作日志记录 100 → 88', (int)$pdo->query("select count(*) from admin_log where action like '%信誉分 100 → 88%'")->fetchColumn() === 1);
    $j = req($asid, 'POST', '/admin1314/member/updateShop', ['id' => $S, 'seller_intro' => '', 'deposit' => 0, 'shop_score' => 5, 'fans_count' => 0, 'credit_score' => 1000]);
    ok('超出 0~999 被拒', ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '0 ~ 999') !== false && credit($S) === 88, json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('代码里不再有自动扣分', strpos((string)file_get_contents("$root/app/index/common.php"), "'credit_score' => \$newCredit") === false);
} finally {
    if ($origDays === false) { $pdo->exec("delete from setting where name='order_ship_remind_days'"); } else { setv('order_ship_remind_days', $origDays); }
    $pdo->exec("delete from sys_message where user_id in ($S,$B)");
    $pdo->exec("delete from admin_log where action like '%19999995001%'");
    $pdo->exec("delete from `order` where id=$O");
    $pdo->exec("delete from goods where id=$G");
    $pdo->exec("delete from user where id in ($S,$B)");
    @unlink("$root/runtime/session/sess_$asid");
    echo "[cleanup] done，催发货天数已还原\n";
}

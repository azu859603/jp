<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,0,0,0,0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function log_($uid, $type, $amount, $remark) { global $pdo, $T; $pdo->exec("insert into balance_log(user_id,type,amount,balance,remark,create_time) values($uid,'$type',$amount,0,'$remark',$T)"); }
function sess($id) { global $root, $pdo, $T; $sid = md5('wt' . $id . $T); $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u])); return $sid; }
function wallet($sid) {
    $ch = curl_init('http://localhost/user/wallet'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]); $b = curl_exec($ch); curl_close($ch);
    $num = function ($re) use ($b) { return preg_match($re, $b, $m) ? (float)str_replace(',', '', $m[1]) : null; };
    return [
        'assets'  => $num('#<div class="wl-total"><small>¥</small>\s*([\d,]+\.\d{2})#u'),
        'income'  => $num('#<div class="v in">¥([\d,]+\.\d{2})</div>#u'),
        'expense' => $num('#<div class="v out">¥([\d,]+\.\d{2})</div>#u'),
    ];
}
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
$users = [];
try {
    // 场景 1：充值 500，提现 200 被拒绝（申请扣 200 + 拒绝退回 200）
    $u1 = mk('19999990340', 'QA钱包1'); $users[] = $u1;
    log_($u1, 'recharge', 500, '充值到账：500元');
    log_($u1, 'withdraw', -200, '提现申请冻结：200元');
    log_($u1, 'refund', 200, '提现拒绝退回：200元');
    $w = wallet(sess($u1));
    ok('提现被拒：总收入 500、总支出 0', $w['income'] == 500 && $w['expense'] == 0, json_encode($w));

    // 场景 2：充值 500，提现 200 已打款（只有申请那条负流水）
    $u2 = mk('19999990341', 'QA钱包2'); $users[] = $u2;
    log_($u2, 'recharge', 500, '充值到账：500元');
    log_($u2, 'withdraw', -200, '提现申请冻结：200元');
    $w = wallet(sess($u2));
    ok('提现成功：总收入 500、总支出 200', $w['income'] == 500 && $w['expense'] == 200, json_encode($w));

    // 场景 3：出价冻结 50 后未中标退回
    $u3 = mk('19999990342', 'QA钱包3'); $users[] = $u3;
    log_($u3, 'recharge', 1000, '充值到账：1000元');
    log_($u3, 'deposit', -50, '拍卖保证金（QA）');
    log_($u3, 'refund', 50, '未拍中，保证金退回（QA）');
    $w = wallet(sess($u3));
    ok('未拍中退保证金：总收入 1000、总支出 0', $w['income'] == 1000 && $w['expense'] == 0, json_encode($w));

    // 场景 4：中标付款（冻结 50 + 付款 120，成交价 170）
    $u4 = mk('19999990343', 'QA钱包4'); $users[] = $u4;
    log_($u4, 'recharge', 1000, '充值到账：1000元');
    log_($u4, 'deposit', -50, '拍卖保证金（QA）');
    log_($u4, 'pay', -120, '拍卖订单支付：AU1');
    $w = wallet(sess($u4));
    ok('中标付款：总支出 = 成交价 170', $w['income'] == 1000 && $w['expense'] == 170, json_encode($w));

    // 场景 5：保证金被没收（冻结 -50 扣余额，没收 -50 只减冻结）
    $u5 = mk('19999990344', 'QA钱包5'); $users[] = $u5;
    log_($u5, 'recharge', 1000, '充值到账：1000元');
    log_($u5, 'deposit', -50, '拍卖保证金（QA）');
    log_($u5, 'forfeit', -50, '订单超时未付款，保证金没收（QA）');
    $w = wallet(sess($u5));
    ok('保证金没收：总支出 50，不重复计', $w['income'] == 1000 && $w['expense'] == 50, json_encode($w));

    // 场景 6：售后退款（冻结 50 + 付款 120，后退回 170）
    $u6 = mk('19999990345', 'QA钱包6'); $users[] = $u6;
    log_($u6, 'recharge', 1000, '充值到账：1000元');
    log_($u6, 'deposit', -50, '拍卖保证金（QA）');
    log_($u6, 'pay', -120, '拍卖订单支付：AU2');
    log_($u6, 'refund', 170, '售后退款：AU2');
    $w = wallet(sess($u6));
    ok('售后全额退款：总收入 1000、总支出 0', $w['income'] == 1000 && $w['expense'] == 0, json_encode($w));

    // 场景 7：卖家收入与售后扣回
    $u7 = mk('19999990346', 'QA钱包7'); $users[] = $u7;
    log_($u7, 'income', 153, '拍卖成交收入：AU3');
    log_($u7, 'refund', -153, '售后扣回成交收入：AU3');
    $w = wallet(sess($u7));
    ok('卖家收入后被扣回：收入 153、支出 153', $w['income'] == 153 && $w['expense'] == 153, json_encode($w));

    // 场景 8：后台调整加减
    $u8 = mk('19999990347', 'QA钱包8'); $users[] = $u8;
    log_($u8, 'recharge', 300, '后台调整');
    log_($u8, 'refund', -100, '后台调整');
    $w = wallet(sess($u8));
    ok('后台加 300 减 100：收入 300、支出 100', $w['income'] == 300 && $w['expense'] == 100, json_encode($w));
} finally {
    if ($users) { $in = implode(',', $users); $pdo->exec("delete from balance_log where user_id in ($in)"); $pdo->exec("delete from user where id in ($in)"); }
    foreach (glob("$root/runtime/session/sess_*") as $f) { $c = @file_get_contents($f); if ($c && strpos($c, 'QA钱包') !== false) @unlink($f); }
    echo "[cleanup] done\n";
}

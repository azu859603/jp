<?php
/**
 * 站内信多语言：系统自动发送的全部模板在英文 / 繁体环境下标题与正文都被翻译
 *  - 列表页与详情页都翻译；中文环境原样
 *  - 动态内容（订单号 / 拍品名 / 金额 / 时间）保留
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function sess($id) {
    global $root, $pdo;
    $sid = md5('mi' . $id . microtime(true));
    $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u]));
    return $sid;
}
function req($sid, $p) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b];
}
function body($html) { // 详情页正文
    return preg_match('/<div class="md-body">(.*?)<\/div>/s', $html, $m) ? html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8') : '';
}
function hasCjk($s) { return preg_match('/\p{Han}/u', $s) === 1; }

$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990520','x','QA站内信','mi0520',0,1,0,1,1,0,0,2,$T,$T,$T)");
$U = (int)$pdo->lastInsertId();
$tpl = [
    'outbid'   => ['竞拍出局通知', '您出价竞拍的「QA Item A」已出局：您的出价 ¥100.00 于 2026-09-17 10:00:00 被 ¥110.00 超过，当前最高价 ¥110.00。如需继续竞拍，请再次出价。'],
    'offline'  => ['拍品下架通知', '您参与竞拍的「QA Item B」已平台下架，本次竞拍取消，已缴纳的保证金已退回您的可用余额。'],
    'income'   => ['成交款到账通知', '订单 AU1（QA Item C）买家已确认收货，成交款 ¥900.00（成交价 ¥1,000.00 − 平台佣金 ¥100.00）已存入您的可用余额。'],
    'cancel_a' => ['订单取消通知', '您的订单 AU2（QA Item D）已由平台取消，货款 ¥500.00 已退回您的可用余额。'],
    'cancel_s' => ['订单取消通知', '订单 AU2（QA Item D）已由平台取消，该订单的成交收入 ¥450.00 已从您的余额扣回，商品已下架。'],
    'cancel_t' => ['订单取消通知', '您的订单 AU3（QA Item E）已取消：超过24小时未付款，系统自动取消。保证金已没收。'],
    'cancel_b' => ['订单取消通知', '您的订单 AU4（QA Item F）已取消：买家主动取消。无保证金。'],
    'unpaid'   => ['买家未付款通知', '商品「QA Item E」的买家未付款，订单 AU3 已取消，商品已回到可重新上架状态。保证金已赔付卖家。'],
    'confirm'  => ['自动确认收货通知', '您的订单 AU5（QA Item G）发货后超过3天未确认收货，系统自动确认，交易已完成。如商品有问题，可在订单中申请售后。'],
    'remind'   => ['发货提醒', '订单 AU6（QA Item H）买家已于 09-10 12:00 付款，至今 3 天未发货，请尽快处理。因未按时发货，您的信誉分已扣 1 分，当前 99 分。'],
];
$ids = [];
foreach ($tpl as $k => [$title, $content]) {
    $pdo->prepare("insert into sys_message(user_id,admin_id,title,content,is_read,create_time) values(?,0,?,?,0,?)")->execute([$U, $title, $content, $T]);
    $ids[$k] = (int)$pdo->lastInsertId();
}
$sid = sess($U);

try {
    echo "== 英文 ==\n";
    [$c, $html] = req($sid, '/user/messages?lang=en-us');
    ok('列表页 200，标题全部翻译（不含中文标题）', $c == 200 && strpos($html, 'Outbid Notice') !== false && strpos($html, 'Payment Received') !== false && strpos($html, 'Item Removed') !== false && strpos($html, 'Order Cancelled') !== false && strpos($html, 'Shipping Reminder') !== false && strpos($html, '成交款到账通知') === false && strpos($html, '拍品下架通知') === false, "HTTP $c");
    preg_match_all('/<div class="m-summary">(.*?)<\/div>/s', $html, $mm);
    $cjk = array_filter($mm[1], 'hasCjk');
    ok('列表摘要 ' . count($mm[1]) . ' 条均无中文残留', count($mm[1]) === count($tpl) && !$cjk, json_encode(array_values($cjk), JSON_UNESCAPED_UNICODE));
    $expect = [
        'outbid'   => ['You bid on "QA Item A" is outbid', '¥110.00', 'please bid again'],
        'offline'  => ['The item "QA Item B" you bid on has been removed by the platform', 'deposit has been refunded'],
        'income'   => ['Order AU1 (QA Item C) the buyer has confirmed receipt', '¥900.00', 'final price ¥1,000.00', 'platform commission ¥100.00', 'credited to your available balance'],
        'cancel_a' => ['Your order AU2 (QA Item D) has been cancelled by the platform', '¥500.00', 'refunded to your available balance'],
        'cancel_s' => ['The seller income of ¥450.00 has been reclaimed', 'taken off sale'],
        'cancel_t' => ['unpaid for more than 24 hours, cancelled automatically', 'The deposit has been forfeited'],
        'cancel_b' => ['cancelled by the buyer', 'No deposit was involved'],
        'unpaid'   => ['The buyer of "QA Item E" did not pay', 'Order AU3 has been cancelled', 'The deposit has been paid to the seller'],
        'confirm'  => ['was not confirmed within 3 days after shipping', 'apply for after-sale service'],
        'remind'   => ['was paid by the buyer on 09-10 12:00', 'not been shipped for 3 days', 'current score: 99.'],
    ];
    foreach ($expect as $k => $frags) {
        [$c, $html] = req($sid, '/user/message_detail?id=' . $ids[$k] . '&lang=en-us');
        $b = body($html);
        $miss = array_filter($frags, function ($f) use ($b) { return strpos($b, $f) === false; });
        ok("详情 {$k}：英文完整、无中文残留", $c == 200 && !$miss && !hasCjk($b) && strpos($html, 'Platform notice') !== false, json_encode(['miss' => array_values($miss), 'body' => $b], JSON_UNESCAPED_UNICODE));
    }

    echo "== 繁体 ==\n";
    [$c, $html] = req($sid, '/user/message_detail?id=' . $ids['income'] . '&lang=zh-tw');
    $b = body($html);
    ok('繁体：标题「成交款到賬通知」，正文含「買家已確認收貨」', strpos($html, '成交款到賬通知') !== false && strpos($b, '買家已確認收貨') !== false && strpos($b, '¥900.00') !== false, $b);

    echo "== 简体 ==\n";
    [$c, $html] = req($sid, '/user/message_detail?id=' . $ids['remind'] . '&lang=zh-cn');
    ok('简体：正文原样', strpos(body($html), $tpl['remind'][1]) !== false, body($html));
} finally {
    $pdo->exec("delete from sys_message where user_id=$U");
    $pdo->exec("delete from user where id=$U");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

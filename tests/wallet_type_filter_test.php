<?php
/**
 * 我的钱包：资产明细按类型筛选
 *  - 页面头部是类型下拉（全部类型 + 8 种类型），不再显示「按时间倒序」
 *  - ?type=xxx 只返回该类型流水（首屏与 AJAX 翻页一致）
 *  - 未知类型当作全部；某类型无记录时返回空列表
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function sess($id) {
    global $root, $pdo;
    $sid = md5('wt' . $id . microtime(true));
    $u = $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize(['user' => $u]));
    return $sid;
}
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function cnt($html, $type) { return substr_count($html, 'wl-ico t-' . $type . '"'); }

$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990510','x','QA钱包筛选','wt0510',0,1,1000,0,0,0,0,0,2,$T,$T,$T)");
$U = (int)$pdo->lastInsertId();
// 12 条充值 + 3 条支付 + 2 条奖励：充值跨两页，支付/奖励只有一页
$rows = [];
for ($i = 0; $i < 12; $i++) $rows[] = "($U,'recharge',100,0,'QA充值',$T)";
for ($i = 0; $i < 3; $i++)  $rows[] = "($U,'pay',-50,0,'QA支付',$T)";
for ($i = 0; $i < 2; $i++)  $rows[] = "($U,'reward',10,0,'QA奖励',$T)";
$pdo->exec("insert into balance_log(user_id,type,amount,balance,remark,create_time) values " . implode(',', $rows));
$sid = sess($U);

try {
    echo "== 页面 ==\n";
    [$c, $html] = req($sid, '/user/wallet', false);
    ok('钱包页 200 且带筛选按钮和底部选择框', $c == 200 && strpos($html, 'id="wlTypeBtn"') !== false && strpos($html, 'id="wlWheel"') !== false && strpos($html, 'data-v="">全部类型<') !== false && strpos($html, '<span id="wlTypeTxt">全部类型</span>') !== false, "HTTP $c");
    ok('选择框 9 个选项（全部 + 8 种）', substr_count($html, 'class="bl-opt" data-v="') === 9, (string)substr_count($html, 'class="bl-opt" data-v="'));
    ok('不再显示「按时间倒序」', strpos($html, '按时间倒序') === false);
    ok('全部类型首屏 10 条（混合类型）', cnt($html, 'recharge') + cnt($html, 'pay') + cnt($html, 'reward') === 10 && strpos($html, 'data-has-more="1"') !== false);

    [$c, $html] = req($sid, '/user/wallet?type=pay', false);
    ok('首屏 ?type=pay 只显示支付 3 条，按钮高亮显示「支付」', cnt($html, 'pay') === 3 && cnt($html, 'recharge') === 0 && strpos($html, 'class="wl-filter on"') !== false && strpos($html, '<span id="wlTypeTxt">支付</span>') !== false && strpos($html, 'data-has-more="0"') !== false);

    echo "== AJAX ==\n";
    [, , $j] = req($sid, '/user/wallet?page=1&type=recharge');
    ok('type=recharge 第 1 页 10 条充值，has_more=1', ($j['code'] ?? 0) == 1 && cnt($j['html'], 'recharge') === 10 && cnt($j['html'], 'pay') === 0 && !empty($j['has_more']) && ($j['type'] ?? '') === 'recharge', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/wallet?page=2&type=recharge');
    ok('type=recharge 第 2 页 2 条，has_more=0', cnt($j['html'] ?? '', 'recharge') === 2 && empty($j['has_more']), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/wallet?page=1&type=reward');
    ok('type=reward 只有 2 条奖励', cnt($j['html'] ?? '', 'reward') === 2 && cnt($j['html'] ?? '', 'recharge') === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/wallet?page=1&type=withdraw');
    ok('type=withdraw 无记录时返回空列表', ($j['code'] ?? 0) == 1 && trim($j['html'] ?? 'x') === '' && empty($j['has_more']), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/wallet?page=1&type=hack%27');
    ok('未知类型当作全部（10 条，type 回传空）', ($j['code'] ?? 0) == 1 && substr_count($j['html'] ?? '', 'class="wl-item"') === 10 && ($j['type'] ?? 'x') === '', json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("delete from balance_log where user_id=$U");
    $pdo->exec("delete from user where id=$U");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

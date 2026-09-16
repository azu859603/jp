<?php
/**
 * 余额明细：类型筛选（底部滚动选择框）+ AJAX 翻页
 *  - 页面含筛选按钮、底部选择框（全部类型 + 8 种类型）
 *  - ?type=xxx 只返回该类型流水（首屏与 AJAX 翻页一致），按钮显示当前类型
 *  - 未知类型当作全部；某类型无记录时返回空列表
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function sess($id) {
    global $root, $pdo;
    $sid = md5('bl' . $id . microtime(true));
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
function cnt($html, $type) { return substr_count($html, 'class="log-item" data-type="' . $type . '"'); }
function items($html) { return substr_count($html, 'class="log-item" data-type='); }

$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,freeze_balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990511','x','QA明细筛选','bl0511',0,1,1000,0,0,0,0,0,2,$T,$T,$T)");
$U = (int)$pdo->lastInsertId();
// 17 条充值（跨两页，每页 15）+ 3 条支付 + 2 条奖励
$rows = [];
for ($i = 0; $i < 17; $i++) $rows[] = "($U,'recharge',100,0,'QA充值',$T)";
for ($i = 0; $i < 3; $i++)  $rows[] = "($U,'pay',-50,0,'QA支付',$T)";
for ($i = 0; $i < 2; $i++)  $rows[] = "($U,'reward',10,0,'QA奖励',$T)";
$pdo->exec("insert into balance_log(user_id,type,amount,balance,remark,create_time) values " . implode(',', $rows));
$sid = sess($U);

try {
    echo "== 页面 ==\n";
    [$c, $html] = req($sid, '/user/balance_log', false);
    ok('余额明细 200，带筛选按钮和底部选择框', $c == 200 && strpos($html, 'id="blTypeBtn"') !== false && strpos($html, 'id="blWheel"') !== false && strpos($html, 'id="blOk"') !== false, "HTTP $c");
    ok('选择框 9 个选项（全部 + 8 种）', substr_count($html, 'class="bl-opt" data-v="') === 9 && strpos($html, 'data-v="">全部类型<') !== false, (string)substr_count($html, 'class="bl-opt" data-v="'));
    ok('默认按钮文案「全部类型」，首屏 15 条，has_more=1', strpos($html, '<span id="blTypeTxt">全部类型</span>') !== false && items($html) === 15 && strpos($html, 'data-has-more="1"') !== false, (string)items($html));

    [$c, $html] = req($sid, '/user/balance_log?type=pay', false);
    ok('首屏 ?type=pay 只 3 条支付，按钮高亮显示「支付」', cnt($html, 'pay') === 3 && cnt($html, 'recharge') === 0 && strpos($html, 'class="w-filter on"') !== false && strpos($html, '<span id="blTypeTxt">支付</span>') !== false && strpos($html, 'data-has-more="0"') !== false);

    echo "== AJAX ==\n";
    [, , $j] = req($sid, '/user/balance_log?page=1&type=recharge');
    ok('type=recharge 第 1 页 15 条充值，has_more=1', ($j['code'] ?? 0) == 1 && cnt($j['html'], 'recharge') === 15 && cnt($j['html'], 'pay') === 0 && !empty($j['has_more']) && ($j['type'] ?? '') === 'recharge', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/balance_log?page=2&type=recharge');
    ok('type=recharge 第 2 页 2 条，has_more=0', cnt($j['html'] ?? '', 'recharge') === 2 && empty($j['has_more']), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/balance_log?page=1&type=reward');
    ok('type=reward 只有 2 条奖励', cnt($j['html'] ?? '', 'reward') === 2 && items($j['html'] ?? '') === 2, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/balance_log?page=1&type=withdraw');
    ok('type=withdraw 无记录时返回空列表', ($j['code'] ?? 0) == 1 && trim($j['html'] ?? 'x') === '' && empty($j['has_more']), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sid, '/user/balance_log?page=2&type=hack%27');
    ok('未知类型当作全部：第 2 页 7 条，type 回传空', ($j['code'] ?? 0) == 1 && items($j['html'] ?? '') === 7 && ($j['type'] ?? 'x') === '' && empty($j['has_more']), json_encode($j, JSON_UNESCAPED_UNICODE));
} finally {
    $pdo->exec("delete from balance_log where user_id=$U");
    $pdo->exec("delete from user where id=$U");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

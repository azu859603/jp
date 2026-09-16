<?php
/**
 * 提现审核：虚拟会员的提现在主后台 / 代理后台列表里手机号下方标注「（虚拟会员）」，由审核人员自行判断
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid, $virtual) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,100,0,0,0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function req($sid, $p) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); $b = curl_exec($ch); curl_close($ch); return json_decode($b, true); }
function page($sid, $p) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); $b = curl_exec($ch); curl_close($ch); return (string)$b; }
$ag = mk('19999990450', 'QA提现代理', 0, 0); $pdo->exec("update user set is_agent=1 where id=$ag");
$v  = mk('12999990451', 'QA提现虚拟', $ag, 1);
$u  = mk('19999990452', 'QA提现真人', $ag, 0);
$cols = $pdo->query("show columns from withdraw")->fetchAll(PDO::FETCH_COLUMN);
$ids = [];
foreach ([$v, $u] as $uid) {
    $pdo->exec("insert into withdraw(user_id,amount,fee,account_type,account,account_name,status,create_time) values($uid,50,0,1,'qa@x.com','QA',0,$T)");
    $ids[] = (int)$pdo->lastInsertId();
}
$asid = md5('wa' . $T); $a = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($a['password']); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('wg' . $T); $g = $pdo->query("select * from user where id=$ag")->fetch(PDO::FETCH_ASSOC); unset($g['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $g]));
try {
    foreach ([['主后台', $asid, '/admin1314/withdraw/index'], ['代理后台', $gsid, '/agent/withdraw/index']] as [$tag, $sid, $url]) {
        $j = req($sid, $url . '?page=1&limit=50&keyword=QA提现');
        $rows = []; foreach (($j['data'] ?? []) as $x) { $rows[(int)$x['user_id']] = $x; }
        ok("$tag 列表返回 is_virtual：虚拟=1、真人=0", isset($rows[$v], $rows[$u]) && (int)$rows[$v]['is_virtual'] === 1 && (int)$rows[$u]['is_virtual'] === 0, json_encode(array_map(function ($x) { return [$x['user_id'], $x['is_virtual'] ?? null]; }, $rows)));
        $html = page($sid, $url);
        ok("$tag 页面在手机号下方渲染「（虚拟会员）」标注", strpos($html, "w.is_virtual == 1 ? '<br><span class=\"tag tag-orange\"") !== false && strpos($html, '（虚拟会员）') !== false, '');
    }
} finally {
    $pdo->exec("delete from withdraw where id in (" . implode(',', $ids) . ")");
    $pdo->exec("delete from user where id in ($ag,$v,$u)");
    @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid");
    echo "[cleanup] done\n";
}

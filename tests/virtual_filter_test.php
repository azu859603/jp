<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $pid = 0, $agent = 0, $virtual = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,0,0,$agent,$virtual,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$agentId = mk('19999990290', 'QA代理VF', 0, 1); $real = mk('19999990291', 'QA筛真实', $agentId); $virt = mk('12999990292', 'QA筛虚拟', $agentId, 0, 1);
$asid = md5('vf' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('vg' . $T); $gu = $pdo->query("select * from user where id=$agentId")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
function req($sid, $p, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function ids($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
try {
    foreach (['主后台' => ['/admin1314', $asid], '代理端' => ['/agent', $gsid]] as $label => [$pre, $sid]) {
        [$c, $b] = req($sid, "$pre/member/index", false); ok("$label 页面含真实 / 虚拟筛选并参与查询与重置", $c == 200 && strpos($b, 'id="is_virtual"') !== false && strpos($b, '真实会员') !== false && strpos($b, "'&is_virtual='") !== false && strpos($b, "getElementById('is_virtual').value = ''") !== false && strpos($b, 'margin-top:3px;">（虚拟会员）</div>') !== false, "HTTP $c");
        [, , $j] = req($sid, "$pre/member/index?page=1&limit=50&keyword=QA筛&is_seller=&status=&is_agent=&is_virtual=0"); ok("  $label 筛真实会员只返回真实", ids($j) == [$real], json_encode($j, JSON_UNESCAPED_UNICODE));
        [, , $j] = req($sid, "$pre/member/index?page=1&limit=50&keyword=QA筛&is_seller=&status=&is_agent=&is_virtual=1"); ok("  $label 筛虚拟会员只返回虚拟", ids($j) == [$virt], json_encode($j, JSON_UNESCAPED_UNICODE));
        [, , $j] = req($sid, "$pre/member/index?page=1&limit=50&keyword=QA筛&is_seller=&status=&is_agent=&is_virtual="); $x = ids($j); sort($x); ok("  $label 不筛时两个都返回", $x == [$real, $virt], json_encode($x));
        [, , $j] = req($sid, "$pre/member/index?page=1&limit=50&keyword=QA筛&is_seller=1&status=&is_agent=&is_virtual=1"); ok("  $label 与卖家筛选叠加为空", ids($j) == [], json_encode($j, JSON_UNESCAPED_UNICODE));
    }
} finally {
    $pdo->exec("delete from user where id in ($agentId,$real,$virt)"); @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid"); echo "[cleanup] done\n";
}

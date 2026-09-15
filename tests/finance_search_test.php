<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,1000,0,0,$agent,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$agentId = mk('19999990200', 'QA代理搜', 0, 1); $m1 = mk('19999990201', 'QA甲搜索', $agentId); $m2 = mk('19999990202', 'QA乙搜索', $agentId); $out = mk('19999990203', 'QA丙外部', 0);
foreach ([$m1, $m2, $out] as $u) { $pdo->exec("insert into recharge(user_id,amount,pay_type,status,create_time,update_time) values($u,100,0,0,$T,$T)"); $pdo->exec("insert into withdraw(user_id,amount,fee,account_type,account,account_name,status,create_time,update_time) values($u,50,0,1,'','',0,$T,$T)"); }
$asid = md5('fs' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('fg' . $T); $gu = $pdo->query("select * from user where id=$agentId")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
function req($sid, $p, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function uids($j) { return array_map(function ($x) { return (int)$x['user_id']; }, $j['data'] ?? []); }
try {
    foreach (['admin' => ['/admin1314', $asid], 'agent' => ['/agent', $gsid]] as $scope => [$pre, $sid]) {
        foreach (['recharge', 'withdraw'] as $mod) {
            $label = ($scope === 'admin' ? '主后台' : '代理端') . ($mod === 'recharge' ? '充值审核' : '提现审核');
            [$c, $b] = req($sid, "$pre/$mod/index", false); ok("$label 页面含搜索框与查询/重置按钮", $c == 200 && strpos($b, 'id="keyword"') !== false && strpos($b, 'resetSearch()') !== false && strpos($b, "&keyword=") !== false, "HTTP $c");
            [, , $j] = req($sid, "$pre/$mod/index?page=1&limit=15&status=0&keyword=" . urlencode('19999990201')); ok("  按手机号搜到甲", uids($j) == [$m1], json_encode($j, JSON_UNESCAPED_UNICODE));
            [, , $j] = req($sid, "$pre/$mod/index?page=1&limit=15&status=&keyword=" . urlencode('乙搜索')); ok("  按昵称搜到乙", uids($j) == [$m2], json_encode($j, JSON_UNESCAPED_UNICODE));
            [, , $j] = req($sid, "$pre/$mod/index?page=1&limit=15&status=&keyword=$m1"); ok("  按会员 ID 搜到甲", uids($j) == [$m1], json_encode($j, JSON_UNESCAPED_UNICODE));
            [, , $j] = req($sid, "$pre/$mod/index?page=1&limit=15&status=&keyword=" . urlencode('QA')); $u = uids($j); sort($u);
            if ($scope === 'admin') { ok("  模糊搜索 QA 命中 3 人", $u == [$m1, $m2, $out], json_encode($u)); } else { ok("  模糊搜索 QA 只命中团队 2 人", $u == [$m1, $m2], json_encode($u)); }
            [, , $j] = req($sid, "$pre/$mod/index?page=1&limit=15&status=&keyword=" . urlencode('19999990203')); ok($scope === 'admin' ? "  主后台能搜到外部会员丙" : "  代理端搜不到外部会员丙", $scope === 'admin' ? uids($j) == [$out] : uids($j) == [], json_encode($j, JSON_UNESCAPED_UNICODE));
            [, , $j] = req($sid, "$pre/$mod/index?page=1&limit=15&status=2&keyword=" . urlencode('19999990201')); ok("  关键字与状态同时过滤（已拒绝为空）", ($j['count'] ?? -1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
        }
    }
} finally {
    $pdo->exec("delete from recharge where user_id in ($m1,$m2,$out)"); $pdo->exec("delete from withdraw where user_id in ($m1,$m2,$out)");
    $pdo->exec("delete from user where id in ($agentId,$m1,$m2,$out)"); @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid"); echo "[cleanup] done\n";
}

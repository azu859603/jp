<?php
/**
 * 主后台 / 代理后台「调整余额」防重复提交
 *  - 前端：确定按钮提交中被锁住（disabled + 处理中…），成功关窗、失败解锁；layout 的 ajaxPost 多了 done 回调
 *  - 服务端兜底：同一操作者对同一会员、同样金额 + 备注，5 秒内第二次被拒，余额只变一次
 *  - 金额或备注不同、换一个操作者、或过了 5 秒 → 正常放行
 *  - 被校验拒绝的请求（金额 0、超上限）不占用防重名额
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h  = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost', 'Referer: http://localhost/'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, (string)$b, json_decode((string)$b, true)];
}
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,100,0,0,$agent,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function bal($id) { global $pdo; return (float)$pdo->query("select balance from user where id=$id")->fetchColumn(); }
function logs($id) { global $pdo; return (int)$pdo->query("select count(*) from balance_log where user_id=$id")->fetchColumn(); }

$AG = mk('19999994901', 'QA防重代理', 0, 1);
$U  = mk('19999994902', 'QA防重会员', $AG);
$uids = "$AG,$U";
$sa = md5('qbo-a' . $T); $a = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($a['password']); file_put_contents("$root/runtime/session/sess_$sa", serialize(['admin' => $a]));
$sg = md5('qbo-g' . $T); $g = $pdo->query("select * from user where id=$AG")->fetch(PDO::FETCH_ASSOC); unset($g['password']); file_put_contents("$root/runtime/session/sess_$sg", serialize(['user' => $g]));
// 代理端调整余额受主后台开关控制，测试期间确保开启，结束还原
$origSw = $pdo->query("select value from setting where name='agent_balance_adjust'")->fetchColumn();
$pdo->exec("delete from setting where name='agent_balance_adjust'");

try {
    foreach ([['主后台', $sa, '/admin1314/member/adjustBalance'], ['代理后台', $sg, '/agent/member/adjustBalance']] as [$name, $sid, $url]) {
        echo "== $name ==\n";
        $b0 = bal($U); $l0 = logs($U);
        [, , $j1] = req($sid, 'POST', $url, ['id' => $U, 'amount' => 10, 'remark' => 'QA防重']);
        [, , $j2] = req($sid, 'POST', $url, ['id' => $U, 'amount' => 10, 'remark' => 'QA防重']);
        ok('第一次提交成功', ($j1['code'] ?? 0) == 1, json_encode($j1, JSON_UNESCAPED_UNICODE));
        ok('紧接着同样的请求被拒', ($j2['code'] ?? 1) == 0 && strpos($j2['msg'] ?? '', '请勿重复操作') !== false, json_encode($j2, JSON_UNESCAPED_UNICODE));
        ok('  余额只加了一次（+10）', abs(bal($U) - ($b0 + 10)) < 0.001, bal($U) . ' vs ' . ($b0 + 10));
        ok('  流水只多了一条', logs($U) === $l0 + 1, logs($U) . ' vs ' . ($l0 + 1));

        [, , $j3] = req($sid, 'POST', $url, ['id' => $U, 'amount' => 10, 'remark' => 'QA防重-另一个备注']);
        ok('备注不同 → 视为新的调整，放行', ($j3['code'] ?? 0) == 1, json_encode($j3, JSON_UNESCAPED_UNICODE));
        [, , $j4] = req($sid, 'POST', $url, ['id' => $U, 'amount' => -5, 'remark' => 'QA防重']);
        ok('金额不同 → 放行', ($j4['code'] ?? 0) == 1, json_encode($j4, JSON_UNESCAPED_UNICODE));
        ok('  余额累计 +10 +10 -5', abs(bal($U) - ($b0 + 15)) < 0.001, bal($U));

        [, , $j5] = req($sid, 'POST', $url, ['id' => $U, 'amount' => 0, 'remark' => 'QA零']);
        [, , $j6] = req($sid, 'POST', $url, ['id' => $U, 'amount' => 0, 'remark' => 'QA零']);
        ok('被校验拒绝的请求（金额 0）不占防重名额，两次都是同一个校验提示', ($j5['code'] ?? 1) == 0 && ($j6['code'] ?? 1) == 0
            && ($j6['msg'] ?? '') === ($j5['msg'] ?? '') && strpos($j6['msg'] ?? '', '重复') === false, json_encode([$j5, $j6], JSON_UNESCAPED_UNICODE));
    }

    echo "== 跨操作者 ==\n";
    $b0 = bal($U);
    [, , $ja] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 7, 'remark' => 'QA跨']);
    [, , $jg] = req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => 7, 'remark' => 'QA跨']);
    ok('管理员和代理各自提交同样的调整互不影响', ($ja['code'] ?? 0) == 1 && ($jg['code'] ?? 0) == 1 && abs(bal($U) - ($b0 + 14)) < 0.001, json_encode([$ja, $jg], JSON_UNESCAPED_UNICODE));

    echo "== 过了 5 秒 ==\n";
    $b0 = bal($U);
    [, , $j1] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 3, 'remark' => 'QA等待']);
    sleep(6);
    [, , $j2] = req($sa, 'POST', '/admin1314/member/adjustBalance', ['id' => $U, 'amount' => 3, 'remark' => 'QA等待']);
    ok('6 秒后再提交同样的调整 → 放行', ($j1['code'] ?? 0) == 1 && ($j2['code'] ?? 0) == 1 && abs(bal($U) - ($b0 + 6)) < 0.001, json_encode([$j1, $j2], JSON_UNESCAPED_UNICODE));

    echo "== 页面 ==\n";
    foreach ([['主后台', $sa, '/admin1314/member/index'], ['代理后台', $sg, '/agent/member/index']] as [$name, $sid, $url]) {
        [$c, $h] = req($sid, 'GET', $url, null, false);
        ok("$name 确定按钮有 id，提交时上锁", $c == 200 && strpos($h, 'id="balanceSubmit"') !== false
            && strpos($h, 'if (balanceSubmitting) return;') !== false && strpos($h, "b.textContent = on ? '处理中…' : '确定';") !== false, "HTTP $c");
        ok('  请求结束（成功或失败）都解锁，打开弹窗时先重置', strpos($h, 'function(){ balanceLock(false); }') !== false && strpos($h, "balanceLock(false);\n    document.getElementById('balanceMask').classList.add('show');") !== false || strpos($h, "balanceLock(false);\r\n    document.getElementById('balanceMask').classList.add('show');") !== false);
        ok('  layout 的 ajaxPost 带 done 回调', strpos($h, 'function ajaxPost(url, data, cb, errMsg, done)') !== false);
    }
} finally {
    if ($origSw === false) { $pdo->exec("delete from setting where name='agent_balance_adjust'"); } else { $pdo->exec("insert into setting(name,value,create_time) values('agent_balance_adjust','$origSw',$T) on duplicate key update value='$origSw'"); }
    $pdo->exec("delete from balance_log where user_id in ($uids)");
    $pdo->exec("delete from admin_log where action like '%QA防重%' or action like '%QA跨%' or action like '%QA等待%'");
    $pdo->exec("delete from agent_log where action like '%QA防重%' or action like '%QA跨%'");
    $pdo->exec("delete from user where id in ($uids)");
    @unlink("$root/runtime/session/sess_$sa"); @unlink("$root/runtime/session/sess_$sg");
    echo "[cleanup] done\n";
}

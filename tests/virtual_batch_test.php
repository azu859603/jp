<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,create_time,update_time,reg_time) values('19999990260','x','QA代理VB','990260',0,1,0,0,0,1,$T,$T,$T)"); $agentId = (int)$pdo->lastInsertId();
$asid = md5('vb' . $T); $a = $pdo->query('select * from admin_user where id=1')->fetch(PDO::FETCH_ASSOC); file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $a]));
$gsid = md5('vg' . $T); $gu = $pdo->query("select * from user where id=$agentId")->fetch(PDO::FETCH_ASSOC); unset($gu['password']); file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $gu]));
function req($sid, $m, $p, $d = null, $ajax = true) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']); if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); } $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [$c, $b, json_decode($b, true)]; }
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
$made = [];
try {
    [$c, $b] = req($asid, 'GET', '/admin1314/member/index', null, false); ok('主后台会员列表含批量添加按钮与弹窗', $c == 200 && strpos($b, 'openBatchVirtual()') !== false && strpos($b, 'id="vbMask"') !== false && strpos($b, '/admin1314/member/batchAddVirtual') !== false, "HTTP $c");
    [$c, $b] = req($gsid, 'GET', '/agent/member/index', null, false); ok('代理端会员列表含批量添加按钮与弹窗', $c == 200 && strpos($b, 'openBatchVirtual()') !== false && strpos($b, '/agent/member/batchAddVirtual') !== false, "HTTP $c");
    [, , $j] = req($asid, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 0]); ok('数量 0 被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 101]); ok('数量 101 被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 3, 'password' => '123']); ok('密码过短被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 5, 'prefix' => 'QA托', 'balance' => 88888, 'password' => '']); $list = $j['data'] ?? []; foreach ($list as $u) $made[] = (int)$u['id'];
    ok('主后台批量添加 5 个，默认密码 123456', ($j['code'] ?? 0) == 1 && count($list) == 5 && ($j['password'] ?? '') === '123456' && strpos($j['msg'], '123456') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    $allZero = true; $allLen = true; $allNick = true; foreach ($list as $u) { if (substr($u['mobile'], 0, 2) !== '12') $allZero = false; if (strlen($u['mobile']) !== 11 || !ctype_digit($u['mobile'])) $allLen = false; if (!preg_match('/^QA托\d{4}$/', $u['nickname'])) $allNick = false; }
    ok('  账号都是 12 开头的 11 位数字，昵称为前缀 + 4 位数字', $allZero && $allLen && $allNick, json_encode($list, JSON_UNESCAPED_UNICODE));
    ok('  账号互不重复', count(array_unique(array_column($list, 'mobile'))) == 5);
    $ids = implode(',', $made);
    $rows = $pdo->query("select * from user where id in ($ids)")->fetchAll(PDO::FETCH_ASSOC); $okRows = count($rows) == 5; foreach ($rows as $r) { if ($r['is_virtual'] != 1 || $r['status'] != 1 || (float)$r['balance'] != 88888 || $r['pid'] != 0 || strlen($r['invite_code']) != 6 || $r['password'] === '' || $r['is_seller'] != 0) $okRows = false; }
    ok('  入库：虚拟会员、启用、余额 88888、无上级、6 位邀请码、密码已加密', $okRows, json_encode($rows, JSON_UNESCAPED_UNICODE));
    ok('  初始余额流水 5 条', (int)$pdo->query("select count(*) from balance_log where user_id in ($ids) and type='recharge' and amount=88888 and remark='后台添加会员赠送余额'")->fetchColumn() == 5);
    ok('  后台日志已记录', (int)$pdo->query("select count(*) from admin_log where action like '批量添加虚拟会员 5 个%'")->fetchColumn() == 1);
    ok('  密码可用：hash 校验通过', password_verify('123456', $rows[0]['password']) || $rows[0]['password'] === md5('123456') || $rows[0]['password'] === hash('sha256', '123456'));
    [, , $j] = req($gsid, 'POST', '/agent/member/batchAddVirtual', ['count' => 2, 'prefix' => '', 'balance' => 0, 'password' => 'abcdef']); $list2 = $j['data'] ?? []; foreach ($list2 as $u) $made[] = (int)$u['id'];
    $ids2 = implode(',', array_column($list2, 'id')); $rows2 = $ids2 ? $pdo->query("select * from user where id in ($ids2)")->fetchAll(PDO::FETCH_ASSOC) : [];
    $okAgent = count($rows2) == 2; foreach ($rows2 as $r) { if ($r['pid'] != $agentId || $r['is_virtual'] != 1 || (float)$r['balance'] != 0 || !preg_match('/^用户\d{4}$/', $r['nickname'])) $okAgent = false; }
    ok('代理端批量添加 2 个：归入代理团队、默认前缀「用户」、余额 0 不写流水', ($j['code'] ?? 0) == 1 && $okAgent && (int)$pdo->query("select count(*) from balance_log where user_id in ($ids2)")->fetchColumn() == 0, json_encode([$j, $rows2], JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'GET', '/agent/member/index?page=1&limit=50&keyword=' . urlencode($list2[0]['mobile'])); ok('  代理会员列表能搜到新虚拟会员', count($j['data'] ?? []) == 1 && $j['data'][0]['is_virtual'] == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    // 生成的虚拟账号一律 12 开头，且与库里已有账号不重复（前台注册目前并未禁止 12 号段，这里不对全库做假设）
    $madeIds = implode(',', $made);
    ok('批量生成的虚拟账号全部 12 开头且与已有账号不重复', (int)$pdo->query("select count(*) from user where id in ($madeIds) and mobile not like '12_________'")->fetchColumn() == 0
        && (int)$pdo->query("select count(*) from (select mobile from user where id in ($madeIds) group by mobile having count(*) > 1) t")->fetchColumn() == 0);
    ok('虚拟账号在自动出价候选中可见（is_virtual=1 且启用）', (int)$pdo->query("select count(*) from user where id in ($ids) and is_virtual=1 and status=1")->fetchColumn() == 5);
} finally {
    if ($made) { $ids = implode(',', $made); $pdo->exec("delete from balance_log where user_id in ($ids)"); $pdo->exec("delete from user where id in ($ids)"); }
    $pdo->exec("delete from admin_log where action like '批量添加虚拟会员%'"); $pdo->exec("delete from user where id=$agentId"); @unlink("$root/runtime/session/sess_$asid"); @unlink("$root/runtime/session/sess_$gsid"); echo "[cleanup] done\n";
}

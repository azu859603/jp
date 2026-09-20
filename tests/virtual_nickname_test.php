<?php
/**
 * 批量添加虚拟会员的昵称：
 *  - 昵称前缀留空时生成中文昵称（云水轩 / 王掌柜 / 江南藏家 …），不再是「用户1234」
 *  - 同批次内、以及与历史虚拟会员之间都不重复（控制器预载已有昵称做去重）
 *  - 填了前缀仍是「前缀 + 4 位随机数字」，方便按批次识别
 *  - 主后台、代理后台行为一致；邮箱注册模式下昵称同样是中文
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('vn' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function getSet($name) { global $pdo; $st = $pdo->prepare('select value from setting where name=?'); $st->execute([$name]); $v = $st->fetchColumn(); return $v === false ? null : $v; }
/** 取本次测试新建的虚拟会员昵称（按 id 大于起点） */
function newNicks($sinceId) { global $pdo; return $pdo->query("select nickname from user where is_virtual=1 and id>$sinceId order by id")->fetchAll(PDO::FETCH_COLUMN); }
$isCn = fn($s) => (bool)preg_match('/^[\x{4e00}-\x{9fa5}]{2,10}$/u', $s);

$pdo->exec("delete from user where mobile='19999990720'");
$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990720','x','QA昵称代理','990720',0,1,0,0,0,1,0,2,$T,$T,$T)");
$AG = (int)$pdo->lastInsertId();
$START = (int)$pdo->query('select max(id) from user')->fetchColumn();
$bakMode = getSet('register_mode');
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 主后台：留空前缀 ==\n";
    [, , $j] = req($sa, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 50, 'prefix' => '', 'password' => 'pass1234', 'balance' => 0]);
    $n1 = newNicks($START);
    ok('创建 50 个成功', ($j['code'] ?? 0) == 1 && count($n1) === 50, json_encode($j, JSON_UNESCAPED_UNICODE) . ' 实得 ' . count($n1));
    ok('昵称全是中文，不含数字', count(array_filter($n1, $isCn)) === 50, implode(' ', array_slice($n1, 0, 8)));
    ok('不再是「用户+数字」', count(array_filter($n1, fn($s) => strpos($s, '用户') === 0)) === 0, implode(' ', array_slice($n1, 0, 8)));
    ok('同批次内 50 个互不重复', count(array_unique($n1)) === 50, implode(' ', array_slice($n1, 0, 8)));
    echo '       样例：' . implode('、', array_slice($n1, 0, 10)) . "\n";

    echo "== 代理后台：留空前缀（跨批次去重）==\n";
    [, , $j] = req($sg, 'POST', '/agent/member/batchAddVirtual', ['count' => 50, 'prefix' => '', 'password' => 'pass1234', 'balance' => 0]);
    $all = newNicks($START);
    $n2  = array_slice($all, 50);
    ok('代理创建 50 个成功', ($j['code'] ?? 0) == 1 && count($n2) === 50, json_encode($j, JSON_UNESCAPED_UNICODE) . ' 实得 ' . count($n2));
    ok('代理生成的也是中文昵称', count(array_filter($n2, $isCn)) === 50, implode(' ', array_slice($n2, 0, 8)));
    ok('与主后台那批合计 100 个全不重复（跨批次去重生效）', count(array_unique($all)) === 100, '去重后 ' . count(array_unique($all)));
    ok('昵称长度在 2~10 字，不会超出字段上限', max(array_map('mb_strlen', $all)) <= 10);
    ok('代理创建的会员归入自己团队且为虚拟会员', (int)$pdo->query("select count(*) from user where pid=$AG and is_virtual=1")->fetchColumn() === 50);

    echo "== 填了前缀仍是「前缀+4位数字」 ==\n";
    $mark = (int)$pdo->query('select max(id) from user')->fetchColumn();
    [, , $j] = req($sa, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 5, 'prefix' => 'QA测试', 'password' => 'pass1234', 'balance' => 0]);
    $n3 = newNicks($mark);
    ok('前缀模式：5 个昵称都是 QA测试 + 4 位数字', ($j['code'] ?? 0) == 1 && count($n3) === 5 && count(array_filter($n3, fn($s) => (bool)preg_match('/^QA测试\d{4}$/u', $s))) === 5, implode(' ', $n3));

    echo "== 邮箱注册模式 ==\n";
    $pdo->exec("update setting set value='email' where name='register_mode'");
    if (getSet('register_mode') === null) { $pdo->exec("insert into setting(name,value,create_time,update_time) values('register_mode','email',$T,$T)"); }
    $mark = (int)$pdo->query('select max(id) from user')->fetchColumn();
    [, , $j] = req($sa, 'POST', '/admin1314/member/batchAddVirtual', ['count' => 5, 'prefix' => '', 'password' => 'pass1234', 'balance' => 0]);
    $n4 = newNicks($mark);
    $mails = $pdo->query("select email from user where is_virtual=1 and id>$mark")->fetchAll(PDO::FETCH_COLUMN);
    ok('邮箱模式下账号是 @virtual.local，昵称仍是中文', ($j['code'] ?? 0) == 1 && count($n4) === 5 && count(array_filter($n4, $isCn)) === 5 && count(array_filter($mails, fn($e) => substr((string)$e, -14) === '@virtual.local')) === 5, implode(' ', $n4) . ' | ' . implode(' ', array_slice($mails, 0, 2)));

    echo "== 页面文案 ==\n";
    foreach ([['主后台', $sa, '/admin1314/member/index'], ['代理后台', $sg, '/agent/member/index']] as [$tag, $sid, $url]) {
        $ch = curl_init('http://localhost' . $url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
        $h = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        ok("$tag 说明与占位已更新", $c == 200 && strpos($h, '自动生成中文昵称') !== false && strpos($h, '昵称为前缀加 4 位随机数字') === false && strpos($h, '默认「用户」，生成如 用户3827') === false, "HTTP $c");
    }
} finally {
    if ($bakMode === null) { $pdo->exec("delete from setting where name='register_mode'"); }
    else { $st = $pdo->prepare('update setting set value=? where name=?'); $st->execute([$bakMode, 'register_mode']); }
    $pdo->exec("delete from user where id>$START and is_virtual=1");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from admin_log where action like '%批量添加虚拟会员%' and create_time>=$T");
    $pdo->exec("delete from user where id=$AG");
    foreach ([$sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

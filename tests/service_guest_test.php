<?php
/**
 * 在线客服游客模式
 *  - 未登录也能打开客服页、发文字消息，后台能看到并回复，游客能收到回复
 *  - 游客之间互相隔离；游客不能发图片
 *  - 登录页有「联系客服」按钮
 *  - 游客登录后，之前的消息并入其会员会话
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function newSid($tag) { return md5($tag . microtime(true) . mt_rand()); }
function sessData($sid) { global $root; $f = "$root/runtime/session/sess_$sid"; return is_file($f) ? (@unserialize(file_get_contents($f)) ?: []) : []; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $head = $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/service/index'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
/** 发请求并返回响应里 Set-Cookie 下发的新 PHPSESSID（没有下发则返回原 sid） */
function reqSid($sid, $m, $p, $d = null) {
    $head = ['X-Requested-With: XMLHttpRequest'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $raw = (string)curl_exec($ch);
    $hs  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $c   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $headers = substr($raw, 0, $hs); $body = substr($raw, $hs);
    $newSid = preg_match('/Set-Cookie:\s*PHPSESSID=([a-f0-9]+)/i', $headers, $mm) ? $mm[1] : $sid;
    return [$c, $body, json_decode($body, true), $newSid];
}
function rows($where) { global $pdo; return $pdo->query("select * from service_message where $where order by id asc")->fetchAll(PDO::FETCH_ASSOC); }

$sids = [];
$uid  = 0;
$keys = [];
try {
    echo "== 登录页 ==\n";
    [$c, $html] = req(newSid('lp'), 'GET', '/user/login', null, false);
    ok('登录页有「联系客服」按钮，指向 /service/index', $c == 200 && strpos($html, 'href="/service/index"') !== false && strpos($html, '联系客服') !== false, "HTTP $c");

    echo "== 游客打开客服页 ==\n";
    $g1 = newSid('g1'); $sids[] = $g1;
    [$c, $html] = req($g1, 'GET', '/service/index', null, false);
    ok('未登录可打开客服页（不再 302 到登录）', $c == 200 && strpos($html, '游客身份') !== false, "HTTP $c");
    ok('游客页面不显示图片按钮', strpos($html, 'id="imgFile"') === false, '');
    $k1 = (string)(sessData($g1)['service_guest_key'] ?? '');
    $keys[] = $k1;
    ok('session 里分配了 32 位游客标识', preg_match('/^[a-f0-9]{32}$/', $k1) === 1, $k1);

    echo "== 游客发消息 ==\n";
    [, , $j] = req($g1, 'POST', '/service/send', ['type' => 1, 'content' => 'QA游客留言：你好，请问怎么充值？']);
    ok('游客发送文字成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $r = rows("guest_key='$k1'");
    ok('入库 user_id=0 且带 guest_key', count($r) === 1 && (int)$r[0]['user_id'] === 0 && (int)$r[0]['from_type'] === 1, json_encode($r, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($g1, 'POST', '/service/send', ['type' => 2, 'content' => '/uploads/x.jpg']);
    ok('游客不能发图片', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '图片') !== false, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($g1, 'POST', '/service/send', ['type' => 1, 'content' => '第二条']);
    ok('3 秒内重复发送被限流', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($g1, 'POST', '/service/send', ['type' => 1, 'content' => '']);
    ok('空内容被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 未先打开客服页的会话 ==\n";
    $g0 = newSid('g0'); $sids[] = $g0;
    [, , $j] = req($g0, 'POST', '/service/send', ['type' => 1, 'content' => 'QA没有标识']);
    ok('没有游客标识时返回 -2（前端刷新重新分配）', ($j['code'] ?? 1) == -2, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($g0, 'GET', '/service/poll?last_id=0');
    ok('没有游客标识时轮询返回 -2', ($j['code'] ?? 1) == -2, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 游客之间隔离 ==\n";
    $g2 = newSid('g2'); $sids[] = $g2;
    req($g2, 'GET', '/service/index', null, false);
    $k2 = (string)(sessData($g2)['service_guest_key'] ?? '');
    $keys[] = $k2;
    ok('第二个游客拿到不同标识', $k2 !== '' && $k2 !== $k1, "$k1 / $k2");
    [, , $j] = req($g2, 'GET', '/service/poll?last_id=0');
    ok('第二个游客看不到第一个游客的消息', ($j['code'] ?? 0) == 1 && empty($j['data']), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($g1, 'GET', '/service/poll?last_id=0');
    ok('第一个游客能轮询到自己的消息', ($j['code'] ?? 0) == 1 && count($j['data'] ?? []) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 后台工作台 ==\n";
    $asid = newSid('adm'); $sids[] = $asid;
    $admin = $pdo->query("select * from admin_user order by id asc limit 1")->fetch(PDO::FETCH_ASSOC);
    unset($admin['password']);
    file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $admin]));
    [, , $j] = req($asid, 'GET', '/admin1314/service/index?keyword=&unread=0');
    $item = null;
    foreach (($j['data'] ?? []) as $x) { if (($x['key'] ?? '') === 'g:' . $k1) $item = $x; }
    ok('会话列表出现游客会话（key=g:…，未读 1）', $item && (int)$item['is_guest'] === 1 && (int)$item['unread'] === 1 && strpos($item['nickname'], '游客') === 0, json_encode($item, JSON_UNESCAPED_UNICODE));
    ok('未读总数包含游客', (int)($j['total_unread'] ?? 0) >= 1, json_encode($j['total_unread'] ?? null));
    [, , $j] = req($asid, 'GET', '/admin1314/service/index?keyword=游客&unread=0');
    $ids = array_map(function ($x) { return $x['key']; }, $j['data'] ?? []);
    ok('搜索「游客」能筛出游客会话', in_array('g:' . $k1, $ids), json_encode($ids));
    [, , $j] = req($asid, 'GET', '/admin1314/service/index?keyword=&unread=1');
    $ids = array_map(function ($x) { return $x['key']; }, $j['data'] ?? []);
    ok('只看未回复包含该游客', in_array('g:' . $k1, $ids), json_encode($ids));

    [, , $j] = req($asid, 'GET', '/admin1314/service/detail?key=g:' . $k1);
    ok('后台按 key 打开游客会话', ($j['code'] ?? 0) == 1 && (int)$j['user']['is_guest'] === 1 && count($j['data']) === 1 && $j['data'][0]['content'] === 'QA游客留言：你好，请问怎么充值？', json_encode($j, JSON_UNESCAPED_UNICODE));
    $r = rows("guest_key='$k1' and from_type=1");
    ok('打开后游客消息标记已读', (int)$r[0]['is_read'] === 1, json_encode($r, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'GET', '/admin1314/service/detail?key=g:zzz');
    ok('非法 key 被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/service/send', ['key' => 'g:' . str_repeat('0', 32), 'type' => 1, 'content' => 'x']);
    ok('给不存在的游客会话发消息被拒', ($j['code'] ?? 1) == 0, json_encode($j, JSON_UNESCAPED_UNICODE));

    [, , $j] = req($asid, 'POST', '/admin1314/service/send', ['key' => 'g:' . $k1, 'type' => 1, 'content' => 'QA客服回复：请到我的钱包里充值']);
    ok('客服回复游客成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $r = rows("guest_key='$k1' and from_type=2");
    ok('回复入库 user_id=0 + guest_key + admin_id', count($r) === 1 && (int)$r[0]['user_id'] === 0 && (int)$r[0]['admin_id'] === (int)$admin['id'], json_encode($r, JSON_UNESCAPED_UNICODE));

    echo "== 游客收到回复 ==\n";
    [, , $j] = req($g1, 'GET', '/service/poll?last_id=0');
    $got = false;
    foreach (($j['data'] ?? []) as $m) { if ((int)$m['from_type'] === 2 && strpos($m['content'], 'QA客服回复') === 0) $got = true; }
    ok('游客轮询收到客服回复', $got, json_encode($j, JSON_UNESCAPED_UNICODE));
    $r = rows("guest_key='$k1' and from_type=2");
    ok('游客拉取后客服消息标记已读', (int)$r[0]['is_read'] === 1, json_encode($r, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($g2, 'GET', '/service/poll?last_id=0');
    ok('其他游客收不到这条回复', empty($j['data']), json_encode($j, JSON_UNESCAPED_UNICODE));

    // 兼容旧参数：会员会话仍可用 user_id 打开
    [, , $j] = req($asid, 'GET', '/admin1314/service/detail?user_id=1');
    ok('后台旧参数 user_id 仍可用', ($j['code'] ?? 0) == 1 && ($j['key'] ?? '') === 'u:1', json_encode($j['key'] ?? null));

    echo "== 游客登录后合并 ==\n";
    $pwd  = 'qa123456';
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $pdo->exec("delete from user where mobile='19999990395'");
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('19999990395','$hash','QA游客转会员','990395',0,1,0,0,0,0,0,0,$T,$T,$T)");
    $uid = (int)$pdo->lastInsertId();
    // 同一个游客 session 里取验证码再登录
    req($g1, 'GET', '/user/captcha');
    $cap = (string)(sessData($g1)['user_captcha'] ?? '');
    [, , $j, $g1n] = reqSid($g1, 'POST', '/user/doLogin', ['mobile' => '19999990395', 'password' => $pwd, 'captcha' => $cap]);
    $sids[] = $g1n;
    clearstatcache();   // 前面多次 is_file() 会被 PHP 的 stat 缓存记住，先清掉再判断旧文件是否已删
    ok('登录后下发了新的会话 ID（旧 ID 作废）', $g1n !== $g1 && !is_file("$root/runtime/session/sess_$g1"), "old=$g1 new=$g1n");
    ok('游客在同一 session 登录成功', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE) . " cap=$cap");
    $r = rows("user_id=$uid");
    ok('游客消息（含客服回复）并入会员会话', count($r) === 2 && $r[0]['guest_key'] === '' && $r[1]['guest_key'] === '', json_encode($r, JSON_UNESCAPED_UNICODE));
    ok('原游客会话已清空', count(rows("guest_key='$k1'")) === 0, '');
    ok('session 里的游客标识已清除', !empty(sessData($g1n)['user']) && empty(sessData($g1n)['service_guest_key']), json_encode(sessData($g1n)['service_guest_key'] ?? null));
    [, , $j] = req($g1n, 'GET', '/service/poll?last_id=0');
    ok('登录后以会员身份仍能看到这些消息', ($j['code'] ?? 0) == 1 && count($j['data'] ?? []) === 2, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'GET', '/admin1314/service/index?keyword=&unread=0');
    $ks = array_map(function ($x) { return $x['key']; }, $j['data'] ?? []);
    ok('后台列表中游客会话消失、会员会话出现', !in_array('g:' . $k1, $ks) && in_array('u:' . $uid, $ks), json_encode($ks));
} finally {
    foreach ($keys as $k) { if ($k !== '') $pdo->exec("delete from service_message where guest_key='$k'"); }
    $pdo->exec("delete from service_message where content like 'QA游客留言%' or content like 'QA客服回复%' or content='QA没有标识'");
    if ($uid) { $pdo->exec("delete from service_message where user_id=$uid"); $pdo->exec("delete from user where id=$uid"); }
    foreach ($sids as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

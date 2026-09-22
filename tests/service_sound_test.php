<?php
/**
 * 后台在线客服未读提醒（声音 + 右上角提示条）
 *  - /admin1314/service/unread 返回未读数（会员发来、客服未读的消息）
 *  - layout 里注入了 svcUnread()：更新菜单角标，未读数比上次多时响铃并弹「您有新的客服消息未查看」
 *  - 提示条带「前往处理 / 静音 / 关闭」，静音状态记在 localStorage
 *  - 客服页的 updateMenuBadge() 复用同一套逻辑
 * 页面行为（响铃、弹窗、静音开关）已在浏览器里逐项验证过，这里覆盖接口与页面注入。
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid,
        CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html']]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, (string)$b, json_decode((string)$b, true)];
}
function msg($uid, $fromType, $isRead, $content) {
    global $pdo, $T;
    $pdo->exec("insert into service_message(user_id,from_type,admin_id,type,content,is_read,create_time,guest_key) values($uid,$fromType,0,1,'$content',$isRead,$T,'')");
    return (int)$pdo->lastInsertId();
}

$pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,auth_status,create_time,update_time,reg_time) values('19999994501','x','QA客服提醒会员','994501',0,1,0,0,0,2,$T,$T,$T)");
$uid = (int)$pdo->lastInsertId();
$mids = [];
$sid = md5('qsvc' . $T);
$admin = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC);
unset($admin['password']);
file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $admin]));
$before = (int)$pdo->query("select count(*) from service_message where from_type=1 and is_read=0")->fetchColumn();

try {
    echo "== 未读接口 ==\n";
    [$c, , $j] = req($sid, '/admin1314/service/unread');
    ok('接口可用，返回当前未读数', $c == 200 && ($j['code'] ?? 0) == 1 && (int)($j['unread'] ?? -1) === $before, json_encode($j));
    $mids[] = msg($uid, 1, 0, 'QA未读1');
    $mids[] = msg($uid, 1, 0, 'QA未读2');
    [, , $j] = req($sid, '/admin1314/service/unread');
    ok('会员发来两条未读 → 未读数 +2', (int)($j['unread'] ?? -1) === $before + 2, json_encode($j));
    $mids[] = msg($uid, 2, 0, 'QA客服回复');
    [, , $j] = req($sid, '/admin1314/service/unread');
    ok('客服自己发的消息不计入未读', (int)($j['unread'] ?? -1) === $before + 2, json_encode($j));
    $pdo->exec("update service_message set is_read=1 where id={$mids[0]}");
    [, , $j] = req($sid, '/admin1314/service/unread');
    ok('标记已读后未读数 -1', (int)($j['unread'] ?? -1) === $before + 1, json_encode($j));

    echo "== 页面注入 ==\n";
    [$c, $h] = req($sid, '/admin1314/index/index', false);
    ok('后台任意页面都带提醒脚本', $c == 200 && strpos($h, 'window.svcUnread = function') !== false, "HTTP $c");
    ok('  提示文案是「您有新的客服消息未查看」', strpos($h, '您有新的客服消息未查看') !== false);
    ok('  提示条带前往处理 / 静音 / 关闭', strpos($h, 'svcToastMute') !== false && strpos($h, 'svcToastClose') !== false
        && strpos($h, '/admin1314/service/index" style="color:#ea580c') !== false);
    ok('  用 Web Audio 合成提示音，不依赖音频文件', strpos($h, 'createOscillator') !== false && strpos($h, 'AudioContext') !== false);
    ok('  首次点击 / 按键时解锁音频（绕开浏览器自动播放限制）', strpos($h, "['click', 'keydown'].forEach") !== false && strpos($h, '{once: true}') !== false);
    ok('  只有未读变多才提醒', strpos($h, 'if (n > last) { beep(); toast(n); }') !== false);
    ok('  静音状态记在 localStorage', strpos($h, "localStorage.getItem('svc_mute')") !== false && strpos($h, "localStorage.setItem('svc_mute', '1')") !== false);
    ok('  仍保留 30 秒轮询未读数', strpos($h, "fetch('/admin1314/service/unread'") !== false && strpos($h, '}, 30000);') !== false);

    [$c, $h] = req($sid, '/admin1314/service/index', false);
    ok('客服页复用同一套提醒', $c == 200 && strpos($h, "if (typeof window.svcUnread === 'function') { window.svcUnread(n); return; }") !== false, "HTTP $c");
} finally {
    if ($mids) { $pdo->exec("delete from service_message where id in (" . implode(',', $mids) . ")"); }
    $pdo->exec("delete from service_message where user_id=$uid");
    $pdo->exec("delete from user where id=$uid");
    @unlink("$root/runtime/session/sess_$sid");
    echo "[cleanup] done\n";
}

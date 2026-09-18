<?php
/**
 * 审计修复：图片地址校验 + 后台输出转义
 *  - 前台卖家发布商品、入驻企业资料、实名证件图：只接受 /uploads/ 下的图片地址，脚本注入串和外链被拒
 *  - 后台 / 代理后台发布、编辑：接受本站路径和 http(s) 图片外链，拒绝带引号 / 尖括号 / 空格的地址
 *  - 后台商品列表、审核页封面经 esc() 输出；操作日志内容经 esc() 输出（日志里会带商品标题等用户可控文本）
 *  - admin_log 超长内容截断到 255 字，不再整条丢失
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0, $auth = 2) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,real_name,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','QA实名','" . substr($m, -6) . "',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",$agent,0,$auth,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('ax' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
$pdo->exec("delete from user where mobile in ('19999990680','19999990681','19999990682')");
$AG = mk('19999990680', 'QA图片代理', 0, 1);
$S  = mk('19999990681', 'QA图片卖家', $AG, 0, 1);
$N  = mk('19999990682', 'QA图片新人', $AG, 0, 0, 0);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$ss = sess($S); $sn = sess($N); $sa = sess(0, 'admin'); $sg = sess($AG);
$XSS = 'x" onerror="alert(1)';
$good = ['/uploads/qa/a.jpg', '/uploads/qa/b.png', '/uploads/qa/c.webp', '/uploads/qa/d.jpeg'];
$base = ['title' => 'QA图片校验拍品', 'category_id' => $cat, 'content' => 'x', 'start_price' => 100, 'raise_price' => 10, 'reserve_price' => 0, 'deposit' => 0, 'end_time' => date('Y-m-d\TH:i', $T + 86400), 'delay_seconds' => 0];
$imgErr = fn($j) => ($j['code'] ?? 1) == 0 && strpos($j['msg'] ?? '', '图片地址不合法') !== false;
try {
    echo "== 前台卖家发布 ==\n";
    [, , $j] = req($ss, 'POST', '/seller/goods_add', $base + ['images' => $good, 'cover' => $XSS]);
    ok('封面带脚本注入串被拒', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/seller/goods_add', $base + ['images' => array_merge(array_slice($good, 0, 3), [$XSS])]);
    ok('图片列表里混入注入串被拒', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/seller/goods_add', $base + ['images' => array_merge(array_slice($good, 0, 3), ['https://evil.example.com/x.jpg'])]);
    ok('前台不接受外链图片', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($ss, 'POST', '/seller/goods_add', $base + ['images' => array_merge(array_slice($good, 0, 3), ['/uploads/../config/x.jpg'])]);
    ok('带 .. 的路径被拒', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    ok('以上都没有入库', (int)$pdo->query("select count(*) from goods where seller_id=$S")->fetchColumn() === 0);
    [, , $j] = req($ss, 'POST', '/seller/goods_add', $base + ['images' => $good]);
    ok('合法的 /uploads/ 图片可以发布', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 前台实名 / 入驻 ==\n";
    [, , $j] = req($sn, 'POST', '/user/auth', ['real_name' => '张三', 'id_card' => '110101199003077715', 'id_card_front' => $XSS, 'id_card_back' => '/uploads/qa/b.jpg']);
    ok('实名证件图注入串被拒', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sn, 'POST', '/seller/apply', ['shop_name' => 'QA图片店', 'company_name' => 'QA公司', 'license_img' => '/uploads/qa/a.jpg,' . $XSS]);
    ok('入驻企业资料图注入串被拒（或先被实名前置条件拦下）', ($j['code'] ?? 1) == 0 && $pdo->query("select license_img from user where id=$N")->fetchColumn() == '', json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 后台 / 代理后台 ==\n";
    $adm = $base + ['seller_id' => $S];
    [, , $j] = req($sa, 'POST', '/admin1314/goods/add', $adm + ['images' => [$XSS]]);
    ok('主后台发布：注入串被拒', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'POST', '/admin1314/goods/add', $adm + ['images' => ['https://img.example.com/a/b.jpg?x=1', '/static/demo.png']]);
    ok('主后台发布：http(s) 外链和本站路径可用', ($j['code'] ?? 0) == 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/goods/add', $adm + ['images' => ['javascript:alert(1)//.jpg']]);
    ok('代理发布：javascript: 伪协议被拒', $imgErr($j), json_encode($j, JSON_UNESCAPED_UNICODE));
    $G = (int)$pdo->query("select id from goods where seller_id=$S order by id asc limit 1")->fetchColumn();
    [, , $j] = req($sg, 'POST', "/agent/goods/edit?id=$G", $base + ['images' => $good, 'cover' => $XSS]);
    ok('代理编辑：封面注入串被拒，库里封面不变', $imgErr($j) && $pdo->query("select cover from goods where id=$G")->fetchColumn() === $good[0], json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 页面输出转义 ==\n";
    foreach ([['主后台商品列表', $sa, '/admin1314/goods/index'], ['主后台商品审核', $sa, '/admin1314/goods/check'], ['代理产品列表', $sg, '/agent/goods/index'], ['代理产品审核', $sg, '/agent/goods/check']] as [$tag, $sid, $url]) {
        [$c, $h] = req($sid, 'GET', $url, null, false);
        ok("$tag 封面经 esc() 输出", $c == 200 && strpos($h, "src=\"' + esc(g.cover) + '\"") !== false && strpos($h, "src=\"' + g.cover + '\"") === false, "HTTP $c");
    }
    [$c, $h] = req($sg, 'GET', '/agent/goods/check', null, false);
    ok('代理产品审核页标题、卖家经 esc() 输出', $c == 200 && strpos($h, "esc(g.title)") !== false && strpos($h, "'>' + g.title + '") === false && strpos($h, 'esc(g.seller_name') !== false, "HTTP $c");
    [$c, $h] = req($sa, 'GET', '/admin1314/log/index', null, false);
    ok('操作日志页内容经 esc() 输出', $c == 200 && strpos($h, 'esc(l.action)') !== false && strpos($h, "'<td>' + l.action + '</td>'") === false, "HTTP $c");

    echo "== 日志截断 ==\n";
    $max  = (int)$pdo->query("select character_maximum_length from information_schema.columns where table_schema='jp' and table_name='goods' and column_name='title'")->fetchColumn();
    $long = 'QA超长标题' . str_repeat('长', $max - 6);   // 标题列能存的最大长度；加上日志前缀后超过 255
    $pdo->prepare("update goods set title=?, status=0 where id=?")->execute([$long, $G]);
    [, , $j] = req($sa, 'POST', '/admin1314/goods/audit', ['id' => $G, 'action' => 'pass']);
    $log = (string)$pdo->query("select action from admin_log order by id desc limit 1")->fetchColumn();
    ok('超长标题的审核日志仍然写入，且被截断到 255 字', ($j['code'] ?? 0) == 1 && strpos($log, '通过商品审核：QA超长标题') === 0 && mb_strlen($log) === min(255, $max + 7), json_encode($j, JSON_UNESCAPED_UNICODE) . ' len=' . mb_strlen($log));
} finally {
    $pdo->exec("delete from admin_log where action like '%QA超长标题%' or action like '%QA图片校验拍品%'");
    $pdo->exec("delete from agent_log where agent_id=$AG");
    $pdo->exec("delete from goods where seller_id=$S");
    $pdo->exec("delete from user where id in ($AG,$S,$N)");
    foreach ([$ss, $sn, $sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

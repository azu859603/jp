<?php
/**
 * 主后台 / 代理后台「商品审核」列表：卖家后面新增一列「卖家账号」
 *  - 表头有该列，单元格渲染 seller_mobile，列数与空状态 colspan 对齐
 *  - 接口返回的卖家账号正确（手机号 / 邮箱账号都取 user.account）
 *  - 代理只能看到自己团队卖家的待审核商品
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkm($mobile, $email, $nick, $pid = 0, $agent = 0, $seller = 0) {
    global $pdo, $T;
    $m = $mobile === null ? 'NULL' : "'$mobile'";
    $e = $email === null ? 'NULL' : "'$email'";
    $code = substr((string)($mobile ?: $email), -6);
    $pdo->exec("insert into user(mobile,email,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values($m,$e,'x','$nick','$code',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",$agent,0,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('cs' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function pick($list, $id) { foreach ((array)$list as $x) if ((int)($x['id'] ?? 0) === (int)$id) return $x; return null; }

$pdo->exec("delete from user where mobile like '199999912%' or email like 'qa.check%'");
$AG = mkm('19999991201', null, 'QA审核代理', 0, 1);
$S1 = mkm('19999991202', null, 'QA审核手机卖家', $AG, 0, 1);
$S2 = mkm(null, 'qa.check.seller@example.com', 'QA审核邮箱卖家', $AG, 0, 1);
$SX = mkm('19999991203', null, 'QA审核外部卖家', 0, 0, 1);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$G = []; $i = 0;
foreach ([$S1, $S2, $SX] as $sid) {
    $i++;
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($sid,$cat,'QA审核拍品$i','','[]',100,10,0,0,$T-600,$T+86400,$T,$T)");
    $G[$i] = (int)$pdo->lastInsertId();
}
$sa = sess(0, 'admin'); $sg = sess($AG);
try {
    echo "== 主后台 ==\n";
    [, , $j] = req($sa, '/admin1314/goods/check?page=1&limit=100&status=0');
    $g1 = pick($j['data'] ?? [], $G[1]); $g2 = pick($j['data'] ?? [], $G[2]);
    ok('接口返回手机号卖家的账号', $g1 && ($g1['seller_mobile'] ?? '') === '19999991202' && ($g1['seller_name'] ?? '') === 'QA审核手机卖家', json_encode($g1, JSON_UNESCAPED_UNICODE));
    ok('接口返回邮箱卖家的账号', $g2 && ($g2['seller_mobile'] ?? '') === 'qa.check.seller@example.com', json_encode($g2, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sa, '/admin1314/goods/check', false);
    ok('表头有「卖家账号」，紧跟在「卖家」后面', $c == 200 && strpos($h, '<th>卖家</th><th>卖家账号</th><th>起拍价</th>') !== false, "HTTP $c");
    ok('单元格渲染卖家账号', strpos($h, "+ '<td>' + esc(g.seller_mobile || '-') + '</td>'") !== false);
    ok('空状态 colspan 与列数一致（11）', strpos($h, 'colspan="11"') !== false && strpos($h, 'colspan="10"') === false);
    $th = substr_count(substr($h, strpos($h, '<thead>'), strpos($h, '</thead>') - strpos($h, '<thead>')), '<th>');
    $td = substr_count(substr($h, strpos($h, "html += '<tr>'"), 2000), "+ '<td");
    ok("表头列数与单元格数一致（表头 $th 列）", $th === 11, "th=$th td=$td");

    echo "== 代理后台 ==\n";
    [, , $j] = req($sg, '/agent/goods/check?page=1&limit=100&status=0');
    $ids = array_column($j['data'] ?? [], 'id');
    ok('代理只看到团队卖家的两件，看不到外部卖家的', in_array($G[1], $ids) && in_array($G[2], $ids) && !in_array($G[3], $ids), json_encode($ids));
    $g2 = pick($j['data'] ?? [], $G[2]);
    ok('代理接口也返回卖家账号', ($g2['seller_mobile'] ?? '') === 'qa.check.seller@example.com', json_encode($g2, JSON_UNESCAPED_UNICODE));
    [$c, $h] = req($sg, '/agent/goods/check', false);
    ok('代理审核页表头与单元格同步', $c == 200 && strpos($h, '<th>卖家</th><th>卖家账号</th><th>起拍价</th>') !== false
        && strpos($h, "+ '<td>' + esc(g.seller_mobile || '-') + '</td>'") !== false && strpos($h, 'colspan="11"') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from goods where seller_id in ($S1,$S2,$SX)");
    $pdo->exec("delete from user where id in ($AG,$S1,$S2,$SX)");
    foreach ([$sa, $sg] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

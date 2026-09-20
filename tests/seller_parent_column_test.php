<?php
/**
 * 主后台「商品列表」「自动出价」：卖家账号下方显示卖家的上级账号
 *  - 有上级显示上级账号并可点进会员详情；无上级显示「无」；上级已删除显示「#ID（已删除）」
 *  - 多连一张表不影响原有的卖家搜索
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $seller = 0, $virtual = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess() { global $root, $pdo; $sid = md5('pc' . microtime(true)); $u = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $u])); return $sid; }
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function pick($list, $key, $val) { foreach ((array)$list as $x) if (($x[$key] ?? null) == $val) return $x; return null; }

$pdo->exec("delete from user where mobile like '199999911%'");
$P  = mk('19999991101', 'QA列上级');
$S1 = mk('19999991102', 'QA列有上级卖家', $P, 1);
$S2 = mk('19999991103', 'QA列无上级卖家', 0, 1);
$PD = mk('19999991104', 'QA列待删上级');
$S3 = mk('19999991105', 'QA列上级已删卖家', $PD, 1);
$VB = mk('19999991106', 'QA列虚拟买家', 0, 0, 1);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$G = []; $i = 0;
foreach ([$S1, $S2, $S3] as $sid) {
    $i++;
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($sid,$cat,'QA列上级拍品$i','','[]',100,10,0,1,$T-600,$T+7200,$T,$T)");
    $G[$i] = (int)$pdo->lastInsertId();
    $pdo->exec("insert into auto_bid(goods_id,interval_min,max_price,stop_hours,status,stop_reason,next_time,last_time,bid_count,creator_type,creator_id,create_time,update_time) values({$G[$i]},30,500,1,1,'',$T+600,0,0,'admin',1,$T,$T)");
}
$pdo->exec("delete from user where id=$PD");   // 制造「上级已删除」
$sa = sess();
try {
    echo "== 商品列表接口 ==\n";
    [, , $j] = req($sa, '/admin1314/goods/index?page=1&limit=50&keyword=' . urlencode('QA列上级拍品'));
    $g1 = pick($j['data'] ?? [], 'id', $G[1]); $g2 = pick($j['data'] ?? [], 'id', $G[2]); $g3 = pick($j['data'] ?? [], 'id', $G[3]);
    ok('三件商品都在（多连一张表不丢数据）', $g1 && $g2 && $g3, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    ok('有上级：返回上级账号与上级ID', ($g1['seller_parent'] ?? '') === '19999991101' && (int)($g1['seller_pid'] ?? 0) === $P, json_encode($g1, JSON_UNESCAPED_UNICODE));
    ok('无上级：seller_parent 空、seller_pid 为 0', ($g2['seller_parent'] ?? null) === null && (int)($g2['seller_pid'] ?? -1) === 0, json_encode($g2, JSON_UNESCAPED_UNICODE));
    ok('上级已删除：seller_pid 保留、seller_parent 为空', ($g3['seller_parent'] ?? null) === null && (int)($g3['seller_pid'] ?? 0) === $PD, json_encode($g3, JSON_UNESCAPED_UNICODE));
    ok('卖家账号与昵称仍然正确', ($g1['seller_mobile'] ?? '') === '19999991102' && ($g1['seller_name'] ?? '') === 'QA列有上级卖家', json_encode($g1, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, '/admin1314/goods/index?page=1&limit=50&seller_kw=19999991102');
    ok('按卖家账号搜索仍然命中', pick($j['data'] ?? [], 'id', $G[1]) !== null, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));

    echo "== 自动出价接口 ==\n";
    [, , $j] = req($sa, '/admin1314/auto_bid/index?page=1&limit=50&keyword=' . urlencode('QA列上级拍品'));
    $t1 = pick($j['data'] ?? [], 'goods_id', $G[1]); $t2 = pick($j['data'] ?? [], 'goods_id', $G[2]); $t3 = pick($j['data'] ?? [], 'goods_id', $G[3]);
    ok('三个任务都在', $t1 && $t2 && $t3, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    ok('有上级：返回上级账号与上级ID', ($t1['seller_parent'] ?? '') === '19999991101' && (int)($t1['seller_pid'] ?? 0) === $P, json_encode($t1, JSON_UNESCAPED_UNICODE));
    ok('无上级 / 上级已删除的两种情况正确', ($t2['seller_parent'] ?? null) === null && (int)($t2['seller_pid'] ?? -1) === 0 && ($t3['seller_parent'] ?? null) === null && (int)($t3['seller_pid'] ?? 0) === $PD, json_encode([$t2, $t3], JSON_UNESCAPED_UNICODE));
    ok('原有的 seller_text 仍然正常', ($t1['seller_text'] ?? '') === 'QA列有上级卖家', json_encode($t1, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, '/admin1314/auto_bid/index?page=1&limit=50&keyword=19999991102');
    ok('自动出价按卖家账号搜索仍然命中', pick($j['data'] ?? [], 'goods_id', $G[1]) !== null, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));

    echo "== 页面渲染 ==\n";
    foreach ([['商品列表', '/admin1314/goods/index', 'g'], ['自动出价', '/admin1314/auto_bid/index', 't']] as [$tag, $url, $v]) {
        [$c, $h] = req($sa, $url, false);
        ok("$tag 卖家账号列渲染上级并可点进详情", $c == 200
            && strpos($h, "esc($v.seller_mobile || '-')") !== false
            && strpos($h, "'<div class=\"gray\" style=\"font-size:12px;\">上级：'") !== false
            && strpos($h, "member/detail?id=' + $v.seller_pid") !== false
            && strpos($h, '（已删除）') !== false, "HTTP $c");
    }
} finally {
    $pdo->exec("delete from auto_bid where goods_id in (" . implode(',', $G) . ")");
    $pdo->exec("delete from goods where seller_id in ($S1,$S2,$S3)");
    $pdo->exec("delete from user where mobile like '199999911%'");
    @unlink("$root/runtime/session/sess_$sa");
    echo "[cleanup] done\n";
}

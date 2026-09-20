<?php
/**
 * 主后台订单列表：卖家账号下方显示卖家的上级账号
 *  - 有上级：显示上级账号，可点进会员详情
 *  - 无上级：显示「无」
 *  - 上级已被删除：显示「#ID（已删除）」
 *  - 原有的买家 / 卖家关键字搜索不受多连一张表的影响
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,$seller," . ($seller ? 1 : 0) . ",0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess() { global $root, $pdo; $sid = md5('sp' . microtime(true)); $u = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize(['admin' => $u])); return $sid; }
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function row($list, $no) { foreach ((array)$list as $o) if ($o['order_no'] === $no) return $o; return null; }

$pdo->exec("delete from user where mobile like '199999909%'");
$P   = mk('19999990901', 'QA上级会员');          // 卖家甲的上级
$S1  = mk('19999990902', 'QA有上级卖家', $P, 1);
$S2  = mk('19999990903', 'QA无上级卖家', 0, 1);
$PD  = mk('19999990904', 'QA待删上级');
$S3  = mk('19999990905', 'QA上级已删卖家', $PD, 1);
$B   = mk('19999990906', 'QA上级买家');
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$i = 0;
foreach ([$S1, $S2, $S3] as $sid) {
    $i++;
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,create_time,update_time) values($sid,$cat,'QA上级拍品$i','','[]',100,10,0,2,$T-7200,$T-3600,$T,$T)");
    $g = (int)$pdo->lastInsertId();
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,seller_id,buyer_id,price,deposit,seller_income,pay_status,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QAPARENT000$i',$g,'QA上级拍品$i',$sid,$B,120,0,120,1,1,'张三','13900001111','上海',$T,$T)");
}
$pdo->exec("delete from user where id=$PD");   // 制造「上级已删除」的情况
$sa = sess();
try {
    echo "== 接口字段 ==\n";
    [, , $j] = req($sa, '/admin1314/order/index?page=1&limit=50&keyword=QAPARENT');
    $r1 = row($j['data'] ?? [], 'QAPARENT0001');
    $r2 = row($j['data'] ?? [], 'QAPARENT0002');
    $r3 = row($j['data'] ?? [], 'QAPARENT0003');
    ok('三条订单都能搜到（多连一张表不影响原有搜索）', $r1 && $r2 && $r3, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    ok('有上级的卖家返回上级账号与上级ID', ($r1['seller_parent'] ?? '') === '19999990901' && (int)($r1['seller_pid'] ?? 0) === $P, json_encode($r1, JSON_UNESCAPED_UNICODE));
    ok('无上级的卖家 seller_parent 为空、seller_pid 为 0', ($r2['seller_parent'] ?? null) === null && (int)($r2['seller_pid'] ?? -1) === 0, json_encode($r2, JSON_UNESCAPED_UNICODE));
    ok('上级已删除：seller_pid 还在但 seller_parent 为空', ($r3['seller_parent'] ?? null) === null && (int)($r3['seller_pid'] ?? 0) === $PD, json_encode($r3, JSON_UNESCAPED_UNICODE));
    ok('卖家账号本身仍然正确', ($r1['seller_mobile'] ?? '') === '19999990902' && ($r2['seller_mobile'] ?? '') === '19999990903', json_encode([$r1['seller_mobile'] ?? '', $r2['seller_mobile'] ?? '']));

    echo "== 原有搜索 ==\n";
    [, , $j] = req($sa, '/admin1314/order/index?page=1&limit=50&keyword=19999990902');
    ok('按卖家账号搜索仍然命中', row($j['data'] ?? [], 'QAPARENT0001') !== null, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));
    [, , $j] = req($sa, '/admin1314/order/index?page=1&limit=50&keyword=19999990906');
    ok('按买家账号搜索仍然命中 3 条', count(array_filter($j['data'] ?? [], fn($o) => strpos($o['order_no'], 'QAPARENT') === 0)) === 3, mb_substr(json_encode($j, JSON_UNESCAPED_UNICODE), 0, 200));

    echo "== 页面 ==\n";
    [$c, $h] = req($sa, '/admin1314/order/index', false);
    ok('列表页卖家列渲染上级，且可点进会员详情', $c == 200 && strpos($h, "'<div class=\"gray\" style=\"font-size:12px;\">上级：'") !== false
        && strpos($h, "o.seller_parent") !== false && strpos($h, "member/detail?id=' + o.seller_pid") !== false && strpos($h, '（已删除）') !== false, "HTTP $c");
} finally {
    $pdo->exec("delete from `order` where order_no like 'QAPARENT%'");
    $pdo->exec("delete from goods where seller_id in ($S1,$S2,$S3)");
    $pdo->exec("delete from user where mobile like '199999909%'");
    @unlink("$root/runtime/session/sess_$sa");
    echo "[cleanup] done\n";
}

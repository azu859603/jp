<?php
/**
 * 列表搜索条件与菜单位置
 *  - 自动出价：按拍品标题 / 卖家手机号搜索，列表带卖家手机号列
 *  - 订单列表：按订单号 / 商品名称 / 卖家手机号 / 买家手机号搜索
 *  - 充值 / 提现 / 余额流水：按手机号 / 昵称搜索（占位文案）
 *  - 菜单：数据报表紧跟数据中心（主后台）/ 团队概览（代理后台）
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,0,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($title, $sellerId) {
    global $pdo, $T;
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('$title','','','',1,$sellerId,100,10,0,0," . ($T - 3600) . "," . ($T + 86400 * 3) . ",1,0,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sess($id, $key = 'user') {
    global $root, $pdo;
    $sid = md5('ls' . $key . $id . microtime(true));
    $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC);
    unset($u['password']);
    file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u]));
    return $sid;
}
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function idsOf($j, $k = 'id') { return array_map(function ($x) use ($k) { return (int)$x[$k]; }, $j['data'] ?? []); }

$AG = mk('19999990490', 'QA搜索代理', 0, 1);
$S  = mk('19999990491', 'QA搜索卖家', $AG);
$B  = mk('19999990492', 'QA搜索买家');
$g  = mkGoods('QALS自动出价拍品', $S);
$pdo->exec("insert into auto_bid(goods_id,interval_min,max_price,stop_hours,status,stop_reason,next_time,last_time,bid_count,creator_type,creator_id,create_time,update_time) values($g,30,500,1,1,''," . ($T + 600) . ",0,0,'admin',1,$T,$T)");
$task = (int)$pdo->lastInsertId();
$pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,commission_rate,commission,seller_income,income_paid,deposit,pay_status,pay_time,order_status,ship_name,ship_mobile,ship_address,create_time,update_time) values('QALS0001',$g,'QALS订单商品','',$S,$B,100,10,10,90,0,0,1,$T,1,'QA','13900000000','QA',$T,$T)");
$order = (int)$pdo->lastInsertId();
$sa = sess(0, 'admin');
$sag = sess($AG);

try {
    echo "== 自动出价 ==\n";
    foreach ([['主后台', $sa, '/admin1314'], ['代理后台', $sag, '/agent']] as [$tag, $sid, $pre]) {
        [, , $j] = req($sid, "$pre/auto_bid/index?page=1&limit=50&keyword=19999990491");
        ok("$tag 按卖家手机号搜到任务，且返回 seller_mobile", in_array($task, idsOf($j)) && ($j['data'][0]['seller_mobile'] ?? '') === '19999990491', json_encode($j, JSON_UNESCAPED_UNICODE));
        [, , $j] = req($sid, "$pre/auto_bid/index?page=1&limit=50&keyword=QALS自动");
        ok("$tag 按拍品标题搜到任务", in_array($task, idsOf($j)), json_encode(idsOf($j)));
        [, , $j] = req($sid, "$pre/auto_bid/index?page=1&limit=50&keyword=$g");
        ok("$tag 纯拍品 ID 不再作为搜索条件", !in_array($task, idsOf($j)), json_encode(idsOf($j)));
        [$c, $html] = req($sid, "$pre/auto_bid/index", false);
        ok("$tag 页面：占位文案、卖家手机号列", $c == 200 && strpos($html, 'placeholder="拍品标题 / 卖家账号"') !== false && strpos($html, '<th>卖家账号</th>') !== false && strpos($html, "esc(t.seller_mobile || '-')") !== false && strpos($html, 'colspan="12"') !== false, "HTTP $c");
    }

    echo "== 订单列表 ==\n";
    foreach ([['主后台', $sa, '/admin1314', '订单号 / 商品名称 / 卖家账号 / 买家账号'], ['代理后台', $sag, '/agent', '订单号 / 商品名称 / 卖家账号 / 买家账号']] as [$tag, $sid, $pre, $ph]) {
        foreach (['QALS0001' => '订单号', 'QALS订单' => '商品名称', '19999990491' => '卖家手机号', '19999990492' => '买家手机号'] as $kw => $what) {
            [, , $j] = req($sid, "$pre/order/index?page=1&limit=50&keyword=" . urlencode($kw));
            ok("$tag 按{$what}搜到订单", in_array($order, idsOf($j)), json_encode(idsOf($j)));
        }
        [$c, $html] = req($sid, "$pre/order/index", false);
        ok("$tag 订单页占位文案", strpos($html, 'placeholder="' . $ph . '"') !== false, '');
    }

    echo "== 财务列表占位 ==\n";
    foreach ([['主后台', $sa, '/admin1314'], ['代理后台', $sag, '/agent']] as [$tag, $sid, $pre]) {
        foreach (['recharge', 'withdraw', 'balance'] as $mod) {
            [$c, $html] = req($sid, "$pre/$mod/index", false);
            ok("$tag $mod 占位为「手机号 / 昵称」", $c == 200 && strpos($html, 'placeholder="账号 / 昵称"') !== false, "HTTP $c");
        }
    }

    echo "== 菜单顺序 ==\n";
    [, $html] = req($sa, '/admin1314/index/index', false);
    $p1 = strpos($html, '数据中心'); $p2 = strpos($html, '数据报表'); $p3 = strpos($html, '会员管理');
    ok('主后台：数据报表在数据中心之后、会员管理之前', $p1 !== false && $p2 !== false && $p3 !== false && $p1 < $p2 && $p2 < $p3, "$p1 $p2 $p3");
    [, $html] = req($sag, '/agent/index/index', false);
    $p1 = strpos($html, '团队概览'); $p2 = strpos($html, '数据报表'); $p3 = strpos($html, '会员管理');
    ok('代理后台：数据报表在团队概览之后、会员管理之前', $p1 !== false && $p2 !== false && $p3 !== false && $p1 < $p2 && $p2 < $p3, "$p1 $p2 $p3");
} finally {
    $pdo->exec("delete from auto_bid where id=$task");
    $pdo->exec("delete from `order` where id=$order");
    $pdo->exec("delete from goods where id=$g");
    $pdo->exec("delete from user where id in ($AG,$S,$B)");
    foreach ([$sa, $sag] as $s) { @unlink("$root/runtime/session/sess_$s"); }
    echo "[cleanup] done\n";
}

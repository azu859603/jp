<?php
/**
 * 主后台 / 代理后台「出价记录」列表
 *  - 拍品后面新增「卖家账号」列（原样显示，后台要按它检索）
 *  - 原来的「账号」列改名「买家账号」，并做脱敏（手机号 138****8888、邮箱 ab***@x.com）
 *  - 搜索只按拍品标题 / 卖家账号，买家账号与买家昵称都不参与检索
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function req($sid, $p, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid,
        CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : ['Accept: text/html']]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, (string)$b, json_decode((string)$b, true)];
}
function mkUser($m, $nick, $pid = 0, $agent = 0, $email = null) {
    global $pdo, $T;
    $mob = $email ? 'NULL' : "'$m'";
    $em  = $email ? "'$email'" : 'NULL';
    $pdo->exec("insert into user(mobile,email,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,auth_status,create_time,update_time,reg_time) values($mob,$em,'x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($seller, $title) { global $pdo, $T; $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,bid_count,create_time,update_time) values($seller,1,'$title','','[]',100,10,0,1," . ($T - 3600) . "," . ($T + 86400) . ",0,$T,$T)"); return (int)$pdo->lastInsertId(); }
function mkBid($gid, $uid, $price) { global $pdo, $T; $pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($gid,$uid,$price,0,0,0,$T)"); return (int)$pdo->lastInsertId(); }
function row($j, $bidId) { foreach (($j['data'] ?? []) as $b) { if ((int)$b['id'] === (int)$bidId) return $b; } return null; }
function ids($j) { return array_map(function ($b) { return (int)$b['id']; }, $j['data'] ?? []); }

$agent  = mkUser('19999994601', 'QA出价代理', 0, 1);
$seller = mkUser('19999994602', 'QA出价卖家', $agent);
$buyer  = mkUser('19999994603', 'QA出价买家', $agent);
$buyerE = mkUser('19999994604', 'QA邮箱买家', $agent, 0, 'qabidbuyer@example.com');
$uids   = "$agent,$seller,$buyer,$buyerE";

$g   = mkGoods($seller, 'QBS出价记录拍品');
$b1  = mkBid($g, $buyer, 120);
$b2  = mkBid($g, $buyerE, 130);
$gids = "$g";

$asid = md5('qbsa' . $T);
$admin = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC);
unset($admin['password']);
file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $admin]));
$gsid = md5('qbsg' . $T);
$ag = $pdo->query("select * from user where id=$agent")->fetch(PDO::FETCH_ASSOC);
unset($ag['password']);
file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $ag]));

try {
    foreach ([['主后台', $asid, '/admin1314/bid/index'], ['代理后台', $gsid, '/agent/bid/index']] as [$name, $sid, $url]) {
        echo "== $name ==\n";
        [, , $j] = req($sid, $url . '?page=1&limit=50&keyword=QBS出价记录拍品');
        $r1 = row($j, $b1); $r2 = row($j, $b2);
        ok("$name 列表返回卖家账号", $r1 && ($r1['seller_mobile'] ?? '') === '19999994602', json_encode($r1, JSON_UNESCAPED_UNICODE));
        ok('  买家手机号脱敏为 199****4603', $r1 && $r1['mobile'] === '199****4603', $r1['mobile'] ?? 'null');
        ok('  买家邮箱脱敏为 qa***@example.com', $r2 && $r2['mobile'] === 'qa***@example.com', $r2['mobile'] ?? 'null');
        ok('  卖家账号不脱敏', $r1 && strpos($r1['seller_mobile'], '*') === false);

        echo "  -- 搜索 --\n";
        [, , $j] = req($sid, $url . '?page=1&limit=50&keyword=19999994602');
        ok('  按卖家账号能搜到', in_array($b1, ids($j)) && in_array($b2, ids($j)), json_encode(ids($j)));
        [, , $j] = req($sid, $url . '?page=1&limit=50&keyword=19999994603');
        ok('  按买家账号搜不到（已改为卖家账号检索）', !in_array($b1, ids($j)), json_encode(ids($j)));
        [, , $j] = req($sid, $url . '?page=1&limit=50&keyword=QA出价买家');
        ok('  按买家昵称搜不到（昵称也已从检索里去掉）', !in_array($b1, ids($j)), json_encode(ids($j)));
        [, , $j] = req($sid, $url . '?page=1&limit=50&keyword=QBS出价记录');
        ok('  按拍品标题仍能搜到', in_array($b1, ids($j)) && in_array($b2, ids($j)), json_encode(ids($j)));

        echo "  -- 页面 --\n";
        [$c, $h] = req($sid, $url, false);
        ok('  表头：拍品后面是卖家账号，账号列改名买家账号', $c == 200
            && (strpos($h, '<th>商品</th><th>卖家账号</th><th>出价人</th><th>买家账号</th>') !== false
                || strpos($h, '<th>拍品</th><th>卖家账号</th><th>出价人</th><th>买家账号</th>') !== false)
            && strpos($h, '<th>账号</th>') === false, "HTTP $c");
        ok('  搜索框提示只剩拍品标题与卖家账号', strpos($h, 'placeholder="拍品标题 / 卖家账号"') !== false);
        ok('  渲染了卖家账号单元格', strpos($h, "esc(b.seller_mobile || '-')") !== false);
        ok('  空状态列数跟着 +1', strpos($h, $name === '主后台' ? 'colspan="10"' : 'colspan="11"') !== false
            && strpos($h, $name === '主后台' ? 'colspan="9"' : 'colspan="10"') === false);
    }
} finally {
    $pdo->exec("delete from bid_record where goods_id in ($gids)");
    $pdo->exec("delete from goods where id in ($gids)");
    $pdo->exec("delete from user where id in ($uids)");
    @unlink("$root/runtime/session/sess_$asid");
    @unlink("$root/runtime/session/sess_$gsid");
    echo "[cleanup] done\n";
}

<?php
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 200)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function req($sid, $p) { $ch = curl_init('http://localhost' . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'], CURLOPT_COOKIE => 'PHPSESSID=' . $sid]); $b = curl_exec($ch); curl_close($ch); return json_decode($b, true); }
function ids($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
$AG = mk('19999990500', 'QA出价代理', 0, 1); $S = mk('19999990501', 'QA出价卖家', $AG); $B = mk('19999990502', 'QA出价买家昵称', $AG);
$pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('QABS出价拍品','','','',1,$S,100,10,0,0," . ($T - 3600) . "," . ($T + 86400) . ",1,1,0,$T,$T)"); $g = (int)$pdo->lastInsertId();
$pdo->exec("insert into bid_record(goods_id,user_id,price,deposit,status,is_winner,create_time) values($g,$B,100,0,0,0,$T)"); $bid = (int)$pdo->lastInsertId();
$sa = md5('bsa' . $T); $a = $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC); unset($a['password']); file_put_contents("$root/runtime/session/sess_$sa", serialize(['admin' => $a]));
$sg = md5('bsg' . $T); $u = $pdo->query("select * from user where id=$AG")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sg", serialize(['user' => $u]));
try {
    foreach ([['主后台', $sa, '/admin1314'], ['代理后台', $sg, '/agent']] as [$tag, $sid, $pre]) {
        foreach (['QABS出价' => '拍品标题', '19999990502' => '买家手机号', 'QA出价买家昵称' => '昵称'] as $kw => $what) {
            $j = req($sid, "$pre/bid/index?page=1&limit=50&keyword=" . urlencode($kw));
            ok("$tag 按{$what}搜到出价记录", in_array($bid, ids($j)), json_encode(ids($j)));
        }
        $ch = curl_init("http://localhost$pre/bid/index"); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]); $h = curl_exec($ch); curl_close($ch);
        ok("$tag 占位文案", strpos($h, 'placeholder="拍品标题 / 买家账号 / 昵称"') !== false, '');
    }
} finally {
    $pdo->exec("delete from bid_record where id=$bid"); $pdo->exec("delete from goods where id=$g"); $pdo->exec("delete from user where id in ($AG,$S,$B)");
    @unlink("$root/runtime/session/sess_$sa"); @unlink("$root/runtime/session/sess_$sg"); echo "[cleanup] done\n";
}

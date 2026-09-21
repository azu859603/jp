<?php
/**
 * 自动出价任务：平台自营自动出价开启时，「自营店铺」卖家（user.is_self_shop=1）的拍品
 * 由脚本统一出价，后台 / 代理后台都不能手动添加任务
 * 覆盖主后台与代理后台的拍品搜索过滤 + 服务端强校验
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mkUser($m, $nick, $pid = 0, $agent = 0, $selfShop = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,is_self_shop,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,0,1,1,$agent,0,$selfShop,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($title, $sellerId) {
    global $pdo, $T;
    $pdo->exec("insert into goods(title,cover,images,content,category_id,seller_id,start_price,raise_price,reserve_price,deposit,start_time,end_time,status,bid_count,view_count,create_time,update_time) values('$title','','','',1,$sellerId,100,10,0,0," . ($T - 3600) . "," . ($T + 86400 * 7) . ",1,0,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function req($sid, $m, $p, $d = null) {
    $head = ['X-Requested-With: XMLHttpRequest'];
    if ($m === 'POST') { $head[] = 'Origin: http://localhost'; $head[] = 'Referer: http://localhost/'; }
    $ch = curl_init('http://localhost' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_HTTPHEADER => $head, CURLOPT_COOKIE => 'PHPSESSID=' . $sid]);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d)); }
    $b = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function idsOf($j) { return array_map(function ($x) { return (int)$x['id']; }, $j['data'] ?? []); }
function taskCount($goodsId) { global $pdo; return (int)$pdo->query("select count(*) from auto_bid where goods_id=$goodsId")->fetchColumn(); }

// 需要一个启用中的虚拟会员，否则 add 会被「没有虚拟会员」挡住
$hasVirtual = (int)$pdo->query("select count(*) from user where is_virtual=1 and status=1")->fetchColumn();
$vid = 0;
if (!$hasVirtual) { $vid = mkUser('19999990380', 'QA虚拟'); $pdo->exec("update user set is_virtual=1 where id=$vid"); }

$agent   = mkUser('19999990381', 'QA自动出价代理', 0, 1);
$selfS   = mkUser('19999990383', 'QA自营店铺卖家', $agent, 0, 1);   // 店铺属性 = 自营店铺，且在代理团队下
$seller  = mkUser('19999990382', 'QA自动出价卖家', $agent);         // 非自营
$gPlat   = mkGoods('QAAB自营店铺拍品', $selfS);
$gNormal = mkGoods('QAAB普通卖家拍品', $seller);
$uids    = array_filter([$vid, $agent, $selfS, $seller]);
// 本测试验证的是「平台自营自动出价开启时」的限制：先打开开关，结束后还原
$origSw = $pdo->query("select value from setting where name='platform_auto_bid_enabled'")->fetchColumn();
$pdo->exec("insert into setting(name,value,create_time) values('platform_auto_bid_enabled','1',$T) on duplicate key update value='1'");

// 会话
$asid = md5('abadm' . $T);
$admin = $pdo->query("select * from admin_user order by id asc limit 1")->fetch(PDO::FETCH_ASSOC);
unset($admin['password']);
file_put_contents("$root/runtime/session/sess_$asid", serialize(['admin' => $admin]));
$gsid = md5('abagt' . $T);
$ag = $pdo->query("select * from user where id=$agent")->fetch(PDO::FETCH_ASSOC);
unset($ag['password']);
file_put_contents("$root/runtime/session/sess_$gsid", serialize(['user' => $ag]));

try {
    echo "== 主后台：拍品搜索 ==\n";
    [, , $j] = req($asid, 'GET', '/admin1314/bid/searchGoods?kw=QAAB');
    $r = idsOf($j);
    ok('不带 scene（添加出价场景）仍可搜到自营店铺卖家拍品', in_array($gPlat, $r) && in_array($gNormal, $r), json_encode($r));
    [, , $j] = req($asid, 'GET', '/admin1314/bid/searchGoods?scene=auto_bid&kw=QAAB');
    $r = idsOf($j);
    ok('自动出价场景搜不到自营店铺卖家拍品', !in_array($gPlat, $r) && in_array($gNormal, $r), json_encode($r));
    [, , $j] = req($asid, 'GET', '/admin1314/bid/searchGoods?scene=auto_bid&kw=' . $gPlat);
    ok('按拍品 ID 精确搜索也搜不到', !in_array($gPlat, idsOf($j)), json_encode(idsOf($j)));

    echo "== 主后台：添加任务 ==\n";
    $p = ['interval_min' => 10, 'max_price' => 5000, 'stop_hours' => 1];
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $gPlat] + $p);
    ok('自营店铺卖家拍品不能添加任务', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '自营店铺') !== false && taskCount($gPlat) === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $gNormal] + $p);
    ok('非自营卖家拍品可以添加任务', ($j['code'] ?? 0) == 1 && taskCount($gNormal) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("delete from auto_bid where goods_id=$gNormal");

    echo "== 代理后台：拍品搜索 ==\n";
    [, , $j] = req($gsid, 'GET', '/agent/bid/searchGoods?kw=QAAB');
    $r = idsOf($j);
    ok('代理端不带 scene 仍可搜到自营店铺卖家拍品', in_array($gPlat, $r) && in_array($gNormal, $r), json_encode($r));
    [, , $j] = req($gsid, 'GET', '/agent/bid/searchGoods?scene=auto_bid&kw=QAAB');
    $r = idsOf($j);
    ok('代理端自动出价场景搜不到自营店铺卖家拍品', !in_array($gPlat, $r) && in_array($gNormal, $r), json_encode($r));

    echo "== 代理后台：添加任务 ==\n";
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/add', ['goods_id' => $gPlat] + $p);
    ok('代理端自营店铺卖家拍品不能添加任务', ($j['code'] ?? 1) == 0 && strpos((string)($j['msg'] ?? ''), '自营店铺') !== false && taskCount($gPlat) === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($gsid, 'POST', '/agent/auto_bid/add', ['goods_id' => $gNormal] + $p);
    ok('代理端团队卖家拍品可以添加任务', ($j['code'] ?? 0) == 1 && taskCount($gNormal) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("delete from auto_bid where goods_id=$gNormal");

    echo "== 改回非自营店铺 ==\n";
    $pdo->exec("update user set is_self_shop=0 where id=$selfS");
    [, , $j] = req($asid, 'GET', '/admin1314/bid/searchGoods?scene=auto_bid&kw=QAAB');
    ok('改为非自营后又能搜到该卖家拍品', in_array($gPlat, idsOf($j)), json_encode(idsOf($j)));
    [, , $j] = req($asid, 'POST', '/admin1314/auto_bid/add', ['goods_id' => $gPlat] + $p);
    ok('改为非自营后可以添加任务', ($j['code'] ?? 0) == 1 && taskCount($gPlat) === 1, json_encode($j, JSON_UNESCAPED_UNICODE));
    $pdo->exec("delete from auto_bid where goods_id=$gPlat");

    echo "== 页面 ==\n";
    foreach ([['主后台', $asid, '/admin1314/auto_bid/index'], ['代理后台', $gsid, '/agent/auto_bid/index']] as [$name, $sid, $url]) {
        $ch = curl_init('http://localhost' . $url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIE => 'PHPSESSID=' . $sid, CURLOPT_HTTPHEADER => ['Accept: text/html']]);
        $b = curl_exec($ch);
        curl_close($ch);
        ok("$name 页面选择器带 scene=auto_bid 且提示改为「自营店铺」", strpos($b, "bid/searchGoods?scene=auto_bid") !== false && strpos($b, '「自营店铺」卖家的拍品由平台脚本出价，不能添加') !== false, mb_substr((string)$b, 0, 150));
    }
} finally {
    if ($origSw === false) { $pdo->exec("delete from setting where name='platform_auto_bid_enabled'"); } else { $pdo->exec("update setting set value='" . $origSw . "' where name='platform_auto_bid_enabled'"); }
    $pdo->exec("delete from auto_bid where goods_id in ($gPlat,$gNormal)");
    $pdo->exec("delete from goods where id in ($gPlat,$gNormal)");
    if ($uids) { $in = implode(',', $uids); $pdo->exec("delete from user where id in ($in)"); }
    @unlink("$root/runtime/session/sess_$asid");
    @unlink("$root/runtime/session/sess_$gsid");
    echo "[cleanup] done\n";
}

<?php
/**
 * php think shop:self-assign：把某个卖家的商品平均分配给「自营店铺」会员（只动商品，不建会员）
 *  - 演练模式不改任何数据；--force 才分配
 *  - 默认目标 = 库里全部自营店铺会员；源卖家自己是自营时也占一个名额，留下自己那一份
 *  - --exclude-from 源卖家不留货；--to 指定目标；--include-sold 连已成交的一起转并同步订单卖家
 *  - 按状态分组轮流发，各店铺件数最多差 1；默认跳过有订单 / 得标人的商品
 * 关键隔离：脚本默认取库里全部自营店铺会员，测试期间先把真实的自营会员临时置 0，finally 还原，
 * 否则会把测试商品分给会员 1 等真实店铺。
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function run($args) { global $php, $root; return (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think shop:self-assign ' . $args . ' 2>&1'); }
function mkUser($m, $nick, $selfShop = 0) {
    global $pdo, $T;
    $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,is_self_shop,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,1,1,0,0,$selfShop,2,$T,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function mkGoods($seller, $title, $status, $orderId = 0, $winner = 0) {
    global $pdo, $T;
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,order_id,winner_id,bid_count,create_time,update_time) values($seller,1,'$title','','[]',100,10,0,$status," . ($T - 3600) . "," . ($T + 86400) . ",$orderId,$winner,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sellerOf($gid) { global $pdo; return (int)$pdo->query("select seller_id from goods where id=$gid")->fetchColumn(); }
function held($uid) { global $pdo; return (int)$pdo->query("select count(*) from goods where seller_id=$uid and title like 'QSA%'")->fetchColumn(); }
function heldSt($uid, $st) { global $pdo; return (int)$pdo->query("select count(*) from goods where seller_id=$uid and status=$st and title like 'QSA%'")->fetchColumn(); }

$src = mkUser('19999994201', 'QA分配源卖家', 1);     // 源卖家自己也是自营店铺
$s1  = mkUser('19999994202', 'QA店铺甲', 1);
$s2  = mkUser('19999994203', 'QA店铺乙', 1);
$out = mkUser('19999994204', 'QA非自营店', 0);
$buy = mkUser('19999994205', 'QA分配买家', 0);
$uids = "$src,$s1,$s2,$out,$buy";

// 隔离真实的自营店铺会员
$realSelf = $pdo->query('select id from user where is_self_shop=1')->fetchAll(PDO::FETCH_COLUMN);
$realSelf = array_values(array_diff(array_map('intval', $realSelf), [$src, $s1, $s2]));
if ($realSelf) { $pdo->exec('update user set is_self_shop=0 where id in (' . implode(',', $realSelf) . ')'); }

try {
    echo "== 参数校验 ==\n";
    ok('不带 --from 被拒', strpos(run('--force'), '请用 --from 指定源卖家') !== false);
    ok('源卖家不存在被拒', strpos(run('--from=99999999 --force'), '不存在') !== false);
    ok('--to 里会员不存在被拒', strpos(run("--from=$src --to=99999999 --force"), '这些会员不存在') !== false);
    $pdo->exec("update user set is_self_shop=0 where id in ($src,$s1,$s2)");
    ok('一个自营店铺会员都没有时提示先建店铺', strpos(run("--from=$src --force"), 'shop:self-create') !== false);
    $pdo->exec("update user set is_self_shop=1 where id in ($src,$s1,$s2)");
    ok('源卖家没有商品时直接退出', strpos(run("--from=$src --force"), '没有可分配的商品') !== false);

    echo "== 商品准备 ==\n";
    $g = [];
    for ($i = 1; $i <= 6; $i++) { $g[] = mkGoods($src, "QSA拍卖中$i", 1); }
    for ($i = 1; $i <= 6; $i++) { $g[] = mkGoods($src, "QSA流拍$i", 3); }
    $pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,pay_status,order_status,create_time,update_time) values('QSA$T',0,'QSA已成交','',$src,$buy,100,1,1,$T,$T)");
    $orderId = (int)$pdo->lastInsertId();
    $gSold   = mkGoods($src, 'QSA已成交', 2, $orderId, $buy);
    $pdo->exec("update `order` set goods_id=$gSold where id=$orderId");
    ok('源卖家 12 件未成交 + 1 件已成交', held($src) === 13);

    echo "== 演练模式 ==\n";
    $o = run("--from=$src");
    ok('演练打印统计与分配预览', strpos($o, '拍卖中：6 件') !== false && strpos($o, '流拍：6 件') !== false
        && strpos($o, '分配目标：3 个店铺（库里全部「自营店铺」会员）') !== false
        && strpos($o, '本次分配：12 件 → 3 个店铺') !== false, $o);
    ok('  提示跳过已成交 1 件', strpos($o, '已跳过已成交（有订单 / 得标人）1 件') !== false, $o);
    ok('  标出源卖家那一行', strpos($o, '[源卖家，留在自己名下]') !== false && strpos($o, '含源卖家自己') !== false, $o);
    ok('  演练不动商品', strpos($o, '演练模式') !== false && held($src) === 13 && held($s1) === 0 && held($s2) === 0);

    echo "== 默认目标：全部自营店铺（含源卖家） ==\n";
    $o = run("--from=$src --force");
    ok('12 件平分给 3 个店铺，每店拍卖中 2 + 流拍 2', heldSt($src, 1) === 2 && heldSt($src, 3) === 2
        && heldSt($s1, 1) === 2 && heldSt($s1, 3) === 2 && heldSt($s2, 1) === 2 && heldSt($s2, 3) === 2,
        json_encode([held($src), held($s1), held($s2)]));
    ok('  源卖家自己留下 4 件 + 那件已成交的', held($src) === 5 && sellerOf($gSold) === $src);
    ok('  非自营店铺没分到', held($out) === 0);
    ok('  订单卖家未被改动', (int)$pdo->query("select seller_id from `order` where id=$orderId")->fetchColumn() === $src);
    ok('  写了操作日志与脚本日志', (int)$pdo->query("select count(*) from admin_log where action like '%把会员 $src 的 12 件商品平均分配给 3 个自营店铺（含源卖家自己）%'")->fetchColumn() === 1
        && strpos((string)@file_get_contents("$root/runtime/log/self_shop.log"), "把会员 $src 的 12 件商品") !== false);
    ok('  只动商品，没建会员', (int)$pdo->query("select count(*) from user where mobile like '199999942%'")->fetchColumn() === 5);

    echo "== --exclude-from：源卖家不留货 ==\n";
    $o = run("--from=$src --force --exclude-from");
    ok('源卖家名下的 4 件全部转走，只剩已成交那件', strpos($o, '源卖家自己不参与') !== false && held($src) === 1 && sellerOf($gSold) === $src, $o);
    ok('  两个店铺各拿到 2 件，累计各 6 件', held($s1) === 6 && held($s2) === 6, json_encode([held($s1), held($s2)]));

    echo "== --to 指定目标 ==\n";
    $o = run("--from=$s1 --to=$out --force");
    ok('只分给 --to 指定的会员，即使它不是自营店铺', strpos($o, '不是「自营店铺」') !== false && held($out) === 6 && held($s1) === 0, $o);

    echo "== --include-sold ==\n";
    $o = run("--from=$src --to=$s2 --force --include-sold");
    ok('已成交的商品也被转移', strpos($o, '也会一起转移') !== false && sellerOf($gSold) === $s2 && held($src) === 0, $o);
    ok('  订单的卖家同步更新', (int)$pdo->query("select seller_id from `order` where id=$orderId")->fetchColumn() === $s2);

    echo "== 余数分配 ==\n";
    $extra = [];
    for ($i = 1; $i <= 7; $i++) { $extra[] = mkGoods($src, "QSA余数$i", 3); }
    $o = run("--from=$src --to=$s1,$out --force");
    ok('7 件分给 2 个店铺 = 4 + 3', ((held($s1) === 4 && held($out) === 6 + 3) || (held($s1) === 3 && held($out) === 6 + 4)), json_encode([held($s1), held($out)]));
} finally {
    if ($realSelf) { $pdo->exec('update user set is_self_shop=1 where id in (' . implode(',', $realSelf) . ')'); }   // 还原真实的自营会员
    if (isset($orderId)) { $pdo->exec("delete from `order` where id=$orderId"); }
    $pdo->exec("delete from goods where title like 'QSA%'");
    $pdo->exec("delete from admin_log where action like '%平均分配给%自营店铺%' and create_time>=$T");
    $pdo->exec("delete from user where id in ($uids)");
    echo "[cleanup] done，真实的自营店铺会员已还原\n";
}

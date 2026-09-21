<?php
/**
 * php think shop:self-split：批量创建「自营店铺」卖家并平分某个卖家的商品
 *  - 演练模式不改任何数据；--force 才执行
 *  - 新会员属性：自营店铺 / 卖家 / 免审核 / 非虚拟 / 已实名 / 店铺名 = 昵称 / 密码可校验
 *  - 商品按状态分组轮流分配，各店铺件数最多差 1
 *  - 默认跳过有订单或有得标人的商品，--include-sold 才一起转并同步订单卖家
 *  - 参数校验：数量 / 密码 / 源卖家 / 上级 / 账号类型
 * 全程只用测试自建的源卖家，不碰会员 1 的真实商品。
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();
$PRE  = '19999995';   // 新店铺的手机号前缀，清理时按它删

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function run($args) { global $php, $root; return (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think shop:self-split ' . $args . ' 2>&1'); }
function mkUser($m, $nick) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,is_self_shop,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',0,1,0,1,1,0,0,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function mkGoods($seller, $title, $status, $orderId = 0, $winner = 0) {
    global $pdo, $T;
    $pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,status,start_time,end_time,order_id,winner_id,bid_count,create_time,update_time) values($seller,1,'$title','','[]',100,10,0,$status," . ($T - 3600) . "," . ($T + 86400) . ",$orderId,$winner,0,$T,$T)");
    return (int)$pdo->lastInsertId();
}
function sellerOf($gid) { global $pdo; return (int)$pdo->query("select seller_id from goods where id=$gid")->fetchColumn(); }
function shops() { global $pdo, $PRE; return $pdo->query("select * from user where mobile like '$PRE%' order by id asc")->fetchAll(PDO::FETCH_ASSOC); }

$src   = mkUser('19999994001', 'QA拆分源卖家');
$buyer = mkUser('19999994002', 'QA拆分买家');
$gids  = [];
for ($i = 1; $i <= 6; $i++) { $gids[] = mkGoods($src, "QASS拍卖中$i", 1); }
for ($i = 1; $i <= 6; $i++) { $gids[] = mkGoods($src, "QASS流拍$i", 3); }
// 已成交的一件：有订单也有得标人
$pdo->exec("insert into `order`(order_no,goods_id,goods_title,goods_cover,seller_id,buyer_id,price,pay_status,order_status,create_time,update_time) values('QASS$T',0,'QASS已成交','',$src,$buyer,100,1,1,$T,$T)");
$orderId = (int)$pdo->lastInsertId();
$gSold   = mkGoods($src, 'QASS已成交', 2, $orderId, $buyer);
$gids[]  = $gSold;
$pdo->exec("update `order` set goods_id=$gSold where id=$orderId");
$in = implode(',', $gids);

try {
    echo "== 参数校验 ==\n";
    ok('不带 --from 的 --force 被拒（防止误用默认源卖家 1）', strpos(run('--force'), '必须显式指定源卖家') !== false);
    ok('数量超范围被拒', strpos(run("--from=$src --count=0 --force"), '店铺数量需为') !== false);
    ok('密码太短被拒', strpos(run("--from=$src --password=123 --force"), '密码至少 6 位') !== false);
    ok('源卖家不存在被拒', strpos(run('--from=99999999 --force'), '不存在') !== false);
    ok('上级不存在被拒', strpos(run("--from=$src --pid=99999999 --force"), '上级会员') !== false);
    ok('账号类型非法被拒', strpos(run("--from=$src --account=qq --force"), '只能是 mobile 或 email') !== false);
    ok('  以上都没建会员、没动商品', count(shops()) === 0 && sellerOf($gids[0]) === $src, count(shops()) . ' / ' . sellerOf($gids[0]));

    echo "== 演练模式 ==\n";
    $o = run("--from=$src --count=3 --account=mobile --mobile-prefix=$PRE");
    ok('演练输出统计与分配预览', strpos($o, '拍卖中：6 件') !== false && strpos($o, '流拍：6 件') !== false && strpos($o, '本次分配：12 件 → 3 个自营店铺') !== false, $o);
    ok('  提示跳过已成交 1 件', strpos($o, '已跳过已成交（有订单 / 得标人）1 件') !== false, $o);
    ok('  演练不建会员、不动商品', strpos($o, '演练模式') !== false && count(shops()) === 0 && sellerOf($gids[0]) === $src, $o);

    echo "== 正式执行 ==\n";
    $o = run("--from=$src --count=3 --account=mobile --mobile-prefix=$PRE --password=qa123456 --force");
    $list = shops();
    ok('创建 3 个店铺会员', count($list) === 3, $o);
    $attrOk = true;
    foreach ($list as $u) {
        if ((int)$u['is_self_shop'] !== 1 || (int)$u['is_seller'] !== 1 || (int)$u['seller_check'] !== 1
            || (int)$u['is_virtual'] !== 0 || (int)$u['status'] !== 1 || (int)$u['auth_status'] !== 2
            || (int)$u['pid'] !== 0 || $u['shop_name'] !== $u['nickname'] || $u['invite_code'] === ''
            || !password_verify('qa123456', $u['password'])) { $attrOk = false; }
    }
    ok('  属性正确：自营店铺 / 卖家免审核 / 非虚拟 / 已实名 / 店铺名=昵称 / 密码可校验', $attrOk, json_encode($list[0] ?? null, JSON_UNESCAPED_UNICODE));
    $nicks = array_column($list, 'nickname');
    ok('  昵称是中文且互不重复', count(array_unique($nicks)) === 3 && !preg_match('/^[a-z0-9@.]+$/i', $nicks[0]), implode(' / ', $nicks));
    $accs = array_column($list, 'mobile');
    ok('  账号是手机号、按前缀生成且不重复', count(array_unique($accs)) === 3 && strpos($accs[0], $PRE) === 0 && strlen($accs[0]) === 11, implode(' / ', $accs));

    echo "== 分配结果 ==\n";
    $shopIds = array_map('intval', array_column($list, 'id'));
    $cnt = [];
    foreach ($shopIds as $sid) {
        $cnt[$sid] = [
            1 => (int)$pdo->query("select count(*) from goods where seller_id=$sid and status=1 and id in ($in)")->fetchColumn(),
            3 => (int)$pdo->query("select count(*) from goods where seller_id=$sid and status=3 and id in ($in)")->fetchColumn(),
        ];
    }
    $allFour = true; foreach ($cnt as $c) { if ($c[1] !== 2 || $c[3] !== 2) $allFour = false; }
    ok('12 件平分给 3 个店铺，每店拍卖中 2 件 + 流拍 2 件', $allFour, json_encode($cnt));
    ok('  源卖家只剩已成交那 1 件', (int)$pdo->query("select count(*) from goods where seller_id=$src")->fetchColumn() === 1 && sellerOf($gSold) === $src);
    ok('  订单卖家未被改动', (int)$pdo->query("select seller_id from `order` where id=$orderId")->fetchColumn() === $src);
    ok('  写了操作日志与脚本日志', (int)$pdo->query("select count(*) from admin_log where action like '%批量创建自营店铺卖家 3 个，把会员 $src 的 12 件商品%'")->fetchColumn() === 1
        && strpos((string)@file_get_contents("$root/runtime/log/self_shop_split.log"), "把会员 $src 的 12 件商品") !== false);
    ok('  输出里带登录密码与每店件数', strpos($o, '登录密码 qa123456') !== false && strpos($o, '4 件（拍卖中 2，流拍 2）') !== false, $o);

    echo "== 没有可分配的商品 ==\n";
    $o = run("--from=$src --count=2 --account=mobile --mobile-prefix=$PRE --force");
    ok('源卖家没有可分配商品时直接退出、不建会员', strpos($o, '没有可分配的商品') !== false && count(shops()) === 3, $o);

    echo "== --include-sold ==\n";
    $o = run("--from=$src --count=1 --account=mobile --mobile-prefix=$PRE --force --include-sold");
    $newShop = (int)$pdo->query("select id from user where mobile like '$PRE%' order by id desc limit 1")->fetchColumn();
    ok('已成交的商品也被转移', strpos($o, '也会一起转移') !== false && sellerOf($gSold) === $newShop && count(shops()) === 4, $o);
    ok('  订单的卖家同步更新', (int)$pdo->query("select seller_id from `order` where id=$orderId")->fetchColumn() === $newShop);

    echo "== 上级与昵称前缀 ==\n";
    $g2 = mkGoods($src, 'QASS补充1', 3); $g3 = mkGoods($src, 'QASS补充2', 3);
    $o = run("--from=$src --count=2 --account=mobile --mobile-prefix=$PRE --force --pid=$buyer --prefix=自营店");
    $last = $pdo->query("select * from user where mobile like '$PRE%' order by id desc limit 2")->fetchAll(PDO::FETCH_ASSOC);
    $pidOk = true; $nickOk = true;
    foreach ($last as $u) { if ((int)$u['pid'] !== $buyer) $pidOk = false; if (strpos($u['nickname'], '自营店') !== 0) $nickOk = false; }
    ok('--pid 生效：新店铺挂到指定上级下', $pidOk, json_encode(array_column($last, 'pid')));
    ok('--prefix 生效：昵称用前缀+序号', $nickOk && $last[0]['shop_name'] === $last[0]['nickname'], json_encode(array_column($last, 'nickname'), JSON_UNESCAPED_UNICODE));
    $pdo->exec("delete from goods where id in ($g2,$g3)");
    echo "== --include-from：源卖家也算一个店铺 ==
";
    $fg = [];
    for ($i = 1; $i <= 3; $i++) { $fg[] = mkGoods($src, "QASS含源拍卖中$i", 1); }
    for ($i = 1; $i <= 3; $i++) { $fg[] = mkGoods($src, "QASS含源流拍$i", 3); }
    $fin = implode(',', $fg);
    $pdo->exec("update user set is_self_shop=0 where id=$src");
    $before = count(shops());
    $o = run("--from=$src --count=3 --account=mobile --mobile-prefix=$PRE --force --include-from");
    ok('只新建 2 个店铺（3 个名额里源卖家占 1 个）', strpos($o, '含源卖家会员 ' . $src . ' 自己，只新建 2 个') !== false && count(shops()) === $before + 2, $o);
    ok('  源卖家自己留下 2 件（在拍 1 + 流拍 1）', (int)$pdo->query("select count(*) from goods where seller_id=$src and id in ($fin)")->fetchColumn() === 2
        && (int)$pdo->query("select count(*) from goods where seller_id=$src and status=1 and id in ($fin)")->fetchColumn() === 1,
        (string)$pdo->query("select group_concat(concat(id,':',status)) from goods where seller_id=$src")->fetchColumn());
    $two = array_slice(shops(), -2);
    $eachTwo = true;
    foreach ($two as $u) { if ((int)$pdo->query("select count(*) from goods where seller_id={$u['id']} and id in ($fin)")->fetchColumn() !== 2) $eachTwo = false; }
    ok('  另外两个新店铺各 2 件', $eachTwo, json_encode(array_column($two, 'id')));
    ok('  源卖家被一并设为自营店铺', (int)$pdo->query("select is_self_shop from user where id=$src")->fetchColumn() === 1);
    ok('  输出里标出源卖家那一行', strpos($o, '[源卖家，留在自己名下]') !== false && strpos($o, '（含源卖家自己）') !== false, $o);
} finally {
    $shopIds = array_map('intval', array_column(shops(), 'id'));
    $allIds  = array_merge([$src, $buyer], $shopIds);
    $pdo->exec("delete from `order` where id=$orderId");
    $pdo->exec("delete from goods where title like 'QASS%'");
    $pdo->exec("delete from admin_log where action like '%批量创建自营店铺卖家%' and create_time>=$T");
    $pdo->exec("delete from user where id in (" . implode(',', $allIds) . ")");
    echo "[cleanup] done，已删除测试卖家 / 买家 / " . count($shopIds) . " 个店铺会员与全部 QASS 商品\n";
}

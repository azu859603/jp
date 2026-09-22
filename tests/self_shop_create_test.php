<?php
/**
 * php think shop:self-create：批量创建「自营店铺」卖家会员（只建会员，不动商品）
 *  - 演练模式不改任何数据；--force 才创建
 *  - 属性：自营店铺 / 卖家免审核 / 非虚拟 / 已实名 / 店铺名 = 昵称 / 密码可校验 / 余额 0
 *  - 昵称与账号都不重复；--prefix、--pid、--account、--mobile-prefix 生效
 *  - 参数校验：数量 / 密码 / 上级 / 账号类型
 *  - 只建会员，不碰任何商品
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo  = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T    = time();
$PRE  = '19999996';   // 新店铺的手机号前缀，清理时按它删

function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 400)) . "\n"; }
function run($args) { global $php, $root; return (string)shell_exec('cd /d ' . str_replace('/', '\\', $root) . ' && "' . $php . '" think shop:self-create ' . $args . ' 2>&1'); }
function shops() { global $pdo, $PRE; return $pdo->query("select * from user where mobile like '$PRE%' order by id asc")->fetchAll(PDO::FETCH_ASSOC); }

$up = $pdo->query("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,is_self_shop,auth_status,create_time,update_time,reg_time) values('19999994101','x','QA建店上级','994101',0,1,0,0,0,1,0,0,2,$T,$T,$T)");
$up = (int)$pdo->lastInsertId();
$goodsBefore = (int)$pdo->query("select count(*) from goods")->fetchColumn();

try {
    echo "== 参数校验 ==\n";
    ok('数量为 0 被拒（不会落回默认 30）', strpos(run('--count=0 --force'), '创建数量需为') !== false);
    ok('数量超上限被拒', strpos(run('--count=201 --force'), '创建数量需为') !== false);
    ok('密码太短被拒', strpos(run('--password=123 --force'), '密码至少 6 位') !== false);
    ok('上级不存在被拒', strpos(run('--pid=99999999 --force'), '上级会员') !== false);
    ok('账号类型非法被拒', strpos(run('--account=qq --force'), '只能是 mobile 或 email') !== false);
    ok('手机号前缀非法被拒', strpos(run('--mobile-prefix=abc --force'), '手机号前缀') !== false);
    ok('  以上都没建会员', count(shops()) === 0, count(shops()));

    echo "== 演练模式 ==\n";
    $o = run("--count=3 --account=mobile --mobile-prefix=$PRE");
    ok('演练打印将要创建的账号与昵称', strpos($o, '本次创建：3 个自营店铺卖家') !== false && strpos($o, '演练模式') !== false && preg_match_all('/' . $PRE . '\d{3}/', $o) === 3, $o);
    ok('  演练不建会员', count(shops()) === 0);

    echo "== 正式创建 ==\n";
    $o = run("--count=3 --account=mobile --mobile-prefix=$PRE --password=qa123456 --force");
    $list = shops();
    ok('创建 3 个会员', count($list) === 3, $o);
    $attrOk = true;
    foreach ($list as $u) {
        if ((int)$u['is_self_shop'] !== 1 || (int)$u['is_seller'] !== 1 || (int)$u['seller_check'] !== 1
            || (int)$u['is_virtual'] !== 0 || (int)$u['status'] !== 1 || (int)$u['auth_status'] !== 2
            || (int)$u['pid'] !== 0 || (float)$u['balance'] != 0 || $u['shop_name'] !== $u['nickname']
            || $u['invite_code'] === '' || !password_verify('qa123456', $u['password'])) { $attrOk = false; }
    }
    ok('  属性正确：自营店铺 / 卖家免审核 / 非虚拟 / 已实名 / 店铺名=昵称 / 余额 0 / 密码可校验', $attrOk, json_encode($list[0] ?? null, JSON_UNESCAPED_UNICODE));
    $nicks = array_column($list, 'nickname');
    ok('  昵称是中文且互不重复', count(array_unique($nicks)) === 3 && !preg_match('/^[a-z0-9@.]+$/i', $nicks[0]), implode(' / ', $nicks));
    $accs = array_column($list, 'mobile');
    ok('  账号按前缀生成、11 位且不重复', count(array_unique($accs)) === 3 && strpos($accs[0], $PRE) === 0 && strlen($accs[0]) === 11, implode(' / ', $accs));
    $codes = array_column($list, 'invite_code');
    ok('  邀请码不重复', count(array_unique($codes)) === 3, implode(' / ', $codes));
    ok('  只建会员，没碰商品', (int)$pdo->query("select count(*) from goods")->fetchColumn() === $goodsBefore);
    ok('  写了操作日志与脚本日志', (int)$pdo->query("select count(*) from admin_log where action like '%批量创建自营店铺卖家 3 个%' and create_time>=$T")->fetchColumn() >= 1
        && strpos((string)@file_get_contents("$root/runtime/log/self_shop.log"), '批量创建自营店铺卖家 3 个') !== false);
    ok('  输出带密码与后续命令提示', strpos($o, '登录密码 qa123456') !== false && strpos($o, 'shop:self-assign') !== false, $o);

    echo "== --prefix / --pid / --account=email ==\n";
    $o = run("--count=2 --account=mobile --mobile-prefix=$PRE --force --prefix=自营店 --pid=$up");
    $last = $pdo->query("select * from user where mobile like '$PRE%' order by id desc limit 2")->fetchAll(PDO::FETCH_ASSOC);
    $pidOk = true; $nickOk = true;
    foreach ($last as $u) { if ((int)$u['pid'] !== $up) $pidOk = false; if (strpos($u['nickname'], '自营店') !== 0) $nickOk = false; }
    ok('--pid 生效', $pidOk, json_encode(array_column($last, 'pid')));
    ok('--prefix 生效，店铺名同步', $nickOk && $last[0]['shop_name'] === $last[0]['nickname'], json_encode(array_column($last, 'nickname'), JSON_UNESCAPED_UNICODE));
    $o = run('--count=2 --account=email');
    ok('--account=email 生成邮箱账号', strpos($o, '账号类型：邮箱') !== false && substr_count($o, '@self.local') === 2, $o);
    ok('  库里累计 5 个店铺会员', count(shops()) === 5, count(shops()));
} finally {
    $ids = array_map('intval', array_column(shops(), 'id'));
    $ids[] = $up;
    $pdo->exec("delete from admin_log where action like '%批量创建自营店铺卖家%' and create_time>=$T");
    $pdo->exec("delete from user where id in (" . implode(',', $ids) . ")");
    echo "[cleanup] done，已删除 " . (count($ids) - 1) . " 个店铺会员与测试上级\n";
}

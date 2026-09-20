<?php
/**
 * php think user:rename-virtual：把「用户」开头的虚拟会员昵称改成中文昵称
 *  - 只改 is_virtual=1 的会员；真实会员默认昵称也是「用户+手机号后4位」，必须原样不动
 *  - 不加 --force 只演练，不改数据；--limit 限制条数；--all 覆盖全部虚拟会员
 *  - 新昵称与库里已有虚拟会员昵称不重复
 * 注意：本脚本会先备份库里所有虚拟会员的昵称，跑完原样还原，不影响现有数据。
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$php  = 'D:/phpstudy_pro/Extensions/php/php8.0.2nts/php.exe';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function run($args = '') { global $php, $root; return trim((string)shell_exec("\"$php\" \"$root/think\" user:rename-virtual $args 2>&1")); }
function nick($id) { global $pdo; return (string)$pdo->query("select nickname from user where id=$id")->fetchColumn(); }
function mk($mobile, $nickname, $virtual) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$mobile','x','$nickname','" . substr($mobile, -6) . "',0,1,0,0,0,0,$virtual,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
$isCn = fn($s) => (bool)preg_match('/^[\x{4e00}-\x{9fa5}]{2,10}$/u', $s);

// 备份所有虚拟会员昵称，结束时原样还原
$snap = $pdo->query('select id,nickname from user where is_virtual=1')->fetchAll(PDO::FETCH_KEY_PAIR);
$pdo->exec("delete from user where mobile like '199999908%'");
$V = [];
foreach (['19999990801' => '用户1111', '19999990802' => '用户2222', '19999990803' => '用户3333'] as $m => $n) { $V[] = mk($m, $n, 1); }
$VCN  = mk('19999990804', '云水测试斋', 1);      // 虚拟会员，昵称已是中文，默认模式下不该被改
$REAL = mk('19999990805', '用户5555', 0);        // 真实会员，昵称也是「用户」开头，绝不能动
$logFile = "$root/runtime/log/rename_virtual.log";
$logBefore = is_file($logFile) ? filesize($logFile) : 0;
try {
    echo "== 演练（不加 --force）==\n";
    $out = run();
    $cntUser = (int)$pdo->query("select count(*) from user where is_virtual=1 and nickname like '用户%'")->fetchColumn();
    ok('演练列出待改数量并提示加 --force', strpos($out, '符合条件的虚拟会员：' . $cntUser . ' 个') !== false && strpos($out, '演练模式') !== false, $out);
    ok('演练不修改任何数据', nick($V[0]) === '用户1111' && nick($V[1]) === '用户2222' && nick($REAL) === '用户5555');
    $outAll = run('--all');
    $cntAll = (int)$pdo->query("select count(*) from user where is_virtual=1")->fetchColumn();
    ok('--all 演练覆盖全部虚拟会员（含已是中文的）', strpos($outAll, '符合条件的虚拟会员：' . $cntAll . ' 个') !== false && $cntAll > $cntUser, $outAll);

    echo "== 执行 --force ==\n";
    $out = run('--force');
    $n0 = nick($V[0]); $n1 = nick($V[1]); $n2 = nick($V[2]);
    ok('三个「用户」开头的虚拟会员都改成了中文昵称', $isCn($n0) && $isCn($n1) && $isCn($n2) && strpos($n0, '用户') !== 0 && strpos($n1, '用户') !== 0 && strpos($n2, '用户') !== 0, "$n0 / $n1 / $n2");
    ok('真实会员的「用户5555」原样不动', nick($REAL) === '用户5555', nick($REAL));
    ok('已是中文的虚拟会员不被波及', nick($VCN) === '云水测试斋', nick($VCN));
    ok('输出含完成提示与示例', strpos($out, '完成：已改名') !== false && strpos($out, '用户1111 → ') !== false, $out);
    ok('全库虚拟会员昵称无重复', (int)$pdo->query('select count(*) from (select nickname from user where is_virtual=1 group by nickname having count(*)>1) t')->fetchColumn() === 0);
    clearstatcache(true, $logFile);   // filesize() 在同一进程内有缓存，比对前先清掉
    ok('已写改名日志', is_file($logFile) && filesize($logFile) > $logBefore);
    $out2 = run();
    ok('再次演练时已无「用户」开头的虚拟会员', strpos($out2, '没有「用户」开头的虚拟会员昵称') !== false, $out2);

    echo "== --limit ==\n";
    $L1 = mk('19999990806', '用户6666', 1);
    $L2 = mk('19999990807', '用户7777', 1);
    $out = run('--force --limit=1');
    $changed = (nick($L1) !== '用户6666' ? 1 : 0) + (nick($L2) !== '用户7777' ? 1 : 0);
    ok('--limit=1 只改 1 个', $changed === 1 && strpos($out, '本次处理 1 个') !== false, $out . ' changed=' . $changed);
    run('--force');
    ok('再次执行把剩下的也改完', $isCn(nick($L1)) && $isCn(nick($L2)), nick($L1) . ' / ' . nick($L2));

    echo "== --all ==\n";
    $before = nick($VCN);
    run('--force --all --limit=' . (int)$pdo->query('select count(*) from user where is_virtual=1')->fetchColumn());
    ok('--all 会把已是中文的虚拟会员也换一个新昵称', nick($VCN) !== $before && $isCn(nick($VCN)), $before . ' → ' . nick($VCN));
    ok('--all 之后真实会员依旧不动', nick($REAL) === '用户5555', nick($REAL));
    ok('--all 之后仍无重复昵称', (int)$pdo->query('select count(*) from (select nickname from user where is_virtual=1 group by nickname having count(*)>1) t')->fetchColumn() === 0);
} finally {
    $pdo->exec("delete from user where mobile like '199999908%'");
    $st = $pdo->prepare('update user set nickname=? where id=?');
    foreach ($snap as $id => $nk) { $st->execute([$nk, $id]); }   // 还原现有虚拟会员的昵称
    echo "[cleanup] done（已还原 " . count($snap) . " 个现有虚拟会员的昵称）\n";
}

<?php
/**
 * 代理后台操作日志（agent_log 表 + 财务管理 → 操作日志 页面）：
 *  - 代理的写操作（禁用/启用、编辑、余额调整、产品浏览量、拍卖时间、审核等）都写入 agent_log，内容含会员手机号/产品名
 *  - 代理后台不提供日志页面；主后台「系统设置 → 代理日志」可按代理 / 内容筛选查看
 *  - 代理「完成支付」不再写到管理员日志表
 */
$root = 'D:/phpstudy_pro/WWW/jp';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=jp;charset=utf8mb4', 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$T = time();
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function mk($m, $nick, $pid = 0, $agent = 0, $seller = 0) { global $pdo, $T; $pdo->exec("insert into user(mobile,password,nickname,invite_code,pid,status,balance,is_seller,seller_check,is_agent,is_virtual,auth_status,create_time,update_time,reg_time) values('$m','x','$nick','" . substr($m, -6) . "',$pid,1,100,$seller," . ($seller ? 1 : 0) . ",$agent,0,2,$T,$T,$T)"); return (int)$pdo->lastInsertId(); }
function sess($id, $key = 'user') { global $root, $pdo; $sid = md5('al' . $key . $id . microtime(true)); $u = $key === 'admin' ? $pdo->query('select * from admin_user order by id asc limit 1')->fetch(PDO::FETCH_ASSOC) : $pdo->query("select * from user where id=$id")->fetch(PDO::FETCH_ASSOC); unset($u['password']); file_put_contents("$root/runtime/session/sess_$sid", serialize([$key => $u])); return $sid; }
function req($sid, $m, $p, $d = null, $ajax = true) {
    $ch = curl_init('http://localhost' . $p);
    $h = $ajax ? ['X-Requested-With: XMLHttpRequest', 'Origin: http://localhost'] : ['Accept: text/html'];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $sid ? 'PHPSESSID=' . $sid : '']);
    if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d ?: [])); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b, json_decode($b, true)];
}
function logs($agentId) { global $pdo; return $pdo->query("select action,ip from agent_log where agent_id=$agentId order by id asc")->fetchAll(PDO::FETCH_COLUMN); }
function hasLog($agentId, $needle) { foreach (logs($agentId) as $a) if (strpos($a, $needle) !== false) return true; return false; }

$pdo->exec("delete from user where mobile in ('19999990640','19999990641','19999990642','19999990643')");
$AG  = mk('19999990640', 'QA日志代理', 0, 1);
$AG2 = mk('19999990641', 'QA日志代理2', 0, 1);
$U   = mk('19999990642', 'QA日志会员', $AG);
$S   = mk('19999990643', 'QA日志卖家', $AG, 0, 1);
$cat = (int)$pdo->query("select id from category where status=1 order by id limit 1")->fetchColumn();
$pdo->exec("insert into goods(seller_id,category_id,title,cover,images,start_price,raise_price,deposit,reserve_price,status,bid_count,view_count,start_time,end_time,create_time,update_time) values($S,$cat,'QA日志拍品','','[]',100,10,0,0,1,0,5,$T-60,$T+7200,$T,$T)");
$G = (int)$pdo->lastInsertId();
$pdo->exec("delete from agent_log where agent_id in ($AG,$AG2)");
$sg = sess($AG); $sg2 = sess($AG2); $sa = sess(0, 'admin');
try {
    echo "== 会员操作 ==\n";
    req($sg, 'POST', '/agent/member/setStatus', ['id' => $U, 'status' => 0, 'remark' => 'QA违规']);
    ok('禁用会员写日志（含手机号与备注）', hasLog($AG, '禁用会员：19999990642，备注：QA违规'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    req($sg, 'POST', '/agent/member/setStatus', ['id' => $U, 'status' => 1]);
    ok('启用会员写日志', hasLog($AG, '启用会员：19999990642'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    req($sg, 'POST', '/agent/member/editSave', ['id' => $U, 'password' => '', 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 1, 'can_withdraw' => 0]);
    ok('编辑会员写日志（设为虚拟会员，关闭提现）', hasLog($AG, '编辑会员 19999990642：设为虚拟会员，关闭提现'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    req($sg, 'POST', '/agent/member/editSave', ['id' => $U, 'password' => '', 'is_seller' => 0, 'is_agent' => 0, 'is_virtual' => 1, 'can_withdraw' => 0]);
    ok('无修改的编辑不写日志', count(array_filter(logs($AG), fn($a) => strpos($a, '编辑会员') === 0)) === 1, json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    req($sg, 'POST', '/agent/member/adjustBalance', ['id' => $U, 'amount' => 50, 'remark' => '奖励']);
    ok('余额调整写日志', hasLog($AG, '调整会员余额 19999990642：+50.00，调整后 150.00（代理调整：奖励）'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    req($sg, 'POST', '/agent/member/savePayAccount', ['user_id' => $U, 'type' => 3, 'real_name' => 'QA', 'account' => '6222000000000002', 'bank_name' => 'QA银行', 'bank_branch' => 'QA支行']);
    ok('保存提现账户写日志', hasLog($AG, '修改会员提现账户：19999990642 银行卡'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    req($sg, 'POST', '/agent/member/deletePayAccount', ['user_id' => $U, 'type' => 3]);
    ok('删除提现账户写日志', hasLog($AG, '删除会员提现账户：19999990642 银行卡'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));

    echo "== 产品操作 ==\n";
    req($sg, 'POST', '/agent/goods/setViews', ['id' => $G, 'view_count' => 88]);
    ok('修改浏览量写日志', hasLog($AG, '修改产品浏览量：QA日志拍品 5 → 88'), json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/goods/setTime', ['id' => $G, 'start_time' => date('Y-m-d\TH:i', $T - 60), 'end_time' => date('Y-m-d\TH:i', $T + 10800)]);
    ok('修改拍卖时间写日志', ($j['code'] ?? 0) == 1 && hasLog($AG, '修改产品拍卖时间：QA日志拍品 → '), json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/goods/setStatus', ['id' => $G, 'status' => 4]);
    ok('下架产品写日志', ($j['code'] ?? 0) == 1 && hasLog($AG, '下架产品：QA日志拍品'), json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode(logs($AG), JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sg, 'POST', '/agent/goods/delete', ['id' => $G]);
    ok('删除产品写日志', ($j['code'] ?? 0) == 1 && hasLog($AG, '删除产品：QA日志拍品'), json_encode($j, JSON_UNESCAPED_UNICODE) . json_encode(logs($AG), JSON_UNESCAPED_UNICODE));

    echo "== 越权操作不写日志 ==\n";
    $before = count(logs($AG2));
    [, , $j] = req($sg2, 'POST', '/agent/member/setStatus', ['id' => $U, 'status' => 0, 'remark' => 'x']);
    ok('代理2 操作非团队会员被拒且无日志', ($j['code'] ?? 1) == 0 && count(logs($AG2)) === $before, json_encode($j, JSON_UNESCAPED_UNICODE));

    echo "== 代理端没有日志页 ==\n";
    [$c, $h] = req($sg, 'GET', '/agent/log/index', null, false);
    ok('代理后台日志页已移除（404）', $c == 404, "HTTP $c");
    [$c, $h] = req($sg, 'GET', '/agent/member/index', null, false);
    ok('代理菜单里没有「操作日志」', $c == 200 && strpos($h, '/agent/log/index') === false, "HTTP $c");

    echo "== 主后台 系统设置 → 代理日志 ==\n";
    $pdo->exec("insert into agent_log(agent_id,action,ip,create_time) values($AG2,'代理2的其它操作','1.1.1.1',$T)");
    [$c, $h] = req($sa, 'GET', '/admin1314/log/agent', null, false);
    ok('代理日志页可打开，菜单里有「代理日志」', $c == 200 && strpos($h, '代理后台操作日志') !== false && strpos($h, 'href="/admin1314/log/agent"') !== false, "HTTP $c");
    [, , $j] = req($sa, 'GET', '/admin1314/log/agent?page=1&limit=50');
    $acts = array_column($j['data'] ?? [], 'action');
    ok('接口返回所有代理的日志（倒序、带代理手机号与 IP）', ($j['count'] ?? 0) >= 11 && in_array('代理2的其它操作', $acts, true) && in_array('删除产品：QA日志拍品', $acts, true) && !empty($j['data'][0]['ip']), json_encode($j, JSON_UNESCAPED_UNICODE));
    $row = null; foreach ($j['data'] ?? [] as $x) if ($x['action'] === '删除产品：QA日志拍品') $row = $x;
    ok('日志行带代理手机号和昵称', $row && $row['agent_mobile'] === '19999990640' && $row['agent_nickname'] === 'QA日志代理', json_encode($row, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', '/admin1314/log/agent?page=1&limit=15&agent=' . $AG . '&keyword=' . urlencode('余额'));
    ok('按内容搜索', ($j['count'] ?? 0) === 1 && strpos($j['data'][0]['action'], '调整会员余额') === 0, json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', '/admin1314/log/agent?page=1&limit=50&agent=19999990641');
    ok('按代理手机号筛选', ($j['count'] ?? 0) === 1 && $j['data'][0]['action'] === '代理2的其它操作', json_encode($j, JSON_UNESCAPED_UNICODE));
    [, , $j] = req($sa, 'GET', '/admin1314/log/agent?page=1&limit=50&agent=' . $AG);
    ok('按代理 ID 筛选', ($j['count'] ?? 0) === 10, json_encode($j, JSON_UNESCAPED_UNICODE));
    [$c, $b] = req($sg, 'GET', '/admin1314/log/agent?page=1&limit=50');
    ok('代理会话访问主后台代理日志接口被拒', $c != 200 || strpos($b, '"count"') === false, "HTTP $c " . mb_substr($b, 0, 80));
    ok('主后台管理员日志表未混入代理操作', (int)$pdo->query("select count(*) from admin_log where action like '%19999990642%'")->fetchColumn() === 0);
} finally {
    $pdo->exec("delete from agent_log where agent_id in ($AG,$AG2)");
    $pdo->exec("delete from balance_log where user_id=$U");
    $pdo->exec("delete from pay_account where user_id=$U");
    $pdo->exec("delete from goods where id=$G");
    $pdo->exec("delete from user where id in ($AG,$AG2,$U,$S)");
    foreach ([$sg, $sg2, $sa] as $s) @unlink("$root/runtime/session/sess_$s");
    echo "[cleanup] done\n";
}

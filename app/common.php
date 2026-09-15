<?php
// 应用公共文件

use think\facade\Db;

/**
 * 生成订单/单号
 * @param string $prefix 前缀
 * @return string
 */
function make_order_no($prefix = 'AU')
{
    return $prefix . date('YmdHis') . str_pad((string)mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 密码加密
 * @param string $password
 * @param string $salt
 * @return string
 */
function encrypt_password($password, $salt = '')
{
    return md5(md5($password) . $salt);
}

/**
 * 富文本净化：用于所有以 |raw 原样输出的用户/后台提交内容
 *
 * 策略：先整段移除会执行脚本的标签及其内容，再用白名单 strip_tags 去掉其余标签，
 * 最后清除残留的事件属性与危险协议。宁可少留标签，也不放过一个执行点。
 */
function clean_html($html)
{
    $html = (string)$html;
    if ($html === '') {
        return '';
    }

    // 1) 连内容一起删除的标签（留着文本也没意义，且可能是脚本源码）
    $html = preg_replace('#<\s*(script|style|iframe|frameset|frame|object|embed|applet|form|link|meta|base|svg|math)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
    // 2) 上述标签的自闭合/未闭合写法
    $html = preg_replace('#<\s*/?\s*(script|style|iframe|frameset|frame|object|embed|applet|form|link|meta|base|svg|math)\b[^>]*>#i', '', $html);

    // 3) 白名单：仅保留排版类标签
    $allow = '<p><br><hr><div><span><b><strong><i><em><u><s><del><sub><sup>'
           . '<ul><ol><li><dl><dt><dd><blockquote><pre><code>'
           . '<h1><h2><h3><h4><h5><h6>'
           . '<table><thead><tbody><tfoot><tr><td><th><caption><col><colgroup>'
           . '<img><a><figure><figcaption>';
    $html = strip_tags($html, $allow);

    // 4) 清除所有事件处理属性（onclick / onerror / onload ...）
    $html = preg_replace('#\son[a-z-]+\s*=\s*"[^"]*"#i', '', $html);
    $html = preg_replace("#\son[a-z-]+\s*=\s*'[^']*'#i", '', $html);
    $html = preg_replace('#\son[a-z-]+\s*=\s*[^\s>]+#i', '', $html);

    // 5) 清除任何承载危险协议的属性
    //    不枚举属性名（background/poster/dynsrc/lowsrc 等遗留属性同样可执行脚本），
    //    改为凡属性值以危险协议开头一律删除；data: 仅放行 data:image/ 用于内联图片。
    $danger = 'javascript|vbscript|livescript|mocha|about|data(?!:image/)';
    $html = preg_replace('#\s[a-zA-Z_:][\w:.-]*\s*=\s*"\s*(?:' . $danger . ')\s*:[^"]*"#i', '', $html);
    $html = preg_replace("#\s[a-zA-Z_:][\w:.-]*\s*=\s*'\s*(?:" . $danger . ")\s*:[^']*'#i", '', $html);
    $html = preg_replace('#\s[a-zA-Z_:][\w:.-]*\s*=\s*(?:' . $danger . ')\s*:[^\s>]*#i', '', $html);

    // 6) 清除 style 内联属性（可承载 expression()/url(javascript:)）
    $html = preg_replace('#\sstyle\s*=\s*"[^"]*"#i', '', $html);
    $html = preg_replace("#\sstyle\s*=\s*'[^']*'#i", '', $html);

    return $html;
}

/**
 * 生成唯一的纯数字邀请码（默认 6 位，首位不为 0）
 * 后台添加会员与前台注册统一使用
 */
function generate_invite_code($length = 6)
{
    $length = max(4, (int)$length);
    do {
        $code = (string)random_int(1, 9);
        for ($i = 1; $i < $length; $i++) {
            $code .= (string)random_int(0, 9);
        }
    } while (\think\facade\Db::name('user')->where('invite_code', $code)->find());
    return $code;
}

/**
 * 生成密码哈希（bcrypt）
 * 旧的 encrypt_password() 仅保留用于校验历史数据，新写入一律走这里
 */
function hash_password($password)
{
    return password_hash((string)$password, PASSWORD_DEFAULT);
}

/**
 * 校验密码：同时兼容 bcrypt 新哈希与历史的 md5(md5(pwd)) 旧哈希
 */
function verify_password($password, $stored)
{
    $stored = (string)$stored;
    if ($stored === '') {
        return false;
    }
    // bcrypt / argon 等标准哈希
    if (strlen($stored) > 32 && $stored[0] === '$') {
        return password_verify((string)$password, $stored);
    }
    // 历史遗留：md5(md5(pwd))，用 hash_equals 防时序侧信道
    return hash_equals($stored, encrypt_password($password));
}

/**
 * 该哈希是否为需要升级的旧格式（md5 系）
 */
function password_is_legacy($stored)
{
    $stored = (string)$stored;
    return !(strlen($stored) > 32 && $stored !== '' && $stored[0] === '$');
}
/**
 * 后台操作日志
 * @param string $action 操作描述
 * @param int $adminId
 */
function admin_log($action, $adminId = 0)
{
    try {
        Db::name('admin_log')->insert([
            // 登录时写入的是 session('admin')，此前误读 session('admin_id') 导致所有不传 id 的日志静默丢失
            'admin_id'    => $adminId ?: (int)((session('admin') ?: [])['id'] ?? 0),
            'action'      => $action,
            'ip'          => request()->ip(),
            'create_time' => time(),
        ]);
    } catch (\Throwable $e) {
    }
}

function arraySort($arr, $keys, $type = 'asc')
{
    if (count($arr) <= 1) {
        return $arr;
    }

    $keysValue = [];
    $newArray = [];

    foreach ($arr as $k => $v) {
        $keysValue[$k] = $v[$keys];
    }

    $type == 'asc' ? asort($keysValue) : arsort($keysValue);
    reset($keysValue);
    foreach ($keysValue as $k => $v) {
        $newArray[$k] = $arr[$k];
    }

    return $newArray;
}

/**
 * 读取系统设置（内存缓存）。前台 / 后台 / 代理端共用
 */
function site_settings()
{
    static $settings = null;
    if ($settings === null) {
        $list = \think\facade\Db::name('setting')->select()->toArray();
        $settings = [];
        foreach ($list as $row) {
            $settings[$row['name']] = $row['value'];
        }
    }
    return $settings;
}

/**
 * 读取单个设置
 */
function get_setting($name, $default = '')
{
    $settings = site_settings();
    return isset($settings[$name]) && $settings[$name] !== '' ? $settings[$name] : $default;
}

/* ==================== 谷歌验证器（TOTP，RFC 6238） ==================== */

/**
 * 生成随机 Base32 密钥（16 位，Google Authenticator 可直接识别）
 */
function google_auth_secret($length = 16)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, 31)];
    }
    return $secret;
}

/**
 * Base32 解码
 */
function google_auth_base32_decode($b32)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', (string)$b32));
    $bits = '';
    for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
        $bits .= str_pad(decbin(strpos($chars, $b32[$i])), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $out .= chr(bindec(substr($bits, $i, 8)));
    }
    return $out;
}

/**
 * 计算某个 30 秒时间片的 6 位动态码
 */
function google_auth_code($secret, $timeSlice = null)
{
    if ($timeSlice === null) {
        $timeSlice = (int)floor(time() / 30);
    }
    $key  = google_auth_base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $time, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $part = substr($hash, $offset, 4);
    $value = unpack('N', $part)[1] & 0x7FFFFFFF;
    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

/**
 * 校验动态码：允许前后各 2 个时间片（±60 秒）的时钟误差，兼顾服务器与手机之间的小幅时间偏差
 * 返回命中的时间片（用于防重放），不匹配返回 false
 */
function google_auth_verify($secret, $code, $window = 2)
{
    $code = preg_replace('/\D/', '', (string)$code);
    if ($secret === '' || strlen($code) !== 6) {
        return false;
    }
    $now = (int)floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(google_auth_code($secret, $now + $i), $code)) {
            return $now + $i;
        }
    }
    return false;
}

/**
 * 生成 otpauth 链接（用于生成二维码，Google Authenticator / Microsoft Authenticator 扫码绑定）
 */
function google_auth_uri($secret, $account, $issuer)
{
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
        . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
}

/**
 * 商品下架 / 删除时释放出价：所有有效出价标记为流拍，逐个买家退回冻结的保证金并写流水、发站内信。
 * 前台卖家、主后台、代理端三处的下架与删除都必须先调用它，避免买家保证金被永久冻结。
 *
 * @param int    $goodsId
 * @param string $reason  站内信与流水里的原因文字，如「卖家下架」「平台下架」「平台删除」
 * @return int   退回保证金的出价记录条数
 */
function release_goods_bids($goodsId, $reason = '商品下架')
{
    $db = \think\facade\Db::class;
    $goods = $db::name('goods')->where('id', (int)$goodsId)->find();
    if (!$goods) {
        return 0;
    }
    $bids = $db::name('bid_record')->where('goods_id', $goods['id'])->where('status', 0)->select()->toArray();
    if (empty($bids)) {
        return 0;
    }
    $now = time();
    $refunded = 0;
    $notified = [];
    $db::startTrans();
    try {
        $db::name('bid_record')->where('goods_id', $goods['id'])->where('status', 0)->update(['status' => 2, 'is_winner' => 0]);
        foreach ($bids as $b) {
            if ((float)$b['deposit'] > 0) {
                $user = $db::name('user')->where('id', $b['user_id'])->lock(true)->find();
                if ($user) {
                    $newBalance = round($user['balance'] + $b['deposit'], 2);
                    $newFreeze  = round(max($user['freeze_balance'] - $b['deposit'], 0), 2);
                    $db::name('user')->where('id', $user['id'])->update(['balance' => $newBalance, 'freeze_balance' => $newFreeze, 'update_time' => $now]);
                    $db::name('balance_log')->insert([
                        'user_id'     => $user['id'],
                        'type'        => 'refund',
                        'amount'      => $b['deposit'],
                        'balance'     => $newBalance,
                        'remark'      => $reason . '，保证金退回（' . $goods['title'] . '）',
                        'create_time' => $now,
                    ]);
                    $refunded++;
                }
            }
            if (!isset($notified[$b['user_id']])) {
                $notified[$b['user_id']] = true;
                $db::name('sys_message')->insert([
                    'user_id'     => $b['user_id'],
                    'admin_id'    => 0,
                    'title'       => '拍品下架通知',
                    'content'     => '您参与竞拍的「' . $goods['title'] . '」已' . $reason . '，本次竞拍取消，已缴纳的保证金已退回您的可用余额。',
                    'is_read'     => 0,
                    'create_time' => $now,
                ]);
            }
        }
        $db::commit();
    } catch (\Throwable $e) {
        $db::rollback();
        throw $e;
    }
    return $refunded;
}

/* ==================== 指定卖家流拍商品自动上架 ==================== */

/**
 * 把指定卖家的流拍商品自动重新上架
 *
 * 后台设置 auto_relist_seller_id（卖家 ID，默认 1）、auto_relist_hours（重新上架后的拍卖时长，小时；0 为关闭）。
 * 每件商品：清空旧出价记录、出价数 / 得标人 / 成交价归零，开拍时间为当前时间，截拍时间 = 当前时间 + 拍卖时长。
 * 由 php think goods:auto-relist 与 php think settle 调用，单次最多处理 $limit 件，避免积压过多时单次执行过久。
 *
 * @return array ['enabled' => bool, 'seller_id' => int, 'hours' => float, 'end_time' => int, 'ids' => int[]]
 */
function auto_relist_failed_goods($limit = 500)
{
    $sellerId = (int)get_setting('auto_relist_seller_id', 1);
    $hours    = round((float)get_setting('auto_relist_hours', 0), 2);
    $now      = time();
    $result   = ['enabled' => $hours > 0 && $sellerId > 0, 'seller_id' => $sellerId, 'hours' => $hours, 'end_time' => $now + (int)round($hours * 3600), 'ids' => []];
    if (!$result['enabled']) {
        return $result;
    }
    $ids = Db::name('goods')->where('seller_id', $sellerId)->where('status', 3)->order('end_time', 'asc')->limit((int)$limit)->column('id');
    if (empty($ids)) {
        return $result;
    }
    foreach ($ids as $gid) {
        Db::startTrans();
        try {
            // 二次确认状态，避免与后台手动上架 / 删除并发
            $goods = Db::name('goods')->where('id', $gid)->lock(true)->find();
            if (!$goods || (int)$goods['status'] !== 3) {
                Db::rollback();
                continue;
            }
            Db::name('bid_record')->where('goods_id', $gid)->delete();
            Db::name('goods')->where('id', $gid)->update([
                'status'      => 1,
                'start_time'  => $now,
                'end_time'    => $result['end_time'],
                'bid_count'   => 0,
                'winner_id'   => 0,
                'final_price' => 0,
                'update_time' => $now,
            ]);
            Db::commit();
            $result['ids'][] = (int)$gid;
        } catch (\Throwable $e) {
            Db::rollback();
        }
    }
    if (!empty($result['ids'])) {
        admin_log('自动上架流拍商品：卖家 ' . $sellerId . '，' . count($result['ids']) . ' 件，拍卖时长 ' . $hours . ' 小时', 0);
    }
    return $result;
}

/* ==================== 虚拟用户自动出价 ==================== */

/**
 * 拍品当前价：有效最高出价，没有则为起拍价
 */
function auto_bid_current_price(array $goods)
{
    $top = Db::name('bid_record')->where('goods_id', $goods['id'])->where('status', 0)->max('price');
    return max((float)$top, (float)$goods['start_price']);
}

/**
 * 校验自动出价参数，返回错误文案；通过返回空串
 */
function auto_bid_validate(array $goods, $intervalMin, $maxPrice, $stopHours)
{
    $now = time();
    if ((int)$goods['status'] !== 1) {
        return '该拍品不在拍卖中';
    }
    if ((int)$goods['end_time'] <= $now) {
        return '该拍品已截拍';
    }
    if ($intervalMin < 1 || $intervalMin > 1440) {
        return '出价间隔需为 1 ~ 1440 分钟';
    }
    if ($stopHours < 0 || $stopHours > 720) {
        return '停止出价的提前小时数需为 0 ~ 720';
    }
    if ($stopHours * 3600 >= $goods['end_time'] - $now) {
        return '距截拍已不足 ' . rtrim(rtrim(number_format($stopHours, 2, '.', ''), '0'), '.') . ' 小时，任务不会执行，请缩短停止提前量';
    }
    $current = auto_bid_current_price($goods);
    $raise   = (float)$goods['raise_price'] > 0 ? (float)$goods['raise_price'] : 1;
    if ($maxPrice < $current + $raise) {
        return '最高出价金额至少要能出一次价：当前价 ' . number_format($current, 2) . ' + 加价幅度 ' . number_format($raise, 2) . ' = ' . number_format($current + $raise, 2);
    }
    return '';
}

/**
 * 下次出价时间：间隔 × 70%~130% 随机，至少 60 秒
 */
function auto_bid_next_time($intervalMin, $from = null)
{
    $from = $from ?: time();
    $sec  = (int)round($intervalMin * 60 * mt_rand(70, 130) / 100);
    return $from + max(60, $sec);
}

/**
 * 执行一轮自动出价（由 php think bid:auto 与 php think settle 调用）
 *
 * 每个运行中的任务：拍品不在拍卖中 → 结束；进入截拍前停止时段 → 结束；当前价已达上限 → 结束；
 * 未到下次出价时间 → 跳过；否则随机挑一个虚拟会员（排除卖家与当前最高价者）按一个加价幅度出价，
 * 出价记录保证金记 0，不冻结余额；给被超过的真实买家发出局通知；触发拍品延时规则。
 *
 * @return array ['tasks' => 检查的任务数, 'bids' => [出价记录ID...], 'logs' => [日志行...]]
 */
function auto_bid_run($limit = 200)
{
    $now    = time();
    $result = ['tasks' => 0, 'bids' => [], 'logs' => []];
    $tasks  = Db::name('auto_bid')->where('status', 1)->order('next_time', 'asc')->limit((int)$limit)->select()->toArray();
    $result['tasks'] = count($tasks);
    if (!$tasks) {
        return $result;
    }
    $virtualIds = Db::name('user')->where('is_virtual', 1)->where('status', 1)->column('id');
    foreach ($tasks as $task) {
        $goods = Db::name('goods')->find($task['goods_id']);
        $finish = function ($reason) use ($task, $now, &$result) {
            Db::name('auto_bid')->where('id', $task['id'])->update(['status' => 2, 'stop_reason' => $reason, 'update_time' => $now]);
            $result['logs'][] = "任务#{$task['id']} 拍品#{$task['goods_id']} 结束：{$reason}";
        };
        if (!$goods) {
            $finish('拍品已删除');
            continue;
        }
        if ((int)$goods['status'] !== 1 || (int)$goods['end_time'] <= $now) {
            $finish('拍卖已结束或拍品已下架');
            continue;
        }
        if ($now < (int)$goods['start_time']) {
            continue;
        }
        if ((int)$goods['end_time'] - $now <= (float)$task['stop_hours'] * 3600) {
            $finish('已进入截拍前 ' . rtrim(rtrim(number_format((float)$task['stop_hours'], 2, '.', ''), '0'), '.') . ' 小时的停止时段');
            continue;
        }
        $current = auto_bid_current_price($goods);
        $raise   = (float)$goods['raise_price'] > 0 ? (float)$goods['raise_price'] : 1;
        $price   = round($current + $raise, 2);
        if ($current >= (float)$task['max_price'] || $price > (float)$task['max_price']) {
            $finish('当前价 ' . number_format($current, 2) . ' 已达到最高出价金额 ' . number_format((float)$task['max_price'], 2));
            continue;
        }
        if ((int)$task['next_time'] > $now) {
            continue;
        }
        $topBid = Db::name('bid_record')->where('goods_id', $goods['id'])->where('status', 0)->order('price', 'desc')->order('id', 'asc')->find();
        $candidates = array_values(array_filter($virtualIds, function ($id) use ($goods, $topBid) {
            return (int)$id !== (int)$goods['seller_id'] && (!$topBid || (int)$id !== (int)$topBid['user_id']);
        }));
        if (!$candidates) {
            $result['logs'][] = "任务#{$task['id']} 拍品#{$task['goods_id']} 跳过：没有可用的虚拟会员";
            Db::name('auto_bid')->where('id', $task['id'])->update(['next_time' => auto_bid_next_time($task['interval_min'], $now), 'update_time' => $now]);
            continue;
        }
        $userId = (int)$candidates[array_rand($candidates)];

        Db::startTrans();
        try {
            $g = Db::name('goods')->where('id', $goods['id'])->lock(true)->find();
            if (!$g || (int)$g['status'] !== 1 || (int)$g['end_time'] <= $now) {
                Db::rollback();
                continue;
            }
            // 锁内重算，避免与真人出价并发
            $top2    = Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->order('price', 'desc')->order('id', 'asc')->find();
            $cur2    = max($top2 ? (float)$top2['price'] : 0, (float)$g['start_price']);
            $price   = round($cur2 + $raise, 2);
            if ($price > (float)$task['max_price']) {
                Db::rollback();
                $finish('当前价 ' . number_format($cur2, 2) . ' 已达到最高出价金额 ' . number_format((float)$task['max_price'], 2));
                continue;
            }
            if ($top2 && (int)$top2['user_id'] === $userId) {
                Db::rollback();
                continue;
            }
            $bidId = Db::name('bid_record')->insertGetId([
                'goods_id'    => $g['id'],
                'user_id'     => $userId,
                'price'       => $price,
                'deposit'     => 0,
                'status'      => 0,
                'is_winner'   => 0,
                'create_time' => $now,
            ]);
            $update = ['bid_count' => Db::raw('bid_count + 1'), 'update_time' => $now];
            $delay  = (int)$g['delay_seconds'] > 0 ? (int)$g['delay_seconds'] : (int)get_setting('auction_delay', 0);
            if ($delay > 0 && (int)$g['end_time'] - $now <= $delay) {
                $update['end_time'] = $now + $delay;
            }
            Db::name('goods')->where('id', $g['id'])->update($update);
            // 被超过的真实买家发出局通知（虚拟会员之间不发）
            if ($top2 && (int)$top2['user_id'] !== $userId && !in_array((int)$top2['user_id'], array_map('intval', $virtualIds), true)) {
                Db::name('sys_message')->insert([
                    'user_id'     => $top2['user_id'],
                    'admin_id'    => 0,
                    'title'       => '竞拍出局通知',
                    'content'     => '您出价竞拍的「' . $g['title'] . '」已出局：您的出价 ¥' . number_format((float)$top2['price'], 2) . ' 于 ' . date('Y-m-d H:i:s', $now) . ' 被 ¥' . number_format($price, 2) . ' 超过，当前最高价 ¥' . number_format($price, 2) . '。如需继续竞拍，请再次出价。',
                    'is_read'     => 0,
                    'create_time' => $now,
                ]);
            }
            $done = round($price + $raise, 2) > (float)$task['max_price'];
            Db::name('auto_bid')->where('id', $task['id'])->update([
                'last_time'   => $now,
                'next_time'   => auto_bid_next_time($task['interval_min'], $now),
                'bid_count'   => Db::raw('bid_count + 1'),
                'status'      => $done ? 2 : 1,
                'stop_reason' => $done ? '当前价 ' . number_format($price, 2) . ' 已达到最高出价金额 ' . number_format((float)$task['max_price'], 2) : '',
                'update_time' => $now,
            ]);
            Db::commit();
            $result['bids'][] = (int)$bidId;
            $result['logs'][] = "任务#{$task['id']} 拍品#{$g['id']}「{$g['title']}」 虚拟会员#{$userId} 出价 " . number_format($price, 2) . ($done ? '，已达上限，任务结束' : '');
        } catch (\Throwable $e) {
            Db::rollback();
            $result['logs'][] = "任务#{$task['id']} 拍品#{$task['goods_id']} 出价失败：" . $e->getMessage();
        }
    }
    return $result;
}

/**
 * 生成虚拟会员账号：12 开头 + 9 位随机数字，共 11 位
 * 国内真实手机号没有 12 号段，因此不会与真实用户注册的号码冲突
 */
function generate_virtual_mobile()
{
    for ($i = 0; $i < 50; $i++) {
        $mobile = '12' . str_pad((string)mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        if (!Db::name('user')->where('mobile', $mobile)->count()) {
            return $mobile;
        }
    }
    throw new \RuntimeException('生成虚拟会员账号失败，请重试');
}

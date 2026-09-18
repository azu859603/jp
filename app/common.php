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

/**
 * 代理后台操作日志（代理用前台账号登录，session('user') 即当前代理）
 * @param string $action 操作描述
 * @param int $agentId
 */
function agent_log($action, $agentId = 0)
{
    try {
        Db::name('agent_log')->insert([
            'agent_id'    => $agentId ?: (int)((session('user') ?: [])['id'] ?? 0),
            'action'      => mb_substr((string)$action, 0, 255),
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
    if ($settings !== null) {
        return $settings;
    }
    // Redis 缓存整张设置表；用「行数 + 键值 CRC 校验和」做版本号（MySQL 端计算，只回传两个数字），
    // 任何方式改了设置版本都会变化；每次请求不再把含长文本（协议 / 关于我们）的整张表读回 PHP
    $ver = setting_version();
    $key = 'site_settings';
    $cached = \think\facade\Cache::get($key);
    if (is_array($cached) && ($cached['ver'] ?? '') === $ver && is_array($cached['data'] ?? null)) {
        return $settings = $cached['data'];
    }
    $list = \think\facade\Db::name('setting')->field('name,value')->select()->toArray();
    $settings = [];
    foreach ($list as $row) {
        $settings[$row['name']] = $row['value'];
    }
    \think\facade\Cache::set($key, ['ver' => $ver, 'data' => $settings], 600);
    return $settings;
}

/**
 * 设置表版本号：行数 + 全部键值的 CRC 校验和（在 MySQL 端计算，只回传两个数字）
 * 任何写入路径（后台保存 / 脚本 / 手工改库 / 测试）只要值变了版本就变，缓存不会读到旧值
 */
function setting_version()
{
    $row = \think\facade\Db::name('setting')
        ->fieldRaw("COUNT(*) AS c, IFNULL(SUM(CRC32(CONCAT(name, '=', IFNULL(value, '')))), 0) AS h")
        ->select()->toArray();   // 无 where 条件时 find() 不执行查询，这里用 select 取首行
    $row = $row[0] ?? [];
    return (int)($row['c'] ?? 0) . '-' . (string)($row['h'] ?? 0);
}

/**
 * 后台保存设置后立即失效缓存（版本号也会变化，这里只是让当前进程与其它 worker 立刻看到新值）
 */
function site_settings_refresh()
{
    \think\facade\Cache::delete('site_settings');
}

/**
 * 启用中的分类（按 sort 升序），Redis 缓存 10 分钟；后台增删改分类时调用 categories_cache_clear()
 */
function active_categories()
{
    static $list = null;
    if ($list === null) {
        $list = \think\facade\Cache::remember('categories_active', function () {
            return \think\facade\Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        }, 600);
    }
    return $list;
}
function active_category($id)
{
    foreach (active_categories() as $c) {
        if ((int)$c['id'] === (int)$id) {
            return $c;
        }
    }
    return null;
}
function categories_cache_clear()
{
    \think\facade\Cache::delete('categories_active');
}

/**
 * 启用中的轮播图（sort、id 升序），Redis 缓存 10 分钟；后台增删改轮播时调用 banners_cache_clear()
 */
function active_banners()
{
    return \think\facade\Cache::remember('banners_active', function () {
        return \think\facade\Db::name('banner')->where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
    }, 600);
}
function banners_cache_clear()
{
    \think\facade\Cache::delete('banners_active');
}

/**
 * 读取单个设置
 */
function get_setting($name, $default = '')
{
    $settings = site_settings();
    return isset($settings[$name]) && $settings[$name] !== '' ? $settings[$name] : $default;
}

/**
 * 主后台开关：是否允许代理后台调整会员余额（默认开启）
 */
function agent_balance_adjust_enabled()
{
    return (string)get_setting('agent_balance_adjust', '1') === '1';
}

/**
 * 竞拍中商品自动增加浏览量（php think goods:auto-views 每次执行调用一次）
 *
 * 后台「基础设置 › 浏览量自动增加」：
 *   auto_view_enabled  开关，1 开启
 *   auto_view_amount   每次执行每件商品增加的基准量
 *   auto_view_float    浮动比例（%）：每件商品实际增加量在 基准量 ×(1 ± 浮动比例) 之间随机取整
 *
 * 只处理正在竞拍的商品（status=1 且已开拍、未截拍）；不改 update_time，浏览量上限 99999999。
 * @return array ['enabled'=>bool, 'amount'=>int, 'float'=>int, 'min'=>int, 'max'=>int, 'goods'=>int, 'total'=>int]
 */
function auto_increase_views()
{
    $enabled = (string)get_setting('auto_view_enabled', '0') === '1';
    $amount  = max(0, (int)get_setting('auto_view_amount', 0));
    $float   = max(0, min(100, (int)get_setting('auto_view_float', 50)));
    $min     = (int)floor($amount * (100 - $float) / 100);
    $max     = (int)ceil($amount * (100 + $float) / 100);
    $result  = ['enabled' => $enabled && $amount > 0, 'amount' => $amount, 'float' => $float, 'min' => $min, 'max' => $max, 'goods' => 0, 'total' => 0];
    if (!$result['enabled']) {
        return $result;
    }
    $now = time();
    $ids = Db::name('goods')->where('status', 1)->where('start_time', '<=', $now)->where('end_time', '>', $now)->column('id');
    if (!$ids) {
        return $result;
    }
    // 每件商品各自随机一个增量，再按增量分组批量更新（SQL 条数 = 不同增量的个数，而不是商品数）
    $groups = [];
    foreach ($ids as $id) {
        $delta = mt_rand($min, $max);
        if ($delta > 0) {
            $groups[$delta][] = (int)$id;
        }
    }
    foreach ($groups as $delta => $gids) {
        foreach (array_chunk($gids, 500) as $chunk) {
            Db::name('goods')->whereIn('id', $chunk)->where('status', 1)
                ->update(['view_count' => Db::raw('LEAST(view_count + ' . (int)$delta . ', 99999999)')]);
            $result['goods'] += count($chunk);
            $result['total'] += $delta * count($chunk);
        }
    }
    return $result;
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
 * 每件商品：清空旧出价记录、出价数 / 得标人 / 成交价归零，开拍时间为当前时间，
 * 截拍时间 = 当前时间 + 拍卖时长 + 随机 0~6 小时（每件各自随机，避免同时截拍）。
 * 由 php think goods:auto-relist 与 php think settle 调用，单次最多处理 $limit 件，避免积压过多时单次执行过久。
 *
 * @return array ['enabled' => bool, 'seller_id' => int, 'hours' => float, 'end_time' => int（截拍窗口起点）, 'ids' => int[]]
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
                'end_time'    => $result['end_time'] + mt_rand(0, 6 * 3600),
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
 * 把成交款（成交价 − 佣金）打给卖家
 * 时机：买家确认收货 / 系统自动确认收货 / 后台、代理后台标记完成。
 * 必须在调用方的事务内调用（订单行已加锁）；按 order.income_paid 条件更新，保证只入账一次。
 * @param array $order 订单行
 * @return bool 本次是否入账（未付款 / 已入账过 → false）
 */
function pay_seller_income(array $order)
{
    if ((int)($order['pay_status'] ?? 0) !== 1 || (int)($order['income_paid'] ?? 0) === 1) {
        return false;
    }
    $now = time();
    if (Db::name('order')->where('id', (int)$order['id'])->where('pay_status', 1)->where('income_paid', 0)->update([
        'income_paid' => 1,
        'update_time' => $now,
    ]) !== 1) {
        return false;
    }
    $income = round((float)$order['seller_income'], 2);
    $seller = Db::name('user')->where('id', (int)$order['seller_id'])->lock(true)->find();
    if (!$seller) {
        return true;   // 卖家已不存在：标记已处理，避免反复尝试
    }
    $balance = round((float)$seller['balance'] + $income, 2);
    Db::name('user')->where('id', $seller['id'])->update([
        'balance'     => $balance,
        'total_sell'  => Db::raw('total_sell+1'),
        'update_time' => $now,
    ]);
    if ($income > 0) {
        Db::name('balance_log')->insert([
            'user_id'     => $seller['id'],
            'type'        => 'income',
            'amount'      => $income,
            'balance'     => $balance,
            'remark'      => '拍卖成交收入：' . $order['order_no'] . '（平台佣金 ¥' . number_format((float)$order['commission'], 2, '.', '') . '）',
            'create_time' => $now,
        ]);
    }
    Db::name('sys_message')->insert([
        'user_id'     => $seller['id'],
        'admin_id'    => 0,
        'title'       => '成交款到账通知',
        'content'     => '订单 ' . $order['order_no'] . '（' . $order['goods_title'] . '）买家已确认收货，成交款 ¥' . number_format($income, 2)
                       . '（成交价 ¥' . number_format((float)$order['price'], 2) . ' − 平台佣金 ¥' . number_format((float)$order['commission'], 2) . '）已存入您的可用余额。',
        'is_read'     => 0,
        'create_time' => $now,
    ]);
    return true;
}
/**
 * 卖家入驻是否免审核：后台「卖家入驻审核」设为「自动开通」时返回 true
 * 此时买家在个人中心点「去申请」即可直接成为卖家，无需填写资料
 * @return bool
 */
function seller_auto_open()
{
    return (int)get_setting('seller_check', 1) !== 1;
}
/**
 * 平台自营自动出价：会员 ID 1（平台自营账号）发布的所有拍卖中拍品，由脚本按后台参数安排虚拟会员出价
 * 配置直接读库（不走 site_settings() 的请求内缓存），保证后台刚保存的开关立即生效
 * @return array ['enabled'=>bool,'seller_id'=>int,'interval'=>int(分钟),'multiple'=>float(起拍价倍数),'stop_hours'=>float]
 */
function platform_auto_bid_config()
{
    $names = ['platform_auto_bid_enabled', 'platform_auto_bid_interval', 'platform_auto_bid_multiple', 'platform_auto_bid_stop_hours'];
    $v = Db::name('setting')->whereIn('name', $names)->column('value', 'name');
    return [
        'enabled'    => (int)($v['platform_auto_bid_enabled'] ?? 0) === 1,
        'seller_id'  => 1,
        'interval'   => max(1, min(1440, (int)($v['platform_auto_bid_interval'] ?? 30))),
        'multiple'   => max(1, round((float)($v['platform_auto_bid_multiple'] ?? 2), 2)),
        'stop_hours' => max(0, min(720, round((float)($v['platform_auto_bid_stop_hours'] ?? 1), 2))),
    ];
}

/**
 * 平台自营自动出价是否开启
 */
function platform_auto_bid_enabled()
{
    return platform_auto_bid_config()['enabled'];
}

/**
 * 后台 / 代理后台手动添加自动出价任务时的黑名单卖家：
 * 平台自营自动出价开启时，会员 ID 1 的拍品由脚本统一出价，不允许再手动挂任务；关闭时不限制
 * @param int $sellerId
 * @return bool
 */
function auto_bid_blocked_seller($sellerId)
{
    return (int)$sellerId === 1 && platform_auto_bid_enabled();
}

/**
 * 同步平台自营自动出价任务（php think platform:auto-bid 每轮开头、后台保存开关/参数时调用）
 *
 * 开启时：
 *   1. 会员 1 拍品上所有非脚本创建的任务 → 改由脚本接管（creator_type=platform），以脚本参数为准；
 *   2. 会员 1 每件拍卖中的拍品若没有任务 → 新建脚本任务；已有脚本任务 → 参数同步为最新设置，
 *      已结束 / 已停用但拍品仍满足条件的 → 重新运行。
 * 关闭时：脚本创建的运行中任务全部停用（手动添加的任务不受影响）。
 *
 * @return array ['enabled'=>bool,'taken'=>int[],'created'=>int[],'resumed'=>int[],'updated'=>int,'stopped'=>int[]]
 */
function platform_auto_bid_sync()
{
    $cfg    = platform_auto_bid_config();
    $now    = time();
    $result = ['enabled' => $cfg['enabled'], 'taken' => [], 'created' => [], 'resumed' => [], 'updated' => 0, 'stopped' => [], 'errors' => []];

    if (!$cfg['enabled']) {
        $ids = Db::name('auto_bid')->where('creator_type', 'platform')->where('status', 1)->column('id');
        if ($ids) {
            Db::name('auto_bid')->whereIn('id', $ids)->update(['status' => 0, 'stop_reason' => '平台自营自动出价已关闭', 'update_time' => $now]);
            $result['stopped'] = array_map('intval', $ids);
        }
        return $result;
    }

    // 1. 接管会员 1 拍品上的手动任务
    $manual = Db::name('auto_bid')->alias('a')->leftJoin('goods g', 'a.goods_id = g.id')
        ->where('g.seller_id', $cfg['seller_id'])->where('a.creator_type', '<>', 'platform')->column('a.id');
    if ($manual) {
        Db::name('auto_bid')->whereIn('id', $manual)->update(['creator_type' => 'platform', 'creator_id' => 0, 'update_time' => $now]);
        $result['taken'] = array_map('intval', $manual);
    }

    // 2. 会员 1 拍卖中的拍品：没有任务的新建，有任务的同步参数 / 恢复
    $goodsList = Db::name('goods')->where('seller_id', $cfg['seller_id'])->where('status', 1)
        ->where('start_time', '<=', $now)->where('end_time', '>', $now)->select()->toArray();
    if (!$goodsList) {
        return $result;
    }
    $tasks = Db::name('auto_bid')->whereIn('goods_id', array_column($goodsList, 'id'))->select()->toArray();
    $tasks = array_column($tasks, null, 'goods_id');
    foreach ($goodsList as $goods) {
        // 上限 = 起拍价 × 倍数，封顶到字段能存的最大值（decimal(10,2)）
        $maxPrice  = min(round((float)$goods['start_price'] * $cfg['multiple'], 2), MONEY_MAX);
        $task      = $tasks[(int)$goods['id']] ?? null;
        // 只有需要新建或恢复时才做完整校验（避免每分钟对几千件运行中的拍品重复查询）
        $qualifies = function () use ($goods, $cfg, $maxPrice) {
            return auto_bid_validate($goods, $cfg['interval'], $maxPrice, $cfg['stop_hours']) === '';
        };
        try {
            if (!$task) {
                if (!$qualifies()) {
                    continue;
                }
                $id = Db::name('auto_bid')->insertGetId([
                    'goods_id'     => $goods['id'],
                    'interval_min' => $cfg['interval'],
                    'max_price'    => $maxPrice,
                    'stop_hours'   => $cfg['stop_hours'],
                    'status'       => 1,
                    'stop_reason'  => '',
                    'next_time'    => $now + mt_rand(60, max(60, $cfg['interval'] * 60)),
                    'last_time'    => 0,
                    'bid_count'    => 0,
                    'creator_type' => 'platform',
                    'creator_id'   => 0,
                    'create_time'  => $now,
                    'update_time'  => $now,
                ]);
                $result['created'][] = (int)$id;
                continue;
            }
            $data = [];
            if ((int)$task['interval_min'] !== $cfg['interval']) {
                $data['interval_min'] = $cfg['interval'];
            }
            if (abs((float)$task['max_price'] - $maxPrice) >= 0.005) {
                $data['max_price'] = $maxPrice;
            }
            if (abs((float)$task['stop_hours'] - $cfg['stop_hours']) >= 0.005) {
                $data['stop_hours'] = $cfg['stop_hours'];
            }
            if ((int)$task['status'] !== 1 && $qualifies()) {
                $data['status']      = 1;
                $data['stop_reason'] = '';
                $data['next_time']   = $now + mt_rand(60, max(60, $cfg['interval'] * 60));
                $result['resumed'][] = (int)$task['id'];
            }
            if ($data) {
                $data['update_time'] = $now;
                Db::name('auto_bid')->where('id', $task['id'])->update($data);
                $result['updated']++;
            }
        } catch (\Throwable $e) {
            // 单件拍品出错不影响其它拍品
            $result['errors'][] = '拍品#' . $goods['id'] . ' ' . $e->getMessage();
        }
    }
    return $result;
}

/**
 * 同步结果的一句话摘要（后台保存设置、脚本输出用）
 */
function platform_auto_bid_sync_summary(array $sync)
{
    if (!$sync['enabled']) {
        return $sync['stopped'] ? '平台自营自动出价已关闭，停止了 ' . count($sync['stopped']) . ' 个脚本任务' : '平台自营自动出价已关闭';
    }
    $parts = [];
    if ($sync['taken']) {
        $parts[] = '接管手动任务 ' . count($sync['taken']) . ' 个';
    }
    if ($sync['created']) {
        $parts[] = '新建任务 ' . count($sync['created']) . ' 个';
    }
    if ($sync['resumed']) {
        $parts[] = '恢复任务 ' . count($sync['resumed']) . ' 个';
    }
    if ($sync['updated']) {
        $parts[] = '同步参数 ' . $sync['updated'] . ' 个';
    }
    return '平台自营自动出价已开启' . ($parts ? '：' . implode('，', $parts) : '');
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
    $hasBid  = Db::name('bid_record')->where('goods_id', $goods['id'])->where('status', 0)->count() > 0;
    // 第一手可直接出起拍价；有出价后每手不低于当前价 + 加价幅度
    $next    = $hasBid ? round($current + $raise, 2) : round($current, 2);
    if ($maxPrice < $next) {
        return '最高出价金额至少要能出一次价：' . ($hasBid ? '当前价 ' . number_format($current, 2) . ' + 加价幅度 ' . number_format($raise, 2) . ' = ' : '暂无出价，第一手为起拍价 ') . number_format($next, 2);
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
 * 执行一轮自动出价
 *   php think bid:auto          → scope = 'manual'：只跑后台 / 代理后台手动添加的任务，不做平台同步
 *   php think platform:auto-bid → scope = 'platform'：先同步平台自营任务，再只跑平台任务
 *
 * 每个运行中的任务：拍品不在拍卖中 → 结束；进入截拍前停止时段 → 结束；当前价已达上限 → 结束；
 * 未到下次出价时间 → 跳过；否则随机挑一个虚拟会员（排除卖家与当前最高价者）按一个加价幅度出价，
 * 出价记录保证金记 0，不冻结余额；给被超过的真实买家发出局通知；触发拍品延时规则。
 *
 * @return array ['tasks' => 检查的任务数, 'bids' => [出价记录ID...], 'logs' => [日志行...]]
 */
function auto_bid_run($limit = 200, $scope = 'all')
{
    $now    = time();
    $result = ['tasks' => 0, 'bids' => [], 'logs' => []];
    $query  = Db::name('auto_bid')->where('status', 1);
    if ($scope === 'platform') {
        // 平台自营：先同步任务（开关/参数/接管/新拍品），再只跑脚本自己的任务
        $sync   = platform_auto_bid_sync();
        $result['sync'] = $sync;
        if ($sync['taken'] || $sync['created'] || $sync['resumed'] || $sync['stopped']) {
            $result['logs'][] = platform_auto_bid_sync_summary($sync);
        }
        $query->where('creator_type', 'platform');
    } elseif ($scope === 'manual') {
        // 后台 / 代理后台手动添加的任务，不碰平台任务
        $query->where('creator_type', '<>', 'platform');
    }
    $tasks  = $query->order('next_time', 'asc')->limit((int)$limit)->select()->toArray();
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
        $topBid  = Db::name('bid_record')->where('goods_id', $goods['id'])->where('status', 0)->order('price', 'desc')->order('id', 'asc')->find();
        $current = max($topBid ? (float)$topBid['price'] : 0, (float)$goods['start_price']);
        $raise   = (float)$goods['raise_price'] > 0 ? (float)$goods['raise_price'] : 1;
        // 第一手可直接出起拍价；有出价后每手不低于当前价 + 加价幅度
        $price   = $topBid ? round($current + $raise, 2) : round($current, 2);
        if ($price > (float)$task['max_price']) {
            $finish('当前价 ' . number_format($current, 2) . ' 已达到最高出价金额 ' . number_format((float)$task['max_price'], 2));
            continue;
        }
        if ((int)$task['next_time'] > $now) {
            continue;
        }
        $candidates = array_values(array_filter($virtualIds, function ($id) use ($goods, $topBid) {
            return (int)$id !== (int)$goods['seller_id'] && (!$topBid || (int)$id !== (int)$topBid['user_id']);
        }));
        if (!$candidates) {
            $result['logs'][] = "任务#{$task['id']} 拍品#{$task['goods_id']} 跳过：没有可用的虚拟会员";
            Db::name('auto_bid')->where('id', $task['id'])->update(['next_time' => auto_bid_next_time($task['interval_min'], $now), 'update_time' => $now]);
            continue;
        }
        $userId = (int)$candidates[array_rand($candidates)];
        // 抢占任务：条件更新 next_time，两个进程同时跑时只有一个能拿到
        if (Db::name('auto_bid')->where('id', $task['id'])->where('status', 1)->where('next_time', '<=', $now)->update(['next_time' => $now + 60]) !== 1) {
            continue;
        }

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
            $price   = $top2 ? round($cur2 + $raise, 2) : round($cur2, 2);
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

/**
 * 模板变量默认输出过滤（config/view.php default_filter）：转义 < > & " '
 */
function html_escape($value)
{
    if (is_array($value) || is_object($value)) {
        return $value;
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 命令级文件锁：同名命令上一轮未结束时本轮直接跳过（返回 null 表示未拿到锁）
 */
function command_lock($name)
{
    $file = app()->getRuntimePath() . $name . '.lock';
    $fp = @fopen($file, 'c');
    if (!$fp) {
        return false;
    }
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return null;
    }
    return $fp;
}

/**
 * 手机号脱敏：保留前 3 位和后 4 位，中间用 **** 代替（不足 7 位的原样返回）
 */
function mask_mobile($mobile)
{
    $mobile = (string)$mobile;
    if (strlen($mobile) < 7) {
        return $mobile;
    }
    return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
}

/**
 * 金额类字段（DECIMAL(10,2)）能存的最大值；写入前用 money_over_max() 校验，避免数据库抛 Out of range
 * 后台 / 代理后台「余额调整」单次最多添加 ADJUST_MAX
 */
const MONEY_MAX = 99999999.99;
const ADJUST_MAX = 10000000;
function money_over_max($v)
{
    return (float)$v > MONEY_MAX;
}
function money_max_text()
{
    return number_format(MONEY_MAX, 2);
}

/**
 * 后台 / 代理后台「完成支付」：代买家用余额支付待付款订单
 * 规则与前台 Order::pay() 一致：应付 = 成交价 − 保证金，保证金从冻结余额抵扣，差额从可用余额扣；
 * 余额不足直接返回失败，不做垫付；成交款仍在买家确认收货后才结算给卖家。
 * 买家是虚拟会员时不真实扣款：直接标记已支付，冻结的保证金解冻回余额；收货信息同样必填，供卖家发货。
 *
 * @param int    $orderId
 * @param array  $ship     ['name' => 收货人, 'mobile' => 电话, 'address' => 地址]
 * @param string $operator 操作人描述，写入买家流水备注，如「后台代付」「代理代付」
 * @return array ['ok' => bool, 'msg' => string]
 */
function pay_order_for_buyer($orderId, array $ship, $operator = '后台代付')
{
    $shipName    = trim((string)($ship['name'] ?? ''));
    $shipMobile  = trim((string)($ship['mobile'] ?? ''));
    $shipAddress = trim((string)($ship['address'] ?? ''));
    // 不论买家是否虚拟会员，收货信息都必填：卖家发货时要看到地址
    if ($shipName === '' || $shipMobile === '' || $shipAddress === '') {
        return ['ok' => false, 'msg' => '请填写收货人、电话和地址'];
    }
    Db::startTrans();
    try {
        $order = Db::name('order')->where('id', (int)$orderId)->lock(true)->find();
        if (!$order) {
            Db::rollback();
            return ['ok' => false, 'msg' => '订单不存在'];
        }
        if ((int)$order['order_status'] !== 0 || (int)$order['pay_status'] !== 0) {
            Db::rollback();
            return ['ok' => false, 'msg' => '订单不是待付款状态'];
        }
        $user = Db::name('user')->where('id', $order['buyer_id'])->lock(true)->find();
        if (!$user) {
            Db::rollback();
            return ['ok' => false, 'msg' => '买家不存在'];
        }
        $isVirtual = (int)$user['is_virtual'] === 1;
        $now       = time();
        if ($isVirtual) {
            // 虚拟会员：不真实扣款；若有冻结的保证金，解冻回可用余额
            $unfreeze = min((float)$order['deposit'], (float)$user['freeze_balance']);
            $upd = ['total_buy' => Db::raw('total_buy+1'), 'update_time' => $now];
            if ($unfreeze > 0) {
                $upd['balance']        = round($user['balance'] + $unfreeze, 2);
                $upd['freeze_balance'] = round($user['freeze_balance'] - $unfreeze, 2);
            }
            Db::name('user')->where('id', $user['id'])->update($upd);
            if ($unfreeze > 0) {
                Db::name('balance_log')->insert([
                    'user_id'     => $user['id'],
                    'type'        => 'refund',
                    'amount'      => $unfreeze,
                    'balance'     => $upd['balance'],
                    'remark'      => '虚拟会员完成支付，保证金解冻：' . $order['order_no'] . '（' . $operator . '）',
                    'create_time' => $now,
                ]);
            }
        } else {
            $payAmount     = round($order['price'] - $order['deposit'], 2);
            $freezeDeduct  = min((float)$order['deposit'], (float)$user['freeze_balance']);
            $balanceDeduct = round($payAmount + ($order['deposit'] - $freezeDeduct), 2);
            if ((float)$user['balance'] < $balanceDeduct) {
                Db::rollback();
                return ['ok' => false, 'msg' => '买家余额不足，还需 ¥' . number_format($balanceDeduct - (float)$user['balance'], 2) . '，请先给买家充值'];
            }
            $newBalance = round($user['balance'] - $balanceDeduct, 2);
            $newFreeze  = round($user['freeze_balance'] - $freezeDeduct, 2);
            Db::name('user')->where('id', $user['id'])->update([
                'balance'        => $newBalance,
                'freeze_balance' => $newFreeze,
                'total_buy'      => Db::raw('total_buy+1'),
                'update_time'    => $now,
            ]);
            if ($payAmount > 0) {
                Db::name('balance_log')->insert([
                    'user_id'     => $user['id'],
                    'type'        => 'pay',
                    'amount'      => -$payAmount,
                    'balance'     => $newBalance,
                    'remark'      => '拍卖订单支付：' . $order['order_no'] . '（' . $operator . '）',
                    'create_time' => $now,
                ]);
            }
        }
        if (Db::name('order')->where('id', $order['id'])->where('pay_status', 0)->where('order_status', 0)->update([
            'pay_status'   => 1,
            'pay_time'     => $now,
            'order_status' => 1,
            'ship_name'    => mb_substr($shipName, 0, 50),
            'ship_mobile'  => mb_substr($shipMobile, 0, 20),
            'ship_address' => mb_substr($shipAddress, 0, 255),
            'update_time'  => $now,
        ]) !== 1) {
            throw new \RuntimeException('订单状态已变化');
        }
        Db::commit();
    } catch (\Throwable $e) {
        Db::rollback();
        return ['ok' => false, 'msg' => '支付失败：' . $e->getMessage()];
    }
    return ['ok' => true, 'msg' => $isVirtual ? '已完成支付（虚拟会员，未扣款），订单转为待发货' : '已完成支付，订单转为待发货'];
}

/**
 * 「完成支付」弹窗所需信息：应付金额、买家余额、默认收货地址
 */
function pay_order_info(array $order)
{
    $user = Db::name('user')->where('id', $order['buyer_id'])->field('id,nickname,mobile,balance,freeze_balance,is_virtual')->find();
    $addr = Db::name('user_address')->where('user_id', $order['buyer_id'])->order('is_default', 'desc')->order('id', 'desc')->find();
    $payAmount    = round($order['price'] - $order['deposit'], 2);
    $freezeDeduct = $user ? min((float)$order['deposit'], (float)$user['freeze_balance']) : 0;
    $balanceNeed  = round($payAmount + ($order['deposit'] - $freezeDeduct), 2);
    return [
        'order_no'      => $order['order_no'],
        'goods_title'   => $order['goods_title'],
        'price'         => number_format((float)$order['price'], 2, '.', ''),
        'deposit'       => number_format((float)$order['deposit'], 2, '.', ''),
        'pay_amount'    => number_format($payAmount, 2, '.', ''),
        'balance_need'  => number_format($balanceNeed, 2, '.', ''),
        'buyer'         => $user ? ($user['nickname'] . '（' . $user['mobile'] . '）') : '',
        'buyer_balance' => $user ? number_format((float)$user['balance'], 2, '.', '') : '0.00',
        'is_virtual'    => $user ? (int)$user['is_virtual'] : 0,
        'enough'        => $user ? ((int)$user['is_virtual'] === 1 || (float)$user['balance'] >= $balanceNeed) : false,
        'ship_name'     => $order['ship_name'] ?: ($addr['name'] ?? ''),
        'ship_mobile'   => $order['ship_mobile'] ?: ($addr['mobile'] ?? ''),
        'ship_address'  => $order['ship_address'] ?: ($addr ? trim($addr['province'] . ' ' . $addr['city'] . ' ' . $addr['district'] . ' ' . $addr['address']) : ''),
    ];
}

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

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
 * 后台操作日志
 * @param string $action 操作描述
 * @param int $adminId
 */
function admin_log($action, $adminId = 0)
{
    try {
        Db::name('admin_log')->insert([
            'admin_id'    => $adminId ?: session('admin_id'),
            'action'      => $action,
            'ip'          => request()->ip(),
            'create_time' => time(),
        ]);
    } catch (\Throwable $e) {
    }
}

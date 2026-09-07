<?php
// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------
// 默认驱动由 .env 的 [CACHE] DRIVER 决定（redis / file），Redis 连接参数在 .env 的 [REDIS] 段。
// 缓存里放的是：登录防爆破计数、结算兜底锁等临时数据，切换驱动不影响业务数据。

use think\facade\Env;

// 配置为 redis 但当前 PHP 未加载 redis 扩展（例如 php.ini 改动后 php-cgi 尚未重启）时自动退回 file，避免整站 500
$driver = Env::get('cache.driver', 'file');
if ($driver === 'redis' && !extension_loaded('redis')) {
    $driver = 'file';
}

return [
    // 默认缓存驱动
    'default' => $driver,

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        'redis' => [
            'type'       => 'redis',
            'host'       => Env::get('redis.host', '127.0.0.1'),
            'port'       => (int)Env::get('redis.port', 6379),
            'password'   => Env::get('redis.password', ''),
            // 使用的库编号
            'select'     => (int)Env::get('redis.select', 0),
            // 连接超时（秒）
            'timeout'    => 3,
            // 是否长连接
            'persistent' => false,
            // 默认有效期 0 永久
            'expire'     => 0,
            // 键前缀：同一台 Redis 上多个站点共用时用于隔离
            'prefix'     => Env::get('redis.prefix', 'jp:'),
            'tag_prefix' => 'tag:',
            'serialize'  => [],
        ],
    ],
];

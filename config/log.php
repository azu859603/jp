<?php
return [
    // 默认日志记录通道
    'default'      => env('log.channel', 'file'),
    // 日志记录级别
    'level'        => [],
    // 日志类型记录的通道 ['error'=>'email',...]
    'type_channel' => [],
    // 关闭全局日志写入
    'close'        => false,
    // 全局日志处理 支持闭包类型
    'processor'    => null,

    // 日志通道列表
    'channels'     => [
        'file' => [
            // 日志记录方式
            'type'           => 'File',
            // 日志保存目录
            // 框架日志单独放 runtime/log/app/，与定时脚本自己的 *.log 分开，30 天轮转只清理这个目录
            'path'           => runtime_path('log' . DIRECTORY_SEPARATOR . 'app'),
            // 单文件日志写入
            'single'         => false,
            // 独立日志级别
            'apart_level'    => ['error'],   // error 单独写 runtime/log/YYYYMM/DD_error.log
            // 最大日志文件数量
            'max_files'      => 30,          // 只保留最近 30 天
            // 使用JSON格式记录
            'json'           => false,
            // 日志处理
            'processor'      => null,
            // 关闭通道日志写入
            'close'          => false,
            // 日志输出格式化
            'format'         => '[%s][%s] %s',
            // 是否实时写入
            'realtime_write' => false,
        ],
        // 其它日志通道配置
    ],
];

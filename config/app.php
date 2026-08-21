<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => env('app.default_timezone', 'Asia/Shanghai'),

    // 应用映射（自动多应用模式有效）
    'app_map'          => ['admin1314' => 'admin'],
    // 快速模式：URL第一段不是应用目录时，直接用默认应用解析
    'app_express'      => true,
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => ['admin', 'common'],

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => app()->getRootPath() . 'app/error_tpl.tpl',
    'dispatch_error_tmpl'   => app()->getRootPath() . 'app/error_tpl.tpl',

    // 异常页面的模板文件
    'exception_tmpl'        => app()->getRootPath() . 'app/exception_tpl.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'         => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'        => false,
];

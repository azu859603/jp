<?php
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

return [
    // pathinfo分隔符
    'pathinfo_depr'         => '/',
    // URL伪静态后缀
    'url_html_suffix'       => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'      => true,
    // 是否开启路由延迟解析
    'url_lazy_route'        => false,
    // 是否强制使用路由
    'url_route_must'        => false,
    // 合并路由规则
    'route_rule_merge'      => false,
    // 路由是否完全匹配
    'route_complete_match'  => false,
    // 访问地址时是否使用子域名
    'use_domain_root'       => false,
    // 是否开启自动转换URL中的字母和数字
    'url_convert'           => true,
    // 默认的路由变量规则
    'default_route_pattern' => '[\w\.]+',
    // URL域名绑定
    'domain_bind'           => [],
    // URL域名绑定参数
    'domain_bind_rule'      => [],
    // URL参数绑定 用于生成url地址
    'url_param_bind_type'   => 1,
    // 参数 bind type
    'url_param_type'        => 0,
];

<?php
// 全局中间件定义文件（应用基础路径下）
return [
    \think\middleware\SessionInit::class,
    // 改状态请求的 Origin / Referer 必须与本站一致（跨站表单 / 脚本提交直接拒绝）
    \app\middleware\CsrfOrigin::class,
];

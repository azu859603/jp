<?php
// 全局中间件定义文件（应用基础路径下）
return [
    \think\middleware\SessionInit::class,
    \think\middleware\LoadLangPack::class,
];

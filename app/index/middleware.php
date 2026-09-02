<?php
// index 应用中间件定义文件
//
// 语言检测必须放在「应用级」而非全局：
// think-multi-app 的 MultiApp 中间件在解析应用后会执行
// $this->app->loadLangPack($this->app->lang->defaultLangSet())，
// 无条件把语言重置回默认值。全局中间件跑在它之前，设置会被覆盖；
// 应用级中间件跑在它之后，才能真正生效。
return [
    \app\middleware\LangDetect::class,
];

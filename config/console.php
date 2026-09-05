<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 拍卖结算：php think settle（每分钟由定时任务调用）
        'settle' => \app\command\Settle::class,
        // 待付款超时取消：php think order:timeout（每 5 分钟由定时任务调用）
        'order:timeout' => \app\command\OrderTimeout::class,
        // 待收货自动确认：php think order:confirm（每小时）
        'order:confirm' => \app\command\OrderConfirm::class,
        // 待发货催发货提醒：php think order:remind（每天）
        'order:remind'  => \app\command\OrderRemind::class,
        // 初始化前台用户数据（手动执行，TRUNCATE 清空会员及衍生数据）：php think user:init --force
        'user:init'     => \app\command\UserInit::class,
    ],
];

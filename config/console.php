<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 拍卖结算：php think settle（每分钟；只做结算，不串联其它命令）
        'settle' => \app\command\Settle::class,
        // 待付款超时取消：php think order:timeout（每 5 分钟由定时任务调用）
        'order:timeout' => \app\command\OrderTimeout::class,
        // 待收货自动确认：php think order:confirm（每小时）
        'order:confirm' => \app\command\OrderConfirm::class,
        // 待发货催发货提醒：php think order:remind（每天）
        'order:remind'  => \app\command\OrderRemind::class,
        // 初始化前台用户数据（手动执行，TRUNCATE 清空会员及衍生数据）：php think user:init --force
        'user:init'     => \app\command\UserInit::class,
        // 采集远端商品并下载图片到本地（手动执行）：php think goods:collect [--start=11] [--end=N] [--limit=N] [--update]
        'goods:collect' => \app\command\GoodsCollect::class,
        // 「自营店铺」卖家的流拍商品自动上架：php think goods:auto-relist（每分钟，独立执行）
        'goods:auto-relist' => \app\command\GoodsAutoRelist::class,
        // 竞拍中商品自动增加浏览量：php think goods:auto-views（建议每 5~10 分钟，独立执行；开关与增加量在后台设置）
        'goods:auto-views' => \app\command\GoodsAutoViews::class,
        // 虚拟用户自动出价：php think bid:auto（每分钟，独立执行；只跑后台 / 代理后台手动添加的任务）
        'bid:auto' => \app\command\BidAuto::class,
        // 平台自营（「自营店铺」卖家）拍品自动出价：php think platform:auto-bid（每分钟，独立执行；只同步并跑平台自营任务）
        'platform:auto-bid' => \app\command\PlatformAutoBid::class,
        // 把「用户」开头的虚拟会员昵称改成中文昵称（手动执行）：php think user:rename-virtual [--force] [--limit=N] [--all]
        'user:rename-virtual' => \app\command\VirtualNickname::class,
        // 批量创建「自营店铺」卖家会员（手动执行，只建会员）：php think shop:self-create [--count=30] [--force]
        'shop:self-create' => \app\command\SelfShopCreate::class,
        // 把某个卖家的商品平分给全部自营店铺会员（手动执行，只动商品）：php think shop:self-assign --from=1 [--force]
        'shop:self-assign' => \app\command\SelfShopAssign::class,
        // 清理表结构缓存（数据库加字段后执行）
        'schema:clear'  => \app\command\SchemaClear::class,
    ],
];

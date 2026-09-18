<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 竞拍中商品自动增加浏览量
 *
 * 用法：php think goods:auto-views
 * 部署：独立命令，建议每 5~10 分钟执行一次（执行越频繁增长越快）。
 *
 * 后台「基础设置 › 浏览量自动增加」配置：
 *   auto_view_enabled  开关（关闭时脚本空跑，不改任何数据）
 *   auto_view_amount   每次执行每件商品增加的基准量
 *   auto_view_float    浮动比例（%），每件商品实际增加量在 基准量 ×(1 ± 浮动比例) 内随机
 *
 * 逻辑在 app/common.php 的 auto_increase_views()。
 * 心跳 runtime/auto_views.heartbeat；出错时写 runtime/log/auto_views.log。
 */
class GoodsAutoViews extends Command
{
    protected function configure()
    {
        $this->setName('goods:auto-views')->setDescription('竞拍中商品自动增加浏览量（开关、增加量、浮动比例由后台设置）');
    }

    protected function execute(Input $input, Output $output)
    {
        // 上一轮还没跑完时本轮直接跳过，避免两轮重叠执行
        $lock = command_lock('auto_views');
        if ($lock === null) {
            $output->writeln('[' . date('Y-m-d H:i:s') . '] 上一轮仍在执行，本轮跳过');
            return 0;
        }
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'auto_views.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'auto_views.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            $r = auto_increase_views();
        } catch (\Throwable $e) {
            $msg = $stamp . 'ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' ok goods=' . $r['goods'] . ' total=' . $r['total']);

        if (!$r['enabled']) {
            $output->writeln($stamp . '未开启（开关关闭或增加量为 0），跳过');
            return 0;
        }
        if ($r['goods'] === 0) {
            $output->writeln($stamp . '没有正在竞拍的商品');
            return 0;
        }
        $output->writeln($stamp . "竞拍中商品 {$r['goods']} 件，每件 +{$r['min']}~{$r['max']}（基准 {$r['amount']}，浮动 {$r['float']}%），本次共增加 {$r['total']}");
        return 0;
    }
}

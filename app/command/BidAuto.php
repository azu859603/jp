<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 虚拟用户自动出价定时任务
 *
 * 用法：php think bid:auto
 * 部署：每分钟执行一次（独立命令，不与 settle / platform:auto-bid 串联）。
 *       只处理后台 / 代理后台手动添加的任务；平台自营任务由 php think platform:auto-bid 负责。
 *
 * 任务由后台 / 代理后台「竞拍管理 › 自动出价」按拍品配置：出价间隔、最高出价金额、截拍前停止小时数。
 * 执行逻辑在 app/common.php 的 auto_bid_run()。
 * 心跳 runtime/auto_bid.heartbeat；有出价或出错时写 runtime/log/auto_bid.log。
 */
class BidAuto extends Command
{
    protected function configure()
    {
        $this->setName('bid:auto')->setDescription('按后台配置让虚拟用户自动为指定拍品出价');
    }

    protected function execute(Input $input, Output $output)
    {
        // 上一轮还没跑完时本轮直接跳过，避免两轮重叠执行
        $lock = command_lock('bid_auto');
        if ($lock === null) {
            $output->writeln('[' . date('Y-m-d H:i:s') . '] 上一轮仍在执行，本轮跳过');
            return 0;
        }
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'auto_bid.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'auto_bid.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            $result = auto_bid_run(200, 'manual');
        } catch (\Throwable $e) {
            $msg = $stamp . 'ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' ok tasks=' . $result['tasks'] . ' bids=' . count($result['bids']));

        if ($result['tasks'] === 0) {
            $output->writeln($stamp . '没有运行中的自动出价任务');
            return 0;
        }
        foreach ($result['logs'] as $line) {
            $output->writeln($stamp . $line);
            @file_put_contents($logFile, $stamp . $line . PHP_EOL, FILE_APPEND);
        }
        if (empty($result['logs'])) {
            $output->writeln($stamp . "检查 {$result['tasks']} 个任务，本次无需出价");
        }
        return 0;
    }
}

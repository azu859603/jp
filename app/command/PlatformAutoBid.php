<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 平台自营（会员属性为「自营店铺」的卖家）拍品自动出价
 *
 * 用法：php think platform:auto-bid
 * 部署：每分钟执行一次（独立命令，不与 settle / bid:auto 串联）。
 *       只同步并执行平台自营任务；后台 / 代理后台手动添加的任务由 php think bid:auto 负责。
 *
 * 后台「系统设置 › 基础设置 › 平台自营自动出价」控制：
 *   - 开关：开启后自营店铺卖家发布的所有拍卖中拍品由脚本安排虚拟会员出价；后台 / 代理后台不能再为这些拍品手动添加任务，
 *           之前添加过的任务被脚本接管；关闭后脚本任务停止，恢复可手动添加。
 *   - 出价间隔（分钟）、最高出价（起拍价的倍数）、截拍前停止时间（小时）。
 * 同步逻辑 platform_auto_bid_sync()、出价逻辑 auto_bid_run() 都在 app/common.php。
 * 心跳 runtime/platform_auto_bid.heartbeat；有动作或出错时写 runtime/log/auto_bid.log。
 */
class PlatformAutoBid extends Command
{
    protected function configure()
    {
        $this->setName('platform:auto-bid')->setDescription('平台自营（自营店铺卖家）拍品由虚拟会员自动出价');
    }

    protected function execute(Input $input, Output $output)
    {
        $lock = command_lock('platform_auto_bid');
        if ($lock === null) {
            $output->writeln('[' . date('Y-m-d H:i:s') . '] 上一轮仍在执行，本轮跳过');
            return 0;
        }
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'platform_auto_bid.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'auto_bid.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            // scope=platform：先 platform_auto_bid_sync()，再只跑 creator_type=platform 的任务
            $result = auto_bid_run(200, 'platform');
        } catch (\Throwable $e) {
            $msg = $stamp . 'ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        $sync = $result['sync'] ?? ['enabled' => false];
        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ($sync['enabled'] ? ' ok' : ' disabled') . ' tasks=' . $result['tasks'] . ' bids=' . count($result['bids']));

        if (!$sync['enabled']) {
            $output->writeln($stamp . '未启用（后台「平台自营自动出价」为关闭）' . ($sync['stopped'] ?? [] ? '，已停止 ' . count($sync['stopped']) . ' 个脚本任务' : ''));
        } else {
            $output->writeln($stamp . platform_auto_bid_sync_summary($sync));
        }
        foreach ($result['logs'] as $line) {
            $output->writeln($stamp . $line);
            @file_put_contents($logFile, $stamp . $line . PHP_EOL, FILE_APPEND);
        }
        if ($sync['enabled'] && empty($result['logs'])) {
            $output->writeln($stamp . "检查 {$result['tasks']} 个任务，本次无需出价");
        }
        return 0;
    }
}

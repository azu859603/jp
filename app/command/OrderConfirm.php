<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 待收货订单自动确认定时任务
 *
 * 用法：php think order:confirm
 * 部署：每小时执行一次
 *
 * 后台「基础设置」配置项 order_auto_confirm_days：发货后超过 N 天买家未确认收货则自动完成，0 为不启用。
 * 完成逻辑在 app/index/common.php 的 complete_order()。
 * 心跳 runtime/order_confirm.heartbeat；有订单被完成或出错时写 runtime/log/order_confirm.log。
 */
class OrderConfirm extends Command
{
    protected function configure()
    {
        $this->setName('order:confirm')->setDescription('发货后超过 N 天未确认收货的订单自动完成');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!function_exists('complete_order')) {
            require_once $this->app->getBasePath() . 'index' . DIRECTORY_SEPARATOR . 'common.php';
        }
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'order_confirm.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'order_confirm.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            $days = (int)get_setting('order_auto_confirm_days', 0);
            if ($days <= 0) {
                @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' disabled');
                $output->writeln($stamp . '未启用（后台「自动确认收货天数」为 0）');
                return 0;
            }
            $deadline = time() - $days * 86400;
            $ids = Db::name('order')
                ->where('order_status', 2)
                ->where('ship_time', '>', 0)
                ->where('ship_time', '<=', $deadline)
                ->order('id', 'asc')
                ->column('id');

            $done = []; $skip = [];
            foreach ($ids as $id) {
                if (complete_order((int)$id, "发货后超过{$days}天未确认收货，系统自动确认")) {
                    $done[] = $id;
                } else {
                    $skip[] = $id;
                }
            }
        } catch (\Throwable $e) {
            $msg = $stamp . 'ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' ok confirmed=' . count($done));
        if (empty($ids)) {
            $output->writeln($stamp . "无需自动确认的订单（阈值 {$days} 天）");
            return 0;
        }
        $line = $stamp . '自动确认 ' . count($done) . ' 单（阈值 ' . $days . ' 天）ids=' . implode(',', $done)
              . (count($skip) ? '，跳过 ' . count($skip) . ' 单 ids=' . implode(',', $skip) : '');
        $output->writeln($line);
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
        return 0;
    }
}

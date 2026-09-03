<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 待发货超时提醒定时任务
 *
 * 用法：php think order:remind
 * 部署：每天执行一次
 *
 * 后台「基础设置」配置项 order_ship_remind_days：付款后超过 N 天卖家未发货则发站内信催发货，0 为不启用。
 * 提醒逻辑在 app/index/common.php 的 remind_unshipped_order()，同一订单 24 小时内只提醒一次。
 * 心跳 runtime/order_remind.heartbeat；有提醒发出或出错时写 runtime/log/order_remind.log。
 */
class OrderRemind extends Command
{
    protected function configure()
    {
        $this->setName('order:remind')->setDescription('付款后超过 N 天未发货的订单，给卖家发站内信催发货');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!function_exists('remind_unshipped_order')) {
            require_once $this->app->getBasePath() . 'index' . DIRECTORY_SEPARATOR . 'common.php';
        }
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'order_remind.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'order_remind.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            $days = (int)get_setting('order_ship_remind_days', 0);
            if ($days <= 0) {
                @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' disabled');
                $output->writeln($stamp . '未启用（后台「未发货提醒天数」为 0）');
                return 0;
            }
            $deadline = time() - $days * 86400;
            $ids = Db::name('order')
                ->where('order_status', 1)
                ->where('pay_time', '>', 0)
                ->where('pay_time', '<=', $deadline)
                ->order('id', 'asc')
                ->column('id');

            $sent = []; $skip = [];
            foreach ($ids as $id) {
                $r = remind_unshipped_order((int)$id);
                if ($r === 'sent') {
                    $sent[] = $id;
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

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' ok sent=' . count($sent));
        if (empty($ids)) {
            $output->writeln($stamp . "无需提醒的订单（阈值 {$days} 天）");
            return 0;
        }
        $line = $stamp . '发出提醒 ' . count($sent) . ' 单（阈值 ' . $days . ' 天）ids=' . implode(',', $sent)
              . (count($skip) ? '，24小时内已提醒跳过 ' . count($skip) . ' 单 ids=' . implode(',', $skip) : '');
        $output->writeln($line);
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
        return 0;
    }
}

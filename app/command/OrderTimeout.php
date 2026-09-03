<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 待付款订单超时取消定时任务
 *
 * 用法：php think order:timeout
 * 部署：每 5 分钟执行一次
 *
 * 后台「基础设置」两个配置项：
 *   order_pay_timeout_hours  超过多少小时未付款则取消，0 为不启用
 *   order_timeout_deposit    保证金处理：forfeit_platform 没收 | to_seller 赔付卖家 | refund_buyer 退还买家
 *
 * 取消逻辑在 app/index/common.php 的 cancel_unpaid_order()，与买家手动取消共用。
 * 心跳 runtime/order_timeout.heartbeat 每次刷新；有订单被取消或出错时写 runtime/log/order_timeout.log。
 */
class OrderTimeout extends Command
{
    protected function configure()
    {
        $this->setName('order:timeout')->setDescription('取消超时未付款的订单，处理保证金，商品回到可重拍状态');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!function_exists('cancel_unpaid_order')) {
            require_once $this->app->getBasePath() . 'index' . DIRECTORY_SEPARATOR . 'common.php';
        }
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'order_timeout.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'order_timeout.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            $hours = (int)get_setting('order_pay_timeout_hours', 0);
            if ($hours <= 0) {
                @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' disabled');
                $output->writeln($stamp . '未启用（后台「待付款超时」为 0）');
                return 0;
            }
            $mode = (string)get_setting('order_timeout_deposit', 'forfeit_platform');
            if (!in_array($mode, ['forfeit_platform', 'to_seller', 'refund_buyer'], true)) {
                $mode = 'forfeit_platform';
            }

            $deadline = time() - $hours * 3600;
            $ids = Db::name('order')
                ->where('pay_status', 0)
                ->where('order_status', 0)
                ->where('create_time', '<=', $deadline)
                ->order('id', 'asc')
                ->column('id');

            $done = [];
            $skip = [];
            foreach ($ids as $id) {
                $r = cancel_unpaid_order((int)$id, "超过{$hours}小时未付款，系统自动取消", $mode);
                if ($r === false) {
                    $skip[] = $id;
                } else {
                    $done[$id] = $r;
                }
            }
        } catch (\Throwable $e) {
            $msg = $stamp . 'ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' ok cancelled=' . count($done));

        if (empty($ids)) {
            $output->writeln($stamp . "无超时订单（阈值 {$hours} 小时）");
            return 0;
        }
        $line = $stamp . '取消 ' . count($done) . ' 单（阈值 ' . $hours . ' 小时，保证金：' . $mode . '）'
              . (count($skip) ? '，跳过 ' . count($skip) . ' 单 ids=' . implode(',', $skip) : '')
              . ' 明细=' . json_encode($done, JSON_UNESCAPED_UNICODE);
        $output->writeln($line);
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
        return 0;
    }
}

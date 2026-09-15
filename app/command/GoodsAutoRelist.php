<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 指定卖家流拍商品自动重新上架
 *
 * 用法：php think goods:auto-relist
 * 部署：每分钟执行一次；`php think settle` 结算完成后也会自动调用一次，
 *       已部署 settle 定时任务的环境可以不单独配置本命令。
 *
 * 后台「基础设置 › 竞拍规则」配置：
 *   auto_relist_seller_id  自动上架的卖家会员 ID（默认 1）
 *   auto_relist_hours      重新上架后的拍卖时长（小时），0 为不自动上架
 *
 * 截拍时间 = 上架时间 + 拍卖时长 + 每件随机 0~6 小时；上架逻辑在 app/common.php 的 auto_relist_failed_goods()。
 * 心跳 runtime/auto_relist.heartbeat；有商品被上架或出错时写 runtime/log/auto_relist.log。
 */
class GoodsAutoRelist extends Command
{
    protected function configure()
    {
        $this->setName('goods:auto-relist')->setDescription('指定卖家的流拍商品自动重新上架（拍卖时长由后台设置，0 为关闭）');
    }

    protected function execute(Input $input, Output $output)
    {
        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'auto_relist.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'auto_relist.log';
        $stamp     = '[' . date('Y-m-d H:i:s') . '] ';

        try {
            $result = auto_relist_failed_goods();
        } catch (\Throwable $e) {
            $msg = $stamp . 'ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' ok count=' . count($result['ids']));

        if (!$result['enabled']) {
            $output->writeln($stamp . '未开启（拍卖时长为 0），跳过');
            return 0;
        }
        if (empty($result['ids'])) {
            $output->writeln($stamp . "卖家 {$result['seller_id']} 没有流拍商品");
            return 0;
        }
        $line = $stamp . "卖家 {$result['seller_id']} 自动上架 " . count($result['ids']) . " 件，拍卖时长 {$result['hours']} 小时，截拍 "
              . date('Y-m-d H:i', $result['end_time']) . ' 起 0~6 小时内随机 ids=' . implode(',', $result['ids']);
        $output->writeln($line);
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
        return 0;
    }
}

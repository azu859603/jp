<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 拍卖结算定时任务
 *
 * 用法：php think settle
 * 部署：每分钟执行一次（Linux crontab / Windows 计划任务）
 *
 * 结算逻辑本身在 app/index/common.php 的 settle_goods() / settle_expired_goods()，
 * 这里只负责按时调用、输出结果、写心跳，不复制业务规则。
 *
 * 心跳文件 runtime/settle.heartbeat 每次运行都会刷新，可据此监控定时任务是否存活；
 * 有商品被结算或发生异常时追加写入 runtime/log/settle.log。
 */
class Settle extends Command
{
    protected function configure()
    {
        $this->setName('settle')->setDescription('结算已到期的拍卖（成交 / 流拍）');
    }

    protected function execute(Input $input, Output $output)
    {
        // 多应用模式下 CLI 不会自动加载 index 应用的公共函数，这里显式引入
        if (!function_exists('settle_expired_goods')) {
            require_once $this->app->getBasePath() . 'index' . DIRECTORY_SEPARATOR . 'common.php';
        }

        $runtime   = $this->app->getRuntimePath();
        $heartbeat = $runtime . 'settle.heartbeat';
        $logFile   = $runtime . 'log' . DIRECTORY_SEPARATOR . 'settle.log';
        $start     = microtime(true);

        try {
            $results = settle_expired_goods();
        } catch (\Throwable $e) {
            $msg = '[' . date('Y-m-d H:i:s') . '] ERROR ' . $e->getMessage();
            $output->writeln('<error>' . $msg . '</error>');
            @file_put_contents($logFile, $msg . PHP_EOL, FILE_APPEND);
            @file_put_contents($heartbeat, date('Y-m-d H:i:s') . ' error');
            return 1;
        }

        $cost  = round((microtime(true) - $start) * 1000);
        $total = count($results);
        $deal  = count(array_filter($results, function ($r) { return $r === '成交'; }));
        $fail  = count(array_filter($results, function ($r) { return $r === '流拍'; }));
        $err   = $total - $deal - $fail;   // settle_goods 返回 false 的（并发已被处理 / 事务失败）

        @file_put_contents($heartbeat, date('Y-m-d H:i:s') . " ok total={$total}");

        if ($total === 0) {
            $output->writeln('[' . date('Y-m-d H:i:s') . "] 无到期商品（{$cost}ms）");
            return 0;
        }

        $line = '[' . date('Y-m-d H:i:s') . "] 结算 {$total} 件：成交 {$deal}，流拍 {$fail}"
              . ($err > 0 ? "，未处理 {$err}" : '') . "（{$cost}ms） ids=" . implode(',', array_keys($results));
        $output->writeln($line);
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
        return 0;
    }
}

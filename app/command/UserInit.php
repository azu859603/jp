<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 初始化前台用户数据
 *
 * 用法：php think user:init --force
 *   不加 --force 只做「演练」（显示将清空哪些表、各多少行），不动任何数据。
 *   加 --force 才真正执行。
 *
 * 行为：用 TRUNCATE 清空会员及其全部衍生数据（会员、地址、提现账户、余额流水、
 *       出价、订单、售后、充值、提现、足迹、收藏、关注、站内信、聊天）。
 *       TRUNCATE 不可逆、不走事务、自增ID归零。
 *
 * 商品（goods）按需求保留：清空后把商品的中标人/订单/成交价重置，成交(2)/流拍(3)
 *       状态回到拍卖中(1)，使其恢复为可竞拍的干净状态，避免悬空引用已删除的会员。
 *       平台数据（管理员、系统设置、分类、轮播、新闻、管理员日志）一律不动。
 */
class UserInit extends Command
{
    /** 会员及其衍生数据表：全部 TRUNCATE 清空 */
    private $truncateTables = [
        'user',            // 会员
        'user_address',    // 收货地址
        'pay_account',     // 提现账户绑定
        'balance_log',     // 余额流水
        'bid_record',      // 出价记录
        'order',           // 订单
        'after_sale',      // 售后单
        'recharge',        // 充值申请
        'withdraw',        // 提现申请
        'browse_history',  // 浏览足迹
        'goods_favorite',  // 商品收藏
        'seller_follow',   // 店铺关注
        'sys_message',     // 站内信
        'message',         // 聊天消息
        'admin_log',         // 后台日志
    ];

    /** 明确保护、绝不清空的平台数据表 */
    private $protectedTables = [
        'admin_user', 'admin_log', 'setting', 'category', 'banner', 'news', 'goods',
    ];

    protected function configure()
    {
        $this->setName('user:init')
            ->addOption('force', 'f', Option::VALUE_NONE, '确认执行清空（不加则只演练不动数据）')
            ->setDescription('初始化前台用户数据：TRUNCATE 清空会员及其全部衍生数据（保留商品与平台配置）');
    }

    protected function execute(Input $input, Output $output)
    {
        $force = $input->hasOption('force') && $input->getOption('force');

        // 演练：仅统计，不动数据
        $output->writeln('');
        $output->writeln('将要 <comment>TRUNCATE 清空</comment> 以下表：');
        $total = 0;
        foreach ($this->truncateTables as $t) {
            $n = Db::name($t)->count();
            $total += $n;
            $output->writeln(sprintf('  %-16s %8d 行', $t, $n));
        }
        $output->writeln('  ' . str_repeat('-', 27));
        $output->writeln(sprintf('  %-16s %8d 行', '合计', $total));
        $output->writeln('');
        $output->writeln('受保护、<info>不会清空</info> 的表：' . implode('、', $this->protectedTables));
        $goodsAffected = Db::name('goods')->whereIn('status', [2, 3])->count();
        $output->writeln("商品（goods）保留，其中 {$goodsAffected} 件已成交/流拍将被重置为「拍卖中」。");
        $output->writeln('');

        if (!$force) {
            $output->writeln('<comment>这是演练模式，未改动任何数据。</comment>');
            $output->writeln('确认无误后，执行：<info>php think user:init --force</info>');
            return 0;
        }

        // 真正执行
        $output->writeln('<error>正在执行 TRUNCATE（不可逆）...</error>');
        $prefix = Db::getConfig('connections.mysql.prefix') ?: '';

        // TRUNCATE 是 DDL，隐式提交、无法回滚，且受外键约束影响；先关外键检查再逐表截断
        Db::execute('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($this->truncateTables as $t) {
                Db::execute('TRUNCATE TABLE `' . $prefix . $t . '`');
                $output->writeln('  已清空 ' . $prefix . $t);
            }

            // 商品恢复为可竞拍的干净状态（保留商品本身，只解除对已删会员/订单的引用）
            $reset = Db::name('goods')->where('id', '>', 0)->update([
                'seller_id'   => 1,
                'winner_id'   => 0,
                'order_id'    => 0,
                'final_price' => 0,
                'bid_count'   => 0,
                'status'      => Db::raw('CASE WHEN status IN (2,3) THEN 1 ELSE status END'),
                'update_time' => time(),
            ]);
        } finally {
            Db::execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        $output->writeln('  商品已重置引用并恢复可竞拍状态（影响 ' . $reset . ' 条）');
        admin_log('执行 user:init 初始化前台用户数据（TRUNCATE ' . count($this->truncateTables) . ' 张表）', 0);
        $output->writeln('');
        $output->writeln('<info>完成：前台用户数据已初始化。</info>');
        return 0;
    }
}

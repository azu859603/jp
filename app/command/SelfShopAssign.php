<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 把某个卖家的商品平均分配给「自营店铺」会员（只动商品，不建会员）
 *
 * 用法：
 *   php think shop:self-assign --from=1                        演练，只打印怎么分，不改数据
 *   php think shop:self-assign --from=1 --force                真正分配：会员 1 的商品平分给全部自营店铺会员
 *   php think shop:self-assign --from=1 --exclude-from --force  源卖家自己不留货，商品全部转走
 *   php think shop:self-assign --from=1 --to=101,102,103 --force  只分给指定的几个会员
 *   php think shop:self-assign --from=1 --include-sold --force   连已成交（有订单 / 得标人）的商品一起转，并同步订单卖家
 *
 * 分配目标：默认是库里全部「自营店铺」会员（user.is_self_shop=1，在主后台「会员管理 › 会员列表 ›
 * 编辑会员 › 店铺属性」里设置）。源卖家自己只要也是自营店铺，就同样占一个名额、留下属于自己的那一份；
 * 不想让它留货就加 --exclude-from。店铺会员用 php think shop:self-create 批量创建。
 *
 * 商品范围：源卖家名下、没有订单也没有得标人的商品（即「未成交」的）。
 * 分配规则：按商品状态（拍卖中 / 流拍 / 待审核…）分组后打乱，再轮流发给各店铺，
 * 保证每个店铺拿到的各状态数量最多相差 1 件。
 */
class SelfShopAssign extends Command
{
    protected function configure()
    {
        $this->setName('shop:self-assign')
            ->addOption('force', 'f', Option::VALUE_NONE, '确认执行（不加则只演练，不修改数据）')
            ->addOption('from', null, Option::VALUE_OPTIONAL, '源卖家会员 ID，--force 时必填')
            ->addOption('to', null, Option::VALUE_OPTIONAL, '只分给这些会员 ID（逗号分隔），默认全部自营店铺会员')
            ->addOption('exclude-from', null, Option::VALUE_NONE, '源卖家自己不参与分配，商品全部转走')
            ->addOption('include-sold', null, Option::VALUE_NONE, '连已有订单 / 得标人的商品一起转（同步更新订单卖家）')
            ->setDescription('把某个卖家的商品平均分配给「自营店铺」会员（只动商品，不建会员）');
    }

    protected function execute(Input $input, Output $output)
    {
        $force    = (bool)$input->getOption('force');
        $excl     = (bool)$input->getOption('exclude-from');
        $withSold = (bool)$input->getOption('include-sold');
        $fromOpt  = $input->getOption('from');
        $toOpt    = trim((string)$input->getOption('to'));
        $fromId   = ($fromOpt === null || $fromOpt === '') ? 0 : (int)$fromOpt;

        // 真正执行时必须显式写明源卖家，避免误操作
        if ($fromId <= 0) {
            $output->writeln('<error>请用 --from 指定源卖家会员 ID，例如 --from=1</error>');
            return 1;
        }
        $from = Db::name('user')->where('id', $fromId)->find();
        if (!$from) {
            $output->writeln('<error>源卖家会员 ' . $fromId . ' 不存在</error>');
            return 1;
        }

        // ---------- 1. 分配目标 ----------
        if ($toOpt !== '') {
            $wanted = array_values(array_unique(array_filter(array_map('intval', explode(',', $toOpt)))));
            if (empty($wanted)) {
                $output->writeln('<error>--to 里没有有效的会员 ID</error>');
                return 1;
            }
            $shops = Db::name('user')->whereIn('id', $wanted)->field('id,mobile,email,nickname,is_self_shop')
                ->order('id', 'asc')->select()->toArray();
            $miss  = array_diff($wanted, array_map('intval', array_column($shops, 'id')));
            if ($miss) {
                $output->writeln('<error>--to 里这些会员不存在：' . implode(',', $miss) . '</error>');
                return 1;
            }
            $notSelf = array_column(array_filter($shops, function ($u) { return (int)$u['is_self_shop'] !== 1; }), 'id');
            if ($notSelf) {
                $output->writeln('<comment>提示：--to 里这些会员不是「自营店铺」：' . implode(',', $notSelf) . '（仍会分给它们）</comment>');
            }
        } else {
            $shops = Db::name('user')->where('is_self_shop', 1)->field('id,mobile,email,nickname,is_self_shop')
                ->order('id', 'asc')->select()->toArray();
        }
        if ($excl) {
            $shops = array_values(array_filter($shops, function ($u) use ($fromId) { return (int)$u['id'] !== $fromId; }));
        }
        if (empty($shops)) {
            $output->writeln('<error>没有可分配的目标店铺'
                . ($toOpt === '' ? '：库里没有「自营店铺」会员，先执行 php think shop:self-create --force 创建' : '')
                . '</error>');
            return 1;
        }
        $count   = count($shops);
        $fromIn  = in_array($fromId, array_map('intval', array_column($shops, 'id')), true);

        // ---------- 2. 待分配的商品 ----------
        $query   = Db::name('goods')->where('seller_id', $fromId);
        $total   = (clone $query)->count();
        $skipped = (clone $query)->where(function ($q) {
            $q->where('order_id', '>', 0)->whereOr('winner_id', '>', 0);
        })->count();
        if (!$withSold) {
            $query->where('order_id', 0)->where('winner_id', 0);
        }
        $goods = (clone $query)->field('id,status')->order('id', 'asc')->select()->toArray();
        if (empty($goods)) {
            $output->writeln('<error>会员 ' . $fromId . ' 没有可分配的商品（名下共 ' . $total . ' 件，其中已成交 ' . $skipped . ' 件）</error>');
            return 1;
        }

        $statusName = [0 => '待审核', 1 => '拍卖中', 2 => '已成交', 3 => '流拍', 4 => '已下架', 5 => '审核未过'];
        $byStatus   = [];
        foreach ($goods as $g) {
            $byStatus[(int)$g['status']][] = (int)$g['id'];
        }
        ksort($byStatus);

        $output->writeln('源卖家：会员 ' . $fromId . '（' . $from['nickname'] . '），名下商品共 ' . $total . ' 件');
        foreach ($byStatus as $st => $list) {
            $output->writeln('  ' . ($statusName[$st] ?? ('状态' . $st)) . '：' . count($list) . ' 件');
        }
        if ($skipped > 0) {
            $output->writeln($withSold
                ? '  其中已成交（有订单 / 得标人）' . $skipped . ' 件也会一起转移，订单的卖家同步更新'
                : '  已跳过已成交（有订单 / 得标人）' . $skipped . ' 件，需要一起转请加 --include-sold');
        }
        $output->writeln('分配目标：' . $count . ' 个店铺'
            . ($toOpt !== '' ? '（--to 指定）' : '（库里全部「自营店铺」会员）')
            . ($fromIn ? '，含源卖家自己，它会留下属于自己的那一份' : ($excl ? '，源卖家自己不参与（--exclude-from）' : '')));
        $output->writeln('本次分配：' . count($goods) . ' 件 → ' . $count . ' 个店铺，每店约 '
            . intdiv(count($goods), $count) . ' ~ ' . (int)ceil(count($goods) / $count) . ' 件');
        if (!$force) {
            $output->writeln('<comment>演练模式（未修改任何数据），确认无误后加 --force 执行</comment>');
        }

        // ---------- 3. 按状态分组轮流分配 ----------
        $assign = [];
        $detail = [];
        for ($i = 0; $i < $count; $i++) {
            $assign[$i] = [];
            $detail[$i] = [];
        }
        foreach ($byStatus as $st => $list) {
            shuffle($list);
            foreach ($list as $n => $goodsId) {
                $idx = $n % $count;
                $assign[$idx][] = $goodsId;
                $detail[$idx][$st] = ($detail[$idx][$st] ?? 0) + 1;
            }
        }

        $now = time();
        if ($force) {
            Db::startTrans();
            try {
                foreach ($assign as $idx => $ids) {
                    $shopId = (int)$shops[$idx]['id'];
                    // 源卖家那一份本来就在它名下，不用再 update
                    if (empty($ids) || $shopId === $fromId) {
                        continue;
                    }
                    foreach (array_chunk($ids, 500) as $chunk) {
                        Db::name('goods')->whereIn('id', $chunk)->update(['seller_id' => $shopId, 'update_time' => $now]);
                        if ($withSold) {
                            Db::name('order')->whereIn('goods_id', $chunk)->where('seller_id', $fromId)->update(['seller_id' => $shopId]);
                        }
                    }
                }
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                $output->writeln('<error>分配失败：' . $e->getMessage() . '（已回滚，商品归属未变）</error>');
                return 1;
            }
        }

        // ---------- 4. 输出 ----------
        $accounts = array_map(function ($u) { return (string)($u['mobile'] ?: $u['email']); }, $shops);
        $output->writeln('');
        $accW = max(8, max(array_map([$this, 'width'], $accounts)));
        $output->writeln($this->pad('序号', 6) . $this->pad('会员ID', 9) . $this->pad('账号', $accW + 2) . $this->pad('昵称', 18) . '分到商品');
        foreach ($shops as $i => $u) {
            $parts = [];
            foreach ($detail[$i] as $st => $n) {
                $parts[] = ($statusName[$st] ?? ('状态' . $st)) . ' ' . $n;
            }
            $output->writeln($this->pad((string)($i + 1), 6)
                . $this->pad((string)$u['id'], 9)
                . $this->pad($accounts[$i], $accW + 2)
                . $this->pad((string)$u['nickname'], 18)
                . count($assign[$i]) . ' 件（' . implode('，', $parts) . '）'
                . ((int)$u['id'] === $fromId ? ' [源卖家，留在自己名下]' : ''));
        }
        $output->writeln('');

        if (!$force) {
            $output->writeln('<comment>演练结束：将分配 ' . count($goods) . ' 件商品给 ' . $count . ' 个店铺，实际未修改。加 --force 执行</comment>');
            return 0;
        }

        $summary = '把会员 ' . $fromId . ' 的 ' . count($goods) . ' 件商品平均分配给 ' . $count . ' 个自营店铺'
            . ($fromIn ? '（含源卖家自己）' : '');
        admin_log($summary, 0);
        @file_put_contents(
            $this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . 'self_shop.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $summary . '，店铺会员 ID：' . implode(',', array_column($shops, 'id')) . PHP_EOL,
            FILE_APPEND
        );
        $output->writeln('<info>完成：' . $summary . '</info>');
        return 0;
    }

    /**
     * 字符串的终端显示宽度（中文按 2 个字符算）
     */
    private function width($s)
    {
        return mb_strlen((string)$s) + preg_match_all('/[\x{4e00}-\x{9fa5}\x{3000}-\x{303f}\x{ff00}-\x{ffef}]/u', (string)$s);
    }

    /**
     * 按显示宽度右补空格，保证中文列也能对齐
     */
    private function pad($s, $width)
    {
        return (string)$s . str_repeat(' ', max(1, $width - $this->width($s)));
    }
}

<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 批量创建「自营店铺」卖家会员，并把指定卖家的商品平均分配给这些店铺
 *
 * 用法：
 *   php think shop:self-split                                   演练（默认 30 个店铺、源卖家会员 1），只打印不改数据
 *   php think shop:self-split --from=1 --force                  真正执行（--force 时必须显式写 --from，防止误用默认值）
 *   php think shop:self-split --count=30 --from=1 --force       指定店铺数量与源卖家
 *   php think shop:self-split --password=abc123456 --force      指定新会员登录密码（默认 123456）
 *   php think shop:self-split --pid=77 --force                  新店铺挂到某个上级（代理）下，默认 0 无上级
 *   php think shop:self-split --prefix=自营店 --force            昵称 / 店铺名用「前缀+序号」，默认用中文昵称词库
 *   php think shop:self-split --include-sold --force            连同已成交（有订单 / 有得标人）的商品一起转，并同步订单的卖家
 *   php think shop:self-split --from=1 --count=30 --include-from --force
 *                                                               源卖家自己也算一个店铺：共 30 个店铺（会员 1 + 新建 29 个），商品平分给这 30 个
 *
 * 新会员：is_seller=1、seller_check=1（免审核）、is_self_shop=1（自营店铺）、is_virtual=0、auth_status=2。
 * 账号按后台「注册方式」自动选手机号或邮箱；手机号默认 166 开头，可用 --mobile-prefix 改。
 *
 * 分配规则：按商品状态分组后打乱，再轮流发给各店铺，保证每个店铺拿到的「拍卖中 / 流拍 / 其它状态」
 * 数量最多相差 1 件。加 --include-from 时源卖家自己也占一个店铺名额（--count 是含它在内的总数，
 * 所以只新建 --count-1 个），它名下会留下属于自己的那一份，其余才转走。
默认跳过已有订单或已有得标人的商品（避免订单与商品的卖家对不上），
 * 需要一起转时加 --include-sold，届时 order 表的 seller_id 会同步更新。
 *
 * 注意：商品换了卖家后，「平台自营自动出价」「流拍自动上架」都会按新卖家的自营属性生效。
 */
class SelfShopSplit extends Command
{
    protected function configure()
    {
        $this->setName('shop:self-split')
            ->addOption('force', 'f', Option::VALUE_NONE, '确认执行（不加则只演练，不修改数据）')
            ->addOption('count', null, Option::VALUE_OPTIONAL, '创建的自营店铺数量，默认 30')
            ->addOption('from', null, Option::VALUE_OPTIONAL, '源卖家会员 ID，默认 1')
            ->addOption('password', null, Option::VALUE_OPTIONAL, '新会员登录密码，默认 123456')
            ->addOption('prefix', null, Option::VALUE_OPTIONAL, '昵称 / 店铺名前缀，留空则用中文昵称词库')
            ->addOption('pid', null, Option::VALUE_OPTIONAL, '新会员的上级会员 ID，默认 0')
            ->addOption('mobile-prefix', null, Option::VALUE_OPTIONAL, '手机号前缀，默认 166')
            ->addOption('account', null, Option::VALUE_OPTIONAL, '账号类型 mobile / email，默认跟随后台「注册方式」')
            ->addOption('include-sold', null, Option::VALUE_NONE, '连同已有订单 / 得标人的商品一起转移（同步更新订单卖家）')
            ->addOption('include-from', null, Option::VALUE_NONE, '源卖家自己也算一个店铺')
            ->setDescription('批量创建自营店铺卖家，并把指定卖家的商品平均分给这些店铺');
    }

    protected function execute(Input $input, Output $output)
    {
        $force       = (bool)$input->getOption('force');
        $withSold    = (bool)$input->getOption('include-sold');
        $withFrom    = (bool)$input->getOption('include-from');
        $countOpt    = $input->getOption('count');
        $fromOpt     = $input->getOption('from');
        // 注意不要用 ?:，否则 --count=0 会被当成没传而落回默认值
        $count       = ($countOpt === null || $countOpt === '') ? 30 : (int)$countOpt;
        $fromId      = ($fromOpt === null || $fromOpt === '') ? 1 : (int)$fromOpt;
        $pid         = max(0, (int)$input->getOption('pid'));
        $password    = trim((string)($input->getOption('password') ?: '123456'));
        $prefix      = mb_substr(trim((string)$input->getOption('prefix')), 0, 20);
        $mobilePre   = preg_replace('/\D/', '', (string)($input->getOption('mobile-prefix') ?: '166'));
        $accountType = strtolower(trim((string)$input->getOption('account')));

        // 真正执行时必须显式写明源卖家，避免误用默认值把会员 1 的商品分掉
        if ($force && ($fromOpt === null || $fromOpt === '')) {
            $output->writeln('<error>执行（--force）时必须显式指定源卖家，例如 --from=1</error>');
            return 1;
        }
        if ($count < 1 || $count > 200) {
            $output->writeln('<error>店铺数量需为 1 ~ 200</error>');
            return 1;
        }
        if (strlen($password) < 6) {
            $output->writeln('<error>密码至少 6 位</error>');
            return 1;
        }
        if ($mobilePre === '' || strlen($mobilePre) > 8) {
            $output->writeln('<error>手机号前缀只能是数字，且不超过 8 位</error>');
            return 1;
        }
        if ($accountType !== '' && !in_array($accountType, ['mobile', 'email'], true)) {
            $output->writeln('<error>--account 只能是 mobile 或 email</error>');
            return 1;
        }
        $from = Db::name('user')->where('id', $fromId)->find();
        if (!$from) {
            $output->writeln('<error>源卖家会员 ' . $fromId . ' 不存在</error>');
            return 1;
        }
        if ($pid > 0 && !Db::name('user')->where('id', $pid)->find()) {
            $output->writeln('<error>上级会员 ' . $pid . ' 不存在</error>');
            return 1;
        }

        // ---------- 1. 统计待分配的商品 ----------
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
            $output->writeln('<error>会员 ' . $fromId . ' 没有可分配的商品（共 ' . $total . ' 件，其中已成交 ' . $skipped . ' 件）</error>');
            return 1;
        }

        $statusName = [0 => '待审核', 1 => '拍卖中', 2 => '已成交', 3 => '流拍', 4 => '已下架', 5 => '审核未过'];
        $byStatus   = [];
        foreach ($goods as $g) {
            $byStatus[(int)$g['status']][] = (int)$g['id'];
        }
        ksort($byStatus);

        $output->writeln('源卖家：会员 ' . $fromId . '（' . $from['nickname'] . '），商品共 ' . $total . ' 件');
        foreach ($byStatus as $st => $list) {
            $output->writeln('  ' . ($statusName[$st] ?? ('状态' . $st)) . '：' . count($list) . ' 件');
        }
        if ($skipped > 0) {
            $output->writeln($withSold
                ? '  其中已成交（有订单 / 得标人）' . $skipped . ' 件也会一起转移，订单的卖家同步更新'
                : '  已跳过已成交（有订单 / 得标人）' . $skipped . ' 件，需要一起转请加 --include-sold');
        }
        $output->writeln('本次分配：' . count($goods) . ' 件 → ' . $count . ' 个自营店铺'
            . ($withFrom ? '（含源卖家会员 ' . $fromId . ' 自己，只新建 ' . ($count - 1) . ' 个）' : '')
            . '，每店约 ' . intdiv(count($goods), $count) . ' ~ ' . (int)ceil(count($goods) / $count) . ' 件');
        if (!$force) {
            $output->writeln('<comment>演练模式（未修改任何数据），确认无误后加 --force 执行</comment>');
        }

        // ---------- 2. 准备 / 创建店铺会员 ----------
        $byEmail  = $accountType !== '' ? $accountType === 'email' : register_mode() === 'email';
        $shops    = [];   // [['id'=>int,'account'=>string,'nickname'=>string], ...]
        $now      = time();
        $output->writeln('新会员账号类型：' . ($byEmail ? '邮箱' : '手机号（' . $mobilePre . ' 开头）')
            . ($accountType !== '' ? '（--account 指定）' : '（跟随后台「注册方式」，可用 --account 覆盖）'));

        // 源卖家自己占一个店铺名额时，它排在第一个，只新建 count-1 个
        if ($withFrom) {
            $shops[] = ['id' => $fromId, 'account' => (string)($from['mobile'] ?: $from['email']), 'nickname' => (string)$from['nickname'], 'from' => true];
            if ((int)$from['is_self_shop'] !== 1) {
                $output->writeln('<comment>源卖家会员 ' . $fromId . ' 目前不是「自营店铺」，' . ($force ? '本次会一并设为自营店铺' : '执行时会一并设为自营店铺') . '</comment>');
            }
        }
        $need = $count - count($shops);
        // 昵称去重：库里所有会员的昵称 + 本批已用的
        $usedNick = array_flip(Db::name('user')->column('nickname'));
        // 账号去重：本批内先占位，避免同批重复
        $usedAcc  = [];
        $plan     = [];
        for ($i = 0; $i < $need; $i++) {
            $nickname = $this->makeNickname($prefix, $usedNick, count($shops) + $i + 1);
            $account  = $byEmail ? $this->makeEmail($usedAcc) : $this->makeMobile($mobilePre, $usedAcc);
            $plan[]   = ['account' => $account, 'nickname' => $nickname];
        }

        if ($force && $need > 0) {
            $hash = hash_password($password);
            Db::startTrans();
            try {
                foreach ($plan as $p) {
                    $userId = Db::name('user')->insertGetId([
                        'mobile'       => $byEmail ? null : $p['account'],
                        'email'        => $byEmail ? $p['account'] : null,
                        'password'     => $hash,
                        'nickname'     => $p['nickname'],
                        'shop_name'    => $p['nickname'],
                        'invite_code'  => generate_invite_code(),
                        'pid'          => $pid,
                        'is_seller'    => 1,
                        'seller_check' => 1,
                        'is_self_shop' => 1,
                        'is_virtual'   => 0,
                        'auth_status'  => 2,
                        'auth_time'    => $now,
                        'status'       => 1,
                        'reg_time'     => $now,
                        'create_time'  => $now,
                        'update_time'  => $now,
                    ]);
                    $shops[] = ['id' => (int)$userId, 'account' => $p['account'], 'nickname' => $p['nickname'], 'from' => false];
                }
                if ($withFrom && (int)$from['is_self_shop'] !== 1) {
                    Db::name('user')->where('id', $fromId)->update(['is_self_shop' => 1, 'update_time' => $now]);
                }
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                $output->writeln('<error>创建店铺会员失败：' . $e->getMessage() . '（已回滚，未分配商品）</error>');
                return 1;
            }
        } else {
            foreach ($plan as $p) {
                $shops[] = ['id' => 0, 'account' => $p['account'], 'nickname' => $p['nickname'], 'from' => false];
            }
        }

        // ---------- 3. 按状态分组轮流分配 ----------
        $assign = [];   // 店铺下标 => 商品 ID[]
        $detail = [];   // 店铺下标 => [状态 => 件数]
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

        if ($force) {
            Db::startTrans();
            try {
                foreach ($assign as $idx => $ids) {
                    // 源卖家那一份本来就在它名下，不用再 update
                    if (empty($ids) || empty($shops[$idx]['id']) || !empty($shops[$idx]['from'])) {
                        continue;
                    }
                    foreach (array_chunk($ids, 500) as $chunk) {
                        Db::name('goods')->whereIn('id', $chunk)->update(['seller_id' => $shops[$idx]['id'], 'update_time' => $now]);
                        if ($withSold) {
                            Db::name('order')->whereIn('goods_id', $chunk)->where('seller_id', $fromId)->update(['seller_id' => $shops[$idx]['id']]);
                        }
                    }
                }
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                $output->writeln('<error>分配商品失败：' . $e->getMessage() . '（商品分配已回滚，已创建的店铺会员保留）</error>');
                return 1;
            }
        }

        // ---------- 4. 输出 ----------
        $output->writeln('');
        $accW = max(8, max(array_map([$this, 'width'], array_column($shops, 'account'))));
        $output->writeln($this->pad('店铺', 6) . $this->pad('会员ID', 9) . $this->pad('账号', $accW + 2) . $this->pad('昵称 / 店铺名', 18) . '分到商品');
        foreach ($shops as $i => $s) {
            $parts = [];
            foreach ($detail[$i] as $st => $n) {
                $parts[] = ($statusName[$st] ?? ('状态' . $st)) . ' ' . $n;
            }
            $output->writeln(
                $this->pad((string)($i + 1), 6)
                . $this->pad($s['id'] > 0 ? (string)$s['id'] : '-', 9)
                . $this->pad($s['account'], $accW + 2)
                . $this->pad($s['nickname'], 18)
                . count($assign[$i]) . ' 件（' . implode('，', $parts) . '）' . (empty($s['from']) ? '' : ' [源卖家，留在自己名下]')
            );
        }
        $output->writeln('');

        if (!$force) {
            $output->writeln('<comment>演练结束：将创建 ' . $need . ' 个自营店铺会员，分配 ' . count($goods) . ' 件商品，实际未修改。加 --force 执行</comment>');
            return 0;
        }

        $summary = '批量创建自营店铺卖家 ' . $need . ' 个，把会员 ' . $fromId . ' 的 ' . count($goods) . ' 件商品平均分配给 ' . $count . ' 个自营店铺'
            . ($withFrom ? '（含源卖家自己）' : '');
        admin_log($summary, 0);
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $summary . '，店铺会员 ID：' . implode(',', array_column($shops, 'id'));
        @file_put_contents($this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . 'self_shop_split.log', $line . PHP_EOL, FILE_APPEND);
        $output->writeln('<info>完成：' . $summary . '，登录密码 ' . $password . '</info>');
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

    /**
     * 昵称：有前缀就「前缀+序号」，否则用中文昵称词库并与库里已有昵称去重
     */
    private function makeNickname($prefix, array &$used, $seq)
    {
        if ($prefix !== '') {
            $name = $prefix . str_pad((string)$seq, 2, '0', STR_PAD_LEFT);
            while (isset($used[$name])) {
                $name = $prefix . str_pad((string)$seq, 2, '0', STR_PAD_LEFT) . mt_rand(10, 99);
            }
            $used[$name] = 1;
            return $name;
        }
        for ($i = 0; $i < 50; $i++) {
            $name = random_cn_nickname();
            if (!isset($used[$name])) {
                $used[$name] = 1;
                return $name;
            }
        }
        $name = random_cn_nickname() . mt_rand(10, 99);
        $used[$name] = 1;
        return $name;
    }

    /**
     * 手机号：前缀 + 随机数字补足 11 位，与库里和本批都不重复
     */
    private function makeMobile($prefix, array &$used)
    {
        $len = max(1, 11 - strlen($prefix));
        for ($i = 0; $i < 100; $i++) {
            $mobile = $prefix . str_pad((string)mt_rand(0, (int)str_repeat('9', min(9, $len))), $len, '0', STR_PAD_LEFT);
            $mobile = substr($mobile, 0, 11);
            if (!isset($used[$mobile]) && !Db::name('user')->where('mobile', $mobile)->count()) {
                $used[$mobile] = 1;
                return $mobile;
            }
        }
        throw new \RuntimeException('生成店铺账号失败，请换一个 --mobile-prefix 重试');
    }

    /**
     * 邮箱账号（后台注册方式为邮箱时使用）
     */
    private function makeEmail(array &$used)
    {
        for ($i = 0; $i < 100; $i++) {
            $email = 'shop' . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT) . '@self.local';
            if (!isset($used[$email]) && !Db::name('user')->where('email', $email)->count()) {
                $used[$email] = 1;
                return $email;
            }
        }
        throw new \RuntimeException('生成店铺账号失败，请重试');
    }
}

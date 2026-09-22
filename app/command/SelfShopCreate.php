<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 批量创建「自营店铺」卖家会员（只建会员，不动商品）
 *
 * 用法：
 *   php think shop:self-create                                  演练（默认 30 个），只打印将要创建什么，不改数据
 *   php think shop:self-create --force                          真正创建 30 个
 *   php think shop:self-create --count=10 --force               创建 10 个
 *   php think shop:self-create --password=abc123456 --force     指定登录密码（默认 123456）
 *   php think shop:self-create --account=mobile --force         账号用手机号（默认跟随后台「注册方式」）
 *   php think shop:self-create --mobile-prefix=166 --force      手机号前缀（默认 166）
 *   php think shop:self-create --prefix=自营店 --force           昵称 / 店铺名用「前缀+序号」，默认用中文昵称词库
 *   php think shop:self-create --pid=77 --force                 挂到某个上级（代理）下，默认 0 无上级
 *
 * 新会员：is_seller=1、seller_check=1（免审核）、is_self_shop=1（自营店铺）、is_virtual=0、auth_status=2、
 * shop_name = 昵称、余额 0。建好后用 php think shop:self-assign 把某个卖家的商品分给这些店铺。
 *
 * 注意：会员一旦是「自营店铺」，「平台自营自动出价」「流拍自动上架」这两个脚本就会管它名下的商品。
 */
class SelfShopCreate extends Command
{
    protected function configure()
    {
        $this->setName('shop:self-create')
            ->addOption('force', 'f', Option::VALUE_NONE, '确认执行（不加则只演练，不修改数据）')
            ->addOption('count', null, Option::VALUE_OPTIONAL, '创建数量，默认 30')
            ->addOption('password', null, Option::VALUE_OPTIONAL, '登录密码，默认 123456')
            ->addOption('prefix', null, Option::VALUE_OPTIONAL, '昵称 / 店铺名前缀，留空则用中文昵称词库')
            ->addOption('pid', null, Option::VALUE_OPTIONAL, '上级会员 ID，默认 0')
            ->addOption('mobile-prefix', null, Option::VALUE_OPTIONAL, '手机号前缀，默认 166')
            ->addOption('account', null, Option::VALUE_OPTIONAL, '账号类型 mobile / email，默认跟随后台「注册方式」')
            ->setDescription('批量创建「自营店铺」卖家会员（只建会员，不动商品）');
    }

    protected function execute(Input $input, Output $output)
    {
        $force       = (bool)$input->getOption('force');
        $countOpt    = $input->getOption('count');
        // 注意不要用 ?:，否则 --count=0 会被当成没传而落回默认值
        $count       = ($countOpt === null || $countOpt === '') ? 30 : (int)$countOpt;
        $pid         = max(0, (int)$input->getOption('pid'));
        $password    = trim((string)($input->getOption('password') ?: '123456'));
        $prefix      = mb_substr(trim((string)$input->getOption('prefix')), 0, 20);
        $mobilePre   = preg_replace('/\D/', '', (string)($input->getOption('mobile-prefix') ?: '166'));
        $accountType = strtolower(trim((string)$input->getOption('account')));

        if ($count < 1 || $count > 200) {
            $output->writeln('<error>创建数量需为 1 ~ 200</error>');
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
        if ($pid > 0 && !Db::name('user')->where('id', $pid)->find()) {
            $output->writeln('<error>上级会员 ' . $pid . ' 不存在</error>');
            return 1;
        }

        $byEmail = $accountType !== '' ? $accountType === 'email' : register_mode() === 'email';
        $already = Db::name('user')->where('is_self_shop', 1)->count();
        $output->writeln('本次创建：' . $count . ' 个自营店铺卖家（库里现有自营店铺会员 ' . $already . ' 个）');
        $output->writeln('账号类型：' . ($byEmail ? '邮箱' : '手机号（' . $mobilePre . ' 开头）')
            . ($accountType !== '' ? '（--account 指定）' : '（跟随后台「注册方式」，可用 --account 覆盖）')
            . '，密码 ' . $password . '，上级 ' . ($pid > 0 ? $pid : '无'));
        if (!$force) {
            $output->writeln('<comment>演练模式（未修改任何数据），确认无误后加 --force 执行</comment>');
        }

        // 昵称去重：库里所有会员的昵称 + 本批已用的；账号在本批内也先占位
        $usedNick = array_flip(Db::name('user')->column('nickname'));
        $usedAcc  = [];
        $plan     = [];
        for ($i = 0; $i < $count; $i++) {
            $plan[] = [
                'nickname' => $this->makeNickname($prefix, $usedNick, $i + 1),
                'account'  => $byEmail ? $this->makeEmail($usedAcc) : $this->makeMobile($mobilePre, $usedAcc),
            ];
        }

        $created = [];
        $now     = time();
        if ($force) {
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
                    $created[] = ['id' => (int)$userId] + $p;
                }
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                $output->writeln('<error>创建失败：' . $e->getMessage() . '（已回滚，未创建任何会员）</error>');
                return 1;
            }
        } else {
            foreach ($plan as $p) {
                $created[] = ['id' => 0] + $p;
            }
        }

        $output->writeln('');
        $accW = max(8, max(array_map([$this, 'width'], array_column($created, 'account'))));
        $output->writeln($this->pad('序号', 6) . $this->pad('会员ID', 9) . $this->pad('账号', $accW + 2) . '昵称 / 店铺名');
        foreach ($created as $i => $c) {
            $output->writeln($this->pad((string)($i + 1), 6)
                . $this->pad($c['id'] > 0 ? (string)$c['id'] : '-', 9)
                . $this->pad($c['account'], $accW + 2)
                . $c['nickname']);
        }
        $output->writeln('');

        if (!$force) {
            $output->writeln('<comment>演练结束：将创建 ' . $count . ' 个自营店铺卖家，实际未创建。加 --force 执行</comment>');
            return 0;
        }

        $ids     = array_column($created, 'id');
        $summary = '批量创建自营店铺卖家 ' . count($ids) . ' 个（会员 ID ' . $ids[0] . '~' . end($ids) . '）';
        admin_log($summary, 0);
        @file_put_contents(
            $this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . 'self_shop.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $summary . '，ID：' . implode(',', $ids) . PHP_EOL,
            FILE_APPEND
        );
        $output->writeln('<info>完成：' . $summary . '，登录密码 ' . $password . '</info>');
        $output->writeln('接下来可以执行：php think shop:self-assign --from=1 --force  把会员 1 的商品平均分给所有自营店铺');
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

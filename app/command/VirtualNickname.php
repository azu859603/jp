<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 把「用户」开头的虚拟会员昵称改成中文昵称
 *
 * 用法：
 *   php think user:rename-virtual              演练，只列出将要怎么改，不动数据
 *   php think user:rename-virtual --force      真正执行
 *   php think user:rename-virtual --force --limit=200   本次最多改 200 个
 *   php think user:rename-virtual --force --all         虚拟会员的全部昵称都重新生成（不限「用户」开头）
 *
 * 只处理 is_virtual=1 的会员。真实注册的会员默认昵称也是「用户+手机号后4位」，
 * 本脚本不会碰他们。新昵称由 app/common.php 的 generate_virtual_nickname() 生成，
 * 与批量添加虚拟会员用的是同一套中文词库，并且与库里已有的虚拟会员昵称去重。
 */
class VirtualNickname extends Command
{
    protected function configure()
    {
        $this->setName('user:rename-virtual')
            ->addOption('force', 'f', Option::VALUE_NONE, '确认执行（不加则只演练，不修改数据）')
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '本次最多处理多少个（默认不限）')
            ->addOption('all', null, Option::VALUE_NONE, '虚拟会员的昵称全部重新生成，不限于「用户」开头')
            ->setDescription('把「用户」开头的虚拟会员昵称改成中文昵称（只处理虚拟会员，真实会员不动）');
    }

    protected function execute(Input $input, Output $output)
    {
        $force = $input->hasOption('force') && $input->getOption('force');
        $all   = $input->hasOption('all') && $input->getOption('all');
        $limit = (int)$input->getOption('limit');

        // 待改名的虚拟会员
        $query = Db::name('user')->where('is_virtual', 1);
        if (!$all) {
            $query->whereLike('nickname', '用户%');
        }
        $total = (clone $query)->count();
        if ($total === 0) {
            $output->writeln($all ? '没有虚拟会员，无需处理' : '没有「用户」开头的虚拟会员昵称，无需处理');
            return 0;
        }
        $todo = $limit > 0 ? min($limit, $total) : $total;

        $output->writeln('符合条件的虚拟会员：' . $total . ' 个' . ($todo < $total ? '，本次处理 ' . $todo . ' 个' : ''));
        if (!$force) {
            $output->writeln('<comment>演练模式（未修改任何数据），确认无误后加 --force 执行</comment>');
        }

        // 已占用的昵称：库里全部虚拟会员的昵称，保证新名字不与现有的重复
        $used = array_flip(Db::name('user')->where('is_virtual', 1)->column('nickname'));
        $done = 0;
        $samples = [];
        $now = time();

        // 分批处理，避免一次性把几万行读进内存
        $lastId = 0;
        while ($done < $todo) {
            $chunk = (clone $query)->where('id', '>', $lastId)
                ->field('id,nickname')->order('id', 'asc')->limit(min(500, $todo - $done))->select()->toArray();
            if (empty($chunk)) {
                break;
            }
            Db::startTrans();
            try {
                foreach ($chunk as $u) {
                    $lastId = (int)$u['id'];
                    // 旧名字让出占用，避免 --all 重跑时把自己算成冲突
                    unset($used[$u['nickname']]);
                    $new = generate_virtual_nickname('', $used);
                    if ($force) {
                        Db::name('user')->where('id', $u['id'])->update(['nickname' => $new, 'update_time' => $now]);
                    }
                    $done++;
                    if (count($samples) < 10) {
                        $samples[] = $u['nickname'] . ' → ' . $new;
                    }
                }
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                $output->writeln('<error>处理失败：' . $e->getMessage() . '（已回滚本批，前面批次的修改保留）</error>');
                return 1;
            }
        }

        $output->writeln('示例：');
        foreach ($samples as $s) {
            $output->writeln('  ' . $s);
        }
        if ($force) {
            $line = '[' . date('Y-m-d H:i:s') . '] 虚拟会员昵称改名完成：' . $done . ' 个';
            @file_put_contents($this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . 'rename_virtual.log', $line . PHP_EOL, FILE_APPEND);
            $output->writeln('<info>完成：已改名 ' . $done . ' 个虚拟会员昵称</info>');
        } else {
            $output->writeln('<comment>演练结束：将改名 ' . $done . ' 个，实际未修改。加 --force 执行</comment>');
        }
        return 0;
    }
}

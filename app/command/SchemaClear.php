<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 清理表结构缓存
 * 关闭调试模式后框架会把每张表的字段列表缓存到 runtime/<应用>/schema/，
 * 数据库加了字段之后必须清掉，否则 update/insert 会报「数据表字段不存在」。
 * 用法：php think schema:clear
 */
class SchemaClear extends Command
{
    protected function configure()
    {
        $this->setName('schema:clear')->setDescription('清理各应用的表结构缓存（runtime/*/schema）');
    }

    protected function execute(Input $input, Output $output)
    {
        $root = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        $dirs = array_merge(glob($root . 'schema', GLOB_ONLYDIR) ?: [], glob($root . '*' . DIRECTORY_SEPARATOR . 'schema', GLOB_ONLYDIR) ?: []);
        if (empty($dirs)) {
            $output->writeln('没有找到表结构缓存目录，无需清理');
            return 0;
        }
        $total = 0;
        foreach ($dirs as $dir) {
            $n = 0;
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                if (@unlink($file)) {
                    $n++;
                }
            }
            @rmdir($dir);
            $total += $n;
            $output->writeln('已清理 ' . str_replace($this->app->getRootPath(), '', $dir) . '（' . $n . ' 个文件）');
        }
        $output->writeln('<info>完成，共删除 ' . $total . ' 个缓存文件</info>');
        return 0;
    }
}

<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 刷新表结构缓存
 * 关闭调试模式后（fields_cache 开启），think-orm 会把每张表的字段列表存进缓存驱动
 * （本项目是 Redis，键形如 jp:127.0.0.1_3306|jp.admin_user）。数据库加了字段之后必须刷新，
 * 否则 update/insert 会报「[10500] 数据表字段不存在」。
 * 用法：php think schema:clear
 */
class SchemaClear extends Command
{
    protected function configure()
    {
        $this->setName('schema:clear')->setDescription('刷新所有数据表的表结构缓存（数据库加字段后执行）');
    }

    protected function execute(Input $input, Output $output)
    {
        $conn   = Db::connect();
        $tables = $conn->getTables();
        $n = 0;
        foreach ($tables as $table) {
            // force=true：跳过缓存重新读取 SHOW FULL COLUMNS，并把最新结构写回缓存
            $info = $conn->getSchemaInfo($table, true);
            $output->writeln(sprintf('  %-24s %d 个字段', $table, count($info['fields'] ?? [])));
            $n++;
        }
        // 旧版本框架的文件缓存目录（如存在一并清掉）
        $root = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        $dirs = array_merge(glob($root . 'schema', GLOB_ONLYDIR) ?: [], glob($root . '*' . DIRECTORY_SEPARATOR . 'schema', GLOB_ONLYDIR) ?: []);
        foreach ($dirs as $dir) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
            $output->writeln('已删除旧缓存目录 ' . str_replace($this->app->getRootPath(), '', $dir));
        }
        $output->writeln('<info>完成：已刷新 ' . $n . ' 张表的结构缓存</info>');
        return 0;
    }
}

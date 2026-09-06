<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 商品采集命令
 *
 * 用法：
 *   php think goods:collect                    POST getProductDetail，id 从 11 到 3362 逐个采集，无内容跳过，图片下载到本地
 *   php think goods:collect --start=11 --end=100
 *   php think goods:collect --end=0            结束 id 自动取远端当前最大 id
 *   php think goods:collect --limit=20         本次最多采集 20 件（调试用）
 *   php think goods:collect --update           已采集过的也重新覆盖（默认跳过）
 *   php think goods:collect --seller=1         挂到哪个卖家名下（默认 1）
 *   php think goods:collect --source=list      改用列表接口分页采集（列表接口不认 id，按 page/limit 翻页）
 *   php think goods:collect --relist=7         远端已结束的商品重新上架，结束时间随机落在 1~7 天后（默认按流拍落库）
 *
 * 接口：
 *   POST {base}/getProductDetail     参数 id      单件商品详情（默认来源）
 *   POST {base}/getHomeProductList   参数 page/limit  商品列表分页（--source=list）
 *
 * 图片：封面、图集、详情内嵌图片全部下载到 public/uploads/collect/{远端id}/，
 *       落库时写本地路径 /uploads/collect/...；下载失败则保留远端地址并记入日志。
 *
 * 去重：建 collect_goods 映射表（远端id -> 本地goods.id），重复执行默认跳过已采集项，
 *       加 --update 才覆盖更新；重复运行不会产生重复商品。
 *
 * 日志：runtime/log/goods_collect.log
 */
class GoodsCollect extends Command
{
    private $apiBase = 'https://dfghlp.tyiplkjh.cc/api/index/product.product/';

    /** 本地保存目录（相对 public） */
    private $saveDir = 'uploads/collect';

    private $logFile = '';
    private $prefix = '';
    private $sellerId = 1;
    private $sleepMs = 200;
    private $relistDays = 0;
    private $stats = ['ok' => 0, 'skip' => 0, 'miss' => 0, 'fail' => 0, 'img_ok' => 0, 'img_fail' => 0];

    /** 标题关键词 -> 本地分类ID 映射（匹配不到归入「杂项」） */
    private $categoryRules = [
        1 => ['画', '书法', '字', '书', '卷', '轴', '扇面', '对联', '碑', '帖', '拓'],
        3 => ['玉', '翡翠', '玛瑙', '琥珀', '蜜蜡', '水晶', '和田', '岫', '珀'],
        4 => ['瓷', '瓶', '碗', '盘', '罐', '壶', '杯', '釉', '窑', '青花', '盏', '尊', '炉', '盆'],
        5 => ['币', '钱', '邮票', '银元', '铜元', '纸币', '票', '元宝', '袁大头', '通宝', '金条', '银锭'],
        6 => ['珠宝', '钻', '宝石', '珍珠', '项链', '戒指', '手镯', '手串', '吊坠', '金饰', '首饰'],
        7 => ['现代', '当代', '手表', '腕表', '相机', '酒', '茅台', '茶', '沉香', '紫砂'],
    ];
    private $defaultCategory = 2;

    protected function configure()
    {
        $this->setName('goods:collect')
            ->addOption('start', null, Option::VALUE_REQUIRED, '起始远端商品ID', '11')
            ->addOption('end', null, Option::VALUE_REQUIRED, '结束远端商品ID（默认 3362；填 0 则自动取远端最大ID）', '3362')
            ->addOption('limit', null, Option::VALUE_REQUIRED, '本次最多采集件数（0 不限）', '0')
            ->addOption('seller', null, Option::VALUE_REQUIRED, '商品挂在哪个卖家ID名下', '1')
            ->addOption('sleep', null, Option::VALUE_REQUIRED, '每次请求间隔毫秒', '200')
            ->addOption('source', null, Option::VALUE_REQUIRED, 'detail=按ID逐个取详情(默认) list=列表接口翻页', 'detail')
            ->addOption('update', 'u', Option::VALUE_NONE, '已采集过的商品也重新覆盖更新')
            ->addOption('relist', null, Option::VALUE_REQUIRED, '远端已结束的商品重新上架，结束时间随机落在 1~N 天后（0 不重上架，按流拍落库）', '0')
            ->setDescription('采集远端商品到本站，图片下载到本地 public/uploads/collect/');
    }

    protected function execute(Input $input, Output $output)
    {
        $start    = max(1, (int)$input->getOption('start'));
        $end      = (int)$input->getOption('end');
        $limit    = max(0, (int)$input->getOption('limit'));
        $update   = (bool)$input->getOption('update');
        $source   = $input->getOption('source') === 'list' ? 'list' : 'detail';
        $this->sellerId = max(1, (int)$input->getOption('seller'));
        $this->sleepMs  = max(0, (int)$input->getOption('sleep'));
        $this->relistDays = max(0, (int)$input->getOption('relist'));
        $this->logFile  = $this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . 'goods_collect.log';
        $this->prefix   = (string)(Db::getConfig('connections.mysql.prefix') ?: '');

        if (!Db::name('user')->where('id', $this->sellerId)->find()) {
            $output->writeln("<error>卖家 ID {$this->sellerId} 不存在，请用 --seller 指定有效卖家</error>");
            return 1;
        }
        $this->ensureMapTable();

        $output->writeln('');
        $output->writeln("接口：<info>{$this->apiBase}</info>  来源：{$source}  卖家：{$this->sellerId}  覆盖更新：" . ($update ? '是' : '否') . '  已结束商品重上架：' . ($this->relistDays > 0 ? "1~{$this->relistDays} 天" : '否'));
        $this->log('===== 开始采集 source=' . $source . ' start=' . $start . ' end=' . $end . ' limit=' . $limit . ' update=' . ($update ? 1 : 0));

        $t0 = microtime(true);
        if ($source === 'list') {
            $this->runByList($output, $limit, $update);
        } else {
            $this->runById($output, $start, $end, $limit, $update);
        }

        $sec = round(microtime(true) - $t0, 1);
        $s = $this->stats;
        $summary = "完成：新增/更新 {$s['ok']}，跳过 {$s['skip']}，远端不存在 {$s['miss']}，失败 {$s['fail']}；图片下载成功 {$s['img_ok']}，失败 {$s['img_fail']}；耗时 {$sec}s";
        $this->log('===== ' . $summary);
        $output->writeln('');
        $output->writeln('<info>' . $summary . '</info>');
        $output->writeln('日志：' . $this->logFile);
        return 0;
    }

    /* ------------------------------------------------------------------ 采集主流程 */

    /** 按 ID 逐个调用详情接口 */
    private function runById(Output $output, int $start, int $end, int $limit, bool $update)
    {
        if ($end <= 0) {
            $end = $this->fetchRemoteMaxId();
            if ($end <= 0) {
                $output->writeln('<error>无法从列表接口取得远端最大 ID，请用 --end 指定</error>');
                return;
            }
            $output->writeln("远端最大商品 ID：{$end}");
        }
        $output->writeln("采集范围：{$start} ~ {$end}");
        $output->writeln('');

        $done = 0;
        for ($id = $start; $id <= $end; $id++) {
            if ($limit > 0 && $done >= $limit) {
                $output->writeln("已达本次上限 {$limit} 件，停止");
                break;
            }
            if (!$update && $this->isCollected($id)) {
                $this->stats['skip']++;
                $output->writeln("[{$id}] 已采集过，跳过");
                continue;
            }
            $res = $this->post('getProductDetail', ['id' => $id]);
            $this->throttle();
            if ($res === null) {
                $this->stats['fail']++;
                $this->log("[{$id}] 请求失败");
                $output->writeln("[{$id}] <error>请求失败</error>");
                continue;
            }
            if ((int)($res['code'] ?? 0) !== 200 || empty($res['data']['id'])) {
                $this->stats['miss']++;
                $output->writeln("[{$id}] 远端不存在（" . ($res['msg'] ?? '-') . '）');
                continue;
            }
            $this->importOne($output, $res['data']);
            $done++;
        }
    }

    /** 列表接口翻页采集（不依赖 ID 连续） */
    private function runByList(Output $output, int $limit, bool $update)
    {
        $page = 1; $pageSize = 50; $done = 0;
        while (true) {
            $res = $this->post('getHomeProductList', ['page' => $page, 'limit' => $pageSize]);
            $this->throttle();
            if ($res === null || (int)($res['code'] ?? 0) !== 200) {
                $this->stats['fail']++;
                $this->log("列表第 {$page} 页请求失败");
                $output->writeln("<error>列表第 {$page} 页请求失败，停止</error>");
                break;
            }
            $list  = $res['data']['list'] ?? [];
            $pages = (int)($res['data']['pagination']['pages'] ?? $page);
            $output->writeln("列表第 {$page}/{$pages} 页，{$this->cnt($list)} 件");
            if (empty($list)) {
                break;
            }
            foreach ($list as $item) {
                if ($limit > 0 && $done >= $limit) {
                    $output->writeln("已达本次上限 {$limit} 件，停止");
                    return;
                }
                $rid = (int)($item['id'] ?? 0);
                if ($rid <= 0) continue;
                if (!$update && $this->isCollected($rid)) {
                    $this->stats['skip']++;
                    continue;
                }
                // 列表项与详情字段基本一致（封面在 carousel_list[0]，详情接口另有 logo）
                if (empty($item['logo'])) {
                    $item['logo'] = $item['carousel_list'][0] ?? '';
                }
                $this->importOne($output, $item);
                $done++;
            }
            if ($page >= $pages) break;
            $page++;
        }
    }

    private function cnt($a): int
    {
        return is_array($a) ? count($a) : 0;
    }

    /** 把一条远端商品写入本地 goods */
    private function importOne(Output $output, array $d)
    {
        $rid   = (int)$d['id'];
        $title = trim(mb_substr(strip_tags((string)($d['name'] ?? '')), 0, 100));
        if ($title === '') {
            $title = '藏品 #' . $rid;
        }

        // 图片：封面 + 图集 + 详情内嵌
        $carousel = $d['carousel_list'] ?? [];
        if (is_string($carousel)) {
            $carousel = $carousel === '' ? [] : explode(',', $carousel);
        }
        if (empty($carousel) && !empty($d['carousel'])) {
            $carousel = explode(',', (string)$d['carousel']);
        }
        $carousel = array_values(array_unique(array_filter(array_map('trim', (array)$carousel))));

        $coverRemote = trim((string)($d['logo'] ?? '')) ?: ($carousel[0] ?? '');
        $cover  = $coverRemote !== '' ? $this->downloadImage($coverRemote, $rid) : '';
        $images = [];
        foreach ($carousel as $u) {
            $images[] = $this->downloadImage($u, $rid);
        }
        if ($cover !== '' && !in_array($cover, $images, true)) {
            array_unshift($images, $cover);
        }
        if ($cover === '' && !empty($images)) {
            $cover = $images[0];
        }

        $content = $this->localizeInlineImages((string)($d['bewrite'] ?? ''), $rid);
        if (function_exists('clean_html')) {
            $content = clean_html($content);
        }

        // 价格：本地 decimal(10,2) 上限 99999999.99，超出则截到上限并记日志
        $startPrice = round((float)($d['starting_price'] ?? 0), 2);
        if ($startPrice > 99999999.99) {
            $this->log("[{$rid}] 起拍价 {$startPrice} 超过字段上限，已截为 99999999.99");
            $startPrice = 99999999.99;
        }
        if ($startPrice <= 0) {
            $startPrice = 1.00;
        }
        $raisePrice = max(1.0, round($startPrice * 0.01, 2));              // 加价幅度：起拍价 1%
        $bailRate   = (float)($d['shop_bail'] ?? ($d['shop_info']['bail'] ?? 0));
        $deposit    = $bailRate > 0 ? round($startPrice * $bailRate / 100, 2) : 0.0; // 保证金：远端 bail%
        if ($deposit > 99999999.99) $deposit = 99999999.99;

        // 时间：远端可能给 0000-00-00，parseTime 对无效值返回 0
        $endTime   = $this->parseTime($d['end_time'] ?? '') ?: $this->parseTime($d['auction_end_time'] ?? '');
        $startTime = $this->parseTime($d['create_time'] ?? '');
        $ended     = !empty($d['is_auction_end']) || $endTime <= time();
        if ($this->relistDays > 0 && $ended) {
            // 已结束的商品按 --relist 重新上架：结束时间随机落在 1 ~ N 天后
            $startTime = time();
            $endTime   = time() + mt_rand(1, $this->relistDays) * 86400 + mt_rand(0, 86399);
            $ended     = false;
        }
        if ($endTime <= 0) {
            $endTime = time() - 86400; // 远端无结束时间且未重上架：按已结束处理
            $ended   = true;
        }
        if ($startTime <= 0 || $startTime > $endTime) {
            $startTime = $endTime - 86400;
        }
        // 远端 status 1=已上架；未结束的落为拍卖中(1)，已结束的落为流拍(3)
        $status = $ended ? 3 : 1;

        $now = time();
        $row = [
            'seller_id'    => $this->sellerId,
            'category_id'  => $this->guessCategory($title),
            'title'        => $title,
            'cover'        => $cover,
            'images'       => json_encode(array_values($images), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'content'      => $content,
            'start_price'  => $startPrice,
            'raise_price'  => $raisePrice,
            'deposit'      => $deposit,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'status'       => $status,
            'view_count'   => (int)($d['collection_quantity'] ?? 0),
            'update_time'  => $now,
        ];

        try {
            $map = Db::name('collect_goods')->where('remote_id', $rid)->find();
            $localId = $map ? (int)$map['goods_id'] : 0;
            if ($localId > 0 && Db::name('goods')->where('id', $localId)->find()) {
                Db::name('goods')->where('id', $localId)->update($row);
                $action = '更新';
            } else {
                $row['create_time'] = $now;
                $localId = (int)Db::name('goods')->insertGetId($row);
                $action = '新增';
            }
            $shop = (string)($d['shop_info']['name'] ?? ($d['shop_name'] ?? ''));
            $mapRow = ['goods_id' => $localId, 'title' => $title, 'shop_name' => mb_substr($shop, 0, 50), 'update_time' => $now];
            if ($map) {
                Db::name('collect_goods')->where('remote_id', $rid)->update($mapRow);
            } else {
                Db::name('collect_goods')->insert($mapRow + ['remote_id' => $rid, 'create_time' => $now]);
            }
            $this->stats['ok']++;
            $msg = "[{$rid}] {$action} -> 本地 #{$localId}  {$title}  图 " . count($images) . " 张  起拍 {$startPrice}";
            $output->writeln($msg);
            $this->log($msg);
        } catch (\Throwable $e) {
            $this->stats['fail']++;
            $this->log("[{$rid}] 落库失败：" . $e->getMessage());
            $output->writeln("[{$rid}] <error>落库失败：" . $e->getMessage() . '</error>');
        }
    }

    /* ------------------------------------------------------------------ 辅助 */

    /** 用列表接口第一页取远端最大商品 ID（列表按 ID 倒序） */
    private function fetchRemoteMaxId(): int
    {
        $res = $this->post('getHomeProductList', ['page' => 1, 'limit' => 1]);
        $this->throttle();
        return (int)($res['data']['list'][0]['id'] ?? 0);
    }

    /** 解析远端时间字符串为时间戳；空值 / 0000-00-00 / 2000 年之前一律返回 0 */
    private function parseTime($v): int
    {
        $v = trim((string)$v);
        if ($v === '' || strpos($v, '0000-') === 0) {
            return 0;
        }
        $t = strtotime($v);
        return ($t !== false && $t > 946684800) ? $t : 0;
    }

    private function isCollected(int $rid): bool
    {
        $map = Db::name('collect_goods')->where('remote_id', $rid)->find();
        return $map && Db::name('goods')->where('id', (int)$map['goods_id'])->count() > 0;
    }

    private function guessCategory(string $title): int
    {
        foreach ($this->categoryRules as $cid => $words) {
            foreach ($words as $w) {
                if (mb_strpos($title, $w) !== false) {
                    return $cid;
                }
            }
        }
        return $this->defaultCategory;
    }

    /** 详情富文本里的 <img src> 也下载到本地并改写 */
    private function localizeInlineImages(string $html, int $rid): string
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }
        return preg_replace_callback('#(<img\b[^>]*\bsrc\s*=\s*)(["\']?)([^"\'\s>]+)\2#i', function ($m) use ($rid) {
            $local = $this->downloadImage($m[3], $rid);
            return $m[1] . '"' . $local . '"';
        }, $html);
    }

    /**
     * 下载图片到 public/uploads/collect/{rid}/，返回本地 URL；失败返回原远端地址
     * 同一地址重复执行直接复用已下载文件
     */
    private function downloadImage(string $url, int $rid): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (strpos($url, '//') === 0) $url = 'https:' . $url;
        if (!preg_match('#^https?://#i', $url)) {
            return $url; // 已是本地路径或 data:，原样返回
        }

        $dirRel  = $this->saveDir . '/' . $rid;
        $dirAbs  = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dirRel);
        $ext     = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            $ext = '';
        }
        $name = substr(md5($url), 0, 16);

        // 已下载过（任意扩展名）直接复用
        foreach (glob($dirAbs . DIRECTORY_SEPARATOR . $name . '.*') ?: [] as $exist) {
            if (filesize($exist) > 0) {
                return '/' . $dirRel . '/' . basename($exist);
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120 Safari/537.36',
            CURLOPT_REFERER        => 'https://dfghlp.tyiplkjh.cc/',
        ]);
        $bin  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($bin === false || $code !== 200 || strlen($bin) < 100) {
            $this->stats['img_fail']++;
            $this->log("[{$rid}] 图片下载失败 http={$code} {$err} {$url}");
            return $url;
        }
        // 校验确实是图片；扩展名以实际内容为准
        $info = @getimagesizefromstring($bin);
        if ($info === false) {
            $this->stats['img_fail']++;
            $this->log("[{$rid}] 非图片内容 type={$type} {$url}");
            return $url;
        }
        $realExt = image_type_to_extension($info[2], false);
        if ($realExt === 'jpeg') $realExt = 'jpg';
        if ($realExt) $ext = $realExt;
        if ($ext === '') $ext = 'jpg';

        if (!is_dir($dirAbs) && !@mkdir($dirAbs, 0755, true) && !is_dir($dirAbs)) {
            $this->stats['img_fail']++;
            $this->log("[{$rid}] 无法创建目录 {$dirAbs}");
            return $url;
        }
        $file = $dirAbs . DIRECTORY_SEPARATOR . $name . '.' . $ext;
        if (@file_put_contents($file, $bin) === false) {
            $this->stats['img_fail']++;
            $this->log("[{$rid}] 写文件失败 {$file}");
            return $url;
        }
        $this->stats['img_ok']++;
        return '/' . $dirRel . '/' . $name . '.' . $ext;
    }

    /** POST 请求远端接口，返回解码后的数组；失败重试 2 次后返回 null */
    private function post(string $method, array $data): ?array
    {
        $url = $this->apiBase . $method;
        for ($try = 1; $try <= 3; $try++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120 Safari/537.36',
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($body !== false && $code === 200) {
                $json = json_decode((string)$body, true);
                if (is_array($json)) {
                    return $json;
                }
                $err = '响应非 JSON';
            }
            $this->log("{$method} " . json_encode($data) . " 第 {$try} 次失败 http={$code} {$err}");
            usleep(500000 * $try);
        }
        return null;
    }

    private function throttle()
    {
        if ($this->sleepMs > 0) {
            usleep($this->sleepMs * 1000);
        }
    }

    /** 远端ID -> 本地商品ID 映射表，用于去重与重复执行时更新 */
    private function ensureMapTable()
    {
        Db::execute('CREATE TABLE IF NOT EXISTS `' . $this->prefix . 'collect_goods` (
            `remote_id`   int(10) unsigned NOT NULL COMMENT \'远端商品ID\',
            `goods_id`    int(10) unsigned NOT NULL DEFAULT 0 COMMENT \'本地goods.id\',
            `title`       varchar(100) NOT NULL DEFAULT \'\',
            `shop_name`   varchar(50) NOT NULL DEFAULT \'\' COMMENT \'远端店铺名\',
            `create_time` int(10) unsigned NOT NULL DEFAULT 0,
            `update_time` int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`remote_id`),
            KEY `idx_goods` (`goods_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=\'商品采集映射表\'');
    }

    private function log(string $msg)
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($this->logFile, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
    }
}

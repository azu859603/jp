<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\Lang;
use think\facade\View;

class Index extends Base
{

    /**
     * 列表摘要：结构化详情取首个区块正文（媒材/尺寸），普通详情取纯文本前50字
     */
    protected function goodsDescStr($content)
    {
        $content = (string)$content;
        if ($content === '') {
            return '';
        }
        // 摘要优先级：赏析 → 来源 → 首个区块 → 纯文本
        if (preg_match('/lot-sec">赏析<\/div><div class="lot-txt">(.*?)<\/div>/s', $content, $m)
            || preg_match('/lot-sec">来源<\/div><div class="lot-txt">(.*?)<\/div>/s', $content, $m)
            || preg_match('/lot-txt">(.*?)<\/div>/s', $content, $m)) {
            $txt = strip_tags(str_replace('<br>', ' ', $m[1]));
        } else {
            $txt = strip_tags($content);
        }
        return trim($txt);
    }
    /**
     * 首页拍品列表查询（首页整页与 ajax 局部刷新共用）
     */
    protected function dropMissingImages(array $categories)
    {
        // 分类图片文件已不存在时置空：模板回退为彩色圆底首字，避免浏览器显示裂图 / alt 文字
        $root = app()->getRootPath() . 'public';
        foreach ($categories as &$c) {
            if (!empty($c['image']) && strpos($c['image'], '/') === 0 && !is_file($root . $c['image'])) {
                $c['image'] = '';
            }
        }
        unset($c);
        return $categories;
    }

    protected function queryGoods()
    {
        $categoryId = (int)$this->request->param('category_id', 0);
        $keyword = trim($this->request->param('keyword', ''));
        $sellerId = (int)$this->request->param('seller_id', 0);
        $sort = $this->request->param('sort', 'new'); // end 即将结束 new 最新 price 价格
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $now = time();

        $query = Db::name('goods')->alias('g')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->field('g.*, u.nickname as seller_name')
            ->where('g.status', 1)
            ->where('g.start_time', '<=', $now)
            ->where('g.end_time', '>', $now);

        if ($categoryId > 0) {
            $query->where('g.category_id', $categoryId);
        }
        if ($sellerId > 0) {
            $query->where('g.seller_id', $sellerId);
        }
        if ($keyword !== '') {
            $query->whereLike('g.title', "%{$keyword}%");
        }

        $total = $query->count();
        switch ($sort) {
            case 'price':
                $list = $query->order('g.start_price', 'desc')->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
                break;
            case 'new':
                // 分类轮流混排：同分类内按最新排名，排名相同的不同分类交替出现，
                // 避免整批导入的同类商品在首页扎堆。
                // 排名在 PHP 里算：只取 id/category_id 两列（几千行、毫秒级），
                // 原来的逐行 COUNT 子查询在商品多时是 O(n²)，首页要 2 秒以上。
                $rows = (clone $query)->field('g.id, g.category_id')->order('g.id', 'desc')->select()->toArray();
                $rankInCate = [];
                foreach ($rows as &$r) {
                    $c = (int)$r['category_id'];
                    $rankInCate[$c] = isset($rankInCate[$c]) ? $rankInCate[$c] + 1 : 0;
                    $r['cate_rank'] = $rankInCate[$c];
                }
                unset($r);
                usort($rows, function ($a, $b) {
                    return $a['cate_rank'] <=> $b['cate_rank'] ?: $b['id'] <=> $a['id'];
                });
                $pageIds = array_column(array_slice($rows, ($page - 1) * $limit, $limit), 'id');
                $list = [];
                if ($pageIds) {
                    $found = Db::name('goods')->alias('g')
                        ->leftJoin('user u', 'g.seller_id = u.id')
                        ->field('g.*, u.nickname as seller_name')
                        ->whereIn('g.id', $pageIds)
                        ->select()->toArray();
                    $byId = array_column($found, null, 'id');
                    foreach ($pageIds as $id) {
                        if (isset($byId[$id])) {
                            $list[] = $byId[$id];
                        }
                    }
                }
                break;
            default:
                $list = $query->order('g.end_time', 'asc')->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
        }

        // 当前价 = 最高出价（无出价则起拍价）
        $tops = bid_top_prices(array_column($list, 'id'));
        foreach ($list as &$g) {
            $top = $tops[(int)$g['id']] ?? 0;
            $g['current_price'] = max((float)$top, (float)$g['start_price']);
            $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
            $g['desc_str'] = $this->goodsDescStr($g['content']);
            // 剩余时间（秒）
            $g['remain_sec'] = max($g['end_time'] - $now, 0);
        }
        unset($g);

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'categoryId'  => $categoryId,
            'sellerId'    => $sellerId,
            'keyword'     => $keyword,
            'sort'        => $sort,
            'now'         => $now,
        ];
    }

    /**
     * 关于我们（首页卡片「更多」的落地页，展示完整内容）
     */
    public function about()
    {
        View::assign([
            'about'      => about_us_content(),
            'depts'      => about_dept_content(),
            'page_title' => lang('关于我们'),
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }

    /**
     * 拍品列表片段（首页切换分类/排序时局部刷新，不整页重载）
     */
    public function goodsList()
    {
        $goods = $this->queryGoods();
        View::assign([
            'list'        => $goods['list'],
            'total'       => $goods['total'],
            'page'        => $goods['page'],
            'limit'       => $goods['limit'],
            'category_id' => $goods['categoryId'],
            'seller_id'   => $goods['sellerId'],
            'keyword'     => $goods['keyword'],
            'sort'        => $goods['sort'],
            'now'         => $goods['now'],
        ]);
        return View::fetch('goods_list');
    }

    /**
     * 首页：拍卖头条列表
     */
    public function index()
    {
        $goods = $this->queryGoods();
        $list       = $goods['list'];
        $total      = $goods['total'];
        $page       = $goods['page'];
        $limit      = $goods['limit'];
        $categoryId = $goods['categoryId'];
        $sellerId   = $goods['sellerId'];
        $keyword    = $goods['keyword'];
        $sort       = $goods['sort'];
        $now        = $goods['now'];
        $categories = $this->dropMissingImages(Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray());

        // 首页数据条：在拍拍品 / 累计成交 / 注册会员
        $stats = [
            'hot'     => (int)Db::name('goods')->where('status', 1)->where('start_time', '<=', $now)->where('end_time', '>', $now)->count(),
            'deals'   => (int)Db::name('goods')->where('status', 2)->count(),
            'members' => (int)Db::name('user')->count(),
        ];

        // 多语言映射分类名
        $langField = Lang::getLangSet() === 'zh-tw' ? 'name_tw' : (Lang::getLangSet() === 'en-us' ? 'name_en' : 'name');
        foreach ($categories as &$c) {
            $c['name'] = !empty($c[$langField]) ? $c[$langField] : $c['name'];
        }
        unset($c);

        // 成交记录（最近10条成交）
        $deals = Db::name('bid_record')->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.price, b.create_time, g.title, u.nickname')
            ->where('b.status', 1)
            ->order('b.id', 'desc')
            ->limit(10)
            ->order('b.create_time', 'desc')
            ->select()
            ->toArray();

        foreach ($deals as &$d) {
            $d['display_name'] = '***'.mb_substr($d['nickname'], -4);
            $d['is_mock'] = 0;
        }
        unset($d);
        // 不足 10 条时用在拍商品模拟补齐：随机取在拍拍品，价格按加价幅度在起拍价上加几档，
        // 时间落在最近 3 天内；按小时固定随机种子，同一小时内刷新页面内容不变
        $need = 10 - count($deals);
        if ($need > 0) {
            mt_srand((int)floor(time() / 3600));
            $pool = Db::name('goods')->where('status', 1)->where('end_time', '>', $now)
                ->field('title,start_price,raise_price')->orderRaw('RAND(' . (int)floor(time() / 3600) . ')')->limit($need)->select()->toArray();
            if (count($pool) < $need && count($pool) > 0) {
                while (count($pool) < $need) {
                    $pool[] = $pool[mt_rand(0, count($pool) - 1)];
                }
            }
            foreach ($pool as $g) {
                $raise = (float)$g['raise_price'] > 0 ? (float)$g['raise_price'] : max(1, round((float)$g['start_price'] * 0.05));
                $deals[] = [
                    'price'        => round((float)$g['start_price'] + $raise * mt_rand(1, 8), 2),
                    'create_time'  => $now - mt_rand(600, 3 * 86400),
                    'title'        => $g['title'],
                    'nickname'     => '',
                    'display_name' => '***' . mt_rand(1000, 9999),
                    'is_mock'      => 1,
                ];
            }
            usort($deals, function ($x, $y) { return $y['create_time'] <=> $x['create_time']; });
        }

        // 拍卖头条（对接新闻模块：最新3条已发布新闻）
        $langField = Lang::getLangSet() === 'zh-tw' ? 'title_tw' : (Lang::getLangSet() === 'en-us' ? 'title_en' : 'title');
        $headlines = Db::name('news')
            ->where('status', 1)
            ->field('id, title, title_tw, title_en, create_time')
            ->order('id', 'desc')
            ->limit(3)->select()->toArray();
        foreach ($headlines as &$h) {
            $h['title'] = !empty($h[$langField]) ? $h[$langField] : $h['title'];
            $h['end_time'] = $h['create_time'];
        }
        unset($h);

        // 分类入口（取前4个作为首页方块）
        $homeCates = array_slice($categories, 0, 4);

        // 首页banner（轮播图）
        $banners = Db::name('banner')->where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();

        View::assign([
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'categories'  => $categories,
            'category_id' => $categoryId,
            'seller_id'   => $sellerId,
            'keyword'     => $keyword,
            'sort'        => $sort,
            'now'         => $now,
            'stats'       => $stats,
            'page_title'  => lang('首页'),
            'tab_active'  => 'index',
            'hide_header' => true,
            'deals'       => $deals,
            'headlines'   => $headlines,
            'site_logo'   => get_setting('site_logo', ''),
            'homeCates'   => $homeCates,
            'banners'     => $banners,
            'about'       => about_us_content(),
        ]);
        return View::fetch();
    }

    /**
     * 分类页
     */
    public function category()
    {
        $categories = $this->dropMissingImages(Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray());
        // 多语言映射分类名
        $langField = Lang::getLangSet() === 'zh-tw' ? 'name_tw' : (Lang::getLangSet() === 'en-us' ? 'name_en' : 'name');
        foreach ($categories as &$c) {
            $c['name'] = !empty($c[$langField]) ? $c[$langField] : $c['name'];
        }
        unset($c);
        $ids = array_column($categories, 'id');
        // 口径与首页「在拍拍品」一致：拍卖中且在开拍～截拍时间窗内（已过截拍待结算的不算）
        $now = time();
        $counts = $ids
            ? Db::name('goods')->where('status', 1)->where('start_time', '<=', $now)->where('end_time', '>', $now)
                ->whereIn('category_id', $ids)->group('category_id')->column('COUNT(*)', 'category_id')
            : [];
        $totalCount = 0;
        foreach ($categories as &$c) {
            $c['goods_count'] = isset($counts[$c['id']]) ? (int)$counts[$c['id']] : 0;
            $totalCount += $c['goods_count'];
        }
        unset($c);
        $categoryId = (int)$this->request->param('category_id', 0);
        View::assign([
            'categories'       => $categories,
            'total_goods_count'=> $totalCount,
            'category_id'      => $categoryId,
            'page_title'       => lang('分类'),
            'tab_active'       => 'category',
            'hide_header'      => true,
        ]);
        return View::fetch();
    }

    /**
     * 分类拍品列表（首页分类入口单独跳转，带返回头部）
     */
    public function cateList()
    {
        $categoryId = (int)$this->request->param('category_id', 0);
        $cate = null;
        if ($categoryId > 0) {
            $cate = Db::name('category')->where('id', $categoryId)->where('status', 1)->find();
            if (!$cate) {
                return redirect('/');
            }
            // 多语言映射分类名
            $langField = Lang::getLangSet() === 'zh-tw' ? 'name_tw' : (Lang::getLangSet() === 'en-us' ? 'name_en' : 'name');
            $cate['name'] = !empty($cate[$langField]) ? $cate[$langField] : $cate['name'];
        }
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $now = time();

        $q = Db::name('goods')->alias('g')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->field('g.*, u.nickname as seller_name')
            ->where('g.status', 1)
            ->where('g.start_time', '<=', $now)
            ->where('g.end_time', '>', $now);
        if ($categoryId > 0) {
            $q->where('g.category_id', $categoryId);
        }
        $total = $q->count();
        $list = $q->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
        $tops = bid_top_prices(array_column($list, 'id'));
        foreach ($list as &$g) {
            $top = $tops[(int)$g['id']] ?? 0;
            $g['current_price'] = max((float)$top, (float)$g['start_price']);
            $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
            $g['desc_str'] = $this->goodsDescStr($g['content']);
            $g['remain_sec'] = max($g['end_time'] - $now, 0);
        }
        unset($g);

        View::assign([
            'cate'        => $cate,
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'category_id' => $categoryId,
            'now'         => $now,
            'page_title'  => $cate ? $cate['name'] : lang('全部拍品'),
            'tab_active'  => 'index',
        ]);
        // 加载更多为 AJAX 追加：只返回列表片段，不带布局
        if ($this->request->isAjax()) {
            return View::fetch('cate_list_items');
        }
        return View::fetch();
    }

    /**
     * 搜索页
     */
    public function search()
    {
        $keyword = trim($this->request->param('keyword', ''));
        $type = $this->request->param('type', 'goods'); // goods 拍品 shop 店铺
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $now = time();
        $list = [];
        $total = 0;

        if ($keyword !== '') {
            if ($type === 'shop') {
                // 店铺（卖家）搜索：匹配店铺名或昵称
                $q = Db::name('user')->where('is_seller', 1)->where('seller_check', 1)
                    ->where(function ($query) use ($keyword) {
                        $query->whereLike('shop_name', "%{$keyword}%")->whereOr('nickname', 'like', "%{$keyword}%");
                    });
                $total = $q->count();
                $list = $q->field('id, nickname, shop_name, avatar, seller_intro, total_sell, total_buy')
                    ->order('id', 'desc')->page($page, $limit)->select()->toArray();
            } else {
                $q = Db::name('goods')->alias('g')
                    ->leftJoin('user u', 'g.seller_id = u.id')
                    ->field('g.*, u.nickname as seller_name')
                    ->where('g.status', 1)
                    ->where('g.start_time', '<=', $now)
                    ->where('g.end_time', '>', $now)
                    ->whereLike('g.title', "%{$keyword}%");
                $total = $q->count();
                $list = $q->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
                $tops = bid_top_prices(array_column($list, 'id'));
                foreach ($list as &$g) {
                    $top = $tops[(int)$g['id']] ?? 0;
                    $g['current_price'] = max((float)$top, (float)$g['start_price']);
                    $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
            $g['desc_str'] = $this->goodsDescStr($g['content']);
                    $g['remain_sec'] = max($g['end_time'] - $now, 0);
                }
                unset($g);
            }
        }

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'keyword'    => $keyword,
            'type'       => $type,
            'now'        => $now,
            'page_title' => lang('搜索'),
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }

    /**
     * 成交记录详情（已支付订单列表）
     */
    public function deals()
    {
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 15;

        $query = Db::name('order')->alias('o')
            ->leftJoin('goods g', 'o.goods_id = g.id')
            ->leftJoin('user s', 'o.seller_id = s.id')
            ->leftJoin('user b', 'o.buyer_id = b.id')
            ->field('o.id, o.order_no, o.goods_id, o.goods_title, o.goods_cover, o.price, o.pay_time, o.create_time, s.shop_name as seller_shop, s.nickname as seller_nick, b.nickname as buyer_nick')
            ->where('o.pay_status', 1)
            ->where('o.order_status', '<>', 4);

        $total = $query->count();
        $list = $query->order('o.id', 'desc')->page($page, $limit)->select()->toArray();

        foreach ($list as &$d) {
            $d['seller_name'] = !empty($d['seller_shop']) ? $d['seller_shop'] : (translate_nickname($d['seller_nick']) ?: lang('卖家'));
            $d['buyer_name']  = !empty($d['buyer_nick']) ? mb_substr($d['buyer_nick'], 0, 1) . '***' : '匿***';
            $d['deal_time']   = $d['pay_time'] ?: $d['create_time'];
        }
        unset($d);
        // 根据成交时间排序
        $list = arraySort($list,'deal_time','desc');

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'page_title' => lang('成交记录'),
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }
}
<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

class Index extends Base
{
    /**
     * 首页：拍卖头条列表
     */
    public function index()
    {
        $categoryId = (int)$this->request->param('category_id', 0);
        $keyword = trim($this->request->param('keyword', ''));
        $sellerId = (int)$this->request->param('seller_id', 0);
        $sort = $this->request->param('sort', 'end'); // end 即将结束 new 最新 price 价格
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
                $list = $query->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
                break;
            default:
                $list = $query->order('g.end_time', 'asc')->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
        }

        // 当前价 = 最高出价（无出价则起拍价）
        foreach ($list as &$g) {
            $top = Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->max('price');
            $g['current_price'] = $top ? (float)$top : (float)$g['start_price'];
            $g['current_price'] = max($g['current_price'], (float)$g['start_price']);
            $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
            // 剩余时间（秒）
            $g['remain_sec'] = max($g['end_time'] - $now, 0);
        }
        unset($g);

        $categories = Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();

        // 成交记录（最近5条成交）
        $deals = Db::name('bid_record')->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.price, b.create_time, g.title, u.nickname')
            ->where('b.status', 1)
            ->order('b.id', 'desc')
            ->limit(5)->select()->toArray();
        foreach ($deals as &$d) {
            $d['display_name'] = mb_substr($d['nickname'], 0, 1) . '***';
        }
        unset($d);

        // 拍卖头条（对接新闻模块：最新3条已发布新闻）
        $headlines = Db::name('news')
            ->where('status', 1)
            ->field('id, title, create_time')
            ->order('id', 'desc')
            ->limit(3)->select()->toArray();
        foreach ($headlines as &$h) {
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
            'page_title'  => '首页',
            'tab_active'  => 'index',
            'hide_header' => true,
            'deals'       => $deals,
            'headlines'   => $headlines,
            'site_logo'   => get_setting('site_logo', ''),
            'homeCates'   => $homeCates,
            'banners'     => $banners,
        ]);
        return View::fetch();
    }

    /**
     * 分类页
     */
    public function category()
    {
        $categories = Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        // 各分类竞拍中（status=1）商品数
        $ids = array_column($categories, 'id');
        $counts = $ids
            ? Db::name('goods')->where('status', 1)->whereIn('category_id', $ids)->group('category_id')->column('COUNT(*)', 'category_id')
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
            'page_title'       => '分类',
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
        }
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $now = time();

        $q = Db::name('goods')->alias('g')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->field('g.*, u.nickname as seller_name')
            ->where('g.status', 1);
        if ($categoryId > 0) {
            $q->where('g.category_id', $categoryId);
        }
        $total = $q->count();
        $list = $q->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
        foreach ($list as &$g) {
            $top = Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->max('price');
            $g['current_price'] = max((float)$top, (float)$g['start_price']);
            $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
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
            'page_title'  => $cate ? $cate['name'] : '全部拍品',
            'tab_active'  => 'index',
        ]);
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
                foreach ($list as &$g) {
                    $top = Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->max('price');
                    $g['current_price'] = max((float)$top, (float)$g['start_price']);
                    $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
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
            'page_title' => '搜索',
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
            $d['seller_name'] = !empty($d['seller_shop']) ? $d['seller_shop'] : ($d['seller_nick'] ?: '卖家');
            $d['buyer_name']  = !empty($d['buyer_nick']) ? mb_substr($d['buyer_nick'], 0, 1) . '***' : '匿***';
            $d['deal_time']   = $d['pay_time'] ?: $d['create_time'];
        }
        unset($d);

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'page_title' => '成交记录',
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }
}

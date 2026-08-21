<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Index extends Base
{
    /**
     * 仪表盘
     */
    public function index()
    {
        $now = time();

        // 统计
        $stats = [
            'user_count'      => Db::name('user')->count(),
            'seller_count'    => Db::name('user')->where('is_seller', 1)->count(),
            'goods_count'     => Db::name('goods')->count(),
            'auctioning'      => Db::name('goods')->where('status', 1)->where('end_time', '>', $now)->count(),
            'order_count'     => Db::name('order')->count(),
            'unpaid_order'    => Db::name('order')->where('pay_status', 0)->where('order_status', 0)->count(),
            'pending_check'   => Db::name('goods')->where('status', 0)->count(),
            'pending_seller'  => Db::name('user')->where('seller_check', 0)->count(),
            'pending_withdraw'=> Db::name('withdraw')->where('status', 0)->count(),
            'total_amount'    => Db::name('order')->where('pay_status', 1)->sum('price'),
            'total_commission'=> Db::name('order')->where('pay_status', 1)->sum('commission'),
            'today_amount'    => Db::name('order')->where('pay_status', 1)->where('pay_time', '>', strtotime('today'))->sum('price'),
        ];

        // 最近订单
        $recentOrders = Db::name('order')->order('id', 'desc')->limit(8)->select()->toArray();

        // 最近出价
        $recentBids = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.*, g.title as goods_title, u.nickname, u.mobile')
            ->order('b.id', 'desc')
            ->limit(8)
            ->select()
            ->toArray();

        // 近7天成交走势
        $trend = [];
        $maxAmount = 0;
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = strtotime(date('Y-m-d', strtotime("-{$i} day")));
            $dayEnd = $dayStart + 86400;
            $amount = round((float)Db::name('order')->where('pay_time', '>=', $dayStart)->where('pay_time', '<', $dayEnd)->sum('price'), 2);
            $maxAmount = max($maxAmount, $amount);
            $trend[] = [
                'date'   => date('m-d', $dayStart),
                'count'  => Db::name('order')->where('pay_time', '>=', $dayStart)->where('pay_time', '<', $dayEnd)->count(),
                'amount' => $amount,
                'height' => $amount > 0 ? max(8, (int)($amount / max($maxAmount, 0.01) * 120)) : 4,
            ];
        }

        View::assign([
            'stats'       => $stats,
            'recentOrders'=> $recentOrders,
            'recentBids'  => $recentBids,
            'trend'       => $trend,
            'menu_active' => '/admin1314/index/index',
        ]);
        return View::fetch();
    }
}

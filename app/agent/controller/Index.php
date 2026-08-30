<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 数据概览（只读）
 */
class Index extends Base
{
    public function index()
    {
        $ids = $this->memberIds();
        $now = time();

        $stat = [
            'member_total'  => count($ids),
            'member_today'  => 0,
            'member_month'  => 0,
            'seller_count'  => 0,
            'buy_amount'    => 0.00,
            'buy_count'     => 0,
            'sell_amount'   => 0.00,
            'sell_count'    => 0,
            'unpaid_count'  => 0,
            'bid_count'     => 0,
        ];

        $recent = [];
        $trend  = [];

        if (!$this->hasNoMember()) {
            $todayStart = strtotime('today 00:00:00');
            $monthStart = strtotime(date('Y-m-01 00:00:00'));

            $stat['member_today'] = (clone $this->memberQuery())->where('reg_time', '>=', $todayStart)->count();
            $stat['member_month'] = (clone $this->memberQuery())->where('reg_time', '>=', $monthStart)->count();
            $stat['seller_count'] = (clone $this->memberQuery())->where('is_seller', 1)->where('seller_check', 1)->count();

            // 下级作为买家的已支付订单
            $buy = Db::name('order')->whereIn('buyer_id', $ids)->where('pay_status', 1);
            $stat['buy_count']  = (clone $buy)->count();
            $stat['buy_amount'] = round((float)(clone $buy)->sum('price'), 2);

            // 下级作为卖家的已支付订单（下级里有卖家时才有数据）
            $sell = Db::name('order')->whereIn('seller_id', $ids)->where('pay_status', 1);
            $stat['sell_count']  = (clone $sell)->count();
            $stat['sell_amount'] = round((float)(clone $sell)->sum('seller_income'), 2);

            // 下级待付款订单
            $stat['unpaid_count'] = Db::name('order')
                ->whereIn('buyer_id', $ids)
                ->where('pay_status', 0)
                ->where('order_status', 0)
                ->count();

            // 下级累计出价次数
            $stat['bid_count'] = Db::name('bid_record')->whereIn('user_id', $ids)->count();

            // 最近加入的 10 位下级
            $recent = (clone $this->memberQuery())
                ->field('id,nickname,mobile,avatar,is_seller,seller_check,status,total_buy,total_sell,reg_time')
                ->order('id', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            foreach ($recent as &$r) {
                $r['mobile_mask'] = $this->maskMobile($r['mobile']);
                unset($r['mobile']);
            }
            unset($r);

            // 近 14 天新增下级趋势
            $trend = $this->joinTrend(14);
        }

        View::assign([
            'stat'        => $stat,
            'recent'      => $recent,
            'trend'       => $trend,
            'now'         => $now,
            'menu_active' => '/agent/index/index',
        ]);
        return View::fetch();
    }

    /**
     * 近 N 天新增下级趋势
     * @param int $days
     * @return array
     */
    protected function joinTrend($days = 14)
    {
        $rows = [];
        $max  = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $from = strtotime('today -' . $i . ' days');
            $to   = $from + 86399;
            $count = (clone $this->memberQuery())
                ->where('reg_time', 'between', [$from, $to])
                ->count();
            $max = max($max, $count);
            $rows[] = ['label' => date('m-d', $from), 'count' => $count];
        }
        foreach ($rows as &$r) {
            $r['height'] = $max > 0 && $r['count'] > 0 ? max(6, (int)($r['count'] / $max * 120)) : 3;
        }
        unset($r);
        return $rows;
    }
}

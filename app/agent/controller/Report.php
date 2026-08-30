<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 团队业绩报表（只读）
 *
 * 口径说明：
 * - 成交额/订单数 = 我的下级作为「买家」且已支付的订单，按 pay_time 归期；
 * - 卖出收入     = 我的下级作为「卖家」且已支付的订单 seller_income，按 pay_time 归期；
 * - 新增会员     = 下级的 reg_time 落在区间内；
 * - 平台佣金仅作展示，一期不做代理分佣（结算逻辑 settle_goods() 未改动）。
 */
class Report extends Base
{
    public function index()
    {
        list($startTime, $endTime, $startDate, $endDate) = $this->rangeParam(30);
        $dim = $this->request->param('dim', 'day');
        if (!in_array($dim, ['day', 'week', 'month'], true)) {
            $dim = 'day';
        }

        $ids = $this->memberIds();

        $summary = [
            'member_total' => count($ids),
            'member_new'   => 0,
            'order_count'  => 0,
            'amount'       => 0.00,
            'commission'   => 0.00,
            'avg_price'    => 0.00,
            'buyer_count'  => 0,
            'sell_count'   => 0,
            'sell_amount'  => 0.00,
            'unpaid_count' => 0,
        ];
        $rows = [];

        if (!$this->hasNoMember()) {
            $summary['member_new'] = (clone $this->memberQuery())
                ->where('reg_time', 'between', [$startTime, $endTime])
                ->count();

            $paid = Db::name('order')
                ->whereIn('buyer_id', $ids)
                ->where('pay_status', 1)
                ->where('pay_time', 'between', [$startTime, $endTime]);

            $summary['order_count'] = (clone $paid)->count();
            $summary['amount']      = round((float)(clone $paid)->sum('price'), 2);
            $summary['commission']  = round((float)(clone $paid)->sum('commission'), 2);
            $summary['buyer_count'] = (clone $paid)->group('buyer_id')->count();
            $summary['avg_price']   = $summary['order_count'] > 0
                ? round($summary['amount'] / $summary['order_count'], 2) : 0;

            $sold = Db::name('order')
                ->whereIn('seller_id', $ids)
                ->where('pay_status', 1)
                ->where('pay_time', 'between', [$startTime, $endTime]);
            $summary['sell_count']  = (clone $sold)->count();
            $summary['sell_amount'] = round((float)(clone $sold)->sum('seller_income'), 2);

            $summary['unpaid_count'] = Db::name('order')
                ->whereIn('buyer_id', $ids)
                ->where('pay_status', 0)
                ->where('order_status', 0)
                ->where('create_time', 'between', [$startTime, $endTime])
                ->count();

            $rows = $this->trendRows($ids, $startTime, $endTime, $dim);
        }

        $maxAmount = 0;
        foreach ($rows as $r) {
            $maxAmount = max($maxAmount, $r['amount']);
        }
        foreach ($rows as &$r) {
            $r['height'] = $maxAmount > 0 && $r['amount'] > 0
                ? max(6, (int)($r['amount'] / $maxAmount * 150)) : 3;
        }
        unset($r);

        View::assign([
            'summary'     => $summary,
            'rows'        => array_reverse($rows),  // 表格按时间倒序
            'chart'       => $rows,                 // 图表按时间正序
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'dim'         => $dim,
            'menu_active' => '/agent/report/index',
        ]);
        return View::fetch();
    }

    /**
     * 按维度切分时间段统计团队业绩
     * @param array $ids 下级会员ID（调用前已确保非空）
     */
    protected function trendRows(array $ids, $startTime, $endTime, $dim)
    {
        $points = [];
        if ($dim === 'month') {
            $cur = strtotime(date('Y-m-01 00:00:00', $startTime));
            while ($cur <= $endTime) {
                $next = strtotime('+1 month', $cur);
                $points[] = [date('Y-m', $cur), $cur, min($next - 1, $endTime)];
                $cur = $next;
            }
        } elseif ($dim === 'week') {
            $cur = strtotime('monday this week', $startTime);
            while ($cur <= $endTime) {
                $next = strtotime('+1 week', $cur);
                $points[] = [date('m-d', $cur) . '~' . date('m-d', $next - 1), $cur, min($next - 1, $endTime)];
                $cur = $next;
            }
        } else {
            $cur = strtotime(date('Y-m-d 00:00:00', $startTime));
            while ($cur <= $endTime) {
                $next = $cur + 86400;
                $points[] = [date('m-d', $cur), $cur, min($next - 1, $endTime)];
                $cur = $next;
            }
        }

        // 超过 60 个点时只保留最近 60 个，避免图表过密
        if (count($points) > 60) {
            $points = array_slice($points, -60);
        }

        $rows = [];
        foreach ($points as $p) {
            list($label, $from, $to) = $p;

            $q = Db::name('order')->whereIn('buyer_id', $ids)
                ->where('pay_status', 1)
                ->where('pay_time', 'between', [$from, $to]);
            $amount = round((float)(clone $q)->sum('price'), 2);
            $count  = (clone $q)->count();

            $newMember = (clone $this->memberQuery())
                ->where('reg_time', 'between', [$from, $to])
                ->count();

            $rows[] = [
                'label'      => $label,
                'count'      => $count,
                'amount'     => $amount,
                'new_member' => $newMember,
                'avg'        => $count > 0 ? round($amount / $count, 2) : 0,
            ];
        }
        return $rows;
    }
}

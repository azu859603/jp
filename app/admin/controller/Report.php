<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 交易报表
 */
class Report extends Base
{
    /**
     * 交易报表：按日/周/月统计成交额、订单数、佣金
     */
    public function index()
    {
        // 时间范围（默认近 30 天）
        $startDate = trim($this->request->param('start_date', date('Y-m-d', strtotime('-29 days'))));
        $endDate   = trim($this->request->param('end_date', date('Y-m-d')));
        $dim       = $this->request->param('dim', 'day'); // day 日 week 周 month 月

        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime   = strtotime($endDate . ' 23:59:59');
        if (!$startTime || !$endTime || $startTime > $endTime) {
            $startTime = strtotime('-29 days 00:00:00');
            $endTime   = strtotime('today 23:59:59');
            $startDate = date('Y-m-d', $startTime);
            $endDate   = date('Y-m-d', $endTime);
        }

        // 汇总：已支付订单
        $paid = Db::name('order')->where('pay_status', 1)
            ->where('pay_time', 'between', [$startTime, $endTime]);
        $summary = [
            'order_count' => (clone $paid)->count(),
            'amount'      => round((float)(clone $paid)->sum('price'), 2),
            'commission'  => round((float)(clone $paid)->sum('commission'), 2),
            'income'      => round((float)(clone $paid)->sum('seller_income'), 2),
            'buyer_count' => (clone $paid)->group('buyer_id')->count(),
        ];
        $summary['avg_price'] = $summary['order_count'] > 0
            ? round($summary['amount'] / $summary['order_count'], 2) : 0;

        // 同期拍卖情况
        $summary['deal_goods'] = Db::name('goods')->where('status', 2)
            ->where('end_time', 'between', [$startTime, $endTime])->count();
        $summary['fail_goods'] = Db::name('goods')->where('status', 3)
            ->where('end_time', 'between', [$startTime, $endTime])->count();
        $total = $summary['deal_goods'] + $summary['fail_goods'];
        $summary['deal_rate'] = $total > 0 ? round($summary['deal_goods'] / $total * 100, 1) : 0;

        // 未付款订单（同期拍下但未支付）
        $summary['unpaid_count'] = Db::name('order')->where('pay_status', 0)
            ->where('order_status', 0)
            ->where('create_time', 'between', [$startTime, $endTime])->count();

        // 趋势明细
        $rows = $this->trendRows($startTime, $endTime, $dim);
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
            'rows'        => array_reverse($rows),   // 表格按时间倒序
            'chart'       => $rows,                  // 图表按时间正序
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'dim'         => $dim,
            'menu_active' => '/admin1314/report/index',
        ]);
        return View::fetch();
    }

    /**
     * 按维度切分时间段并统计
     */
    protected function trendRows($startTime, $endTime, $dim)
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

        // 一条按天分组的聚合查询代替「每个时间点 3 条查询」；月 / 周 / 日的边界都落在自然日上，按天桶再合并即可
        $off  = (int)date('Z');
        $from = $points[0][1];
        $to   = $points[count($points) - 1][2];
        $buckets = [];
        $rows = Db::name('order')
            ->field("FLOOR((pay_time + {$off}) / 86400) AS d, COUNT(*) AS c, SUM(price) AS a, SUM(commission) AS m")
            ->where('pay_status', 1)->where('pay_time', 'between', [$from, $to])
            ->group('d')->select()->toArray();
        foreach ($rows as $b) {
            $buckets[(int)$b['d']] = [(int)$b['c'], (float)$b['a'], (float)$b['m']];
        }
        $rows = [];
        foreach ($points as $p) {
            list($label, $pf, $pt) = $p;
            $count = 0; $amount = 0; $commission = 0;
            for ($d = (int)floor(($pf + $off) / 86400); $d <= (int)floor(($pt + $off) / 86400); $d++) {
                if (isset($buckets[$d])) {
                    $count += $buckets[$d][0]; $amount += $buckets[$d][1]; $commission += $buckets[$d][2];
                }
            }
            $amount = round($amount, 2); $commission = round($commission, 2);
            $rows[] = [
                'label'      => $label,
                'count'      => $count,
                'amount'     => $amount,
                'commission' => $commission,
                'avg'        => $count > 0 ? round($amount / $count, 2) : 0,
            ];
        }
        return $rows;
    }
}

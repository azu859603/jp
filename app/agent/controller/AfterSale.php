<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 售后管理
 *
 * 数据范围：卖家是我的一级下级的售后单（团队买家在别人店铺的售后不在此范围）。
 * 处理逻辑与主后台一致：同意退款时款项退回买家余额，并从卖家的成交收入中扣回。
 */
class AfterSale extends Base
{
    /**
     * 售后单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $status  = $this->request->param('status', '');
            $keyword = trim((string)$this->request->param('keyword', ''));

            $query = $this->saleQuery();
            if ($status !== '') {
                $query->where('a.status', (int)$status);
            }
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('a.order_no', "%{$keyword}%")
                        ->whereOr('a.goods_title', 'like', "%{$keyword}%")
                        ->whereOr('u.account', 'like', "%{$keyword}%");
                });
            }

            $total = $query->count();
            $list  = $query->order('a.id', 'desc')->page($page, $limit)->select()->toArray();
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/agent/after_sale/index');
        return View::fetch();
    }

    /**
     * 售后单详情
     */
    public function detail()
    {
        $sale = $this->assertMySale($this->request->param('id', 0));
        $order = Db::name('order')->where('id', $sale['order_id'])->find();
        $sale['order_status']     = $order['order_status'] ?? null;
        $sale['pay_status']       = $order['pay_status'] ?? null;
        $sale['create_time_text'] = $sale['create_time'] ? date('Y-m-d H:i:s', $sale['create_time']) : '-';
        $sale['handle_time_text'] = $sale['handle_time'] ? date('Y-m-d H:i:s', $sale['handle_time']) : '-';
        $sale['pay_time_text']    = !empty($order['pay_time']) ? date('Y-m-d H:i:s', $order['pay_time']) : '-';
        $sale['finish_time_text'] = !empty($order['finish_time']) ? date('Y-m-d H:i:s', $order['finish_time']) : '-';

        View::assign([
            'sale'        => $sale,
            'menu_active' => '/agent/after_sale/index',
        ]);
        return View::fetch();
    }

    /**
     * 处理售后单（同意退款 / 驳回）
     */
    public function handle()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id     = (int)$this->request->post('id');
        $action = trim((string)$this->request->post('action', ''));
        $note   = trim((string)$this->request->post('note', ''));

        // 归属校验：不是我团队的售后单直接拒绝
        $this->assertMySale($id);

        if ($action !== 'agree' && $action !== 'reject') {
            return json(['code' => 0, 'msg' => '操作类型错误']);
        }
        if ($action === 'reject' && $note === '') {
            return json(['code' => 0, 'msg' => '驳回时请填写处理备注']);
        }

        $now = time();
        Db::startTrans();
        try {
            // 行锁在事务内：重复点击只有一次生效
            $sale = Db::name('after_sale')->where('id', $id)->lock(true)->find();
            if (!$sale) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '售后单不存在']);
            }
            if ((int)$sale['status'] !== 0) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '该售后单已处理']);
            }
            $order = Db::name('order')->where('id', $sale['order_id'])->lock(true)->find();

            if ($action === 'agree') {
                // 1. 买家退款
                $buyer = Db::name('user')->where('id', $sale['user_id'])->lock(true)->find();
                if (!$buyer) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '买家不存在']);
                }
                $refund     = round((float)$sale['price'], 2);
                $newBalance = round($buyer['balance'] + $refund, 2);
                Db::name('user')->where('id', $buyer['id'])->update(['balance' => $newBalance, 'update_time' => $now]);
                $this->addBalanceLog($buyer['id'], 'refund', $refund, $newBalance, '售后退款：' . $sale['order_no']);

                // 2. 卖家收入扣回
                // 成交款只有在买家确认收货后才会打给卖家；未入账的（income_paid=0）不需要扣回
                if ($order && (int)$order['income_paid'] === 1 && (float)$order['seller_income'] > 0) {
                    $seller = Db::name('user')->where('id', $order['seller_id'])->lock(true)->find();
                    if (!$seller) {
                        Db::rollback();
                        return json(['code' => 0, 'msg' => '卖家不存在']);
                    }
                    $income = round((float)$order['seller_income'], 2);
                    if ((float)$seller['balance'] < $income) {
                        Db::rollback();
                        return json(['code' => 0, 'msg' => '卖家余额不足，无法扣回成交收入，请联系卖家充值后再处理']);
                    }
                    $sellerBalance = round($seller['balance'] - $income, 2);
                    Db::name('user')->where('id', $seller['id'])->update(['balance' => $sellerBalance, 'update_time' => $now]);
                    $this->addBalanceLog($seller['id'], 'refund', -$income, $sellerBalance, '售后扣回成交收入：' . $sale['order_no']);
                }

                // 3. 订单标记为已退款
                if ($order) {
                    Db::name('order')->where('id', $order['id'])->update([
                        'pay_status'   => 2,
                        'order_status' => 3,
                        'update_time'  => $now,
                    ]);
                }
                if (Db::name('after_sale')->where('id', $id)->where('status', 0)->update([
                    'status'      => 1,
                    'admin_note'  => mb_substr($note, 0, 200),
                    'handle_time' => $now,
                ]) !== 1) {
                    throw new \RuntimeException('售后单状态已变化');
                }
            } else {
                // 驳回：订单恢复已完成
                if ($order) {
                    Db::name('order')->where('id', $order['id'])->update([
                        'order_status' => 3,
                        'update_time'  => $now,
                    ]);
                }
                if (Db::name('after_sale')->where('id', $id)->where('status', 0)->update([
                    'status'      => 2,
                    'admin_note'  => mb_substr($note, 0, 200),
                    'handle_time' => $now,
                ]) !== 1) {
                    throw new \RuntimeException('售后单状态已变化');
                }
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '处理失败：' . $e->getMessage()]);
        }
        agent_log(($action === 'agree' ? '售后同意退款：' : '售后驳回：') . $sale['order_no']);
        return json(['code' => 1, 'msg' => $action === 'agree' ? '已同意退款' : '已驳回']);
    }

    // ------------------------------------------------------------------

    /**
     * 团队范围内的售后单查询：卖家是我的下级
     */
    protected function saleQuery()
    {
        $ids = $this->memberIds() ?: [-1];
        return Db::name('after_sale')->alias('a')
            ->leftJoin('user u', 'a.user_id = u.id')
            ->leftJoin('user s', 'a.seller_id = s.id')
            ->field('a.*, u.account as buyer_mobile, u.nickname as buyer_name, s.account as seller_mobile, s.nickname as seller_name')
            ->whereIn('a.seller_id', $ids);
    }

    /**
     * 归属校验：不是我团队的售后单直接踢回列表
     */
    protected function assertMySale($id)
    {
        $id   = (int)$id;
        $sale = $id > 0 ? $this->saleQuery()->where('a.id', $id)->find() : null;
        if (empty($sale)) {
            if ($this->request->isAjax()) {
                throw new \think\exception\HttpResponseException(json(['code' => 0, 'msg' => '该售后单不属于您的团队']));
            }
            throw new \think\exception\HttpResponseException(response('', 302, ['Location' => '/agent/after_sale/index']));
        }
        return $sale;
    }

    /**
     * 写余额流水
     */
    protected function addBalanceLog($userId, $type, $amount, $balance, $remark)
    {
        Db::name('balance_log')->insert([
            'user_id'     => $userId,
            'type'        => $type,
            'amount'      => $amount,
            'balance'     => $balance,
            'remark'      => $remark,
            'create_time' => time(),
        ]);
    }
}

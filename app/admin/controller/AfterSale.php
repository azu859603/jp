<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 售后管理
 */
class AfterSale extends Base
{
    /**
     * 售后单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $status = $this->request->param('status', '');
            $keyword = trim($this->request->param('keyword', ''));

            $query = Db::name('after_sale')->alias('a')
                ->leftJoin('user u', 'a.user_id = u.id')
                ->field('a.*, u.mobile as buyer_mobile, u.nickname as buyer_name');

            if ($status !== '') {
                $query->where('a.status', (int)$status);
            }
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('a.order_no', "%{$keyword}%")
                        ->whereOr('a.goods_title', 'like', "%{$keyword}%")
                        ->whereOr('u.mobile', 'like', "%{$keyword}%");
                });
            }

            $total = $query->count();
            $list = $query->order('a.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/after_sale/index');
        return View::fetch();
    }

    /**
     * 售后单详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id');
        $sale = Db::name('after_sale')->alias('a')
            ->leftJoin('user u', 'a.user_id = u.id')
            ->leftJoin('user s', 'a.seller_id = s.id')
            ->leftJoin('order o', 'a.order_id = o.id')
            ->field('a.*, u.mobile as buyer_mobile, u.nickname as buyer_name, s.mobile as seller_mobile, s.nickname as seller_name, o.order_status, o.pay_status, o.pay_time, o.finish_time')
            ->where('a.id', $id)
            ->find();
        if (!$sale) {
            return $this->error('售后单不存在');
        }
        $sale['create_time_text'] = $sale['create_time'] ? date('Y-m-d H:i:s', $sale['create_time']) : '-';
        $sale['handle_time_text'] = $sale['handle_time'] ? date('Y-m-d H:i:s', $sale['handle_time']) : '-';
        $sale['pay_time_text'] = $sale['pay_time'] ? date('Y-m-d H:i:s', $sale['pay_time']) : '-';
        $sale['finish_time_text'] = $sale['finish_time'] ? date('Y-m-d H:i:s', $sale['finish_time']) : '-';

        View::assign([
            'sale'        => $sale,
            'menu_active' => '/admin1314/after_sale/index',
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
        $id = (int)$this->request->post('id');
        $action = trim($this->request->post('action', ''));
        $note = trim($this->request->post('note', ''));

        $sale = Db::name('after_sale')->where('id', $id)->lock(true)->find();
        if (!$sale) {
            return json(['code' => 0, 'msg' => '售后单不存在']);
        }
        if ($sale['status'] != 0) {
            return json(['code' => 0, 'msg' => '该售后单已处理']);
        }
        if ($action !== 'agree' && $action !== 'reject') {
            return json(['code' => 0, 'msg' => '操作类型错误']);
        }
        if ($action === 'reject' && $note === '') {
            return json(['code' => 0, 'msg' => '驳回时请填写处理备注']);
        }

        $now = time();
        $order = Db::name('order')->where('id', $sale['order_id'])->lock(true)->find();

        Db::startTrans();
        try {
            if ($action === 'agree') {
                // 1. 买家余额退款
                $buyer = Db::name('user')->where('id', $sale['user_id'])->lock(true)->find();
                if (!$buyer) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '买家不存在']);
                }
                $refund = (float)$sale['price'];
                $newBalance = round($buyer['balance'] + $refund, 2);
                Db::name('user')->where('id', $buyer['id'])->update([
                    'balance'     => $newBalance,
                    'update_time' => $now,
                ]);
                $this->addBalanceLog($buyer['id'], 'refund', $refund, $newBalance, '售后退款：' . $sale['order_no']);

                // 2. 卖家收入扣回（成交价 - 佣金）
                if ($order && $order['seller_income'] > 0) {
                    $seller = Db::name('user')->where('id', $order['seller_id'])->lock(true)->find();
                    if (!$seller) {
                        Db::rollback();
                        return json(['code' => 0, 'msg' => '卖家不存在']);
                    }
                    if ($seller['balance'] < $order['seller_income']) {
                        Db::rollback();
                        return json(['code' => 0, 'msg' => '卖家余额不足，无法扣回成交收入，请联系卖家充值后再处理']);
                    }
                    $sellerBalance = round($seller['balance'] - $order['seller_income'], 2);
                    Db::name('user')->where('id', $seller['id'])->update([
                        'balance'     => $sellerBalance,
                        'update_time' => $now,
                    ]);
                    $this->addBalanceLog($seller['id'], 'refund', -$order['seller_income'], $sellerBalance, '售后扣回成交收入：' . $sale['order_no']);
                }

                // 3. 订单状态：已退款（订单恢复已完成状态，支付状态标记已退款）
                if ($order) {
                    Db::name('order')->where('id', $order['id'])->update([
                        'pay_status'   => 2,
                        'order_status' => 3,
                        'update_time'  => $now,
                    ]);
                }

                Db::name('after_sale')->where('id', $id)->update([
                    'status'      => 1,
                    'admin_note'  => $note,
                    'handle_time' => $now,
                ]);
                admin_log('售后同意退款：' . $sale['order_no']);
            } else {
                // 驳回：订单恢复已完成
                if ($order) {
                    Db::name('order')->where('id', $order['id'])->update([
                        'order_status' => 3,
                        'update_time'  => $now,
                    ]);
                }
                Db::name('after_sale')->where('id', $id)->update([
                    'status'      => 2,
                    'admin_note'  => $note,
                    'handle_time' => $now,
                ]);
                admin_log('售后驳回：' . $sale['order_no']);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '处理失败：' . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => '处理成功']);
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

<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Order extends Base
{
    /**
     * 订单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $orderStatus = $this->request->param('order_status', '');
            $payStatus = $this->request->param('pay_status', '');

            $query = Db::name('order')->alias('o')
                ->leftJoin('user u', 'o.buyer_id = u.id')
                ->leftJoin('user s', 'o.seller_id = s.id')
                ->field('o.*, u.mobile as buyer_mobile, u.nickname as buyer_name, s.mobile as seller_mobile, s.nickname as seller_name');

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('o.order_no', "%{$keyword}%")
                        ->whereOr('o.goods_title', 'like', "%{$keyword}%")
                        ->whereOr('u.mobile', 'like', "%{$keyword}%");
                });
            }
            if ($orderStatus !== '') {
                $query->where('o.order_status', (int)$orderStatus);
            }
            if ($payStatus !== '') {
                $query->where('o.pay_status', (int)$payStatus);
            }

            $total = $query->count();
            $list = $query->order('o.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/order/index');
        return View::fetch();
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id');
        $order = Db::name('order')->alias('o')
            ->leftJoin('user u', 'o.buyer_id = u.id')
            ->leftJoin('user s', 'o.seller_id = s.id')
            ->field('o.*, u.mobile as buyer_mobile, u.nickname as buyer_name, s.mobile as seller_mobile, s.nickname as seller_name')
            ->where('o.id', $id)
            ->find();

        if (!$order) {
            return $this->error('订单不存在');
        }

        $order['pay_time_text'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '-';
        $order['ship_time_text'] = $order['ship_time'] ? date('Y-m-d H:i:s', $order['ship_time']) : '-';
        $order['finish_time_text'] = $order['finish_time'] ? date('Y-m-d H:i:s', $order['finish_time']) : '-';

        View::assign([
            'order'      => $order,
            'menu_active'=> '/admin1314/order/index',
        ]);
        return View::fetch();
    }

    /**
     * 订单发货
     */
    public function ship()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $company = trim($this->request->post('company', ''));
        $shipNo = trim($this->request->post('ship_no', ''));

        if (empty($shipNo)) {
            return json(['code' => 0, 'msg' => '请输入快递单号']);
        }

        $order = Db::name('order')->find($id);
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ($order['pay_status'] != 1) {
            return json(['code' => 0, 'msg' => '买家未付款，不能发货']);
        }
        if ($order['order_status'] != 1) {
            return json(['code' => 0, 'msg' => '当前订单状态不能发货']);
        }

        Db::name('order')->where('id', $id)->update([
            'order_status' => 2,
            'ship_company' => $company,
            'ship_no'      => $shipNo,
            'ship_time'    => time(),
        ]);
        admin_log('订单发货：' . $order['order_no']);
        return json(['code' => 1, 'msg' => '发货成功']);
    }

    /**
     * 标记完成
     */
    public function finish()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');

        $order = Db::name('order')->find($id);
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ($order['order_status'] != 2) {
            return json(['code' => 0, 'msg' => '只有待收货订单可以完成']);
        }

        Db::name('order')->where('id', $id)->update([
            'order_status' => 3,
            'finish_time'  => time(),
        ]);
        admin_log('订单完成：' . $order['order_no']);
        return json(['code' => 1, 'msg' => '订单已完成']);
    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');

        $order = Db::name('order')->find($id);
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ($order['order_status'] == 3 || $order['order_status'] == 4) {
            return json(['code' => 0, 'msg' => '订单已结束，不能取消']);
        }

        Db::startTrans();
        try {
            if ($order['pay_status'] == 1) {
                // 已支付：退款给买家
                $buyer = Db::name('user')->where('id', $order['buyer_id'])->find();
                if ($buyer) {
                    $newBalance = round($buyer['balance'] + $order['price'], 2);
                    Db::name('user')->where('id', $buyer['id'])->update(['balance' => $newBalance]);
                    Db::name('balance_log')->insert([
                        'user_id'     => $buyer['id'],
                        'type'        => 'refund',
                        'amount'      => $order['price'],
                        'balance'     => $newBalance,
                        'remark'      => '订单取消退款：' . $order['order_no'],
                        'create_time' => time(),
                    ]);
                }
                // 退还卖家冻结的成交款（简单模式：无需冻结，直接回滚商品状态）
                // 商品回到待审核状态由卖家重新上架
                Db::name('goods')->where('id', $order['goods_id'])->update([
                    'status'      => 4,
                    'winner_id'   => 0,
                    'order_id'    => 0,
                    'final_price' => 0,
                ]);
            }
            Db::name('order')->where('id', $id)->update([
                'order_status' => 4,
                'pay_status'   => $order['pay_status'],
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        admin_log('取消订单：' . $order['order_no']);
        return json(['code' => 1, 'msg' => '订单已取消']);
    }
}

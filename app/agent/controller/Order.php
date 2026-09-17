<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 订单管理
 *
 * 数据范围：卖家是我的一级下级的订单（团队买家在别人店铺买的不在此范围）。
 * 所有按 id 的操作先过 assertMyOrder()。
 */
class Order extends Base
{
    /**
     * 订单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $keyword     = trim((string)$this->request->param('keyword', ''));
            $orderStatus = $this->request->param('order_status', '');
            $payStatus   = $this->request->param('pay_status', '');

            $query = $this->orderQuery();
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('o.order_no', "%{$keyword}%")
                        ->whereOr('o.goods_title', 'like', "%{$keyword}%")
                        ->whereOr('u.mobile', 'like', "%{$keyword}%")
                        ->whereOr('s.mobile', 'like', "%{$keyword}%");
                });
            }
            if ($orderStatus !== '') {
                $query->where('o.order_status', (int)$orderStatus);
            }
            if ($payStatus !== '') {
                $query->where('o.pay_status', (int)$payStatus);
            }

            $total = $query->count();
            $list  = $query->order('o.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/agent/order/index');
        return View::fetch();
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $order = $this->assertMyOrder($this->request->param('id', 0));
        $order['pay_time_text']    = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '-';
        $order['ship_time_text']   = $order['ship_time'] ? date('Y-m-d H:i:s', $order['ship_time']) : '-';
        $order['finish_time_text'] = $order['finish_time'] ? date('Y-m-d H:i:s', $order['finish_time']) : '-';

        View::assign([
            'order'       => $order,
            'menu_active' => '/agent/order/index',
        ]);
        return View::fetch();
    }

    /**
     * 代团队卖家发货
     */
    /**
     * 「完成支付」弹窗信息（仅团队卖家的订单）
     */
    public function payInfo()
    {
        $order = $this->assertMyOrder($this->request->param('id', 0));
        if ((int)$order['order_status'] !== 0 || (int)$order['pay_status'] !== 0) {
            return json(['code' => 0, 'msg' => '订单不是待付款状态']);
        }
        return json(['code' => 1, 'data' => pay_order_info($order)]);
    }

    /**
     * 完成支付：代买家用余额支付（仅团队卖家的订单；余额不足则失败，不垫付）
     */
    public function pay()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $order = $this->assertMyOrder($this->request->post('id', 0));
        $r = pay_order_for_buyer($order['id'], [
            'name'    => $this->request->post('ship_name', ''),
            'mobile'  => $this->request->post('ship_mobile', ''),
            'address' => $this->request->post('ship_address', ''),
        ], '代理代付');
        if (!$r['ok']) {
            return json(['code' => 0, 'msg' => $r['msg']]);
        }
        agent_log('完成支付（代买家余额支付）：订单 ' . $order['order_no'] . '，买家 ID ' . $order['buyer_id'] . '，成交价 ' . $order['price']);
        return json(['code' => 1, 'msg' => $r['msg']]);
    }

    public function ship()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $order   = $this->assertMyOrder($this->request->post('id', 0));
        $company = trim((string)$this->request->post('company', ''));
        $shipNo  = trim((string)$this->request->post('ship_no', ''));

        if ($company === '' || $shipNo === '') {
            return json(['code' => 0, 'msg' => '请填写快递公司和快递单号']);
        }
        if ((int)$order['pay_status'] !== 1) {
            return json(['code' => 0, 'msg' => '买家未付款，不能发货']);
        }
        if ((int)$order['order_status'] !== 1) {
            return json(['code' => 0, 'msg' => '当前订单状态不能发货']);
        }

        $n = Db::name('order')->where('id', $order['id'])->where('pay_status', 1)->where('order_status', 1)->update([
            'order_status' => 2,
            'ship_company' => mb_substr($company, 0, 50),
            'ship_no'      => mb_substr($shipNo, 0, 50),
            'ship_time'    => time(),
            'update_time'  => time(),
        ]);
        if ($n !== 1) {
            return json(['code' => 0, 'msg' => '订单状态已变化，请刷新']);
        }
        agent_log('订单发货：' . $order['order_no'] . '（' . $company . ' ' . $shipNo . '）');
        return json(['code' => 1, 'msg' => '发货成功']);
    }

    /**
     * 标记订单完成（待收货 → 已完成）
     */
    public function finish()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $order = $this->assertMyOrder($this->request->post('id', 0));
        if ((int)$order['order_status'] !== 2) {
            return json(['code' => 0, 'msg' => '只有待收货订单可以完成']);
        }
        Db::startTrans();
        try {
            $order = Db::name('order')->where('id', $order['id'])->lock(true)->find();
            $now   = time();
            if (!$order || Db::name('order')->where('id', $order['id'])->where('order_status', 2)->update([
                'order_status' => 3,
                'finish_time'  => $now,
                'update_time'  => $now,
            ]) !== 1) {
                throw new \RuntimeException('订单状态已变化，请刷新');
            }
            // 标记完成等同买家确认收货：成交款此时打给卖家
            pay_seller_income($order);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
        agent_log('订单完成：' . $order['order_no']);
        return json(['code' => 1, 'msg' => '订单已完成']);
    }

    // ------------------------------------------------------------------

    /**
     * 团队范围内的订单查询：卖家是我的下级
     */
    protected function orderQuery()
    {
        $ids = $this->memberIds() ?: [-1];
        return Db::name('order')->alias('o')
            ->leftJoin('user u', 'o.buyer_id = u.id')
            ->leftJoin('user s', 'o.seller_id = s.id')
            ->field('o.*, u.mobile as buyer_mobile, u.nickname as buyer_name, u.is_virtual as buyer_virtual, s.mobile as seller_mobile, s.nickname as seller_name')
            ->whereIn('o.seller_id', $ids);
    }

    /**
     * 归属校验：不是我团队的订单直接踢回列表
     */
    protected function assertMyOrder($id)
    {
        $id    = (int)$id;
        $order = $id > 0 ? $this->orderQuery()->where('o.id', $id)->find() : null;
        if (empty($order)) {
            if ($this->request->isAjax()) {
                throw new \think\exception\HttpResponseException(json(['code' => 0, 'msg' => '该订单不属于您的团队']));
            }
            throw new \think\exception\HttpResponseException(response('', 302, ['Location' => '/agent/order/index']));
        }
        return $order;
    }
}

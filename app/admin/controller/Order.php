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
                ->field('o.*, u.account as buyer_mobile, u.nickname as buyer_name, u.is_virtual as buyer_virtual, s.account as seller_mobile, s.nickname as seller_name');

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('o.order_no', "%{$keyword}%")
                        ->whereOr('o.goods_title', 'like', "%{$keyword}%")
                        ->whereOr('u.account', 'like', "%{$keyword}%")
                        ->whereOr('s.account', 'like', "%{$keyword}%");
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
            ->field('o.*, u.account as buyer_mobile, u.nickname as buyer_name, u.is_virtual as buyer_virtual, s.account as seller_mobile, s.nickname as seller_name')
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
    /**
     * 「完成支付」弹窗信息
     */
    public function payInfo()
    {
        $order = Db::name('order')->find((int)$this->request->param('id', 0));
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ((int)$order['order_status'] !== 0 || (int)$order['pay_status'] !== 0) {
            return json(['code' => 0, 'msg' => '订单不是待付款状态']);
        }
        return json(['code' => 1, 'data' => pay_order_info($order)]);
    }

    /**
     * 完成支付：代买家用余额支付待付款订单（余额不足则失败，不垫付）
     */
    public function pay()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id    = (int)$this->request->post('id', 0);
        $order = Db::name('order')->find($id);
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        $r = pay_order_for_buyer($id, [
            'name'    => $this->request->post('ship_name', ''),
            'mobile'  => $this->request->post('ship_mobile', ''),
            'address' => $this->request->post('ship_address', ''),
        ], '后台代付');
        if (!$r['ok']) {
            return json(['code' => 0, 'msg' => $r['msg']]);
        }
        admin_log('完成支付（代买家余额支付）：订单 ' . $order['order_no'] . '，买家 ID ' . $order['buyer_id'] . '，成交价 ' . $order['price']);
        return json(['code' => 1, 'msg' => $r['msg']]);
    }

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

        if (Db::name('order')->where('id', $id)->where('order_status', 1)->where('pay_status', 1)->update([
            'order_status' => 2,
            'ship_company' => mb_substr($company, 0, 50),
            'ship_no'      => mb_substr($shipNo, 0, 50),
            'ship_time'    => time(),
            'update_time'  => time(),
        ]) !== 1) {
            return json(['code' => 0, 'msg' => '订单状态已变化，请刷新']);
        }
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

        Db::startTrans();
        try {
            $order = Db::name('order')->where('id', $id)->lock(true)->find();
            if (!$order) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '订单不存在']);
            }
            if ((int)$order['order_status'] !== 2) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '只有待收货订单可以完成']);
            }
            $now = time();
            if (Db::name('order')->where('id', $id)->where('order_status', 2)->update([
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
        if (!function_exists('cancel_unpaid_order')) {
            require_once app()->getBasePath() . 'index' . DIRECTORY_SEPARATOR . 'common.php';
        }
        $order = Db::name('order')->find($id);
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if (in_array((int)$order['order_status'], [3, 4], true)) {
            return json(['code' => 0, 'msg' => '订单已结束，不能取消']);
        }
        if ((int)$order['order_status'] === 5) {
            return json(['code' => 0, 'msg' => '订单在售后中，请先在售后管理处理']);
        }

        // 未付款：与超时取消 / 买家主动取消同一套逻辑（按后台设置处理保证金，商品回到流拍）
        if ((int)$order['pay_status'] === 0) {
            $mode   = (string)get_setting('order_timeout_deposit', 'forfeit_platform');
            $result = cancel_unpaid_order($id, '平台取消订单', $mode);
            if ($result === false) {
                return json(['code' => 0, 'msg' => '订单状态已变化，请刷新']);
            }
            admin_log('取消未付款订单：' . $order['order_no'] . '（' . $result . '）');
            return json(['code' => 1, 'msg' => '订单已取消（' . $result . '）']);
        }

        // 已付款（待发货 / 待收货）：全额退回买家，商品下架。
        // 成交款只在买家确认收货后才打给卖家（income_paid=1），这两个阶段款项通常还在平台，无需扣回；
        // 只有历史上已入账的订单才扣回卖家收入。
        $now = time();
        Db::startTrans();
        try {
            $order = Db::name('order')->where('id', $id)->lock(true)->find();
            if (!$order || (int)$order['pay_status'] !== 1 || !in_array((int)$order['order_status'], [1, 2], true)) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '订单状态已变化，请刷新']);
            }
            $income = (int)$order['income_paid'] === 1 ? round((float)$order['seller_income'], 2) : 0;
            if ($income > 0) {
                $seller = Db::name('user')->where('id', $order['seller_id'])->lock(true)->find();
                if (!$seller) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '卖家不存在']);
                }
                if ((float)$seller['balance'] < $income) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '卖家余额不足以扣回成交收入 ' . number_format($income, 2) . ' 元，无法取消']);
                }
                $sellerBalance = round($seller['balance'] - $income, 2);
                Db::name('user')->where('id', $seller['id'])->update(['balance' => $sellerBalance, 'update_time' => $now]);
                Db::name('balance_log')->insert([
                    'user_id'     => $seller['id'],
                    'type'        => 'refund',
                    'amount'      => -$income,
                    'balance'     => $sellerBalance,
                    'remark'      => '订单取消扣回成交收入：' . $order['order_no'],
                    'create_time' => $now,
                ]);
            }
            $buyer = Db::name('user')->where('id', $order['buyer_id'])->lock(true)->find();
            if ($buyer) {
                $newBalance = round($buyer['balance'] + $order['price'], 2);
                Db::name('user')->where('id', $buyer['id'])->update(['balance' => $newBalance, 'update_time' => $now]);
                Db::name('balance_log')->insert([
                    'user_id'     => $buyer['id'],
                    'type'        => 'refund',
                    'amount'      => $order['price'],
                    'balance'     => $newBalance,
                    'remark'      => '订单取消退款：' . $order['order_no'],
                    'create_time' => $now,
                ]);
            }
            if (Db::name('order')->where('id', $id)->where('pay_status', 1)->whereIn('order_status', [1, 2])->update([
                'order_status' => 4,
                'pay_status'   => 2,
                'remark'       => '平台取消订单，已全额退款',
                'update_time'  => $now,
            ]) !== 1) {
                throw new \RuntimeException('订单状态已变化');
            }
            Db::name('goods')->where('id', $order['goods_id'])->update([
                'status'      => 4,
                'winner_id'   => 0,
                'order_id'    => 0,
                'final_price' => 0,
                'update_time' => $now,
            ]);
            Db::name('sys_message')->insertAll([
                ['user_id' => $order['buyer_id'], 'admin_id' => (int)($this->admin['id'] ?? 0), 'title' => '订单取消通知',
                 'content' => '您的订单 ' . $order['order_no'] . '（' . $order['goods_title'] . '）已由平台取消，货款 ¥' . number_format((float)$order['price'], 2) . ' 已退回您的可用余额。',
                 'is_read' => 0, 'create_time' => $now],
                ['user_id' => $order['seller_id'], 'admin_id' => (int)($this->admin['id'] ?? 0), 'title' => '订单取消通知',
                 'content' => '订单 ' . $order['order_no'] . '（' . $order['goods_title'] . '）已由平台取消，' . ($income > 0 ? '该订单的成交收入 ¥' . number_format($income, 2) . ' 已从您的余额扣回，' : '成交款尚未入账、无需扣回，') . '商品已下架。',
                 'is_read' => 0, 'create_time' => $now],
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        admin_log('取消已付款订单：' . $order['order_no'] . '，退买家 ' . number_format((float)$order['price'], 2) . ($income > 0 ? '，扣回卖家 ' . number_format($income, 2) : '，卖家未入账无需扣回'));
        return json(['code' => 1, 'msg' => '订单已取消，货款已退回买家' . ($income > 0 ? '，卖家收入已扣回' : '')]);
    }
}

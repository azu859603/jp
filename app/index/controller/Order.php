<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

class Order extends Base
{
    /**
     * 我的订单列表（买家）
     */
    public function list()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $orderStatus = $this->request->param('order_status', '');

        $query = Db::name('order')->alias('o')
            ->leftJoin('goods g', 'o.goods_id = g.id')
            ->leftJoin('after_sale a', 'a.order_id = o.id')
            ->field('o.*, g.cover as goods_cover2, IF(a.id IS NULL, 0, 1) as has_after_sale')
            ->where('o.buyer_id', $id);
        if ($orderStatus !== '') {
            $query->where('o.order_status', (int)$orderStatus);
        }
        $total = $query->count();
        $list = $query->order('o.id', 'desc')->page($page, $limit)->select()->toArray();
        // 状态名
        $statusMap = [0 => '待付款', 1 => '待发货', 2 => '待收货', 3 => '已完成', 4 => '已取消', 5 => '售后中'];
        foreach ($list as &$o) {
            $o['status_name'] = $statusMap[$o['order_status']] ?? '未知';
        }
        unset($o);

        View::assign([
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'order_status'=> $orderStatus,
            'page_title'  => '我的订单',
            'center_tab'  => 'orders',
            'tab_active'  => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 订单支付页（余额支付）
     */
    public function pay()
    {
        $this->checkLogin();
        $orderId = (int)$this->request->param('id', 0);

        if ($this->request->isPost()) {
            $order = Db::name('order')->where('id', $orderId)->where('buyer_id', $this->user['id'])->lock(true)->find();
            if (!$order) {
                return json(['code' => 0, 'msg' => '订单不存在']);
            }
            if ($order['order_status'] != 0 || $order['pay_status'] != 0) {
                return json(['code' => 0, 'msg' => '订单状态不正确，无需支付']);
            }

            // 收货地址
            $addressId = (int)$this->request->post('address_id', 0);
            $shipName = trim($this->request->post('ship_name', ''));
            $shipMobile = trim($this->request->post('ship_mobile', ''));
            $shipAddress = trim($this->request->post('ship_address', ''));

            if ($addressId > 0) {
                $addr = Db::name('user_address')->where('id', $addressId)->where('user_id', $this->user['id'])->find();
                if ($addr) {
                    $shipName = $addr['name'];
                    $shipMobile = $addr['mobile'];
                    $shipAddress = trim($addr['province'] . ' ' . $addr['city'] . ' ' . $addr['district'] . ' ' . $addr['address']);
                }
            }
            if ($shipName === '' || $shipMobile === '' || $shipAddress === '') {
                return json(['code' => 0, 'msg' => '请填写或选择收货地址']);
            }

            // 应付 = 成交价 - 保证金（已冻结）
            $payAmount = round($order['price'] - $order['deposit'], 2);
            $user = Db::name('user')->where('id', $this->user['id'])->lock(true)->find();
            $isVirtualBuyer = (int)$user['is_virtual'] === 1;
            // 虚拟会员：无真实资金变动，跳过余额校验与扣款，订单直接支付成功
            $freezeDeduct = 0;
            $balanceDeduct = 0;
            if (!$isVirtualBuyer) {
                // 冻结余额不足以抵扣保证金时（历史订单/拍卖期间保证金变动），差额从可用余额补扣
                $freezeDeduct = min($order['deposit'], $user['freeze_balance']);
                $balanceDeduct = round($payAmount + ($order['deposit'] - $freezeDeduct), 2);
                if ($user['balance'] < $balanceDeduct) {
                    return json(['code' => 0, 'msg' => '余额不足，还需支付 ¥' . number_format($balanceDeduct, 2) . '，请先充值']);
                }
            }

            $now = time();
            Db::startTrans();
            try {
                if (!$isVirtualBuyer) {
                    $newBalance = round($user['balance'] - $balanceDeduct, 2);
                    $newFreeze = round($user['freeze_balance'] - $freezeDeduct, 2);
                    Db::name('user')->where('id', $user['id'])->update([
                        'balance'        => $newBalance,
                        'freeze_balance' => $newFreeze,
                        'update_time'    => $now,
                    ]);

                    // 买家流水（仅记录实际扣款部分）
                    if ($payAmount > 0) {
                        $this->addBalanceLog($user['id'], 'pay', -$payAmount, $newBalance, '拍卖订单支付：' . $order['order_no']);
                    }
                }

                // 订单更新
                Db::name('order')->where('id', $orderId)->update([
                    'pay_status'   => 1,
                    'pay_time'     => $now,
                    'order_status' => 1,
                    'ship_name'    => $shipName,
                    'ship_mobile'  => $shipMobile,
                    'ship_address' => $shipAddress,
                    'update_time'  => $now,
                ]);

                // 买家累计
                Db::name('user')->where('id', $user['id'])->update([
                    'total_buy'   => Db::raw('total_buy+1'),
                ]);

                // 卖家入账（成交价 - 佣金）
                $seller = Db::name('user')->where('id', $order['seller_id'])->lock(true)->find();
                if ($seller) {
                    $sellerBalance = round($seller['balance'] + $order['seller_income'], 2);
                    Db::name('user')->where('id', $seller['id'])->update([
                        'balance'     => $sellerBalance,
                        'total_sell'  => Db::raw('total_sell+1'),
                        'update_time' => $now,
                    ]);
                    $this->addBalanceLog($seller['id'], 'income', $order['seller_income'], $sellerBalance, '拍卖成交收入：' . $order['order_no'] . '（平台佣金 ¥' . $order['commission'] . '）');
                }

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '支付失败：' . $e->getMessage()]);
            }

            return json(['code' => 1, 'msg' => '支付成功', 'url' => '/order/list']);
        }

        // 支付页
        $order = Db::name('order')->alias('o')
            ->leftJoin('goods g', 'o.goods_id = g.id')
            ->field('o.*, g.cover as goods_cover2, g.images')
            ->where('o.id', $orderId)
            ->where('o.buyer_id', $this->user['id'])
            ->find();
        if (!$order) {
            return $this->error('订单不存在', '/order/list');
        }
        if ($order['order_status'] != 0 || $order['pay_status'] != 0) {
            return $this->error('该订单已处理', '/order/list');
        }

        $payAmount = round($order['price'] - $order['deposit'], 2);
        $addresses = Db::name('user_address')->where('user_id', $this->user['id'])->order('is_default', 'desc')->order('id', 'desc')->select()->toArray();
        // 默认地址（is_default 优先，否则取最新）
        $address = !empty($addresses) ? $addresses[0] : null;

        View::assign([
            'order'      => $order,
            'pay_amount' => $payAmount,
            'addresses'  => $addresses,
            'address'    => $address,
            'page_title' => '订单支付',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 取消订单（未支付）
     */
    public function cancel()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $orderId = (int)$this->request->post('id', 0);
        $order = Db::name('order')->where('id', $orderId)->where('buyer_id', $this->user['id'])->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ($order['order_status'] != 0) {
            return json(['code' => 0, 'msg' => '订单状态不正确']);
        }

        Db::name('order')->where('id', $orderId)->update([
            'order_status' => 4,
            'update_time'  => time(),
        ]);
        return json(['code' => 1, 'msg' => '订单已取消（已缴纳的保证金不予退还）']);
    }

    /**
     * 确认收货
     */
    public function confirm()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $orderId = (int)$this->request->post('id', 0);
        $order = Db::name('order')->where('id', $orderId)->where('buyer_id', $this->user['id'])->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ($order['order_status'] != 2) {
            return json(['code' => 0, 'msg' => '订单状态不正确']);
        }

        Db::name('order')->where('id', $orderId)->update([
            'order_status' => 3,
            'finish_time'  => time(),
            'update_time'  => time(),
        ]);
        return json(['code' => 1, 'msg' => '已确认收货，交易完成']);
    }

    /**
     * 申请售后（仅已完成订单）
     */
    public function afterSaleApply()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $orderId = (int)$this->request->post('id', 0);
        $reason = trim($this->request->post('reason', ''));
        if ($orderId <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if (mb_strlen($reason) < 5) {
            return json(['code' => 0, 'msg' => '请填写售后理由（至少 5 个字）']);
        }
        if (mb_strlen($reason) > 500) {
            return json(['code' => 0, 'msg' => '售后理由不能超过 500 字']);
        }

        $order = Db::name('order')->where('id', $orderId)->where('buyer_id', $this->user['id'])->lock(true)->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        if ($order['order_status'] != 3) {
            return json(['code' => 0, 'msg' => '只有已完成的订单才能申请售后']);
        }
        $exists = Db::name('after_sale')->where('order_id', $orderId)->find();
        if ($exists) {
            return json(['code' => 0, 'msg' => '该订单已申请过售后，请勿重复申请']);
        }

        $now = time();
        Db::name('after_sale')->insert([
            'order_id'    => $orderId,
            'order_no'    => $order['order_no'],
            'user_id'     => $order['buyer_id'],
            'seller_id'   => $order['seller_id'],
            'goods_id'    => $order['goods_id'],
            'goods_title' => $order['goods_title'],
            'price'       => $order['price'],
            'reason'      => $reason,
            'status'      => 0,
            'create_time' => $now,
        ]);
        Db::name('order')->where('id', $orderId)->update([
            'order_status' => 5,
            'update_time'  => $now,
        ]);
        return json(['code' => 1, 'msg' => '售后申请已提交，请等待平台处理']);
    }
}

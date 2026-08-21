<?php
// index应用公共函数
use think\facade\Db;

/**
 * 读取系统设置（内存缓存）
 */
function site_settings()
{
    static $settings = null;
    if ($settings === null) {
        $list = Db::name('setting')->select()->toArray();
        $settings = [];
        foreach ($list as $row) {
            $settings[$row['name']] = $row['value'];
        }
    }
    return $settings;
}

/**
 * 读取单个设置
 */
function get_setting($name, $default = '')
{
    $settings = site_settings();
    return isset($settings[$name]) && $settings[$name] !== '' ? $settings[$name] : $default;
}

/**
 * 计算商品佣金比例
 */
function goods_commission_rate($goods)
{
    if (!empty($goods['commission_rate']) && $goods['commission_rate'] > 0) {
        return (float)$goods['commission_rate'];
    }
    return (float)get_setting('commission_rate', 10);
}

/**
 * 竞拍结束结算（成交/流拍/保证金处理）
 * @param int $goodsId
 */
function settle_goods($goodsId)
{
    $goods = Db::name('goods')->where('id', $goodsId)->lock(true)->find();
    if (!$goods || $goods['status'] != 1 || $goods['end_time'] > time()) {
        return false;
    }

    $bids = Db::name('bid_record')
        ->where('goods_id', $goodsId)
        ->where('status', 0)
        ->order('price', 'desc')
        ->order('id', 'asc')
        ->select()
        ->toArray();

    Db::startTrans();
    try {
        // 无出价 或 最高价低于保留价 → 流拍
        $top = $bids[0] ?? null;
        $fail = !$top || ($goods['reserve_price'] > 0 && $top['price'] < $goods['reserve_price']);

        if ($fail) {
            foreach ($bids as $b) {
                Db::name('bid_record')->where('id', $b['id'])->update(['status' => 2]);
                if ($b['deposit'] > 0) {
                    refund_deposit($b['user_id'], $b['deposit'], '拍卖流拍，保证金退回（' . $goods['title'] . '）');
                }
            }
            Db::name('goods')->where('id', $goodsId)->update(['status' => 3, 'update_time' => time()]);
            Db::commit();
            return '流拍';
        }

        // 成交
        $rate = goods_commission_rate($goods);
        $commission = round($top['price'] * $rate / 100, 2);
        $sellerIncome = round($top['price'] - $commission, 2);

        // 出价记录：其余流拍，得标者成交
        Db::name('bid_record')->where('goods_id', $goodsId)->where('status', 0)->update(['status' => 2]);
        Db::name('bid_record')->where('id', $top['id'])->update(['status' => 1, 'is_winner' => 1]);

        // 未得标者保证金退回
        foreach ($bids as $b) {
            if ($b['id'] != $top['id'] && $b['deposit'] > 0) {
                refund_deposit($b['user_id'], $b['deposit'], '未拍中，保证金退回（' . $goods['title'] . '）');
            }
        }

        // 得标者实际冻结的保证金（多次出价只有首条带 deposit，取最大值）
        $topDeposit = (float)Db::name('bid_record')
            ->where('goods_id', $goodsId)
            ->where('user_id', $top['user_id'])
            ->where('deposit', '>', 0)
            ->max('deposit');

        // 生成订单（待付款）
        $orderId = Db::name('order')->insertGetId([
            'order_no'        => make_order_no('AU'),
            'goods_id'        => $goodsId,
            'goods_title'     => $goods['title'],
            'goods_cover'     => $goods['cover'],
            'seller_id'       => $goods['seller_id'],
            'buyer_id'        => $top['user_id'],
            'price'           => $top['price'],
            'commission_rate' => $rate,
            'commission'      => $commission,
            'seller_income'   => $sellerIncome,
            'deposit'         => $topDeposit, // 用得标者实际冻结的保证金，而非商品当前配置值（拍卖期间修改保证金不会造成不一致）
            'pay_status'      => 0,
            'order_status'    => 0,
            'create_time'     => time(),
            'update_time'     => time(),
        ]);

        Db::name('goods')->where('id', $goodsId)->update([
            'status'      => 2,
            'final_price' => $top['price'],
            'winner_id'   => $top['user_id'],
            'order_id'    => $orderId,
            'update_time' => time(),
        ]);

        Db::commit();
        return '成交';
    } catch (\Throwable $e) {
        Db::rollback();
        return false;
    }
}

/**
 * 扫描并结算所有到期商品
 */
function settle_expired_goods()
{
    $now = time();
    $list = Db::name('goods')->where('status', 1)->where('end_time', '<=', $now)->column('id');
    foreach ($list as $id) {
        settle_goods($id);
    }
}

/**
 * 退回保证金
 */
function refund_deposit($userId, $amount, $remark)
{
    if ($amount <= 0) {
        return;
    }
    $user = Db::name('user')->where('id', $userId)->lock(true)->find();
    if (!$user) {
        return;
    }
    $newBalance = round($user['balance'] + $amount, 2);
    $newFreeze = round(max($user['freeze_balance'] - $amount, 0), 2);
    Db::name('user')->where('id', $userId)->update([
        'balance'        => $newBalance,
        'freeze_balance' => $newFreeze,
        'update_time'    => time(),
    ]);
    Db::name('balance_log')->insert([
        'user_id'     => $userId,
        'type'        => 'refund',
        'amount'      => $amount,
        'balance'     => $newBalance,
        'remark'      => $remark,
        'create_time' => time(),
    ]);
}

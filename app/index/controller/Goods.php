<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

class Goods extends Base
{
    /**
     * 商品详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $goods = Db::name('goods')->alias('g')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->leftJoin('category c', 'g.category_id = c.id')
            ->field('g.*, u.nickname as seller_name, u.shop_name as seller_shop, u.avatar as seller_avatar, u.seller_intro, u.deposit as seller_deposit, u.shop_score, u.fans_count, c.name as category_name')
            ->where('g.id', $id)
            ->find();

        if (!$goods || !in_array($goods['status'], [1, 2, 3])) {
            return $this->error(lang('商品不存在或已下架'), '/');
        }

        // 浏览量 +1（拍卖中）
        if ($goods['status'] == 1) {
            Db::name('goods')->where('id', $id)->inc('view_count')->update();
        }

        // 图片（兼容数组与旧版逗号字符串两种存储）
        $images = json_decode($goods['images'], true);
        if (is_string($images)) {
            $images = $images === '' ? [] : explode(',', $images);
        }
        $images = is_array($images) ? $images : [];
        if (!empty($goods['cover'])) {
            array_unshift($images, $goods['cover']);
        }
        $images = array_values(array_unique($images));

        // 当前价
        $topBid = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.*, u.nickname, u.mobile')
            ->where('b.goods_id', $id)
            ->where('b.status', 0)
            ->order('b.price', 'desc')
            ->order('b.id', 'asc')
            ->find();
        $currentPrice = $topBid ? (float)$topBid['price'] : (float)$goods['start_price'];

        // 出价记录（全部，含已结束）
        $bids = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.*, u.nickname, u.mobile')
            ->where('b.goods_id', $id)
            ->order('b.price', 'desc')
            ->order('b.id', 'asc')
            ->limit(50)
            ->select()
            ->toArray();
        foreach ($bids as &$b) {
            $b['display_name'] = !empty($b['nickname']) ? $b['nickname'] : (!empty($b['mobile']) ? substr($b['mobile'], 0, 3) . '****' . substr($b['mobile'], -4) : lang('拍友') . $b['user_id']);
        }
        unset($b);

        // 我的出价（登录时）
        $myBid = null;
        if (!empty($this->user)) {
            $myBid = Db::name('bid_record')
                ->where('goods_id', $id)
                ->where('user_id', $this->user['id'])
                ->where('status', 0)
                ->max('price');
        }

        // 是否已交保证金
        $paidDeposit = 0;
        if (!empty($this->user)) {
            $d = Db::name('bid_record')
                ->where('goods_id', $id)
                ->where('user_id', $this->user['id'])
                ->where('deposit', '>', 0)
                ->max('deposit');
            $paidDeposit = (float)$d;
        }

        // 成交订单
        $order = null;
        if ($goods['status'] == 2) {
            $order = Db::name('order')->where('goods_id', $id)->find();
        }

        // 浏览足迹（登录用户）
        $isFaved = 0;
        $isFollowed = 0;
        if (!empty($this->user)) {
            $uid = $this->user['id'];
            Db::name('browse_history')
                ->replace()
                ->insert(['user_id' => $uid, 'goods_id' => $id, 'create_time' => time()]);
            $isFaved = (int)Db::name('goods_favorite')->where('user_id', $uid)->where('goods_id', $id)->count();
            $isFollowed = (int)Db::name('seller_follow')->where('user_id', $uid)->where('seller_id', $goods['seller_id'])->count();
        }

        View::assign([
            'goods'         => $goods,
            'images'        => $images,
            'bids'          => $bids,
            'top_bid'       => $topBid,
            'current_price' => $currentPrice,
            'min_bid'       => round($currentPrice + (float)$goods['raise_price'], 2),
            'my_bid'        => $myBid,
            'paid_deposit'  => $paidDeposit,
            'order'         => $order,
            'now'           => time(),
            'is_faved'      => $isFaved,
            'is_followed'   => $isFollowed,
            'page_title'    => $goods['title'],
            'page_class'    => 'detail-page',
            'tab_active'    => 'index',
        ]);
        return View::fetch();
    }

    /**
     * 收藏/取消收藏商品
     */
    public function toggleFavorite()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        $goodsId = (int)$this->request->post('goods_id', 0);
        $goods = Db::name('goods')->where('id', $goodsId)->find();
        if (!$goods) {
            return json(['code' => 0, 'msg' => lang('商品不存在')]);
        }
        $uid = $this->user['id'];
        $exists = Db::name('goods_favorite')->where('user_id', $uid)->where('goods_id', $goodsId)->find();
        if ($exists) {
            Db::name('goods_favorite')->where('id', $exists['id'])->delete();
            return json(['code' => 1, 'msg' => lang('已取消收藏'), 'faved' => 0]);
        }
        Db::name('goods_favorite')->insert(['user_id' => $uid, 'goods_id' => $goodsId, 'create_time' => time()]);
        return json(['code' => 1, 'msg' => lang('收藏成功'), 'faved' => 1]);
    }

    /**
     * 关注/取消关注店铺
     */
    public function toggleFollow()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        $sellerId = (int)$this->request->post('seller_id', 0);
        if ($sellerId <= 0) {
            return json(['code' => 0, 'msg' => lang('参数错误')]);
        }
        $uid = $this->user['id'];
        if ($uid == $sellerId) {
            return json(['code' => 0, 'msg' => lang('不能关注自己')]);
        }
        $exists = Db::name('seller_follow')->where('user_id', $uid)->where('seller_id', $sellerId)->find();
        if ($exists) {
            Db::name('seller_follow')->where('id', $exists['id'])->delete();
            return json(['code' => 1, 'msg' => lang('已取消关注'), 'followed' => 0]);
        }
        Db::name('seller_follow')->insert(['user_id' => $uid, 'seller_id' => $sellerId, 'create_time' => time()]);
        return json(['code' => 1, 'msg' => lang('关注成功'), 'followed' => 1]);
    }

    /**
     * 出价
     */
    public function bid()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }

        $goodsId = (int)$this->request->post('goods_id', 0);
        $price = round((float)$this->request->post('price', 0), 2);

        $goods = Db::name('goods')->where('id', $goodsId)->lock(true)->find();
        if (!$goods) {
            return json(['code' => 0, 'msg' => lang('商品不存在')]);
        }
        if ($goods['status'] != 1) {
            return json(['code' => 0, 'msg' => lang('该商品不在拍卖中')]);
        }
        $now = time();
        if ($now < $goods['start_time']) {
            return json(['code' => 0, 'msg' => lang('拍卖尚未开始')]);
        }
        if ($now >= $goods['end_time']) {
            return json(['code' => 0, 'msg' => lang('拍卖已结束')]);
        }
        if ($goods['seller_id'] == $this->user['id']) {
            return json(['code' => 0, 'msg' => lang('不能给自己的商品出价')]);
        }

        // 当前最高价
        $topPrice = (float)Db::name('bid_record')
            ->where('goods_id', $goodsId)
            ->where('status', 0)
            ->max('price');
        $basePrice = max($topPrice, (float)$goods['start_price']);

        // 加价幅度（防止为 0）
        $raise = (float)$goods['raise_price'];
        if ($raise <= 0) {
            $raise = 1;
        }
        $minPrice = round($basePrice + $raise, 2);

        if ($price < $minPrice) {
            return json(['code' => 0, 'msg' => lang('出价不能低于 ') . number_format($minPrice, 2) . lang(' 元')]);
        }
        // 阶梯校验：出价必须是 当前价 + 加价幅度的整数倍，禁止乱加价
        $steps = ($price - $basePrice) / $raise;
        if (abs($steps - round($steps)) > 0.0001) {
            return json(['code' => 0, 'msg' => lang('出价必须按加价幅度 ') . number_format($raise, 2) . lang(' 元递增（如 ') . number_format($minPrice, 2) . lang('、') . number_format($minPrice + $raise, 2) . lang(' 元）')]);
        }

        $user = Db::name('user')->where('id', $this->user['id'])->lock(true)->find();

        // 是否已交过保证金
        $paidDeposit = (float)Db::name('bid_record')
            ->where('goods_id', $goodsId)
            ->where('user_id', $user['id'])
            ->where('deposit', '>', 0)
            ->max('deposit');

        Db::startTrans();
        try {
            // 出价前当前最高出价者（若被本次出价超过，则向其发出局站内信）
            $topBid = Db::name('bid_record')
                ->where('goods_id', $goodsId)
                ->where('status', 0)
                ->order('price', 'desc')
                ->order('id', 'asc')
                ->find();

            // 首次出价冻结保证金（虚拟会员免保证金，余额为永存金额不产生资金变动）
            $freeze = 0.00;
            if ($paidDeposit <= 0 && (float)$goods['deposit'] > 0 && (int)$user['is_virtual'] !== 1) {
                $deposit = (float)$goods['deposit'];
                if ($user['balance'] < $deposit) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => lang('可用余额不足，无法缴纳保证金（需 ') . number_format($deposit, 2) . lang('元），请先充值')]);
                }
                $newBalance = round($user['balance'] - $deposit, 2);
                $newFreeze = round($user['freeze_balance'] + $deposit, 2);
                Db::name('user')->where('id', $user['id'])->update([
                    'balance'        => $newBalance,
                    'freeze_balance' => $newFreeze,
                    'update_time'    => $now,
                ]);
                $this->addBalanceLog($user['id'], 'deposit', -$deposit, $newBalance, '拍卖保证金（' . $goods['title'] . '）');
                $freeze = $deposit;
            }

            Db::name('bid_record')->insert([
                'goods_id'    => $goodsId,
                'user_id'     => $user['id'],
                'price'       => $price,
                'deposit'     => $freeze,
                'status'      => 0,
                'is_winner'   => 0,
                'create_time' => $now,
            ]);

            // 出价次数
            Db::name('goods')->where('id', $goodsId)->update([
                'bid_count'   => Db::raw('bid_count+1'),
                'update_time' => $now,
            ]);

            // 出局通知：原最高出价者已被本次出价超过
            if ($topBid && (int)$topBid['user_id'] !== (int)$user['id']) {
                Db::name('sys_message')->insert([
                    'user_id'     => $topBid['user_id'],
                    'admin_id'    => 0,
                    'title'       => '竞拍出局通知',
                    'content'     => '您出价竞拍的「' . $goods['title'] . '」已出局：您的出价 ¥' . number_format((float)$topBid['price'], 2) . ' 于 ' . date('Y-m-d H:i:s', $now) . ' 被 ¥' . number_format($price, 2) . ' 超过，当前最高价 ¥' . number_format($price, 2) . '。如需继续竞拍，请再次出价。',
                    'is_read'     => 0,
                    'create_time' => $now,
                ]);
            }

            // 延时拍卖：结束前 N 秒出价，自动延长
            $delay = $goods['delay_seconds'] > 0 ? $goods['delay_seconds'] : (int)get_setting('auction_delay', 0);
            if ($delay > 0 && $goods['end_time'] - $now <= $delay) {
                Db::name('goods')->where('id', $goodsId)->update([
                    'end_time' => $now + $delay,
                ]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => lang('出价失败：') . $e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => lang('出价成功')]);
    }

    /**
     * 出价记录（AJAX 刷新）
     */
    public function bids()
    {
        $goodsId = (int)$this->request->param('goods_id', 0);
        $list = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.*, u.nickname, u.mobile')
            ->where('b.goods_id', $goodsId)
            ->order('b.price', 'desc')
            ->order('b.id', 'asc')
            ->limit(50)
            ->select()
            ->toArray();
        foreach ($list as &$b) {
            $b['display_name'] = !empty($b['nickname']) ? $b['nickname'] : (!empty($b['mobile']) ? substr($b['mobile'], 0, 3) . '****' . substr($b['mobile'], -4) : lang('拍友') . $b['user_id']);
        }
        unset($b);

        // 当前价
        $top = $list[0]['price'] ?? 0;
        $goods = Db::name('goods')->find($goodsId);

        return json([
            'code' => 0,
            'data' => $list,
            'top'  => $top ?: ($goods['start_price'] ?? 0),
            'bid_count' => $goods['bid_count'] ?? 0,
        ]);
    }
}

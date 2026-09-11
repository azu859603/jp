<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 竞拍管理 / 出价记录
 *
 * 数据范围：拍品的卖家 ∈ 我的下级，或出价人 ∈ 我的下级。
 * 手动添加出价：拍品必须是我下级卖家发布的（assertMyGoods），买家必须是我的下级（assertMyMember），
 * 校验与写入规则和主后台「竞拍管理 › 添加出价」完全一致。
 */
class Bid extends Base
{
    /**
     * 出价记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $keyword = trim($this->request->param('keyword', ''));
            $goodsId = $this->request->param('goods_id', '');
            $ids     = $this->memberIds();

            $query = Db::name('bid_record')->alias('b')
                ->leftJoin('goods g', 'b.goods_id = g.id')
                ->leftJoin('user u', 'b.user_id = u.id')
                ->field('b.*, g.title as goods_title, g.seller_id, u.mobile, u.nickname')
                // 团队范围：拍品卖家是我的下级，或出价人是我的下级
                ->where(function ($q) use ($ids) {
                    $q->whereIn('g.seller_id', $ids)->whereOr('b.user_id', 'in', $ids);
                });

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('g.title', "%{$keyword}%")
                        ->whereOr('u.mobile', 'like', "%{$keyword}%")
                        ->whereOr('u.nickname', 'like', "%{$keyword}%");
                });
            }
            if ($goodsId !== '') {
                $query->where('b.goods_id', (int)$goodsId);
            }

            $total = $query->count();
            $list  = $query->order('b.id', 'desc')->page($page, $limit)->select()->toArray();
            $set   = array_flip($ids);
            foreach ($list as &$b) {
                $b['seller_in_team'] = isset($set[(int)$b['seller_id']]) ? 1 : 0;
                $b['buyer_in_team']  = isset($set[(int)$b['user_id']]) ? 1 : 0;
            }
            unset($b);
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/agent/bid/index');
        return View::fetch();
    }

    /**
     * 添加出价：搜索我下级卖家发布的、拍卖中的拍品（按拍品 ID 精确或标题模糊）
     */
    public function searchGoods()
    {
        $kw  = trim((string)$this->request->param('kw', ''));
        $now = time();
        $query = $this->goodsQuery()->where('status', 1)->where('end_time', '>', $now);
        if ($kw !== '') {
            if (ctype_digit($kw)) {
                $query->where(function ($q) use ($kw) {
                    $q->where('id', (int)$kw)->whereOr('title', 'like', "%{$kw}%");
                });
            } else {
                $query->where('title', 'like', "%{$kw}%");
            }
        }
        $list = $query->field('id,title,cover,start_price,raise_price,end_time,bid_count,seller_id')->order('id', 'desc')->limit(20)->select()->toArray();
        $ids  = array_column($list, 'id');
        $tops = [];
        if ($ids) {
            $rows = Db::name('bid_record')->whereIn('goods_id', $ids)->where('status', 0)->field('goods_id, MAX(price) AS top')->group('goods_id')->select()->toArray();
            foreach ($rows as $r) {
                $tops[(int)$r['goods_id']] = (float)$r['top'];
            }
        }
        $sellers = $ids ? Db::name('user')->whereIn('id', array_unique(array_column($list, 'seller_id')))->column('shop_name,nickname', 'id') : [];
        foreach ($list as &$g) {
            $g['top_price']   = max($tops[(int)$g['id']] ?? 0, (float)$g['start_price']);
            $g['raise_price'] = (float)$g['raise_price'] > 0 ? (float)$g['raise_price'] : 1;
            $g['end_text']    = date('m-d H:i', (int)$g['end_time']);
            $s = $sellers[(int)$g['seller_id']] ?? [];
            $g['seller_name'] = !empty($s['shop_name']) ? $s['shop_name'] : ($s['nickname'] ?? '');
        }
        unset($g);
        return json(['code' => 1, 'data' => $list]);
    }

    /**
     * 添加出价：搜索我的下级会员（手机号 / 昵称模糊，或会员 ID 精确）
     */
    public function searchUser()
    {
        $kw = trim((string)$this->request->param('kw', ''));
        $query = $this->memberQuery()->where('status', 1);
        if ($kw !== '') {
            $query->where(function ($q) use ($kw) {
                $q->where('mobile', 'like', "%{$kw}%")->whereOr('nickname', 'like', "%{$kw}%");
                if (ctype_digit($kw)) {
                    $q->whereOr('id', (int)$kw);
                }
            });
        }
        $list = $query->field('id,mobile,nickname,is_virtual,balance')->order('id', 'desc')->limit(20)->select()->toArray();
        return json(['code' => 1, 'data' => $list]);
    }

    /**
     * 手动添加出价记录（拍品 ∈ 我下级卖家，买家 ∈ 我的下级）
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $goodsId = (int)$this->request->post('goods_id', 0);
        $userId  = (int)$this->request->post('user_id', 0);
        $price   = round((float)$this->request->post('price', 0), 2);

        if ($goodsId <= 0 || $userId <= 0 || $price <= 0) {
            return json(['code' => 0, 'msg' => '请选择拍品、买家并填写出价金额']);
        }
        // 归属校验：不属于团队直接拒绝
        $this->assertMyGoods($goodsId);
        $buyer = $this->assertMyMember($userId);
        if ($buyer['status'] != 1) {
            return json(['code' => 0, 'msg' => '买家已被禁用']);
        }

        Db::startTrans();
        try {
            $goods = Db::name('goods')->where('id', $goodsId)->lock(true)->find();
            if (!$goods || $goods['status'] != 1) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '该拍品不在拍卖中']);
            }
            $now = time();
            if ($now < $goods['start_time']) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '拍卖尚未开始']);
            }
            if ($now >= $goods['end_time']) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '拍卖已结束']);
            }
            if ((int)$goods['seller_id'] === $userId) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '卖家不能给自己的拍品出价']);
            }

            // 当前最高价 + 阶梯校验（与前台一致）
            $topBid = Db::name('bid_record')
                ->where('goods_id', $goodsId)
                ->where('status', 0)
                ->order('price', 'desc')
                ->order('id', 'asc')
                ->find();
            $topPrice = $topBid ? (float)$topBid['price'] : (float)$goods['start_price'];
            $raise    = (float)$goods['raise_price'] > 0 ? (float)$goods['raise_price'] : 1;
            $minPrice = round($topPrice + $raise, 2);
            if ($price < $minPrice) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '出价不能低于当前最高价加价幅度，最低 ' . number_format($minPrice, 2) . ' 元']);
            }
            $steps = ($price - $topPrice) / $raise;
            if (abs($steps - round($steps)) > 0.0001) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '出价必须按加价幅度 ' . number_format($raise, 2) . ' 元递增']);
            }

            Db::name('bid_record')->insert([
                'goods_id'    => $goodsId,
                'user_id'     => $userId,
                'price'       => $price,
                'status'      => 0,
                'is_winner'   => 0,
                'deposit'     => 0,
                'create_time' => $now,
            ]);
            Db::name('goods')->where('id', $goodsId)->update([
                'bid_count'   => Db::raw('bid_count + 1'),
                'update_time' => $now,
            ]);

            // 原最高出价者出局通知（与前台出价一致）
            if ($topBid && (int)$topBid['user_id'] !== $userId) {
                Db::name('sys_message')->insert([
                    'user_id'     => $topBid['user_id'],
                    'admin_id'    => 0,
                    'title'       => '竞拍出局通知',
                    'content'     => '您出价竞拍的「' . $goods['title'] . '」已出局：您的出价 ¥' . number_format((float)$topBid['price'], 2) . ' 于 ' . date('Y-m-d H:i:s', $now) . ' 被 ¥' . number_format($price, 2) . ' 超过，当前最高价 ¥' . number_format($price, 2) . '。如需继续竞拍，请再次出价。',
                    'is_read'     => 0,
                    'create_time' => $now,
                ]);
            }

            // 延时拍卖：结束前 N 秒出价自动延长（与前台一致）
            $delay = $goods['delay_seconds'] > 0 ? (int)$goods['delay_seconds'] : (int)get_setting('auction_delay', 0);
            if ($delay > 0 && $goods['end_time'] - $now <= $delay) {
                Db::name('goods')->where('id', $goodsId)->update(['end_time' => $now + $delay]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => '已添加出价记录']);
    }
}

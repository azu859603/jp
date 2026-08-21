<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Bid extends Base
{
    /**
     * 出价记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $goodsId = $this->request->param('goods_id', '');

            $query = Db::name('bid_record')->alias('b')
                ->leftJoin('goods g', 'b.goods_id = g.id')
                ->leftJoin('user u', 'b.user_id = u.id')
                ->field('b.*, g.title as goods_title, u.mobile, u.nickname');

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('g.title', "%{$keyword}%")
                        ->whereOr('u.mobile', 'like', "%{$keyword}%");
                });
            }
            if ($goodsId !== '') {
                $query->where('b.goods_id', (int)$goodsId);
            }

            $total = $query->count();
            $list = $query->order('b.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        // 手动添加出价：下拉数据（拍卖中商品 + 正常用户）
        $goodsList = Db::name('goods')->where('status', 1)->order('id', 'desc')->limit(300)->select()->toArray();
        foreach ($goodsList as &$g) {
            $g['top_price'] = (float)Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->max('price');
            $g['top_price'] = max($g['top_price'], (float)$g['start_price']);
        }
        unset($g);
        $userList = Db::name('user')->where('status', 1)->order('id', 'desc')->limit(300)->select()->toArray();

        View::assign([
            'menu_active' => '/admin1314/bid/index',
            'goods_list'  => $goodsList,
            'user_list'   => $userList,
        ]);
        return View::fetch();
    }

    /**
     * 手动添加出价记录（买家为已创建的会员）
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
            return json(['code' => 0, 'msg' => '请选择商品、买家并填写出价金额']);
        }

        Db::startTrans();
        try {
            $goods = Db::name('goods')->where('id', $goodsId)->lock(true)->find();
            if (!$goods) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            if ($goods['status'] != 1) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '该商品不在拍卖中']);
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

            $user = Db::name('user')->where('id', $userId)->find();
            if (!$user || $user['status'] != 1) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '买家不存在或已被禁用']);
            }

            // 当前最高价 + 阶梯校验（与前台一致）
            $topBid = Db::name('bid_record')
                ->where('goods_id', $goodsId)
                ->where('status', 0)
                ->order('price', 'desc')
                ->order('id', 'asc')
                ->find();
            $topPrice = $topBid ? (float)$topBid['price'] : (float)$goods['start_price'];
            $raise = (float)$goods['raise_price'] > 0 ? (float)$goods['raise_price'] : 1;
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

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        admin_log('手动添加出价：商品「' . $goods['title'] . '」 买家ID ' . $userId . ' 出价 ' . number_format($price, 2) . ' 元');
        return json(['code' => 1, 'msg' => '已添加出价记录']);
    }
}

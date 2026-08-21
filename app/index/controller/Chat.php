<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use think\facade\Request;

class Chat extends Base
{
    /**
     * 聊天窗口
     */
    public function detail()
    {
        $uid = session('user.id');
        if (!$uid) {
            return redirect('/user/login');
        }
        $goodsId = (int)Request::param('goods_id', 0);
        $sellerId = (int)Request::param('seller_id', 0);

        if (!$goodsId || !$sellerId) {
            return redirect('/');
        }

        // 校验商品
        $goods = Db::name('goods')->where('id', $goodsId)->where('status', 1)->find();
        if (!$goods) {
            return redirect('/');
        }

        // 校验卖家
        $seller = Db::name('user')->where('id', $sellerId)->find();
        if (!$seller) {
            return redirect('/');
        }

        // 查询历史消息
        $messages = Db::name('message')
            ->where('goods_id', $goodsId)
            ->where(function ($q) use ($uid, $sellerId) {
                $q->where(function ($q2) use ($uid, $sellerId) {
                    $q2->where('from_uid', $uid)->where('to_uid', $sellerId);
                })->whereOr(function ($q2) use ($uid, $sellerId) {
                    $q2->where('from_uid', $sellerId)->where('to_uid', $uid);
                });
            })
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 标记卖家发给当前用户的消息为已读
        Db::name('message')
            ->where('goods_id', $goodsId)
            ->where('from_uid', $sellerId)
            ->where('to_uid', $uid)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        $otherName = !empty($seller['shop_name']) ? $seller['shop_name'] : $seller['nickname'];

        // 获取最后一条的 id，用于轮询
        $lastId = count($messages) > 0 ? end($messages)['id'] : 0;

        View::assign([
            'messages'   => $messages,
            'goods'      => $goods,
            'seller'     => $seller,
            'other_name' => $otherName,
            'last_id'    => $lastId,
            'uid'        => $uid,
            'page_title' => '咨询 - ' . $otherName,
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }

    /**
     * 发送消息（AJAX）
     */
    public function send()
    {
        if (!Request::isAjax()) {
            return json(['code' => 0, 'msg' => '请求错误']);
        }
        $uid = session('user.id');
        if (!$uid) {
            return json(['code' => -1, 'msg' => '请先登录']);
        }
        $goodsId = (int)Request::param('goods_id', 0);
        $sellerId = (int)Request::param('seller_id', 0);
        $content = trim(Request::param('content', ''));

        if (!$goodsId || !$sellerId) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if ($content === '') {
            return json(['code' => 0, 'msg' => '请输入消息内容']);
        }
        if (mb_strlen($content) > 500) {
            return json(['code' => 0, 'msg' => '消息不能超过500字']);
        }

        $msgId = Db::name('message')->insertGetId([
            'from_uid'    => $uid,
            'to_uid'      => $sellerId,
            'goods_id'    => $goodsId,
            'content'     => $content,
            'is_read'     => 0,
            'create_time' => time(),
        ]);

        return json(['code' => 1, 'msg' => '发送成功', 'id' => $msgId]);
    }

    /**
     * 轮询新消息（AJAX）
     */
    public function poll()
    {
        if (!Request::isAjax()) {
            return json(['code' => 0]);
        }
        $uid = session('user.id');
        if (!$uid) {
            return json(['code' => -1]);
        }
        $goodsId = (int)Request::param('goods_id', 0);
        $sellerId = (int)Request::param('seller_id', 0);
        $lastId = (int)Request::param('last_id', 0);

        if (!$goodsId || !$sellerId) {
            return json(['code' => 0]);
        }

        $messages = Db::name('message')
            ->where('goods_id', $goodsId)
            ->where('id', '>', $lastId)
            ->where(function ($q) use ($uid, $sellerId) {
                $q->where(function ($q2) use ($uid, $sellerId) {
                    $q2->where('from_uid', $uid)->where('to_uid', $sellerId);
                })->whereOr(function ($q2) use ($uid, $sellerId) {
                    $q2->where('from_uid', $sellerId)->where('to_uid', $uid);
                });
            })
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 标记新收到的消息为已读
        $newIds = [];
        foreach ($messages as $m) {
            if ($m['from_uid'] == $sellerId && $m['to_uid'] == $uid) {
                $newIds[] = $m['id'];
            }
        }
        if (!empty($newIds)) {
            Db::name('message')->whereIn('id', $newIds)->update(['is_read' => 1]);
        }

        // 格式化时间
        foreach ($messages as &$m) {
            $m['time_str'] = date('m-d H:i', $m['create_time']);
        }
        unset($m);

        return json(['code' => 1, 'data' => $messages]);
    }

    /**
     * 消息列表
     */
    public function list()
    {
        $uid = session('user.id');
        if (!$uid) {
            return redirect('/user/login');
        }
        $tab = Request::param('tab', 'buyer');

        // 买家消息：当前用户发出的消息，按 goods_id 分组取最新一条
        $buyerMessages = Db::name('message')
            ->alias('m')
            ->field('m.*, u.nickname as other_name, u.avatar as other_avatar, u.shop_name as other_shop, g.title as goods_title')
            ->leftJoin('user u', 'm.to_uid = u.id')
            ->leftJoin('goods g', 'm.goods_id = g.id')
            ->where('m.from_uid', $uid)
            ->order('m.id', 'desc')
            ->select()
            ->toArray();

        // 按 goods_id 分组，去重取第一条（即每组最新消息）
        $buyerGrouped = [];
        $seen = [];
        foreach ($buyerMessages as $m) {
            $key = $m['goods_id'] . '_' . $m['to_uid'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $m['time_str'] = date('m-d H:i', $m['create_time']);
                $m['other_display'] = !empty($m['other_shop']) ? $m['other_shop'] : $m['other_name'];
                // 未读回复条数
                $m['unread'] = Db::name('message')
                    ->where('goods_id', $m['goods_id'])
                    ->where('from_uid', $m['to_uid'])
                    ->where('to_uid', $uid)
                    ->where('is_read', 0)
                    ->count();
                $buyerGrouped[] = $m;
            }
        }

        // 卖家消息：当前用户收到的消息，按 goods_id + from_uid 分组取最新一条
        $sellerMessages = Db::name('message')
            ->alias('m')
            ->field('m.*, u.nickname as other_name, u.avatar as other_avatar, u.shop_name as other_shop, g.title as goods_title')
            ->leftJoin('user u', 'm.from_uid = u.id')
            ->leftJoin('goods g', 'm.goods_id = g.id')
            ->where('m.to_uid', $uid)
            ->order('m.id', 'desc')
            ->select()
            ->toArray();

        $sellerGrouped = [];
        $seen = [];
        foreach ($sellerMessages as $m) {
            $key = $m['goods_id'] . '_' . $m['from_uid'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $m['time_str'] = date('m-d H:i', $m['create_time']);
                $m['other_display'] = !empty($m['other_shop']) ? $m['other_shop'] : $m['other_name'];
                $m['unread'] = Db::name('message')
                    ->where('goods_id', $m['goods_id'])
                    ->where('from_uid', $m['from_uid'])
                    ->where('to_uid', $uid)
                    ->where('is_read', 0)
                    ->count();
                $sellerGrouped[] = $m;
            }
        }

        View::assign([
            'buyer_messages'  => $buyerGrouped,
            'seller_messages' => $sellerGrouped,
            'tab'             => $tab,
            'page_title'      => '我的消息',
            'tab_active'      => 'user',
        ]);
        return View::fetch();
    }
}
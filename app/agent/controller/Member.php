<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 我的会员（只读）
 *
 * 一期为纯只读：不提供任何写操作（改余额/改状态/重置密码/发私信均不开放），
 * 需要变更请走平台后台。新增方法时务必保持数据范围经 memberQuery()/assertMyMember() 收口。
 */
class Member extends Base
{
    /**
     * 会员列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            return $this->listData();
        }

        View::assign('menu_active', '/agent/member/index');
        return View::fetch();
    }

    /**
     * 列表数据
     */
    protected function listData()
    {
        list($page, $limit) = array_values($this->pageParam());

        if ($this->hasNoMember()) {
            return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
        }

        $keyword  = trim($this->request->param('keyword', ''));
        $isSeller = $this->request->param('is_seller', '');
        $status   = $this->request->param('status', '');
        $regStart = trim($this->request->param('reg_start', ''));
        $regEnd   = trim($this->request->param('reg_end', ''));

        // 起手即锁定 pid = 我，后续条件只能收窄不会放宽
        $query = $this->memberQuery();

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('mobile', "%{$keyword}%")
                    ->whereOr('nickname', 'like', "%{$keyword}%")
                    ->whereOr('invite_code', 'like', "%{$keyword}%");
            });
        }
        if ($isSeller !== '') {
            $query->where('is_seller', (int)$isSeller);
        }
        if ($status !== '') {
            $query->where('status', (int)$status);
        }
        if ($regStart !== '') {
            $ts = strtotime($regStart . ' 00:00:00');
            if ($ts) {
                $query->where('reg_time', '>=', $ts);
            }
        }
        if ($regEnd !== '') {
            $ts = strtotime($regEnd . ' 23:59:59');
            if ($ts) {
                $query->where('reg_time', '<=', $ts);
            }
        }

        $total = $query->count();
        $list  = $query->order('id', 'desc')
            ->page($page, $limit)
            ->field('id,nickname,avatar,mobile,invite_code,is_seller,seller_check,is_virtual,status,total_buy,total_sell,reg_time,last_login_time')
            ->select()
            ->toArray();

        foreach ($list as &$u) {
            // 代理只看到脱敏手机号；余额等资金字段一律不下发
            $u['mobile_mask'] = $this->maskMobile($u['mobile']);
            unset($u['mobile']);
        }
        unset($u);

        return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
    }

    /**
     * 会员详情（只读）
     */
    public function detail()
    {
        // 归属校验：不是我的下级直接被踢回列表
        $member = $this->assertMyMember($this->request->param('id', 0));
        $mid    = (int)$member['id'];

        $member['mobile_mask'] = $this->maskMobile($member['mobile']);
        unset($member['mobile'], $member['id_card'], $member['id_card_front'], $member['id_card_back']);

        // 该会员作为买家的订单
        $orders = Db::name('order')
            ->where('buyer_id', $mid)
            ->field('id,order_no,goods_title,price,pay_status,order_status,create_time,pay_time')
            ->order('id', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        // 该会员的出价记录
        $bids = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->field('b.id,b.goods_id,b.price,b.status,b.is_winner,b.create_time,g.title')
            ->where('b.user_id', $mid)
            ->order('b.id', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        // 该会员汇总
        $paid = Db::name('order')->where('buyer_id', $mid)->where('pay_status', 1);
        $summary = [
            'order_count' => (clone $paid)->count(),
            'amount'      => round((float)(clone $paid)->sum('price'), 2),
            'bid_total'   => Db::name('bid_record')->where('user_id', $mid)->count(),
            'win_total'   => Db::name('bid_record')->where('user_id', $mid)->where('is_winner', 1)->count(),
        ];

        View::assign([
            'member'      => $member,
            'orders'      => $orders,
            'bids'        => $bids,
            'summary'     => $summary,
            'menu_active' => '/agent/member/index',
        ]);
        return View::fetch();
    }
}

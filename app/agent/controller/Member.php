<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 我的会员（只读）
 *
 * 会员资料只读（改余额/改状态/重置密码/发私信均不开放，需要变更请走平台后台）；
 * 开放两类审核动作：实名认证审核、卖家入驻审核，且仅限本团队会员。
 * 新增方法时务必保持数据范围经 memberQuery()/assertMyMember() 收口。
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

    // ------------------------------------------------------------------
    // 实名认证审核（仅本团队）
    // ------------------------------------------------------------------

    /**
     * 实名认证列表
     * 从主后台迁移；范围收口为本团队，身份证号脱敏后下发
     */
    public function auth()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $status = $this->request->param('status', '');

            $query = $this->memberQuery();
            if ($status !== '') {
                $query->where('auth_status', (int)$status);
            } else {
                // 全部：排除从未提交认证的会员
                $query->where('auth_status', '<>', 0);
            }
            $query->field('id,mobile,nickname,real_name,id_card,id_card_front,id_card_back,auth_status,auth_reason,auth_time,reg_time,create_time');
            $total = $query->count();
            $list  = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

            foreach ($list as &$u) {
                $u['mobile_mask']  = $this->maskMobile($u['mobile']);
                $u['id_card_mask'] = $this->maskIdCard($u['id_card']);
                unset($u['mobile'], $u['id_card']);   // 完整号码不出服务器
            }
            unset($u);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/agent/member/auth');
        return View::fetch();
    }

    /**
     * 实名认证审核操作
     */
    public function authAudit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id     = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');
        $reason = trim($this->request->post('reason', ''));

        // 归属校验：不是我团队的会员直接拒绝
        $user = $this->assertMyMember($id);
        if ((int)$user['auth_status'] !== 1) {
            return json(['code' => 0, 'msg' => '该会员当前没有待审核的实名认证']);
        }

        if ($action === 'pass') {
            Db::name('user')->where('id', $id)->update([
                'auth_status' => 2,
                'auth_reason' => '',
                'auth_time'   => time(),
                'update_time' => time(),
            ]);
            return json(['code' => 1, 'msg' => '已通过，该会员可申请成为卖家']);
        }

        if ($reason === '') {
            return json(['code' => 0, 'msg' => '请填写拒绝原因']);
        }
        Db::name('user')->where('id', $id)->update([
            'auth_status' => 3,
            'auth_reason' => mb_substr($reason, 0, 200),
            'update_time' => time(),
        ]);
        return json(['code' => 1, 'msg' => '已拒绝']);
    }

    // ------------------------------------------------------------------
    // 卖家入驻审核（仅本团队）
    // ------------------------------------------------------------------

    /**
     * 卖家申请列表
     */
    public function seller()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $status = $this->request->param('status', '');

            // 全部：本团队中提交过入驻申请的会员（店铺名称非空）；否则按审核状态筛选
            $query = $this->memberQuery()->where('shop_name', '<>', '');
            if ($status !== '') {
                $query->where('seller_check', (int)$status);
            }
            $query->field('id,mobile,nickname,avatar,invite_code,is_seller,seller_check,total_buy,total_sell,status,reg_time,create_time,shop_name,company_name,license_img,real_name');
            $total = $query->count();
            $list  = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

            foreach ($list as &$u) {
                $u['mobile_mask'] = $this->maskMobile($u['mobile']);
                unset($u['mobile']);
            }
            unset($u);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/agent/member/seller');
        return View::fetch();
    }

    /**
     * 卖家入驻审核操作
     */
    public function sellerAudit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id     = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');

        $user = $this->assertMyMember($id);
        if ((int)$user['seller_check'] !== 0 || $user['shop_name'] === '') {
            return json(['code' => 0, 'msg' => '该会员当前没有待审核的入驻申请']);
        }

        if ($action === 'pass') {
            Db::name('user')->where('id', $id)->update([
                'seller_check' => 1,
                'is_seller'    => 1,
                'update_time'  => time(),
            ]);
            return json(['code' => 1, 'msg' => '已通过，该会员已开通卖家权限']);
        }

        Db::name('user')->where('id', $id)->update([
            'seller_check' => 2,
            'update_time'  => time(),
        ]);
        return json(['code' => 1, 'msg' => '已拒绝']);
    }

    /**
     * 身份证号脱敏：保留前 4 位与后 4 位
     */
    protected function maskIdCard($idCard)
    {
        $idCard = (string)$idCard;
        $len = strlen($idCard);
        if ($len < 9) {
            return $idCard === '' ? '' : str_repeat('*', $len);
        }
        return substr($idCard, 0, 4) . str_repeat('*', $len - 8) . substr($idCard, -4);
    }
}

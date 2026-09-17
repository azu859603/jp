<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 我的会员
 *
 * 可添加会员（新会员上级固定为当前代理）、编辑下级卖家的店铺资料、调整下级余额；其余会员资料只读（改状态/重置密码/发私信均不开放，需要变更请走平台后台）；
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
        $isVirtual = $this->request->param('is_virtual', '');
        if ($isVirtual !== '') {
            $query->where('is_virtual', (int)$isVirtual);
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
            ->field('id,nickname,avatar,mobile,invite_code,is_seller,seller_check,is_agent,is_virtual,status,status_remark,can_withdraw,balance,freeze_balance,total_buy,total_sell,reg_time,last_login_time,shop_name,seller_intro,deposit,shop_score,credit_score,fans_count')
            ->select()
            ->toArray();

        // 手机号、余额对代理完整展示（代理需要了解自己下级的情况）

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

        // 手机号、余额完整展示；身份证信息仍不下发
        unset($member['id_card'], $member['id_card_front'], $member['id_card_back']);

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

        // 提现绑定账户（代理可新增 / 修改 / 删除）
        $payAccounts = Db::name('pay_account')->where('user_id', $mid)->order('type', 'asc')->select()->toArray();
        $typeNames = [1 => '支付宝', 2 => '微信', 3 => '银行卡', 4 => '虚拟货币(USDT-TRC20)'];
        foreach ($payAccounts as &$p) {
            $p['type_name'] = $typeNames[$p['type']] ?? '未知';
        }
        unset($p);

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
            'pay_accounts'=> $payAccounts,
            'pay_json'    => json_encode(array_column($payAccounts, null, 'type'), JSON_UNESCAPED_UNICODE),
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
    }    /**
     * 添加会员：新会员的上级固定为当前代理（pid = 我）
     * 可设置会员类型（普通 / 虚拟）、初始余额、是否同时开通卖家权限，规则与主后台添加会员一致
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $mobile   = trim($this->request->post('mobile', ''));
        $password = trim($this->request->post('password', ''));
        $nickname = trim($this->request->post('nickname', ''));
        $balance   = round((float)$this->request->post('balance', 0), 2);
        $isSeller  = (int)$this->request->post('is_seller', 0) === 1;
        $isVirtual = (int)$this->request->post('is_virtual', 0) === 1;

        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return json(['code' => 0, 'msg' => '手机号格式不正确']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少 6 位']);
        }
        if (money_over_max($balance)) {
            return json(['code' => 0, 'msg' => '赠送余额超过系统上限（最大 ' . money_max_text() . '）']);
        }
        if ($balance < 0) {
            return json(['code' => 0, 'msg' => '初始余额不能为负数']);
        }
        if ($nickname === '') {
            $nickname = '用户' . substr($mobile, -4);
        }
        if (Db::name('user')->where('mobile', $mobile)->find()) {
            return json(['code' => 0, 'msg' => '该手机号已注册']);
        }

        $now = time();
        Db::startTrans();
        try {
            $userId = Db::name('user')->insertGetId([
                'mobile'       => $mobile,
                'password'     => hash_password($password),
                'nickname'     => mb_substr($nickname, 0, 30),
                'invite_code'  => generate_invite_code(),
                'pid'          => $this->uid,
                // 虚拟会员：余额 = 表单填写的金额（默认 100000），永存不减、不审计流水
                'balance'      => $balance,
                'is_virtual'   => $isVirtual ? 1 : 0,
                'is_seller'    => $isSeller ? 1 : 0,
                'seller_check' => $isSeller ? 1 : 0,
                'status'       => 1,
                'reg_ip'       => $this->request->ip(),
                'reg_time'     => $now,
                'create_time'  => $now,
                'update_time'  => $now,
            ]);
            // 初始余额写流水（虚拟会员与普通会员一致）
            if ($balance > 0) {
                Db::name('balance_log')->insert([
                    'user_id'     => $userId,
                    'type'        => 'recharge',
                    'amount'      => $balance,
                    'balance'     => $balance,
                    'remark'      => '代理添加会员赠送余额',
                    'create_time' => $now,
                ]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '添加失败：' . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => '添加成功，该会员已归入您的团队', 'id' => $userId]);
    }    /**
     * 编辑店铺资料（仅限我的下级中已开通的卖家），字段与主后台一致
     */
    public function updateShop()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $member = $this->assertMyMember($this->request->post('id', 0));
        if ((int)$member['is_seller'] !== 1 || (int)$member['seller_check'] !== 1) {
            return json(['code' => 0, 'msg' => '该会员还不是卖家，无法编辑店铺资料']);
        }
        $intro   = trim($this->request->post('seller_intro', ''));
        $deposit = round((float)$this->request->post('deposit', 0), 2);
        $score   = round((float)$this->request->post('shop_score', 5), 1);
        $fans    = max((int)$this->request->post('fans_count', 0), 0);
        $credit  = (int)$this->request->post('credit_score', $member['credit_score'] ?? 100);
        if ($deposit < 0 || $score < 0 || $score > 5 || $fans < 0) {
            return json(['code' => 0, 'msg' => '参数不正确']);
        }
        if ($credit < 0 || $credit > 999) {
            return json(['code' => 0, 'msg' => '信誉分范围 0 ~ 999']);
        }
        Db::name('user')->where('id', $member['id'])->update([
            'seller_intro' => mb_substr($intro, 0, 200),
            'deposit'      => $deposit,
            'shop_score'   => $score,
            'credit_score' => $credit,
            'fans_count'   => $fans,
            'update_time'  => time(),
        ]);
        return json(['code' => 1, 'msg' => '已保存']);
    }    /**
     * 调整下级会员余额（正数增加，负数扣减），规则与主后台一致
     * 写流水，备注前缀「代理调整」（虚拟会员与普通会员一致）
     */
    /**
     * 禁用 / 启用下级会员（禁用必须填写备注，启用时清空备注）
     */
    public function setStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $member = $this->assertMyMember($this->request->post('id', 0));
        $status = (int)$this->request->post('status', 0) ? 1 : 0;
        $remark = mb_substr(trim((string)$this->request->post('remark', '')), 0, 200);
        if ($status === 0 && $remark === '') {
            return json(['code' => 0, 'msg' => '请填写禁用备注']);
        }
        Db::name('user')->where('id', $member['id'])->update([
            'status'        => $status,
            'status_remark' => $status === 0 ? $remark : '',
            'update_time'   => time(),
        ]);
        return json(['code' => 1, 'msg' => $status ? '已启用' : '已禁用']);
    }

    public function adjustBalance()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $member = $this->assertMyMember($this->request->post('id', 0));
        $amount = round((float)$this->request->post('amount', 0), 2);
        $remark = trim($this->request->post('remark', ''));
        if ($amount == 0) {
            return json(['code' => 0, 'msg' => '调整金额不能为0']);
        }
        if ($amount > ADJUST_MAX) {
            return json(['code' => 0, 'msg' => '单次添加余额最多 ' . number_format(ADJUST_MAX) . '']);
        }
        if ($amount < 0 && ($member['balance'] + $amount) < 0) {
            return json(['code' => 0, 'msg' => '扣减金额超过会员余额']);
        }
        if (money_over_max($member['balance'] + $amount)) {
            return json(['code' => 0, 'msg' => '调整后余额超过系统上限（最大 ' . money_max_text() . '）']);
        }
        $remark = '代理调整' . ($remark !== '' ? '：' . mb_substr($remark, 0, 50) : '');

        $newBalance = round($member['balance'] + $amount, 2);
        Db::startTrans();
        try {
            Db::name('user')->where('id', $member['id'])->update(['balance' => $newBalance, 'update_time' => time()]);
            Db::name('balance_log')->insert([
                'user_id'     => $member['id'],
                'type'        => $amount > 0 ? 'recharge' : 'refund',
                'amount'      => $amount,
                'balance'     => $newBalance,
                'remark'      => $remark,
                'create_time' => time(),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => '余额已调整，当前 ¥' . number_format($newBalance, 2)]);
    }    /**
     * 新增 / 修改下级会员的提现账户（每种方式一条），校验规则与前台绑定一致
     */
    public function savePayAccount()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $member     = $this->assertMyMember($this->request->post('user_id', 0));
        $userId     = (int)$member['id'];
        $type       = (int)$this->request->post('type');
        $realName   = trim($this->request->post('real_name', ''));
        $account    = trim($this->request->post('account', ''));
        $bankName   = trim($this->request->post('bank_name', ''));
        $bankBranch = trim($this->request->post('bank_branch', ''));
        $qrCode     = trim($this->request->post('qr_code', ''));

        if (!in_array($type, [1, 2, 3, 4])) {
            return json(['code' => 0, 'msg' => '请选择提现方式']);
        }
        if ($type === 4) {
            if (!preg_match('/^T[A-Za-z0-9]{25,40}$/', $account)) {
                return json(['code' => 0, 'msg' => 'USDT-TRC20 地址格式不正确（应以 T 开头的字母数字）']);
            }
            $realName = '';
            $qrCode = '';
            $bankName = 'TRC20';
            $bankBranch = '';
        } elseif ($type === 3) {
            if ($realName === '' || $account === '') {
                return json(['code' => 0, 'msg' => '请填写姓名和银行卡号']);
            }
            if ($bankName === '' || $bankBranch === '') {
                return json(['code' => 0, 'msg' => '请填写银行名称和开户行']);
            }
            $qrCode = '';
        } else {
            if ($qrCode === '' || !preg_match('~^/uploads/[\w\-./]+\.(jpg|jpeg|png|gif|webp)$~i', $qrCode)) {
                return json(['code' => 0, 'msg' => '请上传收款码图片']);
            }
            $realName = '';
            $account = '';
            $bankName = '';
            $bankBranch = '';
        }
        // 银行卡号 / USDT 地址全站唯一
        if ($type === 3 || $type === 4) {
            $dup = Db::name('pay_account')->where('type', $type)->where('account', $account)->where('user_id', '<>', $userId)->find();
            if ($dup) {
                return json(['code' => 0, 'msg' => ($type === 3 ? '该银行卡号' : '该钱包地址') . '已被其他会员绑定']);
            }
        }

        $now = time();
        $data = [
            'real_name'   => $realName,
            'account'     => $account,
            'bank_name'   => $bankName,
            'bank_branch' => mb_substr($bankBranch, 0, 100),
            'qr_code'     => $qrCode,
            'update_time' => $now,
        ];
        $exists = Db::name('pay_account')->where('user_id', $userId)->where('type', $type)->find();
        if ($exists) {
            Db::name('pay_account')->where('id', $exists['id'])->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['type'] = $type;
            $data['create_time'] = $now;
            Db::name('pay_account')->insert($data);
        }
        return json(['code' => 1, 'msg' => '已保存']);
    }

    /**
     * 删除下级会员的某个提现账户
     */
    public function deletePayAccount()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $member = $this->assertMyMember($this->request->post('user_id', 0));
        $type   = (int)$this->request->post('type');
        $row = Db::name('pay_account')->where('user_id', $member['id'])->where('type', $type)->find();
        if (!$row) {
            return json(['code' => 0, 'msg' => '该绑定不存在']);
        }
        Db::name('pay_account')->where('id', $row['id'])->delete();
        return json(['code' => 1, 'msg' => '已删除']);
    }
    /**
     * 批量添加虚拟会员
     * 账号统一为 12 开头的 11 位数字（真实手机号没有 12 号段，不会冲突），昵称 = 前缀 + 随机 4 位数字
     */
    public function batchAddVirtual()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $count    = (int)$this->request->post('count', 0);
        $prefix   = trim((string)$this->request->post('prefix', ''));
        $password = trim((string)$this->request->post('password', ''));
        $balance  = round((float)$this->request->post('balance', 0), 2);
        if ($count < 1 || $count > 100) {
            return json(['code' => 0, 'msg' => '数量需为 1 ~ 100']);
        }
        if ($password === '') {
            $password = '123456';
        }
        if (strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少 6 位']);
        }
        if (money_over_max($balance)) {
            return json(['code' => 0, 'msg' => '赠送余额超过系统上限（最大 ' . money_max_text() . '）']);
        }
        if ($balance < 0) {
            return json(['code' => 0, 'msg' => '初始余额不能为负数']);
        }
        $prefix = $prefix === '' ? '用户' : mb_substr($prefix, 0, 20);
        $now    = time();
        $hash   = hash_password($password);
        $ip     = $this->request->ip();
        $created = [];
        Db::startTrans();
        try {
            for ($i = 0; $i < $count; $i++) {
                $mobile   = generate_virtual_mobile();
                $nickname = $prefix . str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $userId = Db::name('user')->insertGetId([
                    'mobile'       => $mobile,
                    'password'     => $hash,
                    'nickname'     => $nickname,
                    'invite_code'  => generate_invite_code(),
                    'pid'          => $this->uid,
                    'balance'      => $balance,
                    'is_virtual'   => 1,
                    'is_seller'    => 0,
                    'seller_check' => 0,
                    'status'       => 1,
                    'reg_ip'       => $ip,
                    'reg_time'     => $now,
                    'create_time'  => $now,
                    'update_time'  => $now,
                ]);
                if ($balance > 0) {
                    Db::name('balance_log')->insert([
                        'user_id'     => $userId,
                        'type'        => 'recharge',
                        'amount'      => $balance,
                        'balance'     => $balance,
                        'remark'      => '代理添加会员赠送余额',
                        'create_time' => $now,
                    ]);
                }
                $created[] = ['id' => $userId, 'mobile' => $mobile, 'nickname' => $nickname];
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '添加失败：' . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => '已添加 ' . count($created) . ' 个虚拟会员，已归入您的团队，登录密码 ' . $password, 'data' => $created, 'password' => $password]);
    }
    /**
     * 会员搜索：录入实名 / 卖家资料时选择会员（只在自己的一级下级里找）
     * scene=auth 只列还没有实名资料的会员；scene=seller 只列还没有入驻资料的会员
     */
    public function searchUser()
    {
        if ($this->hasNoMember()) {
            return json(['code' => 1, 'data' => []]);
        }
        $kw    = trim((string)$this->request->param('kw', ''));
        $scene = $this->request->param('scene', 'auth') === 'seller' ? 'seller' : 'auth';
        $query = $this->memberQuery();
        if ($kw !== '') {
            $query->where(function ($q) use ($kw) {
                $q->where('mobile', 'like', "%{$kw}%")->whereOr('nickname', 'like', "%{$kw}%");
                if (ctype_digit($kw)) {
                    $q->whereOr('id', (int)$kw);
                }
            });
        }
        if ($scene === 'auth') {
            $query->where('auth_status', 0);
        } else {
            $query->where('shop_name', '');
        }
        $list = $query->field('id,mobile,nickname,auth_status,seller_check,shop_name,is_seller')
            ->order('id', 'desc')->limit(20)->select()->toArray();
        return json(['code' => 1, 'data' => $list]);
    }

    /**
     * 取一个团队会员的完整实名资料（列表里身份证是脱敏的，编辑时需要原值）
     */
    public function authInfo()
    {
        $user = $this->assertMyMember($this->request->param('id', 0));
        return json(['code' => 1, 'data' => [
            'id'            => (int)$user['id'],
            'nickname'      => $user['nickname'],
            'mobile_mask'   => $this->maskMobile($user['mobile']),
            'real_name'     => $user['real_name'],
            'id_card'       => $user['id_card'],
            'id_card_front' => $user['id_card_front'],
            'id_card_back'  => $user['id_card_back'],
            'auth_status'   => (int)$user['auth_status'],
            'auth_reason'   => $user['auth_reason'],
        ]]);
    }

    /**
     * 录入 / 修改实名认证资料（仅限自己的下级，规则与主后台一致）
     */
    public function authSave()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $user     = $this->assertMyMember($this->request->post('id', 0));
        $id       = (int)$user['id'];
        $realName = trim((string)$this->request->post('real_name', ''));
        $idCard   = strtoupper(trim((string)$this->request->post('id_card', '')));
        $front    = trim((string)$this->request->post('id_card_front', ''));
        $back     = trim((string)$this->request->post('id_card_back', ''));
        $status   = (int)$this->request->post('auth_status', 2);
        $reason   = trim((string)$this->request->post('auth_reason', ''));

        if ($realName === '' || mb_strlen($realName) > 30) {
            return json(['code' => 0, 'msg' => '请输入真实姓名（30 字以内）']);
        }
        if (!preg_match('/^\d{17}[\dX]$/', $idCard)) {
            return json(['code' => 0, 'msg' => '身份证号格式不正确（18 位，最后一位可为 X）']);
        }
        if (!in_array($status, [1, 2, 3], true)) {
            return json(['code' => 0, 'msg' => '认证状态不正确']);
        }
        if ($status === 3 && $reason === '') {
            return json(['code' => 0, 'msg' => '状态为已拒绝时请填写拒绝原因']);
        }
        $dup = Db::name('user')->where('id_card', $idCard)->where('id', '<>', $id)->find();
        if ($dup) {
            return json(['code' => 0, 'msg' => '该身份证号已被其他会员使用']);
        }
        if ($status !== 2 && (int)$user['is_seller'] === 1) {
            return json(['code' => 0, 'msg' => '该会员已是卖家，实名状态不能改为未通过，请先在「卖家审核」中取消其卖家资格']);
        }

        $isNew = (int)$user['auth_status'] === 0;
        Db::name('user')->where('id', $id)->update([
            'real_name'     => $realName,
            'id_card'       => $idCard,
            'id_card_front' => $front,
            'id_card_back'  => $back,
            'auth_status'   => $status,
            'auth_reason'   => $status === 3 ? mb_substr($reason, 0, 200) : '',
            'auth_time'     => $status === 2 ? time() : (int)$user['auth_time'],
            'update_time'   => time(),
        ]);
        $stText = [1 => '待审核', 2 => '已通过', 3 => '已拒绝'][$status];
        return json(['code' => 1, 'msg' => ($isNew ? '实名资料已录入' : '实名资料已更新') . '（' . $stText . '）']);
    }

    /**
     * 录入 / 修改卖家入驻资料（仅限自己的下级，规则与主后台一致）
     */
    public function sellerSave()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $user     = $this->assertMyMember($this->request->post('id', 0));
        $id       = (int)$user['id'];
        $shopName = trim((string)$this->request->post('shop_name', ''));
        $company  = trim((string)$this->request->post('company_name', ''));
        $license  = $this->request->post('license_img', '');
        if (is_array($license)) {
            $license = implode(',', array_filter($license));
        }
        $license = trim((string)$license);
        $status  = (int)$this->request->post('seller_check', 1);

        if ($shopName === '' || mb_strlen($shopName) > 50) {
            return json(['code' => 0, 'msg' => '请输入店铺名称（50 字以内）']);
        }
        if (mb_strlen($company) > 100) {
            return json(['code' => 0, 'msg' => '企业名称不能超过 100 字']);
        }
        if (!in_array($status, [0, 1, 2], true)) {
            return json(['code' => 0, 'msg' => '审核状态不正确']);
        }
        if ($status === 1 && (int)$user['auth_status'] !== 2) {
            return json(['code' => 0, 'msg' => '该会员尚未通过实名认证，不能设为已通过，请先在「实名认证」中录入并通过']);
        }
        $dup = Db::name('user')->where('shop_name', $shopName)->where('id', '<>', $id)->find();
        if ($dup) {
            return json(['code' => 0, 'msg' => '该店铺名称已被其他会员使用']);
        }

        $isNew = trim((string)$user['shop_name']) === '';
        Db::name('user')->where('id', $id)->update([
            'shop_name'    => $shopName,
            'company_name' => $company,
            'license_img'  => $license,
            'seller_check' => $status,
            'is_seller'    => $status === 1 ? 1 : 0,
            'update_time'  => time(),
        ]);
        $stText = [0 => '待审核', 1 => '已通过', 2 => '已拒绝'][$status];
        return json(['code' => 1, 'msg' => ($isNew ? '卖家资料已录入' : '卖家资料已更新') . '（' . $stText . ($status === 1 ? '，已开通卖家权限' : '') . '）']);
    }
    /**
     * 编辑团队会员：重置密码 / 卖家 / 代理 / 虚拟会员 一次保存（代理端不能改上级）
     * 语义与主后台 editSave() 一致；只能编辑自己的一级下级
     */
    public function editSave()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id   = (int)$this->request->post('id');
        $user = $this->assertMyMember($id);
        $password  = trim((string)$this->request->post('password', ''));
        $isSeller  = $this->request->has('is_seller', 'post') ? ((int)$this->request->post('is_seller') === 1 ? 1 : 0) : (int)$user['is_seller'];
        $isAgent   = $this->request->has('is_agent', 'post') ? ((int)$this->request->post('is_agent') === 1 ? 1 : 0) : (int)$user['is_agent'];
        $isVirtual = $this->request->has('is_virtual', 'post') ? ((int)$this->request->post('is_virtual') === 1 ? 1 : 0) : (int)$user['is_virtual'];
        $canWithdraw = $this->request->has('can_withdraw', 'post') ? ((int)$this->request->post('can_withdraw') === 1 ? 1 : 0) : (int)$user['can_withdraw'];
        if ($password !== '' && strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少6位']);
        }
        $data = [];
        $logs = [];
        if ($password !== '') {
            $data['password'] = hash_password($password);
            $logs[] = '重置密码';
        }
        if ($isSeller !== (int)$user['is_seller']) {
            $data['is_seller']    = $isSeller;
            $data['seller_check'] = $isSeller ? 1 : ((int)$user['seller_check'] === 1 ? 0 : (int)$user['seller_check']);
            $logs[] = $isSeller ? '设为卖家' : '取消卖家';
        }
        if ($isAgent !== (int)$user['is_agent']) {
            $data['is_agent']   = $isAgent;
            $data['agent_time'] = $isAgent ? ($user['agent_time'] > 0 ? $user['agent_time'] : time()) : 0;
            $logs[] = $isAgent ? '设为代理' : '取消代理';
        }
        if ($isVirtual !== (int)$user['is_virtual']) {
            $data['is_virtual'] = $isVirtual;
            $logs[] = $isVirtual ? '设为虚拟会员' : '取消虚拟会员';
        }
        if ($canWithdraw !== (int)$user['can_withdraw']) {
            $data['can_withdraw'] = $canWithdraw;
            $logs[] = $canWithdraw ? '开启提现' : '关闭提现';
        }
        if (!$data) {
            return json(['code' => 1, 'msg' => '没有需要修改的内容']);
        }
        $data['update_time'] = time();
        Db::name('user')->where('id', $id)->update($data);
        return json(['code' => 1, 'msg' => '已保存：' . implode('，', $logs)]);
    }
}

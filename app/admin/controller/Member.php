<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Member extends Base
{
    /**
     * 会员列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $isSeller = $this->request->param('is_seller', '');
            $status = $this->request->param('status', '');
            $regStart = trim($this->request->param('reg_start', ''));
            $regEnd = trim($this->request->param('reg_end', ''));

            $query = Db::name('user');
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
            $isAgent = $this->request->param('is_agent', '');
            if ($isAgent !== '') {
                $query->where('is_agent', (int)$isAgent);
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
            $list = $query->order('id', 'desc')->page($page, $limit)->field('id,mobile,nickname,avatar,invite_code,pid,is_seller,is_agent,is_virtual,seller_check,balance,freeze_balance,points,commission_rate,total_buy,total_sell,status,reg_ip,reg_time,last_login_time,create_time')->select()->toArray();

            // 上级会员信息（列表展示用）
            $pids = array_values(array_unique(array_filter(array_column($list, 'pid'))));
            $parents = $pids ? Db::name('user')->whereIn('id', $pids)->column('mobile,nickname', 'id') : [];
            foreach ($list as &$u) {
                $p = $parents[$u['pid']] ?? null;
                $u['parent_mobile']   = $p['mobile'] ?? '';
                $u['parent_nickname'] = $p['nickname'] ?? '';
            }
            unset($u);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/member/index');
        return View::fetch();
    }

    /**
     * 卖家审核列表
     */
    public function seller()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $status = $this->request->param('status', '');

            // 全部：所有提交过入驻申请的用户（店铺名称非空）；否则按审核状态筛选
            $query = Db::name('user')->where('shop_name', '<>', '');
            if ($status !== '') {
                $query->where('seller_check', (int)$status);
            }
            $query->field('id,mobile,nickname,avatar,invite_code,pid,is_seller,seller_check,balance,freeze_balance,points,commission_rate,total_buy,total_sell,status,reg_ip,reg_time,last_login_time,create_time,shop_name,company_name,license_img,real_name,shop_score,credit_score');
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/member/seller');
        return View::fetch();
    }

    /**
     * 卖家审核操作
     */
    public function sellerAudit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        if ($user['seller_check'] != 0) {
            return json(['code' => 0, 'msg' => '该会员已审核']);
        }

        if ($action === 'pass') {
            Db::name('user')->where('id', $id)->update([
                'seller_check' => 1,
                'is_seller'    => 1,
            ]);
            admin_log('通过卖家审核：' . $user['mobile']);
            return json(['code' => 1, 'msg' => '已通过，该会员已开通卖家权限']);
        } else {
            Db::name('user')->where('id', $id)->update([
                'seller_check' => 2,
            ]);
            admin_log('拒绝卖家审核：' . $user['mobile']);
            return json(['code' => 1, 'msg' => '已拒绝']);
        }
    }

    /**
     * 实名认证列表
     */
    public function auth()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $status = $this->request->param('status', '');

            $query = Db::name('user');
            if ($status !== '') {
                $query->where('auth_status', (int)$status);
            } else {
                // 全部：排除从未提交认证的用户（未认证无记录）
                $query->where('auth_status', '<>', 0);
            }
            $query->field('id,mobile,nickname,real_name,id_card,id_card_front,id_card_back,auth_status,auth_reason,auth_time,reg_time,create_time');
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/member/auth');
        return View::fetch('auth_list');
    }

    /**
     * 实名认证审核操作
     */
    public function authAudit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');
        $reason = trim($this->request->post('reason', ''));

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        if ($user['auth_status'] != 1) {
            return json(['code' => 0, 'msg' => '该会员已审核']);
        }

        if ($action === 'pass') {
            Db::name('user')->where('id', $id)->update([
                'auth_status' => 2,
                'auth_reason' => '',
                'auth_time'   => time(),
            ]);
            admin_log('通过实名认证：' . $user['mobile']);
            return json(['code' => 1, 'msg' => '已通过，该会员可申请成为卖家']);
        } else {
            if ($reason === '') {
                return json(['code' => 0, 'msg' => '请填写拒绝原因']);
            }
            Db::name('user')->where('id', $id)->update([
                'auth_status' => 3,
                'auth_reason' => $reason,
            ]);
            admin_log('拒绝实名认证：' . $user['mobile'] . '，原因：' . $reason);
            return json(['code' => 1, 'msg' => '已拒绝']);
        }
    }

    /**
     * 会员详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id');
        $user = Db::name('user')->find($id);
        if (!$user) {
            return $this->error('会员不存在');
        }
        unset($user['password']);
        $user['reg_time_text'] = $user['reg_time'] ? date('Y-m-d H:i:s', $user['reg_time']) : '-';
        $user['last_login_text'] = $user['last_login_time'] ? date('Y-m-d H:i:s', $user['last_login_time']) : '-';
        $user['lic_list'] = !empty($user['license_img']) ? explode(',', $user['license_img']) : [];

        // 上级会员
        $user['parent'] = $user['pid'] > 0
            ? Db::name('user')->where('id', $user['pid'])->field('id,mobile,nickname,invite_code')->find()
            : null;
        $user['child_count'] = Db::name('user')->where('pid', $id)->count();

        // 统计
        $user['sell_count'] = Db::name('goods')->where('seller_id', $id)->where('status', 2)->count();
        $user['buy_count'] = Db::name('order')->where('buyer_id', $id)->where('pay_status', 1)->count();
        $user['bid_count'] = Db::name('bid_record')->where('user_id', $id)->count();

        // 余额流水
        $logs = Db::name('balance_log')->where('user_id', $id)->order('id', 'desc')->limit(20)->select()->toArray();

        // 参与竞拍
        $bids = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->field('b.*, g.title as goods_title')
            ->where('b.user_id', $id)
            ->order('b.id', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 提现绑定账户
        $payAccounts = Db::name('pay_account')->where('user_id', $id)->order('id', 'desc')->select()->toArray();
        $typeNames = [1 => '支付宝', 2 => '微信', 3 => '银行卡', 4 => '虚拟货币(USDT)'];
        foreach ($payAccounts as &$p) {
            $p['type_name'] = $typeNames[$p['type']] ?? '未知';
        }
        unset($p);

        View::assign([
            'user'        => $user,
            'logs'        => $logs,
            'bids'        => $bids,
            'pay_accounts'=> $payAccounts,
            'menu_active' => '/admin1314/member/index',
        ]);
        return View::fetch();
    }

    /**
     * 更新店铺资料（店铺介绍/消费保证金/店铺星级/卖家信誉分/粉丝数量）
     */
    public function updateShop()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        $shopName = trim($this->request->post('shop_name', $user['shop_name'] ?? ''));
        $intro = trim($this->request->post('seller_intro', ''));
        $deposit = round((float)$this->request->post('deposit', 0), 2);
        $score = round((float)$this->request->post('shop_score', 5), 1);
        $fans = max((int)$this->request->post('fans_count', 0), 0);
        $credit = (int)$this->request->post('credit_score', $user['credit_score'] ?? 100);
        if ($deposit < 0 || $score < 0 || $score > 5 || $fans < 0) {
            return json(['code' => 0, 'msg' => '参数不正确']);
        }
        if ($credit < 0 || $credit > 999) {
            return json(['code' => 0, 'msg' => '信誉分范围 0 ~ 999']);
        }
        Db::name('user')->where('id', $id)->update([
            'shop_name'    => mb_substr($shopName, 0, 50),
            'seller_intro' => mb_substr($intro, 0, 200),
            'deposit'      => $deposit,
            'shop_score'   => $score,
            'credit_score' => $credit,
            'fans_count'   => $fans,
            'update_time'  => time(),
        ]);
        $creditNote = $credit !== (int)($user['credit_score'] ?? 100) ? '，信誉分 ' . (int)$user['credit_score'] . ' → ' . $credit : '';
        admin_log('修改店铺资料：会员 ' . ($user['mobile'] ?: $user['id']) . $creditNote);
        return json(['code' => 1, 'msg' => '已保存']);
    }

    /**
     * 添加会员（后台手动添加）
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $mobile = trim($this->request->post('mobile', ''));
        $nickname = trim($this->request->post('nickname', ''));
        $password = trim($this->request->post('password', ''));
        $balance = round((float)$this->request->post('balance', 0), 2);
        $isSeller = (int)$this->request->post('is_seller', 0);
        $isAgent = (int)$this->request->post('is_agent', 0);
        $isVirtual = (int)$this->request->post('is_virtual', 0);

        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return json(['code' => 0, 'msg' => '手机号格式不正确']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少6位']);
        }
        if ($balance < 0) {
            return json(['code' => 0, 'msg' => '初始余额不能为负数']);
        }
        if (Db::name('user')->where('mobile', $mobile)->find()) {
            return json(['code' => 0, 'msg' => '该手机号已注册']);
        }
        if ($nickname === '') {
            $nickname = '用户' . substr($mobile, -4);
        }

        // 生成唯一的纯数字邀请码
        $myCode = generate_invite_code();

        $now = time();
        // 虚拟会员：余额 = 表单填写的金额（默认 100000），永存不减、不审计流水
        $virtualBalance = $balance;
        Db::startTrans();
        try {
            $userId = Db::name('user')->insertGetId([
                'mobile'      => $mobile,
                'password'    => hash_password($password),
                'nickname'    => $nickname,
                'invite_code' => $myCode,
                'balance'     => $virtualBalance,
                'is_seller'   => $isSeller ? 1 : 0,
                'is_agent'    => $isAgent ? 1 : 0,
                'agent_time'  => $isAgent ? $now : 0,
                'is_virtual'  => $isVirtual ? 1 : 0,
                'seller_check'=> $isSeller ? 1 : 0,
                'status'      => 1,
                'reg_ip'      => $this->request->ip(),
                'reg_time'    => $now,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            // 普通会员初始余额写流水；虚拟会员不写流水
            if (!$isVirtual && $balance > 0) {
                Db::name('balance_log')->insert([
                    'user_id'     => $userId,
                    'type'        => 'recharge',
                    'amount'      => $balance,
                    'balance'     => $balance,
                    'remark'      => '后台添加会员赠送余额',
                    'create_time' => $now,
                ]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '添加失败：' . $e->getMessage()]);
        }

        admin_log('添加会员：' . $mobile . ($isSeller ? '（卖家）' : ''));
        return json(['code' => 1, 'msg' => '添加成功']);
    }

    /**
     * 发送站内信（后台私信用户）
     */
    public function sendMessage()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $userId = (int)$this->request->post('user_id');
        $title = trim($this->request->post('title', ''));
        $content = trim($this->request->post('content', ''));
        if ($userId <= 0) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        $user = Db::name('user')->find($userId);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        if ($title === '' || $content === '') {
            return json(['code' => 0, 'msg' => '请填写标题和内容']);
        }
        Db::name('sys_message')->insert([
            'user_id'     => $userId,
            'admin_id'    => (int)session('admin_id'),
            'title'       => mb_substr($title, 0, 100),
            'content'     => mb_substr($content, 0, 2000),
            'is_read'     => 0,
            'create_time' => time(),
        ]);
        admin_log('发送站内信：会员 ' . ($user['mobile'] ?: $user['id']) . '「' . mb_substr($title, 0, 30) . '」');
        return json(['code' => 1, 'msg' => '发送成功']);
    }

    /**
     * 启用/禁用会员
     */
    public function setStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $status = (int)$this->request->post('status', 0);

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        Db::name('user')->where('id', $id)->update(['status' => $status ? 1 : 0]);
        admin_log(($status ? '启用' : '禁用') . '会员：' . $user['mobile']);
        return json(['code' => 1, 'msg' => '操作成功']);
    }

    /**
     * 设置/取消卖家
     */
    public function setSeller()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $isSeller = (int)$this->request->post('is_seller', 0);

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        Db::name('user')->where('id', $id)->update([
            'is_seller'    => $isSeller ? 1 : 0,
            'seller_check' => $isSeller ? 1 : ($user['seller_check'] == 1 ? 0 : $user['seller_check']),
        ]);
        admin_log(($isSeller ? '设置' : '取消') . '卖家：' . $user['mobile']);
        return json(['code' => 1, 'msg' => '操作成功']);
    }

    /**
     * 调整余额
     */
    public function adjustBalance()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $amount = round((float)$this->request->post('amount', 0), 2);
        $remark = trim($this->request->post('remark', '后台调整'));

        if ($amount == 0) {
            return json(['code' => 0, 'msg' => '调整金额不能为0']);
        }

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        if ((int)$user['is_virtual'] === 1) {
            return json(['code' => 0, 'msg' => '虚拟会员余额为系统永存金额，不可手动调整']);
        }
        if ($amount < 0 && ($user['balance'] + $amount) < 0) {
            return json(['code' => 0, 'msg' => '扣减金额超过会员余额']);
        }

        $newBalance = round($user['balance'] + $amount, 2);
        Db::startTrans();
        try {
            Db::name('user')->where('id', $id)->update(['balance' => $newBalance]);
            Db::name('balance_log')->insert([
                'user_id'     => $id,
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

        admin_log('调整会员余额 ' . $user['mobile'] . '：' . $amount);
        return json(['code' => 1, 'msg' => '余额已调整']);
    }

    /**
     * 修改会员密码
     */
    public function resetPassword()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $password = trim($this->request->post('password', ''));

        if (strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少6位']);
        }
        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        Db::name('user')->where('id', $id)->update(['password' => hash_password($password)]);
        admin_log('重置会员密码：' . $user['mobile']);
        return json(['code' => 1, 'msg' => '密码已重置']);
    }
    /**
     * 设置/取消代理资格
     * 代理中心（/agent）的准入开关：仅 is_agent=1 的会员可进入
     */
    public function setAgent()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $isAgent = (int)$this->request->post('is_agent', 0);

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        Db::name('user')->where('id', $id)->update([
            'is_agent'    => $isAgent ? 1 : 0,
            'agent_time'  => $isAgent ? ($user['agent_time'] > 0 ? $user['agent_time'] : time()) : 0,
            'update_time' => time(),
        ]);
        admin_log(($isAgent ? '设置' : '取消') . '代理：' . $user['mobile']);
        return json(['code' => 1, 'msg' => '操作成功']);
    }

    /**
     * 修改会员上级（邀请人）
     *
     * POST id=会员ID  parent=上级的手机号 / 邀请码 / 会员ID；parent 为空则清空上级
     * 上级即 user.pid：影响前台「我的邀请」统计以及代理中心（/agent）的下级数据范围。
     * 校验：不能设为自己；上级必须存在；不能把会员挂到自己的下级链上（防止形成环）。
     */
    public function setParent()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $parentInput = trim((string)$this->request->post('parent', ''));

        $user = Db::name('user')->find($id);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        $newPid = 0;
        $parent = null;
        if ($parentInput !== '') {
            // 依次按 手机号 / 邀请码 / 会员ID 查找上级
            $parent = Db::name('user')->where('mobile', $parentInput)->find()
                ?: Db::name('user')->where('invite_code', $parentInput)->find();
            if (!$parent && ctype_digit($parentInput)) {
                $parent = Db::name('user')->where('id', (int)$parentInput)->find();
            }
            if (!$parent) {
                return json(['code' => 0, 'msg' => '未找到该上级会员，请输入正确的手机号、邀请码或会员ID']);
            }
            if ((int)$parent['id'] === $id) {
                return json(['code' => 0, 'msg' => '不能把自己设为上级']);
            }
            // 沿新上级的 pid 链向上查，若能回到自己则会形成环
            $cursor = (int)$parent['pid'];
            for ($i = 0; $i < 50 && $cursor > 0; $i++) {
                if ($cursor === $id) {
                    return json(['code' => 0, 'msg' => '该会员是当前会员的下级，不能反过来设为上级']);
                }
                $cursor = (int)Db::name('user')->where('id', $cursor)->value('pid');
            }
            $newPid = (int)$parent['id'];
        }

        if ($newPid === (int)$user['pid']) {
            return json(['code' => 0, 'msg' => '上级未变化']);
        }

        Db::name('user')->where('id', $id)->update(['pid' => $newPid, 'update_time' => time()]);

        $oldText = $user['pid'] > 0 ? ('#' . $user['pid']) : '无';
        $newText = $parent ? ($parent['mobile'] . '(#' . $parent['id'] . ')') : '无';
        admin_log('修改会员上级：' . $user['mobile'] . ' 由 ' . $oldText . ' 改为 ' . $newText);
        return json(['code' => 1, 'msg' => $parent ? ('已将上级改为 ' . $parent['mobile']) : '已清空上级']);
    }
}
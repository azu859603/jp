<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

class User extends Base
{
    /**
     * 登录页
     */
    public function login()
    {
        if (!empty($this->user)) {
            return redirect('/user/center');
        }
        View::assign('page_title', '会员登录');
        return View::fetch();
    }

    /**
     * 登录处理
     */
    public function doLogin()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $mobile = trim($this->request->post('mobile', ''));
        $password = trim($this->request->post('password', ''));

        if (empty($mobile) || empty($password)) {
            return json(['code' => 0, 'msg' => '请输入手机号和密码']);
        }

        $user = Db::name('user')->where('mobile', $mobile)->find();
        if (!$user || $user['password'] !== encrypt_password($password)) {
            return json(['code' => 0, 'msg' => '手机号或密码错误']);
        }
        if ($user['status'] != 1) {
            return json(['code' => 0, 'msg' => '账号已被禁用']);
        }

        Db::name('user')->where('id', $user['id'])->update([
            'last_login_time' => time(),
            'update_time'     => time(),
        ]);
        unset($user['password']);
        session('user', $user);

        return json(['code' => 1, 'msg' => '登录成功', 'url' => '/user/center']);
    }

    /**
     * 注册页
     */
    public function register()
    {
        if (!empty($this->user)) {
            return redirect('/user/center');
        }
        View::assign('page_title', '会员注册');
        return View::fetch();
    }

    /**
     * 注册处理
     */
    public function doRegister()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $mobile = trim($this->request->post('mobile', ''));
        $password = trim($this->request->post('password', ''));
        $password2 = trim($this->request->post('password2', ''));
        $nickname = trim($this->request->post('nickname', ''));
        $inviteCode = trim($this->request->post('invite_code', ''));

        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return json(['code' => 0, 'msg' => '手机号格式不正确']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 0, 'msg' => '密码至少6位']);
        }
        if ($password !== $password2) {
            return json(['code' => 0, 'msg' => '两次密码不一致']);
        }
        if ($nickname === '') {
            $nickname = '用户' . substr($mobile, -4);
        }
        if (Db::name('user')->where('mobile', $mobile)->find()) {
            return json(['code' => 0, 'msg' => '该手机号已注册']);
        }

        // 邀请码
        $pid = 0;
        if ($inviteCode !== '') {
            $inviter = Db::name('user')->where('invite_code', $inviteCode)->find();
            if (!$inviter) {
                return json(['code' => 0, 'msg' => '邀请码不存在']);
            }
            $pid = $inviter['id'];
        }

        // 生成唯一邀请码
        do {
            $myCode = strtoupper(substr(md5($mobile . mt_rand(1000, 9999)), 0, 8));
        } while (Db::name('user')->where('invite_code', $myCode)->find());

        $now = time();
        $userId = Db::name('user')->insertGetId([
            'mobile'      => $mobile,
            'password'    => encrypt_password($password),
            'nickname'    => $nickname,
            'invite_code' => $myCode,
            'pid'         => $pid,
            'status'      => 1,
            'reg_ip'      => $this->request->ip(),
            'reg_time'    => $now,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        $user = Db::name('user')->find($userId);
        unset($user['password']);
        session('user', $user);

        return json(['code' => 1, 'msg' => '注册成功', 'url' => '/user/center']);
    }

    /**
     * 用户协议 / 隐私政策详情（内容在后台基础设置中配置）
     */
    public function agreement()
    {
        $type = $this->request->param('type', 'protocol');
        if ($type === 'privacy') {
            $name = 'privacy_policy';
            $title = '隐私政策';
        } else {
            $name = 'user_protocol';
            $title = '用户协议';
        }
        $content = (string)Db::name('setting')->where('name', $name)->value('value');
        View::assign([
            'title'      => $title,
            'content'    => $content,
            'page_title' => $title,
            'hide_tabbar' => true,
        ]);
        return View::fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('user', null);
        return redirect('/');
    }

    /**
     * 个人中心主页
     */
    public function center()
    {
        $this->checkLogin();
        $id = $this->user['id'];

        // 统计
        $data = [
            'bid_count'    => Db::name('bid_record')->where('user_id', $id)->count(),
            'buy_count'    => Db::name('order')->where('buyer_id', $id)->where('pay_status', 1)->count(),
            'sell_count'   => Db::name('goods')->where('seller_id', $id)->where('status', 2)->count(),
            'goods_count'  => Db::name('goods')->where('seller_id', $id)->count(),
            'won_wait_pay' => Db::name('order')->where('buyer_id', $id)->where('pay_status', 0)->where('order_status', 0)->count(),
            'pending_ship' => Db::name('order')->where('seller_id', $id)->where('order_status', 1)->count(),
            // 我的订单四宫格
            'wait_pay'     => Db::name('order')->where('buyer_id', $id)->where('order_status', 0)->where('pay_status', 0)->count(),
            'wait_ship'    => Db::name('order')->where('buyer_id', $id)->where('order_status', 1)->count(),
            'wait_receive' => Db::name('order')->where('buyer_id', $id)->where('order_status', 2)->count(),
            'finished'     => Db::name('order')->where('buyer_id', $id)->where('order_status', 3)->count(),
            // 待发货（卖家视角）
            'my_wait_ship' => Db::name('order')->where('seller_id', $id)->where('order_status', 1)->count(),
            // 关注拍品 / 关注店铺 / 我的足迹
            'fav_count'    => Db::name('goods_favorite')->where('user_id', $id)->count(),
            'follow_count' => Db::name('seller_follow')->where('user_id', $id)->count(),
            'browse_count' => Db::name('browse_history')->where('user_id', $id)->count(),
            // 站内信未读
            'msg_unread'   => Db::name('sys_message')->where('user_id', $id)->where('is_read', 0)->count(),
        ];

        // 等级
        $level = '白丁';
        $points = (int)$this->user['points'];
        if ($points >= 10000) {
            $level = '至尊';
        } elseif ($points >= 5000) {
            $level = '黄金';
        } elseif ($points >= 2000) {
            $level = '白银';
        } elseif ($points >= 500) {
            $level = '青铜';
        } elseif ($points >= 100) {
            $level = '学徒';
        }

        // 最近流水
        $logs = Db::name('balance_log')->where('user_id', $id)->order('id', 'desc')->limit(5)->select()->toArray();

        // 参与的竞拍
        $bids = Db::name('bid_record')->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->field('b.*, g.title, g.cover, g.status as goods_status, g.end_time')
            ->where('b.user_id', $id)
            ->order('b.id', 'desc')
            ->limit(5)
            ->select()
            ->toArray();

        // 分享记录（我邀请的用户）
        $shareUsers = Db::name('user')->where('pid', $id)->field('id, nickname, mobile, reg_time')->order('id', 'desc')->limit(10)->select()->toArray();

        // 后台配置的客服链接
        $serviceLink = (string)Db::name('setting')->where('name', 'service_link')->value('value');

        // 虚拟会员标识（隐藏充值/提现记录入口）
        $isVirtual = (int)Db::name('user')->where('id', $id)->value('is_virtual') === 1;

        View::assign([
            'data'         => $data,
            'logs'         => $logs,
            'bids'         => $bids,
            'level'        => $level,
            'share_users'  => $shareUsers,
            'service_link' => $serviceLink,
            'is_virtual'   => $isVirtual,
            'page_title'   => '个人中心',
            'tab_active'   => 'mine',
            'hide_header'  => true,
        ]);
        return View::fetch();
    }

    /**
     * 关注的拍品（收藏）
     */
    public function favorites()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $list = Db::name('goods_favorite')->alias('f')
            ->leftJoin('goods g', 'f.goods_id = g.id')
            ->field('f.*, g.title, g.cover, g.start_price, g.status as goods_status, g.end_time, g.seller_id')
            ->where('f.user_id', $id)
            ->order('f.id', 'desc')
            ->select()
            ->toArray();
        foreach ($list as &$v) {
            $top = Db::name('bid_record')->where('goods_id', $v['goods_id'])->where('status', 0)->max('price');
            $v['cur_price'] = $top > 0 ? (float)$top : (float)$v['start_price'];
            $v['status_txt'] = $v['goods_status'] == 1 ? '竞拍中' : ($v['goods_status'] == 2 ? '已成交' : ($v['goods_status'] == 3 ? '已流拍' : '已下架'));
        }
        unset($v);
        View::assign([
            'list'       => $list,
            'page_title' => '关注的拍品',
            'tab_active' => 'mine',
            'hide_tabbar' => true,
        ]);
        return View::fetch();
    }

    /**
     * 关注的店铺
     */
    public function follows()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $list = Db::name('seller_follow')->alias('f')
            ->leftJoin('user u', 'f.seller_id = u.id')
            ->field('f.*, u.nickname, u.shop_name, u.avatar')
            ->where('f.user_id', $id)
            ->order('f.id', 'desc')
            ->select()
            ->toArray();
        foreach ($list as &$v) {
            $v['shop_name'] = !empty($v['shop_name']) ? $v['shop_name'] : $v['nickname'];
            $v['goods_count'] = Db::name('goods')->where('seller_id', $v['seller_id'])->count();
            $v['saling_count'] = Db::name('goods')->where('seller_id', $v['seller_id'])->where('status', 1)->count();
        }
        unset($v);
        View::assign([
            'list'       => $list,
            'page_title' => '关注的店铺',
            'tab_active' => 'mine',
            'hide_tabbar' => true,
        ]);
        return View::fetch();
    }

    /**
     * 我的足迹
     */
    public function footprints()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $list = Db::name('browse_history')->alias('h')
            ->leftJoin('goods g', 'h.goods_id = g.id')
            ->field('h.*, g.title, g.cover, g.start_price, g.status as goods_status, g.end_time')
            ->where('h.user_id', $id)
            ->order('h.create_time', 'desc')
            ->limit(200)
            ->select()
            ->toArray();
        foreach ($list as &$v) {
            $top = Db::name('bid_record')->where('goods_id', $v['goods_id'])->where('status', 0)->max('price');
            $v['cur_price'] = $top > 0 ? (float)$top : (float)$v['start_price'];
            $v['status_txt'] = $v['goods_status'] == 1 ? '竞拍中' : ($v['goods_status'] == 2 ? '已成交' : ($v['goods_status'] == 3 ? '已流拍' : '已下架'));
        }
        unset($v);
        View::assign([
            'list'       => $list,
            'page_title' => '我的足迹',
            'tab_active' => 'mine',
            'hide_tabbar' => true,
        ]);
        return View::fetch();
    }

    /**
     * 我的钱包
     */
    public function wallet()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $user = Db::name('user')->find($id);
        $isVirtual = (int)$user['is_virtual'] === 1;

        // 资产总额 = 可用 + 冻结
        $totalAssets = round($user['balance'] + $user['freeze_balance'], 2);
        // 待结算订单（待付款）
        $waitPay = Db::name('order')->where('buyer_id', $id)->where('order_status', 0)->where('pay_status', 0)->count();
        // 已结算订单（已完成）
        $finished = Db::name('order')->where('buyer_id', $id)->where('order_status', 3)->count();
        // 累计成交（买卖合计）
        $dealCount = $user['total_buy'] + $user['total_sell'];

        // 最近流水
        $logs = Db::name('balance_log')->where('user_id', $id)->order('id', 'desc')->limit(8)->select()->toArray();
        // 虚拟会员：余额展示永存金额，不展示流水（不审计）
        if ($isVirtual) {
            $user['balance'] = (float)get_setting('virtual_balance', 0);
            $logs = [];
        }

        View::assign([
            'user'         => $user,
            'is_virtual'   => $isVirtual,
            'total_assets' => number_format($totalAssets, 2),
            'wait_pay'     => $waitPay,
            'finished'     => $finished,
            'deal_count'   => $dealCount,
            'logs'         => $logs,
            'page_title'   => '我的钱包',
            'tab_active'   => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 修改资料
     */
    public function profile()
    {
        $this->checkLogin();
        if ($this->request->isPost()) {
            $nickname = trim($this->request->post('nickname', ''));
            if ($nickname === '') {
                return json(['code' => 0, 'msg' => '请输入昵称']);
            }
            $avatar = trim($this->request->post('avatar', ''));
            if ($avatar !== '' && !preg_match('~^/uploads/[\w\-./]+\.(jpg|jpeg|png|gif|webp)$~i', $avatar)) {
                return json(['code' => 0, 'msg' => '头像地址不合法']);
            }
            $data = [
                'nickname'    => $nickname,
                'update_time' => time(),
            ];
            if ($avatar !== '') {
                $data['avatar'] = $avatar;
            }
            // 卖家可修改店铺名称和企业名称
            if ((int)$this->user['is_seller'] === 1) {
                $shopName = trim($this->request->post('shop_name', ''));
                $companyName = trim($this->request->post('company_name', ''));
                if ($shopName !== '') {
                    $data['shop_name'] = $shopName;
                }
                if ($companyName !== '') {
                    $data['company_name'] = $companyName;
                }
            }
            Db::name('user')->where('id', $this->user['id'])->update($data);
            return json(['code' => 1, 'msg' => '资料已更新']);
        }
        View::assign(['page_title' => '修改资料', 'center_tab' => 'profile', 'tab_active' => 'mine']);
        return View::fetch();
    }

    /**
     * 修改密码
     */
    public function password()
    {
        $this->checkLogin();
        if ($this->request->isPost()) {
            $oldPwd = trim($this->request->post('old_password', ''));
            $newPwd = trim($this->request->post('new_password', ''));
            $newPwd2 = trim($this->request->post('new_password2', ''));

            if ($this->user['password'] !== encrypt_password($oldPwd)) {
                return json(['code' => 0, 'msg' => '原密码错误']);
            }
            if (strlen($newPwd) < 6) {
                return json(['code' => 0, 'msg' => '新密码至少6位']);
            }
            if ($newPwd !== $newPwd2) {
                return json(['code' => 0, 'msg' => '两次密码不一致']);
            }
            Db::name('user')->where('id', $this->user['id'])->update([
                'password'    => encrypt_password($newPwd),
                'update_time' => time(),
            ]);
            return json(['code' => 1, 'msg' => '密码已修改']);
        }
        View::assign(['page_title' => '修改密码', 'center_tab' => 'password', 'tab_active' => 'mine']);
        return View::fetch();
    }

    /**
     * 余额充值（提交申请，后台审核后到账）
     */
    public function recharge()
    {
        $this->checkLogin();
        // 虚拟会员无出入款操作
        if (Db::name('user')->where('id', $this->user['id'])->value('is_virtual')) {
            if ($this->request->isPost()) {
                return json(['code' => 0, 'msg' => '虚拟会员不支持充值']);
            }
            return $this->error('虚拟会员不支持充值操作', '/user/wallet');
        }
        if ($this->request->isPost()) {
            $amount = round((float)$this->request->post('amount', 0), 2);
            $payType = (int)$this->request->post('pay_type', 1);
            if ($amount <= 0 || $amount > 100000) {
                return json(['code' => 0, 'msg' => '请输入正确的充值金额']);
            }
            // 未处理完成的申请只能提交一次
            $pending = Db::name('recharge')->where('user_id', $this->user['id'])->where('status', 0)->find();
            if ($pending) {
                return json(['code' => 0, 'msg' => '您有一笔充值申请正在审核中，请等待审核结果']);
            }
            $now = time();
            Db::name('recharge')->insert([
                'user_id'     => $this->user['id'],
                'amount'      => $amount,
                'pay_type'    => $payType,
                'status'      => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            return json(['code' => 1, 'msg' => '充值申请已提交，请等待平台审核']);
        }

        // 充值记录（含状态）
        $records = Db::name('recharge')->where('user_id', $this->user['id'])->order('id', 'desc')->limit(20)->select()->toArray();
        // 是否有审核中的申请（未处理前禁止重复提交）
        $hasPending = (bool)Db::name('recharge')->where('user_id', $this->user['id'])->where('status', 0)->count();
        $serviceLink = Db::name('setting')->where('name', 'service_link')->value('value');
        View::assign([
            'records'      => $records,
            'has_pending'  => $hasPending,
            'page_title'   => '余额充值',
            'center_tab'   => 'recharge',
            'tab_active'   => 'mine',
            'service_link' => $serviceLink,
        ]);
        return View::fetch();
    }

    /**
     * 充值记录（含状态与拒绝理由）
     */
    public function recharge_log()
    {
        $this->checkLogin();
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 15;
        $query = Db::name('recharge')->where('user_id', $this->user['id']);
        $total = $query->count();
        $records = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        View::assign([
            'records'     => $records,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'page_title'  => '充值记录',
            'center_tab'  => 'recharge',
            'tab_active'  => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 提现记录（含状态与拒绝理由）
     */
    public function withdraw_log()
    {
        $this->checkLogin();
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 15;
        $query = Db::name('withdraw')->where('user_id', $this->user['id']);
        $total = $query->count();
        $records = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        foreach ($records as &$r) {
            $r['account_type_name'] = isset($r['account_type']) ? ['支付宝', '微信', '银行卡', '虚拟货币'][$r['account_type'] - 1] ?? '未知' : '';
        }
        unset($r);
        View::assign([
            'records'     => $records,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'page_title'  => '提现记录',
            'center_tab'  => 'withdraw',
            'tab_active'  => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 站内信列表
     */
    public function messages()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 15;

        $query = Db::name('sys_message')->where('user_id', $id);
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        foreach ($list as &$m) {
            $m['summary'] = mb_substr(preg_replace('/\s+/', ' ', trim($m['content'])), 0, 60);
        }
        unset($m);

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'page_title' => '站内信',
            'center_tab' => 'messages',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 站内信详情（标记已读）
     */
    public function messageDetail()
    {
        $this->checkLogin();
        $id = (int)$this->request->param('id', 0);
        $msg = Db::name('sys_message')->where('id', $id)->where('user_id', $this->user['id'])->find();
        if (!$msg) {
            return $this->error('消息不存在');
        }
        if ($msg['is_read'] == 0) {
            Db::name('sys_message')->where('id', $id)->update(['is_read' => 1]);
            $msg['is_read'] = 1;
        }
        View::assign([
            'msg'        => $msg,
            'page_title' => '消息详情',
            'center_tab' => 'messages',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 余额明细
     */
    public function balance_log()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 15;

        $query = Db::name('balance_log')->where('user_id', $id);
        $total = $query->count();
        $logs = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        View::assign([
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'page_title' => '余额明细',
            'center_tab' => 'balance',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 提现账户绑定（可修改）
     */
    public function payAccount()
    {
        $this->checkLogin();
        // 虚拟会员无出入款操作，不允许绑定提现账户
        if ($this->request->isPost() && Db::name('user')->where('id', $this->user['id'])->value('is_virtual')) {
            return json(['code' => 0, 'msg' => '虚拟会员不支持绑定提现账户']);
        }
        if ($this->request->isPost()) {
            $type = (int)$this->request->post('type', 0);
            $realName = trim($this->request->post('real_name', ''));
            $account = trim($this->request->post('account', ''));
            $bankName = trim($this->request->post('bank_name', ''));
            $qrCode = trim($this->request->post('qr_code', ''));

            if (!in_array($type, [1, 2, 3, 4])) {
                return json(['code' => 0, 'msg' => '请选择提现方式']);
            }
            if ($type == 4) {
                // 虚拟货币：钱包地址必填，网络（链）选填，无需姓名和收款码
                if ($account === '') {
                    return json(['code' => 0, 'msg' => '请填写钱包地址']);
                }
                $realName = '';
                $qrCode = '';
            } else {
                if ($realName === '') {
                    return json(['code' => 0, 'msg' => '请填写姓名']);
                }
                if ($account === '') {
                    return json(['code' => 0, 'msg' => '请填写账号']);
                }
                if ($type === 3) {
                    if ($bankName === '') {
                        return json(['code' => 0, 'msg' => '请填写银行名称']);
                    }
                    $qrCode = '';
                } else {
                    if ($qrCode === '' || !preg_match('~^/uploads/[\w\-./]+\.(jpg|jpeg|png|gif|webp)$~i', $qrCode)) {
                        return json(['code' => 0, 'msg' => '请上传收款码图片']);
                    }
                    $bankName = '';
                }
            }

            $now = time();
            $data = [
                'real_name'   => $realName,
                'account'     => $account,
                'bank_name'   => $bankName,
                'qr_code'     => $qrCode,
                'update_time' => $now,
            ];
            $exists = Db::name('pay_account')->where('user_id', $this->user['id'])->where('type', $type)->find();
            if ($exists) {
                Db::name('pay_account')->where('id', $exists['id'])->update($data);
            } else {
                $data['user_id'] = $this->user['id'];
                $data['type'] = $type;
                $data['create_time'] = $now;
                Db::name('pay_account')->insert($data);
            }
            return json(['code' => 1, 'msg' => '绑定成功']);
        }

        $accounts = Db::name('pay_account')->where('user_id', $this->user['id'])->select()->toArray();
        $map = [];
        foreach ($accounts as $a) {
            $map[$a['type']] = $a;
        }
        View::assign([
            'accounts'   => $map,
            'pa_json'    => json_encode($map, JSON_UNESCAPED_UNICODE),
            'page_title' => '提现账户',
            'center_tab' => 'withdraw',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 提现申请
     */
    public function withdraw()
    {
        $this->checkLogin();
        // 虚拟会员无出入款操作
        if (Db::name('user')->where('id', $this->user['id'])->value('is_virtual')) {
            if ($this->request->isPost()) {
                return json(['code' => 0, 'msg' => '虚拟会员不支持提现']);
            }
            return $this->error('虚拟会员不支持提现操作', '/user/wallet');
        }
        if ($this->request->isPost()) {
            $amount = round((float)$this->request->post('amount', 0), 2);
            $payType = trim($this->request->post('pay_type', ''));
            $typeMap = ['alipay' => 1, 'wechat' => 2, 'bank' => 3, 'usdt' => 4];
            $type = isset($typeMap[$payType]) ? $typeMap[$payType] : 0;

            if ($amount <= 0) {
                return json(['code' => 0, 'msg' => '请输入提现金额']);
            }
            if (!$type) {
                return json(['code' => 0, 'msg' => '请选择提现方式']);
            }

            // 单笔限额（后台设置）
            $min = (float)get_setting('withdraw_min', 0);
            $max = (float)get_setting('withdraw_max', 0);
            if ($min > 0 && $amount < $min) {
                return json(['code' => 0, 'msg' => '单笔提现金额不能低于 ' . number_format($min, 2) . ' 元']);
            }
            if ($max > 0 && $amount > $max) {
                return json(['code' => 0, 'msg' => '单笔提现金额不能超过 ' . number_format($max, 2) . ' 元']);
            }

            $pa = Db::name('pay_account')->where('user_id', $this->user['id'])->where('type', $type)->find();
            if (!$pa) {
                return json(['code' => 0, 'msg' => '请先绑定该提现方式']);
            }

            // 手续费（百分比）
            $feeRate = (float)get_setting('withdraw_fee', 0);
            $fee = round($amount * $feeRate / 100, 2);

            $now = time();
            Db::startTrans();
            try {
                // 锁行重查余额，提交即扣减冻结，防止重复提交多笔
                $user = Db::name('user')->where('id', $this->user['id'])->lock(true)->find();
                // 只能同时存在一笔待审核提现，审核通过后方可提交下一笔
                $pending = Db::name('withdraw')->where('user_id', $user['id'])->where('status', 0)->find();
                if ($pending) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '您有一笔提现正在审核中，审核通过后方可提交下一笔']);
                }
                if ($user['balance'] < $amount) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '提现金额超过可用余额']);
                }
                $newBalance = round($user['balance'] - $amount, 2);
                Db::name('user')->where('id', $user['id'])->update([
                    'balance'     => $newBalance,
                    'update_time' => $now,
                ]);
                Db::name('withdraw')->insert([
                    'user_id'      => $this->user['id'],
                    'amount'       => $amount,
                    'fee'          => $fee,
                    'account_type' => $type,
                    'account'      => $pa['account'],
                    'account_name' => $pa['real_name'],
                    'bank_name'    => $pa['bank_name'],
                    'qr_code'      => $pa['qr_code'],
                    'status'       => 0,
                    'create_time'  => $now,
                    'update_time'  => $now,
                ]);
                $this->addBalanceLog($user['id'], 'withdraw', -$amount, $newBalance, '提现申请冻结：' . $amount . '元');
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '提交失败，请重试']);
            }

            return json(['code' => 1, 'msg' => '提现申请已提交，等待平台审核']);
        }

        // 提现记录
        $records = Db::name('withdraw')->where('user_id', $this->user['id'])->order('id', 'desc')->limit(20)->select()->toArray();
        // 已绑定账户
        $payAccounts = Db::name('pay_account')->where('user_id', $this->user['id'])->select()->toArray();
        $paMap = [];
        foreach ($payAccounts as $pa) {
            $paMap[$pa['type']] = $pa;
        }
        // 是否有待审核提现（审核通过后才能提交下一笔）
        $pending = Db::name('withdraw')->where('user_id', $this->user['id'])->where('status', 0)->find();
        View::assign([
            'records'      => $records,
            'fee_rate'     => (float)get_setting('withdraw_fee', 0),
            'pay_accounts' => $paMap,
            'pa_json'      => json_encode($paMap, JSON_UNESCAPED_UNICODE),
            'pending'      => $pending ? 1 : 0,
            'withdraw_min' => (float)get_setting('withdraw_min', 0),
            'withdraw_max' => (float)get_setting('withdraw_max', 0),
            'page_title'   => '申请提现',
            'center_tab'   => 'withdraw',
            'tab_active'   => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 我的出价
     */
    public function bids()
    {
        $this->checkLogin();
        $id = $this->user['id'];
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 15;

        $query = Db::name('bid_record')->alias('b')
            ->leftJoin('goods g', 'b.goods_id = g.id')
            ->field('b.*, g.title, g.cover, g.status as goods_status, g.end_time, g.final_price, g.winner_id')
            ->where('b.user_id', $id);
        $total = $query->count();
        $list = $query->order('b.id', 'desc')->page($page, $limit)->select()->toArray();

        // 领先判断：每个商品价格最高（同价先出优先）的那条出价标记领先，其余出局（与详情页一致）
        $goodsIds = array_values(array_unique(array_column($list, 'goods_id')));
        $leadMap  = [];
        if ($goodsIds) {
            $tops = Db::name('bid_record')
                ->whereIn('goods_id', $goodsIds)
                ->field('goods_id, id')
                ->order('price', 'desc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            foreach ($tops as $t) {
                if (!isset($leadMap[$t['goods_id']])) {
                    $leadMap[$t['goods_id']] = $t['id'];
                }
            }
        }
        foreach ($list as &$b) {
            $b['is_lead'] = (!empty($leadMap[$b['goods_id']]) && $leadMap[$b['goods_id']] == $b['id']) ? 1 : 0;
        }
        unset($b);

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'page_title' => '我的出价',
            'center_tab' => 'bids',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 收货地址管理
     */
    public function address()
    {
        $this->checkLogin();
        $id = $this->user['id'];

        if ($this->request->isPost()) {
            $act = $this->request->post('act', 'save');
            if ($act === 'delete') {
                $addrId = (int)$this->request->post('id');
                Db::name('user_address')->where('id', $addrId)->where('user_id', $id)->delete();
                return json(['code' => 1, 'msg' => '已删除']);
            }
            return $this->saveAddress();
        }

        $addresses = Db::name('user_address')->where('user_id', $id)->order('is_default', 'desc')->order('id', 'desc')->select()->toArray();
        View::assign([
            'addresses'  => $addresses,
            'page_title' => '收货地址',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 添加/编辑地址
     */
    public function address_edit()
    {
        $this->checkLogin();
        $id = $this->user['id'];

        if ($this->request->isPost()) {
            return $this->saveAddress();
        }

        $addrId = (int)$this->request->param('id', 0);
        $addr = null;
        if ($addrId > 0) {
            $addr = Db::name('user_address')->where('id', $addrId)->where('user_id', $id)->find();
        }
        View::assign([
            'addr'       => $addr,
            'page_title' => $addr ? '编辑地址' : '添加地址',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 保存地址
     */
    protected function saveAddress()
    {
        $id = $this->user['id'];
        $addrId = (int)$this->request->post('id', 0);
        $name = trim($this->request->post('name', ''));
        $mobile = trim($this->request->post('mobile', ''));
        $province = trim($this->request->post('province', ''));
        $city = trim($this->request->post('city', ''));
        $district = trim($this->request->post('district', ''));
        $address = trim($this->request->post('address', ''));
        $isDefault = (int)$this->request->post('is_default', 0);

        if ($name === '' || $mobile === '' || $address === '') {
            return json(['code' => 0, 'msg' => '请填写完整收货信息']);
        }
        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return json(['code' => 0, 'msg' => '联系电话格式不正确']);
        }

        $fullAddress = trim($province . ' ' . $city . ' ' . $district . ' ' . $address);
        if ($addrId > 0) {
            // 该地址是否属于当前用户
            $exists = Db::name('user_address')->where('id', $addrId)->where('user_id', $id)->find();
            if (!$exists) {
                return json(['code' => 0, 'msg' => '地址不存在']);
            }
            Db::name('user_address')->where('id', $addrId)->update([
                'name' => $name, 'mobile' => $mobile, 'province' => $province,
                'city' => $city, 'district' => $district, 'address' => $address,
                'update_time' => time(),
            ]);
            if ($isDefault) {
                Db::name('user_address')->where('user_id', $id)->update(['is_default' => 0]);
                Db::name('user_address')->where('id', $addrId)->update(['is_default' => 1]);
            }
        } else {
            $newId = Db::name('user_address')->insertGetId([
                'user_id' => $id, 'name' => $name, 'mobile' => $mobile,
                'province' => $province, 'city' => $city, 'district' => $district,
                'address' => $address, 'is_default' => $isDefault,
                'create_time' => time(), 'update_time' => time(),
            ]);
            if ($isDefault) {
                Db::name('user_address')->where('user_id', $id)->update(['is_default' => 0]);
                Db::name('user_address')->where('id', $newId)->update(['is_default' => 1]);
            }
        }
        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 实名认证（GET 表单 / POST 提交）
     */
    public function auth()
    {
        $this->checkLogin();
        $id = $this->user['id'];

        if ($this->request->isPost()) {
            $realName = trim($this->request->post('real_name', ''));
            $idCard   = strtoupper(trim($this->request->post('id_card', '')));
            $front    = trim($this->request->post('id_card_front', ''));
            $back     = trim($this->request->post('id_card_back', ''));

            if ($realName === '') {
                return json(['code' => 0, 'msg' => '请输入真实姓名']);
            }
            if (!preg_match('/^\d{17}[\dX]$/', $idCard)) {
                return json(['code' => 0, 'msg' => '身份证号格式不正确']);
            }
            if ($front === '' || $back === '') {
                return json(['code' => 0, 'msg' => '请上传身份证正反面照片']);
            }
            if ($this->user['auth_status'] == 1) {
                return json(['code' => 0, 'msg' => '实名认证审核中，请勿重复提交']);
            }

            Db::name('user')->where('id', $id)->update([
                'real_name'    => $realName,
                'id_card'      => $idCard,
                'id_card_front'=> $front,
                'id_card_back' => $back,
                'auth_status'  => 1,
                'auth_reason'  => '',
                'update_time'  => time(),
            ]);

            // 刷新 session 中的用户信息
            $user = Db::name('user')->find($id);
            unset($user['password']);
            session('user', $user);

            return json(['code' => 1, 'msg' => '认证资料已提交，请等待平台审核']);
        }

        $user = Db::name('user')->find($id);
        unset($user['password']);
        View::assign([
            'user'       => $user,
            'page_title' => '实名认证',
            'center_tab' => 'seller',
            'tab_active' => 'mine',
            'hide_tabbar'=> true,
        ]);
        return View::fetch();
    }

    /**
     * 我的邀请码
     */
    public function invite()
    {
        $this->checkLogin();
        $user = Db::name('user')->find($this->user['id']);
        unset($user['password']);
        View::assign([
            'user'       => $user,
            'page_title' => '我的邀请码',
            'center_tab' => 'seller',
            'tab_active' => 'mine',
            'hide_tabbar'=> true,
        ]);
        return View::fetch();
    }
}

<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\Lang;
use think\facade\View;

class Seller extends Base
{
    /**
     * 卖家中心首页（入驻状态判断）
     */
    public function apply()
    {
        $this->checkLogin();
        // 入驻状态：只有提交过入驻申请（填写了店铺名称）才进入审核流程
        $apply = null;
        if ($this->user['seller_check'] == 1 || $this->user['is_seller'] == 1) {
            $apply = ['status' => 1];
        } elseif ($this->user['seller_check'] == 2) {
            $apply = ['status' => 2, 'reason' => lang('审核未通过，请联系管理员')];
        } elseif (!empty($this->user['shop_name'])) {
            // 已提交申请，待审核
            $apply = ['status' => 0];
        }
        View::assign([
            'apply'      => $apply,
            'user'       => $this->user,
            'seller_auto'=> seller_auto_open() ? 1 : 0,
            'lic_list'   => !empty($this->user['license_img']) ? explode(',', $this->user['license_img']) : [],
            'page_title' => lang('卖家入驻'),
            'center_tab' => 'seller',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 一键开通卖家（后台「卖家入驻审核」设为自动开通时可用）
     * 不需要填写任何资料，店铺名称自动生成
     */
    public function quickApply()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        if (!seller_auto_open()) {
            return json(['code' => 0, 'msg' => lang('平台已开启卖家入驻审核，请提交资料后等待审核')]);
        }
        if ((int)$this->user['is_seller'] === 1) {
            return json(['code' => 0, 'msg' => lang('您已经是卖家了')]);
        }
        // 实名认证仍是前置条件，和提交资料入驻的规则一致
        if ((int)$this->user['auth_status'] !== 2) {
            return json(['code' => 0, 'msg' => lang('请先完成实名认证后再申请入驻')]);
        }

        $id   = (int)$this->user['id'];
        $data = [
            'is_seller'    => 1,
            'seller_check' => 1,
            'update_time'  => time(),
        ];
        if (trim((string)$this->user['shop_name']) === '') {
            $data['shop_name'] = $this->buildShopName($id);
        }
        Db::name('user')->where('id', $id)->where('is_seller', 0)->update($data);

        return json(['code' => 1, 'msg' => lang('已开通卖家权限'), 'url' => '/seller/apply']);
    }

    /**
     * 自动生成一个不重复的店铺名称
     */
    protected function buildShopName($id)
    {
        $base = trim((string)$this->user['nickname']);
        if ($base === '') {
            $base = lang('用户') . substr(explode('@', user_account($this->user))[0], -4);
        }
        $base = mb_substr($base, 0, 20) . lang('的店铺');
        $name = $base;
        $i    = 1;
        while (Db::name('user')->where('shop_name', $name)->where('id', '<>', $id)->find()) {
            $name = $base . ++$i;
            if ($i > 50) {
                $name = $base . $id;
                break;
            }
        }
        return $name;
    }
    /**
     * 提交入驻申请
     */
    public function doApply()
    {
        $this->checkLogin();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        // 实名认证拦截：只有认证通过才能申请成为卖家
        if ($this->user['auth_status'] != 2) {
            return json(['code' => 0, 'msg' => lang('请先完成实名认证后再申请入驻')]);
        }
        $id = $this->user['id'];

        $realName = trim($this->request->post('realname', ''));
        $mobile = trim($this->request->post('mobile', ''));
        $shopName = trim($this->request->post('shop_name', ''));
        $companyName = trim($this->request->post('company_name', ''));
        $sellerIntro = trim($this->request->post('seller_intro', ''));
        $licenseImgs = $this->request->post('license_img', '');
        if (is_array($licenseImgs)) {
            $licenseImgs = implode(',', array_values(array_filter($licenseImgs)));
        }
        $licenseImgs = trim($licenseImgs, ',');
        if ($shopName === '' || $companyName === '') {
            return json(['code' => 0, 'msg' => lang('请填写完整信息')]);
        }
        $licArr = $licenseImgs === '' ? [] : explode(',', $licenseImgs);
        foreach ($licArr as $img) {
            if (!is_upload_image($img)) {
                return json(['code' => 0, 'msg' => lang('图片地址不合法，请重新上传')]);
            }
        }
        if (count($licArr) > 5) {
            return json(['code' => 0, 'msg' => lang('企业资料最多上传5张图片')]);
        }
        // 姓名/手机号以实名认证信息为准（实名已通过，姓名不可更改）
        $realName = $this->user['real_name'];
        $mobile = (string)$this->user['mobile'];

        // 是否需要人工审核
        $needCheck = (int)get_setting('seller_check', 1) === 1;

        $data = [
            'real_name'    => $realName,
            'shop_name'    => $shopName,
            'company_name' => $companyName,
            'license_img'  => $licenseImgs,
            'seller_intro' => mb_substr($sellerIntro, 0, 200),
            'update_time'  => time(),
        ];
        // 首次提交（待审核/未入驻）与审核被拒后重新提交：重新进入审核流程
        if ($this->user['seller_check'] == 0 || $this->user['seller_check'] == 2) {
            $data['seller_check'] = $needCheck ? 0 : 1;
            $data['is_seller']    = $needCheck ? 0 : 1;
        }
        Db::name('user')->where('id', $id)->update($data);

        $msg = $needCheck ? lang('入驻申请已提交，请等待平台审核') : lang('已开通卖家权限');
        return json(['code' => 1, 'msg' => $msg]);
    }

    /**
     * 发布商品
     */
    public function goods_add()
    {
        $this->checkSeller();
        if ($this->request->isPost()) {
            $title = trim($this->request->post('title', ''));
            $categoryId = (int)$this->request->post('category_id', 0);
            $content = clean_html(trim($this->request->post('content', '')));
            $startPrice = round((float)$this->request->post('start_price', 0), 2);
            $raisePrice = round((float)$this->request->post('raise_price', 0), 2);
            $reservePrice = round((float)$this->request->post('reserve_price', 0), 2);
            $deposit = round((float)$this->request->post('deposit', 0), 2);
            $endTime = trim($this->request->post('end_time', ''));
            // 延时秒数 0 ~ 86400；金额类字段上限 9999 万，截拍最多一年后
            $delaySeconds = max(0, min(86400, (int)$this->request->post('delay_seconds', 0)));
            foreach (['起拍价' => $startPrice, '加价幅度' => $raisePrice, '保留价' => $reservePrice, '保证金' => $deposit] as $label => $val) {
                if ($val < 0 || $val > 99999999) {
                    return json(['code' => 0, 'msg' => lang($label) . lang('金额超出允许范围')]);
                }
            }
            $cover = trim($this->request->post('cover', ''));
            $images = $this->request->post('images', []);
            if (is_string($images)) {
                $images = $images === '' ? [] : explode(',', $images);
            }

            if ($title === '') {
                return json(['code' => 0, 'msg' => lang('请输入商品标题')]);
            }
            if ($categoryId <= 0) {
                return json(['code' => 0, 'msg' => lang('请选择分类')]);
            }
            if ($startPrice <= 0) {
                return json(['code' => 0, 'msg' => lang('起拍价必须大于0')]);
            }
            if ($raisePrice <= 0) {
                return json(['code' => 0, 'msg' => lang('加价幅度必须大于0')]);
            }
            if ($reservePrice > 0 && $reservePrice < $startPrice) {
                return json(['code' => 0, 'msg' => lang('保留价不能低于起拍价')]);
            }
            if ($endTime === '') {
                return json(['code' => 0, 'msg' => lang('请选择截拍时间')]);
            }
            $st = time(); // 默认提交即开拍
            $et = strtotime($endTime);
            if (!$et || $et <= $st) {
                return json(['code' => 0, 'msg' => lang('截拍时间必须晚于当前时间')]);
            }
            if ($et - time() < 60) {
                return json(['code' => 0, 'msg' => lang('截拍时间必须晚于当前时间1分钟以上')]);
            }
            if ($et - time() > 366 * 86400) {
                return json(['code' => 0, 'msg' => lang('截拍时间最多为一年内')]);
            }

            $images = is_array($images) ? array_values(array_filter($images)) : [];
            foreach (array_merge($images, $cover !== '' ? [$cover] : []) as $img) {
                if (!is_upload_image($img)) {
                    return json(['code' => 0, 'msg' => lang('图片地址不合法，请重新上传')]);
                }
            }
            if (count($images) < 4) {
                return json(['code' => 0, 'msg' => lang('请至少上传4张不同角度的商品照片')]);
            }
            if (empty($cover) && !empty($images)) {
                $cover = $images[0];
            }

            $now = time();
            // 发布商品是否需审核（系统设置 goods_check）
            $goodsStatus = (int)get_setting('goods_check', 1) === 1 ? 0 : 1;
            Db::name('goods')->insert([
                'seller_id'       => $this->user['id'],
                'category_id'     => $categoryId,
                'title'           => $title,
                'cover'           => $cover,
                'images'          => !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
                'content'         => $content,
                'start_price'     => $startPrice,
                'raise_price'     => $raisePrice,
                'reserve_price'   => $reservePrice,
                'deposit'         => $deposit,
                'start_time'      => $st,
                'end_time'        => $et,
                'delay_seconds'   => $delaySeconds,
                'status'          => $goodsStatus,
                'create_time'     => $now,
                'update_time'     => $now,
            ]);

            return json(['code' => 1, 'msg' => $goodsStatus === 0 ? lang('发布成功，等待平台审核') : lang('发布成功')]);
        }

        $categories = active_categories();
        // 多语言映射分类名
        $langField = Lang::getLangSet() === 'zh-tw' ? 'name_tw' : (Lang::getLangSet() === 'en-us' ? 'name_en' : 'name');
        foreach ($categories as &$c) {
            $c['name'] = !empty($c[$langField]) ? $c[$langField] : $c['name'];
        }
        unset($c);
        $publishProtocol = (string)get_setting('publish_protocol');
        // 多语言发布协议（未填则回退简体）
        $langSet = Lang::getLangSet();
        if ($langSet !== 'zh-cn') {
            $alt = (string)Db::name('setting')->where('name', 'publish_protocol' . ($langSet === 'zh-tw' ? '_tw' : '_en'))->value('value');
            if ($alt !== '') {
                $publishProtocol = $alt;
            }
        }
        View::assign([
            'categories'  => $categories,
            'publish_protocol' => $publishProtocol,
            'page_title'  => lang('发布商品'),
            'center_tab'  => 'seller',
            'tab_active'  => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 我的商品
     */
    public function goods_list()
    {
        $this->checkSeller();
        $id = $this->user['id'];
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $status = $this->request->param('status', '');

        $query = Db::name('goods')->where('seller_id', $id);
        if ($status !== '') {
            if ((int)$status === 3) {
                // 已流拍 tab：同时展示流拍(3)与审核拒绝(5)
                $query->whereIn('status', [3, 5]);
            } else {
                $query->where('status', (int)$status);
            }
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        // 流拍数量：>0 时页面显示「一键重新上架」
        $failCount = (int)Db::name('goods')->where('seller_id', $id)->where('status', 3)->count();

        // 当前价 / 当前最高竞拍价（仅统计有效出价 status=0；已成交的看 final_price）
        $tops = bid_top_prices(array_column($list, 'id'));
        foreach ($list as &$g) {
            $top = (float)($tops[(int)$g['id']] ?? 0);
            $g['top_price']     = $top;
            $g['has_bid']       = $top > 0 ? 1 : 0;
            $g['current_price'] = max($top, (float)$g['start_price']);
        }
        unset($g);

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'status'     => $status,
            'fail_count' => $failCount,
            'now'        => time(),
            'page_title' => lang('我的商品'),
            'center_tab' => 'seller',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 上架/下架/流拍重新上架
     */
    public function set_status()
    {
        $this->checkSeller();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        $goodsId = (int)$this->request->post('id', 0);
        $status = (int)$this->request->post('status', 1);
        $endTime = trim((string)$this->request->post('end_time', ''));

        $goods = Db::name('goods')->where('id', $goodsId)->where('seller_id', $this->user['id'])->find();
        if (!$goods) {
            return json(['code' => 0, 'msg' => lang('商品不存在')]);
        }
        if (!in_array($status, [1, 4])) {
            return json(['code' => 0, 'msg' => lang('参数错误')]);
        }
        if ($goods['status'] == 2) {
            return json(['code' => 0, 'msg' => lang('已成交的商品不能操作')]);
        }
        // 待审核 / 审核拒绝的商品只能由平台审核通过后上架，卖家不能自行操作
        if (in_array((int)$goods['status'], [0, 5], true)) {
            return json(['code' => 0, 'msg' => lang('商品尚未通过审核，不能操作')]);
        }

        // 流拍商品重新上架：重置拍卖时间、清理旧出价记录
        if ($goods['status'] == 3 && $status == 1) {
            if ($endTime === '') {
                return json(['code' => 0, 'msg' => lang('请选择结束时间')]);
            }
            $et = strtotime(str_replace('T', ' ', $endTime));
            if (!$et || $et <= time() + 60) {
                return json(['code' => 0, 'msg' => lang('结束时间需晚于当前时间')]);
            }
            Db::name('bid_record')->where('goods_id', $goodsId)->delete();
            Db::name('goods')->where('id', $goodsId)->update([
                'status'      => 1,
                'start_time'  => time(),
                'end_time'    => $et,
                'bid_count'   => 0,
                'winner_id'   => 0,
                'final_price' => 0,
                'update_time' => time(),
            ]);
            return json(['code' => 1, 'msg' => lang('已重新上架')]);
        }

        $refunded = 0;
        if ($status == 4 && $goods['status'] == 1) {
            // 拍卖中下架：视同流拍，退回所有买家的保证金
            $refunded = release_goods_bids($goodsId, '卖家下架');
        }
        if ($status == 1 && $goods['status'] == 4) {
            // 下架后重新上架：旧出价已在下架时作废，清掉记录并归零出价次数
            Db::name('bid_record')->where('goods_id', $goodsId)->delete();
            $reset = ['bid_count' => 0, 'winner_id' => 0, 'final_price' => 0];
            // 截拍时间已过：按原拍卖时长（至少 1 小时）从现在重新计时，避免上架后立刻被结算成流拍
            if ((int)$goods['end_time'] <= time() + 60) {
                $duration = max(3600, (int)$goods['end_time'] - (int)$goods['start_time']);
                $reset['start_time'] = time();
                $reset['end_time']   = time() + $duration;
            }
            Db::name('goods')->where('id', $goodsId)->update($reset);
        }
        Db::name('goods')->where('id', $goodsId)->update([
            'status'      => $status,
            'update_time' => time(),
        ]);
        if ($status == 4) {
            return json(['code' => 1, 'msg' => $refunded > 0 ? lang('已下架，已退回 %d 笔买家保证金', [$refunded]) : lang('已下架')]);
        }
        return json(['code' => 1, 'msg' => lang('已上架')]);
    }

    /**
     * 删除商品（仅未审核/已下架/审核拒绝）
     */
    public function delete()
    {
        $this->checkSeller();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        $goodsId = (int)$this->request->post('id', 0);
        $goods = Db::name('goods')->where('id', $goodsId)->where('seller_id', $this->user['id'])->find();
        if (!$goods) {
            return json(['code' => 0, 'msg' => lang('商品不存在')]);
        }
        if (in_array($goods['status'], [1, 2])) {
            return json(['code' => 0, 'msg' => lang('拍卖中/已成交商品不能删除')]);
        }
        release_goods_bids($goodsId, '卖家删除');
        Db::name('bid_record')->where('goods_id', $goodsId)->delete();
        Db::name('goods')->where('id', $goodsId)->delete();
        return json(['code' => 1, 'msg' => lang('已删除')]);
    }

    /**
     * 我的订单（卖家视角：待发货/已发货）
     */
    public function orders()
    {
        $this->checkSeller();
        $id = $this->user['id'];
        $page = max((int)$this->request->param('page', 1), 1);
        $limit = 10;
        $orderStatus = $this->request->param('order_status', '');

        $query = Db::name('order')->where('seller_id', $id);
        if ($orderStatus !== '') {
            $query->where('order_status', (int)$orderStatus);
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        // 每件拍品的起拍价（订单 price 即成交价）
        $goodsIds   = array_values(array_unique(array_map('intval', array_column($list, 'goods_id'))));
        $startPrice = $goodsIds ? Db::name('goods')->whereIn('id', $goodsIds)->column('start_price', 'id') : [];
        // 状态名 + 买家名
        $statusMap = [0 => lang('待付款'), 1 => lang('待发货'), 2 => lang('待收货'), 3 => lang('已完成'), 4 => lang('已取消'), 5 => lang('售后中')];
        $buyerIds  = array_values(array_unique(array_map('intval', array_column($list, 'buyer_id'))));
        $buyerName = $buyerIds ? Db::name('user')->whereIn('id', $buyerIds)->column('nickname', 'id') : [];
        foreach ($list as &$o) {
            $o['status_name'] = $statusMap[$o['order_status']] ?? lang('未知');
            $o['start_price'] = (float)($startPrice[(int)$o['goods_id']] ?? 0);
            if (empty($o['buyer_name'])) {
                $o['buyer_name'] = $buyerName[(int)$o['buyer_id']] ?? lang('匿名');
            }
        }
        unset($o);

        View::assign([
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'order_status'=> $orderStatus,
            'page_title'  => lang('卖家订单'),
            'center_tab'  => 'seller_orders',
            'tab_active'  => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 发货
     */
    public function ship()
    {
        $this->checkSeller();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        $orderId = (int)$this->request->post('id', 0);
        $company = trim($this->request->post('ship_company', ''));
        $shipNo = trim($this->request->post('ship_no', ''));

        $order = Db::name('order')->where('id', $orderId)->where('seller_id', $this->user['id'])->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => lang('订单不存在')]);
        }
        if ($order['order_status'] != 1) {
            return json(['code' => 0, 'msg' => lang('订单状态不正确')]);
        }
        if ($company === '' || $shipNo === '') {
            return json(['code' => 0, 'msg' => lang('请填写快递公司和单号')]);
        }

        $n = Db::name('order')->where('id', $orderId)->where('seller_id', $this->user['id'])->where('order_status', 1)->update([
            'ship_company' => mb_substr($company, 0, 50),
            'ship_no'      => mb_substr($shipNo, 0, 50),
            'ship_time'    => time(),
            'order_status' => 2,
            'update_time'  => time(),
        ]);
        if ($n !== 1) {
            return json(['code' => 0, 'msg' => lang('订单状态不正确')]);
        }
        return json(['code' => 1, 'msg' => lang('发货成功')]);
    }    /**
     * 一键重新上架：把当前卖家所有流拍(3)的拍品重新开拍
     * end_time 为统一的结束时间；stagger=1 时每件在此基础上随机延后 0～6 小时，避免同时截拍
     */
    public function relist_all()
    {
        $this->checkSeller();
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求方式错误')]);
        }
        $endTime = trim((string)$this->request->post('end_time', ''));
        $stagger = (int)$this->request->post('stagger', 1) === 1;
        if ($endTime === '') {
            return json(['code' => 0, 'msg' => lang('请选择结束时间')]);
        }
        $et = strtotime(str_replace('T', ' ', $endTime));
        if (!$et || $et <= time() + 60) {
            return json(['code' => 0, 'msg' => lang('结束时间需晚于当前时间')]);
        }
        $ids = Db::name('goods')->where('seller_id', $this->user['id'])->where('status', 3)->column('id');
        if (empty($ids)) {
            return json(['code' => 0, 'msg' => lang('没有流拍的拍品')]);
        }
        $now = time();
        Db::startTrans();
        try {
            Db::name('bid_record')->whereIn('goods_id', $ids)->delete();
            foreach ($ids as $gid) {
                Db::name('goods')->where('id', $gid)->update([
                    'status'      => 1,
                    'start_time'  => $now,
                    'end_time'    => $stagger ? $et + mt_rand(0, 6 * 3600) : $et,
                    'bid_count'   => 0,
                    'winner_id'   => 0,
                    'final_price' => 0,
                    'update_time' => $now,
                ]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => lang('操作失败：') . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => lang('已重新上架 %d 件拍品', [count($ids)]), 'count' => count($ids)]);
    }
}

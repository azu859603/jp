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
            'lic_list'   => !empty($this->user['license_img']) ? explode(',', $this->user['license_img']) : [],
            'page_title' => lang('卖家入驻'),
            'center_tab' => 'seller',
            'tab_active' => 'mine',
        ]);
        return View::fetch();
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
        if (count($licArr) > 5) {
            return json(['code' => 0, 'msg' => lang('企业资料最多上传5张图片')]);
        }
        // 姓名/手机号以实名认证信息为准（实名已通过，姓名不可更改）
        $realName = $this->user['real_name'];
        $mobile = $this->user['mobile'];

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
            $commissionRate = round((float)$this->request->post('commission_rate', 0), 2);
            $endTime = trim($this->request->post('end_time', ''));
            $delaySeconds = (int)$this->request->post('delay_seconds', 0);
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

            $images = is_array($images) ? array_values(array_filter($images)) : [];
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
                'commission_rate' => $commissionRate,
                'start_time'      => $st,
                'end_time'        => $et,
                'delay_seconds'   => $delaySeconds,
                'is_free_shipping' => (int)$this->request->post('is_free_shipping', 0),
                'is_featured'      => (int)$this->request->post('is_featured', 0),
                'status'          => $goodsStatus,
                'create_time'     => $now,
                'update_time'     => $now,
            ]);

            return json(['code' => 1, 'msg' => $goodsStatus === 0 ? lang('发布成功，等待平台审核') : lang('发布成功')]);
        }

        $categories = Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        // 多语言映射分类名
        $langField = Lang::getLangSet() === 'zh-tw' ? 'name_tw' : (Lang::getLangSet() === 'en-us' ? 'name_en' : 'name');
        foreach ($categories as &$c) {
            $c['name'] = !empty($c[$langField]) ? $c[$langField] : $c['name'];
        }
        unset($c);
        $publishProtocol = (string)Db::name('setting')->where('name', 'publish_protocol')->value('value');
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

        // 当前价
        foreach ($list as &$g) {
            $top = Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->max('price');
            $g['current_price'] = max((float)$top, (float)$g['start_price']);
        }
        unset($g);

        View::assign([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'status'     => $status,
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

        Db::name('goods')->where('id', $goodsId)->update([
            'status'      => $status,
            'update_time' => time(),
        ]);
        return json(['code' => 1, 'msg' => $status == 1 ? lang('已上架') : lang('已下架')]);
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
        // 状态名 + 买家名
        $statusMap = [0 => lang('待付款'), 1 => lang('待发货'), 2 => lang('待收货'), 3 => lang('已完成'), 4 => lang('已取消'), 5 => lang('售后中')];
        foreach ($list as &$o) {
            $o['status_name'] = $statusMap[$o['order_status']] ?? lang('未知');
            if (empty($o['buyer_name'])) {
                $buyer = Db::name('user')->where('id', $o['buyer_id'])->field('nickname')->find();
                $o['buyer_name'] = $buyer ? $buyer['nickname'] : lang('匿名');
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

        Db::name('order')->where('id', $orderId)->update([
            'ship_company' => $company,
            'ship_no'      => $shipNo,
            'ship_time'    => time(),
            'order_status' => 2,
            'update_time'  => time(),
        ]);
        return json(['code' => 1, 'msg' => lang('发货成功')]);
    }
}

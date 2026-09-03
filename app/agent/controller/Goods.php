<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 产品管理
 *
 * 从主后台 Goods 模块迁移，数据范围收口为「卖家 ∈ 我的一级下级」：
 * - 所有列表查询从 goodsQuery() 起手；
 * - 所有按 id 的操作（详情/审核/上下架/删除）先过 assertMyGoods()；
 * - 代发布的目标店铺必须在 sellerIds() 内；
 * - 手机号一律脱敏后下发。
 */
class Goods extends Base
{
    /**
     * 产品列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            $keyword     = trim($this->request->param('keyword', ''));
            $sellerKw    = trim($this->request->param('seller_kw', ''));
            $status      = $this->request->param('status', '');
            $categoryId  = $this->request->param('category_id', '');
            $createStart = trim($this->request->param('create_start', ''));
            $createEnd   = trim($this->request->param('create_end', ''));

            // 起手即锁定卖家范围，后续条件只能收窄
            $query = $this->goodsQuery('g')
                ->leftJoin('user u', 'g.seller_id = u.id')
                ->leftJoin('category c', 'g.category_id = c.id')
                ->field('g.*, u.mobile as seller_mobile, u.nickname as seller_name, c.name as category_name');

            if ($keyword !== '') {
                $query->whereLike('g.title', "%{$keyword}%");
            }
            if ($sellerKw !== '') {
                $query->where(function ($q) use ($sellerKw) {
                    $q->whereLike('u.nickname', "%{$sellerKw}%")
                        ->whereOr('u.mobile', 'like', "%{$sellerKw}%");
                });
            }
            if ($status !== '') {
                $query->where('g.status', (int)$status);
            }
            if ($categoryId !== '') {
                $query->where('g.category_id', (int)$categoryId);
            }
            if ($createStart !== '') {
                $ts = strtotime($createStart . ' 00:00:00');
                if ($ts) {
                    $query->where('g.create_time', '>=', $ts);
                }
            }
            if ($createEnd !== '') {
                $ts = strtotime($createEnd . ' 23:59:59');
                if ($ts) {
                    $query->where('g.create_time', '<=', $ts);
                }
            }

            $total = $query->count();
            $list  = $query->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
            foreach ($list as &$g) {
                $g['seller_mobile'] = $this->maskMobile($g['seller_mobile']);
            }
            unset($g);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        $categories = Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        $sellerIds  = $this->sellerIds();
        $sellers    = empty($sellerIds) ? [] : Db::name('user')
            ->whereIn('id', $sellerIds)
            ->field('id,nickname,mobile')
            ->order('id', 'desc')
            ->select()
            ->toArray();
        foreach ($sellers as &$s) {
            $s['mobile'] = $this->maskMobile($s['mobile']);
        }
        unset($s);

        View::assign([
            'categories'  => $categories,
            'sellers'     => $sellers,
            'menu_active' => '/agent/goods/index',
        ]);
        return View::fetch();
    }

    /**
     * 代发布产品（发到团队内卖家的店铺）
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $sellerId       = (int)$this->request->post('seller_id', 0);
        $title          = trim($this->request->post('title', ''));
        $categoryId     = (int)$this->request->post('category_id', 0);
        $content        = clean_html(trim($this->request->post('content', '')));
        $startPrice     = round((float)$this->request->post('start_price', 0), 2);
        $raisePrice     = round((float)$this->request->post('raise_price', 0), 2);
        $reservePrice   = round((float)$this->request->post('reserve_price', 0), 2);
        $deposit        = round((float)$this->request->post('deposit', 0), 2);
        $commissionRate = round((float)$this->request->post('commission_rate', 0), 2);
        $endTime        = trim($this->request->post('end_time', ''));
        $delaySeconds   = (int)$this->request->post('delay_seconds', 0);
        $cover          = trim($this->request->post('cover', ''));
        $images         = $this->request->post('images', []);
        if (is_string($images)) {
            $images = $images === '' ? [] : explode(',', $images);
        }

        // 目标店铺必须是我团队内已审核的卖家
        if (!in_array($sellerId, $this->sellerIds(), true)) {
            return json(['code' => 0, 'msg' => '只能发布到您团队内已审核通过的卖家店铺']);
        }
        $seller = Db::name('user')->where('id', $sellerId)->find();
        if ($title === '') {
            return json(['code' => 0, 'msg' => '请输入产品标题']);
        }
        if ($categoryId <= 0 || !Db::name('category')->where('id', $categoryId)->where('status', 1)->count()) {
            return json(['code' => 0, 'msg' => '请选择有效的分类']);
        }
        if ($startPrice <= 0) {
            return json(['code' => 0, 'msg' => '起拍价必须大于0']);
        }
        if ($raisePrice <= 0) {
            return json(['code' => 0, 'msg' => '加价幅度必须大于0']);
        }
        if ($reservePrice > 0 && $reservePrice < $startPrice) {
            return json(['code' => 0, 'msg' => '保留价不能低于起拍价']);
        }
        if ($endTime === '') {
            return json(['code' => 0, 'msg' => '请选择截拍时间']);
        }
        $st = time();
        $et = strtotime(str_replace('T', ' ', $endTime));
        if (!$et || $et <= $st) {
            return json(['code' => 0, 'msg' => '截拍时间必须晚于当前时间']);
        }
        if ($et - $st < 60) {
            return json(['code' => 0, 'msg' => '截拍时间必须晚于当前时间1分钟以上']);
        }

        $images = is_array($images) ? array_values(array_filter($images)) : [];
        if (empty($images)) {
            return json(['code' => 0, 'msg' => '请至少上传一张产品图片']);
        }
        if (empty($cover)) {
            $cover = $images[0];
        }

        $now = time();
        Db::name('goods')->insert([
            'seller_id'        => $sellerId,
            'category_id'      => $categoryId,
            'title'            => $title,
            'cover'            => $cover,
            'images'           => json_encode($images, JSON_UNESCAPED_UNICODE),
            'content'          => $content,
            'start_price'      => $startPrice,
            'raise_price'      => $raisePrice,
            'reserve_price'    => $reservePrice,
            'deposit'          => $deposit,
            'commission_rate'  => $commissionRate,
            'reference_price'  => round((float)$this->request->post('reference_price', 0), 2),
            'is_free_shipping' => (int)$this->request->post('is_free_shipping', 0),
            'is_featured'      => (int)$this->request->post('is_featured', 0),
            'start_time'       => $st,
            'end_time'         => $et,
            'delay_seconds'    => $delaySeconds,
            'status'           => 1, // 与主后台一致：代发布直接上架
            'create_time'      => $now,
            'update_time'      => $now,
        ]);

        return json(['code' => 1, 'msg' => '发布成功，产品已上架到 ' . $seller['nickname'] . ' 店铺']);
    }

    /**
     * 产品审核列表
     */
    public function check()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            $status = (int)$this->request->param('status', 0);
            if (!in_array($status, [0, 5], true)) {
                $status = 0;
            }

            $query = $this->goodsQuery('g')
                ->leftJoin('user u', 'g.seller_id = u.id')
                ->field('g.*, u.mobile as seller_mobile, u.nickname as seller_name')
                ->where('g.status', $status);

            $total = $query->count();
            $list  = $query->order('g.id', 'desc')->page($page, $limit)->select()->toArray();
            foreach ($list as &$g) {
                $g['seller_mobile'] = $this->maskMobile($g['seller_mobile']);
            }
            unset($g);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/agent/goods/check');
        return View::fetch();
    }

    /**
     * 产品详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id');
        // 归属校验：不是我团队的产品直接踢回列表
        $this->assertMyGoods($id);

        $goods = $this->goodsQuery('g')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->leftJoin('category c', 'g.category_id = c.id')
            ->field('g.*, u.mobile as seller_mobile, u.nickname as seller_name, c.name as category_name')
            ->where('g.id', $id)
            ->find();

        $goods['seller_mobile']   = $this->maskMobile($goods['seller_mobile']);
        $goods['images_arr']      = $goods['images'] ? json_decode($goods['images'], true) : [];
        $goods['status_text']     = $this->statusText($goods['status']);
        $goods['start_time_text'] = $goods['start_time'] ? date('Y-m-d H:i:s', $goods['start_time']) : '-';
        $goods['end_time_text']   = $goods['end_time'] ? date('Y-m-d H:i:s', $goods['end_time']) : '-';

        // 出价记录（出价人手机号脱敏）
        $bids = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.*, u.mobile, u.nickname')
            ->where('b.goods_id', $id)
            ->order('b.price', 'desc')
            ->select()
            ->toArray();
        foreach ($bids as &$b) {
            $b['mobile'] = $this->maskMobile($b['mobile']);
        }
        unset($b);

        // 成交订单
        $order = Db::name('order')->where('goods_id', $id)->find();

        View::assign([
            'goods'       => $goods,
            'bids'        => $bids,
            'order'       => $order,
            'menu_active' => '/agent/goods/index',
        ]);
        return View::fetch();
    }

    /**
     * 产品审核操作
     */
    public function audit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id     = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');
        $reason = trim($this->request->post('reason', ''));

        $goods = $this->assertMyGoods($id);
        if ($goods['status'] != 0 && $goods['status'] != 5) {
            return json(['code' => 0, 'msg' => '该产品已审核过，不能重复操作']);
        }

        if ($action === 'pass') {
            Db::name('goods')->where('id', $id)->update([
                'status'        => 1,
                'refuse_reason' => '',
                'update_time'   => time(),
            ]);
            return json(['code' => 1, 'msg' => '已通过审核，产品进入拍卖中']);
        }

        if ($reason === '') {
            return json(['code' => 0, 'msg' => '请填写拒绝原因']);
        }
        Db::name('goods')->where('id', $id)->update([
            'status'        => 5,
            'refuse_reason' => mb_substr($reason, 0, 200),
            'update_time'   => time(),
        ]);
        return json(['code' => 1, 'msg' => '已拒绝']);
    }

    /**
     * 上架 / 下架
     */
    public function setStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id     = (int)$this->request->post('id');
        $status = (int)$this->request->post('status', 4);

        $goods = $this->assertMyGoods($id);
        if ($status != 1 && $status != 4) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if ($goods['status'] == 2 || $goods['status'] == 3) {
            return json(['code' => 0, 'msg' => '已结束的产品不能上下架']);
        }

        Db::name('goods')->where('id', $id)->update(['status' => $status, 'update_time' => time()]);
        return json(['code' => 1, 'msg' => '操作成功']);
    }

    /**
     * 删除产品（支持批量，只删得掉自己团队的）
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $ids = $this->request->post('ids', '');
        if ($ids !== '') {
            $idArr = array_unique(array_values(array_filter(array_map('intval', explode(',', $ids)))));
            if (empty($idArr)) {
                return json(['code' => 0, 'msg' => '请选择要删除的产品']);
            }
            // 只取回属于我团队的，非团队产品被静默过滤
            $goodsList = $this->goodsQuery()->whereIn('id', $idArr)->select()->toArray();
            if (empty($goodsList)) {
                return json(['code' => 0, 'msg' => '所选产品均不属于您的团队']);
            }
            $delIds = [];
            $skipDeal = 0;
            foreach ($goodsList as $g) {
                if ($g['status'] == 2) {
                    $skipDeal++;
                    continue;
                }
                $delIds[] = $g['id'];
            }
            if (!empty($delIds)) {
                Db::name('goods')->whereIn('id', $delIds)->delete();
            }
            $msg = '删除成功 ' . count($delIds) . ' 个产品';
            if ($skipDeal > 0) {
                $msg .= '，已成交产品 ' . $skipDeal . ' 个自动跳过';
            }
            $notMine = count($idArr) - count($goodsList);
            if ($notMine > 0) {
                $msg .= '，非团队产品 ' . $notMine . ' 个已忽略';
            }
            return json(['code' => 1, 'msg' => $msg]);
        }

        $id    = (int)$this->request->post('id');
        $goods = $this->assertMyGoods($id);
        if ($goods['status'] == 2) {
            return json(['code' => 0, 'msg' => '已成交产品不能删除']);
        }
        Db::name('goods')->where('id', $id)->delete();
        return json(['code' => 1, 'msg' => '删除成功']);
    }

    /**
     * 修改拍卖开始/结束时间（仅竞拍中 / 待审核，且限本团队产品）
     */
    public function setTime()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id        = (int)$this->request->post('id');
        $startTime = trim($this->request->post('start_time', ''));
        $endTime   = trim($this->request->post('end_time', ''));

        $goods = $this->assertMyGoods($id);
        if ($goods['status'] != 0 && $goods['status'] != 1) {
            return json(['code' => 0, 'msg' => '只有竞拍中或待审核的产品可以修改拍卖时间']);
        }
        if ($startTime === '') {
            return json(['code' => 0, 'msg' => '请选择开始时间']);
        }
        if ($endTime === '') {
            return json(['code' => 0, 'msg' => '请选择结束时间']);
        }
        $st = strtotime(str_replace('T', ' ', $startTime));
        $et = strtotime(str_replace('T', ' ', $endTime));
        if (!$st || !$et) {
            return json(['code' => 0, 'msg' => '时间格式不正确']);
        }
        if ($et <= $st) {
            return json(['code' => 0, 'msg' => '结束时间必须晚于开始时间']);
        }
        if ($et <= time() + 60) {
            return json(['code' => 0, 'msg' => '结束时间必须晚于当前时间1分钟以上']);
        }

        Db::name('goods')->where('id', $id)->update([
            'start_time'  => $st,
            'end_time'    => $et,
            'update_time' => time(),
        ]);
        return json(['code' => 1, 'msg' => '拍卖时间已更新：' . date('m-d H:i', $st) . ' ~ ' . date('m-d H:i', $et)]);
    }

    /**
     * 状态文字
     */
    protected function statusText($status)
    {
        $map = [
            0 => ['待审核', 'tag-orange'],
            1 => ['拍卖中', 'tag-green'],
            2 => ['已成交', 'tag-blue'],
            3 => ['流拍', 'tag-gray'],
            4 => ['已下架', 'tag-purple'],
            5 => ['审核拒绝', 'tag-red'],
        ];
        return $map[$status] ?? ['未知', 'tag-gray'];
    }
}

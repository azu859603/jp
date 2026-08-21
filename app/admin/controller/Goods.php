<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Goods extends Base
{
    /**
     * 商品列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $sellerKw = trim($this->request->param('seller_kw', ''));
            $status = $this->request->param('status', '');
            $categoryId = $this->request->param('category_id', '');
            $createStart = trim($this->request->param('create_start', ''));
            $createEnd = trim($this->request->param('create_end', ''));

            $query = Db::name('goods')->alias('g')
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
            $list = $query->order('g.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        $categories = Db::name('category')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        $sellers = Db::name('user')->where('is_seller', 1)->field('id,nickname,mobile')->order('id', 'desc')->select()->toArray();
        View::assign(['categories' => $categories, 'sellers' => $sellers, 'menu_active' => '/admin1314/goods/index']);
        return View::fetch();
    }

    /**
     * 后台发布商品（发到指定卖家店铺）
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $sellerId = (int)$this->request->post('seller_id', 0);
        $title = trim($this->request->post('title', ''));
        $categoryId = (int)$this->request->post('category_id', 0);
        $content = trim($this->request->post('content', ''));
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

        $seller = Db::name('user')->where('id', $sellerId)->where('is_seller', 1)->find();
        if (!$seller) {
            return json(['code' => 0, 'msg' => '请选择有效的卖家店铺']);
        }
        if ($title === '') {
            return json(['code' => 0, 'msg' => '请输入商品标题']);
        }
        if ($categoryId <= 0) {
            return json(['code' => 0, 'msg' => '请选择分类']);
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
            return json(['code' => 0, 'msg' => '请至少上传一张商品图片']);
        }
        if (empty($cover) && !empty($images)) {
            $cover = $images[0];
        }

        $now = time();
        Db::name('goods')->insert([
            'seller_id'       => $sellerId,
            'category_id'     => $categoryId,
            'title'           => $title,
            'cover'           => $cover,
            'images'          => json_encode($images, JSON_UNESCAPED_UNICODE),
            'content'         => $content,
            'start_price'     => $startPrice,
            'raise_price'     => $raisePrice,
            'reserve_price'   => $reservePrice,
            'deposit'         => $deposit,
            'commission_rate' => $commissionRate,
            'reference_price' => round((float)$this->request->post('reference_price', 0), 2),
            'is_free_shipping' => (int)$this->request->post('is_free_shipping', 0),
            'is_featured'      => (int)$this->request->post('is_featured', 0),
            'start_time'      => $st,
            'end_time'        => $et,
            'delay_seconds'   => $delaySeconds,
            'status'          => 1, // 管理员发布直接上架拍卖
            'create_time'     => $now,
            'update_time'     => $now,
        ]);

        admin_log('后台发布商品：' . $title . '（卖家ID:' . $sellerId . '）');
        return json(['code' => 1, 'msg' => '发布成功，商品已上架到 ' . $seller['nickname'] . ' 店铺']);
    }

    /**
     * 商品审核列表
     */
    public function check()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $status = $this->request->param('status', 0);

            $query = Db::name('goods')->alias('g')
                ->leftJoin('user u', 'g.seller_id = u.id')
                ->field('g.*, u.mobile as seller_mobile, u.nickname as seller_name')
                ->where('g.status', (int)$status);

            $total = $query->count();
            $list = $query->order('g.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/goods/check');
        return View::fetch();
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id');
        $goods = Db::name('goods')->alias('g')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->leftJoin('category c', 'g.category_id = c.id')
            ->field('g.*, u.mobile as seller_mobile, u.nickname as seller_name, c.name as category_name')
            ->where('g.id', $id)
            ->find();

        if (!$goods) {
            return $this->error('商品不存在');
        }

        $goods['images_arr'] = $goods['images'] ? json_decode($goods['images'], true) : [];
        $goods['status_text'] = $this->statusText($goods['status']);
        $goods['start_time_text'] = $goods['start_time'] ? date('Y-m-d H:i:s', $goods['start_time']) : '-';
        $goods['end_time_text'] = $goods['end_time'] ? date('Y-m-d H:i:s', $goods['end_time']) : '-';

        // 出价记录
        $bids = Db::name('bid_record')
            ->alias('b')
            ->leftJoin('user u', 'b.user_id = u.id')
            ->field('b.*, u.mobile, u.nickname')
            ->where('b.goods_id', $id)
            ->order('b.price', 'desc')
            ->select()
            ->toArray();

        // 成交订单
        $order = Db::name('order')->where('goods_id', $id)->find();

        View::assign([
            'goods'      => $goods,
            'bids'       => $bids,
            'order'      => $order,
            'menu_active'=> '/admin1314/goods/index',
        ]);
        return View::fetch();
    }

    /**
     * 商品审核操作
     */
    public function audit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');
        $reason = trim($this->request->post('reason', ''));

        $goods = Db::name('goods')->find($id);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '商品不存在']);
        }
        if ($goods['status'] != 0 && $goods['status'] != 5) {
            return json(['code' => 0, 'msg' => '该商品已审核过，不能重复操作']);
        }

        if ($action === 'pass') {
            Db::name('goods')->where('id', $id)->update([
                'status'         => 1,
                'refuse_reason'  => '',
            ]);
            admin_log('通过商品审核：' . $goods['title']);
            return json(['code' => 1, 'msg' => '已通过审核，商品进入拍卖中']);
        } else {
            if (empty($reason)) {
                return json(['code' => 0, 'msg' => '请填写拒绝原因']);
            }
            Db::name('goods')->where('id', $id)->update([
                'status'        => 5,
                'refuse_reason' => $reason,
            ]);
            admin_log('拒绝商品审核：' . $goods['title'] . '，原因：' . $reason);
            return json(['code' => 1, 'msg' => '已拒绝']);
        }
    }

    /**
     * 上架/下架
     */
    public function setStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $status = (int)$this->request->post('status', 4);

        $goods = Db::name('goods')->find($id);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '商品不存在']);
        }
        if ($status != 1 && $status != 4) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if ($goods['status'] == 2 || $goods['status'] == 3) {
            return json(['code' => 0, 'msg' => '已结束的商品不能上下架']);
        }

        Db::name('goods')->where('id', $id)->update(['status' => $status]);
        admin_log(($status == 1 ? '上架' : '下架') . '商品：' . $goods['title']);
        return json(['code' => 1, 'msg' => '操作成功']);
    }

    /**
     * 删除商品（支持批量）
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $ids = $this->request->post('ids', '');
        if ($ids !== '') {
            // 批量删除
            $idArr = array_values(array_filter(array_map('intval', explode(',', $ids))));
            if (empty($idArr)) {
                return json(['code' => 0, 'msg' => '请选择要删除的商品']);
            }
            $idArr = array_unique($idArr);
            $goodsList = Db::name('goods')->whereIn('id', $idArr)->select()->toArray();
            if (empty($goodsList)) {
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            $delIds = [];
            $skipCount = 0;
            foreach ($goodsList as $g) {
                if ($g['status'] == 2) {
                    $skipCount++;
                    continue;
                }
                $delIds[] = $g['id'];
            }
            if (!empty($delIds)) {
                Db::name('goods')->whereIn('id', $delIds)->delete();
                admin_log('批量删除商品：' . implode(',', $delIds));
            }
            $msg = '删除成功' . count($delIds) . ' 个商品';
            if ($skipCount > 0) {
                $msg .= '，已成交商品 ' . $skipCount . ' 个自动跳过';
            }
            return json(['code' => 1, 'msg' => $msg]);
        }

        $id = (int)$this->request->post('id');
        $goods = Db::name('goods')->find($id);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '商品不存在']);
        }
        if ($goods['status'] == 2) {
            return json(['code' => 0, 'msg' => '已成交商品不能删除']);
        }

        Db::name('goods')->where('id', $id)->delete();
        admin_log('删除商品：' . $goods['title']);
        return json(['code' => 1, 'msg' => '删除成功']);
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

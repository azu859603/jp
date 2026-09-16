<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 竞拍管理 - 虚拟用户自动出价
 *
 * 与主后台逻辑一致，数据范围收口为「拍品卖家 ∈ 我的下级」：
 * 只能为团队卖家拍卖中的拍品添加任务，列表 / 编辑 / 启停 / 删除也只限这些拍品。
 * 执行由 php think bid:auto 定时完成（平台自营任务由 php think platform:auto-bid 负责），逻辑在 app/common.php 的 auto_bid_run()。
 */
class AutoBid extends Base
{
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $keyword = trim((string)$this->request->param('keyword', ''));
            $status  = trim((string)$this->request->param('status', ''));

            $query = $this->listQuery();
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    // 拍品标题 / 卖家手机号
                    $q->where('g.title', 'like', "%{$keyword}%")->whereOr('u.mobile', 'like', "%{$keyword}%");
                });
            }
            if ($status !== '') {
                $query->where('a.status', (int)$status);
            }
            $total = $query->count();
            $list  = $query->order('a.status', 'desc')->order('a.id', 'desc')->page($page, $limit)->select()->toArray();
            $this->decorate($list);
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }
        View::assign(['menu_active' => '/agent/auto_bid/index', 'virtual_count' => $this->virtualCount(), 'platform_auto_bid_on' => platform_auto_bid_enabled() ? 1 : 0]);
        return View::fetch();
    }

    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $goodsId = (int)$this->request->post('goods_id', 0);
        $goods   = $this->findGoods($goodsId);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '该拍品不属于您的团队']);
        }
        if (auto_bid_blocked_seller($goods['seller_id'])) {
            return json(['code' => 0, 'msg' => '该拍品属于会员 ID 1 的卖家（平台自营），已由系统脚本统一自动出价，不能手动添加任务']);
        }
        if (Db::name('auto_bid')->where('goods_id', $goodsId)->count()) {
            return json(['code' => 0, 'msg' => '该拍品已有自动出价任务，请直接编辑']);
        }
        [$interval, $maxPrice, $stopHours] = $this->readParams();
        $err = auto_bid_validate($goods, $interval, $maxPrice, $stopHours);
        if ($err !== '') {
            return json(['code' => 0, 'msg' => $err]);
        }
        if ($this->virtualCount() === 0) {
            return json(['code' => 0, 'msg' => '系统里还没有启用的虚拟会员，任务无法出价']);
        }
        $now = time();
        $id  = Db::name('auto_bid')->insertGetId([
            'goods_id'     => $goodsId,
            'interval_min' => $interval,
            'max_price'    => $maxPrice,
            'stop_hours'   => $stopHours,
            'status'       => 1,
            'stop_reason'  => '',
            'next_time'    => $now + mt_rand(60, max(60, $interval * 60)),
            'last_time'    => 0,
            'bid_count'    => 0,
            'creator_type' => 'agent',
            'creator_id'   => (int)($this->agent['id'] ?? 0),
            'create_time'  => $now,
            'update_time'  => $now,
        ]);
        return json(['code' => 1, 'msg' => '任务已创建，首次出价将在 ' . $interval . ' 分钟内随机进行', 'id' => $id]);
    }

    public function edit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $task = $this->findTask((int)$this->request->post('id', 0));
        if (!$task) {
            return json(['code' => 0, 'msg' => '任务不存在或不属于您的团队']);
        }
        if (($task['creator_type'] ?? '') === 'platform') {
            return json(['code' => 0, 'msg' => '该任务由「平台自营自动出价」脚本管理，请在系统设置中调整参数或关闭该功能']);
        }
        $goods = $this->findGoods($task['goods_id']);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '该拍品不属于您的团队']);
        }
        if (auto_bid_blocked_seller($goods['seller_id'])) {
            return json(['code' => 0, 'msg' => '该拍品属于会员 ID 1 的卖家（平台自营），已由系统脚本统一自动出价，不能手动添加任务']);
        }
        [$interval, $maxPrice, $stopHours] = $this->readParams();
        $err = auto_bid_validate($goods, $interval, $maxPrice, $stopHours);
        if ($err !== '') {
            return json(['code' => 0, 'msg' => $err]);
        }
        $now  = time();
        $data = ['interval_min' => $interval, 'max_price' => $maxPrice, 'stop_hours' => $stopHours, 'update_time' => $now];
        if ((int)$task['status'] === 2) {
            $data['status']      = 1;
            $data['stop_reason'] = '';
            $data['next_time']   = $now + mt_rand(60, max(60, $interval * 60));
        }
        Db::name('auto_bid')->where('id', $task['id'])->update($data);
        return json(['code' => 1, 'msg' => '已保存' . (isset($data['status']) ? '，任务已重新运行' : '')]);
    }

    public function setStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $task = $this->findTask((int)$this->request->post('id', 0));
        if (!$task) {
            return json(['code' => 0, 'msg' => '任务不存在或不属于您的团队']);
        }
        if (($task['creator_type'] ?? '') === 'platform') {
            return json(['code' => 0, 'msg' => '该任务由「平台自营自动出价」脚本管理，请在系统设置中调整参数或关闭该功能']);
        }
        $status = (int)$this->request->post('status', 0) === 1 ? 1 : 0;
        $now    = time();
        $data   = ['status' => $status, 'update_time' => $now];
        if ($status === 1) {
            $goods = $this->findGoods($task['goods_id']);
            if (!$goods) {
                return json(['code' => 0, 'msg' => '该拍品不属于您的团队']);
            }
            $err = auto_bid_validate($goods, (int)$task['interval_min'], (float)$task['max_price'], (float)$task['stop_hours']);
            if ($err !== '') {
                return json(['code' => 0, 'msg' => '无法启用：' . $err]);
            }
            $data['stop_reason'] = '';
            $data['next_time']   = $now + mt_rand(60, max(60, (int)$task['interval_min'] * 60));
        } else {
            $data['stop_reason'] = '手动停用';
        }
        Db::name('auto_bid')->where('id', $task['id'])->update($data);
        return json(['code' => 1, 'msg' => $status === 1 ? '任务已启用' : '任务已停用']);
    }

    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $task = $this->findTask((int)$this->request->post('id', 0));
        if (!$task) {
            return json(['code' => 0, 'msg' => '任务不存在或不属于您的团队']);
        }
        if (($task['creator_type'] ?? '') === 'platform') {
            return json(['code' => 0, 'msg' => '该任务由「平台自营自动出价」脚本管理，请在系统设置中调整参数或关闭该功能']);
        }
        Db::name('auto_bid')->where('id', $task['id'])->delete();
        return json(['code' => 1, 'msg' => '任务已删除，已产生的出价记录保留']);
    }

    // ------------------------------------------------------------------

    protected function listQuery()
    {
        $ids = $this->memberIds();
        return Db::name('auto_bid')->alias('a')
            ->leftJoin('goods g', 'a.goods_id = g.id')
            ->leftJoin('user u', 'g.seller_id = u.id')
            ->field('a.*, g.title, g.cover, g.status as goods_status, g.start_price, g.raise_price, g.end_time, g.bid_count as goods_bid_count, g.seller_id, u.nickname as seller_name, u.mobile as seller_mobile, u.shop_name')
            ->whereIn('g.seller_id', $ids ?: [-1]);
    }

    protected function decorate(array &$list)
    {
        $ids  = array_column($list, 'goods_id');
        $tops = [];
        if ($ids) {
            $rows = Db::name('bid_record')->whereIn('goods_id', $ids)->where('status', 0)->field('goods_id, MAX(price) AS top')->group('goods_id')->select()->toArray();
            foreach ($rows as $r) {
                $tops[(int)$r['goods_id']] = (float)$r['top'];
            }
        }
        $now = time();
        foreach ($list as &$t) {
            $t['current_price'] = max($tops[(int)$t['goods_id']] ?? 0, (float)$t['start_price']);
            $t['has_bid']       = isset($tops[(int)$t['goods_id']]) ? 1 : 0;
            $t['seller_text']   = !empty($t['shop_name']) ? $t['shop_name'] : ($t['seller_name'] ?: ('ID:' . $t['seller_id']));
            $t['remain_hours']  = $t['end_time'] ? round(max(0, (int)$t['end_time'] - $now) / 3600, 1) : 0;
        }
        unset($t);
    }

    /** 团队范围内的拍品，不在范围返回 null */
    protected function findGoods($id)
    {
        $id = (int)$id;
        return $id > 0 ? $this->goodsQuery()->where('id', $id)->find() : null;
    }

    /** 团队范围内的任务，不在范围返回 null */
    protected function findTask($id)
    {
        $task = Db::name('auto_bid')->find((int)$id);
        if (!$task || !$this->findGoods($task['goods_id'])) {
            return null;
        }
        return $task;
    }

    protected function readParams()
    {
        return [
            (int)$this->request->post('interval_min', 0),
            round((float)$this->request->post('max_price', 0), 2),
            round((float)$this->request->post('stop_hours', 0), 2),
        ];
    }

    protected function virtualCount()
    {
        return (int)Db::name('user')->where('is_virtual', 1)->where('status', 1)->count();
    }
}

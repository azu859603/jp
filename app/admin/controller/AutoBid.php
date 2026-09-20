<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 竞拍管理 - 虚拟用户自动出价
 *
 * 按拍品配置：出价间隔（分钟，实际 70%~130% 随机）、最高出价金额、截拍前停止小时数。
 * 执行由 php think bid:auto 定时完成（平台自营任务由 php think platform:auto-bid 负责），逻辑在 app/common.php 的 auto_bid_run()。
 */
class AutoBid extends Base
{
    public function index()
    {
        if ($this->request->isAjax()) {
            $page    = max(1, (int)$this->request->param('page', 1));
            $limit   = max(1, min(100, (int)$this->request->param('limit', 15)));
            $keyword = trim((string)$this->request->param('keyword', ''));
            $status  = trim((string)$this->request->param('status', ''));

            $query = $this->listQuery();
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    // 拍品标题 / 卖家手机号
                    $q->where('g.title', 'like', "%{$keyword}%")->whereOr('u.account', 'like', "%{$keyword}%");
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
        View::assign(['menu_active' => '/admin1314/auto_bid/index', 'virtual_count' => $this->virtualCount(), 'platform_auto_bid_on' => platform_auto_bid_enabled() ? 1 : 0]);
        return View::fetch();
    }

    /**
     * 新增任务
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $goodsId = (int)$this->request->post('goods_id', 0);
        $goods   = $this->findGoods($goodsId);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '拍品不存在']);
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
            return json(['code' => 0, 'msg' => '系统里还没有启用的虚拟会员，请先在会员管理中添加']);
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
            'creator_type' => $this->creatorType(),
            'creator_id'   => $this->creatorId(),
            'create_time'  => $now,
            'update_time'  => $now,
        ]);
        $this->log('添加自动出价任务：拍品「' . $goods['title'] . '」(ID:' . $goodsId . ') 间隔 ' . $interval . ' 分钟，上限 ' . number_format($maxPrice, 2) . '，截拍前 ' . $stopHours . ' 小时停止');
        return json(['code' => 1, 'msg' => '任务已创建，首次出价将在 ' . $interval . ' 分钟内随机进行', 'id' => $id]);
    }

    /**
     * 编辑任务参数（拍品不可更换）
     */
    public function edit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $task = $this->findTask((int)$this->request->post('id', 0));
        if (!$task) {
            return json(['code' => 0, 'msg' => '任务不存在']);
        }
        if (($task['creator_type'] ?? '') === 'platform') {
            return json(['code' => 0, 'msg' => '该任务由「平台自营自动出价」脚本管理，请在系统设置中调整参数或关闭该功能']);
        }
        $goods = $this->findGoods($task['goods_id']);
        if (!$goods) {
            return json(['code' => 0, 'msg' => '拍品不存在']);
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
        // 参数改动后若原任务已结束（如达到旧上限），自动恢复运行
        if ((int)$task['status'] === 2) {
            $data['status']      = 1;
            $data['stop_reason'] = '';
            $data['next_time']   = $now + mt_rand(60, max(60, $interval * 60));
        }
        Db::name('auto_bid')->where('id', $task['id'])->update($data);
        $this->log('修改自动出价任务：拍品「' . $goods['title'] . '」(ID:' . $goods['id'] . ') 间隔 ' . $interval . ' 分钟，上限 ' . number_format($maxPrice, 2) . '，截拍前 ' . $stopHours . ' 小时停止');
        return json(['code' => 1, 'msg' => '已保存' . (isset($data['status']) ? '，任务已重新运行' : '')]);
    }

    /**
     * 启用 / 停用
     */
    public function setStatus()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $task = $this->findTask((int)$this->request->post('id', 0));
        if (!$task) {
            return json(['code' => 0, 'msg' => '任务不存在']);
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
                return json(['code' => 0, 'msg' => '拍品不存在']);
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
        $this->log(($status === 1 ? '启用' : '停用') . '自动出价任务：拍品 ID ' . $task['goods_id']);
        return json(['code' => 1, 'msg' => $status === 1 ? '任务已启用' : '任务已停用']);
    }

    /**
     * 删除任务（已产生的出价记录保留）
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $task = $this->findTask((int)$this->request->post('id', 0));
        if (!$task) {
            return json(['code' => 0, 'msg' => '任务不存在']);
        }
        if (($task['creator_type'] ?? '') === 'platform') {
            return json(['code' => 0, 'msg' => '该任务由「平台自营自动出价」脚本管理，请在系统设置中调整参数或关闭该功能']);
        }
        Db::name('auto_bid')->where('id', $task['id'])->delete();
        $this->log('删除自动出价任务：拍品 ID ' . $task['goods_id'] . '，已自动出价 ' . $task['bid_count'] . ' 次');
        return json(['code' => 1, 'msg' => '任务已删除，已产生的出价记录保留']);
    }

    // ------------------------------------------------------------------

    protected function listQuery()
    {
        return Db::name('auto_bid')->alias('a')
            ->leftJoin('goods g', 'a.goods_id = g.id')
            ->leftJoin('user u', 'g.seller_id = u.id')
            // up：卖家的上级（邀请人），列表里显示在卖家账号下方
            ->leftJoin('user up', 'u.pid = up.id')
            ->field('a.*, g.title, g.cover, g.status as goods_status, g.start_price, g.raise_price, g.end_time, g.bid_count as goods_bid_count, g.seller_id, u.nickname as seller_name, u.account as seller_mobile, u.pid as seller_pid, up.account as seller_parent, u.shop_name');
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

    protected function findGoods($id)
    {
        return Db::name('goods')->find((int)$id);
    }

    protected function findTask($id)
    {
        return Db::name('auto_bid')->find((int)$id);
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

    protected function creatorType()
    {
        return 'admin';
    }

    protected function creatorId()
    {
        return (int)($this->admin['id'] ?? 0);
    }

    protected function log($msg)
    {
        admin_log($msg);
    }
}

<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Recharge extends Base
{
    /**
     * 充值列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $status = $this->request->param('status', '');

            $query = Db::name('recharge')->alias('r')
                ->leftJoin('user u', 'r.user_id = u.id')
                ->field('r.*, u.mobile, u.nickname');

            if ($status !== '') {
                $query->where('r.status', (int)$status);
            }

            $total = $query->count();
            $list = $query->order('r.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/recharge/index');
        return View::fetch();
    }

    /**
     * 审核操作
     */
    public function audit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');
        $reason = trim($this->request->post('reason', ''));

        $recharge = Db::name('recharge')->find($id);
        if (!$recharge) {
            return json(['code' => 0, 'msg' => '充值申请不存在']);
        }
        if ($recharge['status'] != 0) {
            return json(['code' => 0, 'msg' => '该申请已处理过']);
        }

        $user = Db::name('user')->find($recharge['user_id']);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        Db::startTrans();
        try {
            if ($action === 'pass') {
                // 审核通过：余额到账 + 流水
                $newBalance = round($user['balance'] + $recharge['amount'], 2);
                Db::name('user')->where('id', $user['id'])->update([
                    'balance'     => $newBalance,
                    'update_time' => time(),
                ]);
                Db::name('balance_log')->insert([
                    'user_id'     => $user['id'],
                    'type'        => 'recharge',
                    'amount'      => $recharge['amount'],
                    'balance'     => $newBalance,
                    'remark'      => '充值到账：' . $recharge['amount'] . '元',
                    'create_time' => time(),
                ]);
                Db::name('recharge')->where('id', $id)->update([
                    'status'      => 1,
                    'handle_time' => time(),
                ]);
                admin_log('充值到账：会员 ' . $user['mobile'] . ' ' . $recharge['amount'] . '元');
            } else {
                if (empty($reason)) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '请填写拒绝原因']);
                }
                Db::name('recharge')->where('id', $id)->update([
                    'status'        => 2,
                    'refuse_reason' => $reason,
                    'handle_time'   => time(),
                ]);
                admin_log('拒绝充值：会员 ' . $user['mobile'] . '，原因：' . $reason);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => $action === 'pass' ? '已到账' : '已拒绝']);
    }
}

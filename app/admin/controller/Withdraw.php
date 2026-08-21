<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Withdraw extends Base
{
    /**
     * 提现列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $status = $this->request->param('status', '');

            $query = Db::name('withdraw')->alias('w')
                ->leftJoin('user u', 'w.user_id = u.id')
                ->field('w.*, u.mobile, u.nickname');

            if ($status !== '') {
                $query->where('w.status', (int)$status);
            }

            $total = $query->count();
            $list = $query->order('w.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/withdraw/index');
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

        $withdraw = Db::name('withdraw')->find($id);
        if (!$withdraw) {
            return json(['code' => 0, 'msg' => '提现申请不存在']);
        }
        if ($withdraw['status'] != 0) {
            return json(['code' => 0, 'msg' => '该申请已处理过']);
        }

        $user = Db::name('user')->find($withdraw['user_id']);
        if (!$user) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }

        Db::startTrans();
        try {
            if ($action === 'pass') {
                // 余额已在提交申请时扣减冻结，打款仅更新状态
                Db::name('withdraw')->where('id', $id)->update([
                    'status'      => 1,
                    'handle_time' => time(),
                ]);
                admin_log('提现打款：会员 ' . $user['mobile'] . ' ' . $withdraw['amount'] . '元');
            } else {
                if (empty($reason)) {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '请填写拒绝原因']);
                }
                // 拒绝退回冻结的余额
                $newBalance = round($user['balance'] + $withdraw['amount'], 2);
                Db::name('user')->where('id', $user['id'])->update([
                    'balance'     => $newBalance,
                    'update_time' => time(),
                ]);
                Db::name('balance_log')->insert([
                    'user_id'     => $user['id'],
                    'type'        => 'refund',
                    'amount'      => $withdraw['amount'],
                    'balance'     => $newBalance,
                    'remark'      => '提现拒绝退回：' . $withdraw['amount'] . '元',
                    'create_time' => time(),
                ]);
                Db::name('withdraw')->where('id', $id)->update([
                    'status'        => 2,
                    'refuse_reason' => $reason,
                    'handle_time'   => time(),
                ]);
                admin_log('拒绝提现：会员 ' . $user['mobile'] . '，原因：' . $reason);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => $action === 'pass' ? '已打款' : '已拒绝']);
    }
}

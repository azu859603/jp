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
                ->field('w.*, u.account as mobile, u.nickname, u.is_virtual');

            if ($status !== '') {
                $query->where('w.status', (int)$status);
            }
            $keyword = trim((string)$this->request->param('keyword', ''));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('u.account', 'like', "%{$keyword}%")->whereOr('u.nickname', 'like', "%{$keyword}%");
                });
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

        Db::startTrans();
        try {
            // 行锁在事务内：重复点击只有一次生效
            $withdraw = Db::name('withdraw')->where('id', $id)->lock(true)->find();
            if (!$withdraw) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '提现申请不存在']);
            }
            if ($withdraw['status'] != 0) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '该申请已处理过']);
            }
            $user = Db::name('user')->where('id', $withdraw['user_id'])->lock(true)->find();
            if (!$user) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '会员不存在']);
            }

            if ($action === 'pass') {
                // 余额已在提交申请时扣减冻结，打款仅更新状态
                if (Db::name('withdraw')->where('id', $id)->where('status', 0)->update([
                    'status'      => 1,
                    'handle_time' => time(),
                ]) !== 1) {
                    throw new \RuntimeException('申请状态已变化');
                }
                admin_log('提现打款：会员 ' . user_account($user) . ' ' . $withdraw['amount'] . '元');
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
                if (Db::name('withdraw')->where('id', $id)->where('status', 0)->update([
                    'status'        => 2,
                    'refuse_reason' => $reason,
                    'handle_time'   => time(),
                ]) !== 1) {
                    throw new \RuntimeException('申请状态已变化');
                }
                admin_log('拒绝提现：会员 ' . user_account($user) . '，原因：' . $reason);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => $action === 'pass' ? '已打款' : '已拒绝']);
    }
}

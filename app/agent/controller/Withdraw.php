<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 财务管理 / 提现审核
 * 数据范围：申请人 ∈ 我的下级。通过即标记已打款（余额在申请时已扣减），拒绝则退回余额并写流水。
 */
class Withdraw extends Base
{
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $status = $this->request->param('status', '');
            $query = Db::name('withdraw')->alias('w')
                ->leftJoin('user u', 'w.user_id = u.id')
                ->field('w.*, u.mobile, u.nickname')
                ->whereIn('w.user_id', $this->memberIds());
            if ($status !== '') {
                $query->where('w.status', (int)$status);
            }
            $total = $query->count();
            $list  = $query->order('w.id', 'desc')->page($page, $limit)->select()->toArray();
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }
        View::assign('menu_active', '/agent/withdraw/index');
        return View::fetch();
    }

    public function audit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id     = (int)$this->request->post('id');
        $action = $this->request->post('action', 'pass');
        $reason = trim($this->request->post('reason', ''));

        $withdraw = Db::name('withdraw')->find($id);
        if (!$withdraw) {
            return json(['code' => 0, 'msg' => '提现申请不存在']);
        }
        $user = $this->assertMyMember($withdraw['user_id']);
        if ($withdraw['status'] != 0) {
            return json(['code' => 0, 'msg' => '该申请已处理过']);
        }

        Db::startTrans();
        try {
            if ($action === 'pass') {
                Db::name('withdraw')->where('id', $id)->update(['status' => 1, 'handle_time' => time()]);
            } else {
                if ($reason === '') {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '请填写拒绝原因']);
                }
                $newBalance = round($user['balance'] + $withdraw['amount'], 2);
                Db::name('user')->where('id', $user['id'])->update(['balance' => $newBalance, 'update_time' => time()]);
                Db::name('balance_log')->insert([
                    'user_id'     => $user['id'],
                    'type'        => 'refund',
                    'amount'      => $withdraw['amount'],
                    'balance'     => $newBalance,
                    'remark'      => '提现拒绝退回：' . $withdraw['amount'] . '元',
                    'create_time' => time(),
                ]);
                Db::name('withdraw')->where('id', $id)->update(['status' => 2, 'refuse_reason' => mb_substr($reason, 0, 200), 'handle_time' => time()]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => $action === 'pass' ? '已打款' : '已拒绝']);
    }
}

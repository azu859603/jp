<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 财务管理 / 充值审核
 * 数据范围：申请人 ∈ 我的下级。审核逻辑与主后台一致（通过即到账并写流水，拒绝需填原因）。
 */
class Recharge extends Base
{
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $status = $this->request->param('status', '');
            $query = Db::name('recharge')->alias('r')
                ->leftJoin('user u', 'r.user_id = u.id')
                ->field('r.*, u.mobile, u.nickname')
                ->whereIn('r.user_id', $this->memberIds());
            if ($status !== '') {
                $query->where('r.status', (int)$status);
            }
            $total = $query->count();
            $list  = $query->order('r.id', 'desc')->page($page, $limit)->select()->toArray();
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }
        View::assign('menu_active', '/agent/recharge/index');
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

        $recharge = Db::name('recharge')->find($id);
        if (!$recharge) {
            return json(['code' => 0, 'msg' => '充值申请不存在']);
        }
        // 归属校验：申请人必须是我的下级
        $user = $this->assertMyMember($recharge['user_id']);
        if ($recharge['status'] != 0) {
            return json(['code' => 0, 'msg' => '该申请已处理过']);
        }

        Db::startTrans();
        try {
            if ($action === 'pass') {
                $newBalance = round($user['balance'] + $recharge['amount'], 2);
                Db::name('user')->where('id', $user['id'])->update(['balance' => $newBalance, 'update_time' => time()]);
                Db::name('balance_log')->insert([
                    'user_id'     => $user['id'],
                    'type'        => 'recharge',
                    'amount'      => $recharge['amount'],
                    'balance'     => $newBalance,
                    'remark'      => '充值到账：' . $recharge['amount'] . '元',
                    'create_time' => time(),
                ]);
                Db::name('recharge')->where('id', $id)->update(['status' => 1, 'handle_time' => time()]);
            } else {
                if ($reason === '') {
                    Db::rollback();
                    return json(['code' => 0, 'msg' => '请填写拒绝原因']);
                }
                Db::name('recharge')->where('id', $id)->update(['status' => 2, 'refuse_reason' => mb_substr($reason, 0, 200), 'handle_time' => time()]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => $action === 'pass' ? '已到账' : '已拒绝']);
    }
}

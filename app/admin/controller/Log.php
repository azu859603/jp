<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Log extends Base
{
    /**
     * 操作日志
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));

            $query = Db::name('admin_log')->alias('l')
                ->leftJoin('admin_user a', 'l.admin_id = a.id')
                ->field('l.*, a.username, a.real_name');

            if ($keyword !== '') {
                $query->whereLike('l.action', "%{$keyword}%");
            }

            $total = $query->count();
            $list = $query->order('l.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/log/index');
        return View::fetch();
    }

    /**
     * 代理日志：代理后台的所有操作（agent_log），可按代理 / 内容筛选
     */
    public function agent()
    {
        if ($this->request->isAjax()) {
            $page    = (int)$this->request->param('page', 1);
            $limit   = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $agent   = trim($this->request->param('agent', ''));

            $query = Db::name('agent_log')->alias('l')
                ->leftJoin('user u', 'l.agent_id = u.id')
                ->field('l.*, u.mobile as agent_mobile, u.nickname as agent_nickname');
            if ($keyword !== '') {
                $query->whereLike('l.action', "%{$keyword}%");
            }
            if ($agent !== '') {
                $query->where(function ($q) use ($agent) {
                    $q->whereLike('u.mobile', "%{$agent}%")->whereOr('u.nickname', 'like', "%{$agent}%");
                    if (ctype_digit($agent)) {
                        $q->whereOr('l.agent_id', (int)$agent);
                    }
                });
            }
            $total = $query->count();
            $list  = $query->order('l.id', 'desc')->page($page, $limit)->select()->toArray();
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/log/agent');
        return View::fetch();
    }

    /**
     * 清空代理日志
     */
    public function clearAgent()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        Db::name('agent_log')->delete(true);
        admin_log('清空代理日志');
        return json(['code' => 1, 'msg' => '代理日志已清空']);
    }

    /**
     * 清空日志
     */
    public function clear()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        Db::name('admin_log')->delete(true);
        admin_log('清空操作日志');
        return json(['code' => 1, 'msg' => '日志已清空']);
    }
}

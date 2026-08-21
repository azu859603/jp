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

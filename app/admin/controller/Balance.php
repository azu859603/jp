<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Balance extends Base
{
    /**
     * 余额流水
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $type = trim($this->request->param('type', ''));

            $query = Db::name('balance_log')->alias('l')
                ->leftJoin('user u', 'l.user_id = u.id')
                ->field('l.*, u.mobile, u.nickname');

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('l.user_id', (int)$keyword)
                        ->whereOr('u.mobile', 'like', "%{$keyword}%");
                });
            }
            if ($type !== '') {
                $query->where('l.type', $type);
            }

            $total = $query->count();
            $list = $query->order('l.id', 'desc')->page($page, $limit)->select()->toArray();

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/balance/index');
        return View::fetch();
    }
}

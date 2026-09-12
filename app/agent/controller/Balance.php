<?php
namespace app\agent\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 代理后台 - 财务管理 / 余额流水（只读）
 * 数据范围：会员 ∈ 我的下级。
 */
class Balance extends Base
{
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit) = array_values($this->pageParam());
            if ($this->hasNoMember()) {
                return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
            }
            $keyword = trim($this->request->param('keyword', ''));
            $type    = trim($this->request->param('type', ''));
            $query = Db::name('balance_log')->alias('l')
                ->leftJoin('user u', 'l.user_id = u.id')
                ->field('l.*, u.mobile, u.nickname')
                ->whereIn('l.user_id', $this->memberIds());
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('u.mobile', 'like', "%{$keyword}%")->whereOr('u.nickname', 'like', "%{$keyword}%");
                    if (ctype_digit($keyword)) {
                        $q->whereOr('l.user_id', (int)$keyword);
                    }
                });
            }
            if ($type !== '') {
                $query->where('l.type', $type);
            }
            $total = $query->count();
            $list  = $query->order('l.id', 'desc')->page($page, $limit)->select()->toArray();
            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }
        View::assign('menu_active', '/agent/balance/index');
        return View::fetch();
    }
}

<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 卖家咨询信息审核（聊天记录管理）
 */
class Message extends Base
{
    /**
     * 聊天信息列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page    = max(1, (int)$this->request->param('page', 1));
            $limit   = max(1, min(100, (int)$this->request->param('limit', 15)));
            $keyword = trim($this->request->param('keyword', ''));

            $query = Db::name('message')
                ->alias('m')
                ->leftJoin('user fu', 'm.from_uid = fu.id')
                ->leftJoin('user tu', 'm.to_uid = tu.id')
                ->leftJoin('goods g', 'm.goods_id = g.id')
                ->field('m.*, fu.nickname as from_nick, fu.mobile as from_mobile, fu.shop_name as from_shop, tu.nickname as to_nick, tu.mobile as to_mobile, tu.shop_name as to_shop, g.title as goods_title');
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('m.content', "%{$keyword}%")
                        ->whereOr('g.title', 'like', "%{$keyword}%")
                        ->whereOr('fu.nickname', 'like', "%{$keyword}%")
                        ->whereOr('fu.mobile', 'like', "%{$keyword}%")
                        ->whereOr('tu.nickname', 'like', "%{$keyword}%")
                        ->whereOr('tu.mobile', 'like', "%{$keyword}%");
                });
            }

            $total = $query->count();
            $list  = $query->order('m.id', 'desc')->page($page, $limit)->select()->toArray();
            foreach ($list as &$m) {
                $m['from_name'] = !empty($m['from_shop']) ? $m['from_shop'] : (!empty($m['from_nick']) ? $m['from_nick'] : $m['from_mobile']);
                $m['to_name']   = !empty($m['to_shop']) ? $m['to_shop'] : (!empty($m['to_nick']) ? $m['to_nick'] : $m['to_mobile']);
                $m['time_text'] = $m['create_time'] ? date('Y-m-d H:i', $m['create_time']) : '-';
            }
            unset($m);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        View::assign('menu_active', '/admin1314/message/index');
        return View::fetch();
    }

    /**
     * 修改聊天内容
     */
    public function update()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id      = (int)$this->request->post('id');
        $content = trim($this->request->post('content', ''));

        $msg = Db::name('message')->find($id);
        if (!$msg) {
            return json(['code' => 0, 'msg' => '信息不存在']);
        }
        if ($content === '') {
            return json(['code' => 0, 'msg' => '内容不能为空']);
        }
        if (mb_strlen($content) > 500) {
            return json(['code' => 0, 'msg' => '内容不能超过500字']);
        }

        Db::name('message')->where('id', $id)->update([
            'content'     => $content,
            'is_read'     => 0, // 修改后视为未读，提醒接收方重新查看
        ]);
        admin_log('修改咨询信息：ID ' . $id . '（发送者UID ' . $msg['from_uid'] . '）');
        return json(['code' => 1, 'msg' => '已保存']);
    }

    /**
     * 删除聊天信息
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');

        $msg = Db::name('message')->find($id);
        if (!$msg) {
            return json(['code' => 0, 'msg' => '信息不存在']);
        }

        Db::name('message')->where('id', $id)->delete();
        admin_log('删除咨询信息：ID ' . $id . '（发送者UID ' . $msg['from_uid'] . '）');
        return json(['code' => 1, 'msg' => '已删除']);
    }
}

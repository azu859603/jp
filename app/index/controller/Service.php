<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 在线客服：会员（买家/卖家）与平台管理员聊天
 *
 * 每个会员只有一个会话（user_id），对方固定是平台；后台任何管理员都可回复。
 * 消息类型：1 文本（最多 500 字）、2 图片（本站上传的 /uploads/ 地址）。
 * 实时性用 3 秒轮询，与买卖家聊天一致。
 */
class Service extends Base
{
    const MAX_HISTORY = 100;

    /**
     * 客服聊天页
     */
    public function index()
    {
        $this->checkLogin();
        $uid = (int)$this->user['id'];

        $messages = Db::name('service_message')
            ->where('user_id', $uid)
            ->order('id', 'desc')
            ->limit(self::MAX_HISTORY)
            ->select()
            ->toArray();
        $messages = array_reverse($messages);

        // 客服发来的标记已读
        Db::name('service_message')->where('user_id', $uid)->where('from_type', 2)->where('is_read', 0)->update(['is_read' => 1]);

        $lastId = $messages ? (int)end($messages)['id'] : 0;
        foreach ($messages as &$m) {
            $m['time_str'] = date('m-d H:i', $m['create_time']);
        }
        unset($m);

        View::assign([
            'messages'     => $messages,
            'last_id'      => $lastId,
            'service_link' => (string)get_setting('service_link', ''),
            'page_title'   => lang('在线客服'),
            'tab_active'   => 'mine',
        ]);
        return View::fetch();
    }

    /**
     * 发送消息（AJAX）
     */
    public function send()
    {
        if (!$this->request->isAjax() || !$this->request->isPost()) {
            return json(['code' => 0, 'msg' => lang('请求错误')]);
        }
        if (empty($this->user)) {
            return json(['code' => -1, 'msg' => lang('请先登录')]);
        }
        $uid  = (int)$this->user['id'];
        $type = (int)$this->request->post('type', 1) === 2 ? 2 : 1;
        $content = trim((string)$this->request->post('content', ''));

        if ($type === 2) {
            // 图片：只接受本站上传目录下的图片地址
            if (!preg_match('~^/uploads/[\w\-./]+\.(jpg|jpeg|png|gif|webp)$~i', $content)) {
                return json(['code' => 0, 'msg' => lang('图片地址不合法')]);
            }
        } else {
            $content = strip_tags($content);
            if ($content === '') {
                return json(['code' => 0, 'msg' => lang('请输入消息内容')]);
            }
            if (mb_strlen($content) > 500) {
                return json(['code' => 0, 'msg' => lang('消息不能超过500字')]);
            }
        }

        if (!message_rate_ok($uid)) {
            return json(['code' => 0, 'msg' => lang('发送太频繁，请3秒后再试')]);
        }

        $now = time();
        $id = Db::name('service_message')->insertGetId([
            'user_id'     => $uid,
            'from_type'   => 1,
            'admin_id'    => 0,
            'type'        => $type,
            'content'     => $content,
            'is_read'     => 0,
            'create_time' => $now,
        ]);
        return json(['code' => 1, 'msg' => lang('发送成功'), 'id' => $id, 'time_str' => date('m-d H:i', $now)]);
    }

    /**
     * 轮询新消息（AJAX）
     */
    public function poll()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0]);
        }
        if (empty($this->user)) {
            return json(['code' => -1]);
        }
        $uid    = (int)$this->user['id'];
        $lastId = (int)$this->request->param('last_id', 0);

        $messages = Db::name('service_message')
            ->where('user_id', $uid)
            ->where('id', '>', $lastId)
            ->order('id', 'asc')
            ->limit(50)
            ->select()
            ->toArray();

        $readIds = [];
        foreach ($messages as &$m) {
            $m['time_str'] = date('m-d H:i', $m['create_time']);
            if ((int)$m['from_type'] === 2 && !(int)$m['is_read']) {
                $readIds[] = $m['id'];
            }
        }
        unset($m);
        if ($readIds) {
            Db::name('service_message')->whereIn('id', $readIds)->update(['is_read' => 1]);
        }
        return json(['code' => 1, 'data' => $messages]);
    }
}

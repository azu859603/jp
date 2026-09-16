<?php
namespace app\index\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\View;

/**
 * 在线客服：会员（买家/卖家）或未登录游客 与 平台管理员聊天
 *
 * 登录会员：会话归属 user_id，对方固定是平台；后台任何管理员都可回复。
 * 游客模式：未登录也能发消息。会话用 session 里的随机 guest_key 标识（user_id = 0），
 *           只能发文本；同一 IP 每小时最多 30 条；登录 / 注册成功后游客消息自动并入该会员的会话。
 * 消息类型：1 文本（最多 500 字）、2 图片（本站上传的 /uploads/ 地址，仅登录会员）。
 * 实时性用 3 秒轮询，与买卖家聊天一致。
 */
class Service extends Base
{
    const MAX_HISTORY    = 100;
    const GUEST_IP_LIMIT = 30;   // 游客：同一 IP 每小时最多发送条数

    /**
     * 客服聊天页（登录会员 / 游客都可访问）
     */
    public function index()
    {
        $isGuest = empty($this->user);
        // 游客首次进入即分配会话标识，随 session 保持
        $conv = $this->conv(true);

        $messages = $this->convQuery($conv)
            ->order('id', 'desc')
            ->limit(self::MAX_HISTORY)
            ->select()
            ->toArray();
        $messages = array_reverse($messages);

        // 客服发来的标记已读
        $this->convQuery($conv)->where('from_type', 2)->where('is_read', 0)->update(['is_read' => 1]);

        $lastId = $messages ? (int)end($messages)['id'] : 0;
        foreach ($messages as &$m) {
            $m['time_str'] = date('m-d H:i', $m['create_time']);
        }
        unset($m);

        View::assign([
            'messages'     => $messages,
            'last_id'      => $lastId,
            'is_guest'     => $isGuest ? 1 : 0,
            'service_link' => (string)get_setting('service_link', ''),
            'page_title'   => lang('在线客服'),
            'tab_active'   => $isGuest ? 'index' : 'mine',
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
        $isGuest = empty($this->user);
        $conv    = $this->conv(false);
        if ($isGuest && $conv['guest_key'] === '') {
            // 没有会话标识（未先打开客服页 / session 失效）：让前端刷新页面重新分配
            return json(['code' => -2, 'msg' => lang('会话已失效，请刷新页面')]);
        }
        $type    = (int)$this->request->post('type', 1) === 2 ? 2 : 1;
        $content = trim((string)$this->request->post('content', ''));

        if ($type === 2) {
            if ($isGuest) {
                return json(['code' => 0, 'msg' => lang('游客模式只能发送文字，登录后可发送图片')]);
            }
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

        $rateKey = $isGuest ? 'g' . $conv['guest_key'] : (string)$conv['user_id'];
        if (!message_rate_ok($rateKey)) {
            return json(['code' => 0, 'msg' => lang('发送太频繁，请3秒后再试')]);
        }
        if ($isGuest && !$this->guestIpOk()) {
            return json(['code' => 0, 'msg' => lang('您发送的消息过多，请稍后再试或登录后继续')]);
        }

        $now = time();
        $id  = Db::name('service_message')->insertGetId([
            'user_id'     => $conv['user_id'],
            'guest_key'   => $conv['guest_key'],
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
        $conv = $this->conv(false);
        if (empty($this->user) && $conv['guest_key'] === '') {
            return json(['code' => -2]);
        }
        $lastId = (int)$this->request->param('last_id', 0);

        $messages = $this->convQuery($conv)
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

    // ------------------------------------------------------------------

    /**
     * 当前会话归属：登录会员 → user_id；游客 → session 里的 guest_key
     * @param bool $create 游客没有标识时是否立即分配
     */
    protected function conv($create)
    {
        if (!empty($this->user)) {
            return ['user_id' => (int)$this->user['id'], 'guest_key' => ''];
        }
        return ['user_id' => 0, 'guest_key' => service_guest_key($create)];
    }

    /**
     * 该会话的消息查询
     */
    protected function convQuery(array $conv)
    {
        $q = Db::name('service_message');
        if ($conv['user_id'] > 0) {
            return $q->where('user_id', $conv['user_id']);
        }
        return $q->where('user_id', 0)->where('guest_key', $conv['guest_key']);
    }

    /**
     * 游客按 IP 限流：每小时最多 GUEST_IP_LIMIT 条
     */
    protected function guestIpOk()
    {
        $key = 'svc_guest_ip_' . md5($this->request->ip());
        $n   = (int)Cache::get($key, 0);
        if ($n >= self::GUEST_IP_LIMIT) {
            return false;
        }
        Cache::set($key, $n + 1, 3600);
        return true;
    }
}

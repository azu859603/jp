<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 在线客服工作台：查看所有会员 / 游客的客服会话并回复
 *
 * 会话标识 key：
 *   u:<user_id>    登录会员的会话（service_message.user_id > 0）
 *   g:<guest_key>  未登录游客的会话（user_id = 0，guest_key 为 session 里的随机串）
 * 任何管理员都可回复，回复记录管理员ID。列表 8 秒、当前会话 3 秒轮询。
 */
class Service extends Base
{
    /**
     * 工作台页面 / 会话列表（AJAX）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $keyword    = trim((string)$this->request->param('keyword', ''));
            $onlyUnread = (int)$this->request->param('unread', 0) === 1;

            // 每个会员最后一条消息
            $lastRows   = Db::name('service_message')->where('user_id', '>', 0)->field('user_id, MAX(id) AS last_id')->group('user_id')->select()->toArray();
            $lastIds    = array_column($lastRows, 'last_id');
            $lastMsgs   = $lastIds ? Db::name('service_message')->whereIn('id', $lastIds)->select()->toArray() : [];
            $lastByUser = array_column($lastMsgs, null, 'user_id');

            // 每个游客最后一条消息
            $guestRows    = Db::name('service_message')->where('user_id', 0)->where('guest_key', '<>', '')->field('guest_key, MAX(id) AS last_id')->group('guest_key')->select()->toArray();
            $guestIds     = array_column($guestRows, 'last_id');
            $guestMsgs    = $guestIds ? Db::name('service_message')->whereIn('id', $guestIds)->select()->toArray() : [];
            $lastByGuest  = array_column($guestMsgs, null, 'guest_key');

            // 有搜索词时，把没有会话记录的会员也列出来（标记为「未开始」），客服可主动发起聊天
            $newUsers = [];
            if ($keyword !== '' && !$onlyUnread) {
                $q = Db::name('user')->field('id,nickname,account as mobile,avatar,is_seller,shop_name')->where('status', 1)
                    ->where(function ($q) use ($keyword) {
                        $q->whereLike('account', "%{$keyword}%")->whereOr('nickname', 'like', "%{$keyword}%")->whereOr('shop_name', 'like', "%{$keyword}%");
                    });
                if ($lastByUser) {
                    $q->whereNotIn('id', array_keys($lastByUser));
                }
                $newUsers = $q->order('id', 'desc')->limit(20)->select()->toArray();
            }
            if (empty($lastRows) && empty($guestRows) && empty($newUsers)) {
                return json(['code' => 0, 'msg' => '', 'data' => [], 'total_unread' => 0]);
            }

            // 各会员 / 各游客未读（对方发来、客服未读）
            $unreadRows   = Db::name('service_message')->where('user_id', '>', 0)->field('user_id, COUNT(*) AS c')
                ->where('from_type', 1)->where('is_read', 0)->group('user_id')->select()->toArray();
            $unreadByUser = array_column($unreadRows, 'c', 'user_id');
            $unreadGuestRows = Db::name('service_message')->where('user_id', 0)->field('guest_key, COUNT(*) AS c')
                ->where('from_type', 1)->where('is_read', 0)->group('guest_key')->select()->toArray();
            $unreadByGuest = array_column($unreadGuestRows, 'c', 'guest_key');

            $userIds = array_keys($lastByUser) ?: [0];
            $usersQ  = Db::name('user')->whereIn('id', $userIds)->field('id,nickname,account as mobile,avatar,is_seller,shop_name');
            if ($keyword !== '') {
                $usersQ->where(function ($q) use ($keyword) {
                    $q->whereLike('account', "%{$keyword}%")->whereOr('nickname', 'like', "%{$keyword}%")->whereOr('shop_name', 'like', "%{$keyword}%");
                });
            }
            $users = array_column($usersQ->select()->toArray(), null, 'id');

            $list = [];
            foreach ($lastByUser as $uid => $m) {
                if (!isset($users[$uid])) {
                    continue;
                }
                $unread = (int)($unreadByUser[$uid] ?? 0);
                if ($onlyUnread && $unread === 0) {
                    continue;
                }
                $u = $users[$uid];
                $list[] = [
                    'key'       => 'u:' . (int)$uid,
                    'is_guest'  => 0,
                    'user_id'   => (int)$uid,
                    'nickname'  => $u['nickname'],
                    'mobile'    => $u['mobile'],
                    'avatar'    => $u['avatar'],
                    'is_seller' => (int)$u['is_seller'],
                    'shop_name' => $u['shop_name'],
                    'last_id'   => (int)$m['id'],
                    'last_text' => (int)$m['type'] === 2 ? '[图片]' : mb_substr((string)$m['content'], 0, 40),
                    'last_from' => (int)$m['from_type'],
                    'last_time' => date('m-d H:i', $m['create_time']),
                    'unread'    => $unread,
                ];
            }
            // 游客会话：搜索词为「游客」或游客编号时命中
            foreach ($lastByGuest as $gk => $m) {
                $name = self::guestName($gk);
                if ($keyword !== '' && mb_stripos($name, $keyword) === false && stripos((string)$gk, $keyword) === false) {
                    continue;
                }
                $unread = (int)($unreadByGuest[$gk] ?? 0);
                if ($onlyUnread && $unread === 0) {
                    continue;
                }
                $list[] = [
                    'key'       => 'g:' . $gk,
                    'is_guest'  => 1,
                    'user_id'   => 0,
                    'nickname'  => $name,
                    'mobile'    => '',
                    'avatar'    => '',
                    'is_seller' => 0,
                    'shop_name' => '',
                    'last_id'   => (int)$m['id'],
                    'last_text' => (int)$m['type'] === 2 ? '[图片]' : mb_substr((string)$m['content'], 0, 40),
                    'last_from' => (int)$m['from_type'],
                    'last_time' => date('m-d H:i', $m['create_time']),
                    'unread'    => $unread,
                ];
            }
            usort($list, function ($a, $b) {
                return $b['last_id'] <=> $a['last_id'];
            });
            // 搜索命中的、尚无会话的会员排在已有会话之后
            foreach ($newUsers as $u) {
                $list[] = [
                    'key'       => 'u:' . (int)$u['id'],
                    'is_guest'  => 0,
                    'user_id'   => (int)$u['id'],
                    'nickname'  => $u['nickname'],
                    'mobile'    => $u['mobile'],
                    'avatar'    => $u['avatar'],
                    'is_seller' => (int)$u['is_seller'],
                    'shop_name' => $u['shop_name'],
                    'last_id'   => 0,
                    'last_text' => '',
                    'last_from' => 0,
                    'last_time' => '',
                    'unread'    => 0,
                    'is_new'    => 1,
                ];
            }
            $totalUnread = (int)array_sum($unreadByUser) + (int)array_sum($unreadByGuest);
            return json(['code' => 0, 'msg' => '', 'data' => $list, 'total_unread' => $totalUnread]);
        }

        View::assign('menu_active', '/admin1314/service/index');
        return View::fetch();
    }

    /**
     * 某个会话的消息（AJAX）：首次拉最近 100 条，之后按 last_id 增量
     * 参数 key（u:<user_id> / g:<guest_key>）；兼容旧的 user_id 参数
     */
    public function detail()
    {
        $conv = $this->parseKey();
        if (!$conv) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        $lastId = (int)$this->request->param('last_id', 0);

        if ($conv['type'] === 'u') {
            $user = Db::name('user')->where('id', $conv['user_id'])->field('id,nickname,account as mobile,avatar,is_seller,shop_name,reg_time')->find();
            if (!$user) {
                return json(['code' => 0, 'msg' => '会员不存在']);
            }
            $user['is_guest']      = 0;
            $user['reg_time_text'] = $user['reg_time'] ? date('Y-m-d', $user['reg_time']) : '-';
        } else {
            $user = [
                'id'            => 0,
                'is_guest'      => 1,
                'nickname'      => self::guestName($conv['guest_key']),
                'mobile'        => '',
                'avatar'        => '',
                'is_seller'     => 0,
                'shop_name'     => '',
                'reg_time'      => 0,
                'reg_time_text' => '-',
            ];
        }

        $q = $this->convQuery($conv);
        if ($lastId > 0) {
            $messages = $q->where('id', '>', $lastId)->order('id', 'asc')->limit(100)->select()->toArray();
        } else {
            $messages = array_reverse($q->order('id', 'desc')->limit(100)->select()->toArray());
        }
        foreach ($messages as &$m) {
            $m['time_str'] = date('Y-m-d H:i', $m['create_time']);
        }
        unset($m);

        // 对方发来的标记为已读
        $this->convQuery($conv)->where('from_type', 1)->where('is_read', 0)->update(['is_read' => 1]);

        return json(['code' => 1, 'user' => $user, 'data' => $messages, 'key' => $conv['key']]);
    }

    /**
     * 客服回复（AJAX）
     */
    public function send()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $conv = $this->parseKey();
        if (!$conv) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        $type    = (int)$this->request->post('type', 1) === 2 ? 2 : 1;
        $content = trim((string)$this->request->post('content', ''));
        if ($conv['type'] === 'u' && !Db::name('user')->where('id', $conv['user_id'])->find()) {
            return json(['code' => 0, 'msg' => '会员不存在']);
        }
        if ($conv['type'] === 'g' && !$this->convQuery($conv)->find()) {
            return json(['code' => 0, 'msg' => '该游客会话不存在']);
        }
        if ($type === 2) {
            if (!preg_match('~^/uploads/[\w\-./]+\.(jpg|jpeg|png|gif|webp)$~i', $content)) {
                return json(['code' => 0, 'msg' => '图片地址不合法']);
            }
        } else {
            $content = strip_tags($content);
            if ($content === '') {
                return json(['code' => 0, 'msg' => '请输入回复内容']);
            }
            if (mb_strlen($content) > 500) {
                return json(['code' => 0, 'msg' => '回复不能超过500字']);
            }
        }
        $now = time();
        $id = Db::name('service_message')->insertGetId([
            'user_id'     => $conv['user_id'],
            'guest_key'   => $conv['guest_key'],
            'from_type'   => 2,
            'admin_id'    => (int)$this->admin['id'],
            'type'        => $type,
            'content'     => $content,
            'is_read'     => 0,
            'create_time' => $now,
        ]);
        return json(['code' => 1, 'msg' => '已发送', 'id' => $id, 'time_str' => date('Y-m-d H:i', $now)]);
    }

    /**
     * 未读总数（菜单角标轮询用）：会员 + 游客
     */
    public function unread()
    {
        $n = Db::name('service_message')->where('from_type', 1)->where('is_read', 0)->count();
        return json(['code' => 1, 'unread' => $n]);
    }

    // ------------------------------------------------------------------

    /**
     * 解析会话标识：key=u:12 / g:abcdef…；兼容 user_id=12
     * @return array|null ['type'=>'u'|'g','user_id'=>int,'guest_key'=>string,'key'=>string]
     */
    protected function parseKey()
    {
        $key = trim((string)$this->request->param('key', ''));
        if ($key === '') {
            $uid = (int)$this->request->param('user_id', 0);
            $key = $uid > 0 ? 'u:' . $uid : '';
        }
        if (preg_match('/^u:(\d+)$/', $key, $m) && (int)$m[1] > 0) {
            return ['type' => 'u', 'user_id' => (int)$m[1], 'guest_key' => '', 'key' => $key];
        }
        if (preg_match('/^g:([a-f0-9]{32})$/', $key, $m)) {
            return ['type' => 'g', 'user_id' => 0, 'guest_key' => $m[1], 'key' => $key];
        }
        return null;
    }

    /**
     * 该会话的消息查询
     */
    protected function convQuery(array $conv)
    {
        $q = Db::name('service_message');
        if ($conv['type'] === 'u') {
            return $q->where('user_id', $conv['user_id']);
        }
        return $q->where('user_id', 0)->where('guest_key', $conv['guest_key']);
    }

    /**
     * 游客显示名：游客 + 标识前 6 位
     */
    protected static function guestName($guestKey)
    {
        return '游客 ' . strtoupper(substr((string)$guestKey, 0, 6));
    }
}

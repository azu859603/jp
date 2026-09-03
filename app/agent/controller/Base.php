<?php
namespace app\agent\controller;

use app\BaseController;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\View;

/**
 * 代理后台基类
 *
 * 说明：
 * 1. 登录态直接复用前台会员 session('user')，不单独登录；
 * 2. 不继承 app\index\controller\Base，避免把 settle_expired_goods() 的全表扫描带进后台列表页；
 * 3. 一期为一级代理：数据范围恒定为 user.pid = 当前代理ID，所有查询必须经过
 *    memberIds() / assertMyMember() 收口，禁止在子类里裸查 user / order / bid_record。
 */
class Base extends BaseController
{
    /** @var array 当前代理（前台会员） */
    protected $agent = [];

    /** @var int 当前代理ID */
    protected $uid = 0;

    /** @var array|null 下级会员ID缓存 */
    private $memberIdCache = null;

    /** @var array 代理后台菜单 */
    protected $menus = [
        'dashboard' => [
            'title' => '团队概览',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7.5" height="7.5" rx="1"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1"/></svg>',
            'items' => [
                ['title' => '数据概览', 'url' => '/agent/index/index'],
            ],
        ],
        'member' => [
            'title' => '会员管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16.5 14.5a5 5 0 0 1 5 5"/></svg>',
            'items' => [
                ['title' => '我的会员', 'url' => '/agent/member/index'],
                ['title' => '实名认证', 'url' => '/agent/member/auth'],
                ['title' => '卖家审核', 'url' => '/agent/member/seller'],
            ],
        ],
        'goods' => [
            'title' => '产品管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8 12 3 3 8v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>',
            'items' => [
                ['title' => '产品列表', 'url' => '/agent/goods/index'],
                ['title' => '产品审核', 'url' => '/agent/goods/check'],
            ],
        ],
        'report' => [
            'title' => '数据报表',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="4" y1="20" x2="20" y2="20"/><rect x="6" y="11" width="3" height="7" rx="1"/><rect x="11" y="6" width="3" height="12" rx="1"/><rect x="16" y="14" width="3" height="4" rx="1"/></svg>',
            'items' => [
                ['title' => '团队业绩', 'url' => '/agent/report/index'],
            ],
        ],
    ];

    protected function initialize()
    {
        parent::initialize();

        $sessUser = session('user');
        if (empty($sessUser['id'])) {
            $this->reject('请先登录', '/agent/login/index');
        }

        // 实时回读，避免 session 里的旧状态（被禁用 / 被取消代理资格）继续生效
        $user = Db::name('user')->where('id', $sessUser['id'])->find();
        if (!$user || $user['status'] != 1) {
            session('user', null);
            $this->reject('账号状态异常，请重新登录', '/agent/login/index');
        }
        if ((int)$user['is_agent'] !== 1) {
            // 代理资格被取消：留在代理登录页给出说明，不静默踢回前台
            $this->reject('您不是代理，无权访问代理中心', '/agent/login/index');
        }

        unset($user['password']);
        $this->agent = $user;
        $this->uid   = (int)$user['id'];

        View::assign([
            'agent'     => $user,
            'menus'     => $this->menus,
            'site_name' => $this->siteName(),
        ]);
    }

    /**
     * 拒绝访问：AJAX 返回 JSON，页面请求 302
     */
    protected function reject($msg, $url)
    {
        if ($this->request->isAjax()) {
            throw new HttpResponseException(json(['code' => -1, 'msg' => $msg, 'url' => $url]));
        }
        throw new HttpResponseException(response('', 302, ['Location' => $url]));
    }

    /**
     * 站点名称（agent 应用不加载 index/common.php，这里单独读一次）
     */
    protected function siteName()
    {
        static $name = null;
        if ($name === null) {
            $name = (string)Db::name('setting')->where('name', 'site_name')->value('value');
            if ($name === '') {
                $name = '竞拍商城';
            }
        }
        return $name;
    }

    // ------------------------------------------------------------------
    // 数据范围收口
    // ------------------------------------------------------------------

    /**
     * 我的下级会员查询构造器（一级：pid = 我）
     * 子类查会员一律从这里起手，不要自己写 Db::name('user')
     * @return \think\db\Query
     */
    protected function memberQuery()
    {
        return Db::name('user')->where('pid', $this->uid);
    }

    /**
     * 我的下级会员ID数组（单次请求内缓存）
     * @return array
     */
    protected function memberIds()
    {
        if ($this->memberIdCache === null) {
            $this->memberIdCache = array_map('intval', $this->memberQuery()->column('id'));
        }
        return $this->memberIdCache;
    }

    /**
     * 是否没有任何下级（用于提前短路，避免 whereIn 空数组产生的意外全表匹配）
     * @return bool
     */
    protected function hasNoMember()
    {
        return count($this->memberIds()) === 0;
    }

    /**
     * 归属校验：确认该会员ID确实是我的下级，否则直接拒绝
     * 所有按 id 取单条数据的入口（详情页等）必须先过这里
     * @param int $id
     * @return array 会员数据
     */
    protected function assertMyMember($id)
    {
        $id = (int)$id;
        $member = $id > 0 ? $this->memberQuery()->where('id', $id)->find() : null;
        if (empty($member)) {
            if ($this->request->isAjax()) {
                throw new HttpResponseException(json(['code' => 0, 'msg' => '该会员不在您的团队中']));
            }
            throw new HttpResponseException(response('', 302, ['Location' => '/agent/member/index']));
        }
        unset($member['password']);
        return $member;
    }

    // ------------------------------------------------------------------
    // 产品范围收口（卖家 ∈ 我的下级）
    // ------------------------------------------------------------------

    /**
     * 我的下级中已审核通过的卖家ID（代发布产品只能发到这些店铺）
     * @return array
     */
    protected function sellerIds()
    {
        return array_map('intval', $this->memberQuery()
            ->where('is_seller', 1)
            ->where('seller_check', 1)
            ->column('id'));
    }

    /**
     * 团队产品查询构造器：卖家必须是我的下级
     * 无下级时返回恒为空的查询，避免 whereIn 空数组产生意外全表匹配
     * @param string $alias 表别名（需要 join 时传入）
     * @return \think\db\Query
     */
    protected function goodsQuery($alias = '')
    {
        $q = Db::name('goods');
        if ($alias !== '') {
            $q->alias($alias);
        }
        $prefix = $alias !== '' ? $alias . '.' : '';
        $ids = $this->memberIds();
        if (empty($ids)) {
            return $q->where($prefix . 'id', -1);
        }
        return $q->whereIn($prefix . 'seller_id', $ids);
    }

    /**
     * 归属校验：该产品的卖家必须是我的下级，否则直接拒绝
     * 所有按 id 操作产品的入口（详情/审核/上下架/删除）必须先过这里
     * @param int $id
     * @return array 产品数据
     */
    protected function assertMyGoods($id)
    {
        $id = (int)$id;
        $goods = $id > 0 ? $this->goodsQuery()->where('id', $id)->find() : null;
        if (empty($goods)) {
            if ($this->request->isAjax()) {
                throw new HttpResponseException(json(['code' => 0, 'msg' => '该产品不属于您的团队']));
            }
            throw new HttpResponseException(response('', 302, ['Location' => '/agent/goods/index']));
        }
        return $goods;
    }

    // ------------------------------------------------------------------
    // 通用输出
    // ------------------------------------------------------------------

    /**
     * 手机号脱敏
     */
    protected function maskMobile($mobile)
    {
        $mobile = (string)$mobile;
        if (strlen($mobile) < 7) {
            return $mobile;
        }
        return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
    }

    /**
     * 分页参数
     * @return array
     */
    protected function pageParam()
    {
        return [
            'page'  => max(1, (int)$this->request->param('page', 1)),
            'limit' => max(1, min(100, (int)$this->request->param('limit', 15))),
        ];
    }

    /**
     * 统计区间参数（报表/概览共用）
     * @return array [startTime, endTime, startDate, endDate]
     */
    protected function rangeParam($defaultDays = 30)
    {
        $startDate = trim($this->request->param('start_date', date('Y-m-d', strtotime('-' . ($defaultDays - 1) . ' days'))));
        $endDate   = trim($this->request->param('end_date', date('Y-m-d')));

        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime   = strtotime($endDate . ' 23:59:59');
        if (!$startTime || !$endTime || $startTime > $endTime) {
            $startTime = strtotime('-' . ($defaultDays - 1) . ' days 00:00:00');
            $endTime   = strtotime('today 23:59:59');
            $startDate = date('Y-m-d', $startTime);
            $endDate   = date('Y-m-d', $endTime);
        }
        return [$startTime, $endTime, $startDate, $endDate];
    }
}

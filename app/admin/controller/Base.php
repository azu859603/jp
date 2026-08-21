<?php
namespace app\admin\controller;

use app\BaseController;
use think\exception\HttpResponseException;
use think\facade\View;

/**
 * 后台基类
 */
class Base extends BaseController
{
    /**
     * 当前管理员
     * @var array
     */
    protected $admin = [];

    /**
     * 后台菜单
     * @var array
     */
    protected $menus = [
        'dashboard' => [
            'title' => '仪表盘',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7.5" height="7.5" rx="1"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1"/></svg>',
            'items' => [
                ['title' => '系统概览', 'url' => '/admin1314/index/index'],
            ],
        ],
        'member' => [
            'title' => '会员管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16.5 14.5a5 5 0 0 1 5 5"/></svg>',
            'items' => [
                ['title' => '会员列表', 'url' => '/admin1314/member/index'],
                ['title' => '实名认证', 'url' => '/admin1314/member/auth'],
                ['title' => '卖家审核', 'url' => '/admin1314/member/seller'],
                ['title' => '咨询审核', 'url' => '/admin1314/message/index'],
            ],
        ],
        'goods' => [
            'title' => '商品管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8 12 3 3 8v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>',
            'items' => [
                ['title' => '商品列表', 'url' => '/admin1314/goods/index'],
                ['title' => '商品审核', 'url' => '/admin1314/goods/check'],
                ['title' => '分类管理', 'url' => '/admin1314/category/index'],
            ],
        ],
        'auction' => [
            'title' => '竞拍管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.5 4.5 19.5 9.5 11 18l-5-5 8.5-8.5z"/><path d="M6 13l-3 3a2 2 0 0 0 0 2.8l2.2 2.2a2 2 0 0 0 2.8 0l3-3"/></svg>',
            'items' => [
                ['title' => '出价记录', 'url' => '/admin1314/bid/index'],
            ],
        ],
        'order' => [
            'title' => '订单管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/></svg>',
            'items' => [
                ['title' => '订单列表', 'url' => '/admin1314/order/index'],
                ['title' => '售后管理', 'url' => '/admin1314/after_sale/index'],
            ],
        ],
        'finance' => [
            'title' => '财务管理',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="14" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="16.5" cy="15" r="1.5"/></svg>',
            'items' => [
                ['title' => '充值审核', 'url' => '/admin1314/recharge/index'],
                ['title' => '提现审核', 'url' => '/admin1314/withdraw/index'],
                ['title' => '余额流水', 'url' => '/admin1314/balance/index'],
            ],
        ],
        'system' => [
            'title' => '系统设置',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="4" y1="7" x2="20" y2="7"/><circle cx="9" cy="7" r="2"/><line x1="4" y1="17" x2="20" y2="17"/><circle cx="15" cy="17" r="2"/></svg>',
            'items' => [
                ['title' => '基础设置', 'url' => '/admin1314/setting/index'],
                ['title' => '轮播管理', 'url' => '/admin1314/banner/index'],
                ['title' => '新闻管理', 'url' => '/admin1314/news/index'],
                ['title' => '管理员管理', 'url' => '/admin1314/admin_user/index'],
                ['title' => '操作日志', 'url' => '/admin1314/log/index'],
            ],
        ],
    ];

    protected function initialize()
    {
        parent::initialize();

        $admin = session('admin');
        if (empty($admin)) {
            if ($this->request->isAjax()) {
                throw new HttpResponseException(json(['code' => -1, 'msg' => '请先登录']));
            }
            throw new HttpResponseException(response('', 302, ['Location' => '/admin1314/login/index']));
        }
        $this->admin = $admin;
        View::assign('admin', $admin);
        View::assign('menus', $this->menus);
    }

    /**
     * 成功提示
     * @param string $msg
     * @param string|null $url
     * @param array $data
     * @return \think\response\Json|\think\response\Redirect
     */
    protected function success($msg = '操作成功', $url = null, $data = [])
    {
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
        }
        return $this->jumpHtml($msg, $url);
    }

    /**
     * 失败提示
     * @param string $msg
     * @param string|null $url
     * @return \think\response\Json|\think\response\Response
     */
    protected function error($msg = '操作失败', $url = null)
    {
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => $msg]);
        }
        return $this->jumpHtml($msg, $url);
    }

    /**
     * 非 AJAX 场景输出提示并跳转
     * @param string $msg
     * @param string|null $url
     * @return \think\response\Response
     */
    protected function jumpHtml($msg, $url = null)
    {
        $jsMsg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        if (!empty($url)) {
            $jsUrl = json_encode($url, JSON_UNESCAPED_UNICODE);
            $script = 'alert(' . $jsMsg . ');location.href=' . $jsUrl . ';';
        } else {
            $script = 'alert(' . $jsMsg . ');history.back(-1);';
        }
        return response('<html><head><meta charset="utf-8"><title>提示</title></head><body><script>' . $script . '</script></body></html>');
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
}

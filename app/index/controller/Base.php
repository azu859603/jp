<?php
namespace app\index\controller;

use app\BaseController;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Lang;
use think\facade\View;

class Base extends BaseController
{
    /** @var array|null 当前登录用户 */
    protected $user = null;

    protected function initialize()
    {
        parent::initialize();

        // 结算到期商品
        try {
            settle_expired_goods();
        } catch (\Throwable $e) {
        }

        // 手动检测并切换语言（URL参数 ?lang= 优先，其次 Cookie）
        $allowLang = ['zh-cn', 'zh-tw', 'en-us'];
        $detectLang = $this->request->param('lang', '');
        if (!$detectLang || !in_array($detectLang, $allowLang)) {
            $detectLang = $this->request->cookie('think_lang', '');
        }
        if ($detectLang && in_array($detectLang, $allowLang) && $detectLang != Lang::getLangSet()) {
            Lang::switchLangSet($detectLang);
        }

        // 登录用户
        $user = session('user');
        if (!empty($user)) {
            $user = Db::name('user')->where('id', $user['id'])->find();
            if (!$user || $user['status'] != 1) {
                session('user', null);
                $user = null;
            } else {
                session('user', $user);
            }
        }
        $this->user = $user;

        $settings = site_settings();
        View::assign([
            'user'     => $user,
            'settings' => $settings,
            'site_name'=> !empty($settings['site_name']) ? $settings['site_name'] : '竞拍商城',
            'current_lang' => Lang::getLangSet(),
        ]);
    }

    /**
     * 校验登录，未登录抛出跳转/JSON
     */
    protected function checkLogin()
    {
        if (empty($this->user)) {
            if ($this->request->isAjax()) {
                throw new HttpResponseException(json(['code' => -1, 'msg' => lang('请先登录')]));
            }
            throw new HttpResponseException(response('', 302, ['Location' => '/user/login']));
        }
    }

    /**
     * 校验卖家身份
     */
    protected function checkSeller()
    {
        $this->checkLogin();
        if ($this->user['is_seller'] != 1 || $this->user['seller_check'] != 1) {
            if ($this->request->isAjax()) {
                throw new HttpResponseException(json(['code' => -1, 'msg' => lang('您还不是卖家，请先申请入驻')]));
            }
            throw new HttpResponseException(response('', 302, ['Location' => '/seller/apply']));
        }
    }

    protected function success($msg = null, $url = null, $data = [])
    {
        if ($this->request->isAjax()) {
            $msg = $msg ?: lang('操作成功');
            return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
        }
        return $this->jumpHtml($msg ?: lang('操作成功'), $url);
    }

    protected function error($msg = null, $url = null)
    {
        if ($this->request->isAjax()) {
            $msg = $msg ?: lang('操作失败');
            return json(['code' => 0, 'msg' => $msg]);
        }
        return $this->jumpHtml($msg ?: lang('操作失败'), $url);
    }

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
     * 写余额流水
     */
    protected function addBalanceLog($userId, $type, $amount, $balance, $remark)
    {
        $user = Db::name('user')->where('id', $userId)->field('id, is_virtual')->find();
        if ($user && (int)$user['is_virtual'] === 1) {
            // 虚拟会员：不审计账单流水，余额恒等于系统设置的永存金额
            $vb = (float)get_setting('virtual_balance', 0);
            Db::name('user')->where('id', $userId)->update([
                'balance'     => $vb,
                'update_time' => time(),
            ]);
            return;
        }
        Db::name('balance_log')->insert([
            'user_id'     => $userId,
            'type'        => $type,
            'amount'      => $amount,
            'balance'     => $balance,
            'remark'      => $remark,
            'create_time' => time(),
        ]);
    }
}

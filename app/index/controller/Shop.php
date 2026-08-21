<?php

namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 店铺
 */
class Shop extends Base
{
    /**
     * 店铺主页
     * 顶部店铺基本信息 + 店铺拍品
     */
    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) {
            return redirect('/');
        }
        $seller = Db::name('user')
            ->where('id', $id)
            ->where('is_seller', 1)
            ->where('seller_check', 1)
            ->where('status', 1)
            ->find();
        if (!$seller) {
            return redirect('/');
        }
        $now = time();

        // 店铺名称（店铺名回退昵称）
        $shopName = !empty($seller['shop_name']) ? $seller['shop_name'] : $seller['nickname'];

        // 企业认证（实名已通过且填写了企业名称）
        $enterpriseAuth = $seller['auth_status'] == 2 && !empty($seller['company_name']);

        // 粉丝数
        $fans = Db::name('seller_follow')->where('seller_id', $id)->count();

        // 是否已关注
        $followed = 0;
        if (!empty($this->user)) {
            $followed = Db::name('seller_follow')->where('user_id', $this->user['id'])->where('seller_id', $id)->count() > 0 ? 1 : 0;
        }

        // 店铺拍品（拍卖中）
        $goods = Db::name('goods')->alias('g')
            ->field('g.*')
            ->where('g.seller_id', $id)
            ->where('g.status', 1)
            ->where('g.start_time', '<=', $now)
            ->where('g.end_time', '>', $now)
            ->order('g.id', 'desc')
            ->limit(20)->select()->toArray();
        foreach ($goods as &$g) {
            $top = Db::name('bid_record')->where('goods_id', $g['id'])->where('status', 0)->max('price');
            $g['current_price'] = max((float)$top, (float)$g['start_price']);
            $g['price_str'] = number_format($g['current_price'], 2, '.', ',');
            $g['remain_sec'] = max($g['end_time'] - $now, 0);
        }
        unset($g);

        View::assign([
            'seller'         => $seller,
            'shop_name'      => $shopName,
            'enterprise_auth'=> $enterpriseAuth,
            'fans'           => $fans,
            'followed'       => $followed,
            'goods'          => $goods,
            'now'            => $now,
            'page_title'     => $shopName,
            'tab_active'     => 'index',
        ]);
        return View::fetch();
    }
}

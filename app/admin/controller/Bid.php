<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Bid extends Base
{
    /**
     * 出价记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 15);
            $keyword = trim($this->request->param('keyword', ''));
            $goodsId = $this->request->param('goods_id', '');

            $query = Db::name('bid_record')->alias('b')
                ->leftJoin('goods g', 'b.goods_id = g.id')
                ->leftJoin('user u', 'b.user_id = u.id')
                ->leftJoin('user su', 'g.seller_id = su.id')
                ->field('b.*, g.title as goods_title, su.account as seller_mobile, u.account as mobile, u.nickname');

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    // 只按拍品标题 / 卖家账号检索：买家账号在列表里是脱敏的，买家昵称也不参与
                    $q->whereLike('g.title', "%{$keyword}%")
                        ->whereOr('su.account', 'like', "%{$keyword}%");
                });
            }
            if ($goodsId !== '') {
                $query->where('b.goods_id', (int)$goodsId);
            }

            $total = $query->count();
            $list = $query->order('b.id', 'desc')->page($page, $limit)->select()->toArray();
            // 买家账号脱敏；卖家账号原样返回，后台要按它检索
            foreach ($list as &$b) {
                $b['mobile'] = mask_mobile($b['mobile']);
            }
            unset($b);

            return json(['code' => 0, 'msg' => '', 'count' => $total, 'data' => $list]);
        }

        // 手动添加出价：拍品与买家改为按关键词搜索（searchGoods / searchUser），不再预加载下拉
        View::assign(['menu_active' => '/admin1314/bid/index']);
        return View::fetch();
    }

    /**
     * 手动添加出价记录（买家为已创建的会员）
     */
    public function add()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $goodsId = (int)$this->request->post('goods_id', 0);
        $userId  = (int)$this->request->post('user_id', 0);
        $price   = round((float)$this->request->post('price', 0), 2);

        if ($goodsId <= 0 || $userId <= 0 || $price <= 0) {
            return json(['code' => 0, 'msg' => '请选择商品、买家并填写出价金额']);
        }

        Db::startTrans();
        try {
            $goods = Db::name('goods')->where('id', $goodsId)->lock(true)->find();
            if (!$goods) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '商品不存在']);
            }
            if ($goods['status'] != 1) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '该商品不在拍卖中']);
            }
            $now = time();
            if ($now < $goods['start_time']) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '拍卖尚未开始']);
            }
            if ($now >= $goods['end_time']) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '拍卖已结束']);
            }

            $user = Db::name('user')->where('id', $userId)->find();
            if (!$user || $user['status'] != 1) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '买家不存在或已被禁用']);
            }
            if ((int)$goods['seller_id'] === $userId) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '卖家不能给自己的商品出价']);
            }

            // 当前最高价 + 阶梯校验（与前台一致）
            $topBid = Db::name('bid_record')
                ->where('goods_id', $goodsId)
                ->where('status', 0)
                ->order('price', 'desc')
                ->order('id', 'asc')
                ->find();
            if ($topBid && (int)$topBid['user_id'] === $userId) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '该买家已是当前最高出价者，无需再次出价']);
            }
            $topPrice = $topBid ? (float)$topBid['price'] : (float)$goods['start_price'];
            $raise = (float)$goods['raise_price'] > 0 ? (float)$goods['raise_price'] : 1;
            // 第一手可直接出起拍价；有出价后每手不低于当前价 + 加价幅度
            $minPrice = $topBid ? round($topPrice + $raise, 2) : round($topPrice, 2);
            if ($price < $minPrice) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '出价不能低于最低出价 ' . number_format($minPrice, 2) . ' 元']);
            }
            $steps = ($price - $topPrice) / $raise;
            if (abs($steps - round($steps)) > 0.0001) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '出价必须按加价幅度 ' . number_format($raise, 2) . ' 元递增']);
            }

            // 真实买家首次出价冻结保证金（与前台一致）；虚拟会员不冻结
            $freeze = 0.00;
            if ((int)$user['is_virtual'] !== 1 && (float)$goods['deposit'] > 0) {
                $paid = (float)Db::name('bid_record')->where('goods_id', $goodsId)->where('user_id', $userId)->where('deposit', '>', 0)->max('deposit');
                if ($paid <= 0) {
                    $bu      = Db::name('user')->where('id', $userId)->lock(true)->find();
                    $deposit = (float)$goods['deposit'];
                    if (!$bu || (float)$bu['balance'] < $deposit) {
                        Db::rollback();
                        return json(['code' => 0, 'msg' => '买家可用余额不足以冻结保证金 ' . number_format($deposit, 2) . ' 元']);
                    }
                    $nb = round($bu['balance'] - $deposit, 2);
                    $nf = round($bu['freeze_balance'] + $deposit, 2);
                    Db::name('user')->where('id', $userId)->update(['balance' => $nb, 'freeze_balance' => $nf, 'update_time' => $now]);
                    Db::name('balance_log')->insert([
                        'user_id'     => $userId,
                        'type'        => 'deposit',
                        'amount'      => -$deposit,
                        'balance'     => $nb,
                        'remark'      => '拍卖保证金（' . $goods['title'] . '）',
                        'create_time' => $now,
                    ]);
                    $freeze = $deposit;
                }
            }

            Db::name('bid_record')->insert([
                'goods_id'    => $goodsId,
                'user_id'     => $userId,
                'price'       => $price,
                'status'      => 0,
                'is_winner'   => 0,
                'deposit'     => $freeze,
                'create_time' => $now,
            ]);
            Db::name('goods')->where('id', $goodsId)->update([
                'bid_count'   => Db::raw('bid_count + 1'),
                'update_time' => $now,
            ]);
            // 延时拍卖：结束前 N 秒出价自动延长（与前台一致）
            $delay = $goods['delay_seconds'] > 0 ? (int)$goods['delay_seconds'] : (int)get_setting('auction_delay', 0);
            if ($delay > 0 && $goods['end_time'] - $now <= $delay) {
                Db::name('goods')->where('id', $goodsId)->update(['end_time' => $now + $delay]);
            }

            // 原最高出价者出局通知（与前台出价一致）
            if ($topBid && (int)$topBid['user_id'] !== $userId) {
                Db::name('sys_message')->insert([
                    'user_id'     => $topBid['user_id'],
                    'admin_id'    => 0,
                    'title'       => '竞拍出局通知',
                    'content'     => '您出价竞拍的「' . $goods['title'] . '」已出局：您的出价 ¥' . number_format((float)$topBid['price'], 2) . ' 于 ' . date('Y-m-d H:i:s', $now) . ' 被 ¥' . number_format($price, 2) . ' 超过，当前最高价 ¥' . number_format($price, 2) . '。如需继续竞拍，请再次出价。',
                    'is_read'     => 0,
                    'create_time' => $now,
                ]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }

        admin_log('手动添加出价：商品「' . $goods['title'] . '」 买家ID ' . $userId . ' 出价 ' . number_format($price, 2) . ' 元');
        return json(['code' => 1, 'msg' => '已添加出价记录']);
    }    /**
     * 添加出价：搜索拍卖中的拍品（按拍品 ID 精确或标题模糊）
     */
    public function searchGoods()
    {
        $kw = trim((string)$this->request->param('kw', ''));
        $now = time();
        $query = Db::name('goods')->where('status', 1)->where('end_time', '>', $now);
        // 自动出价场景：平台自营自动出价开启时，「自营店铺」卖家的拍品由脚本出价，不列出
        if ($this->request->param('scene') === 'auto_bid') {
            $pab = platform_auto_bid_config();
            if ($pab['enabled'] && $pab['seller_ids']) {
                $query->whereNotIn('seller_id', $pab['seller_ids']);
            }
        }
        if ($kw !== '') {
            if (ctype_digit($kw)) {
                // 纯数字：优先按拍品 ID 匹配，同时兼容标题里含该数字
                $query->where(function ($q) use ($kw) {
                    $q->where('id', (int)$kw)->whereOr('title', 'like', "%{$kw}%");
                });
            } else {
                $query->where('title', 'like', "%{$kw}%");
            }
        }
        $list = $query->field('id,title,cover,start_price,raise_price,end_time,bid_count')->order('id', 'desc')->limit(20)->select()->toArray();
        // 当前最高价：一条 GROUP BY 批量取
        $ids = array_column($list, 'id');
        $tops = [];
        if ($ids) {
            $rows = Db::name('bid_record')->whereIn('goods_id', $ids)->where('status', 0)->field('goods_id, MAX(price) AS top')->group('goods_id')->select()->toArray();
            foreach ($rows as $r) {
                $tops[(int)$r['goods_id']] = (float)$r['top'];
            }
        }
        foreach ($list as &$g) {
            $g['top_price'] = max($tops[(int)$g['id']] ?? 0, (float)$g['start_price']);
            $g['has_bid']     = isset($tops[(int)$g['id']]) ? 1 : 0;
            $g['raise_price'] = (float)$g['raise_price'] > 0 ? (float)$g['raise_price'] : 1;
            $g['end_text'] = date('m-d H:i', (int)$g['end_time']);
        }
        unset($g);
        return json(['code' => 1, 'data' => $list]);
    }

    /**
     * 添加出价：搜索买家（手机号 / 昵称模糊，或会员 ID 精确）
     */
    public function searchUser()
    {
        $kw = trim((string)$this->request->param('kw', ''));
        $query = Db::name('user')->where('status', 1);
        if ($kw !== '') {
            $query->where(function ($q) use ($kw) {
                $q->where('account', 'like', "%{$kw}%")->whereOr('nickname', 'like', "%{$kw}%");
                if (ctype_digit($kw)) {
                    $q->whereOr('id', (int)$kw);
                }
            });
        }
        $list = $query->field('id,account as mobile,nickname,is_virtual,balance')->order('id', 'desc')->limit(20)->select()->toArray();
        return json(['code' => 1, 'data' => $list]);
    }
    /**
     * 删除出价记录
     * - 已成交 / 得标的出价不能删除
     * - 竞拍中的出价若冻结了保证金：该买家在同一商品上还有其它有效出价时，保证金转到最早的那条；否则退回可用余额并写流水
     * - 删除后出价次数按剩余记录重新计数，前台当前价按剩余最高出价自动回落
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id  = (int)$this->request->post('id');
        $bid = Db::name('bid_record')->find($id);
        if (!$bid) {
            return json(['code' => 0, 'msg' => '出价记录不存在']);
        }
        $goods = Db::name('goods')->find($bid['goods_id']);
        if ((int)$bid['is_winner'] === 1 || (int)$bid['status'] === 1) {
            return json(['code' => 0, 'msg' => '已成交（得标）的出价不能删除']);
        }
        $now = time();
        $refunded = 0;
        $moved = false;
        Db::startTrans();
        try {
            $bid = Db::name('bid_record')->where('id', $id)->lock(true)->find();
            if (!$bid) {
                throw new \Exception('出价记录不存在');
            }
            if ((int)$bid['status'] === 0 && (float)$bid['deposit'] > 0) {
                $other = Db::name('bid_record')->where('goods_id', $bid['goods_id'])->where('user_id', $bid['user_id'])
                    ->where('status', 0)->where('id', '<>', $id)->order('id', 'asc')->find();
                if ($other) {
                    // 同一买家还有其它有效出价：保证金随之转移，不退回
                    Db::name('bid_record')->where('id', $other['id'])->update(['deposit' => $bid['deposit']]);
                    $moved = true;
                } else {
                    $user = Db::name('user')->where('id', $bid['user_id'])->lock(true)->find();
                    if ($user) {
                        $newBalance = round($user['balance'] + $bid['deposit'], 2);
                        $newFreeze  = round(max($user['freeze_balance'] - $bid['deposit'], 0), 2);
                        Db::name('user')->where('id', $user['id'])->update(['balance' => $newBalance, 'freeze_balance' => $newFreeze, 'update_time' => $now]);
                        Db::name('balance_log')->insert([
                            'user_id'     => $user['id'],
                            'type'        => 'refund',
                            'amount'      => $bid['deposit'],
                            'balance'     => $newBalance,
                            'remark'      => '出价记录删除，保证金退回（' . ($goods['title'] ?? '') . '）',
                            'create_time' => $now,
                        ]);
                        $refunded = (float)$bid['deposit'];
                    }
                }
            }
            Db::name('bid_record')->where('id', $id)->delete();
            if ($goods) {
                $cnt = Db::name('bid_record')->where('goods_id', $goods['id'])->count();
                Db::name('goods')->where('id', $goods['id'])->update(['bid_count' => $cnt, 'update_time' => $now]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
        admin_log('删除出价记录 #' . $id . '：商品「' . ($goods['title'] ?? $bid['goods_id']) . '」 买家ID ' . $bid['user_id'] . ' 出价 ' . number_format((float)$bid['price'], 2) . ' 元' . ($refunded > 0 ? '，退回保证金 ' . number_format($refunded, 2) . ' 元' : ''));
        $msg = '出价记录已删除' . ($refunded > 0 ? '，已退回保证金 ' . number_format($refunded, 2) . ' 元' : ($moved ? '，保证金已转到该买家的其它出价' : ''));
        return json(['code' => 1, 'msg' => $msg]);
    }
}

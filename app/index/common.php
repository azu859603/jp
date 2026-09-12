<?php
// index应用公共函数
use think\facade\Db;
use think\facade\Lang;

// site_settings() / get_setting() 已移至全局 app/common.php：后台、代理端的模板也会用到

/**
 * 昵称展示层翻译
 * 注册时自动生成的昵称形如「用户1234」，已入库不便改动；
 * 这里只在展示时翻译前缀，保留手机尾号。用户自定义的昵称原样返回。
 */
function translate_nickname($nickname)
{
    $nickname = (string)$nickname;
    if ($nickname === '') {
        return $nickname;
    }
    if (preg_match('/^用户(\d+)$/u', $nickname, $m)) {
        return lang('用户') . $m[1];
    }
    return $nickname;
}

/**
 * 首页「关于我们」内容（后台「基础设置」可编辑，支持三语）
 * 取值优先级：当前语言版本 → 简体版本；内容含 HTML 标签时经 clean_html() 净化后原样输出，
 * 纯文本则按换行分段。返回 ['text'=>原文, 'html'=>可 |raw 输出的 HTML, 'image'=>背景图]。
 */
/**
 * 「关于我们」业务部门（按当前语言取 about_dept / _tw / _en，缺省回退简体）
 *
 * 文本格式：每个部门一段，段间空一行；段内第一行为部门名称，其余每行「岗位：姓名 姓名」。
 * 返回 [['title'=>部门, 'roles'=>[['label'=>岗位, 'names'=>姓名文本], ...]], ...]
 */
/**
 * 前台发消息频率限制：同一会员 3 秒内只能发一条（买卖家聊天、在线客服共用）
 * 通过后立即占位，返回 true；3 秒内再次调用返回 false
 */
function message_rate_ok($userId, $seconds = 3)
{
    $key = 'msg_rate_' . (int)$userId;
    if (\think\facade\Cache::has($key)) {
        return false;
    }
    \think\facade\Cache::set($key, 1, $seconds);
    return true;
}

/**
 * 批量取一组商品的当前最高有效出价（status=0），返回 [goods_id => price]
 * 列表页用一条 GROUP BY 代替逐条 MAX 查询（走 bid_record(goods_id,status,price) 覆盖索引）
 */
function bid_top_prices(array $goodsIds)
{
    $goodsIds = array_values(array_unique(array_map('intval', $goodsIds)));
    if (empty($goodsIds)) {
        return [];
    }
    $rows = Db::name('bid_record')->field('goods_id, MAX(price) AS top')
        ->whereIn('goods_id', $goodsIds)->where('status', 0)->group('goods_id')->select()->toArray();
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['goods_id']] = (float)$r['top'];
    }
    return $map;
}

function about_dept_content()
{
    $set  = Lang::getLangSet();
    $key  = $set === 'zh-tw' ? 'about_dept_tw' : ($set === 'en-us' ? 'about_dept_en' : '');
    $text = $key !== '' ? (string)get_setting($key, '') : '';
    if (trim($text) === '') {
        $text = (string)get_setting('about_dept', '');
    }
    // 归一化：换行、全角空格 / 不换行空格 / 制表符都视为普通空格
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = str_replace(["\xE3\x80\x80", "\xC2\xA0", "\t"], ' ', $text);
    $text = trim($text);
    if ($text === '') {
        return [];
    }
    $depts = [];
    foreach (preg_split('/\n\s*\n+/', $text) as $block) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), 'strlen'));
        if (empty($lines)) {
            continue;
        }
        $title = preg_replace('/\s+/u', ' ', array_shift($lines));
        $roles = [];
        foreach ($lines as $line) {
            // 从站点复制时标题常常重复一行，跳过
            if ($line === $title) {
                continue;
            }
            $parts = preg_split('/[：:]/u', $line, 2);
            if (count($parts) === 2) {
                // 岗位名内部的补位空格去掉（如「海 外 拓 展」）
                $label = preg_replace('/\s+/u', '', $parts[0]);
                $names = about_dept_names($parts[1]);
                if ($label === '' && $names === '') {
                    continue;
                }
                $roles[] = ['label' => $label, 'names' => $names];
                continue;
            }
            // 没有冒号：是上一岗位的续行（姓名太多换行了），并入上一岗位
            $names = about_dept_names($line);
            if ($names === '') {
                continue;
            }
            if (!empty($roles)) {
                $last = count($roles) - 1;
                $roles[$last]['names'] = trim($roles[$last]['names'] . ' ' . $names);
            } else {
                $roles[] = ['label' => '', 'names' => $names];
            }
        }
        $depts[] = ['title' => $title, 'roles' => $roles];
    }
    return $depts;
}

/**
 * 整理一串姓名：按空格拆开；被补位空格拆散的两字名（如「王 健 任 星」）按相邻单字两两合并为「王健 任星」
 */
function about_dept_names(string $raw): string
{
    $tokens = preg_split('/\s+/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        // 下一个词是单个汉字，或「单个汉字 + 括号备注」（如「麻 正（紫砂茶具）」）时合并
        if (mb_strlen($t) === 1 && preg_match('/^\p{Han}$/u', $t)
            && isset($tokens[$i + 1]) && preg_match('/^\p{Han}(?:[（(][^）)]*[）)])?$/u', $tokens[$i + 1])) {
            $out[] = $t . $tokens[$i + 1];
            $i++;
        } else {
            $out[] = $t;
        }
    }
    return implode(' ', $out);
}
function about_us_content()
{
    $set  = Lang::getLangSet();
    $key  = $set === 'zh-tw' ? 'about_us_tw' : ($set === 'en-us' ? 'about_us_en' : '');
    $text = $key !== '' ? (string)get_setting($key, '') : '';
    if (trim($text) === '') {
        $text = (string)get_setting('about_us', '');
    }
    $text = trim($text);
    if ($text === '') {
        $html = '';
    } elseif (preg_match('/<\w+[^>]*>/', $text)) {
        $html = clean_html($text);
    } else {
        $paras = preg_split('/\r\n|\r|\n/', $text);
        $html  = '';
        foreach ($paras as $p) {
            $p = trim($p);
            if ($p !== '') {
                $html .= '<p>' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
    }
    return [
        'text'  => $text,
        'html'  => $html,
        'image' => (string)get_setting('about_us_image', ''),
    ];
}

/**
 * 商品展示编号
 * 规则：商品ID + 10000（纯展示，不落库）
 * 若日后改为独立字段，只需改这里
 */
function goods_no($goodsId)
{
    return (int)$goodsId + 10000;
}

/**
 * 计算商品佣金比例
 */
function goods_commission_rate($goods = null)
{
    // 佣金比例统一由后台「基础设置」配置；0 表示不收佣金，卖家实收 = 成交价
    return max(0, (float)get_setting('commission_rate', 0));
}

/**
 * 竞拍结束结算（成交/流拍/保证金处理）
 * @param int $goodsId
 */
function settle_goods($goodsId)
{
    $goods = Db::name('goods')->where('id', $goodsId)->lock(true)->find();
    if (!$goods || $goods['status'] != 1 || $goods['end_time'] > time()) {
        return false;
    }

    $bids = Db::name('bid_record')
        ->where('goods_id', $goodsId)
        ->where('status', 0)
        ->order('price', 'desc')
        ->order('id', 'asc')
        ->select()
        ->toArray();

    Db::startTrans();
    try {
        // 无出价 或 最高价低于保留价 → 流拍
        $top = $bids[0] ?? null;
        $fail = !$top || ($goods['reserve_price'] > 0 && $top['price'] < $goods['reserve_price']);

        if ($fail) {
            foreach ($bids as $b) {
                Db::name('bid_record')->where('id', $b['id'])->update(['status' => 2]);
                if ($b['deposit'] > 0) {
                    refund_deposit($b['user_id'], $b['deposit'], '拍卖流拍，保证金退回（' . $goods['title'] . '）');
                }
            }
            Db::name('goods')->where('id', $goodsId)->update(['status' => 3, 'update_time' => time()]);
            Db::commit();
            return '流拍';
        }

        // 成交
        $rate = goods_commission_rate($goods);
        $commission = round($top['price'] * $rate / 100, 2);
        $sellerIncome = round($top['price'] - $commission, 2);

        // 出价记录：其余流拍，得标者成交
        Db::name('bid_record')->where('goods_id', $goodsId)->where('status', 0)->update(['status' => 2]);
        Db::name('bid_record')->where('id', $top['id'])->update(['status' => 1, 'is_winner' => 1]);

        // 未得标者保证金退回
        foreach ($bids as $b) {
            if ($b['id'] != $top['id'] && $b['deposit'] > 0) {
                refund_deposit($b['user_id'], $b['deposit'], '未拍中，保证金退回（' . $goods['title'] . '）');
            }
        }

        // 得标者实际冻结的保证金（多次出价只有首条带 deposit，取最大值）
        $topDeposit = (float)Db::name('bid_record')
            ->where('goods_id', $goodsId)
            ->where('user_id', $top['user_id'])
            ->where('deposit', '>', 0)
            ->max('deposit');

        // 生成订单（待付款）
        $orderId = Db::name('order')->insertGetId([
            'order_no'        => make_order_no('AU'),
            'goods_id'        => $goodsId,
            'goods_title'     => $goods['title'],
            'goods_cover'     => $goods['cover'],
            'seller_id'       => $goods['seller_id'],
            'buyer_id'        => $top['user_id'],
            'price'           => $top['price'],
            'commission_rate' => $rate,
            'commission'      => $commission,
            'seller_income'   => $sellerIncome,
            'deposit'         => $topDeposit, // 用得标者实际冻结的保证金，而非商品当前配置值（拍卖期间修改保证金不会造成不一致）
            'pay_status'      => 0,
            'order_status'    => 0,
            'create_time'     => time(),
            'update_time'     => time(),
        ]);

        Db::name('goods')->where('id', $goodsId)->update([
            'status'      => 2,
            'final_price' => $top['price'],
            'winner_id'   => $top['user_id'],
            'order_id'    => $orderId,
            'update_time' => time(),
        ]);

        Db::commit();
        return '成交';
    } catch (\Throwable $e) {
        Db::rollback();
        return false;
    }
}

/**
 * 取消待付款订单（超时自动取消 / 买家主动取消 共用）
 *
 * 动作：订单置为已取消并记录原因 → 按 $mode 处理冻结的保证金 → 商品回到流拍(3)
 * 供卖家「重新上架」→ 中标出价记录降为流拍 → 给买卖双方发站内信。全程事务。
 *
 * @param int    $orderId
 * @param string $reason  取消原因（写入 order.remark 与站内信）
 * @param string $mode    保证金处理：forfeit_platform 没收归平台 | to_seller 赔付卖家 | refund_buyer 退还买家
 * @return string|false   成功返回保证金处理结果描述；订单不存在或状态不符返回 false
 */
function cancel_unpaid_order($orderId, $reason, $mode = 'forfeit_platform')
{
    if (!in_array($mode, ['forfeit_platform', 'to_seller', 'refund_buyer'], true)) {
        $mode = 'forfeit_platform';
    }
    $order = Db::name('order')->where('id', (int)$orderId)->lock(true)->find();
    if (!$order || (int)$order['pay_status'] !== 0 || (int)$order['order_status'] !== 0) {
        return false;
    }

    Db::startTrans();
    try {
        $now     = time();
        $deposit = round((float)$order['deposit'], 2);
        $title   = (string)$order['goods_title'];
        $note    = '无保证金';

        if ($deposit > 0) {
            $buyer = Db::name('user')->where('id', $order['buyer_id'])->lock(true)->find();
            if ($buyer) {
                $newFreeze = round(max($buyer['freeze_balance'] - $deposit, 0), 2);
                if ($mode === 'refund_buyer') {
                    $newBalance = round($buyer['balance'] + $deposit, 2);
                    Db::name('user')->where('id', $buyer['id'])->update([
                        'balance' => $newBalance, 'freeze_balance' => $newFreeze, 'update_time' => $now,
                    ]);
                    Db::name('balance_log')->insert([
                        'user_id' => $buyer['id'], 'type' => 'refund', 'amount' => $deposit,
                        'balance' => $newBalance, 'remark' => '订单取消，保证金退回（' . $title . '）', 'create_time' => $now,
                    ]);
                    $note = '保证金已退还买家';
                } else {
                    // 没收：冻结额扣除，可用余额不变（出价时已扣），记一笔资产减少
                    Db::name('user')->where('id', $buyer['id'])->update([
                        'freeze_balance' => $newFreeze, 'update_time' => $now,
                    ]);
                    Db::name('balance_log')->insert([
                        'user_id' => $buyer['id'], 'type' => 'forfeit', 'amount' => -$deposit,
                        'balance' => $buyer['balance'], 'remark' => '订单超时未付款，保证金没收（' . $title . '）', 'create_time' => $now,
                    ]);
                    $note = '保证金已没收';
                    if ($mode === 'to_seller') {
                        $seller = Db::name('user')->where('id', $order['seller_id'])->lock(true)->find();
                        if ($seller) {
                            $sellerBalance = round($seller['balance'] + $deposit, 2);
                            Db::name('user')->where('id', $seller['id'])->update([
                                'balance' => $sellerBalance, 'update_time' => $now,
                            ]);
                            Db::name('balance_log')->insert([
                                'user_id' => $seller['id'], 'type' => 'income', 'amount' => $deposit,
                                'balance' => $sellerBalance, 'remark' => '买家超时未付款，保证金赔付（' . $title . '）', 'create_time' => $now,
                            ]);
                            $note = '保证金已赔付卖家';
                        }
                    }
                }
            }
        }

        Db::name('order')->where('id', $order['id'])->update([
            'order_status' => 4, 'remark' => $reason, 'update_time' => $now,
        ]);
        // 商品回到流拍：卖家可在「我的商品」重新上架（该流程会清理旧出价）
        Db::name('goods')->where('id', $order['goods_id'])->update([
            'status' => 3, 'winner_id' => 0, 'order_id' => 0, 'final_price' => 0, 'update_time' => $now,
        ]);
        Db::name('bid_record')->where('goods_id', $order['goods_id'])->where('is_winner', 1)->update([
            'status' => 2, 'is_winner' => 0,
        ]);

        Db::name('sys_message')->insertAll([
            ['user_id' => $order['buyer_id'], 'admin_id' => 0, 'title' => '订单取消通知',
             'content' => '您的订单 ' . $order['order_no'] . '（' . $title . '）已取消：' . $reason . '。' . $note . '。',
             'is_read' => 0, 'create_time' => $now],
            ['user_id' => $order['seller_id'], 'admin_id' => 0, 'title' => '买家未付款通知',
             'content' => '商品「' . $title . '」的买家未付款，订单 ' . $order['order_no'] . ' 已取消，商品已回到可重新上架状态。' . $note . '。',
             'is_read' => 0, 'create_time' => $now],
        ]);

        Db::commit();
        return $note;
    } catch (\Throwable $e) {
        Db::rollback();
        return false;
    }
}

/**
 * 完成待收货订单（自动确认收货用）
 * 卖家收入在付款时已结算，此处不涉及资金；完成后买家方可申请售后。
 *
 * @param int    $orderId
 * @param string $reason  完成原因（追加到 order.remark，并写入买家站内信）
 * @return bool
 */
function complete_order($orderId, $reason)
{
    $order = Db::name('order')->where('id', (int)$orderId)->lock(true)->find();
    if (!$order || (int)$order['order_status'] !== 2) {
        return false;
    }
    Db::startTrans();
    try {
        $now    = time();
        $remark = trim((string)$order['remark']);
        $remark = $remark === '' ? $reason : $remark . '；' . $reason;
        Db::name('order')->where('id', $order['id'])->update([
            'order_status' => 3, 'finish_time' => $now, 'remark' => $remark, 'update_time' => $now,
        ]);
        Db::name('sys_message')->insert([
            'user_id' => $order['buyer_id'], 'admin_id' => 0, 'title' => '自动确认收货通知',
            'content' => '您的订单 ' . $order['order_no'] . '（' . $order['goods_title'] . '）' . $reason . '，交易已完成。如商品有问题，可在订单中申请售后。',
            'is_read' => 0, 'create_time' => $now,
        ]);
        Db::commit();
        return true;
    } catch (\Throwable $e) {
        Db::rollback();
        return false;
    }
}

/**
 * 催发货提醒：给卖家发站内信
 * 同一订单 24 小时内只提醒一次，避免手动重复执行时刷屏。
 *
 * @param int $orderId
 * @return string|false  'sent' 已发送 | 'skip' 24小时内已提醒 | false 订单不存在或不是待发货
 */
function remind_unshipped_order($orderId)
{
    $order = Db::name('order')->where('id', (int)$orderId)->find();
    if (!$order || (int)$order['order_status'] !== 1) {
        return false;
    }
    $now = time();
    $dup = Db::name('sys_message')
        ->where('user_id', $order['seller_id'])
        ->where('title', '发货提醒')
        ->where('content', 'like', '%' . $order['order_no'] . '%')
        ->where('create_time', '>', $now - 86400)
        ->count();
    if ($dup > 0) {
        return 'skip';
    }
    $waited = max(1, (int)floor(($now - (int)$order['pay_time']) / 86400));

    // 卖家信誉分：每次催发货扣 1 分（同一订单 24 小时内只提醒一次，即每天最多扣 1 分），最低 0 分
    $credit = Db::name('user')->where('id', $order['seller_id'])->value('credit_score');
    $creditText = '';
    if ($credit !== null) {
        $newCredit = max(0, (int)$credit - 1);
        if ($newCredit !== (int)$credit) {
            Db::name('user')->where('id', $order['seller_id'])->update(['credit_score' => $newCredit, 'update_time' => $now]);
        }
        $creditText = '因未按时发货，您的信誉分已扣 1 分，当前 ' . $newCredit . ' 分。';
    }

    Db::name('sys_message')->insert([
        'user_id' => $order['seller_id'], 'admin_id' => 0, 'title' => '发货提醒',
        'content' => '订单 ' . $order['order_no'] . '（' . $order['goods_title'] . '）买家已于 '
                   . date('m-d H:i', (int)$order['pay_time']) . ' 付款，至今 ' . $waited . ' 天未发货，请尽快处理。' . $creditText,
        'is_read' => 0, 'create_time' => $now,
    ]);
    return 'sent';
}

/**
 * 扫描并结算所有到期商品
 */
function settle_expired_goods()
{
    $now = time();
    $list = Db::name('goods')->where('status', 1)->where('end_time', '<=', $now)->column('id');
    // 返回 [商品ID => '成交'|'流拍'|false]，供定时任务输出统计；原有调用方忽略返回值即可
    $results = [];
    foreach ($list as $id) {
        $results[$id] = settle_goods($id);
    }
    return $results;
}

/**
 * 退回保证金
 */
function refund_deposit($userId, $amount, $remark)
{
    if ($amount <= 0) {
        return;
    }
    $user = Db::name('user')->where('id', $userId)->lock(true)->find();
    if (!$user) {
        return;
    }
    $newBalance = round($user['balance'] + $amount, 2);
    $newFreeze = round(max($user['freeze_balance'] - $amount, 0), 2);
    Db::name('user')->where('id', $userId)->update([
        'balance'        => $newBalance,
        'freeze_balance' => $newFreeze,
        'update_time'    => time(),
    ]);
    Db::name('balance_log')->insert([
        'user_id'     => $userId,
        'type'        => 'refund',
        'amount'      => $amount,
        'balance'     => $newBalance,
        'remark'      => $remark,
        'create_time' => time(),
    ]);
}

/**
 * 余额流水备注翻译（展示层：翻译中文前缀，保留动态内容）
 * 示例："提现申请冻结：100.00元" → "Withdrawal freeze: 100.00元"
 */
function translate_remark($remark)
{
    if ($remark === '' || $remark === null) {
        return $remark;
    }
    // 前缀按长度降序匹配（含标点的完整前缀优先）
    $prefixes = [
        '买家超时未付款，保证金赔付（',
        '订单超时未付款，保证金没收（',
        '订单取消，保证金退回（',
        '拍卖流拍，保证金退回（',
        '未拍中，保证金退回（',
        '拍卖成交收入：',
        '拍卖订单支付：',
        '拍卖保证金（',
        '提现申请冻结',
        '提现拒绝退回',
        '订单取消退款',
        '售后退款',
        '售后扣回成交收入',
        '拍卖订单支付',
        '拍卖成交收入',
        '充值到账',
        '后台添加会员赠送余额',
        '代理添加会员赠送余额',
        '后台调整',
        '代理调整',
        '余额充值',
    ];
    foreach ($prefixes as $zh) {
        if (mb_strpos($remark, $zh) === 0) {
            $translated = lang($zh) . mb_substr($remark, mb_strlen($zh));
            // 全角标点转半角，英文环境金额单位“元”转 “yuan”
            $translated = str_replace(['：', '（', '）'], [': ', '(', ')'], $translated);
            $translated = str_replace('平台佣金', lang('平台佣金'), $translated);
            if (Lang::getLangSet() === 'en-us') {
                $translated = str_replace('元', 'yuan', $translated);
            }
            return $translated;
        }
    }
    return $remark;
}

/**
 * 站内信展示层翻译（标题翻译 + 竞拍出局通知内容分段翻译）
 */
function translate_sys_message(&$msg)
{
    if (empty($msg['title'])) {
        return;
    }
    $msg['title'] = lang($msg['title']);
    if (empty($msg['content'])) {
        return;
    }
    // 竞拍出局通知内容模板分段替换（键按长度降序，避免误伤动态内容）
    $segs = [
        '您出价竞拍的「',
        '」已出局：您的出价 ',
        '。如需继续竞拍，请再次出价。',
        ' 超过，当前最高价 ',
        ' 于 ',
        ' 被 ',
    ];
    $content = $msg['content'];
    foreach ($segs as $zh) {
        if (mb_strpos($content, $zh) !== false) {
            $content = str_replace($zh, lang($zh), $content);
        }
    }
    $msg['content'] = $content;
}

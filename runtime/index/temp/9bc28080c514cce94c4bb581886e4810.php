<?php /*a:2:{s:47:"D:\work\08\17_3\app\index\view\user\wallet.html";i:1787185774;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title><?php echo htmlentities((string) (isset($page_title) && ($page_title !== '')?$page_title:$site_name)); ?> - <?php echo htmlentities((string) $site_name); ?></title>
<link rel="stylesheet" href="/static/m.css?v=20260817c">
</head>
<body>
<div class="app">

<?php if(empty($hide_header)): ?>
<div class="hd">
    <a class="back" href="javascript:history.back(-1);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="title"><?php echo htmlentities((string) (isset($page_title) && ($page_title !== '')?$page_title:'')); ?></span>
</div>
<?php endif; ?>


<div class="page <?php if(!empty($hide_header)): ?>no-hd<?php endif; if(!empty($hide_tabbar)): ?>no-tabbar<?php endif; if(!empty($page_class)): ?><?php echo htmlentities((string) $page_class); ?><?php endif; ?>">
    
<div class="wallet-top">
    <div class="w-label">资产总额(元)</div>
    <div class="w-amount"><?php echo htmlentities((string) number_format($user['balance'],2)); ?></div>
    <div class="w-ops">
        <?php if(isset($is_virtual) && $is_virtual): ?>
        <span class="w-tip" style="font-size:12px;color:#999;line-height:44px;padding:0 12px;">虚拟会员：余额为系统永存金额，不支持充值/提现</span>
        <?php else: ?>
        <a class="w-op" href="/user/withdraw">提现</a>
        <a class="w-op" href="/user/recharge">充值</a>
        <?php endif; ?>
    </div>
</div>
<!--<div class="wallet-cards">-->
<!--    <div class="wcard"><div class="num"><?php echo htmlentities((string) (isset($data['reward_pending']) && ($data['reward_pending'] !== '')?$data['reward_pending']:0)); ?></div><div class="lbl">待结算奖励</div></div>-->
<!--    <div class="wcard"><div class="num red"><?php echo htmlentities((string) (isset($data['reward_settled']) && ($data['reward_settled'] !== '')?$data['reward_settled']:0)); ?></div><div class="lbl">已结算奖励</div></div>-->
<!--    <div class="wcard"><div class="num"><?php echo htmlentities((string) (isset($data['order_pending']) && ($data['order_pending'] !== '')?$data['order_pending']:0)); ?></div><div class="lbl">待结算订单</div></div>-->
<!--    <div class="wcard"><div class="num red"><?php echo htmlentities((string) (isset($data['order_settled']) && ($data['order_settled'] !== '')?$data['order_settled']:0)); ?></div><div class="lbl">已结算订单</div></div>-->
<!--</div>-->
<div class="wallet-log">
    <div class="w-head">
        <span class="t">余额明细</span>
        <a class="more" href="/user/balance_log">更多</a>
    </div>
    <?php foreach($logs as $l): ?>
    <div class="log-item">
        <div class="l-ico">💰</div>
        <div class="l-info">
            <div class="l-name"><?php echo htmlentities((string) $l['remark']); ?></div>
            <div class="l-time"><?php echo htmlentities((string) date('Y-m-d H:i',!is_numeric($l['create_time'])? strtotime($l['create_time']) : $l['create_time'])); ?></div>
        </div>
        <div style="text-align:right;">
            <div class="l-amount <?php if($l['amount']>0): ?>in<?php else: ?>out<?php endif; ?>"><?php if($l['amount']>0): ?>+<?php endif; ?><?php echo htmlentities((string) number_format($l['amount'],2)); ?></div>
            <div class="l-balance">余额 <?php echo htmlentities((string) number_format($l['balance'],2)); ?></div>
        </div>
    </div>
    <?php endforeach; if(empty($logs)): ?>
    <div class="empty" style="padding:30px 0;">暂无记录</div>
    <?php endif; ?>
</div>

</div>


<?php if(empty($hide_tabbar)): ?>
<div class="tabbar">
    <a href="/" <?php if($tab_active=='index'): ?>class="on"<?php endif; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
        <span>首页</span>
    </a>
    <a href="/category" <?php if($tab_active=='category'): ?>class="on"<?php endif; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
        <span>分类</span>
    </a>
    <a class="tab-pub" href="/seller/goods_add">
        <span class="pub-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </span>
        <span>发布</span>
    </a>
    <a href="/user/center" <?php if($tab_active=='mine'): ?>class="on"<?php endif; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c1.2-3.6 4-5.5 7.5-5.5s6.3 1.9 7.5 5.5"/></svg>
        <span>我的</span>
    </a>
</div>
<?php endif; ?>

</div>
<script src="/static/m.js"></script>

<script>
document.querySelector('.page').classList.add('no-bg');
</script>

</body>
</html>

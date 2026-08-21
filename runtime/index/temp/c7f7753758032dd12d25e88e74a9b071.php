<?php /*a:2:{s:49:"D:\work\08\17_3\app\index\view\user\recharge.html";i:1787131841;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title><?php echo htmlentities((string) (isset($page_title) && ($page_title !== '')?$page_title:$site_name)); ?> - <?php echo htmlentities((string) $site_name); ?></title>
<link rel="stylesheet" href="/static/m.css">
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
    <div class="w-label">当前余额(元)</div>
    <div class="w-amount" style="font-size:28px;"><?php echo htmlentities((string) number_format($user['balance'],2)); ?></div>
</div>
<div class="form-card" style="margin-top:-50px;position:relative;z-index:2;">
    <div class="form-row">
        <span class="fl">充值金额</span>
        <div class="fr">
            <input type="number" id="amount" placeholder="请输入充值金额" <?php if($has_pending): ?>disabled style="background:#f5f5f5;color:#999;"<?php endif; ?>>
        </div>
    </div>
    <div class="form-row">
        <span class="fl">充值方式</span>
        <div class="fr" style="color:#666;">在线充值（人工审核到账）</div>
    </div>
</div>
<?php if($has_pending): ?>
<div class="form-tip" style="color:#e6a23c;padding:0 16px;">您有一笔充值申请正在审核中，审核完成后可再次提交</div>
<?php else: ?>
<div class="form-tip">提交充值申请后，请联系客服完成转账，平台审核通过后金额自动到账</div>
<?php endif; ?>
<div class="form-submit">
    <button class="btn btn-red" id="submitBtn" onclick="doRecharge()" <?php if($has_pending): ?>disabled style="background:#ccc;"<?php endif; ?>><?php if($has_pending): ?>审核中，暂不可提交<?php else: ?>提交充值申请<?php endif; ?></button>
</div>

<?php if(!empty($records)): ?>
<div class="wallet-log">
    <div class="w-head">
        <span class="t">充值记录</span>
        <a class="more" href="/user/recharge_log">更多</a>
    </div>
    <?php foreach($records as $r): ?>
    <div class="log-item">
        <div class="l-ico">💳</div>
        <div class="l-info">
            <div class="l-name">充值申请 #<?php if($r['id']): ?><?php echo htmlentities((string) $r['id']); ?><?php endif; ?></div>
            <div class="l-time"><?php echo htmlentities((string) date('Y-m-d H:i',!is_numeric($r['create_time'])? strtotime($r['create_time']) : $r['create_time'])); ?></div>
            <?php if($r['status']==2 && !empty($r['refuse_reason'])): ?>
            <div class="l-reason">拒绝理由：<?php echo htmlentities((string) $r['refuse_reason']); ?></div>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <div class="l-amount in">+¥<?php echo htmlentities((string) number_format($r['amount'],2)); ?></div>
            <div class="l-status s<?php echo htmlentities((string) $r['status']); ?>"><?php if($r['status']==0): ?>审核中<?php elseif($r['status']==1): ?>已到账<?php else: ?>未通过<?php endif; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<style>
.l-status{font-size:11px;margin-top:2px;text-align:right;}
.l-status.s0{color:#e6a23c;}
.l-status.s1{color:#52c41a;}
.l-status.s2{color:#E4393C;}
.l-reason{font-size:11px;color:#E4393C;margin-top:3px;line-height:1.5;}
</style>

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
function doRecharge() {
    var amount = document.getElementById('amount').value.trim();
    if (!amount || parseFloat(amount) <= 0) { toast('请输入充值金额'); return; }
    ajaxPost('/user/recharge', {amount: amount, pay_type: 1}, function(res){
        toast(res.msg);
        if (res.code === 1) {
            setTimeout(function(){ location.reload(); }, 800);
        }
    });
}
</script>

</body>
</html>

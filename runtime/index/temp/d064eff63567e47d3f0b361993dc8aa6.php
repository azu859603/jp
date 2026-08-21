<?php /*a:2:{s:49:"D:\work\08\17_3\app\index\view\user\withdraw.html";i:1787183215;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="withdraw-page">
    <div class="withdraw-card">
        <div class="w-balance">
            <div class="num"><?php echo htmlentities((string) number_format($user['balance'],2)); ?></div>
            <div class="lbl">可提现金额(元)</div>
        </div>
        <div class="w-form">
            <div class="w-row">
                <span class="lbl">提现金额</span>
                <input type="number" id="amount" placeholder="请输入提现金额">
                <span class="all-btn" onclick="document.getElementById('amount').value='<?php echo htmlentities((string) $user['balance']); ?>'">全部</span>
            </div>
            <div class="w-row">
                <span class="lbl">提现方式</span>
            </div>
        </div>
        <!-- 提现方式选项 -->
        <div class="w-types">
            <div class="w-type" data-type="wechat" onclick="selectPay('wechat')">
                <span class="w-tname">微信</span>
                <span class="w-tstate" id="st_wechat">未绑定</span>
            </div>
            <div class="w-type" data-type="alipay" onclick="selectPay('alipay')">
                <span class="w-tname">支付宝</span>
                <span class="w-tstate" id="st_alipay">未绑定</span>
            </div>
            <div class="w-type" data-type="bank" onclick="selectPay('bank')">
                <span class="w-tname">银行卡</span>
                <span class="w-tstate" id="st_bank">未绑定</span>
            </div>
            <div class="w-type" data-type="usdt" onclick="selectPay('usdt')">
                <span class="w-tname">虚拟货币</span>
                <span class="w-tstate" id="st_usdt">未绑定</span>
            </div>
        </div>
        <!-- 绑定状态提示区 -->
        <div class="w-bindtip" id="bindTip">
            <div class="w-bindinfo" id="bindInfo" style="display:none;"></div>
            <div class="w-unbind" id="unbindBox" style="display:none;">
                <span>该提现方式未绑定，请先绑定</span>
                <span class="w-bindbtn" onclick="goBind()">去绑定</span>
            </div>
        </div>
    </div>
    <div class="withdraw-btn">
        <button class="btn btn-red" id="submitBtn" onclick="doWithdraw()">提交提现申请</button>
    </div>
    <?php if($pending == 1): ?>
    <div style="margin:0 14px 12px;background:#fff7e6;border:1px solid #ffd591;border-radius:8px;padding:10px 12px;font-size:12px;color:#ad6800;line-height:1.6;">
        您有一笔提现正在审核中，审核通过后方可提交下一笔申请。
    </div>
    <?php endif; ?>
    <div class="form-tip" style="color:#888;padding:0 16px;">提现手续费：<?php if($fee_rate>0): ?><?php echo htmlentities((string) $fee_rate); ?>%<?php else: ?>免费<?php endif; ?>，到账时间1-3个工作日<?php if($withdraw_min>0 || $withdraw_max>0): ?>，单笔限额：<?php if($withdraw_min>0): ?>¥<?php echo htmlentities((string) number_format($withdraw_min,2)); else: ?>不限<?php endif; ?> ~ <?php if($withdraw_max>0): ?>¥<?php echo htmlentities((string) number_format($withdraw_max,2)); else: ?>不限<?php endif; ?><?php endif; ?></div>

    <?php if(!empty($records)): ?>
    <div class="w-records">
        <div class="r-head">提现记录</div>
        <?php foreach($records as $r): ?>
        <div class="w-record">
            <div class="r-amount">-¥<?php echo htmlentities((string) number_format($r['amount'],2)); ?></div>
            <div style="text-align:right;">
                <div class="r-status s<?php echo htmlentities((string) $r['status']); ?>"><?php if($r['status']==0): ?>审核中<?php elseif($r['status']==1): ?>已到账<?php else: ?>已拒绝<?php endif; ?></div>
                <div class="r-info"><?php echo htmlentities((string) date('Y-m-d H:i',!is_numeric($r['create_time'])? strtotime($r['create_time']) : $r['create_time'])); ?></div>
                <?php if($r['status']==2 && !empty($r['refuse_reason'])): ?>
                <div class="r-reason">拒绝原因：<?php echo htmlentities((string) $r['refuse_reason']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<style>
.withdraw-page{min-height:calc(100vh - 44px);background:linear-gradient(180deg,#AC3030 0,#AC3030 150px,#f5f5f5 150px);padding-bottom:20px;}
.w-types{display:flex;gap:10px;padding:0 14px 12px;}
.w-type{flex:1;background:#fff;border:1px solid #eee;border-radius:8px;padding:10px 6px;display:flex;flex-direction:column;align-items:center;gap:4px;cursor:pointer;}
.w-type.on{border-color:#AC3030;background:#fff7f7;}
.w-tname{font-size:14px;color:#333;font-weight:600;}
.w-tstate{font-size:11px;color:#999;}
.w-type.on .w-tstate{color:#AC3030;}
.w-bindtip{padding:0 14px 14px;}
.w-bindinfo{background:#fff;border-radius:8px;padding:10px 12px;font-size:13px;color:#333;line-height:1.8;}
.w-unbind{background:#fff;border-radius:8px;padding:12px;display:flex;align-items:center;justify-content:space-between;font-size:13px;color:#999;}
.w-bindbtn{background:#AC3030;color:#fff;padding:6px 16px;border-radius:6px;font-size:13px;}
.w-record .r-reason{font-size:11px;color:#e6a23c;margin-top:4px;max-width:220px;line-height:1.5;text-align:right;}
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
var accounts = <?php echo $pa_json; ?>;
var curPay = 'wechat';
var typeKey = {'wechat':2, 'alipay':1, 'bank':3, 'usdt':4};
var typeName = {'wechat':'微信', 'alipay':'支付宝', 'bank':'银行卡', 'usdt':'虚拟货币'};
var hasPending = <?php echo htmlentities((string) (isset($pending) && ($pending !== '')?$pending:0)); ?>;

(function(){
    // 初始化各方式绑定状态
    var keys = ['wechat','alipay','bank','usdt'];
    for (var i = 0; i < keys.length; i++) {
        var a = accounts[typeKey[keys[i]]];
        if (a) { document.getElementById('st_' + keys[i]).textContent = '已绑定'; }
    }
    selectPay('wechat');
})();

function selectPay(p) {
    curPay = p;
    var els = document.querySelectorAll('.w-type');
    for (var i = 0; i < els.length; i++) { els[i].classList.remove('on'); }
    document.querySelector('.w-type[data-type="' + p + '"]').classList.add('on');
    var a = accounts[typeKey[p]];
    var bindInfo = document.getElementById('bindInfo');
    var unbindBox = document.getElementById('unbindBox');
    var btn = document.getElementById('submitBtn');
    if (a) {
        var txt = '已绑定' + typeName[p] + '：' + (a.real_name ? a.real_name + '（' + maskAccount(p, a) + '）' : maskAccount(p, a));
        if (p === 'bank') { txt += '<br>银行：' + a.bank_name; }
        if (p === 'usdt' && a.bank_name) { txt += '<br>网络：' + a.bank_name; }
        bindInfo.innerHTML = txt;
        bindInfo.style.display = '';
        unbindBox.style.display = 'none';
        btn.textContent = hasPending ? '审核中，暂不可申请' : '提交提现申请';
    } else {
        bindInfo.style.display = 'none';
        unbindBox.style.display = '';
        btn.textContent = '去绑定' + typeName[p];
    }
    btn.disabled = hasPending;
}

function maskAccount(p, a) {
    var acct = a.account || '';
    if (acct.length <= 4) return acct;
    if (p === 'bank') { return acct.substr(0, 4) + ' **** **** ' + acct.substr(-4); }
    if (p === 'usdt') { return acct.substr(0, 6) + '****' + acct.substr(-4); }
    return acct.substr(0, 3) + '****' + acct.substr(-3);
}

function goBind() {
    location.href = '/user/pay_account?type=' + typeKey[curPay];
}

function doWithdraw() {
    var amount = document.getElementById('amount').value.trim();
    var a = accounts[typeKey[curPay]];
    if (hasPending) { toast('您有一笔提现正在审核中，审核通过后方可提交下一笔'); return; }
    if (!a) { goBind(); return; }
    if (!amount || parseFloat(amount) <= 0) { toast('请输入提现金额'); return; }
    ajaxPost('/user/withdraw', {amount: amount, pay_type: curPay}, function(res){
        if (res.code === 1) {
            toast('提现申请已提交');
            setTimeout(function(){ location.reload(); }, 600);
        } else {
            toast(res.msg);
        }
    });
}
</script>

</body>
</html>

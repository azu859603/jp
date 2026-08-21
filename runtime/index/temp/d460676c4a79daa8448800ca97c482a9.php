<?php /*a:2:{s:45:"D:\work\08\17_3\app\index\view\order\pay.html";i:1787042498;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="pay-card">
    <div class="p-head">
        <div class="lbl">需支付</div>
        <div class="num">¥<?php echo htmlentities((string) number_format($order['price'],2)); ?></div>
    </div>
    <div class="p-row"><span>订单号</span><b><?php echo htmlentities((string) $order['order_no']); ?></b></div>
    <div class="p-row"><span>商品</span><b><?php echo htmlentities((string) $order['goods_title']); ?></b></div>
    <div class="p-row"><span>数量</span><b>1</b></div>
</div>

<div class="pay-addr" onclick="openAddr()">
    <svg class="a-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
    <?php if(!empty($address)): ?>
    <div class="a-info" id="addrBox">
        <div class="a-name" id="addrName"><?php echo htmlentities((string) $address['name']); ?> · <?php echo htmlentities((string) $address['mobile']); ?></div>
        <div class="a-detail" id="addrDetail"><?php echo htmlentities((string) $address['province']); ?> <?php echo htmlentities((string) $address['city']); ?> <?php echo htmlentities((string) $address['district']); ?> <?php echo htmlentities((string) $address['address']); ?></div>
    </div>
    <?php else: ?>
    <div class="a-info" id="addrBox">
        <div class="a-name" id="addrName" style="color:#999;">请添加收货地址</div>
        <div class="a-detail" id="addrDetail" style="color:#bbb;">点击添加</div>
    </div>
    <?php endif; ?>
    <span class="arrow">›</span>
</div>
<input type="hidden" id="addressId" value="<?php echo htmlentities((string) (isset($address['id']) && ($address['id'] !== '')?$address['id']:0)); ?>">

<!-- 地址选择弹窗 -->
<div class="mask" id="addrMask" onclick="closeAddr()"></div>
<div class="sheet" id="addrSheet">
    <div class="s-title">选择收货地址 <span class="s-close" onclick="closeAddr()">×</span></div>
    <div class="s-body" style="max-height:50vh;overflow-y:auto;padding:8px 14px;">
        <?php foreach($addresses as $ad): ?>
        <div class="adr-item" data-id="<?php echo htmlentities((string) $ad['id']); ?>" data-name="<?php echo htmlentities((string) $ad['name']); ?>" data-mobile="<?php echo htmlentities((string) $ad['mobile']); ?>" data-full="<?php echo htmlentities((string) $ad['province']); ?> <?php echo htmlentities((string) $ad['city']); ?> <?php echo htmlentities((string) $ad['district']); ?> <?php echo htmlentities((string) $ad['address']); ?>" onclick="pickAddr(this)">
            <div style="font-size:14px;color:#333;font-weight:600;"><?php echo htmlentities((string) $ad['name']); ?> · <?php echo htmlentities((string) $ad['mobile']); if($ad['is_default']==1): ?><span class="tag tag-red">默认</span><?php endif; ?></div>
            <div style="font-size:12px;color:#999;margin-top:4px;"><?php echo htmlentities((string) $ad['province']); ?> <?php echo htmlentities((string) $ad['city']); ?> <?php echo htmlentities((string) $ad['district']); ?> <?php echo htmlentities((string) $ad['address']); ?></div>
        </div>
        <?php endforeach; ?>
        <a href="/user/address_edit?id=0" style="display:block;text-align:center;font-size:13px;color:#E4393C;padding:12px 0;">＋ 新增收货地址</a>
    </div>
</div>

<div class="form-card" style="margin-top:12px;">
    <div class="form-row">
        <span class="fl">支付方式</span>
        <div class="fr pay-radio">
            <label class="on" onclick="pickPay(0)">余额支付</label>
            <!-- <label onclick="pickPay(1)">微信支付</label> -->
        </div>
    </div>
</div>
<input type="hidden" id="payType" value="balance">

<div class="form-submit">
    <button class="btn btn-red" onclick="doPay()">确认支付</button>
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
var addrCount = <?php echo htmlentities((string) count($addresses)); ?>;
function openAddr(){
    if (addrCount <= 0) { location.href = '/user/address_edit?id=0'; return; }
    if (addrCount <= 1) return;
    document.getElementById('addrMask').classList.add('show');
    document.getElementById('addrSheet').classList.add('show');
}
function closeAddr(){
    document.getElementById('addrMask').classList.remove('show');
    document.getElementById('addrSheet').classList.remove('show');
}
function pickAddr(el){
    document.getElementById('addressId').value = el.getAttribute('data-id');
    document.getElementById('addrName').textContent = el.getAttribute('data-name') + ' · ' + el.getAttribute('data-mobile');
    document.getElementById('addrDetail').textContent = el.getAttribute('data-full');
    closeAddr();
}
function pickPay(i) {
    document.querySelectorAll('.pay-radio label').forEach(function(el, k){
        el.classList.toggle('on', k === i);
    });
    document.getElementById('payType').value = i === 0 ? 'balance' : 'wechat';
}
function doPay() {
    var addressId = document.getElementById('addressId').value;
    if (!addressId || addressId == 0) { toast('请先添加收货地址'); return; }
    ajaxPost('/order/pay', {id: <?php echo htmlentities((string) $order['id']); ?>, address_id: addressId, pay_type: document.getElementById('payType').value}, function(res){
        if (res.code === 1) {
            toast('支付成功');
            setTimeout(function(){ location.href = '/order/list'; }, 600);
        } else {
            toast(res.msg);
        }
    });
}
</script>

</body>
</html>

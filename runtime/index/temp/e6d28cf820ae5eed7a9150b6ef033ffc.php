<?php /*a:2:{s:59:"/www/wwwroot/2026/08/16/17_3/app/index/view/order/list.html";i:1787045308;s:55:"/www/wwwroot/2026/08/16/17_3/app/index/view/layout.html";i:1787189824;}*/ ?>
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
    
<div class="order-tabs">
    <a href="/order/list" <?php if($order_status==''): ?>class="on"<?php endif; ?>>全部</a>
    <a href="/order/list?order_status=0" <?php if($order_status=='0'): ?>class="on"<?php endif; ?>>待付款</a>
    <a href="/order/list?order_status=1" <?php if($order_status=='1'): ?>class="on"<?php endif; ?>>待发货</a>
    <a href="/order/list?order_status=2" <?php if($order_status=='2'): ?>class="on"<?php endif; ?>>待收货</a>
    <a href="/order/list?order_status=3" <?php if($order_status=='3'): ?>class="on"<?php endif; ?>>已完成</a>
    <a href="/order/list?order_status=5" <?php if($order_status=='5'): ?>class="on"<?php endif; ?>>售后中</a>
</div>
<?php if(empty($list)): ?>
<div class="empty"><div class="e-ico">📦</div><div>没有更多数据了</div></div>
<?php else: foreach($list as $o): ?>
<div class="order-card">
    <div class="o-head">
        <span>订单号：<?php echo htmlentities((string) $o['order_no']); ?></span>
        <span class="st"><?php echo htmlentities((string) $o['status_name']); ?></span>
    </div>
    <a class="o-body" href="/order/pay?id=<?php echo htmlentities((string) $o['id']); ?>">
        <div class="thumb">
            <?php if(!empty($o['goods_cover'])): ?>
            <img src="<?php echo htmlentities((string) $o['goods_cover']); ?>" alt="">
            <?php else: ?>
            <span class="noimg">🏺</span>
            <?php endif; ?>
        </div>
        <div class="oinfo">
            <div class="oname"><?php echo htmlentities((string) $o['goods_title']); ?></div>
            <div class="oprice"><small>¥ </small><?php echo htmlentities((string) number_format($o['price'],2)); ?></div>
            <div class="osub"><?php echo htmlentities((string) date('Y-m-d H:i',!is_numeric($o['create_time'])? strtotime($o['create_time']) : $o['create_time'])); ?></div>
        </div>
    </a>
    <?php if($o['order_status']==2 && !empty($o['ship_no'])): ?>
    <div class="o-ship">
        <span class="ship-ico">🚚</span>
        <span class="ship-info"><?php echo htmlentities((string) (isset($o['ship_company']) && ($o['ship_company'] !== '')?$o['ship_company']:'快递')); ?> <b><?php echo htmlentities((string) $o['ship_no']); ?></b></span>
        <a class="ship-copy" href="javascript:copyShip('<?php echo htmlentities((string) $o['ship_no']); ?>')">复制单号</a>
    </div>
    <?php endif; ?>
    <div class="o-foot">
        <?php if($o['order_status']==0): ?>
        <a class="btn-sm solid-red" href="/order/pay?id=<?php echo htmlentities((string) $o['id']); ?>">去付款</a>
        <a class="btn-sm gray" href="javascript:cancelOrder(<?php echo htmlentities((string) $o['id']); ?>)">取消</a>
        <?php elseif($o['order_status']==2): ?>
        <a class="btn-sm solid-red" href="javascript:confirmReceipt(<?php echo htmlentities((string) $o['id']); ?>)">确认收货</a>
        <?php elseif($o['order_status']==3 && empty($o['has_after_sale'])): ?>
        <a class="btn-sm gray" href="javascript:openAfterSale(<?php echo htmlentities((string) $o['id']); ?>)">申请售后</a>
        <?php elseif($o['order_status']==5): ?>
        <span class="aft-tip">售后处理中</span>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; if($page*$limit < $total): ?>
<a class="load-more" href="/order/list?order_status=<?php echo htmlentities((string) $order_status); ?>&page=<?php echo htmlentities((string) $page+1); ?>">加载更多</a>
<?php else: ?>
<div class="no-more">—— 没有更多数据了 ——</div>
<?php endif; ?>
<?php endif; ?>

<!-- 申请售后弹窗 -->
<div class="mask" id="aftMask" onclick="closeAfterSale()"></div>
<div class="sheet" id="aftSheet">
    <div class="s-title">申请售后 <span class="s-close" onclick="closeAfterSale()">×</span></div>
    <div class="s-body">
        <div class="aft-tip">请填写售后理由，提交后订单将转为售后单，由平台审核处理。</div>
        <textarea id="aftReason" class="aft-textarea" rows="4" maxlength="500" placeholder="请输入售后理由（至少 5 个字）"></textarea>
        <button class="btn btn-red" onclick="doAfterSale()">确认提交</button>
    </div>
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
function copyShip(no) {
    function done() { toast('物流单号已复制'); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(no).then(done).catch(function(){
            fallbackCopy(no, done);
        });
    } else {
        fallbackCopy(no, done);
    }
}
function fallbackCopy(no, done) {
    var ta = document.createElement('textarea');
    ta.value = no;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch(e) { toast('复制失败，请手动复制'); }
    document.body.removeChild(ta);
}
function cancelOrder(id) {
    confirmBox('确定要取消该订单吗？', '提示', function(){
        ajaxPost('/order/cancel', {id: id}, function(res){
            if(res.code==1){ toast('已取消'); setTimeout(function(){ location.reload(); }, 600); }
            else { toast(res.msg); }
        });
    });
}
function confirmReceipt(id) {
    confirmBox('确定已收到商品？', '提示', function(){
        ajaxPost('/order/confirm', {id: id}, function(res){
            if(res.code==1){ toast('已确认收货'); setTimeout(function(){ location.reload(); }, 600); }
            else { toast(res.msg); }
        });
    });
}
var aftOrderId = 0;
function openAfterSale(id) {
    aftOrderId = id;
    document.getElementById('aftReason').value = '';
    document.getElementById('aftMask').classList.add('show');
    document.getElementById('aftSheet').classList.add('show');
    setTimeout(function(){ document.getElementById('aftReason').focus(); }, 300);
}
function closeAfterSale() {
    document.getElementById('aftMask').classList.remove('show');
    document.getElementById('aftSheet').classList.remove('show');
}
function doAfterSale() {
    var reason = document.getElementById('aftReason').value.trim();
    if (reason.length < 5) { toast('请填写售后理由（至少 5 个字）'); return; }
    confirmBox('确认提交售后申请？提交后订单将转为售后单，等待平台处理。', '提示', function(){
        ajaxPost('/order/afterSaleApply', {id: aftOrderId, reason: reason}, function(res){
            if(res.code==1){ toast(res.msg); closeAfterSale(); setTimeout(function(){ location.reload(); }, 800); }
            else if(res.code==-1){ location.href='/user/login'; }
            else { toast(res.msg); }
        });
    });
}
</script>

</body>
</html>

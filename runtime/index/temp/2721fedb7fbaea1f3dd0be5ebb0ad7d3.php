<?php /*a:2:{s:47:"D:\work\08\17_3\app\index\view\index\deals.html";i:1787134438;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div style="padding:10px 14px;">
    <?php if(empty($list)): ?>
    <div class="empty" style="padding:60px 0;"><div class="e-ico">🤝</div>暂无成交记录</div>
    <?php else: foreach($list as $d): ?>
    <div class="deal-card">
        <div class="dc-top">
            <span class="dc-no">订单编号：<?php echo htmlentities((string) $d['order_no']); ?></span>
            <span class="dc-price">¥ <?php echo htmlentities((string) number_format($d['price'],2)); ?></span>
        </div>
        <a class="dc-goods" href="/goods/detail?id=<?php echo htmlentities((string) $d['goods_id']); ?>">拍品：<?php echo htmlentities((string) $d['goods_title']); ?></a>
        <div class="dc-row">
            <span>卖家店铺：<?php echo htmlentities((string) $d['seller_name']); ?></span>
            <span>买家：<?php echo htmlentities((string) $d['buyer_name']); ?></span>
        </div>
        <div class="dc-time">成交时间：<?php if($d['deal_time']): ?><?php echo htmlentities((string) date('Y-m-d H:i',!is_numeric($d['deal_time'])? strtotime($d['deal_time']) : $d['deal_time'])); else: ?>-<?php endif; ?></div>
    </div>
    <?php endforeach; ?>
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
var page = <?php echo htmlentities((string) $page); ?>, limit = <?php echo htmlentities((string) $limit); ?>, total = <?php echo htmlentities((string) $total); ?>;
if (page * limit < total) {
    var btn = document.createElement('div');
    btn.className = 'empty';
    btn.style.padding = '20px 0';
    btn.innerHTML = '<a class="e-btn" href="/deals?page=' + (page+1) + '">加载更多</a>';
    document.querySelector('.page').appendChild(btn);
}
</script>

</body>
</html>

<?php /*a:2:{s:48:"D:\work\08\17_3\app\index\view\index\search.html";i:1787046236;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="search-top">
    <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        <input type="text" id="kw" placeholder="请输入搜索内容" value="<?php echo htmlentities((string) $keyword); ?>" onkeydown="if(event.keyCode==13)doSearch();">
    </div>
    <a class="search-btn" href="javascript:doSearch();">搜索</a>
</div>
<div class="search-tabs">
    <a href="javascript:switchTab('goods');" class="<?php if($type=='goods'): ?>on<?php endif; ?>" id="tab-goods">拍品</a>
    <a href="javascript:switchTab('shop');" class="<?php if($type=='shop'): ?>on<?php endif; ?>" id="tab-shop">店铺</a>
</div>

<?php if($keyword==''): ?>
<div class="empty"><div class="e-ico">🔍</div><div>输入关键词搜索拍品或店铺</div></div>
<?php elseif(empty($list)): ?>
<div class="empty"><div class="e-ico">🔍</div><div>没有找到与“<?php echo htmlentities((string) $keyword); ?>”相关的内容</div></div>
<?php else: if($type=='goods'): ?>
<div class="goods-grid">
    <?php foreach($list as $g): ?>
    <a class="grid-item" href="/goods/detail?id=<?php echo htmlentities((string) $g['id']); ?>">
        <div class="g-thumb">
            <?php if(!empty($g['cover'])): ?>
            <img src="<?php echo htmlentities((string) $g['cover']); ?>" alt="<?php echo htmlentities((string) $g['title']); ?>">
            <?php else: ?>
            <span class="noimg">🏺</span>
            <?php endif; ?>
        </div>
        <div class="g-body">
            <div class="g-title"><?php echo htmlentities((string) $g['title']); ?></div>
            <div class="g-price"><small>¥ </small><?php echo htmlentities((string) $g['price_str']); ?></div>
            <div class="g-meta">
                <span class="time" data-countdown="<?php echo htmlentities((string) $g['end_time']); ?>" data-format="short">--</span>
                <span><?php echo htmlentities((string) $g['bid_count']); ?>次出价</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div style="padding:6px 0;">
    <?php foreach($list as $s): ?>
    <a class="shop-card" href="/shop/detail?id=<?php echo htmlentities((string) $s['id']); ?>">
        <div class="avatar">
            <?php if(!empty($s['avatar'])): ?>
            <img src="<?php echo htmlentities((string) $s['avatar']); ?>" alt="<?php echo htmlentities((string) $s['nickname']); ?>">
            <?php else: ?>
            <span class="noa">🏪</span>
            <?php endif; ?>
        </div>
        <div class="sinfo">
            <div class="sname"><?php if(!empty($s['shop_name'])): ?><?php echo htmlentities((string) $s['shop_name']); else: ?><?php echo htmlentities((string) $s['nickname']); ?><?php endif; ?></div>
            <?php if(!empty($s['seller_intro'])): ?><div class="sintro"><?php echo htmlentities((string) $s['seller_intro']); ?></div><?php endif; ?>
            <div class="smeta">累计售出 <?php echo htmlentities((string) $s['total_sell']); ?> 件 · 成交 <?php echo htmlentities((string) $s['total_buy']); ?> 次</div>
        </div>
        <span class="enter">进店</span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; if($page*$limit < $total): ?>
<a class="load-more" href="/search?keyword=<?php echo htmlentities((string) urlencode($keyword)); ?>&type=<?php echo htmlentities((string) $type); ?>&page=<?php echo htmlentities((string) $page+1); ?>">加载更多</a>
<?php else: ?>
<div class="no-more">—— 没有更多数据了 ——</div>
<?php endif; ?>
<?php endif; ?>

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
function doSearch() {
    var kw = document.getElementById('kw').value.trim();
    location.href = '/search?keyword=' + encodeURIComponent(kw) + '&type=<?php echo htmlentities((string) $type); ?>';
}
function switchTab(t) {
    location.href = '/search?keyword=' + encodeURIComponent(document.getElementById('kw').value.trim()) + '&type=' + t;
}
initCountdowns();
</script>

</body>
</html>

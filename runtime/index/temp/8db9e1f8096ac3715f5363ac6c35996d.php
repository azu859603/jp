<?php /*a:2:{s:60:"/www/wwwroot/2026/08/16/17_3/app/index/view/index/index.html";i:1787187993;s:55:"/www/wwwroot/2026/08/16/17_3/app/index/view/layout.html";i:1787189824;}*/ ?>
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

<div class="home-header">
    <div class="top-row">
        <div class="brand">
            <?php if(!empty($site_logo)): ?>
            <img class="logo-img" src="<?php echo htmlentities((string) $site_logo); ?>" alt="logo">
            <?php endif; ?>
            <span class="logo"><?php echo htmlentities((string) $site_name); ?></span>
        </div>
        <a class="search-capsule" href="/search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
            <input type="text" placeholder="搜索拍品" readonly>
        </a>
    </div>
</div>


<div class="page <?php if(!empty($hide_header)): ?>no-hd<?php endif; if(!empty($hide_tabbar)): ?>no-tabbar<?php endif; if(!empty($page_class)): ?><?php echo htmlentities((string) $page_class); ?><?php endif; ?>">
    
<!-- 首页顶部图 -->
<div class="home-banner">
    <img src="/static/index.png" alt="" style="width:100%;display:block;">
</div>

<!-- 拍卖头条 -->
<div class="home-head">
    <div class="hh1">
        <span class="t">拍卖</span>
        <span class="sub">头条</span>
        <span style="flex:1;font-size:12px;color:#898989;margin-left:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlentities((string) $site_name); ?> · 每日精选</span>
        <!-- <a class="rec" href="/?sort=end">成交记录 ></a> -->
    </div>
    <!-- <div class="hh2">
        <span class="date"><?php echo htmlentities((string) date('Y-m-d',!is_numeric($now)? strtotime($now) : $now)); ?></span>
        <span class="r">
            <a href="/?sort=new" <?php if($sort=='new'): ?>style="font-weight:600;"<?php endif; ?>>最新</a>
            <a href="/?sort=price" <?php if($sort=='price'): ?>style="font-weight:600;"<?php endif; ?>>高价</a>
        </span>
    </div> -->
</div>

<!-- 拍卖头条（最新3条新闻） -->
<?php if(!empty($headlines)): ?>
<div style="background:#fff;padding:4px 14px 10px;border-bottom:8px solid #f5f5f5;">
    <?php foreach($headlines as $h): ?>
    <a href="/news/detail?id=<?php echo htmlentities((string) $h['id']); ?>" style="display:flex;align-items:center;padding:6px 0;border-bottom:1px solid #f7f7f7;font-size:13px;color:#333;">
        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlentities((string) $h['title']); ?></span>
        <span style="color:#898989;font-size:12px;flex-shrink:0;"><?php echo htmlentities((string) date('m-d H:i',!is_numeric($h['create_time'])? strtotime($h['create_time']) : $h['create_time'])); ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- 成交记录：标题 + 向上循环滚动 -->
<?php if(!empty($deals)): ?>
<div class="deal-wrap">
    <div class="deal-title">成交记录<a class="deal-more" href="/deals">更多 ›</a></div>
    <div class="deal-bar" id="dealBar">
        <div class="deal-track" id="dealTrack">
            <?php foreach($deals as $d): ?><div class="deal-item"><span class="deal-txt">恭喜 <?php echo htmlentities((string) $d['display_name']); ?> 以 ¥<?php echo htmlentities((string) number_format($d['price'],2)); ?> 成交「<?php echo htmlentities((string) $d['title']); ?>」</span><span class="deal-time"><?php echo htmlentities((string) date('m-d H:i',!is_numeric($d['create_time'])? strtotime($d['create_time']) : $d['create_time'])); ?></span></div><?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 分类 tab -->
<div class="cate-tabs">
    <a href="/" <?php if($category_id==0): ?>class="on"<?php endif; ?>>全部</a>
    <?php foreach($categories as $c): ?>
    <a href="/?category_id=<?php echo htmlentities((string) $c['id']); ?>" <?php if($category_id==$c['id']): ?>class="on"<?php endif; ?>><?php echo htmlentities((string) $c['name']); ?></a>
    <?php endforeach; ?>
    <a class="more" href="/category">
        更多
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
</div>

<!-- 瀑布流商品 -->
<?php if(empty($list)): ?>
<div class="empty">
    <div class="e-ico">🏺</div>
    <div>暂无可参与竞拍的拍品</div>
    <a class="e-btn" href="/">刷新看看</a>
</div>
<?php else: ?>
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
                <span class="time">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 13.5"/></svg>
                    <b data-countdown="<?php echo htmlentities((string) $g['end_time']); ?>" data-format="short">--</b>
                </span>
                <span class="bid-btn">去出价</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php if($page*$limit < $total): ?>
<a class="load-more" href="/?page=<?php echo htmlentities((string) $page+1); ?>&category_id=<?php echo htmlentities((string) $category_id); ?>&seller_id=<?php echo htmlentities((string) $seller_id); ?>&keyword=<?php echo htmlentities((string) urlencode($keyword)); ?>&sort=<?php echo htmlentities((string) $sort); ?>">加载更多</a>
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
function switchSort(s) {
    location.href = '/?sort=' + s + '&category_id=<?php echo htmlentities((string) $category_id); ?>';
}

// 成交记录：向上无缝循环滚动
(function () {
    var bar = document.getElementById('dealBar');
    if (!bar) return;
    var track = document.getElementById('dealTrack');
    var content = track.innerHTML;
    // 复制内容直到轨道高度超过容器 2 倍，保证无缝循环
    var guard = 0;
    while (track.offsetHeight < bar.offsetHeight * 2 && guard < 50) {
        track.innerHTML += content;
        guard++;
    }
    var duration = (track.offsetHeight / 2) / 28; // 28px/s（每秒约 1 条）
    track.style.animationDuration = duration + 's';
    bar.classList.add('scrolling');
})();
initCountdowns();

// 首页轮播：自动播放 + 指示点切换 + 触摸滑动
(function () {
    var slider = document.getElementById('bannerSlider');
    if (!slider) return;
    var items = slider.querySelectorAll('a');
    var dots = document.querySelectorAll('.home-banner .dots i');
    var idx = 0, total = items.length, timer = null;
    var startX = 0, curX = 0, dragging = false;
    if (total <= 1) return;
    function show(n) {
        idx = (n + total) % total;
        slider.style.transform = 'translateX(-' + idx * 100 + '%)';
        dots.forEach(function (d, i) { d.className = i === idx ? 'on' : ''; });
    }
    // 触摸滑动
    slider.addEventListener('touchstart', function (e) {
        dragging = true;
        startX = e.touches[0].clientX;
        curX = startX;
        slider.style.transition = 'none';
        clearInterval(timer);
    }, { passive: true });
    slider.addEventListener('touchmove', function (e) {
        if (!dragging) return;
        curX = e.touches[0].clientX;
        var dx = curX - startX;
        slider.style.transform = 'translateX(calc(-' + idx * 100 + '% + ' + dx + 'px))';
    }, { passive: true });
    slider.addEventListener('touchend', function () {
        if (!dragging) return;
        dragging = false;
        var dx = curX - startX;
        slider.style.transition = 'transform .3s ease';
        if (dx < -50) show(idx + 1);
        else if (dx > 50) show(idx - 1);
        else show(idx);
        restart();
    });
    // 指示点切换
    dots.forEach(function (d, i) {
        d.onclick = function () { show(i); restart(); };
    });
    function restart() {
        clearInterval(timer);
        timer = setInterval(function () { show(idx + 1); }, 3500);
    }
    show(0);
    restart();
})();
</script>

</body>
</html>

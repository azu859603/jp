<?php /*a:2:{s:48:"D:\work\08\17_3\app\index\view\goods\detail.html";i:1787187992;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<!-- 商品大图 轮播 -->
<div class="detail-swiper" id="swiper">
    <div class="swiper-track" id="swiperTrack">
        <?php foreach($images as $k=>$img): ?>
        <div class="img-wrap" data-i="<?php echo htmlentities((string) $k); ?>">
            <img src="<?php echo htmlentities((string) $img); ?>" alt="<?php echo htmlentities((string) $goods['title']); ?>" onclick="previewImg(<?php echo htmlentities((string) $k); ?>)">
        </div>
        <?php endforeach; if(empty($images)): ?>
        <div class="img-wrap">
            <div class="no-img">🏺</div>
        </div>
        <?php endif; ?>
    </div>
    <?php if(count($images)>1): ?>
    <div class="dots">
        <?php foreach($images as $k=>$img): ?><i class="<?php if($k==0): ?>on<?php endif; ?>" data-d="<?php echo htmlentities((string) $k); ?>"></i><?php endforeach; ?>
    </div>
    <span class="img-count" id="imgCount">1/<?php echo htmlentities((string) count($images)); ?></span>
    <?php endif; ?>
</div>

<!-- 竞拍状态条 -->
<?php if($goods['status']==1): ?>
<div class="auction-bar">
    <div class="l">
        <span class="ab-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 13.5"/></svg>
        </span>
        <span class="ab-txt"><b>正在竞拍</b><i>火热进行中</i></span>
    </div>
    <div class="r">
        <span class="ab-lab">距离结束</span>
        <b class="ab-time" data-countdown="<?php echo htmlentities((string) $goods['end_time']); ?>" data-format="detail">--</b>
    </div>
</div>
<?php elseif($goods['status']==2): ?>
<div class="auction-bar" style="background:linear-gradient(135deg,#f97316,#fbbf24);">
    <div class="l">
        <span class="ab-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
        <span class="ab-txt"><b>竞拍成交</b><i>恭喜买家</i></span>
    </div>
    <div class="r">
        <span class="ab-lab">成交价</span>
        <b class="ab-time">¥<?php echo htmlentities((string) $goods['final_price']); ?></b>
    </div>
</div>
<?php else: ?>
<div class="auction-bar" style="background:linear-gradient(135deg,#9ca3af,#cbd5e1);">
    <div class="l">
        <span class="ab-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </span>
        <span class="ab-txt"><b>竞拍已结束</b><i><?php if($goods['status']==3): ?>流拍<?php else: ?>未成交<?php endif; ?></i></span>
    </div>
    <div class="r">
        <span class="ab-lab">状态</span>
        <b class="ab-time"><?php if($goods['status']==3): ?>流拍<?php else: ?>未成交<?php endif; ?></b>
    </div>
</div>
<?php endif; ?>

<!-- 店铺信息 -->
<div class="shop-bar">
    <a class="s-ico" href="/shop/detail?id=<?php echo htmlentities((string) $goods['seller_id']); ?>">
        <?php if(!empty($goods['seller_avatar'])): ?>
        <img src="<?php echo htmlentities((string) $goods['seller_avatar']); ?>" alt="">
        <?php else: ?>
        🏺
        <?php endif; ?>
    </a>
    <div class="s-info">
        <a class="s-name" href="/shop/detail?id=<?php echo htmlentities((string) $goods['seller_id']); ?>" style="display:block;color:inherit;text-decoration:none;"><?php if(!empty($goods['seller_shop'])): ?><?php echo htmlentities((string) $goods['seller_shop']); else: ?><?php echo htmlentities((string) $goods['seller_name']); ?><?php endif; ?> <span class="s-badge">钻石店铺</span></a>
        <div class="s-tags"><span>企业认证</span></div>
    </div>
    <div class="s-ops">
        <a class="s-op yellow" href="javascript:void(0)" id="followBtn" onclick="toggleFollow()"><?php if($is_followed): ?>已关注<?php else: ?>关注<?php endif; ?></a>
        <a class="s-op blue" href="/chat/detail?goods_id=<?php echo htmlentities((string) $goods['id']); ?>&seller_id=<?php echo htmlentities((string) $goods['seller_id']); ?>">咨询</a>
    </div>
</div>

<!-- 店铺资料 -->
<div class="shop-meta">
    <div class="sm-intro"><?php if(!empty($goods['seller_intro'])): ?><?php echo htmlentities((string) $goods['seller_intro']); else: ?>该店铺暂无介绍<?php endif; ?></div>
    <div class="sm-row">
        <div class="sm-item"><div class="v">¥<?php echo htmlentities((string) number_format((isset($goods['seller_deposit']) && ($goods['seller_deposit'] !== '')?$goods['seller_deposit']:0),2)); ?></div><div class="k">消费保证金</div></div>
        <div class="sm-item"><div class="v"><?php echo htmlentities((string) number_format((isset($goods['shop_score']) && ($goods['shop_score'] !== '')?$goods['shop_score']:0),1)); ?></div><div class="k">店铺评分</div></div>
        <div class="sm-item"><div class="v"><?php echo htmlentities((string) (isset($goods['fans_count']) && ($goods['fans_count'] !== '')?$goods['fans_count']:0)); ?></div><div class="k">粉丝数量</div></div>
    </div>
</div>

<!-- 标题与描述 -->
<div class="detail-title"><?php echo htmlentities((string) $goods['title']); ?></div>
<div class="detail-desc">
    <div class="txt" id="descTxt" style="max-height:70px;overflow:hidden;"><?php echo htmlentities((string) (isset($goods['content']) && ($goods['content'] !== '')?$goods['content']:'暂无描述')); ?></div>
    <a href="javascript:void(0)" class="more-btn" id="descMore" style="display:none;" onclick="toggleDesc()">展开</a>
</div>

<!-- 参数区（参考站样式） -->
<div class="detail-params">
    <div class="param-row"><span>起拍价</span><b>¥<?php echo htmlentities((string) number_format($goods['start_price'],2)); ?></b></div>
    <div class="param-row"><span>保证金</span><b><?php if($goods['deposit']>0): ?>¥<?php echo htmlentities((string) number_format($goods['deposit'],2)); else: ?>无需保证金<?php endif; ?></b></div>
    <!-- <div class="param-row"><span>佣金</span><b><?php echo htmlentities((string) (isset($goods['commission_rate']) && ($goods['commission_rate'] !== '')?$goods['commission_rate']:0)); ?>%</b></div> -->
    <div class="param-row"><span>加价幅度</span><b>¥<?php echo htmlentities((string) number_format($goods['raise_price'],2)); ?></b></div>
    <?php if(isset($goods['reference_price']) && $goods['reference_price'] > 0): ?>
    <div class="param-row"><span>参考价</span><b>¥<?php echo htmlentities((string) number_format($goods['reference_price'],2)); ?></b></div>
    <?php endif; ?>
    <div class="param-row"><span>邮费</span><b><?php if(isset($goods['is_free_shipping']) && $goods['is_free_shipping']): ?>包邮<?php else: ?>买家承担<?php endif; ?></b></div>
</div>

<!-- 互动信息 -->
<div class="detail-meta">
    <div class="left">
        <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 13.5"/></svg>
            <?php echo htmlentities((string) date('m-d H:i',!is_numeric($goods['end_time'])? strtotime($goods['end_time']) : $goods['end_time'])); ?>
        </span>
        <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7.5 11-7.5S23 12 23 12s-4 7.5-11 7.5S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <?php echo htmlentities((string) $goods['view_count']); ?>
        </span>
        <!-- <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
            <?php echo htmlentities((string) $goods['bid_count']); ?>
        </span> -->
    </div>
    <!-- <a class="share-btn" href="javascript:shareGoods();">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="3" x2="12" y2="15"/><polyline points="7 8 12 3 17 8"/><path d="M4 21h16"/></svg>
        分享
    </a> -->
    <a class="fav-btn <?php if($is_faved): ?>on<?php endif; ?>" id="favBtn" href="javascript:toggleFav();">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
        <span id="favTxt"><?php if($is_faved): ?>已收藏<?php else: ?>收藏<?php endif; ?></span>
    </a>
</div>

<!-- 出价记录 -->
<div class="bid-list">
    <div class="b-head">出价记录 <span class="b-count">共 <?php echo htmlentities((string) $goods['bid_count']); ?> 次</span></div>
    <?php if(empty($bids)): ?>
    <div class="empty"><div class="e-ico">🔨</div>暂无出价，快来抢第一口</div>
    <?php else: foreach($bids as $k=>$b): ?>
    <div class="bid-row <?php if($k==0): ?>top<?php endif; if(!empty($user) && $b['user_id']==$user['id']): ?> mine<?php endif; if($k>=5): ?> bid-hide<?php endif; ?>">
        <span class="bid-rank r<?php echo htmlentities((string) $k+1); ?>"><?php echo htmlentities((string) $k+1); ?></span>
        <div class="bid-user">
            <div class="u-name"><?php echo htmlentities((string) $b['display_name']); if($k==0): ?><span class="lead-tag">领先</span><?php else: ?><span class="out-tag">出局</span><?php endif; if(!empty($user) && $b['user_id']==$user['id']): ?><span class="my-tag">我</span><?php endif; ?></div>
            <div class="u-time"><?php echo htmlentities((string) date('m-d H:i:s',!is_numeric($b['create_time'])? strtotime($b['create_time']) : $b['create_time'])); ?></div>
        </div>
        <div class="bid-price">¥<?php echo htmlentities((string) number_format($b['price'],2)); ?></div>
    </div>
    <?php endforeach; if(count($bids) > 5): ?>
    <a href="javascript:;" class="bid-more" id="bidMore" onclick="toggleBidMore()">
        <span class="bm-txt">展开更多（<?php echo count($bids)-5; ?>条）</span><span class="bm-arrow">⌄</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- 全屏图片预览 -->
<?php if(count($images)>0): ?>
<div class="preview-mask" id="previewMask" onclick="closePreview()">
    <div class="pv-track" id="pvTrack" onclick="event.stopPropagation()">
        <?php foreach($images as $k=>$img): ?>
        <div class="pv-item"><img src="<?php echo htmlentities((string) $img); ?>" alt=""></div>
        <?php endforeach; ?>
    </div>
    <span class="pv-close" onclick="closePreview()">×</span>
    <span class="pv-count" id="pvCount">1/<?php echo htmlentities((string) count($images)); ?></span>
    <?php if(count($images)>1): ?>
    <span class="pv-prev" onclick="pvMove(-1)">‹</span>
    <span class="pv-next" onclick="pvMove(1)">›</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- 出价弹窗 -->
<div class="mask" id="bidMask" onclick="closeBid()"></div>
<div class="sheet" id="bidSheet">
    <div class="s-title">出价竞拍 <span class="s-close" onclick="closeBid()">×</span></div>
    <div class="s-sub"><?php echo htmlentities((string) $goods['title']); ?></div>
    <div class="s-body">
        <div class="cur-price">
            <span>当前价</span>
            <b>¥ <span id="bidCur"><?php echo htmlentities((string) number_format($current_price,2)); ?></span></b>
        </div>
        <div class="bid-input">
            <span class="unit">¥</span>
            <input type="number" id="bidPrice" placeholder="请输入出价金额">
        </div>
        <div class="bid-tip">
            最低出价：<b>¥<span id="bidMin"><?php echo htmlentities((string) number_format($min_bid,2)); ?></span></b>　加价幅度：<b>¥<span id="bidRaise"><?php echo htmlentities((string) number_format($goods['raise_price'],2)); ?></span></b>
            <br>出价需按加价幅度递增<span style="color:#2ECC71;font-weight:600;">（如 当前价+¥<span id="bidRaise2"><?php echo htmlentities((string) number_format($goods['raise_price'],2)); ?></span>、当前价+¥<span id="bidRaise3"><?php echo htmlentities((string) number_format($goods['raise_price']*2,2)); ?></span>）</span>
            <?php if($goods['deposit']>0): ?>
            <br>缴纳保证金：<b>¥<?php echo htmlentities((string) $goods['deposit']); ?></b><span style="color:#2ECC71;font-weight:600;">（<?php if($paid_deposit>0): ?>已缴纳<?php else: ?>出价时从余额冻结<?php endif; ?>）</span>
            <?php endif; ?>
        </div>
        <button class="btn btn-red" onclick="doBid()">确认出价</button>
    </div>
</div>

</div>


<?php if($goods['status']==1): ?>
<div class="detail-bottom">
    <div class="cur">
        <span>当前价</span>
        <b>¥<span id="footCur"><?php echo htmlentities((string) number_format($current_price,2)); ?></span></b>
    </div>
    <div class="b-actions">
        <!-- <a class="judge-btn" href="javascript:void(0)" onclick="toast('鉴定功能开发中')">去鉴定</a> -->
        <a class="go-bid" href="javascript:openBid();">去出价</a>
    </div>
</div>

<?php else: ?>
<div class="tabbar">
    <a href="/" <?php if($tab_active=='index'): ?>class="on"<?php endif; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
        <span>首页</span>
    </a>
    <a href="/category">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
        <span>分类</span>
    </a>
    <a class="tab-pub" href="/seller/goods_add">
        <span class="pub-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </span>
        <span>发布</span>
    </a>
    <a href="/user/center">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c1.2-3.6 4-5.5 7.5-5.5s6.3 1.9 7.5 5.5"/></svg>
        <span>我的</span>
    </a>
</div>
<?php endif; ?>

</div>
<script src="/static/m.js"></script>

<script>
var goodsId = <?php echo htmlentities((string) $goods['id']); ?>;
var minBid = <?php echo htmlentities((string) $min_bid); ?>;
var curPrice = <?php echo htmlentities((string) $current_price); ?>;
var raisePrice = <?php echo htmlentities((string) $goods['raise_price']); ?>;

// 大图轮播（滑动切换）
var curImg = 0;
var maxImages = Math.max(<?php echo htmlentities((string) count($images)); ?>, 1);
function showBig(i) {
    if (maxImages <= 1) return;
    curImg = Math.max(0, Math.min(i, maxImages-1));
    document.getElementById('swiperTrack').style.transform = 'translateX(-' + curImg*100 + '%)';
    document.querySelectorAll('#swiper .dots i').forEach(function(el){
        el.classList.toggle('on', parseInt(el.getAttribute('data-d'))===curImg);
    });
    var c = document.getElementById('imgCount');
    if (c) c.textContent = (curImg+1) + '/' + maxImages;
}
// 触摸滑动轮播
(function(){
    var sw = document.getElementById('swiper');
    var startX = 0, startY = 0, isDrag = false, moved = false;
    sw.addEventListener('touchstart', function(e){
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        isDrag = true;
        moved = false;
    }, {passive:true});
    sw.addEventListener('touchmove', function(e){
        if (!isDrag) return;
        var dx = e.touches[0].clientX - startX;
        var dy = e.touches[0].clientY - startY;
        if (Math.abs(dx) > 8 && Math.abs(dx) > Math.abs(dy)) {
            moved = true;
            document.getElementById('swiperTrack').style.transition = 'none';
            document.getElementById('swiperTrack').style.transform = 'translateX(calc(-' + curImg*100 + '% + ' + dx + 'px))';
        }
    }, {passive:true});
    sw.addEventListener('touchend', function(e){
        if (!isDrag) return;
        isDrag = false;
        var dx = e.changedTouches[0].clientX - startX;
        var track = document.getElementById('swiperTrack');
        track.style.transition = '';
        if (moved && Math.abs(dx) > 50) {
            if (dx < 0) showBig(curImg+1);
            else showBig(curImg-1);
        } else {
            showBig(curImg);
        }
    }, {passive:true});
})();
// 点击小图/指示点
function gotoImg(i) { showBig(i); }
// 全屏预览（点击放大查看，双指/双击缩放，放大后拖动平移，左右滑动切换，×关闭）
var pvIdx = 0;
var pvScale = {}, pvTx = {}, pvTy = {};
var pvGesture = null, pvLastTap = 0;
function currentPvImg() { return document.querySelectorAll('#pvTrack .pv-item img')[pvIdx] || null; }
function previewImg(i) {
    pvIdx = Math.max(0, Math.min(i, maxImages-1));
    var pv = document.getElementById('previewMask');
    document.getElementById('pvTrack').style.transform = 'translateX(-' + pvIdx*100 + '%)';
    document.getElementById('pvCount').textContent = (pvIdx+1) + '/' + maxImages;
    pv.classList.add('show');
    resetPvZoom();
}
function closePreview() { document.getElementById('previewMask').classList.remove('show'); }
function pvMove(d) {
    pvIdx = Math.max(0, Math.min(pvIdx+d, maxImages-1));
    document.getElementById('pvTrack').style.transform = 'translateX(-' + pvIdx*100 + '%)';
    document.getElementById('pvCount').textContent = (pvIdx+1) + '/' + maxImages;
    resetPvZoom();
}
function resetPvZoom() {
    pvScale = {}; pvTx = {}; pvTy = {};
    document.querySelectorAll('#pvTrack .pv-item img').forEach(function(im){
        im.style.transition = 'none';
        im.style.transform = '';
    });
}
function applyPvZoom(im, anim) {
    var s = pvScale[pvIdx] || 1, tx = pvTx[pvIdx] || 0, ty = pvTy[pvIdx] || 0;
    im.style.transition = anim ? 'transform .2s ease' : 'none';
    im.style.transform = (s === 1 && !tx && !ty) ? '' : 'translate(' + tx + 'px,' + ty + 'px) scale(' + s + ')';
}
function clampPvZoom() {
    var im = currentPvImg(); if (!im) return;
    var s = pvScale[pvIdx] || 1;
    if (s <= 1) { pvScale[pvIdx] = 1; pvTx[pvIdx] = 0; pvTy[pvIdx] = 0; applyPvZoom(im, true); return; }
    var vw = window.innerWidth, vh = window.innerHeight;
    var maxX = Math.max(0, (im.offsetWidth * s - vw) / 2);
    var maxY = Math.max(0, (im.offsetHeight * s - vh) / 2);
    pvTx[pvIdx] = Math.max(-maxX, Math.min(maxX, pvTx[pvIdx] || 0));
    pvTy[pvIdx] = Math.max(-maxY, Math.min(maxY, pvTy[pvIdx] || 0));
    applyPvZoom(im, true);
}
function togglePvZoom(px, py) {
    var im = currentPvImg(); if (!im) return;
    var s = pvScale[pvIdx] || 1;
    if (s > 1) { resetPvZoom(); return; }
    var vw = window.innerWidth, vh = window.innerHeight;
    pvScale[pvIdx] = 2.5;
    pvTx[pvIdx] = (1 - 2.5) * (px - vw / 2);
    pvTy[pvIdx] = (1 - 2.5) * (py - vh / 2);
    clampPvZoom();
}
(function(){
    var track = document.getElementById('pvTrack');
    if (!track) return;
    track.addEventListener('touchstart', function(e){
        if (e.touches.length === 2) {
            var t1 = e.touches[0], t2 = e.touches[1];
            pvGesture = {
                mode: 'pinch',
                d0: Math.hypot(t1.clientX - t2.clientX, t1.clientY - t2.clientY),
                px: (t1.clientX + t2.clientX) / 2,
                py: (t1.clientY + t2.clientY) / 2,
                s0: pvScale[pvIdx] || 1,
                tx0: pvTx[pvIdx] || 0,
                ty0: pvTy[pvIdx] || 0
            };
        } else if (e.touches.length === 1) {
            var now = e.timeStamp;
            if (now - pvLastTap < 300) { togglePvZoom(e.touches[0].clientX, e.touches[0].clientY); return; }
            pvGesture = {
                mode: (pvScale[pvIdx] || 1) > 1 ? 'pan' : 'swipe',
                x0: e.touches[0].clientX,
                y0: e.touches[0].clientY,
                tx0: pvTx[pvIdx] || 0,
                ty0: pvTy[pvIdx] || 0,
                moved: false
            };
        }
    }, {passive: true});
    track.addEventListener('touchmove', function(e){
        var g = pvGesture;
        if (!g) return;
        var im = currentPvImg();
        if (g.mode === 'pinch' && e.touches.length === 2 && im) {
            e.preventDefault();
            var t1 = e.touches[0], t2 = e.touches[1];
            var d1 = Math.hypot(t1.clientX - t2.clientX, t1.clientY - t2.clientY);
            var s1 = Math.max(1, Math.min(4, g.s0 * d1 / (g.d0 || 1)));
            var vw = window.innerWidth, vh = window.innerHeight;
            var W = im.offsetWidth, H = im.offsetHeight;
            var nx = (g.px - (vw / 2 - W * g.s0 / 2 + g.tx0)) / (W * g.s0 || 1);
            var ny = (g.py - (vh / 2 - H * g.s0 / 2 + g.ty0)) / (H * g.s0 || 1);
            pvScale[pvIdx] = s1;
            pvTx[pvIdx] = g.px - vw / 2 + W * (s1 / 2 - nx * s1);
            pvTy[pvIdx] = g.py - vh / 2 + H * (s1 / 2 - ny * s1);
            applyPvZoom(im, false);
        } else if (g.mode === 'pan' && im) {
            e.preventDefault();
            g.moved = true;
            pvTx[pvIdx] = g.tx0 + (e.touches[0].clientX - g.x0);
            pvTy[pvIdx] = g.ty0 + (e.touches[0].clientY - g.y0);
            applyPvZoom(im, false);
        } else if (g.mode === 'swipe') {
            var dx = e.touches[0].clientX - g.x0;
            var dy = e.touches[0].clientY - g.y0;
            if (Math.abs(dx) > 8 && Math.abs(dx) > Math.abs(dy)) {
                g.moved = true;
                track.style.transition = 'none';
                track.style.transform = 'translateX(calc(-' + pvIdx * 100 + '% + ' + dx + 'px))';
            }
        }
    }, {passive: false});
    track.addEventListener('touchend', function(e){
        var g = pvGesture;
        pvGesture = null;
        if (!g) return;
        var im = currentPvImg();
        if (g.mode === 'pinch') {
            if (im) clampPvZoom();
        } else if (g.mode === 'pan') {
            if (im) clampPvZoom();
        } else if (g.mode === 'swipe') {
            var dx = e.changedTouches[0].clientX - g.x0;
            track.style.transition = '';
            if (g.moved && Math.abs(dx) > 50) {
                pvMove(dx < 0 ? 1 : -1);
            } else {
                track.style.transform = 'translateX(-' + pvIdx * 100 + '%)';
            }
        }
        if (g.mode !== 'pinch' && !g.moved) pvLastTap = e.timeStamp;
    }, {passive: true});
})();

// 描述全文（默认收起，超长才显示展开按钮）
(function(){
    var txt = document.getElementById('descTxt');
    if (txt) {
        txt.style.maxHeight = '70px';
        txt.style.overflow = 'hidden';
        if (txt.scrollHeight > 70) {
            document.getElementById('descMore').style.display = 'inline';
        }
    }
})();
function toggleDesc(){
    var txt = document.getElementById('descTxt');
    var more = document.getElementById('descMore');
    if (txt.style.maxHeight) {
        txt.style.maxHeight = '';
        txt.style.overflow = '';
        more.textContent = '收起';
    } else {
        txt.style.maxHeight = '70px';
        txt.style.overflow = 'hidden';
        more.textContent = '展开';
    }
}

// 收藏商品
function toggleFav(){
    ajaxPost('/goods/toggleFavorite', {goods_id: goodsId}, function(res){
        if (res.code === 1) {
            toast(res.msg);
            var btn = document.getElementById('favBtn');
            var txt = document.getElementById('favTxt');
            if (res.faved == 1) {
                btn.classList.add('on');
                txt.textContent = '已收藏';
            } else {
                btn.classList.remove('on');
                txt.textContent = '收藏';
            }
        } else {
            toast(res.msg);
        }
    });
}

// 关注店铺
var sellerId = <?php echo htmlentities((string) $goods['seller_id']); ?>;
function toggleFollow(){
    ajaxPost('/goods/toggleFollow', {seller_id: sellerId}, function(res){
        if (res.code === 1) {
            toast(res.msg);
            var btn = document.getElementById('followBtn');
            btn.textContent = res.followed == 1 ? '已关注' : '关注';
        } else {
            toast(res.msg);
        }
    });
}

// 出价记录展开/收起
function toggleBidMore(){
    var rows = document.querySelectorAll('.bid-hide');
    if(!rows.length) return;
    var btn = document.getElementById('bidMore');
    var open = btn.getAttribute('data-open') === '1';
    rows.forEach(function(r){ r.style.display = open ? 'none' : 'flex'; });
    btn.setAttribute('data-open', open ? '0' : '1');
    btn.querySelector('.bm-txt').textContent = open ? '展开更多（' + rows.length + '条）' : '收起';
    btn.querySelector('.bm-arrow').textContent = open ? '⌄' : '⌃';
}

// 出价弹窗
function openBid() {
    var input = document.getElementById('bidPrice');
    input.value = minBid.toFixed(2);
    document.getElementById('bidMask').classList.add('show');
    document.getElementById('bidSheet').classList.add('show');
    setTimeout(function(){ input.focus(); }, 300);
}
function closeBid() {
    document.getElementById('bidMask').classList.remove('show');
    document.getElementById('bidSheet').classList.remove('show');
}
function doBid() {
    var price = parseFloat(document.getElementById('bidPrice').value);
    if (!price || price < minBid) {
        toast('出价不能低于 ' + minBid.toFixed(2) + ' 元');
        return;
    }
    // 阶梯校验：必须是 当前价 + 加价幅度 的整数倍
    var steps = (price - curPrice) / raisePrice;
    if (raisePrice > 0 && Math.abs(steps - Math.round(steps)) > 0.0001) {
        toast('出价必须按加价幅度 ' + raisePrice.toFixed(2) + ' 元递增（最低 ' + minBid.toFixed(2) + ' 元）');
        return;
    }
    ajaxPost('/goods/bid', {goods_id: goodsId, price: price}, function(res){
        if (res.code === 1) {
            toast(res.msg);
            closeBid();
            setTimeout(function(){ location.reload(); }, 800);
        } else if (res.code === -1) {
            location.href = '/user/login';
        } else {
            toast(res.msg);
        }
    });
}
// 分享
function shareGoods() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo htmlentities((string) $goods['title']); ?>',
            text: '拍卖中，当前价 ¥' + curPrice.toFixed(2),
            url: location.href
        }).catch(function(){});
    } else {
        var ta = document.createElement('textarea');
        ta.value = location.href;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); toast('链接已复制'); } catch(e) { toast(location.href); }
        document.body.removeChild(ta);
    }
}
initCountdowns();
</script>

</body>
</html>

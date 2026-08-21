<?php /*a:2:{s:63:"/www/wwwroot/2026/08/16/17_3/app/index/view/user/favorites.html";i:1787035707;s:55:"/www/wwwroot/2026/08/16/17_3/app/index/view/layout.html";i:1787189824;}*/ ?>
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
    
<style>
.fav-item{display:flex;align-items:center;padding:12px 14px;border-bottom:1px solid #f7f7f7;gap:10px;}
.fav-item .cover{width:64px;height:64px;border-radius:8px;object-fit:cover;background:#f5f5f5;flex-shrink:0;}
.fav-item .info{flex:1;min-width:0;}
.fav-item .t{font-size:13px;color:#333;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.fav-item .sub{font-size:11px;color:#999;margin-top:4px;}
.fav-item .price{font-size:14px;color:#E4393C;font-weight:600;flex-shrink:0;}
.fav-item .st{font-size:10px;color:#fff;background:#ccc;border-radius:3px;padding:1px 5px;margin-left:6px;vertical-align:1px;}
.fav-item .st.on{background:#E4393C;}
.fav-item .time{font-size:10px;color:#bbb;margin-top:3px;}
</style>
<div class="bid-list">
    <div class="b-head">关注的拍品（<?php echo htmlentities((string) count($list)); ?>）</div>
    <?php foreach($list as $v): ?>
    <a class="fav-item" href="/goods/detail?id=<?php echo htmlentities((string) $v['goods_id']); ?>">
        <img class="cover" src="<?php echo htmlentities((string) $v['cover']); ?>" onerror="this.src='/static/img/noimg.png'">
        <div class="info">
            <div class="t"><?php echo htmlentities((string) $v['title']); ?></div>
            <div class="sub">收藏于 <?php echo htmlentities((string) date('Y-m-d H:i',!is_numeric($v['create_time'])? strtotime($v['create_time']) : $v['create_time'])); ?></div>
            <div class="time">结束 <?php echo htmlentities((string) date('m-d H:i',!is_numeric($v['end_time'])? strtotime($v['end_time']) : $v['end_time'])); ?></div>
        </div>
        <div class="price">¥<?php echo htmlentities((string) number_format($v['cur_price'],2)); ?><span class="st <?php if($v['goods_status']==1): ?>on<?php endif; ?>"><?php echo htmlentities((string) $v['status_txt']); ?></span></div>
    </a>
    <?php endforeach; if(empty($list)): ?>
    <div class="empty" style="padding:60px 0;">
        <div class="e-ico">💛</div>
        <div>还没有关注的拍品</div>
        <a class="e-btn" href="/">去逛逛</a>
    </div>
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

</body>
</html>

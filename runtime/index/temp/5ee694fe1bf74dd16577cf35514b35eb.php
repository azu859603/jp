<?php /*a:2:{s:57:"D:\phpstudy_pro\WWW\jp\app\index\view\index\category.html";i:1787189824;s:49:"D:\phpstudy_pro\WWW\jp\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="cate-search">
    <div class="sbox" onclick="location.href='/search'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        <input type="text" placeholder="请输入搜索内容" readonly onclick="event.stopPropagation();location.href='/search'">
    </div>
</div>
<div class="cate-wrap">
    <div class="cate-left">
        <?php foreach($categories as $c): ?>
        <a href="/category/list?category_id=<?php echo htmlentities((string) $c['id']); ?>" <?php if($category_id==$c['id']): ?>class="on"<?php endif; ?>><?php echo htmlentities((string) $c['name']); ?></a>
        <?php endforeach; ?>
    </div>
    <div class="cate-right">
        <a class="cate-card" href="/category/list">
            <div class="cc-icon"><span>全</span></div>
            <div class="cc-name">全部拍品</div>
            <div class="cc-count"><b><?php echo htmlentities((string) $total_goods_count); ?></b>件拍品</div>
        </a>
        <?php foreach($categories as $c): ?>
        <a class="cate-card" href="/category/list?category_id=<?php echo htmlentities((string) $c['id']); ?>">
            <div class="cc-icon"><?php if(!empty($c['image'])): ?><img src="<?php echo htmlentities((string) $c['image']); ?>" alt="<?php echo htmlentities((string) $c['name']); ?>"><?php else: ?><span><?php echo htmlentities((string) mb_substr($c['name'],0,1)); ?></span><?php endif; ?></div>
            <div class="cc-name"><?php echo htmlentities((string) $c['name']); ?></div>
            <div class="cc-count"><b><?php echo htmlentities((string) $c['goods_count']); ?></b>件拍品</div>
        </a>
        <?php endforeach; ?>
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

</body>
</html>

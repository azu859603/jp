<?php /*a:2:{s:47:"D:\work\08\17_3\app\index\view\shop\detail.html";i:1787105463;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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
    
<style>
/* 店铺头部 */
.shop-head{background:#fff;padding:16px 14px 14px;border-bottom:8px solid #f5f5f5;}
.shop-head .sh-top{display:flex;align-items:center;}
.shop-head .sh-avatar{width:56px;height:56px;border-radius:50%;background:#f7f7f7;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:30px;color:#ddd;}
.shop-head .sh-avatar img{width:100%;height:100%;object-fit:cover;}
.shop-head .sh-name{flex:1;min-width:0;margin-left:10px;}
.shop-head .sh-name .nm{font-size:17px;font-weight:600;color:#333;display:flex;align-items:center;gap:6px;}
.shop-head .sh-name .nm .badge{font-size:10px;font-weight:400;color:#fff;background:#E4393C;border-radius:3px;padding:1px 5px;line-height:1.5;flex-shrink:0;}
.shop-head .sh-name .auth{display:flex;align-items:center;gap:3px;font-size:11px;color:#16a34a;margin-top:4px;}
.shop-head .sh-name .auth svg{width:12px;height:12px;flex-shrink:0;}
.shop-head .follow-btn{flex-shrink:0;width:64px;height:30px;line-height:28px;text-align:center;border:1px solid #E4393C;border-radius:15px;font-size:13px;color:#E4393C;background:#fff;}
.shop-head .follow-btn.on{background:#E4393C;color:#fff;border-color:#E4393C;}
.shop-head .sh-intro{font-size:12px;color:#898989;line-height:1.6;margin-top:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.shop-head .sh-stats{display:flex;margin-top:12px;background:#fafafa;border-radius:8px;padding:10px 0;}
.shop-head .sh-stats .st{flex:1;text-align:center;}
.shop-head .sh-stats .st .v{font-size:15px;font-weight:600;color:#333;}
.shop-head .sh-stats .st .k{font-size:11px;color:#898989;margin-top:2px;}
/* 店铺拍品 */
.shop-goods-t{display:flex;align-items:center;padding:12px 14px 2px;font-size:15px;font-weight:600;color:#333;background:#fff;}
.shop-goods-t::before{content:'';width:4px;height:14px;background:#E4393C;border-radius:2px;margin-right:7px;}
.shop-goods-t .more{margin-left:auto;font-size:12px;color:#898989;font-weight:400;}
</style>
<!-- 店铺头部 -->
<div class="shop-head">
    <div class="sh-top">
        <div class="sh-avatar">
            <?php if(!empty($seller['avatar'])): ?>
            <img src="<?php echo htmlentities((string) $seller['avatar']); ?>" alt="<?php echo htmlentities((string) $shop_name); ?>">
            <?php else: ?>
            <span>🏪</span>
            <?php endif; ?>
        </div>
        <div class="sh-name">
            <div class="nm"><?php echo htmlentities((string) $shop_name); ?><span class="badge">钻石店铺</span></div>
            <?php if($enterprise_auth): ?>
            <div class="auth">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                企业认证
            </div>
            <?php endif; ?>
        </div>
        <a class="follow-btn <?php if($followed): ?>on<?php endif; ?>" href="javascript:;" onclick="toggleFollow();"><?php if($followed): ?>已关注<?php else: ?>关注<?php endif; ?></a>
    </div>
    <?php if(!empty($seller['seller_intro'])): ?>
    <div class="sh-intro"><?php echo htmlentities((string) $seller['seller_intro']); ?></div>
    <?php endif; ?>
    <div class="sh-stats">
        <div class="st"><div class="v"><?php echo htmlentities((string) number_format($seller['deposit'],2)); ?></div><div class="k">消保金</div></div>
        <div class="st"><div class="v"><?php echo htmlentities((string) number_format($seller['shop_score'],2)); ?></div><div class="k">店铺评分</div></div>
        <div class="st"><div class="v"><?php echo htmlentities((string) $fans); ?></div><div class="k">粉丝</div></div>
    </div>
</div>

<!-- 店铺拍品 -->
<div class="shop-goods-t">店铺拍品<?php if(!empty($goods)): ?><span class="more">共 <?php echo htmlentities((string) count($goods)); ?> 件</span><?php endif; ?></div>
<?php if(empty($goods)): ?>
<div class="empty"><div class="e-ico">🏺</div><div>该店铺暂无拍卖中的拍品</div></div>
<?php else: ?>
<div class="goods-grid" style="background:#fff;padding-bottom:10px;">
    <?php foreach($goods as $g): ?>
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
                <span class="bid-btn">去出价</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
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
function toggleFollow() {
    var sellerId = <?php echo htmlentities((string) $seller['id']); ?>;
    ajaxPost('/goods/toggleFollow', {seller_id: sellerId}, function (res) {
        if (res.code == 1) {
            var btn = document.querySelector('.follow-btn');
            if (res.followed == 1) {
                btn.classList.add('on');
                btn.textContent = '已关注';
            } else {
                btn.classList.remove('on');
                btn.textContent = '关注';
            }
            toast(res.msg);
        } else {
            if (res.code == -1) { toast('请先登录'); setTimeout(function(){ location.href = '/user/login'; }, 600); return; }
            toast(res.msg);
        }
    });
}
initCountdowns();
</script>

</body>
</html>

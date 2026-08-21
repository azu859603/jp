<?php /*a:2:{s:47:"D:\work\08\17_3\app\index\view\user\center.html";i:1787185776;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<!-- 红色头部 -->
<div class="mine-head">
    <div class="row1">
        <div class="avatar">
            <?php if(!empty($user['avatar'])): ?>
            <img src="<?php echo htmlentities((string) $user['avatar']); ?>" alt="avatar">
            <?php else: ?>
            <span class="noa">👤</span>
            <?php endif; ?>
        </div>
        <div class="uinfo">
            <div class="uname"><a href="/user/profile"><?php echo htmlentities((string) $user['nickname']); ?></a>
                 <!-- <span class="level"><?php echo htmlentities((string) $level); ?></span> -->
                </div>
            <div class="upoints">积分：<?php echo htmlentities((string) $user['points']); ?></div>
        </div>
    </div>
    <div class="ustats">
        <a class="item" href="/user/favorites"><div class="num"><?php echo htmlentities((string) $data['fav_count']); ?></div><div class="lbl">关注拍品</div></a>
        <a class="item" href="/user/follows"><div class="num"><?php echo htmlentities((string) $data['follow_count']); ?></div><div class="lbl">关注店铺</div></a>
        <a class="item" href="/user/footprints"><div class="num"><?php echo htmlentities((string) $data['browse_count']); ?></div><div class="lbl">我的足迹</div></a>
    </div>
</div>

<!-- 我的订单 -->
<div class="mine-orders">
    <div class="o-head">
        <span class="t">我的订单</span>
        <a class="all" href="/order/list">查看全部 ›</a>
    </div>
    <div class="o-list">
        <div class="o-item">
            <a href="/order/list?order_status=0">
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="15" rx="2"/><line x1="3" y1="10.5" x2="21" y2="10.5"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="16" y1="3" x2="16" y2="7"/></svg>
                    <?php if($data['wait_pay']>0): ?><span class="badge"><?php echo htmlentities((string) $data['wait_pay']); ?></span><?php endif; ?>
                </span>
                <span class="lbl">待付款</span>
            </a>
        </div>
        <div class="o-item">
            <a href="/order/list?order_status=1">
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                    <?php if($data['wait_ship']>0): ?><span class="badge"><?php echo htmlentities((string) $data['wait_ship']); ?></span><?php endif; ?>
                </span>
                <span class="lbl">待发货</span>
            </a>
        </div>
        <div class="o-item">
            <a href="/order/list?order_status=2">
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 6.5 12 2l11 4.5"/><path d="M1 6.5 12 11l11-4.5"/><path d="M12 11v10"/><path d="M1 6.5v11L12 21"/><path d="M23 6.5v11L12 21"/></svg>
                    <?php if($data['wait_receive']>0): ?><span class="badge"><?php echo htmlentities((string) $data['wait_receive']); ?></span><?php endif; ?>
                </span>
                <span class="lbl">待收货</span>
            </a>
        </div>
        <div class="o-item">
            <a href="/order/list?order_status=3">
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="11" r="2.5"/></svg>
                </span>
                <span class="lbl">售后</span>
            </a>
        </div>
    </div>
</div>

<!-- 我的服务（2×4宫格） -->
<div class="mine-services">
    <div class="sv-head">我的服务</div>
    <div class="sv-grid">
        <a href="/user/wallet">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="14" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="16.5" cy="15" r="1.5"/></svg>
            <span>我的钱包</span>
        </a>
        <?php if(!isset($is_virtual) || !$is_virtual): ?>
        <a href="/user/recharge_log">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M8.5 10.5 12 7l3.5 3.5"/><path d="M8.5 13.5 12 17l3.5-3.5"/></svg>
            <span>充值记录</span>
        </a>
        <a href="/user/withdraw_log">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="5 10 12 3 19 10"/></svg>
            <span>提现记录</span>
        </a>
        <?php endif; ?>
        <a href="/user/bids">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 4 9 15l-4 4"/><path d="M14 4h6v6"/></svg>
            <span>出价记录</span>
        </a>
        <a href="/user/auth">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="9" r="3"/><path d="M8 17.5a4.5 4.5 0 0 1 8 0"/></svg>
            <span>实名认证</span>
        </a>
        <a href="/user/profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.5 19a6 6 0 0 1 11 0"/></svg>
            <span>设置管理</span>
        </a>
        <a href="/user/messages" style="position:relative;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z"/><polyline points="4 6 12 12 20 6"/></svg>
            <?php if($data['msg_unread'] > 0): ?><i class="sv-badge"><?php echo htmlentities((string) $data['msg_unread']); ?></i><?php endif; ?>
            <span>站内信</span>
        </a>
        <a href="/chat/list">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 13.5"/></svg>
            <span>消息</span>
        </a>
        <a href="javascript:contactService()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            <span>联系客服</span>
        </a>
    </div>
   
</div>

<!-- 卖家中心（移至底部菜单） -->
<div class="mine-menu">
    <a href="/user/password">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
        <span class="m-t">修改密码</span>
        <span class="arrow">›</span>
    </a>
    <a href="/user/address">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <span class="m-t">收货地址</span>
        <span class="arrow">›</span>
    </a>
    <a href="/user/logout" style="color:#999;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="m-t">退出登录</span>
        <span class="arrow">›</span>
    </a>
</div>

<!-- 卖家中心（独立高亮专区） -->
<?php if($user['is_seller']==1 && $user['seller_check']==1): ?>
<a class="seller-zone" href="/seller/goods_list">
    <span class="sz-ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
    </span>
    <span class="sz-txt">
        <b>卖家中心</b>
        <i>管理商品 · 查看出价 · 处理订单</i>
    </span>
    <span class="sz-btn">进入 ›</span>
</a>
<?php else: ?>
<a class="seller-zone apply" href="/seller/apply">
    <span class="sz-ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
    </span>
    <span class="sz-txt">
        <b>申请成为卖家</b>
        <i>开通店铺 · 发布拍品 · 开启竞拍</i>
    </span>
    <span class="sz-btn">去申请 ›</span>
</a>
<?php endif; ?>

<div class="mine-footer"><?php echo htmlentities((string) $site_name); ?> © 2026 · 竞拍出价需缴纳保证金，请理性出价</div>
<script>
function contactService() {
    var link = '<?php echo htmlentities((string) $service_link); ?>'.trim();
    if (!link) { toast('暂未配置客服，请联系平台'); return; }
    location.href = link;
}
</script>

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

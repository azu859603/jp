<?php /*a:2:{s:45:"D:\work\08\17_3\app\index\view\chat\list.html";i:1787061407;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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
    
<!-- Tab 切换 -->
<div class="chat-tabs">
    <a href="/chat/list?tab=buyer" class="<?php if($tab=='buyer'): ?>on<?php endif; ?>">买家消息</a>
    <a href="/chat/list?tab=seller" class="<?php if($tab=='seller'): ?>on<?php endif; ?>">卖家消息</a>
</div>

<?php if($tab == 'buyer'): if(empty($buyer_messages)): ?>
    <div class="empty"><div class="e-ico">💬</div><div>暂无买家消息</div></div>
    <?php else: ?>
    <div class="msg-list">
        <?php foreach($buyer_messages as $m): ?>
        <a class="msg-card" href="/chat/detail?goods_id=<?php echo htmlentities((string) $m['goods_id']); ?>&seller_id=<?php echo htmlentities((string) $m['to_uid']); ?>">
            <div class="mc-avatar">
                <?php if(!empty($m['other_avatar'])): ?>
                <img src="<?php echo htmlentities((string) $m['other_avatar']); ?>" alt="">
                <?php else: ?>
                <span class="mc-noa">🏪</span>
                <?php endif; ?>
            </div>
            <div class="mc-body">
                <div class="mc-top">
                    <span class="mc-name"><?php echo htmlentities((string) $m['other_display']); ?></span>
                    <span class="mc-time"><?php echo htmlentities((string) $m['time_str']); ?></span>
                </div>
                <div class="mc-goods"><?php echo htmlentities((string) $m['goods_title']); ?></div>
                <div class="mc-preview">
                    <span class="mc-txt"><?php echo htmlentities((string) mb_substr($m['content'],0,40)); ?></span>
                    <?php if($m['unread'] > 0): ?><span class="mc-badge"><?php echo htmlentities((string) $m['unread']); ?></span><?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; else: if(empty($seller_messages)): ?>
    <div class="empty"><div class="e-ico">💬</div><div>暂无卖家消息</div></div>
    <?php else: ?>
    <div class="msg-list">
        <?php foreach($seller_messages as $m): ?>
        <a class="msg-card" href="/chat/detail?goods_id=<?php echo htmlentities((string) $m['goods_id']); ?>&seller_id=<?php echo htmlentities((string) $m['from_uid']); ?>">
            <div class="mc-avatar">
                <?php if(!empty($m['other_avatar'])): ?>
                <img src="<?php echo htmlentities((string) $m['other_avatar']); ?>" alt="">
                <?php else: ?>
                <span class="mc-noa">🏪</span>
                <?php endif; ?>
            </div>
            <div class="mc-body">
                <div class="mc-top">
                    <span class="mc-name"><?php echo htmlentities((string) $m['other_display']); ?></span>
                    <span class="mc-time"><?php echo htmlentities((string) $m['time_str']); ?></span>
                </div>
                <div class="mc-goods"><?php echo htmlentities((string) $m['goods_title']); ?></div>
                <div class="mc-preview">
                    <span class="mc-txt"><?php echo htmlentities((string) mb_substr($m['content'],0,40)); ?></span>
                    <?php if($m['unread'] > 0): ?><span class="mc-badge"><?php echo htmlentities((string) $m['unread']); ?></span><?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.chat-tabs{display:flex;background:#fff;border-bottom:1px solid #eee;}
.chat-tabs a{flex:1;text-align:center;padding:12px 0;font-size:14px;color:#666;text-decoration:none;}
.chat-tabs a.on{color:#e4393c;border-bottom:2px solid #e4393c;}
.msg-list{padding:0;}
.msg-card{display:flex;padding:12px 14px;background:#fff;border-bottom:1px solid #f0f0f0;text-decoration:none;color:#333;}
.mc-avatar{width:40px;height:40px;border-radius:50%;overflow:hidden;margin-right:10px;flex-shrink:0;}
.mc-avatar img{width:100%;height:100%;object-fit:cover;}
.mc-noa{width:40px;height:40px;border-radius:50%;background:#f4f4f4;display:flex;align-items:center;justify-content:center;font-size:18px;}
.mc-body{flex:1;min-width:0;}
.mc-top{display:flex;justify-content:space-between;align-items:center;}
.mc-name{font-size:14px;font-weight:500;color:#333;}
.mc-time{font-size:11px;color:#bbb;}
.mc-goods{font-size:12px;color:#999;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mc-preview{display:flex;align-items:center;margin-top:4px;}
.mc-txt{font-size:13px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;}
.mc-badge{background:#e4393c;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;margin-left:6px;flex-shrink:0;}
</style>

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

<script>initCountdowns();</script>

</body>
</html>

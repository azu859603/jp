<?php /*a:2:{s:49:"D:\work\08\17_3\app\index\view\user\messages.html";i:1787183198;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<?php if(empty($list)): ?>
<div class="empty" style="padding:60px 0;"><div class="e-ico">✉️</div>暂无站内信</div>
<?php else: ?>
<div style="background:#fff;">
    <?php foreach($list as $m): ?>
    <a class="msg-item <?php if($m['is_read']==0): ?>unread<?php endif; ?>" href="/user/message_detail?id=<?php echo htmlentities((string) $m['id']); ?>">
        <div class="m-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z"/><polyline points="4 6 12 12 20 6"/></svg>
        </div>
        <div class="m-info">
            <div class="m-title"><?php if($m['is_read']==0): ?><i class="m-dot"></i><?php endif; ?><?php echo htmlentities((string) $m['title']); ?></div>
            <div class="m-summary"><?php echo htmlentities((string) $m['summary']); ?></div>
        </div>
        <div class="m-time"><?php echo htmlentities((string) date('m-d H:i',!is_numeric($m['create_time'])? strtotime($m['create_time']) : $m['create_time'])); ?></div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<style>
.msg-item{display:flex;align-items:center;padding:13px 14px;border-bottom:1px solid #f5f5f5;background:#fff;}
.msg-item .m-ico{width:38px;height:38px;border-radius:50%;background:#fdecec;color:#E4393C;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.msg-item .m-ico svg{width:19px;height:19px;}
.msg-item .m-info{flex:1;min-width:0;margin-left:11px;}
.msg-item .m-title{font-size:14px;color:#333;font-weight:500;display:flex;align-items:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.msg-item .m-dot{width:7px;height:7px;border-radius:50%;background:#E4393C;margin-right:6px;flex-shrink:0;}
.msg-item .m-summary{font-size:12px;color:#999;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.msg-item .m-time{font-size:11px;color:#bbb;flex-shrink:0;margin-left:8px;}
.msg-item.unread .m-title{color:#E4393C;font-weight:600;}
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

<script>
var page = <?php echo htmlentities((string) $page); ?>, limit = <?php echo htmlentities((string) $limit); ?>, total = <?php echo htmlentities((string) $total); ?>;
if (page * limit < total) {
    var btn = document.createElement('div');
    btn.className = 'empty';
    btn.style.padding = '20px 0';
    btn.innerHTML = '<a class="e-btn" href="/user/messages?page=' + (page+1) + '">加载更多</a>';
    document.querySelector('.page').appendChild(btn);
}
</script>

</body>
</html>

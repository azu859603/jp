<?php /*a:2:{s:48:"D:\work\08\17_3\app\index\view\user\address.html";i:1787005483;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<?php if(empty($addresses)): ?>
<div class="empty"><div class="e-ico">📍</div><div>还没有收货地址</div></div>
<?php else: foreach($addresses as $a): ?>
<div class="addr-item">
    <div class="a-row1">
        <span class="a-name"><?php echo htmlentities((string) $a['name']); ?></span>
        <span class="a-mobile"><?php echo htmlentities((string) $a['mobile']); ?></span>
        <?php if($a['is_default']==1): ?><span class="a-default">默认</span><?php endif; ?>
    </div>
    <div class="a-addr"><?php echo htmlentities((string) $a['province']); ?> <?php echo htmlentities((string) $a['city']); ?> <?php echo htmlentities((string) $a['district']); ?> <?php echo htmlentities((string) $a['address']); ?></div>
    <div class="a-ops">
        <a href="javascript:void(0)" onclick="setDefault(<?php echo htmlentities((string) $a['id']); ?>)">设为默认</a>
        <a href="/user/address_edit?id=<?php echo htmlentities((string) $a['id']); ?>">编辑</a>
        <a class="red" href="javascript:void(0)" onclick="delAddr(<?php echo htmlentities((string) $a['id']); ?>)">删除</a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<div class="add-addr-btn">
    <a class="btn btn-red" href="/user/address_edit">+ 添加新地址</a>
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
function delAddr(id) {
    confirmBox('确定删除该地址吗？', '删除地址', function(){
        ajaxPost('/user/address', {act: 'delete', id: id}, function(res){
            toast(res.msg);
            if (res.code === 1) setTimeout(function(){ location.reload(); }, 600);
        });
    });
}
function setDefault(id) {
    ajaxPost('/user/address', {act: 'save', id: id, name: '', mobile: '', address: '', is_default: 1, province: '', city: '', district: ''}, function(res){
        if (res.code === 1) { toast('已设为默认地址'); setTimeout(function(){ location.reload(); }, 600); }
        else toast(res.msg);
    });
}
</script>

</body>
</html>

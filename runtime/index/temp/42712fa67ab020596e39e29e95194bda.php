<?php /*a:2:{s:49:"D:\work\08\17_3\app\index\view\user\password.html";i:1787005486;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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
    
<div class="form-card" style="margin-top:12px;">
    <div class="form-row">
        <span class="fl">原密码</span>
        <div class="fr">
            <input type="password" id="old_password" placeholder="请输入原密码">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">新密码</span>
        <div class="fr">
            <input type="password" id="new_password" placeholder="至少6位">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">确认新密码</span>
        <div class="fr">
            <input type="password" id="new_password2" placeholder="再次输入新密码">
        </div>
    </div>
</div>
<div class="form-submit">
    <button class="btn btn-red" onclick="savePwd()">修改密码</button>
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
function savePwd(){
    var oldPwd = document.getElementById('old_password').value;
    var newPwd = document.getElementById('new_password').value;
    var newPwd2 = document.getElementById('new_password2').value;
    if(!oldPwd || newPwd.length < 6 || newPwd !== newPwd2){ toast('请正确填写密码信息'); return; }
    ajaxPost('/user/password', {old_password: oldPwd, new_password: newPwd, new_password2: newPwd2}, function(res){
        toast(res.msg);
        if(res.code == 1) setTimeout(function(){ location.href = '/user/logout'; }, 800);
    });
}
</script>

</body>
</html>

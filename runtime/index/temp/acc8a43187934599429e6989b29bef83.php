<?php /*a:2:{s:48:"D:\work\08\17_3\app\index\view\user\profile.html";i:1787187073;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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
    <div class="form-row" onclick="document.getElementById('avatarFile').click();" style="cursor:pointer;">
        <span class="fl">头像</span>
        <div class="fr" style="justify-content:flex-end;">
            <span style="width:44px;height:44px;border-radius:50%;background:#f0f0f0;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                <?php if(!empty($user['avatar'])): ?><img id="avatarImg" src="<?php echo htmlentities((string) $user['avatar']); ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><span style="font-size:20px;">👤</span><?php endif; ?>
            </span>
            <input type="file" id="avatarFile" accept="image/*" style="display:none;" onchange="uploadAvatar(this)">
            <span style="font-size:11px;color:#999;margin:0 8px 0 10px;">点击修改</span>
            <span class="arrow">›</span>
        </div>
    </div>
    <div class="form-row">
        <span class="fl">昵称</span>
        <div class="fr">
            <input type="text" id="nickname" value="<?php echo htmlentities((string) $user['nickname']); ?>" maxlength="20" placeholder="请输入昵称">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">姓名</span>
        <div class="fr">
            <input type="text" id="real_name" value="<?php echo htmlentities((string) (isset($user['real_name']) && ($user['real_name'] !== '')?$user['real_name']:'')); ?>" maxlength="20" placeholder="请输入真实姓名">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">手机号码</span>
        <div class="fr">
            <span class="ph"><?php echo htmlentities((string) $user['mobile']); ?></span>
        </div>
    </div>
    <div class="form-row">
        <span class="fl">实名认证</span>
        <div class="fr" style="justify-content:flex-end;">
            <?php if($user['auth_status']==2): ?>
            <span class="tag tag-green">已认证</span>
            <?php elseif($user['auth_status']==1): ?>
            <span class="tag tag-orange">审核中</span>
            <?php elseif($user['auth_status']==3): ?>
            <a class="tag tag-red" href="/user/auth">未通过 ›</a>
            <?php else: ?>
            <a class="tag tag-red" href="/user/auth">未认证 ›</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if($user['is_seller']==1): ?>
    <div class="form-row">
        <span class="fl">店铺名称</span>
        <div class="fr">
            <input type="text" id="shop_name" value="<?php echo htmlentities((string) (isset($user['shop_name']) && ($user['shop_name'] !== '')?$user['shop_name']:'')); ?>" maxlength="30" placeholder="请输入店铺名称">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">企业名称</span>
        <div class="fr">
            <input type="text" id="company_name" value="<?php echo htmlentities((string) (isset($user['company_name']) && ($user['company_name'] !== '')?$user['company_name']:'')); ?>" maxlength="50" placeholder="请输入企业名称">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">店铺头像</span>
        <div class="fr" style="justify-content:flex-end;">
            <span style="width:36px;height:36px;border-radius:50%;background:#f0f0f0;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                <?php if(!empty($user['avatar'])): ?><img src="<?php echo htmlentities((string) $user['avatar']); ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><span style="font-size:16px;">👤</span><?php endif; ?>
            </span>
            <span style="font-size:11px;color:#999;margin-left:8px;">与头像一致</span>
        </div>
    </div>
    <?php endif; ?>
</div>
<div class="form-submit">
    <button class="btn btn-red" onclick="saveProfile()">提交</button>
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
function uploadAvatar(input){
    var f = input.files && input.files[0];
    if(!f) return;
    if(f.size > 5*1024*1024){ toast('图片不能超过5M'); return; }
    var fd = new FormData();
    fd.append('file', f);
    fetch('/upload/image', {method:'POST', body: fd})
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(res.code === 1){
                ajaxPost('/user/profile', {nickname: document.getElementById('nickname').value.trim(), avatar: res.url}, function(r2){
                    toast(r2.msg);
                    if(r2.code == 1) setTimeout(function(){ location.reload(); }, 600);
                });
            } else {
                toast(res.msg);
            }
        })
        .catch(function(){ toast('上传失败，请重试'); });
}
function saveProfile(){
    var nickname = document.getElementById('nickname').value.trim();
    if(!nickname){ toast('请输入昵称'); return; }
    var params = {nickname: nickname};
    var shopName = document.getElementById('shop_name');
    var companyName = document.getElementById('company_name');
    if(shopName) params.shop_name = shopName.value.trim();
    if(companyName) params.company_name = companyName.value.trim();
    ajaxPost('/user/profile', params, function(res){
        toast(res.msg);
        if(res.code == 1) setTimeout(function(){ location.href='/user/center'; }, 600);
    });
}
</script>

</body>
</html>

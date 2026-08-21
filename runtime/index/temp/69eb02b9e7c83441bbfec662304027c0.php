<?php /*a:2:{s:49:"D:\work\08\17_3\app\index\view\user\register.html";i:1787133987;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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


<div class="page <?php if(!empty($hide_header)): ?>no-hd<?php endif; if(!empty($hide_tabbar)): ?>no-tabbar<?php endif; if(!empty($page_class)): ?><?php echo htmlentities((string) $page_class); ?><?php endif; ?>">
    
<style>
    .page {
        padding-top: 0px;
    }
</style>
<div class="login-top">
    <span class="cloud cloud1"></span>
    <span class="cloud cloud2"></span>
    <span class="cloud cloud3"></span>
    <div class="tree l">
        <div class="leaf"></div>
        <div class="trunk"></div>
    </div>
    <div class="tree r">
        <div class="leaf"></div>
        <div class="trunk"></div>
    </div>
    <div class="person">
        <div class="p-head"></div>
        <div class="p-body"></div>
        <div class="p-phone"></div>
        <div class="p-leg"></div>
        <div class="p-leg r"></div>
    </div>
</div>
<div class="login-form">
    <h2>注册</h2>
    <div class="login-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><line x1="10.5" y1="18.5" x2="13.5" y2="18.5"/></svg>
        <input type="tel" id="mobile" maxlength="11" placeholder="请输入手机号码">
    </div>
    <div class="login-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2.5 4.5 5.5v6c0 4.6 3.2 8.4 7.5 10 4.3-1.6 7.5-5.4 7.5-10v-6L12 2.5z"/><polyline points="8.8 12 11 14.2 15.5 9.5"/></svg>
        <input type="text" id="invite" placeholder="邀请码（选填）">
    </div>
    <div class="login-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
        <input type="password" id="password" placeholder="设置密码（至少6位）">
    </div>
    <div class="login-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
        <input type="password" id="password2" placeholder="确认密码">
    </div>
    <button class="login-btn" onclick="doRegister()">立即注册</button>
    <div class="login-sub">
        已有账号？<a href="/user/login">去登录</a>
    </div>
    <div class="login-agree">
        注册即代表同意<a href="/user/agreement?type=protocol">《用户协议》</a>和<a href="/user/agreement?type=privacy">《隐私政策》</a>
    </div>
</div>

</div>




</div>
<script src="/static/m.js"></script>

<script>
function doRegister() {
    var mobile = document.getElementById('mobile').value.trim();
    var invite = document.getElementById('invite').value.trim();
    var pwd = document.getElementById('password').value.trim();
    var pwd2 = document.getElementById('password2').value.trim();
    if (!/^1\d{10}$/.test(mobile)) { toast('请输入正确的手机号'); return; }
    if (pwd.length < 6) { toast('密码至少6位'); return; }
    if (pwd !== pwd2) { toast('两次密码不一致'); return; }
    ajaxPost('/user/doRegister', {mobile: mobile, password: pwd, password2: pwd2, invite_code: invite}, function(res){
        if (res.code === 1) {
            toast(res.msg);
            setTimeout(function(){ location.href = res.url || '/user/center'; }, 600);
        } else {
            toast(res.msg);
        }
    });
}
document.addEventListener('keydown', function(e){ if(e.keyCode==13) doRegister(); });
</script>

</body>
</html>

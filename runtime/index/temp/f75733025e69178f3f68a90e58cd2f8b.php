<?php /*a:2:{s:53:"D:\phpstudy_pro\WWW\jp\app\index\view\user\login.html";i:1787133968;s:49:"D:\phpstudy_pro\WWW\jp\app\index\view\layout.html";i:1787189824;}*/ ?>
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


<div class="page <?php if(!empty($hide_header)): ?>no-hd<?php endif; if(!empty($hide_tabbar)): ?>no-tabbar<?php endif; if(!empty($page_class)): ?><?php echo htmlentities((string) $page_class); ?><?php endif; ?>">
    
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
    <h2>登录</h2>
    <div class="login-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><line x1="10.5" y1="18.5" x2="13.5" y2="18.5"/></svg>
        <input type="tel" id="mobile" maxlength="11" placeholder="请输入手机号码" value="">
    </div>
    <div class="login-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2.5 4.5 5.5v6c0 4.6 3.2 8.4 7.5 10 4.3-1.6 7.5-5.4 7.5-10v-6L12 2.5z"/><polyline points="8.8 12 11 14.2 15.5 9.5"/></svg>
        <input type="password" id="password" placeholder="请输入密码">
    </div>
    <button class="login-btn" onclick="doLogin()">立即登录</button>
    <div class="login-sub">
        还没有账号？<a href="/user/register">立即注册</a>
    </div>
    <div class="login-agree">
        登录即代表同意<a href="/user/agreement?type=protocol">《用户协议》</a>和<a href="/user/agreement?type=privacy">《隐私政策》</a>
    </div>
</div>
<style>
.page{padding-top:0!important;}
</style>

</div>




</div>
<script src="/static/m.js"></script>

<script>
function doLogin() {
    var mobile = document.getElementById('mobile').value.trim();
    var password = document.getElementById('password').value.trim();
    if (!mobile || !password) { toast('请输入手机号和密码'); return; }
    ajaxPost('/user/doLogin', {mobile: mobile, password: password}, function(res){
        if (res.code === 1) {
            toast(res.msg);
            setTimeout(function(){ location.href = res.url || '/user/center'; }, 600);
        } else {
            toast(res.msg);
        }
    });
}
document.addEventListener('keydown', function(e){ if(e.keyCode==13) doLogin(); });
</script>

</body>
</html>

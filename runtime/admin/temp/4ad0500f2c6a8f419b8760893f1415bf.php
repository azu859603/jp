<?php /*a:1:{s:60:"/www/wwwroot/2026/08/16/17_3/app/admin/view/login/index.html";i:1787106811;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台登录 - 竞拍商城</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Microsoft YaHei",Arial,sans-serif;background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);display:flex;align-items:center;justify-content:center;min-height:100vh}
.login-box{width:400px;max-width:92%;background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.35);padding:40px 36px}
.login-title{text-align:center;font-size:22px;font-weight:bold;color:#1e293b;margin-bottom:6px}
.login-sub{text-align:center;font-size:13px;color:#94a3b8;margin-bottom:30px}
.login-sub span{color:#ff7a00}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;color:#475569;margin-bottom:6px}
.form-control{width:100%;padding:11px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;outline:none;transition:border .2s}
.form-control:focus{border-color:#ff7a00;box-shadow:0 0 0 3px rgba(255,122,0,.1)}
.captcha-row{display:flex;gap:10px}
.captcha-row .form-control{flex:1}
.captcha-row img{height:42px;border-radius:8px;cursor:pointer;border:1px solid #e2e8f0}
.btn-login{width:100%;padding:12px;background:#ff7a00;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer;transition:background .2s;font-weight:bold}
.btn-login:hover{background:#e96c00}
.btn-login:disabled{opacity:.6;cursor:not-allowed}
.error-tip{color:#dc2626;font-size:13px;text-align:center;margin-bottom:12px;display:none}
.footer{text-align:center;margin-top:20px;font-size:12px;color:#cbd5e1}
</style>
</head>
<body>
<div class="login-box">
    <div class="login-title">🏆 竞拍商城</div>
    <div class="login-sub"><span>后台管理系统</span></div>
    <div class="error-tip" id="errorTip"></div>
    <form id="loginForm" onsubmit="return doLogin();">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" class="form-control" id="username" placeholder="请输入用户名" value="" autocomplete="off">
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" class="form-control" id="password" placeholder="请输入密码" value="">
        </div>
        <div class="form-group">
            <label>验证码</label>
            <div class="captcha-row">
                <input type="text" class="form-control" id="captcha" placeholder="请输入验证码" autocomplete="off">
                <img src="/admin1314/login/captcha" alt="验证码" onclick="this.src='/admin1314/login/captcha?'+Math.random()" title="点击刷新">
            </div>
        </div>
        <button type="submit" class="btn-login" id="loginBtn">登 录</button>
    </form>
</div>
<div class="footer">竞拍商城管理系统 v1.0</div>
<script>
function doLogin(){
    var btn = document.getElementById('loginBtn');
    var tip = document.getElementById('errorTip');
    tip.style.display = 'none';
    var data = {
        username: document.getElementById('username').value.trim(),
        password: document.getElementById('password').value,
        captcha: document.getElementById('captcha').value.trim()
    };
    if(!data.username || !data.password){ tip.innerHTML = '请输入用户名和密码'; tip.style.display='block'; return false; }
    if(!data.captcha){ tip.innerHTML = '请输入验证码'; tip.style.display='block'; return false; }
    btn.disabled = true;
    btn.textContent = '登录中...';
    var fd = new FormData();
    for(var k in data) fd.append(k, data[k]);
    fetch('/admin1314/login/doLogin', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res){
            btn.disabled = false;
            btn.textContent = '登 录';
            if(res.code == 1){
                location.href = res.url || '/admin1314/index/index';
            } else {
                tip.innerHTML = res.msg || '登录失败';
                tip.style.display = 'block';
                document.getElementById('captcha').value = '';
                document.querySelector('.captcha-row img').src = '/admin1314/login/captcha?' + Math.random();
            }
        })
        .catch(function(){
            btn.disabled = false;
            btn.textContent = '登 录';
            tip.innerHTML = '网络错误，请重试';
            tip.style.display = 'block';
        });
    return false;
}
</script>
</body>
</html>

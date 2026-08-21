<?php
echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>操作提示</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Microsoft YaHei",Arial,sans-serif;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:#fff;border-radius:12px;box-shadow:0 6px 24px rgba(0,0,0,.08);padding:50px 60px;text-align:center;max-width:480px;width:90%}
.icon{font-size:64px;margin-bottom:20px}
.msg{font-size:18px;color:#333;margin-bottom:30px;line-height:1.6;word-break:break-all}
.btn{display:inline-block;padding:10px 36px;border-radius:6px;text-decoration:none;font-size:15px;transition:opacity .2s}
.btn-primary{background:#ff7a00;color:#fff}
.btn-primary:hover{opacity:.85}
.btn-info{background:#e8eef5;color:#3b7cff}
.actions{display:flex;gap:12px;justify-content:center}
</style>
</head>
<body>
<div class="card">
<div class="icon">' . ($statusCode == 1 ? '✅' : '❌') . '</div>
<div class="msg">' . htmlspecialchars($msg) . '</div>
<div class="actions">';
if ($url) {
    echo '<a class="btn btn-primary" href="' . htmlspecialchars($url) . '">立即跳转</a>';
}
echo '<a class="btn btn-info" href="javascript:history.back(-1);">返回上一页</a>
</div>
</div>
<script>
setTimeout(function(){ location.href = "' . htmlspecialchars($url ?: 'javascript:history.back(-1);') . '"; }, ' . ($wait ? $wait * 1000 : 3000) . ');
</script>
</body>
</html>';

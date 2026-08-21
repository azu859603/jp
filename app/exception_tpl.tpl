<?php
/** 自定义异常页面（不暴露框架信息） */
if (!isset($isDebug)) {
    $isDebug = false;
    try {
        if (class_exists('\think\facade\App')) {
            $isDebug = (bool)\think\facade\App::isDebug();
        }
    } catch (\Throwable $e) {
        $isDebug = false;
    }
}
$code = isset($code) ? (int)$code : 500;

if ($code === 404) {
    $pageTitle = '页面不存在';
    $pageDesc  = '您访问的页面可能已被删除、更名或暂时不可用';
    $icon      = 'search';
} elseif ($code === 403) {
    $pageTitle = '访问被拒绝';
    $pageDesc  = '您没有权限访问该页面，如有疑问请联系平台客服';
    $icon      = 'lock';
} else {
    $pageTitle = '系统开小差了';
    $pageDesc  = '服务器繁忙或出现异常，请稍后刷新重试';
    $icon      = 'gear';
}
$detail = ($isDebug && !empty($message)) ? htmlspecialchars((string)$message) : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?php echo $pageTitle; ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Microsoft YaHei","PingFang SC",Arial,sans-serif;background:linear-gradient(160deg,#fff7f0 0%,#fdf2f2 50%,#f7f7fb 100%);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:#fff;border-radius:20px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:56px 48px 44px;text-align:center;max-width:460px;width:100%;position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:-60px;right:-60px;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(255,122,0,.08),transparent 70%)}
.ico{width:96px;height:96px;margin:0 auto 26px;border-radius:50%;background:linear-gradient(150deg,#ffb066,#ff7a00);display:flex;align-items:center;justify-content:center;box-shadow:0 10px 24px rgba(255,122,0,.28)}
.ico svg{width:46px;height:46px;stroke:#fff;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
h1{font-size:24px;font-weight:700;color:#222;letter-spacing:1px}
.desc{font-size:14px;color:#8a8f99;margin-top:12px;line-height:1.7}
.code-tag{display:inline-block;margin-top:18px;padding:4px 14px;border-radius:20px;background:#fff3e8;color:#ff7a00;font-size:12px;font-weight:600;letter-spacing:1px}
.actions{display:flex;gap:12px;justify-content:center;margin-top:34px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:11px 30px;border-radius:24px;text-decoration:none;font-size:14px;transition:all .2s;border:1px solid transparent}
.btn-primary{background:linear-gradient(135deg,#ff8a2a,#ff7a00);color:#fff;box-shadow:0 6px 16px rgba(255,122,0,.32)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(255,122,0,.4)}
.btn-ghost{background:#fff;color:#666;border-color:#e5e7eb}
.btn-ghost:hover{color:#ff7a00;border-color:#ffb066}
.detail{margin-top:22px;padding-top:16px;border-top:1px dashed #eee;font-size:12px;color:#c0c4cc;word-break:break-all;line-height:1.8}
.footer{margin-top:26px;font-size:12px;color:#c8ccd4}
</style>
</head>
<body>
<div class="card">
    <div class="ico">
        <?php if ($icon === 'search'): ?>
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        <?php elseif ($icon === 'lock'): ?>
        <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        <?php else: ?>
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        <?php endif; ?>
    </div>
    <h1><?php echo $pageTitle; ?></h1>
    <div class="desc"><?php echo $pageDesc; ?></div>
    <div class="code-tag">ERROR <?php echo $code; ?></div>
    <div class="actions">
        <a class="btn btn-primary" href="/">返回首页</a>
        <a class="btn btn-ghost" href="javascript:history.back(-1);">返回上一页</a>
    </div>
    <?php if ($detail !== ''): ?>
    <div class="detail"><?php echo $detail; ?></div>
    <?php endif; ?>
    <div class="footer">© <?php echo date('Y'); ?> 本站保留所有权利</div>
</div>
</body>
</html>

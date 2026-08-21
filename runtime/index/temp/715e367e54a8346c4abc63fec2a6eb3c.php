<?php /*a:2:{s:53:"D:\work\08\17_3\app\index\view\user\address_edit.html";i:1787016136;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="form-card" style="margin-top:12px;">
    <div class="form-row">
        <span class="fl">收货姓名</span>
        <div class="fr">
            <input type="text" id="name" value="<?php echo htmlentities((string) (isset($addr['name']) && ($addr['name'] !== '')?$addr['name']:'')); ?>" placeholder="请输入姓名" maxlength="20">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">手机号码</span>
        <div class="fr">
            <input type="tel" id="mobile" value="<?php echo htmlentities((string) (isset($addr['mobile']) && ($addr['mobile'] !== '')?$addr['mobile']:'')); ?>" placeholder="请输入手机号" maxlength="11">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">所在地区</span>
        <div class="fr">
            <input type="text" id="region" value="<?php echo htmlentities((string) (isset($addr['province']) && ($addr['province'] !== '')?$addr['province']:'')); ?> <?php echo htmlentities((string) (isset($addr['city']) && ($addr['city'] !== '')?$addr['city']:'')); ?> <?php echo htmlentities((string) (isset($addr['district']) && ($addr['district'] !== '')?$addr['district']:'')); ?>" placeholder="请填写所在地区，如：广东省 广州市 天河区" maxlength="60">
        </div>
    </div>
    <div class="form-row">
        <span class="fl">详细地址</span>
        <div class="fr">
            <input type="text" id="address" value="<?php echo htmlentities((string) (isset($addr['address']) && ($addr['address'] !== '')?$addr['address']:'')); ?>" placeholder="请填写详细地址">
        </div>
    </div>
</div>
<div class="form-row" style="background:#fff;margin:12px 14px 0;border-radius:10px;height:auto;padding:12px 14px;">
    <span class="fl" style="width:auto;margin-right:10px;">设为默认地址</span>
    <div class="fr" style="justify-content:flex-end;">
        <input type="checkbox" id="is_default" <?php if(!empty($addr) && $addr['is_default']==1): ?>checked<?php endif; ?> style="width:20px;height:20px;">
    </div>
</div>
<div class="form-submit">
    <button class="btn btn-red" onclick="saveAddr()">保存</button>
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
function saveAddr() {
    var name = document.getElementById('name').value.trim();
    var mobile = document.getElementById('mobile').value.trim();
    var region = document.getElementById('region').value.trim();
    var address = document.getElementById('address').value.trim();
    var isDefault = document.getElementById('is_default').checked ? 1 : 0;
    if (!name || !mobile || !address) { toast('请填写完整收货信息'); return; }
    if (!/^1\d{10}$/.test(mobile)) { toast('手机号格式不正确'); return; }
    var parts = region.split(/\s+/);
    var data = {
        id: '<?php echo htmlentities((string) (isset($addr['id']) && ($addr['id'] !== '')?$addr['id']:0)); ?>',
        name: name,
        mobile: mobile,
        province: parts[0] || '',
        city: parts[1] || '',
        district: parts[2] || '',
        address: address,
        is_default: isDefault
    };
    ajaxPost('/user/address_edit', data, function(res){
        toast(res.msg);
        if (res.code === 1) setTimeout(function(){ location.href = '/user/address'; }, 600);
    });
}
</script>

</body>
</html>

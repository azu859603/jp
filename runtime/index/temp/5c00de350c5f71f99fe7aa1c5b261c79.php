<?php /*a:2:{s:45:"D:\work\08\17_3\app\index\view\user\auth.html";i:1787102984;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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
    

<!-- 实名认证（白底头卡片，无顶部渐变状态卡） -->
<div class="apply-card">
   
    <div class="apply-body">
        <?php if($user['auth_status']==2): ?>
        <!-- 已通过状态 -->
        <div class="ap-st">
            <div class="st-ico">✅</div>
            <div class="st-t text-green">已通过</div>
            <div class="st-d">认证姓名：<?php echo htmlentities((string) $user['real_name']); ?><br>身份证号：<?php if(!empty($user['id_card'])): ?><?php echo htmlentities((string) substr($user['id_card'],0,4)); ?>**********<?php echo htmlentities((string) substr($user['id_card'],-4,4)); ?><?php endif; if(!empty($user['auth_time'])): ?><br>认证时间：<?php echo htmlentities((string) date('Y-m-d',!is_numeric($user['auth_time'])? strtotime($user['auth_time']) : $user['auth_time'])); ?><?php endif; ?></div>
        </div>
        <div class="ap-submit">
            <a class="btn btn-red" href="/seller/apply">申请成为卖家</a>
        </div>
        <?php else: ?>
        <div class="ap-form">
            <div class="form-row">
                <span class="fl">真实姓名</span>
                <div class="fr"><input type="text" id="real_name" placeholder="请输入与身份证一致的姓名" value="<?php echo htmlentities((string) (isset($user['real_name']) && ($user['real_name'] !== '')?$user['real_name']:'')); ?>"></div>
            </div>
            <div class="form-row">
                <span class="fl">身份证号</span>
                <div class="fr"><input type="text" id="id_card" maxlength="18" placeholder="请输入18位身份证号" value="<?php echo htmlentities((string) (isset($user['id_card']) && ($user['id_card'] !== '')?$user['id_card']:'')); ?>"></div>
            </div>
            <div class="form-row area">
                <span class="fl">身份证正面</span>
                <div class="fr">
                    <div class="id-upload" style="width:100%;">
                        <div id="frontBox" class="upload-item" onclick="document.getElementById('frontFile').click()">
                            <?php if(!empty($user['id_card_front'])): ?><img src="<?php echo htmlentities((string) $user['id_card_front']); ?>"><?php else: ?><div class="u-plus">＋</div><div class="u-txt">上传人像面</div><?php endif; ?>
                        </div>
                        <input type="file" id="frontFile" accept="image/*" style="display:none;" onchange="uploadIdCard(this, 'front')">
                    </div>
                </div>
            </div>
            <div class="form-row area">
                <span class="fl">身份证反面</span>
                <div class="fr">
                    <div class="id-upload" style="width:100%;">
                        <div id="backBox" class="upload-item" onclick="document.getElementById('backFile').click()">
                            <?php if(!empty($user['id_card_back'])): ?><img src="<?php echo htmlentities((string) $user['id_card_back']); ?>"><?php else: ?><div class="u-plus">＋</div><div class="u-txt">上传国徽面</div><?php endif; ?>
                        </div>
                        <input type="file" id="backFile" accept="image/*" style="display:none;" onchange="uploadIdCard(this, 'back')">
                    </div>
                </div>
            </div>
        </div>

        <!-- 状态提示（显示在提交按钮上方） -->
        <?php if($user['auth_status']==1): ?>
        <div class="ap-status orange">⏳ 审核中：资料已提交，请耐心等待审核（1-3 个工作日）</div>
        <?php elseif($user['auth_status']==3): ?>
        <div class="ap-status red">❌ 未通过：<?php echo htmlentities((string) (isset($user['auth_reason']) && ($user['auth_reason'] !== '')?$user['auth_reason']:'请联系管理员')); ?>，请修改资料后重新提交</div>
        <?php endif; ?>

        <div class="ap-submit">
            <button class="btn btn-red" onclick="doAuth()">提交认证</button>
        </div>
        <?php endif; ?>
    </div>
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
var frontUrl = '<?php if(!empty($user['id_card_front'])): ?><?php echo htmlentities((string) $user['id_card_front']); ?><?php endif; ?>';
var backUrl = '<?php if(!empty($user['id_card_back'])): ?><?php echo htmlentities((string) $user['id_card_back']); ?><?php endif; ?>';

function uploadIdCard(input, side) {
    var file = input.files[0];
    if (!file) return;
    var formData = new FormData();
    formData.append('file', file);
    fetch('/upload/image', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.code === 1) {
            var box = document.getElementById(side === 'front' ? 'frontBox' : 'backBox');
            box.innerHTML = '<img src="' + res.url + '">';
            if (side === 'front') { frontUrl = res.url; } else { backUrl = res.url; }
        } else {
            toast(res.msg);
        }
    }).catch(function(){ toast('上传失败，请重试'); });
    input.value = '';
}

function doAuth() {
    var realName = document.getElementById('real_name').value.trim();
    var idCard = document.getElementById('id_card').value.trim();
    if (!realName) { toast('请输入真实姓名'); return; }
    if (!/^\d{17}[\dXx]$/.test(idCard)) { toast('请输入正确的18位身份证号'); return; }
    if (!frontUrl || !backUrl) { toast('请上传身份证正反面照片'); return; }
    ajaxPost('/user/auth', {
        real_name: realName,
        id_card: idCard,
        id_card_front: frontUrl,
        id_card_back: backUrl
    }, function(res){
        if (res.code === 1) {
            toast(res.msg);
            setTimeout(function(){ location.reload(); }, 600);
        } else {
            toast(res.msg);
        }
    });
}
</script>

</body>
</html>

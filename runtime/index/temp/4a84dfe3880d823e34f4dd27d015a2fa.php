<?php /*a:2:{s:52:"D:\work\08\17_3\app\index\view\seller\goods_add.html";i:1787198393;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="form-card">
    <div class="form-row">
        <span class="fl">拍品名称</span>
        <div class="fr"><input type="text" id="title" placeholder="请输入拍品名称"></div>
    </div>
    <div class="form-row">
        <span class="fl">所属分类</span>
        <div class="fr">
            <select id="category_id">
                <option value="">请选择分类</option>
                <?php foreach($categories as $c): ?>
                <option value="<?php echo htmlentities((string) $c['id']); ?>"><?php echo htmlentities((string) $c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row">
        <span class="fl">起拍价格</span>
        <div class="fr"><input type="number" id="start_price" placeholder="请输入起拍价"></div>
    </div>
    <div class="form-row">
        <span class="fl">加价幅度</span>
        <div class="fr"><input type="number" id="raise_price" placeholder="最低加价额度" value="100"></div>
    </div>
    <div class="form-row">
        <span class="fl">保证金</span>
        <div class="fr"><input type="number" id="deposit" placeholder="买家出价保证金（选填）"></div>
    </div>
    <div class="form-row">
        <span class="fl">截拍时间</span>
        <div class="fr"><input type="datetime-local" id="end_time"></div>
    </div>
    <div class="form-row">
        <span class="fl">快捷选择</span>
        <div class="fr" style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="end-quick" href="javascript:;" onclick="quickEnd(1)">1天</a>
            <a class="end-quick" href="javascript:;" onclick="quickEnd(3)">3天</a>
            <a class="end-quick" href="javascript:;" onclick="quickEnd(10)">10天</a>
            <a class="end-quick" href="javascript:;" onclick="quickEnd(30)">1月</a>
        </div>
    </div>
    <div class="form-row area">
        <span class="fl">描述简介</span>
        <div class="fr"><textarea id="content" placeholder="请描述拍品详情" rows="4"></textarea></div>
    </div>
</div>

<div class="form-card">
    <div class="form-tip" style="padding:10px 14px;">可选设置</div>
    <div class="form-row">
        <span class="fl">是否精选</span>
        <div class="fr">
            <label style="display:flex;align-items:center;gap:6px;"><input type="radio" name="is_featured" value="0" checked> 否</label>
            <label style="display:flex;align-items:center;gap:6px;margin-left:16px;"><input type="radio" name="is_featured" value="1"> 是</label>
        </div>
    </div>
    <div class="form-row">
        <span class="fl">是否包邮</span>
        <div class="fr">
            <label style="display:flex;align-items:center;gap:6px;"><input type="radio" name="is_free_shipping" value="0" checked> 否</label>
            <label style="display:flex;align-items:center;gap:6px;margin-left:16px;"><input type="radio" name="is_free_shipping" value="1"> 是</label>
        </div>
    </div>
</div>

<div class="form-card">
    <div class="form-tip" style="padding:10px 14px;">上传图片 <span style="color:#E4393C;">（至少4张，不同角度拍摄）</span></div>
    <div class="upload-grid" id="uploadGrid">
        <div class="upload-add" id="uploadBtn" onclick="uploadImage()">
            <span>+</span>
            <span class="t">上传</span>
        </div>
    </div>
    <div class="form-tip">请提供至少4张不同角度的商品照片，便于鉴定师准确鉴定拍品</div>
</div>

<div class="form-tip" style="padding:0 16px;">
    <label style="display:flex;align-items:center;gap:6px;">
        <input type="checkbox" id="agree"> 我已阅读并同意<a href="javascript:void(0)" class="agree-link" onclick="openAgreement()">《发布协议》</a>
    </label>
</div>

<style>
.end-quick{display:inline-block;padding:4px 14px;border:1px solid #F5C6C7;border-radius:14px;font-size:12px;color:#E4393C;background:#FFF5F5;text-decoration:none;transition:all .15s ease;}
.end-quick:active{border-color:#E4393C;background:#E4393C;color:#fff;transform:scale(.92);}
</style>
<div class="form-submit">
    <button class="btn btn-red" onclick="doPublish()">提交鉴定</button>
</div>

<!-- 发布协议弹窗 -->
<div class="mask" id="agreeMask" onclick="closeAgreement()"></div>
<div class="sheet" id="agreeSheet">
    <div class="s-title">发布协议 <span class="s-close" onclick="closeAgreement()">×</span></div>
    <div class="agree-body">
        <?php if(!empty($publish_protocol)): ?>
        <?php echo $publish_protocol; else: ?>
        <div class="agree-empty">内容暂未发布，请稍后再查看</div>
        <?php endif; ?>
    </div>
    <div style="padding:0 20px;"><button class="btn btn-red" style="width:100%;" onclick="closeAgreement()">我知道了</button></div>
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
var uploadedImages = [];

// 快捷选择截拍时间：当前时间 + 所选时长
function quickEnd(days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 24 * 3600 * 1000);
    function pad(n){ return n < 10 ? '0' + n : n; }
    var v = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    document.getElementById('end_time').value = v;
    toast('截拍时间已设为' + (days >= 30 ? '1个月' : days + '天') + '后');
}

// 发布协议弹窗
function openAgreement() {
    document.getElementById('agreeMask').classList.add('show');
    document.getElementById('agreeSheet').classList.add('show');
}
function closeAgreement() {
    document.getElementById('agreeMask').classList.remove('show');
    document.getElementById('agreeSheet').classList.remove('show');
}

function uploadImage() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        var file = e.target.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('file', file);
        fetch('/upload/image', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        }).then(function(r){ return r.json(); }).then(function(res){
            if (res.code === 1) {
                uploadedImages.push(res.url);
                var div = document.createElement('div');
                div.className = 'upload-item';
                div.innerHTML = '<img src="'+res.url+'"><span class="del" onclick="this.parentElement.remove()">×</span>';
                document.getElementById('uploadGrid').insertBefore(div, document.getElementById('uploadBtn'));
            } else {
                toast(res.msg || '上传失败');
            }
        }).catch(function(){ toast('上传失败'); });
    };
    input.click();
}

function doPublish() {
    var title = document.getElementById('title').value.trim();
    var categoryId = document.getElementById('category_id').value;
    var startPrice = document.getElementById('start_price').value.trim();
    var raisePrice = document.getElementById('raise_price').value.trim();
    var endTime = document.getElementById('end_time').value;
    var agree = document.getElementById('agree').checked;
    if (!title) { toast('请输入拍品名称'); return; }
    if (!categoryId) { toast('请选择分类'); return; }
    if (!startPrice || parseFloat(startPrice) <= 0) { toast('请输入起拍价'); return; }
    if (!raisePrice || parseFloat(raisePrice) <= 0) { toast('请输入加价幅度'); return; }
    if (!endTime) { toast('请选择截拍时间'); return; }
    if (uploadedImages.length < 4) { toast('请至少上传4张不同角度的商品照片'); return; }
    if (!agree) { toast('请同意发布协议'); return; }

    ajaxPost('/seller/goods_add', {
        title: title,
        category_id: categoryId,
        start_price: startPrice,
        raise_price: raisePrice,
        deposit: document.getElementById('deposit').value.trim(),
        end_time: endTime,
        content: document.getElementById('content').value.trim(),
        is_featured: document.querySelector('input[name=is_featured]:checked').value,
        is_free_shipping: document.querySelector('input[name=is_free_shipping]:checked').value,
        images: uploadedImages
    }, function(res){
        if (res.code === 1) {
            toast('发布成功');
            setTimeout(function(){ location.href = '/seller/goods_list'; }, 600);
        } else {
            toast(res.msg);
        }
    });
}
</script>

</body>
</html>

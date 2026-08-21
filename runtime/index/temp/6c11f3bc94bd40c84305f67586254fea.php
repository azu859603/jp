<?php /*a:2:{s:65:"/www/wwwroot/2026/08/16/17_3/app/index/view/user/pay_account.html";i:1787183214;s:55:"/www/wwwroot/2026/08/16/17_3/app/index/view/layout.html";i:1787189824;}*/ ?>
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
    
<div class="pa-page">
    <!-- 提现方式选择 -->
    <div class="pa-types">
        <div class="pa-type <?php if(empty($accounts['1'])): ?>un<?php endif; ?>" data-type="1" onclick="selectType(1)">
            <div class="pa-ico" style="color:#1677ff;">支</div>
            <div class="pa-tname">支付宝</div>
            <div class="pa-state" id="paState1"><?php if(!empty($accounts['1'])): ?>已绑定<?php else: ?>未绑定<?php endif; ?></div>
        </div>
        <div class="pa-type <?php if(empty($accounts['2'])): ?>un<?php endif; ?>" data-type="2" onclick="selectType(2)">
            <div class="pa-ico" style="color:#07c160;">微</div>
            <div class="pa-tname">微信</div>
            <div class="pa-state" id="paState2"><?php if(!empty($accounts['2'])): ?>已绑定<?php else: ?>未绑定<?php endif; ?></div>
        </div>
        <div class="pa-type <?php if(empty($accounts['3'])): ?>un<?php endif; ?>" data-type="3" onclick="selectType(3)">
            <div class="pa-ico" style="color:#c76b00;">银</div>
            <div class="pa-tname">银行卡</div>
            <div class="pa-state" id="paState3"><?php if(!empty($accounts['3'])): ?>已绑定<?php else: ?>未绑定<?php endif; ?></div>
        </div>
        <div class="pa-type <?php if(empty($accounts['4'])): ?>un<?php endif; ?>" data-type="4" onclick="selectType(4)">
            <div class="pa-ico" style="color:#7b5ee6;">₮</div>
            <div class="pa-tname">虚拟货币</div>
            <div class="pa-state" id="paState4"><?php if(!empty($accounts['4'])): ?>已绑定<?php else: ?>未绑定<?php endif; ?></div>
        </div>
    </div>

    <!-- 绑定表单卡片 -->
    <div class="pa-card" id="paCard">
        <div class="pa-card-title" id="paCardTitle">绑定支付宝</div>

        <div class="pa-row" id="row_real_name">
            <span class="pa-lbl">姓名</span>
            <input type="text" id="real_name" placeholder="请输入真实姓名">
        </div>
        <div class="pa-row" id="row_account">
            <span class="pa-lbl" id="lbl_account">账号</span>
            <input type="text" id="account_input" placeholder="请输入收款账号">
        </div>
        <div class="pa-row" id="row_bank">
            <span class="pa-lbl" id="lbl_bank">银行名称</span>
            <input type="text" id="bank_name" placeholder="如 中国工商银行">
        </div>
        <div class="pa-qr-block" id="row_qr">
            <div class="pa-qr-title">收款码<span class="pa-qr-star">*</span></div>
            <div class="pa-qr-area">
                <div class="pa-qr-add" id="qrAdd" onclick="uploadQr()">
                    <div class="pa-qr-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h3"/><path d="M17 4h3v3"/><path d="M20 17v3h-3"/><path d="M7 20H4v-3"/><path d="M9 8h1.5v1.5H9z" fill="currentColor" stroke="none"/><path d="M13.5 8H15v1.5h-1.5z" fill="currentColor" stroke="none"/><path d="M9 12.5h1.5V14H9z" fill="currentColor" stroke="none"/><path d="M13.5 12.5H15V14h-1.5z" fill="currentColor" stroke="none"/><path d="M9 16.5h1.5V18H9z" fill="currentColor" stroke="none"/><path d="M13.5 16.5H15V18h-1.5z" fill="currentColor" stroke="none"/></svg>
                    </div>
                    <div class="pa-qr-t1">点击上传收款码</div>
                    <div class="pa-qr-t2">支持 jpg / png / gif，建议清晰完整</div>
                </div>
                <div class="pa-qr-prev" id="qrPrev" style="display:none;" onclick="uploadQr()">
                    <img id="qrImg" src="">
                    <div class="pa-qr-mask">点击更换</div>
                    <span class="pa-qr-del" onclick="event.stopPropagation();removeQr()">×</span>
                </div>
            </div>
            <input type="hidden" id="qr_code" value="">
            <div class="pa-qr-tip">收款码仅用于平台打款，请上传清晰的收款码图片</div>
        </div>

        <div class="pa-save" onclick="saveAccount()">保存绑定</div>
        <div class="pa-tip" style="text-align:center;margin-top:8px;">绑定信息支持随时修改，提现时将以最新绑定信息打款</div>
    </div>
</div>

<style>
.pa-page{padding:14px;}
.pa-types{display:flex;gap:10px;margin-bottom:14px;}
.pa-type{flex:1;background:#fff;border:1px solid #eee;border-radius:10px;padding:14px 6px;text-align:center;cursor:pointer;}
.pa-type.on{border-color:#AC3030;background:#fff7f7;}
.pa-type.un{border-style:dashed;}
.pa-ico{width:40px;height:40px;line-height:40px;border-radius:50%;background:#f5f5f5;font-size:16px;font-weight:600;margin:0 auto 8px;}
.pa-tname{font-size:14px;color:#333;font-weight:600;}
.pa-state{font-size:11px;color:#999;margin-top:4px;}
.pa-type.on .pa-state{color:#AC3030;}
.pa-card{background:#fff;border-radius:10px;padding:14px;margin-bottom:14px;}
.pa-card-title{font-size:15px;font-weight:600;color:#333;margin-bottom:12px;}
.pa-row{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #f5f5f5;}
.pa-lbl{width:72px;font-size:14px;color:#333;flex-shrink:0;}
.pa-row input{flex:1;border:none;outline:none;font-size:14px;text-align:right;color:#333;}
.pa-row input::placeholder{color:#bbb;}
.pa-qr-block{padding:12px 0 2px;}
.pa-qr-title{font-size:14px;color:#333;font-weight:600;margin-bottom:10px;}
.pa-qr-star{color:#AC3030;margin-left:2px;}
.pa-qr-area{position:relative;}
.pa-qr-add{width:100%;height:130px;border:1.5px dashed #d9d9d9;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;background:#fafafa;cursor:pointer;transition:border-color .2s,background .2s;}
.pa-qr-add:active{border-color:#AC3030;background:#fff7f7;}
.pa-qr-ico{width:42px;height:42px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;color:#AC3030;box-shadow:0 2px 6px rgba(0,0,0,.07);}
.pa-qr-ico svg{width:20px;height:20px;}
.pa-qr-t1{font-size:13px;color:#555;font-weight:500;}
.pa-qr-t2{font-size:11px;color:#bbb;}
.pa-qr-prev{position:relative;border-radius:12px;overflow:hidden;cursor:pointer;box-shadow:0 1px 6px rgba(0,0,0,.08);}
.pa-qr-prev img{width:100%;max-height:180px;object-fit:contain;background:#fafafa;display:block;}
.pa-qr-mask{position:absolute;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);color:#fff;font-size:12px;text-align:center;padding:6px 0;letter-spacing:2px;}
.pa-qr-del{position:absolute;top:8px;right:8px;width:22px;height:22px;line-height:20px;text-align:center;background:rgba(0,0,0,.55);color:#fff;border-radius:50%;font-size:14px;z-index:2;}
.pa-qr-tip{font-size:11px;color:#999;margin-top:8px;}
.pa-tip{font-size:11px;color:#999;margin-top:6px;}
.pa-save{margin-top:14px;background:#AC3030;color:#fff;text-align:center;padding:12px;border-radius:8px;font-size:15px;font-weight:600;}
</style>

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
var accounts = <?php echo $pa_json; ?>;
var curType = 1;
var typeName = {1:'支付宝', 2:'微信', 3:'银行卡', 4:'虚拟货币'};

function selectType(t) {
    curType = t;
    var els = document.querySelectorAll('.pa-type');
    for (var i = 0; i < els.length; i++) { els[i].classList.remove('on'); }
    document.querySelector('.pa-type[data-type="' + t + '"]').classList.add('on');
    document.getElementById('paCardTitle').textContent = '绑定' + typeName[t];
    var rowName = document.getElementById('row_real_name');
    var rowBank = document.getElementById('row_bank');
    var rowQr = document.getElementById('row_qr');
    var lblAccount = document.getElementById('lbl_account');
    var lblBank = document.getElementById('lbl_bank');
    var accountInput = document.getElementById('account_input');
    var bankInput = document.getElementById('bank_name');
    if (t === 3) {
        rowName.style.display = 'flex';
        rowBank.style.display = 'flex';
        rowQr.style.display = 'none';
        lblAccount.textContent = '账号';
        accountInput.placeholder = '请输入收款账号';
        lblBank.textContent = '银行名称';
        bankInput.placeholder = '如 中国工商银行';
    } else if (t === 4) {
        rowName.style.display = 'none';
        rowBank.style.display = 'flex';
        rowQr.style.display = 'none';
        lblAccount.textContent = '钱包地址';
        accountInput.placeholder = '请输入USDT钱包地址';
        lblBank.textContent = '网络（链）';
        bankInput.placeholder = '如 TRC20 / ERC20';
    } else {
        rowName.style.display = 'flex';
        rowBank.style.display = 'none';
        rowQr.style.display = 'flex';
        lblAccount.textContent = '账号';
        accountInput.placeholder = '请输入收款账号';
    }
    fillForm(t);
}

function fillForm(t) {
    var a = accounts[t];
    document.getElementById('real_name').value = a ? a.real_name : '';
    document.getElementById('account_input').value = a ? a.account : '';
    document.getElementById('bank_name').value = a ? a.bank_name : '';
    document.getElementById('qr_code').value = a ? a.qr_code : '';
    if (a && a.qr_code) {
        document.getElementById('qrImg').src = a.qr_code;
        document.getElementById('qrPrev').style.display = '';
        document.getElementById('qrAdd').style.display = 'none';
    } else {
        document.getElementById('qrPrev').style.display = 'none';
        document.getElementById('qrAdd').style.display = '';
    }
}

function uploadQr() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        var file = e.target.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('file', file);
        fetch('/upload/image', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd})
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.code === 1) {
                    document.getElementById('qrImg').src = res.url;
                    document.getElementById('qrPrev').style.display = '';
                    document.getElementById('qrAdd').style.display = 'none';
                    document.getElementById('qr_code').value = res.url;
                } else {
                    toast(res.msg || '上传失败');
                }
            })
            .catch(function(){ toast('网络错误，请重试'); });
    };
    input.click();
}

function removeQr() {
    document.getElementById('qr_code').value = '';
    document.getElementById('qrPrev').style.display = 'none';
    document.getElementById('qrAdd').style.display = '';
}

function saveAccount() {
    var data = {
        type: curType,
        real_name: document.getElementById('real_name').value.trim(),
        account: document.getElementById('account_input').value.trim(),
        bank_name: document.getElementById('bank_name').value.trim(),
        qr_code: document.getElementById('qr_code').value
    };
    ajaxPost('/user/pay_account', data, function(res){
        toast(res.msg);
        if (res.code === 1) {
            setTimeout(function(){ location.reload(); }, 600);
        }
    });
}

selectType(1);
</script>

</body>
</html>

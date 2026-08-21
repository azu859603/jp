<?php /*a:2:{s:53:"D:\work\08\17_3\app\index\view\seller\goods_list.html";i:1787183434;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787189824;}*/ ?>
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
    
<div class="g-tabs">
    <a href="/seller/goods_list" class="<?php if($status==''): ?>on<?php endif; ?>">全部</a>
    <a href="/seller/goods_list?status=1" class="<?php if($status==1): ?>on<?php endif; ?>">竞拍中</a>
    <a href="/seller/goods_list?status=0" class="<?php if($status==0): ?>on<?php endif; ?>">审核中</a>
    <a href="/seller/goods_list?status=2" class="<?php if($status==2): ?>on<?php endif; ?>">已成交</a>
    <a href="/seller/goods_list?status=3" class="<?php if($status==3): ?>on<?php endif; ?>">已流拍</a>
</div>
<?php if(empty($list)): ?>
<div class="empty"><div class="e-ico">📦</div><div>暂无拍品</div><a class="e-btn" href="/seller/goods_add">发布拍品</a></div>
<?php else: foreach($list as $g): ?>
<div class="gmanage">
    <div class="g-head">
        <span>ID: <?php echo htmlentities((string) $g['id']); ?></span>
        <span class="st s<?php echo htmlentities((string) $g['status']); ?>"><?php if($g['status']==0): ?>待审核<?php elseif($g['status']==1): ?>竞拍中<?php elseif($g['status']==2): ?>已成交<?php elseif($g['status']==3): ?>已流拍<?php elseif($g['status']==4): ?>已下架<?php elseif($g['status']==5): ?>已拒绝<?php else: ?>已结束<?php endif; ?></span>
    </div>
    <div class="g-body">
        <div class="thumb">
            <?php if(!empty($g['cover'])): ?>
            <img src="<?php echo htmlentities((string) $g['cover']); ?>" alt="">
            <?php else: ?>
            <span class="noimg">🏺</span>
            <?php endif; ?>
        </div>
        <div class="ginfo">
            <div class="gtitle"><?php echo htmlentities((string) $g['title']); ?></div>
            <div class="gprice">¥<?php echo htmlentities((string) number_format($g['start_price'],2)); ?></div>
            <div class="gmeta"><?php echo htmlentities((string) $g['view_count']); ?>次浏览 · <?php echo htmlentities((string) $g['bid_count']); ?>次出价</div>
        </div>
    </div>
    <div class="g-foot">
        <?php if($g['status']==4): ?>
        <a class="btn-sm solid-red" href="javascript:void(0)" onclick="putOnSale(<?php echo htmlentities((string) $g['id']); ?>)">上架</a>
        <?php elseif($g['status']==1): ?>
        <a class="btn-sm gray" href="javascript:void(0)" onclick="takeOff(<?php echo htmlentities((string) $g['id']); ?>)">下架</a>
        <?php elseif($g['status']==3): ?>
        <a class="btn-sm solid-red" href="javascript:void(0)" onclick="openResale(<?php echo htmlentities((string) $g['id']); ?>)">重新上架</a>
        <?php elseif($g['status']==5): ?>
        <a class="btn-sm gray" href="javascript:void(0)" onclick="delGoods(<?php echo htmlentities((string) $g['id']); ?>)">删除</a>
        <?php endif; if($g['status']==5): ?><div class="g-reject">审核未通过：<?php echo htmlentities((string) (isset($g['refuse_reason']) && ($g['refuse_reason'] !== '')?$g['refuse_reason']:'未填写原因')); ?></div><?php endif; ?>
        <span style="font-size:12px;color:#bbb;line-height:28px;"><?php echo htmlentities((string) date('m-d H:i',!is_numeric($g['end_time'])? strtotime($g['end_time']) : $g['end_time'])); ?>结束</span>
    </div>
</div>
<?php endforeach; if($page*$limit < $total): ?>
<a class="load-more" href="/seller/goods_list?status=<?php echo htmlentities((string) $status); ?>&page=<?php echo htmlentities((string) $page+1); ?>">加载更多</a>
<?php else: ?>
<div class="no-more">—— 没有更多数据了 ——</div>
<?php endif; ?>
<?php endif; ?>

<!-- 重新上架弹层 -->
<div class="mask" id="resaleMask" onclick="closeResale()"></div>
<div class="sheet" id="resaleSheet">
    <div class="s-title">重新上架拍卖 <span class="s-close" onclick="closeResale()">×</span></div>
    <div class="s-sub">重新开拍将清空旧的出价记录</div>
    <div class="s-body">
        <div class="rs-tip">选择本次拍卖结束时间（精确到分钟）</div>
        <div class="rs-time">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 13.5"/></svg>
            <input type="datetime-local" id="resaleEnd">
        </div>
        <div class="rs-hint">确认后将立即开拍，旧出价记录将被清空</div>
        <a class="rs-btn" href="javascript:void(0)" onclick="doResale()">确认重新上架</a>
    </div>
</div>
<style>
.g-tabs{display:flex;background:#fff;border-bottom:1px solid #f0f0f0;padding:0 4px;}
.g-tabs a{flex:1;text-align:center;font-size:13px;color:#666;padding:12px 0 11px;position:relative;white-space:nowrap;}
.g-tabs a.on{color:#E4393C;font-weight:600;}
.g-tabs a.on::after{content:'';position:absolute;left:50%;transform:translateX(-50%);bottom:0;width:26px;height:3px;border-radius:2px;background:#E4393C;}
.rs-tip{font-size:12px;color:#999;margin-bottom:10px;}
.rs-time{display:flex;align-items:center;gap:8px;border:1px solid #E4393C;border-radius:8px;height:46px;padding:0 12px;margin-bottom:10px;background:#fffaf7;}
.rs-time svg{width:18px;height:18px;color:#E4393C;flex-shrink:0;}
.rs-time input{flex:1;height:44px;font-size:14px;color:#333;border:0;background:none;min-width:0;}
.rs-hint{font-size:11px;color:#bbb;margin-bottom:14px;}
.rs-btn{display:block;height:44px;line-height:44px;text-align:center;background:linear-gradient(135deg,#ff4d4f,#E4393C);color:#fff;border-radius:22px;font-size:15px;font-weight:600;box-shadow:0 4px 12px rgba(228,57,60,.3);}
.g-reject{font-size:12px;color:#E4393C;line-height:22px;}
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
function putOnSale(id) {
    ajaxPost('/seller/goods_status', {id: id, status: 1}, function(res){
        if(res.code==1){ toast('已上架'); location.reload(); } else { toast(res.msg); }
    });
}
function takeOff(id) {
    ajaxPost('/seller/goods_status', {id: id, status: 4}, function(res){
        if(res.code==1){ toast('已下架'); location.reload(); } else { toast(res.msg); }
    });
}
var resaleId = 0;
function delGoods(id) {
    if (!confirm('确定删除该拍品吗？删除后不可恢复')) return;
    ajaxPost('/seller/goods_delete', {id: id}, function(res){
        if(res.code==1){ toast('已删除'); location.reload(); } else { toast(res.msg); }
    });
}
function pad2(n) { return n < 10 ? '0' + n : n; }
function fmtDT(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()) + 'T' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
}
function openResale(id) {
    resaleId = id;
    var now = new Date();
    var def = new Date(now.getTime() + 86400000); // 默认明天此刻
    var min = new Date(now.getTime() + 600000); // 最早：10分钟后
    var inp = document.getElementById('resaleEnd');
    inp.value = fmtDT(def);
    inp.min = fmtDT(min);
    document.getElementById('resaleMask').classList.add('show');
    document.getElementById('resaleSheet').classList.add('show');
}
function closeResale() {
    document.getElementById('resaleMask').classList.remove('show');
    document.getElementById('resaleSheet').classList.remove('show');
}
function doResale() {
    if (!resaleId) return;
    var et = document.getElementById('resaleEnd').value;
    if (!et) { toast('请选择结束时间'); return; }
    if (new Date(et).getTime() <= Date.now() + 60000) { toast('结束时间需晚于当前时间'); return; }
    ajaxPost('/seller/goods_status', {id: resaleId, status: 1, end_time: et}, function(res){
        if(res.code==1){ toast('已重新上架'); location.reload(); } else { toast(res.msg); }
    });
}
</script>

</body>
</html>

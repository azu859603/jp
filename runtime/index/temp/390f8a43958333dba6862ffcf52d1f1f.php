<?php /*a:2:{s:47:"D:\work\08\17_3\app\index\view\chat\detail.html";i:1787105463;s:42:"D:\work\08\17_3\app\index\view\layout.html";i:1787008265;}*/ ?>
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
    
<div class="chat-page">
    <!-- 聊天头部（商品信息） -->
    <div class="ch-head">
        <a href="/goods/detail?id=<?php echo htmlentities((string) $goods['id']); ?>" class="ch-goods">
            <?php if(!empty($goods['cover'])): ?>
            <img src="<?php echo htmlentities((string) $goods['cover']); ?>" alt="<?php echo htmlentities((string) $goods['title']); ?>" class="ch-cover">
            <?php else: ?>
            <span class="ch-noimg">🏺</span>
            <?php endif; ?>
            <span class="ch-gname"><?php echo htmlentities((string) $goods['title']); ?></span>
            <span class="ch-arrow">›</span>
        </a>
    </div>

    <!-- 消息区 -->
    <div class="ch-body" id="chatBody">
        <?php foreach($messages as $m): ?>
        <div class="ch-msg <?php if($m['from_uid'] == $uid): ?>ch-mine<?php else: ?>ch-other<?php endif; ?>">
            <div class="ch-bubble"><?php echo htmlentities((string) $m['content']); ?></div>
            <div class="ch-time"><?php echo htmlentities((string) date('m-d H:i',!is_numeric($m['create_time'])? strtotime($m['create_time']) : $m['create_time'])); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 底部输入框 -->
    <div class="ch-foot">
        <input type="text" id="msgInput" class="ch-input" placeholder="输入消息..." maxlength="500" onkeydown="if(event.keyCode==13)sendMsg();">
        <a class="ch-send" href="javascript:sendMsg();">发送</a>
    </div>
</div>

<style>
.chat-page{display:flex;flex-direction:column;position:fixed;left:0;right:0;top:44px;bottom:0;background:#f5f5f5;}
.ch-head{padding:8px 14px;background:#fff;border-bottom:1px solid #eee;}
.ch-goods{display:flex;align-items:center;text-decoration:none;color:#333;font-size:13px;}
.ch-cover{width:36px;height:36px;border-radius:4px;object-fit:cover;margin-right:8px;flex-shrink:0;}
.ch-noimg{width:36px;height:36px;border-radius:4px;background:#f4f4f4;display:flex;align-items:center;justify-content:center;font-size:18px;margin-right:8px;flex-shrink:0;}
.ch-gname{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ch-arrow{color:#ccc;font-size:18px;margin-left:4px;}
.ch-body{flex:1;overflow-y:auto;padding:10px 14px;background:#f5f5f5;-webkit-overflow-scrolling:touch;}
.ch-msg{margin-bottom:12px;}
.ch-mine{text-align:right;}
.ch-other{text-align:left;}
.ch-bubble{display:inline-block;max-width:80%;padding:8px 12px;border-radius:8px;font-size:14px;line-height:1.5;word-break:break-all;text-align:left;}
.ch-mine .ch-bubble{background:#ffe066;color:#333;}
.ch-other .ch-bubble{background:#fff;color:#333;}
.ch-time{font-size:11px;color:#bbb;margin-top:4px;}
.ch-foot{display:flex;padding:8px 14px;background:#fff;border-top:1px solid #e5e5e5;}
.ch-input{flex:1;height:36px;border:1px solid #ddd;border-radius:18px;padding:0 14px;font-size:14px;outline:none;}
.ch-send{width:56px;height:36px;line-height:36px;text-align:center;background:#e4393c;color:#fff;border-radius:18px;margin-left:8px;font-size:14px;text-decoration:none;}
</style>

</div>


</div>
<script src="/static/m.js"></script>

<script>
var goodsId = <?php echo htmlentities((string) $goods['id']); ?>;
var sellerId = <?php echo htmlentities((string) $seller['id']); ?>;
var lastId = <?php echo htmlentities((string) $last_id); ?>;

// 滚动到底部
function scrollToBottom() {
    var body = document.getElementById('chatBody');
    if (body) body.scrollTop = body.scrollHeight;
}
scrollToBottom();

// 发送消息
function sendMsg() {
    var input = document.getElementById('msgInput');
    var content = input.value.trim();
    if (!content) { toast('请输入消息内容'); return; }
    ajaxPost('/chat/send', {goods_id: goodsId, seller_id: sellerId, content: content}, function(res){
        if (res.code === 1) {
            input.value = '';
            // 追加新消息
            var body = document.getElementById('chatBody');
            var div = document.createElement('div');
            div.className = 'ch-msg ch-mine';
            var now = new Date();
            var ts = ('0'+now.getMonth()).slice(-2)+'-'+('0'+now.getDate()).slice(-2)+' '+('0'+now.getHours()).slice(-2)+':'+('0'+now.getMinutes()).slice(-2);
            div.innerHTML = '<div class="ch-bubble">'+escapeHtml(content)+'</div><div class="ch-time">'+ts+'</div>';
            body.appendChild(div);
            lastId = res.id || lastId;
            scrollToBottom();
        } else {
            toast(res.msg);
            if (res.code === -1) {
                setTimeout(function(){ location.href='/user/login'; }, 600);
            }
        }
    });
}

// 轮询新消息
function pollMsg() {
    ajaxGet('/chat/poll?goods_id='+goodsId+'&seller_id='+sellerId+'&last_id='+lastId, function(res){
        if (res.code === 1 && res.data && res.data.length > 0) {
            var body = document.getElementById('chatBody');
            for (var i = 0; i < res.data.length; i++) {
                var m = res.data[i];
                var div = document.createElement('div');
                div.className = 'ch-msg ' + (m.from_uid == <?php echo htmlentities((string) $uid); ?> ? 'ch-mine' : 'ch-other');
                div.innerHTML = '<div class="ch-bubble">'+escapeHtml(m.content)+'</div><div class="ch-time">'+m.time_str+'</div>';
                body.appendChild(div);
                lastId = m.id;
            }
            scrollToBottom();
        }
    });
}
setInterval(pollMsg, 3000);

function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

</body>
</html>

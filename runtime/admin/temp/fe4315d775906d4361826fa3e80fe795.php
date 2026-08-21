<?php /*a:2:{s:65:"/www/wwwroot/2026/08/16/17_3/app/admin/view/admin_user/index.html";i:1787106486;s:55:"/www/wwwroot/2026/08/16/17_3/app/admin/view/layout.html";i:1787106485;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理员管理 - 竞拍商城</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Microsoft YaHei",Arial,sans-serif;background:#f0f2f5;font-size:14px;color:#333}
a{text-decoration:none;color:#333}
a:hover{color:#ff7a00}

/* 布局 */
.layout{display:flex;min-height:100vh}
.sidebar{width:220px;background:#1e293b;color:#cbd5e1;flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;bottom:0;left:0;z-index:100}
.main{flex:1;margin-left:220px;display:flex;flex-direction:column;min-height:100vh}

/* 侧边栏 */
.sidebar .logo{height:60px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;color:#fff;border-bottom:1px solid #334155}
.sidebar .logo span{color:#ff7a00}
.sidebar nav{flex:1;overflow-y:auto;padding:10px 0}
.sidebar nav::-webkit-scrollbar{width:4px}
.sidebar nav::-webkit-scrollbar-thumb{background:#475569;border-radius:2px}
.menu-group{margin-bottom:4px}
.menu-group>.menu-title{display:flex;align-items:center;padding:11px 20px;cursor:pointer;font-size:14px;transition:background .2s}
.menu-group>.menu-title:hover{background:#334155;color:#fff}
.menu-group>.menu-title .icon{margin-right:10px;display:flex;align-items:center;justify-content:center;width:20px;height:20px;flex-shrink:0}
.menu-group>.menu-title .icon svg{width:18px;height:18px;color:#cbd5e1;transition:color .2s}
.menu-group>.menu-title:hover .icon svg{color:#ff7a00}
.menu-group.open>.menu-title .icon svg{color:#ff7a00}
.menu-group>.menu-title .arrow{margin-left:auto;font-size:10px;transition:transform .2s}
.menu-group.open>.menu-title .arrow{transform:rotate(90deg)}
.menu-group.open>.menu-title{background:#334155;color:#fff}
.menu-group .menu-items{display:none;background:#0f172a;padding:4px 0}
.menu-group.open .menu-items{display:block}
.menu-items a{display:block;padding:10px 20px 10px 50px;font-size:13px;color:#94a3b8;transition:all .2s}
.menu-items a:hover{color:#fff;background:#1e293b}
.menu-items a.active{color:#ff7a00;background:#1e293b;border-left:3px solid #ff7a00}

/* 顶栏 */
.topbar{height:60px;background:#fff;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:90}
.topbar .breadcrumb{color:#64748b;font-size:13px}
.topbar .breadcrumb b{color:#333}
.topbar .admin-info{display:flex;align-items:center;gap:12px}
.topbar .admin-info .avatar{width:34px;height:34px;border-radius:50%;background:#ff7a00;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold}
.topbar .admin-info .name{font-size:14px}
.topbar .admin-info a{font-size:13px;color:#64748b}
.topbar .admin-info a:hover{color:#ff7a00}

/* 内容区 */
.content{padding:20px 24px;flex:1}
.page-title{font-size:18px;font-weight:bold;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between}
.page-title .tools{display:flex;gap:10px}

/* 卡片 */
.card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:20px;margin-bottom:20px}
.card .card-title{font-size:15px;font-weight:bold;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}

/* 统计卡片 */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px}
.stat-card{background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;align-items:center;gap:16px}
.stat-card .stat-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px}
.stat-card .stat-info .num{font-size:24px;font-weight:bold;color:#1e293b}
.stat-card .stat-info .label{font-size:13px;color:#94a3b8;margin-top:2px}
.bg-orange{background:#fff3e6}.bg-blue{background:#e8f0fe}.bg-green{background:#e6f7ee}.bg-purple{background:#f3e8ff}.bg-red{background:#fee2e2}.bg-cyan{background:#e0f7fa}

/* 表格 */
.table-wrap{overflow-x:auto}
table.table{width:100%;border-collapse:collapse}
table.table th{background:#f8fafc;padding:12px 12px;text-align:left;font-weight:600;font-size:13px;color:#475569;border-bottom:1px solid #e2e8f0;white-space:nowrap}
table.table td{padding:12px;border-bottom:1px solid #f1f5f9;font-size:13px;vertical-align:middle}
table.table tr:hover td{background:#fafbfc}
table.table .thumb{width:46px;height:46px;border-radius:6px;object-fit:cover;background:#f1f5f9}
table.table .price{color:#ff7a00;font-weight:bold}
table.table .empty{text-align:center;color:#94a3b8;padding:40px 0}

/* 标签 */
.tag{display:inline-block;padding:2px 10px;border-radius:20px;font-size:12px;line-height:20px}
.tag-green{background:#e6f7ee;color:#16a34a}
.tag-red{background:#fee2e2;color:#dc2626}
.tag-orange{background:#fff3e6;color:#ea580c}
.tag-blue{background:#e8f0fe;color:#2563eb}
.tag-gray{background:#f1f5f9;color:#64748b}
.tag-purple{background:#f3e8ff;color:#9333ea}

/* 按钮 */
.btn{display:inline-block;padding:7px 16px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:13px;cursor:pointer;transition:all .2s;line-height:20px}
.btn:hover{border-color:#ff7a00;color:#ff7a00}
.btn-primary{background:#ff7a00;border-color:#ff7a00;color:#fff}
.btn-primary:hover{background:#e96c00;color:#fff}
.btn-danger{background:#fff;border-color:#fca5a5;color:#dc2626}
.btn-danger:hover{background:#dc2626;color:#fff}
.btn-green{background:#16a34a;border-color:#16a34a;color:#fff}
.btn-green:hover{background:#15803d;color:#fff}
.btn-sm{padding:3px 10px;font-size:12px}
.btn-block{width:100%}

/* 表单 */
.form-group{margin-bottom:16px}
.form-group label{display:block;margin-bottom:6px;font-size:13px;color:#475569}
.form-group label .req{color:#dc2626;margin-right:2px}
.form-control{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;outline:none;transition:border .2s;background:#fff}
.form-control:focus{border-color:#ff7a00}
textarea.form-control{resize:vertical;min-height:80px}
select.form-control{appearance:auto}
.form-row{display:flex;gap:16px}
.form-row .form-group{flex:1}
.form-inline{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.form-inline .form-control{width:auto;min-width:140px}

/* 搜索栏 */
.search-bar{background:#fff;border-radius:10px;padding:12px 16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.search-bar .search-input{position:relative;flex:1 1 150px;min-width:110px}
.search-bar .search-input .s-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;width:14px;height:14px;pointer-events:none}
.search-bar .search-input .form-control{width:100%;min-width:0;padding-left:32px}
.search-bar>input.form-control{flex:1 1 150px;min-width:110px}
.search-bar>select.form-control{width:auto;flex:none;min-width:0}
.search-bar input[type=date].form-control{width:130px;flex:none;min-width:0;padding:9px 8px}
.search-bar .btn{flex:none;white-space:nowrap}
.search-bar .spacer{flex:1}

/* 表格工具条 */
.table-tools{display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;margin-bottom:10px}
.table-tools .sel-info{font-size:13px;color:#64748b}
.table-tools .sel-info b{color:#dc2626;font-size:15px}
table.table input[type=checkbox]{width:15px;height:15px;cursor:pointer;vertical-align:middle}

/* 分页 */
.pagination{display:flex;justify-content:center;align-items:center;gap:6px;margin-top:16px;flex-wrap:wrap}
.pagination a,.pagination span{display:inline-block;padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;background:#fff;color:#475569}
.pagination a:hover{border-color:#ff7a00;color:#ff7a00}
.pagination .current{background:#ff7a00;border-color:#ff7a00;color:#fff}
.pagination .disabled{opacity:.5;pointer-events:none}
.pagination .info{font-size:13px;color:#94a3b8}

/* 详情 */
.detail-list{width:100%}
.detail-list tr td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:13px}
.detail-list tr td:first-child{width:140px;color:#94a3b8;background:#fafbfc;font-weight:600}

/* 弹层 */
.mask{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:999}
.mask.show{display:flex}
.dialog{background:#fff;border-radius:10px;width:420px;max-width:92%;max-height:85vh;overflow-y:auto;padding:24px}
.dialog .dialog-title{font-size:16px;font-weight:bold;margin-bottom:20px}
.dialog .dialog-body{max-height:60vh;overflow-y:auto}
.dialog .dialog-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9}

/* 提示 */
.tip{font-size:12px;color:#94a3b8;margin-top:4px}
.footer{text-align:center;padding:16px;color:#94a3b8;font-size:12px}

/* 上传预览 */
.upload-box{display:flex;gap:10px;flex-wrap:wrap}
.upload-box .up-item{position:relative;width:90px;height:90px;border:1px dashed #d1d5db;border-radius:8px;overflow:hidden;cursor:pointer;background:#fafbfc}
.upload-box .up-item img{width:100%;height:100%;object-fit:cover}
.upload-box .up-item .del{position:absolute;top:2px;right:2px;background:rgba(0,0,0,.6);color:#fff;width:18px;height:18px;border-radius:50%;display:none;align-items:center;justify-content:center;font-size:11px;cursor:pointer}
.upload-box .up-item:hover .del{display:flex}
.upload-box .up-item.add{display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:28px}
.upload-box .up-item.add:hover{border-color:#ff7a00;color:#ff7a00}
</style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="logo">🏆 <span>竞拍</span>商城</div>
        <nav id="menu">
            <?php if(is_array($menus) || $menus instanceof \think\Collection || $menus instanceof \think\Paginator): $gk = 0; $__LIST__ = $menus;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$group): $mod = ($gk % 2 );++$gk;?>
            <div class="menu-group" data-key="<?php echo htmlentities((string) $key); ?>">
                <div class="menu-title">
                    <span class="icon"><?php echo $group['icon']; ?></span><?php echo htmlentities((string) $group['title']); ?><span class="arrow">▶</span>
                </div>
                <div class="menu-items">
                    <?php if(is_array($group['items']) || $group['items'] instanceof \think\Collection || $group['items'] instanceof \think\Paginator): $i = 0; $__LIST__ = $group['items'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?>
                    <a href="<?php echo htmlentities((string) $item['url']); ?>" class="<?php if(strpos($item['url'], $menu_active) !== false): ?>active<?php endif; ?>"><?php echo htmlentities((string) $item['title']); ?></a>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </div>
            </div>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="breadcrumb">系统设置 / <b>管理员管理</b></div>
            <div class="admin-info">
                <div class="avatar"><?php echo !empty($admin['real_name']) ? htmlentities((string) $admin['real_name']) : substr($admin['username'], 0, 1); ?></div>
                <span class="name"><?php echo !empty($admin['real_name']) ? htmlentities((string) $admin['real_name']) : htmlentities((string) $admin['username']); ?></span>
                <a href="/admin1314/login/logout">退出</a>
            </div>
        </header>
        <div class="content">
            
<div class="page-title">
    <span>管理员列表</span>
    <div class="tools">
        <button class="btn btn-primary" onclick="openEdit(0)">+ 新增管理员</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>ID</th><th>用户名</th><th>姓名</th><th>角色</th><th>状态</th>
                <th>最后登录</th><th>创建时间</th><th>操作</th>
            </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>
</div>

<!-- 编辑弹层 -->
<div class="mask" id="editMask">
    <div class="dialog">
        <div class="dialog-title" id="editTitle">新增管理员</div>
        <div class="dialog-body">
            <input type="hidden" id="editId" value="0">
            <div class="form-group">
                <label>用户名 <?php if($admin['id'] != 1 && false): ?><span class="req">*</span><?php endif; ?></label>
                <input type="text" class="form-control" id="editUsername" placeholder="登录用户名">
            </div>
            <div class="form-group">
                <label>密码（修改时留空则不修改）</label>
                <input type="text" class="form-control" id="editPassword" placeholder="至少6位">
            </div>
            <div class="form-group">
                <label>姓名</label>
                <input type="text" class="form-control" id="editRealName" placeholder="真实姓名">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>角色</label>
                    <select class="form-control" id="editRole">
                        <option value="2">普通管理员</option>
                        <option value="1">超级管理员</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select class="form-control" id="editStatus">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn" onclick="document.getElementById('editMask').classList.remove('show')">取消</button>
            <button class="btn btn-primary" onclick="doSave()">保存</button>
        </div>
    </div>
</div>

<script>
var listData = [];
var myId = <?php echo htmlentities((string) $admin['id']); ?>;

function loadList(){
    fetch('/admin1314/admin_user/index', {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){
            listData = res.data || [];
            var html = '';
            if(!listData.length){
                html = '<tr><td colspan="8" class="empty">暂无数据</td></tr>';
            } else {
                listData.forEach(function(a){
                    var roleHtml = a.role == 1 ? '<span class="tag tag-orange">超级管理员</span>' : '<span class="tag tag-gray">普通管理员</span>';
                    var stHtml = a.status == 1 ? '<span class="tag tag-green">启用</span>' : '<span class="tag tag-red">禁用</span>';
                    html += '<tr>'
                        + '<td>' + a.id + '</td>'
                        + '<td>' + a.username + '</td>'
                        + '<td>' + (a.real_name || '-') + '</td>'
                        + '<td>' + roleHtml + '</td>'
                        + '<td>' + stHtml + '</td>'
                        + '<td>' + (a.last_login_time ? fmtTime(a.last_login_time) : '-') + '</td>'
                        + '<td>' + (a.create_time ? fmtTime(a.create_time) : '-') + '</td>'
                        + '<td style="white-space:nowrap;">'
                        + '<a class="btn btn-sm" href="javascript:;" onclick="openEdit(' + a.id + ')">编辑</a> '
                        + (a.id != 1 ? '<a class="btn btn-sm btn-danger" href="javascript:;" onclick="del(' + a.id + ')">删除</a>' : '')
                        + '</td></tr>';
                });
            }
            document.getElementById('tbody').innerHTML = html;
        });
}

function openEdit(id){
    document.getElementById('editId').value = id;
    document.getElementById('editUsername').value = '';
    document.getElementById('editPassword').value = '';
    document.getElementById('editRealName').value = '';
    document.getElementById('editRole').value = 2;
    document.getElementById('editStatus').value = 1;
    document.getElementById('editTitle').textContent = '新增管理员';
    if(id > 0){
        listData.forEach(function(a){
            if(a.id == id){
                document.getElementById('editUsername').value = a.username;
                document.getElementById('editRealName').value = a.real_name;
                document.getElementById('editRole').value = a.role;
                document.getElementById('editStatus').value = a.status;
                document.getElementById('editTitle').textContent = '编辑管理员';
                if(a.id == 1){
                    document.getElementById('editRole').value = 1;
                    document.getElementById('editRole').disabled = true;
                } else {
                    document.getElementById('editRole').disabled = false;
                }
            }
        });
    }
    document.getElementById('editMask').classList.add('show');
}

function doSave(){
    var data = {
        id: document.getElementById('editId').value,
        username: document.getElementById('editUsername').value.trim(),
        password: document.getElementById('editPassword').value.trim(),
        real_name: document.getElementById('editRealName').value.trim(),
        role: document.getElementById('editRole').value,
        status: document.getElementById('editStatus').value
    };
    if(data.id == 0 && !data.username){ alert('请输入用户名'); return; }
    if(data.id == 0 && data.password.length < 6){ alert('密码至少6位'); return; }
    if(data.id > 0 && data.password && data.password.length < 6){ alert('密码至少6位'); return; }
    ajaxPost('/admin1314/admin_user/save', data, function(res){
        alert(res.msg);
        if(res.code == 1){
            document.getElementById('editMask').classList.remove('show');
            loadList();
        }
    });
}

function del(id){
    confirmDialog('确定删除该管理员吗？', function(){
        ajaxPost('/admin1314/admin_user/delete', {id:id}, function(res){
            alert(res.msg); if(res.code == 1) loadList();
        });
    });
}

function fmtTime(ts){
    var d = new Date(ts * 1000);
    function p(n){ return n < 10 ? '0' + n : n; }
    return d.getFullYear() + '-' + p(d.getMonth()+1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
}

loadList();
</script>

        </div>
        <div class="footer">竞拍商城管理系统 v1.0</div>
    </div>
</div>

<div class="mask" id="dialogMask">
    <div class="dialog">
        <div class="dialog-title" id="dialogTitle">提示</div>
        <div class="dialog-body" id="dialogBody"></div>
        <div class="dialog-footer">
            <button class="btn" onclick="closeDialog()">取消</button>
            <button class="btn btn-primary" id="dialogOk">确定</button>
        </div>
    </div>
</div>

<script>
// 菜单展开
document.querySelectorAll('.menu-group').forEach(function(g){
    if(g.querySelector('a.active')){g.classList.add('open');}
    g.querySelector('.menu-title').addEventListener('click',function(){
        document.querySelectorAll('.menu-group.open').forEach(function(o){if(o!==g)o.classList.remove('open')});
        g.classList.toggle('open');
    });
});

// 确认弹层
var dialogCallback = null;
function confirmDialog(msg, cb){
    document.getElementById('dialogBody').innerHTML = msg;
    document.getElementById('dialogMask').classList.add('show');
    dialogCallback = cb;
}
function closeDialog(){
    document.getElementById('dialogMask').classList.remove('show');
    dialogCallback = null;
}
document.getElementById('dialogOk').onclick = function(){
    var cb = dialogCallback;
    closeDialog();
    if(typeof cb === 'function') cb();
};
document.getElementById('dialogMask').addEventListener('click',function(e){
    if(e.target === this) closeDialog();
});

// 表单提交
function submitForm(formId, url, successMsg, failMsg){
    var form = document.getElementById(formId);
    var btn = form.querySelector('[type=submit]');
    if(btn) btn.disabled = true;
    var fd = new FormData(form);
    fetch(url, {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(btn) btn.disabled = false;
            if(res.code == 1){
                if(res.msg) alert(res.msg || '操作成功');
                if(res.url) location.href = res.url; else location.reload();
            } else {
                alert(res.msg || '操作失败');
            }
        })
        .catch(function(){
            if(btn) btn.disabled = false;
            alert('网络错误，请重试');
        });
    return false;
}

// ajax 请求
function ajaxPost(url, data, cb, errMsg){
    fetch(url, {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data)})
        .then(function(r){ return r.json(); })
        .then(function(res){ cb(res); })
        .catch(function(){ alert(errMsg || '网络错误，请重试'); });
}
</script>

</body>
</html>

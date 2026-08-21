<?php /*a:2:{s:60:"/www/wwwroot/2026/08/16/17_3/app/admin/view/goods/index.html";i:1787106491;s:55:"/www/wwwroot/2026/08/16/17_3/app/admin/view/layout.html";i:1787106485;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>商品列表 - 竞拍商城</title>
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
            <div class="breadcrumb">商品管理 / <b>商品列表</b></div>
            <div class="admin-info">
                <div class="avatar"><?php echo !empty($admin['real_name']) ? htmlentities((string) $admin['real_name']) : substr($admin['username'], 0, 1); ?></div>
                <span class="name"><?php echo !empty($admin['real_name']) ? htmlentities((string) $admin['real_name']) : htmlentities((string) $admin['username']); ?></span>
                <a href="/admin1314/login/logout">退出</a>
            </div>
        </header>
        <div class="content">
            
<div class="search-bar">
    <div class="search-input">
        <svg class="s-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        <input type="text" class="form-control" id="keyword" placeholder="商品标题">
    </div>
    <div class="search-input">
        <svg class="s-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c1.2-3.6 4-5.5 7.5-5.5s6.3 1.9 7.5 5.5"/></svg>
        <input type="text" class="form-control" id="seller_kw" placeholder="卖家昵称/手机号">
    </div>
    <select class="form-control" id="category_id">
        <option value="">全部分类</option>
        <?php if(is_array($categories) || $categories instanceof \think\Collection || $categories instanceof \think\Paginator): $i = 0; $__LIST__ = $categories;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i;?>
        <option value="<?php echo htmlentities((string) $c['id']); ?>"><?php echo htmlentities((string) $c['name']); ?></option>
        <?php endforeach; endif; else: echo "" ;endif; ?>
    </select>
    <select class="form-control" id="status">
        <option value="">全部状态</option>
        <option value="0">待审核</option>
        <option value="1">拍卖中</option>
        <option value="2">已成交</option>
        <option value="3">流拍</option>
        <option value="4">已下架</option>
        <option value="5">审核拒绝</option>
    </select>
    <input type="date" class="form-control" id="create_start" title="发布时间开始">
    <span style="color:#94a3b8;">至</span>
    <input type="date" class="form-control" id="create_end" title="发布时间结束">
    <button class="btn btn-primary" onclick="loadList(1)">查询</button>
    <button class="btn" onclick="resetSearch()">重置</button>
    <span class="spacer"></span>
    <button class="btn btn-green" onclick="openAddGoods()">＋ 发布商品</button>
</div>

<div class="card">
    <div class="table-tools">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;color:#475569;">
            <input type="checkbox" id="checkAll" onclick="toggleAll(this)"> 全选
        </label>
        <span class="sel-info">已选 <b id="selCount">0</b> 项</span>
        <span class="spacer"></span>
        <button class="btn btn-danger" id="batchDelBtn" onclick="batchDel()" disabled>批量删除</button>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th style="width:36px;"></th>
                <th>ID</th><th>封面</th><th>商品标题</th><th>分类</th><th>卖家</th>
                <th>起拍价</th><th>当前/成交价</th><th>保证金</th><th>状态</th><th>出价次数</th><th>结束时间</th><th>操作</th>
            </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    </div>
    <div class="pagination" id="pagination"></div>
</div>

<!-- 发布商品弹层 -->
<div class="mask" id="addMask">
    <div class="dialog" style="width:760px;">
        <div class="dialog-title">发布商品（发到指定卖家店铺）</div>
        <div class="dialog-body">
            <div class="g-row">
                <div class="g-col" style="flex:1;">
                    <label>卖家店铺 <span class="req">*</span></label>
                    <input type="text" class="form-control" id="sellerSearch" placeholder="输入昵称/手机号筛选" oninput="filterSeller()" style="margin-bottom:6px;">
                    <select class="form-control" id="sellerSel" style="max-height:130px;">
                        <?php if(is_array($sellers) || $sellers instanceof \think\Collection || $sellers instanceof \think\Paginator): $i = 0; $__LIST__ = $sellers;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$s): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities((string) $s['id']); ?>"><?php echo htmlentities((string) $s['nickname']); ?>（<?php echo htmlentities((string) $s['mobile']); ?>）</option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                    <?php if(empty($sellers)): ?><div style="font-size:12px;color:#e11d48;margin-top:6px;">暂无卖家，请先在会员管理中设置卖家</div><?php endif; ?>
                </div>
                <div class="g-col" style="width:46%;">
                    <label>商品分类 <span class="req">*</span></label>
                    <select class="form-control" id="gCategory">
                        <option value="">请选择分类</option>
                        <?php if(is_array($categories) || $categories instanceof \think\Collection || $categories instanceof \think\Paginator): $i = 0; $__LIST__ = $categories;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities((string) $c['id']); ?>"><?php echo htmlentities((string) $c['name']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>
            <div class="g-row">
                <div class="g-col" style="flex:1;">
                    <label>商品标题 <span class="req">*</span></label>
                    <input type="text" class="form-control" id="gTitle" placeholder="请输入商品标题" maxlength="60">
                </div>
            </div>
            <div class="g-row">
                <div class="g-col"><label>起拍价（元） <span class="req">*</span></label><input type="number" step="0.01" min="0" class="form-control" id="gStart" placeholder="0.00"></div>
                <div class="g-col"><label>加价幅度（元） <span class="req">*</span></label><input type="number" step="0.01" min="0" class="form-control" id="gRaise" placeholder="0.00"></div>
                <div class="g-col"><label>保留价（元）</label><input type="number" step="0.01" min="0" class="form-control" id="gReserve" placeholder="0 表示无"></div>
            </div>
            <div class="g-row">
                <div class="g-col"><label>保证金（元）</label><input type="number" step="0.01" min="0" class="form-control" id="gDeposit" placeholder="0"></div>
                <div class="g-col"><label>佣金比例（%）</label><input type="number" step="0.01" min="0" max="100" class="form-control" id="gCommission" placeholder="0 按系统设置"></div>
                <div class="g-col"><label>参考价（元）</label><input type="number" step="0.01" min="0" class="form-control" id="gRef" placeholder="0"></div>
            </div>
            <div class="g-row">
                <div class="g-col"><label>截拍时间 <span class="req">*</span></label><input type="datetime-local" class="form-control" id="gEndTime"></div>
                <div class="g-col"><label>延迟结束（秒）</label><input type="number" min="0" class="form-control" id="gDelay" placeholder="0 表示不延迟"></div>
                <div class="g-col" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;font-size:13px;margin:0;">
                        <input type="checkbox" id="gFreeShip" style="width:16px;height:16px;"> 包邮
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;font-size:13px;margin:0 0 0 14px;">
                        <input type="checkbox" id="gFeatured" style="width:16px;height:16px;"> 推荐
                    </label>
                </div>
            </div>
            <div class="g-row">
                <div class="g-col" style="flex:1;">
                    <label>商品图片 <span class="req">*</span>（第一张为封面，最多9张）</label>
                    <div class="g-imgs" id="gImgs">
                        <div class="g-img-add" onclick="uploadGoodsImg()">＋<span>上传图片</span></div>
                    </div>
                </div>
            </div>
            <div class="g-row">
                <div class="g-col" style="flex:1;">
                    <label>商品详情</label>
                    <textarea class="form-control" id="gContent" rows="4" placeholder="商品描述、成色、规格等"></textarea>
                </div>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn" onclick="document.getElementById('addMask').classList.remove('show')">取消</button>
            <button class="btn btn-primary" onclick="doAddGoods()">确认发布</button>
        </div>
    </div>
</div>

<style>
.g-row{display:flex;gap:14px;margin-bottom:14px;}
.g-col{flex:1;}
.g-col label{display:block;font-size:13px;color:#334155;margin-bottom:6px;}
.req{color:#e11d48;}
.g-imgs{display:flex;flex-wrap:wrap;gap:10px;}
.g-img-add{width:84px;height:84px;border:1.5px dashed #cbd5e1;border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;color:#94a3b8;font-size:24px;cursor:pointer;background:#f8fafc;}
.g-img-add span{font-size:12px;color:#94a3b8;}
.g-img{position:relative;width:84px;height:84px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;}
.g-img img{width:100%;height:100%;object-fit:cover;display:block;}
.g-img .del{position:absolute;top:2px;right:2px;width:20px;height:20px;line-height:18px;text-align:center;background:rgba(0,0,0,.55);color:#fff;border-radius:50%;font-size:14px;cursor:pointer;}
.g-img .tag{position:absolute;left:0;bottom:0;width:100%;background:rgba(0,0,0,.5);color:#fff;font-size:10px;text-align:center;padding:1px 0;}
</style>

<!-- 拒绝弹层 -->
<div class="mask" id="rejectMask">
    <div class="dialog">
        <div class="dialog-title">填写拒绝原因</div>
        <div class="dialog-body">
            <textarea class="form-control" id="rejectReason" placeholder="请输入拒绝原因"></textarea>
        </div>
        <div class="dialog-footer">
            <button class="btn" onclick="document.getElementById('rejectMask').classList.remove('show')">取消</button>
            <button class="btn btn-primary" onclick="doReject()">确定拒绝</button>
        </div>
    </div>
</div>

<script>
var currentPage = 1;
var currentId = 0;
var statusMap = {
    0: ['待审核','tag-orange'], 1: ['拍卖中','tag-green'], 2: ['已成交','tag-blue'],
    3: ['流拍','tag-gray'], 4: ['已下架','tag-purple'], 5: ['审核拒绝','tag-red']
};

function loadList(page){
    currentPage = page;
    fetch('/admin1314/goods/index?page=' + page + '&limit=15'
        + '&keyword=' + encodeURIComponent(document.getElementById('keyword').value)
        + '&seller_kw=' + encodeURIComponent(document.getElementById('seller_kw').value)
        + '&category_id=' + document.getElementById('category_id').value
        + '&status=' + document.getElementById('status').value
        + '&create_start=' + document.getElementById('create_start').value
        + '&create_end=' + document.getElementById('create_end').value, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){
            var html = '';
            if(!res.data || !res.data.length){
                html = '<tr><td colspan="13" class="empty">暂无数据</td></tr>';
            } else {
                res.data.forEach(function(g){
                    var st = statusMap[g.status] || ['未知','tag-gray'];
                    var cover = g.cover ? '<img class="thumb" src="' + g.cover + '" onerror="this.style.visibility=\'hidden\'">' : '-';
                    html += '<tr>'
                        + '<td><input type="checkbox" class="row-check" value="' + g.id + '" onchange="updateSel()"></td>'
                        + '<td>' + g.id + '</td>'
                        + '<td>' + cover + '</td>'
                        + '<td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + g.title + '">' + g.title + '</td>'
                        + '<td>' + (g.category_name || '-') + '</td>'
                        + '<td>' + (g.seller_name || g.seller_mobile || g.seller_id) + '</td>'
                        + '<td class="price">￥' + g.start_price + '</td>'
                        + '<td>' + (g.final_price > 0 ? '<span class="price">￥' + g.final_price + '</span>' : '￥' + g.start_price) + '</td>'
                        + '<td>￥' + g.deposit + '</td>'
                        + '<td><span class="tag ' + st[1] + '">' + st[0] + '</span></td>'
                        + '<td>' + g.bid_count + '</td>'
                        + '<td>' + (g.end_time ? fmtTime(g.end_time) : '-') + '</td>'
                        + '<td style="white-space:nowrap;">'
                        + '<a class="btn btn-sm" href="/admin1314/goods/detail?id=' + g.id + '">详情</a> '
                        + (g.status == 0 || g.status == 5
                            ? '<a class="btn btn-sm btn-green" href="javascript:;" onclick="audit(' + g.id + ',\'pass\')">通过</a> '
                            + '<a class="btn btn-sm btn-danger" href="javascript:;" onclick="openReject(' + g.id + ')">拒绝</a> '
                            : '')
                        + ((g.status == 1)
                            ? '<a class="btn btn-sm btn-danger" href="javascript:;" onclick="toggleStatus(' + g.id + ',4)">下架</a> '
                            : '')
                        + ((g.status == 4)
                            ? '<a class="btn btn-sm btn-green" href="javascript:;" onclick="toggleStatus(' + g.id + ',1)">上架</a> '
                            : '')
                        + ((g.status != 2)
                            ? '<a class="btn btn-sm btn-danger" href="javascript:;" onclick="del(' + g.id + ')">删除</a>'
                            : '')
                        + '</td></tr>';
                });
            }
            document.getElementById('tbody').innerHTML = html;
            renderPagination(res.count, page);
            resetSel();
        });
}

function renderPagination(total, page){
    var pages = Math.ceil(total / 15) || 1;
    var html = '<span class="info">共 ' + total + ' 条，第 ' + page + '/' + pages + ' 页</span> ';
    html += '<a class="' + (page <= 1 ? 'disabled' : '') + '" href="javascript:;" onclick="loadList(1)">首页</a>';
    html += '<a class="' + (page <= 1 ? 'disabled' : '') + '" href="javascript:;" onclick="loadList(' + (page - 1) + ')">上一页</a>';
    var start = Math.max(1, page - 2), end = Math.min(pages, page + 2);
    for(var i = start; i <= end; i++){
        html += i == page ? '<span class="current">' + i + '</span>' : '<a href="javascript:;" onclick="loadList(' + i + ')">' + i + '</a>';
    }
    html += '<a class="' + (page >= pages ? 'disabled' : '') + '" href="javascript:;" onclick="loadList(' + (page + 1) + ')">下一页</a>';
    html += '<a class="' + (page >= pages ? 'disabled' : '') + '" href="javascript:;" onclick="loadList(' + pages + ')">末页</a>';
    document.getElementById('pagination').innerHTML = html;
}

function resetSearch(){
    document.getElementById('keyword').value = '';
    document.getElementById('seller_kw').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('status').value = '';
    document.getElementById('create_start').value = '';
    document.getElementById('create_end').value = '';
    loadList(1);
}

/* ===== 发布商品 ===== */
var goodsImgs = [];

function openAddGoods(){
    goodsImgs = [];
    document.getElementById('sellerSearch').value = '';
    document.getElementById('sellerSel').selectedIndex = 0;
    document.getElementById('gCategory').value = '';
    document.getElementById('gTitle').value = '';
    document.getElementById('gStart').value = '';
    document.getElementById('gRaise').value = '';
    document.getElementById('gReserve').value = '';
    document.getElementById('gDeposit').value = '';
    document.getElementById('gCommission').value = '';
    document.getElementById('gRef').value = '';
    document.getElementById('gEndTime').value = '';
    document.getElementById('gDelay').value = '';
    document.getElementById('gFreeShip').checked = false;
    document.getElementById('gFeatured').checked = false;
    document.getElementById('gContent').value = '';
    filterSeller();
    renderGoodsImgs();
    document.getElementById('addMask').classList.add('show');
}

function filterSeller(){
    var kw = document.getElementById('sellerSearch').value.trim().toLowerCase();
    var sel = document.getElementById('sellerSel');
    var first = null;
    for(var i = 0; i < sel.options.length; i++){
        var opt = sel.options[i];
        var match = !kw || opt.text.toLowerCase().indexOf(kw) > -1;
        opt.style.display = match ? '' : 'none';
        if(match && !first) first = opt;
    }
    if(first) sel.selectedIndex = first.index;
}

function uploadGoodsImg(){
    if(goodsImgs.length >= 9){ alert('最多上传9张图片'); return; }
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e){
        var file = e.target.files[0];
        if(!file) return;
        var fd = new FormData();
        fd.append('file', file);
        fetch('/admin1314/upload/image', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd})
            .then(function(r){ return r.json(); })
            .then(function(res){
                if(res.code === 1){
                    goodsImgs.push(res.url);
                    renderGoodsImgs();
                } else {
                    alert(res.msg || '上传失败');
                }
            })
            .catch(function(){ alert('网络错误，请重试'); });
    };
    input.click();
}

function removeGoodsImg(i){
    goodsImgs.splice(i, 1);
    renderGoodsImgs();
}

function renderGoodsImgs(){
    var html = '';
    goodsImgs.forEach(function(url, i){
        html += '<div class="g-img"><img src="' + url + '"><span class="tag">' + (i === 0 ? '封面' : '') + '</span><span class="del" onclick="removeGoodsImg(' + i + ')">×</span></div>';
    });
    html += '<div class="g-img-add" onclick="uploadGoodsImg()">＋<span>上传图片</span></div>';
    document.getElementById('gImgs').innerHTML = html;
}

function doAddGoods(){
    var sellerId = document.getElementById('sellerSel').value;
    var title = document.getElementById('gTitle').value.trim();
    var categoryId = document.getElementById('gCategory').value;
    var startPrice = document.getElementById('gStart').value;
    var raisePrice = document.getElementById('gRaise').value;
    var endTime = document.getElementById('gEndTime').value;
    if(!sellerId){ alert('请选择卖家店铺'); return; }
    if(!title){ alert('请输入商品标题'); return; }
    if(!categoryId){ alert('请选择分类'); return; }
    if(!startPrice || parseFloat(startPrice) <= 0){ alert('起拍价必须大于0'); return; }
    if(!raisePrice || parseFloat(raisePrice) <= 0){ alert('加价幅度必须大于0'); return; }
    if(!endTime){ alert('请选择截拍时间'); return; }
    if(goodsImgs.length === 0){ alert('请至少上传一张商品图片'); return; }
    ajaxPost('/admin1314/goods/add', {
        seller_id: sellerId,
        title: title,
        category_id: categoryId,
        content: document.getElementById('gContent').value.trim(),
        start_price: startPrice,
        raise_price: raisePrice,
        reserve_price: document.getElementById('gReserve').value || 0,
        deposit: document.getElementById('gDeposit').value || 0,
        commission_rate: document.getElementById('gCommission').value || 0,
        reference_price: document.getElementById('gRef').value || 0,
        end_time: endTime,
        delay_seconds: document.getElementById('gDelay').value || 0,
        is_free_shipping: document.getElementById('gFreeShip').checked ? 1 : 0,
        is_featured: document.getElementById('gFeatured').checked ? 1 : 0,
        cover: goodsImgs[0],
        images: goodsImgs.join(',')
    }, function(res){
        alert(res.msg);
        if(res.code == 1){
            document.getElementById('addMask').classList.remove('show');
            loadList(1);
        }
    });
}

/* ===== 全选删除 ===== */
function toggleAll(el){
    document.querySelectorAll('.row-check').forEach(function(cb){ cb.checked = el.checked; });
    updateSel();
}

function updateSel(){
    var n = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('selCount').textContent = n;
    document.getElementById('batchDelBtn').disabled = n === 0;
    var all = document.querySelectorAll('.row-check');
    var checkAll = document.getElementById('checkAll');
    if(all.length) checkAll.checked = all.length === n;
}

function resetSel(){
    document.getElementById('checkAll').checked = false;
    document.getElementById('selCount').textContent = '0';
    document.getElementById('batchDelBtn').disabled = true;
}

function getCheckedIds(){
    var ids = [];
    document.querySelectorAll('.row-check:checked').forEach(function(cb){ ids.push(cb.value); });
    return ids;
}

function batchDel(){
    var ids = getCheckedIds();
    if(!ids.length){ alert('请先选择商品'); return; }
    confirmDialog('确定删除选中的 ' + ids.length + ' 个商品吗？（已成交商品自动跳过）', function(){
        ajaxPost('/admin1314/goods/delete', {ids: ids.join(',')}, function(res){
            alert(res.msg);
            if(res.code == 1) loadList(currentPage);
        });
    });
}

function audit(id, action){
    confirmDialog(action == 'pass' ? '通过后商品将进入拍卖中，确定通过吗？' : '确定拒绝该商品吗？', function(){
        ajaxPost('/admin1314/goods/audit', {id:id, action:action}, function(res){
            alert(res.msg); if(res.code == 1) loadList(currentPage);
        });
    });
}

function openReject(id){
    currentId = id;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectMask').classList.add('show');
}

function doReject(){
    var reason = document.getElementById('rejectReason').value.trim();
    if(!reason){ alert('请填写拒绝原因'); return; }
    ajaxPost('/admin1314/goods/audit', {id:currentId, action:'reject', reason:reason}, function(res){
        alert(res.msg);
        if(res.code == 1){
            document.getElementById('rejectMask').classList.remove('show');
            loadList(currentPage);
        }
    });
}

function toggleStatus(id, status){
    var tip = status == 1 ? '确定上架该商品吗？' : '下架后商品将停止竞拍，确定下架吗？';
    confirmDialog(tip, function(){
        ajaxPost('/admin1314/goods/setStatus', {id:id, status:status}, function(res){
            alert(res.msg); if(res.code == 1) loadList(currentPage);
        });
    });
}

function del(id){
    confirmDialog('删除后不可恢复，确定删除该商品吗？', function(){
        ajaxPost('/admin1314/goods/delete', {id:id}, function(res){
            alert(res.msg); if(res.code == 1) loadList(currentPage);
        });
    });
}

function fmtTime(ts){
    var d = new Date(ts * 1000);
    function p(n){ return n < 10 ? '0' + n : n; }
    return d.getFullYear() + '-' + p(d.getMonth()+1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
}

loadList(1);
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

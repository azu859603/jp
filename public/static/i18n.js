/* ==================== 多语言辅助（精简版）==================== */
/* 后端 PHP 已通过 lang() 处理模板/接口翻译，此文件仅处理：
   1. JS 内联 toast/confirm 消息翻译（DICT）
   2. 语言切换 UI（login 页右上角）  */
(function (w) {
    var LS_KEY = 'site_lang';
    var COOKIE_KEY = 'think_lang';

    function getCookie(name) {
        var m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return m ? decodeURIComponent(m[2]) : '';
    }
    var metaLang = document.querySelector('meta[name="current-lang"]');
    // 以服务端实际渲染的语言（meta）为准，避免 ?lang= 切换后按钮标签与页面语言不一致
var LANG = (metaLang ? metaLang.getAttribute('content') : '') || getCookie(COOKIE_KEY) || w.localStorage.getItem(LS_KEY) || 'zh-tw';

    /* ---------- JS 专用翻译字典（仅用于 toast/confirm 等内联消息） ---------- */
    var DICT = {
        // 公共
        '提示': ['提示', 'Notice'],
        '取消': ['取消', 'Cancel'],
        '确定': ['確定', 'OK'],
        '网络错误，请重试': ['網路錯誤，請重試', 'Network error, please retry'],
        '上传失败，请重试': ['上傳失敗，請重試', 'Upload failed, please retry'],
        '上传失败': ['上傳失敗', 'Upload failed'],
        '请先登录': ['請先登入', 'Please login first'],
            '已关注': ['已關注', 'Followed'],
            '关注': ['關注', 'Follow'],
        '图片不能超过5M': ['圖片不能超過5M', 'Image must be under 5M'],
        '邀请码已复制': ['邀請碼已複製', 'Invite code copied'],
        /* 补充：toast/confirm 文案 */
        '加载中': ['載入中', 'Loading'],
        '1个月': ['1個月', '1 month'],
        '后': ['後', ' later'],
        '如 TRC20 / ERC20': ['如 TRC20 / ERC20', 'e.g. TRC20 / ERC20'],
        '加载失败，请重试': ['載入失敗，請重試', 'Loading failed, please retry'],
        '网络异常，请重试': ['網路異常，請重試', 'Network error, please retry'],
        '复制失败，请手动复制': ['複製失敗，請手動複製', 'Copy failed, please copy manually'],
        '链接已复制': ['連結已複製', 'Link copied'],
        '物流单号已复制': ['物流單號已複製', 'Tracking number copied'],
        '已取消': ['已取消', 'Cancelled'],
        '已确认收货': ['已確認收貨', 'Receipt confirmed'],
        '支付成功': ['支付成功', 'Payment successful'],
        '请先添加收货地址': ['請先新增收貨地址', 'Please add a shipping address first'],
        '请填写售后理由（至少 5 个字）': ['請填寫售後理由（至少 5 個字）', 'Please enter a reason (at least 5 characters)'],
        '请输入消息内容': ['請輸入訊息內容', 'Please enter a message'],
        '请输入邀请码': ['請輸入邀請碼', 'Please enter an invite code'],
        '请输入验证码': ['請輸入驗證碼', 'Please enter the captcha'],
        '鉴定功能开发中': ['鑑定功能開發中', 'Appraisal feature coming soon'],
        '发送成功': ['發送成功', 'Sent'],

        // 登录 / 注册
        '请输入手机号和密码': ['請輸入手機號和密碼', 'Enter mobile and password'],
        '请输入正确的手机号': ['請輸入正確的手機號', 'Enter a valid mobile number'],
        '密码至少6位': ['密碼至少6位', 'Password must be at least 6 characters'],
        '两次密码不一致': ['兩次密碼不一致', 'Passwords do not match'],
        '请同意发布协议': ['請同意發佈協議', 'Please agree to the publishing agreement'],

        // 用户中心 / 资料
        '请输入昵称': ['請輸入暱稱', 'Enter nickname'],
        '请正确填写密码信息': ['請正確填寫密碼資訊', 'Please fill in password info correctly'],
        '暂未配置客服，请联系平台': ['暫未配置客服，請聯繫平台', 'Customer service not configured yet'],
        '请输入真实姓名': ['請輸入真實姓名', 'Enter your real name'],
        '请输入正确的18位身份证号': ['請輸入正確的18位身份證號', 'Enter a valid 18-digit ID number'],
        '请上传身份证正反面照片': ['請上傳身份證正反面照片', 'Upload both sides of your ID'],

        // 地址
        '请填写完整收货信息': ['請填寫完整收貨資訊', 'Please complete the address info'],
        '手机号格式不正确': ['手機號格式不正確', 'Invalid mobile number'],
        '已设为默认地址': ['已設為默認地址', 'Set as default'],
        '确定删除该地址吗？': ['確定刪除該地址嗎？', 'Delete this address?'],
        '删除地址': ['刪除地址', 'Delete Address'],

        // 充值 / 提现
        '请输入充值金额': ['請輸入充值金額', 'Enter recharge amount'],
        '请输入提现金额': ['請輸入提現金額', 'Enter withdraw amount'],
        '提现申请已提交': ['提現申請已提交', 'Withdrawal submitted'],
        '您有一笔提现正在审核中，审核通过后方可提交下一笔': ['您有一筆提現正在審核中，審核通過後方可提交下一筆', 'A withdrawal is under review'],
        '微信': ['微信', 'WeChat'],
        '支付宝': ['支付寶', 'Alipay'],
        '银行卡': ['銀行卡', 'Bank Card'],
        '虚拟货币': ['虛擬貨幣', 'Crypto'],
        '已绑定': ['已綁定', 'Bound'],
        '银行：': ['銀行：', 'Bank: '],
        '网络：': ['網路：', 'Network: '],
        '提交提现申请': ['提交提現申請', 'Submit Withdrawal'],
        '审核中，暂不可申请': ['審核中，暫不可申請', 'Reviewing, not submittable'],
        '去绑定': ['去綁定', 'Bind'],
        '绑定': ['綁定', 'Bind '],
        '账号': ['帳號', 'Account'],
        '请输入收款账号': ['請輸入收款帳號', 'Enter account number'],
        '银行名称': ['銀行名稱', 'Bank Name'],
        '如 中国工商银行': ['如 中國工商銀行', 'e.g. ICBC'],
        '钱包地址': ['錢包地址', 'Wallet Address'],
        '请输入USDT钱包地址': ['請輸入USDT錢包地址', 'Enter USDT wallet address'],
        '网络（链）': ['網路（鏈）', 'Network (Chain)'],
        '加载更多': ['加載更多', 'Load more'],

        // 商品发布 / 管理
        '请输入拍品名称': ['請輸入拍品名稱', 'Enter item name'],
        '请选择分类': ['請選擇分類', 'Select category'],
        '请输入起拍价': ['請輸入起拍價', 'Enter starting price'],
        '请输入加价幅度': ['請輸入加價幅度', 'Enter bid increment'],
        '请选择截拍时间': ['請選擇截拍時間', 'Choose an end time'],
        '请至少上传4张不同角度的商品照片': ['請至少上傳4張不同角度的商品照片', 'Upload at least 4 photos'],
        '发布成功': ['發佈成功', 'Published successfully'],
        '截拍时间已设为': ['截拍時間已設為', 'End time set to '],
        '1个月后': ['1個月後', '1 month later'],
        '天后': ['天後', ' days later'],

        // 卖家
        '请输入店铺名称': ['請輸入店鋪名稱', 'Enter shop name'],
        '请输入企业名称': ['請輸入企業名稱', 'Enter company name'],
        '请上传企业资料（营业执照/资质证明）': ['請上傳企業資料（營業執照/資質證明）', 'Upload company docs (license/certificates)'],
        '申请已提交': ['申請已提交', 'Application submitted'],
        '已上架': ['已上架', 'Listed'],
        '已下架': ['已下架', 'Taken down'],
        '已删除': ['已刪除', 'Deleted'],
        '已重新上架': ['已重新上架', 'Relisted'],
        '已标记发货': ['已標記發貨', 'Marked as shipped'],
        '请选择结束时间': ['請選擇結束時間', 'Please choose an end time'],
        '结束时间需晚于当前时间': ['結束時間需晚於當前時間', 'End time must be later than now'],

        // 订单
        '确定已发货？': ['確定已發貨？', 'Confirm shipment?'],
        '确定要取消该订单吗？': ['確定要取消該訂單嗎？', 'Cancel this order?'],
        '确定已收到商品？': ['確定已收到商品？', 'Confirm receipt?'],
        '确认提交售后申请？提交后订单将转为售后单，等待平台处理。': ['確認提交售後申請？提交後訂單將轉為售後單，等待平台處理。', 'Submit the after-sale request?'],

        // 分段（用于 JS 拼接字符串）
        '企业资料最多上传': ['企業資料最多上傳', 'Upload at most '],
        '张图片': ['張圖片', ' images'],
        '出价不能低于 ': ['出價不能低於 ', 'Bid cannot be lower than '],
        ' 元': [' 元', ' yuan'],
        '出价必须按加价幅度 ': ['出價必須按加價幅度 ', 'Bids must increase by '],
        ' 元递增（最低 ': [' 元遞增（最低 ', ' yuan in steps (min '],
        ' 元）': [' 元）', ' yuan)'],
        '拍卖中，当前价 ¥': ['拍賣中，當前價 ¥', 'On auction, current price ¥'],

        // 倒计时（JS startCountdown 使用）
        '天': ['天', 'd'],
        '时': ['時', 'h'],
        '分': ['分', 'm'],
        '秒': ['秒', 's'],
        '日': ['日', 'd'],
        '已结束': ['已結束', 'Ended'],

        // 语言名称
        '简体中文': ['简体中文', '简体中文'],
        '繁體中文': ['繁體中文', '繁體中文'],
        'English': ['English', 'English']
    };

    /* ---------- 翻译函数 ---------- */
    function t(str) {
        if (!str || LANG === 'zh-cn') return str;
        var hit = DICT[str];
        return hit ? (LANG === 'zh-tw' ? hit[0] : hit[1]) : str;
    }

    function segT(s) {
        var full = t(s);
        if (full !== s) return full;
        return s.replace(/[\u4e00-\u9fa5][\u4e00-\u9fa5，。：、（）¥·\s]*/g, function (m) {
            var key = m.replace(/\s+$/, '');
            var tv = t(key);
            return tv === key ? m : tv + m.slice(key.length);
        });
    }

    /* ---------- 语言切换（登录页右上角） ---------- */
    function initLangSwitcher() {
        // 登录页保持原有行为；其他页面只要放了 #langMount 挂载点就注入
        var mount = document.getElementById('langMount');
        if (!mount && location.pathname.indexOf('/user/login') === -1) return;
        var names = { 'zh-cn': '简', 'zh-tw': '繁', 'en-us': 'EN' };
        var labels = { 'zh-cn': '简体中文', 'zh-tw': '繁體中文', 'en-us': 'English' };
        var order = ['zh-tw', 'zh-cn', 'en-us'];
        if (!document.getElementById('lsStyle')) {
            var st = document.createElement('style');
            st.id = 'lsStyle';
            st.textContent = '.ls-wrap{position:fixed;top:10px;right:12px;z-index:999;font-size:12px;}.ls-wrap.in-hd{position:static;margin-left:8px;}.ls-wrap.in-mount{position:relative;top:auto;right:auto;flex-shrink:0;}.ls-btn{display:inline-flex;align-items:center;gap:3px;padding:3px 10px;border:1px solid rgba(255,255,255,.75);border-radius:14px;color:#fff;background:rgba(0,0,0,.28);line-height:1.6;cursor:pointer;-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);}.ls-wrap.in-hd .ls-btn{color:#666;background:#f5f5f5;border-color:#ddd;}.ls-menu{position:absolute;top:32px;right:0;background:#fff;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.15);min-width:96px;overflow:hidden;display:none;z-index:999;}.ls-wrap.in-hd .ls-menu{top:30px;}.ls-menu a{display:block;padding:9px 14px;color:#333;font-size:13px;text-decoration:none;border-bottom:1px solid #f5f5f5;text-align:left;}.ls-menu a:last-child{border-bottom:none;}.ls-menu a.on{color:#E4393C;font-weight:600;}.ls-menu a:active{background:#fafafa;}';
            document.head.appendChild(st);
        }
        var box = document.createElement('div');
        box.className = 'ls-wrap';
        var cur = LANG;
        box.innerHTML = '<span class="ls-btn">' + (names[cur] || '繁') + '<i style="font-style:normal;font-size:9px;">▾</i></span><div class="ls-menu"></div>';
        var menu = box.querySelector('.ls-menu');
        for (var i = 0; i < order.length; i++) {
            var l = order[i];
            var a = document.createElement('a');
            a.textContent = labels[l] || l;
            if (l === cur) a.className = 'on';
            a.href = 'javascript:void(0)';
            (function (lang) {
                a.onclick = function (e) {
                    e.stopPropagation();
                    setLang(lang);
                };
            })(l);
            menu.appendChild(a);
        }
        box.querySelector('.ls-btn').onclick = function (e) {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        };
        document.addEventListener('click', function () { menu.style.display = 'none'; });
        var hd = document.querySelector('.hd');
        if (mount) {
            box.classList.add('in-mount');
            mount.appendChild(box);
        } else if (hd) {
            box.classList.add('in-hd');
            hd.appendChild(box);
        } else {
            document.body.appendChild(box);
        }
    }

    /* ---------- 语言设置 ---------- */
    function setLang(lang) {
        if (['zh-cn', 'zh-tw', 'en-us'].indexOf(lang) < 0) return;
        w.localStorage.setItem(LS_KEY, lang);
        document.cookie = COOKIE_KEY + '=' + lang + '; path=/; max-age=' + (365 * 86400);
        // 使用 URL 参数 ?lang= 切换（更可靠，PHP 直接识别）
        var url = new URL(location.href);
        url.searchParams.set('lang', lang);
        location.href = url.href;
    }
    function getLang() { return LANG; }

    /* ---------- 初始化 ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        initLangSwitcher();
    });

    w.t = t;
    w.segT = segT;
    w.getLang = getLang;
    w.setLang = setLang;
})(window);
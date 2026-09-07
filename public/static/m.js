/* 移动端公共 JS */
// Toast（自动按当前语言翻译）
// 页面正在离开（点链接跳走）时，被中断的 XHR 会触发 onerror → toast；这时不该弹，否则返回（bfcache 恢复）时会看到「网络异常」
window.__pageLeaving = false;
window.addEventListener('pagehide', function () { window.__pageLeaving = true; });
window.addEventListener('beforeunload', function () { window.__pageLeaving = true; });
window.addEventListener('pageshow', function (e) {
    window.__pageLeaving = false;
    // 从后退缓存恢复的页面直接重新加载：避免恢复态下抽屉/提示残留，也避免浏览器或调试工具读取不到页面计时对象（startTime）而报错，同时保证返回后数据是最新的
    if (e.persisted) { location.reload(); return; }
    // 从后退缓存恢复：收起残留的 toast / 卖家抽屉 / 滚动锁
    var tt = document.getElementById('toast'); if (tt) tt.style.display = 'none';
    if (e.persisted && typeof toggleSellerDrawer === 'function') toggleSellerDrawer(false, true);
    document.body.style.overflow = '';
});
function toast(msg, ms) {
    if (window.__pageLeaving) return;
    if (typeof segT === 'function') msg = segT(msg);
    else if (typeof t === 'function') msg = t(msg);
    var el = document.getElementById('toast');
    if (!el) {
        el = document.createElement('div');
        el.id = 'toast';
        document.body.appendChild(el);
    }
    el.textContent = msg;
    el.style.display = 'block';
    clearTimeout(el._timer);
    el._timer = setTimeout(function () { el.style.display = 'none'; }, ms || 1800);
}
// 确认框
function confirmBox(msg, title, okCb) {
    if (typeof segT === 'function') msg = segT(msg);
    else if (typeof t === 'function') msg = t(msg);
    if (title && typeof t === 'function') title = t(title);
    var m = document.getElementById('confirm-mask');
    if (!m) {
        m = document.createElement('div');
        m.id = 'confirm-mask';
        m.innerHTML = '<div class="c-box"><div class="c-title">' + (title || (typeof t === 'function' ? t('提示') : '提示')) + '</div><div class="c-msg"></div><div class="c-btns"><button class="c-no">' + (typeof t === 'function' ? t('取消') : '取消') + '</button><button class="c-yes">' + (typeof t === 'function' ? t('确定') : '确定') + '</button></div></div>';
        document.body.appendChild(m);
    }
    m.querySelector('.c-msg').textContent = msg || '';
    m.classList.add('show');
    m.querySelector('.c-no').onclick = function () { m.classList.remove('show'); };
    m.querySelector('.c-yes').onclick = function () { m.classList.remove('show'); if (okCb) okCb(); };
}
// AJAX
function ajaxGet(url, cb, errMsg) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (cb) cb(res);
        })
        .catch(function () { toast(errMsg || '网络错误，请重试'); });
}
function ajaxPost(url, data, cb, errMsg) {
    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (cb) cb(res);
        })
        .catch(function () { toast(errMsg || '网络错误，请重试'); });
}
// 倒计时（通用：el 元素，endTime 秒时间戳，format 模式 home/detail）
function startCountdown(el, endTime, format) {
    if (!el || !endTime) return;
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tr(s) { return typeof t === 'function' ? t(s) : s; }
    var D = tr('天'), H = tr('时'), M = tr('分'), S = tr('秒'), R = tr('日'), END = tr('已结束');
    function tick() {
        var diff = endTime - Math.floor(Date.now() / 1000);
        if (diff <= 0) {
            el.textContent = END;
            el.classList.add('done');
            clearInterval(el._timer);
            return;
        }
        var d = Math.floor(diff / 86400), h = Math.floor(diff % 86400 / 3600), m = Math.floor(diff % 3600 / 60), s = diff % 60;
        if (format === 'detail') {
            el.textContent = (d > 0 ? d + D : '') + pad(h) + H + pad(m) + M + pad(s) + S;
        } else if (format === 'short') {
            el.textContent = d > 0 ? (d + R) : (h > 0 ? (h + H) : (m + M));
        } else {
            el.textContent = (d > 0 ? d + D : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
        }
    }
    tick();
    el._timer = setInterval(tick, 1000);
}
// 启动所有倒计时
function initCountdowns() {
    document.querySelectorAll('[data-countdown]').forEach(function (el) {
        startCountdown(el, parseInt(el.getAttribute('data-countdown')), el.getAttribute('data-format') || '');
    });
}
// 格式化金额（千分位）
function fmtMoney(n) {
    n = parseFloat(n);
    if (isNaN(n)) n = 0;
    var s = n.toFixed(2);
    var arr = s.split('.');
    var int = arr[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return int + '.' + arr[1];
}
// 原生 confirm/alert 文本翻译（仅翻译，行为不变）
(function () {
    if (typeof t !== 'function') return;
    var _c = window.confirm, _a = window.alert;
    window.confirm = function (m) { return _c(t(m)); };
    window.alert = function (m) { return _a(t(m)); };
})();
document.addEventListener('DOMContentLoaded', initCountdowns);


/**
 * 上拉自动加载更多：容器内出现 .load-more 且滚入视口底部附近时自动触发 loader()
 * loader 需自行处理 loading 状态与节点替换；容器内容整块替换后仍然有效（每次滚动重新查找）
 */
function autoLoadMore(wrap, loader) {
    if (!wrap) return;
    var ticking = false;
    function check() {
        ticking = false;
        var el = wrap.querySelector('.load-more');
        if (!el || el.classList.contains('loading')) return;
        var vh = window.innerHeight || document.documentElement.clientHeight;
        if (el.getBoundingClientRect().top < vh + 120) {
            el.classList.add('loading');
            loader();
        }
    }
    // 用 setTimeout 而不是 requestAnimationFrame：后台标签页里 rAF 不会执行，setTimeout 仍会
    function onScroll() { if (!ticking) { ticking = true; setTimeout(check, 60); } }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    // 内容变化（切换分类/追加完成）后再查一次：一屏装不下时继续拉
    if (window.MutationObserver) new MutationObserver(onScroll).observe(wrap, { childList: true, subtree: true });
    onScroll();
    return check;
}

/** 卖家左侧抽屉开关 */
var _sellerDrawerOpenedAt = 0;
function toggleSellerDrawer(open, force) {
    var d = document.getElementById('sdrawer'), m = document.getElementById('sdrawerMask');
    if (!d || !m) return;
    if (!open && !force && Date.now() - _sellerDrawerOpenedAt < 400) return; // 刚打开就收到的关闭（触摸后补发的 click）忽略
    if (open) _sellerDrawerOpenedAt = Date.now();
    d.classList.toggle('show', !!open);
    m.classList.toggle('show', !!open);
    document.body.style.overflow = open ? 'hidden' : '';
}

/**
 * 卖家菜单悬浮按钮：可拖动、松手贴边、位置记住（localStorage）、轻点打开抽屉
 * 按钮只在卖家登录时由模板输出，退出登录后页面上不再有该节点
 */
(function () {
    function init() {
        var fab = document.getElementById('sellerFab');
        if (!fab) return;
        var KEY = 'seller_fab_pos', MARGIN = 8, TAP_MOVE = 6;
        var size = function () { return { w: fab.offsetWidth || 40, h: fab.offsetHeight || 40 }; };
        var vh = function () { return window.innerHeight || document.documentElement.clientHeight; };
        // PC 上页面收成居中一列（.app），按钮活动范围限制在这一列内；手机上就是整个视口
        var col = function () {
            var a = document.querySelector('.app'), w = window.innerWidth || document.documentElement.clientWidth;
            if (!a) return { l: 0, r: w };
            var r = a.getBoundingClientRect();
            return (r.width > 0 && r.width < w - 1) ? { l: r.left, r: r.right } : { l: 0, r: w };
        };

        function clamp(x, y) {
            var s = size();
            var c = col();
            x = Math.max(c.l + MARGIN, Math.min(x, c.r - s.w - MARGIN));
            y = Math.max(MARGIN, Math.min(y, vh() - s.h - MARGIN));
            return { x: x, y: y };
        }
        function apply(x, y) {
            fab.style.right = 'auto';
            fab.style.left = x + 'px';
            fab.style.top = y + 'px';
        }
        function snap(x, y) {
            // 松手后贴到左右最近的一侧，避免停在页面中间挡内容
            var s = size();
            var p = clamp(x, y);
            var c = col();
            var side = (p.x + s.w / 2) < (c.l + c.r) / 2 ? 'l' : 'r';
            p.x = side === 'l' ? c.l + MARGIN : c.r - s.w - MARGIN;
            apply(p.x, p.y);
            try { localStorage.setItem(KEY, JSON.stringify({ side: side, y: p.y / vh() })); } catch (e) {}
        }
        function restore() {
            var saved = null;
            try { saved = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) {}
            if (!saved || typeof saved.y !== 'number') return;
            var s = size();
            var y = clamp(0, saved.y * vh()).y;
            var c = col();
            apply(saved.side === 'l' ? c.l + MARGIN : c.r - s.w - MARGIN, y);
        }

        var drag = null;
        function onDown(e) {
            var pt = e.touches ? e.touches[0] : e;
            var r = fab.getBoundingClientRect();
            drag = { sx: pt.clientX, sy: pt.clientY, ox: r.left, oy: r.top, moved: false };
            fab.classList.add('dragging');
            if (!e.touches) e.preventDefault();
        }
        function onMove(e) {
            if (!drag) return;
            var pt = e.touches ? e.touches[0] : e;
            var dx = pt.clientX - drag.sx, dy = pt.clientY - drag.sy;
            if (!drag.moved && Math.abs(dx) < TAP_MOVE && Math.abs(dy) < TAP_MOVE) return;
            drag.moved = true;
            var p = clamp(drag.ox + dx, drag.oy + dy);
            apply(p.x, p.y);
            if (e.cancelable) e.preventDefault();
        }
        function onUp() {
            if (!drag) return;
            fab.classList.remove('dragging');
            var r = fab.getBoundingClientRect();
            if (drag.moved) {
                snap(r.left, r.top);
            } else if (typeof toggleSellerDrawer === 'function') {
                toggleSellerDrawer(true);   // 轻点：打开抽屉
            }
            drag = null;
        }

        fab.addEventListener('touchstart', onDown, { passive: true });
        fab.addEventListener('touchmove', onMove, { passive: false });
        fab.addEventListener('touchend', function (e) { onUp(); if (e.cancelable) e.preventDefault(); }, { passive: false });
        fab.addEventListener('touchcancel', onUp);
        fab.addEventListener('mousedown', onDown);
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
        fab.addEventListener('click', function (e) { e.preventDefault(); }); // 打开由 onUp 统一处理，避免拖完误触
        window.addEventListener('resize', function () {
            var r = fab.getBoundingClientRect();
            if (fab.style.left) snap(r.left, r.top);
        });
        restore();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

/**
 * 页面跳转等待层：点击站内链接 / 返回按钮后立即显示转圈，新页面加载出来后随页面一起消失
 * - 只对同源、非新窗口、非 javascript:、非锚点、未按修饰键的链接生效
 * - 超时兜底（8s）与页面恢复（pageshow）时自动隐藏，避免跳转被取消后一直遮着
 */
(function () {
    var box = null, timer = null;
    function el() {
        if (box) return box;
        box = document.createElement('div');
        box.className = 'page-loading';
        box.innerHTML = '<div class="pl-spin"></div><div class="pl-txt">' + (typeof t === 'function' ? t('加载中...') : '加载中...') + '</div>';
        document.body.appendChild(box);
        return box;
    }
    function show() {
        el().classList.add('show');
        clearTimeout(timer);
        timer = setTimeout(hide, 8000);
    }
    function hide() {
        if (box) box.classList.remove('show');
        clearTimeout(timer);
    }
    window.showPageLoading = show;
    window.hidePageLoading = hide;

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest ? e.target.closest('a[href]') : null;
        if (!a) return;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || /^\s*javascript:/i.test(href) || a.target === '_blank' || a.hasAttribute('download')) return;
        if (a.origin && a.origin !== location.origin) return;
        // 仅锚点变化（同页）不显示
        if (a.pathname === location.pathname && a.search === location.search && a.hash) return;
        show();
    }, true);

    // 头部返回按钮（onclick 里走 history.back）
    document.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('.hd .back') : null;
        if (b) show();
    }, true);

    window.addEventListener('pageshow', hide);
    window.addEventListener('pagehide', function () { clearTimeout(timer); });
})();

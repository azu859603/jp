/* 移动端公共 JS */
// Toast（自动按当前语言翻译）
function toast(msg, ms) {
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


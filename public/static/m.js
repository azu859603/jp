/* 移动端公共 JS */
// Toast
function toast(msg, ms) {
    var t = document.getElementById('toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.display = 'block';
    clearTimeout(t._timer);
    t._timer = setTimeout(function () { t.style.display = 'none'; }, ms || 1800);
}
// 确认框
function confirmBox(msg, title, okCb) {
    var m = document.getElementById('confirm-mask');
    if (!m) {
        m = document.createElement('div');
        m.id = 'confirm-mask';
        m.innerHTML = '<div class="c-box"><div class="c-title">' + (title || '提示') + '</div><div class="c-msg"></div><div class="c-btns"><button class="c-no">取消</button><button class="c-yes">确定</button></div></div>';
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
        .then(function (res) { cb(res); })
        .catch(function () { toast(errMsg || '网络错误，请重试'); });
}
function ajaxPost(url, data, cb, errMsg) {
    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
        .then(function (r) { return r.json(); })
        .then(function (res) { cb(res); })
        .catch(function () { toast(errMsg || '网络错误，请重试'); });
}
// 倒计时（通用：el 元素，endTime 秒时间戳，format 模式 home/detail）
function startCountdown(el, endTime, format) {
    if (!el || !endTime) return;
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tick() {
        var diff = endTime - Math.floor(Date.now() / 1000);
        if (diff <= 0) {
            el.textContent = (format === 'detail') ? '已结束' : '已结束';
            el.classList.add('done');
            clearInterval(el._timer);
            return;
        }
        var d = Math.floor(diff / 86400), h = Math.floor(diff % 86400 / 3600), m = Math.floor(diff % 3600 / 60), s = diff % 60;
        if (format === 'detail') {
            el.textContent = (d > 0 ? d + '天' : '') + pad(h) + '时' + pad(m) + '分' + pad(s) + '秒';
        } else if (format === 'short') {
            el.textContent = d > 0 ? (d + '日') : (h > 0 ? (h + '时') : (m + '分'));
        } else {
            el.textContent = (d > 0 ? d + '天' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
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
document.addEventListener('DOMContentLoaded', initCountdowns);

(() => {
    'use strict';
    let busy = false, view = 'scene', deadline = 0, expiredSent = false;
    const app = document.getElementById('flash-app');
    const message = document.getElementById('flash-connection');
    function setView(next) {
        view = next;
        app.querySelectorAll('[data-flash-view]').forEach(el => { el.hidden = el.dataset.flashView !== view; });
        app.querySelectorAll('[data-view]').forEach(el => el.setAttribute('aria-pressed', String(el.dataset.view === view)));
    }
    function clock() {
        const timer = app.querySelector('[data-remaining]');
        if (!timer) return;
        const left = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
        timer.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        timer.classList.toggle('urgent', left < 600);
        if (!left && !expiredSent && !busy) { expiredSent = true; send('status'); }
    }
    function hydrate() {
        const timer = app.querySelector('[data-remaining]');
        if (timer) deadline = performance.now() + Number(timer.dataset.remaining) * 1000;
        const priority = app.querySelector('[data-priority]');
        setView(priority ? priority.dataset.priority : view);
        clock();
    }
    async function send(action, arg = '', extra = '') {
        const state = app.querySelector('[data-csrf]');
        if (busy || !state) return;
        busy = true;
        const body = new URLSearchParams({ command: 'flash_' + action, arg, extra, flash_csrf: state.dataset.csrf, flash_rev: state.dataset.revision });
        app.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch('command.php', { method: 'POST', body, credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) throw new Error('连接暂时中断。请刷新页面确认刚才的操作。');
            const data = await response.json();
            app.innerHTML = data.html;
            message.textContent = '';
            hydrate();
        } catch (err) {
            message.textContent = '连接暂时中断。请刷新页面确认刚才的操作；重复提交不会重复领奖。';
        } finally { busy = false; app.removeAttribute('aria-busy'); }
    }
    app.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button || button.disabled) return;
        if (button.dataset.view) { setView(button.dataset.view); return; }
        if (button.dataset.action) send(button.dataset.action, button.dataset.arg || '', button.dataset.extra || '');
    });
    setInterval(clock, 1000);
    // 倒计时到零时同步服务端；操作本身也会核对截止时间。
    hydrate();
})();

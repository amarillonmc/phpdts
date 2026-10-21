(function () {
    'use strict';
    let busy = false;
    let pollController = null;
    let serverAt = 0;
    let receivedAt = Date.now();
    const drafts = new Map();
    const app = () => document.getElementById('another-app');
    const notice = (text, error) => {
        const box = document.getElementById('an-network');
        if (!box) return;
        box.textContent = text || '';
        box.classList.toggle('an-error', !!error);
        box.classList.toggle('an-network-active', !!text);
    };
    function resetClock() {
        if (!app()) return;
        serverAt = Number(app().dataset.serverNow) || Math.floor(Date.now() / 1000);
        receivedAt = Date.now();
    }
    function formatTime(seconds) {
        seconds = Math.max(0, Math.floor(seconds));
        return String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
    }
    function updateClocks() {
        const root = app();
        if (!root || root.dataset.ended === '1') return;
        const passed = (Date.now() - receivedAt) / 1000;
        root.querySelectorAll('[data-countdown]').forEach(el => { el.textContent = formatTime(Number(el.dataset.countdown) - serverAt - passed); });
        if (root.dataset.ended !== '1') root.querySelectorAll('[data-elapsed]').forEach(el => { el.textContent = formatTime(Number(el.dataset.elapsed) + passed); });
    }
    function formKey(form) {
        return Array.from(form.querySelectorAll('input[type="hidden"]')).filter(el => el.name !== 'seq' && el.name !== 'csrf').map(el => el.name + '=' + el.value).join('&');
    }
    function restoreDrafts() {
        document.querySelectorAll('form[data-another-action]').forEach(form => {
            const values = drafts.get(formKey(form));
            if (!values) return;
            form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(el => { if (Object.prototype.hasOwnProperty.call(values, el.name)) el.value = values[el.name]; });
        });
    }
    function render(data) {
        if (data.url) { window.location.assign(data.url); return; }
        if (typeof data.html !== 'string') throw new Error(data.error || '服务器没有返回完整画面，请刷新后重试。');
        const opened = new Map();
        document.querySelectorAll('details[id]').forEach(el => opened.set(el.id, el.open));
        const scroll = window.scrollY;
        const current = app();
        if (!current) return;
        const holder = document.createElement('template');
        holder.innerHTML = data.html.trim();
        const next = holder.content.querySelector('#another-app');
        if (!next) throw new Error('画面载入失败，请刷新后重试。');
        current.replaceWith(next);
        if (data.seq !== undefined) next.dataset.seq = String(data.seq);
        if (data.csrf) next.dataset.csrf = data.csrf;
        document.querySelectorAll('details[id]').forEach(el => { if (opened.has(el.id)) el.open = opened.get(el.id); });
        restoreDrafts(); resetClock(); updateClocks(); updateBuilds();
        window.scrollTo(0, scroll);
    }
    async function readResponse(response) {
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); } catch (_) { throw new Error('连接没有返回游戏画面。请刷新页面检查登录状态。'); }
        if (!response.ok && !data.html) throw new Error(data.error || '请求失败，请稍后重试。');
        return data;
    }
    async function send(formData, key) {
        if (busy || !app()) return;
        busy = true;
        if (pollController) { pollController.abort(); pollController = null; }
        formData.set('seq', app().dataset.seq);
        formData.set('csrf', app().dataset.csrf);
        const action = formData.get('action');
        const enabled = Array.from(document.querySelectorAll('#another-app button:not(:disabled)'));
        enabled.forEach(button => { button.disabled = true; });
        notice('正在执行……', false);
        try {
            const response = await fetch('command.php', { method: 'POST', body: formData, credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await readResponse(response);
            if (key) drafts.delete(key);
            if (action === 'switch') drafts.clear();
            render(data);
        } catch (error) {
            notice(error.message || '连接失败。不要连续重试；先刷新确认上一次操作结果。', true);
        } finally {
            busy = false;
            enabled.forEach(button => { if (document.contains(button)) button.disabled = false; });
        }
    }
    async function poll() {
        const root = app();
        if (!root || busy || pollController || document.hidden || root.dataset.join === '1' || root.dataset.ended === '1') return;
        const focused = document.activeElement;
        if (focused && /^(INPUT|SELECT|TEXTAREA)$/.test(focused.tagName)) return;
        const controller = new AbortController(); pollController = controller;
        try {
            const response = await fetch('command.php?another_poll=1', { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await readResponse(response);
            if (!busy && pollController === controller) render(data);
        } catch (error) {
            if (error.name !== 'AbortError') notice('暂时没有收到更新。角色仍在场内；网络恢复后会继续同步。', true);
        } finally { if (pollController === controller) pollController = null; }
    }
    function updateBuilds() {
        const form = document.getElementById('an-join');
        if (!form) return;
        const dual = form.querySelector('[name="controls"]:checked').value === '2';
        let allValid = true;
        form.querySelectorAll('[data-build]').forEach(build => {
            const slot = build.dataset.build;
            build.hidden = slot === '2' && !dual;
            let total = 0; const errors = [];
            build.querySelectorAll('.an-tree').forEach(tree => {
                let ordinary = 0; let treeTotal = 0;
                tree.querySelectorAll('[data-talent]').forEach(input => { const value = Number(input.value); treeTotal += value; if (input.dataset.ultimate !== '1') ordinary += value; });
                tree.querySelectorAll('[data-ultimate="1"]').forEach(input => { if (Number(input.value) && ordinary !== 9) errors.push('学习终极前须先投入该树全部9点普通天赋。'); });
                tree.querySelector('[data-tree-budget]').textContent = treeTotal + ' / 10';
                total += treeTotal;
            });
            if (total > 35) errors.unshift('已超出35点预算，请减少配点。');
            const label = build.querySelector('[data-budget]');
            label.textContent = total + ' / 35' + (total < 35 ? ' · 剩余' + (35 - total) : '');
            label.classList.toggle('invalid', errors.length > 0);
            build.querySelector('[data-build-error]').textContent = errors.length ? errors[0] : '';
            if (!build.hidden && errors.length) allValid = false;
        });
        form.querySelector('[type="submit"]').disabled = !allValid;
    }
    function applyPreset(name, target) {
        const build = document.querySelector('[data-build="' + target + '"]');
        if (!build) return;
        const trees = name === 'support' ? ['P', 'C', 'I'] : name === 'escape' ? ['A', 'E', 'L'] : name === 'clear' ? [] : ['S', 'E', 'A'];
        const extra = name === 'support' ? { E1: 5 } : name === 'escape' ? { P1: 3, P2: 1, C2: 1 } : name === 'clear' ? {} : { P1: 3, P2: 1, L4: 1 };
        build.querySelectorAll('[data-talent]').forEach(input => {
            input.value = trees.includes(input.dataset.tree) ? input.options[input.options.length - 1].value : String(extra[input.dataset.talent] || 0);
        });
        updateBuilds();
    }
    document.addEventListener('submit', event => {
        const form = event.target;
        if (form.matches('[data-another-action]')) { event.preventDefault(); send(new FormData(form), formKey(form)); }
        if (form.id === 'an-join') { updateBuilds(); if (form.querySelector('[type="submit"]').disabled) event.preventDefault(); }
    });
    document.addEventListener('click', event => {
        const preset = event.target.closest('[data-preset]');
        if (preset) { applyPreset(preset.dataset.preset, preset.dataset.buildTarget); return; }
        const destination = event.target.closest('[data-another-move]');
        if (destination) { const data = new FormData(); data.set('action', 'move'); data.set('to', destination.dataset.anotherMove); send(data); }
    });
    document.addEventListener('keydown', event => {
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-another-move]')) { event.preventDefault(); event.target.dispatchEvent(new MouseEvent('click', { bubbles: true })); }
    });
    document.addEventListener('input', event => {
        const form = event.target.closest('form[data-another-action]');
        if (form && event.target.name) { const key = formKey(form); const values = drafts.get(key) || {}; values[event.target.name] = event.target.value; drafts.set(key, values); }
        if (event.target.closest('#an-join')) updateBuilds();
    });
    document.addEventListener('change', event => { if (event.target.closest('#an-join')) updateBuilds(); });
    resetClock(); updateClocks(); updateBuilds();
    window.setInterval(updateClocks, 250);
    window.setInterval(poll, 2000);
}());

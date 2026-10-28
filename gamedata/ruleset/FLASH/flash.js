(function () {
    'use strict';
    if (window.flashUI) return;
    window.flashUI = true;
    var busy = false, original = window.postCmd, deadline = 0, expiredSent = false;
    var jojoPosition = {x: 20, y: window.innerHeight - 190}, bubbleOpen = false, drag = null, dragged = false;
    try {
        var saved = JSON.parse(localStorage.getItem('phpdts.flash.jojo-position'));
        if (saved && Number.isFinite(saved.x) && Number.isFinite(saved.y)) jojoPosition = saved;
    } catch (error) {}
    function placeJojo() {
        var jojo = document.getElementById('flash-jojo'), bubble = document.getElementById('flash-jojo-bubble');
        if (!jojo || !bubble) { bubbleOpen = false; return; }
        jojoPosition.x = Math.max(0, Math.min(window.innerWidth - 120, jojoPosition.x));
        jojoPosition.y = Math.max(0, Math.min(window.innerHeight - 170, jojoPosition.y));
        jojo.style.left = jojoPosition.x + 'px'; jojo.style.top = jojoPosition.y + 'px';
        bubble.hidden = !bubbleOpen;
        document.getElementById('flash-jojo-toggle').setAttribute('aria-expanded', String(bubbleOpen));
        if (bubbleOpen) {
            var x = jojoPosition.x < window.innerWidth / 2 ? jojoPosition.x + 100 : jojoPosition.x - bubble.offsetWidth + 20;
            var y = jojoPosition.y - bubble.offsetHeight + 35;
            bubble.style.left = Math.max(8, Math.min(window.innerWidth - bubble.offsetWidth - 8, x)) + 'px';
            bubble.style.top = Math.max(8, Math.min(window.innerHeight - bubble.offsetHeight - 8, y)) + 'px';
        }
    }
    function saveJojoPosition() {
        try { localStorage.setItem('phpdts.flash.jojo-position', JSON.stringify(jojoPosition)); } catch (error) {}
    }
    function clock() {
        var timer = document.getElementById('flash-clock');
        if (!timer) return;
        var left = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
        timer.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        if (!left && !busy && !expiredSent) {
            expiredSent = true;
            postCmd('gamecmd', 'command.php', {data: {command: 'flash_status'}});
        }
    }
    function refresh() {
        var timer = document.getElementById('flash-clock');
        if (timer) deadline = performance.now() + Number(timer.dataset.remaining) * 1000;
        // FLASH 的姿态随行动/同行策略切换，称号仍由本模式的成长流程决定。
        ['pose', 'tactic', 'clubsel'].forEach(function (id) {
            var select = document.getElementById(id);
            if (select) select.disabled = true;
        });
        // 原生子菜单有些只有 name；资料区和地图按钮仍使用 id 查找 mode。
        var form = document.getElementById('gamecmd');
        if (form && !document.getElementById('mode')) {
            var mode = form.querySelector('[name="mode"]');
            if (mode) mode.id = 'mode';
        }
        [['explore', 'zz'], ['swap', 'cc']].forEach(function (entry) {
            var button = document.querySelector('#cmd [data-action="' + entry[0] + '"]');
            if (button) button.id = entry[1];
        });
        for (var i = 1; i <= 6; i++) {
            var use = document.querySelector('#cmd [data-action="use"][data-arg="' + i + '"]');
            if (use) use.id = String(i) + i;
        }
        document.querySelectorAll('#flash-native-dialogs [name]').forEach(function (input) { input.setAttribute('form', 'gamecmd'); });
        window.flashRenderQuests();
        // 原生背景更新把数字 0 当作空值；回到无月之影时传入地点字符串。
        if (window.shwData && window.shwData.locationId === 0 && typeof updateBackgroundImage === 'function') updateBackgroundImage('0');
        placeJojo();
        clock();
        if (window.shwData && ['skillpage', 'itemmix_tips'].indexOf(window.shwData.opendialog) !== -1) {
            var dialog = document.getElementById(window.shwData.opendialog);
            if (dialog && !dialog.open) showModalDialog(dialog);
        }
    }
    window.flashRenderQuests = function () {
        var source = document.getElementById('flash-quest-source'), target = document.getElementById('quest-list');
        if (!source || !target) return false;
        target.innerHTML = source.innerHTML;
        return true;
    };
    window.postCmd = function (form, url, options) {
        var current = document.getElementById('flash-state');
        if (!current || url.indexOf('command.php') === -1) return original(form, url, options);
        if (busy) return false;
        options = Object.assign({}, options || {});
        options.data = Object.assign({}, options.data || {}, {
            flash_csrf: current.querySelector('[name="flash_csrf"]').value,
            flash_rev: current.dataset.revision
        });
        if (form === 'maincmd') {
            // Nouveau 的 main 位于 gamecmd 内，浏览器会去掉嵌套的 maincmd 表单。
            Object.assign(options.data, {command: 'itemmain', itemcmd: 'itemmix', itemindex: document.getElementById('itemindex').value});
            form = 'gamecmd';
        }
        options.timeout = options.timeout || 15000;
        var opened = {};
        document.querySelectorAll('#cmd details[id],#quest-list details[id]').forEach(function (el) { opened[el.id] = el.open; });
        var success = options.onSuccess, failure = options.onError;
        options.onSuccess = function () {
            busy = false;
            refresh();
            Object.keys(opened).forEach(function (id) { var el = document.getElementById(id); if (el) el.open = opened[id]; });
            if (success) success();
        };
        options.onError = function (err) {
            busy = false;
            if (failure) failure(err);
            else showNotice(err.message + ' 请刷新页面确认刚才的操作。');
        };
        busy = true;
        var sent = original(form, url, options);
        if (sent === false) busy = false;
        return sent;
    };
    document.addEventListener('click', function (event) {
        if (event.target.closest('#flash-jojo-toggle')) {
            if (dragged) { dragged = false; return; }
            bubbleOpen = !bubbleOpen; placeJojo(); return;
        }
        if (event.target.closest('.flash-jojo__close')) { bubbleOpen = false; placeJojo(); return; }
        if (!event.target.closest('#flash-jojo') && bubbleOpen) { bubbleOpen = false; placeJojo(); }
        var quest = event.target.closest('[data-flash-quest-open]');
        if (quest) {
            if (typeof openPanel === 'function') openPanel();
            var tab = document.querySelector('.tab-button');
            if (tab) tab.click();
            window.flashRenderQuests();
            return;
        }
        var button = event.target.closest('[data-action]');
        if (!button || button.disabled || busy || !document.getElementById('flash-state')) return;
        event.preventDefault();
        postCmd('gamecmd', 'command.php', {data: {
            mode: 'command', command: 'flash_' + button.dataset.action,
            arg: button.dataset.arg || '', extra: button.dataset.extra || ''
        }});
    });
    document.addEventListener('pointerdown', function (event) {
        var handle = event.target.closest('#flash-jojo-toggle');
        if (!handle || event.button !== 0) return;
        dragged = false;
        drag = {id: event.pointerId, x: event.clientX, y: event.clientY, left: jojoPosition.x, top: jojoPosition.y};
        handle.setPointerCapture(event.pointerId);
    });
    document.addEventListener('pointermove', function (event) {
        if (!drag || drag.id !== event.pointerId) return;
        var dx = event.clientX - drag.x, dy = event.clientY - drag.y;
        if (Math.abs(dx) + Math.abs(dy) > 5) dragged = true;
        if (dragged) { jojoPosition = {x: drag.left + dx, y: drag.top + dy}; placeJojo(); }
    });
    document.addEventListener('pointerup', function (event) {
        if (!drag || drag.id !== event.pointerId) return;
        if (dragged) saveJojoPosition();
        drag = null;
    });
    document.addEventListener('pointercancel', function () { drag = null; dragged = false; });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { bubbleOpen = false; placeJojo(); }
        if (!event.target.closest('#flash-jojo-toggle')) return;
        var steps = {ArrowLeft: [-10, 0], ArrowRight: [10, 0], ArrowUp: [0, -10], ArrowDown: [0, 10]};
        if (steps[event.key]) {
            event.preventDefault(); jojoPosition.x += steps[event.key][0]; jojoPosition.y += steps[event.key][1]; placeJojo(); saveJojoPosition();
        }
    });
    window.addEventListener('resize', placeJojo);
    refresh();
    window.setInterval(clock, 1000);
    window.setInterval(function () {
        // 等待回复时更新真实命体；不打断商店、合成输入或打开的模态框。
        if (busy || document.hidden || !document.querySelector('#cmd [data-action="explore"]') || document.querySelector('dialog[open]')) return;
        if (document.activeElement && /^(INPUT|SELECT|TEXTAREA)$/.test(document.activeElement.tagName)) return;
        postCmd('gamecmd', 'command.php', {data: {command: 'flash_status'}});
    }, 10000);
}());

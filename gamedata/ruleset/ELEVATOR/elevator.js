(function () {
    'use strict';
    if (window.elevatorUI) return;
    window.elevatorUI = true;
    var busy = false, original = window.postCmd;
    function panel() { return document.querySelector('#elevator-panel .el-panel'); }
    function remember() {
        var values = {}, open = {};
        document.querySelectorAll('#elevator-panel input[id],#elevator-panel select[id]').forEach(function (el) { values[el.id] = el.value; });
        document.querySelectorAll('#elevator-panel details[id]').forEach(function (el) { open[el.id] = el.open; });
        return {values: values, open: open, focus: document.activeElement && document.activeElement.id};
    }
    function restore(state) {
        Object.keys(state.values).forEach(function (id) { var el = document.getElementById(id); if (el) el.value = state.values[id]; });
        Object.keys(state.open).forEach(function (id) { var el = document.getElementById(id); if (el) el.open = state.open[id]; });
        if (state.focus) { var el = document.getElementById(state.focus); if (el) el.focus({preventScroll: true}); }
    }
    window.postCmd = function (form, url, options) {
        var current = panel();
        if (!current || url.indexOf('command.php') === -1) return original(form, url, options);
        if (busy) return false;
        options = Object.assign({}, options || {});
        options.data = Object.assign({}, options.data || {}, {el_csrf: current.dataset.elCsrf, el_seq: current.dataset.elSeq, el_pls: current.dataset.elPls});
        options.timeout = options.timeout || 15000;
        var state = remember(), success = options.onSuccess, failure = options.onError;
        var polling = options.data.command === 'elevator_status';
        options.onSuccess = function () {
            busy = false;
            restore(state);
            if (!polling) {
                var recipes = document.getElementById('el-recipes');
                if (recipes && /^(itm|elevator_recipe)/.test(options.data.command || '')) recipes.open = true;
                var nativeMode = document.querySelector('#cmd input[name="mode"]');
                if (nativeMode && /^(revcombat|combat|corpse|itemget)$/.test(nativeMode.value)) {
                    document.getElementById('cmd').scrollIntoView({block: 'center'});
                }
            }
            if (success) success();
        };
        options.onError = function (err) {
            busy = false;
            if (failure) failure(err);
            else if (!polling && typeof showNotice === 'function') showNotice(err.message + ' 请刷新状态后再操作。');
        };
        busy = true;
        var sent = original(form, url, options);
        if (sent === false) busy = false;
        return sent;
    };
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-el-command],[data-el-native]');
        if (!button) return;
        event.preventDefault();
        if (busy) return;
        var args = JSON.parse(button.dataset.elArgs || '{}');
        if (button.dataset.elCommand) {
            var fields = JSON.parse(button.dataset.elFields || '{}');
            Object.keys(fields).forEach(function (name) { var input = document.getElementById(fields[name]); args[name] = input ? input.value : ''; });
            postCmd('gamecmd', 'command.php', {data: {mode: 'command', command: button.dataset.elCommand, el_args: JSON.stringify(args), itemindex: '', sp_cmd: ''}});
        } else {
            args.mode = 'command'; args.command = button.dataset.elNative; args.itemindex = ''; args.sp_cmd = '';
            postCmd('gamecmd', 'command.php', {data: args});
        }
    });
    // 状态请求走原有JSON更新器，和正常指令互斥；不重试任何有副作用的操作。
    window.setInterval(function () {
        if (!panel() || busy || document.hidden) return;
        postCmd('gamecmd', 'command.php', {data: {mode: 'command', command: 'elevator_status'}, bypassDelay: true});
    }, 4000);
    window.setInterval(function () {
        document.querySelectorAll('[data-el-countdown]').forEach(function (el) {
            var left = Math.max(0, Number(el.dataset.elCountdown) - 1);
            el.dataset.elCountdown = String(left); el.textContent = left + ' 秒';
        });
    }, 1000);
}());

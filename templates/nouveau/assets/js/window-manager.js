(function () {
    'use strict';

    var STORAGE_KEY = 'phpdts:nouveau:windows:v2';
    var topZ = 20;

    function isIndexPage() {
        return document.body && document.body.getAttribute('data-nv-script') === 'index';
    }

    function isCompactViewport() {
        return window.matchMedia && window.matchMedia('(max-width: 1100px)').matches;
    }

    function readState() {
        try {
            return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function writeState(state) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // Storage may be disabled in private browsing; dragging still works for the session.
        }
    }

    function windowId(win, index) {
        return win.getAttribute('data-window-id') || win.id || ('nv-window-' + index);
    }

    function windowTitle(win, id) {
        var attr = win.getAttribute('data-window-title');
        if (attr) return attr;
        var title = win.querySelector('.nv-window__title');
        return title ? title.textContent.replace(/\s+/g, ' ').trim() : id;
    }

    function focusWindow(win) {
        Array.prototype.forEach.call(document.querySelectorAll('.nv-window.is-active'), function (active) {
            active.classList.remove('is-active');
        });
        topZ += 1;
        win.style.zIndex = String(topZ);
        win.classList.add('is-active');
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function applyWindowState(win, id, state) {
        var item = state[id];
        win.classList.toggle('is-minimized', Boolean(item && item.minimized));
        if (!item || isCompactViewport()) return;

        if (typeof item.left === 'number' && typeof item.top === 'number') {
            var maxLeft = Math.max(0, window.innerWidth - 180);
            var maxTop = Math.max(0, window.innerHeight - 120);
            win.style.position = 'absolute';
            win.style.left = clamp(item.left, 0, maxLeft) + 'px';
            win.style.top = clamp(item.top, 0, maxTop) + 'px';
            win.style.right = 'auto';
        }
        if (typeof item.width === 'number' && typeof item.height === 'number') {
            win.style.width = Math.max(220, item.width) + 'px';
            win.style.height = Math.max(110, item.height) + 'px';
            win.classList.add('is-resized');
        }
    }

    function saveWindowPatch(id, patch) {
        var state = readState();
        var next = state[id] || {};
        Object.keys(patch).forEach(function (key) {
            next[key] = patch[key];
        });
        state[id] = next;
        writeState(state);
    }

    function setMinimized(win, id, minimized) {
        if (isIndexPage()) return;
        win.classList.toggle('is-minimized', minimized);
        saveWindowPatch(id, { minimized: Boolean(minimized) });
        rebuildTaskbar();
        if (!minimized) focusWindow(win);
    }

    function bindDrag(win, id) {
        var handle = win.querySelector('[data-window-drag-handle]');
        if (!handle || handle._nvDragBound) return;
        handle._nvDragBound = true;

        handle.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || isCompactViewport()) return;
            if (event.target.closest('button, a, input, select, textarea, label')) return;

            var parent = win.offsetParent || document.getElementById('nouveau-desktop') || document.body;
            var parentRect = parent.getBoundingClientRect();
            var rect = win.getBoundingClientRect();
            var startX = event.clientX;
            var startY = event.clientY;
            var baseLeft = rect.left - parentRect.left;
            var baseTop = rect.top - parentRect.top;

            focusWindow(win);
            win.style.position = 'absolute';
            win.style.width = rect.width + 'px';
            win.style.left = baseLeft + 'px';
            win.style.top = baseTop + 'px';
            win.style.right = 'auto';

            function onMove(moveEvent) {
                var left = Math.max(0, baseLeft + moveEvent.clientX - startX);
                var top = Math.max(0, baseTop + moveEvent.clientY - startY);
                win.style.left = left + 'px';
                win.style.top = top + 'px';
            }

            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                saveWindowPatch(id, {
                    left: parseInt(win.style.left, 10) || 0,
                    top: parseInt(win.style.top, 10) || 0
                });
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
            event.preventDefault();
        });
    }

    function bindResize(win, id) {
        if (win.getAttribute('data-window-resizable') === 'false') return;
        var resizer = win.querySelector('.nv-window__resize');
        if (!resizer) {
            resizer = document.createElement('div');
            resizer.className = 'nv-window__resize';
            resizer.title = '拖拽调整窗口尺寸';
            win.appendChild(resizer);
        }
        if (resizer._nvResizeBound) return;
        resizer._nvResizeBound = true;

        resizer.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || isCompactViewport()) return;
            var rect = win.getBoundingClientRect();
            var startX = event.clientX;
            var startY = event.clientY;

            focusWindow(win);

            function onMove(moveEvent) {
                var width = Math.max(220, Math.round(rect.width + moveEvent.clientX - startX));
                var height = Math.max(110, Math.round(rect.height + moveEvent.clientY - startY));
                win.style.width = width + 'px';
                win.style.height = height + 'px';
                win.classList.add('is-resized');
            }

            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                saveWindowPatch(id, {
                    width: parseInt(win.style.width, 10) || rect.width,
                    height: parseInt(win.style.height, 10) || rect.height
                });
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
            event.preventDefault();
            event.stopPropagation();
        });
    }

    function rebuildTaskbar() {
        var bar = document.getElementById('nv-taskbar-buttons');
        if (!bar) return;
        bar.textContent = '';
        if (isIndexPage()) return;

        Array.prototype.forEach.call(document.querySelectorAll('.nv-window'), function (win, index) {
            var id = windowId(win, index);
            var btn = document.createElement('button');
            var minimized = win.classList.contains('is-minimized');
            btn.type = 'button';
            btn.className = 'nv-taskbar__btn' + (minimized ? ' is-minimized' : '');
            btn.textContent = windowTitle(win, id);
            btn.title = minimized ? '点击还原窗口' : '点击激活或最小化窗口';
            btn.addEventListener('click', function () {
                if (win.classList.contains('is-minimized')) {
                    setMinimized(win, id, false);
                } else if (win.classList.contains('is-active')) {
                    setMinimized(win, id, true);
                } else {
                    focusWindow(win);
                }
            });
            bar.appendChild(btn);
        });
    }

    function initWindows() {
        if (isIndexPage()) return;
        var state = readState();
        Array.prototype.forEach.call(document.querySelectorAll('.nv-window'), function (win, index) {
            var id = windowId(win, index);
            win.setAttribute('data-window-id', id);
            applyWindowState(win, id, state);
            bindDrag(win, id);
            bindResize(win, id);

            if (!win._nvFocusBound) {
                win._nvFocusBound = true;
                win.addEventListener('mousedown', function () {
                    focusWindow(win);
                });
            }

            Array.prototype.forEach.call(win.querySelectorAll('[data-window-minimize]'), function (btn) {
                if (btn._nvMinBound) return;
                btn._nvMinBound = true;
                btn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setMinimized(win, id, !win.classList.contains('is-minimized'));
                });
            });
        });
        rebuildTaskbar();
    }

    function resetAll() {
        try {
            window.localStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            // Ignore storage errors.
        }
        Array.prototype.forEach.call(document.querySelectorAll('.nv-window'), function (win) {
            win.classList.remove('is-minimized', 'is-resized', 'is-active');
            win.style.left = '';
            win.style.top = '';
            win.style.right = '';
            win.style.width = '';
            win.style.height = '';
            win.style.position = '';
            win.style.zIndex = '';
        });
        rebuildTaskbar();
    }

    window.NouveauWindows = {
        refresh: initWindows,
        resetAll: resetAll
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWindows);
    } else {
        initWindows();
    }
})();

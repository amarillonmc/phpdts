(function () {
    'use strict';

    var storageKey = 'phpdts:nouveau:windows';
    var zBase = 40;
    var state = {};

    function loadState() {
        try {
            state = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (err) {
            state = {};
        }
    }

    function saveState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (err) {
            // localStorage can be unavailable in private contexts.
        }
    }

    function isMobileLayout() {
        return window.matchMedia('(max-width: 900px), (pointer: coarse)').matches;
    }

    function windowId(win, index) {
        if (!win.id) {
            win.id = 'nv-window-' + index;
        }
        return win.id;
    }

    function activate(win) {
        document.querySelectorAll('.nv-window.is-active').forEach(function (node) {
            node.classList.remove('is-active');
        });
        zBase += 1;
        win.style.zIndex = zBase;
        win.classList.add('is-active');
    }

    function minimize(win) {
        var id = win.id;
        win.classList.add('is-minimized');
        state[id] = state[id] || {};
        state[id].minimized = true;
        saveState();
        updateTaskbar();
    }

    function restore(win) {
        var id = win.id;
        win.classList.remove('is-minimized');
        state[id] = state[id] || {};
        state[id].minimized = false;
        saveState();
        activate(win);
        updateTaskbar();
    }

    function updateTaskbar() {
        var holder = document.getElementById('nv-taskbar-buttons');
        if (!holder) {
            return;
        }
        holder.innerHTML = '';
        document.querySelectorAll('.nv-window').forEach(function (win) {
            var title = win.getAttribute('data-window-title');
            var titleNode = win.querySelector('.nv-window__title');
            if (!title && titleNode) {
                title = titleNode.textContent;
            }
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'nv-taskbar-button';
            if (win.classList.contains('is-minimized')) {
                button.className += ' is-minimized';
            }
            button.textContent = title || win.id;
            button.addEventListener('click', function () {
                if (win.classList.contains('is-minimized')) {
                    restore(win);
                } else {
                    activate(win);
                    win.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                }
            });
            holder.appendChild(button);
        });
    }

    function attachDrag(win) {
        var bar = win.querySelector('.nv-window__titlebar');
        if (!bar) {
            return;
        }

        var drag = null;
        bar.addEventListener('pointerdown', function (event) {
            if (isMobileLayout() || event.target.closest('.nv-window__actions')) {
                return;
            }
            activate(win);
            drag = {
                pointerId: event.pointerId,
                x: event.clientX,
                y: event.clientY,
                left: win.offsetLeft,
                top: win.offsetTop
            };
            bar.setPointerCapture(event.pointerId);
        });

        bar.addEventListener('pointermove', function (event) {
            if (!drag || drag.pointerId !== event.pointerId) {
                return;
            }
            var left = Math.max(0, drag.left + event.clientX - drag.x);
            var top = Math.max(0, drag.top + event.clientY - drag.y);
            win.style.left = left + 'px';
            win.style.top = top + 'px';
        });

        function endDrag(event) {
            if (!drag || drag.pointerId !== event.pointerId) {
                return;
            }
            state[win.id] = state[win.id] || {};
            state[win.id].left = win.offsetLeft;
            state[win.id].top = win.offsetTop;
            saveState();
            drag = null;
        }

        bar.addEventListener('pointerup', endDrag);
        bar.addEventListener('pointercancel', endDrag);
    }

    function hydrateWindow(win, index) {
        var id = windowId(win, index);
        var saved = state[id];
        if (saved && !isMobileLayout()) {
            if (typeof saved.left === 'number') {
                win.style.left = saved.left + 'px';
            }
            if (typeof saved.top === 'number') {
                win.style.top = saved.top + 'px';
            }
        }
        if (saved && saved.minimized) {
            win.classList.add('is-minimized');
        }

        win.addEventListener('pointerdown', function () {
            activate(win);
        });

        var minButton = win.querySelector('[data-window-minimize]');
        if (minButton) {
            minButton.addEventListener('click', function (event) {
                event.preventDefault();
                minimize(win);
            });
        }

        attachDrag(win);
    }

    function init() {
        loadState();
        document.querySelectorAll('.nv-window').forEach(hydrateWindow);
        var first = document.querySelector('.nv-window:not(.is-minimized)');
        if (first) {
            activate(first);
        }
        updateTaskbar();
    }

    window.NouveauWindows = {
        init: init,
        restore: restore,
        minimize: minimize,
        updateTaskbar: updateTaskbar
    };

    document.addEventListener('DOMContentLoaded', init);
})();

(function () {
    'use strict';

    function containsId(list, id) {
        if (!Array.isArray(list)) return false;
        var num = Number(id);
        for (var i = 0; i < list.length; i += 1) {
            if (Number(list[i]) === num) return true;
        }
        return false;
    }

    function syncLocationFromAjax() {
        var pls = document.getElementById('pls');
        var weather = document.getElementById('weather');
        var shw = window.shwData || {};

        if (pls) {
            Array.prototype.forEach.call(document.querySelectorAll('[data-nv-pls]'), function (el) {
                el.textContent = pls.textContent;
            });
        }
        if (weather) {
            Array.prototype.forEach.call(document.querySelectorAll('[data-nv-weather]'), function (el) {
                el.textContent = weather.textContent;
            });
        }
        if (typeof shw.locationId === 'number') {
            Array.prototype.forEach.call(document.querySelectorAll('img[data-nv-location-img]'), function (img) {
                var nextSrc = 'img/location/' + shw.locationId + '.jpg';
                if (img.getAttribute('src') !== nextSrc) {
                    img.removeAttribute('data-nv-fallback-applied');
                    img.setAttribute('src', nextSrc);
                }
            });

            Array.prototype.forEach.call(document.querySelectorAll('.nv-map-node[data-pls-id]'), function (node) {
                var key = Number(node.getAttribute('data-pls-id'));
                var isCurrent = (key === Number(shw.locationId));
                var isDanger = containsId(shw.dangerAreas, key);
                var isWarn = !isDanger && containsId(shw.warnAreas, key);

                node.classList.toggle('is-current', isCurrent);
                node.classList.toggle('is-danger', isDanger);
                node.classList.toggle('is-warn', isWarn);

                var coordEl = node.querySelector('.nv-map-node__coord');
                var nameEl = node.querySelector('.nv-map-node__name');
                var coordText = coordEl ? coordEl.textContent.trim() : '';
                var nameText = nameEl ? nameEl.textContent.trim() : '';
                var suffix = isCurrent ? ' (当前位置)' : (isDanger ? ' (禁区)' : (isWarn ? ' (即将成为禁区)' : ' (点击移动)'));
                node.title = (coordText ? '[' + coordText + '] ' : '') + nameText + suffix;
            });
        }
    }

    function moveTo(plsId) {
        var targetId = String(plsId);
        var shw = window.shwData || {};
        var notice = document.getElementById('notice');

        if (typeof shw.locationId === 'number' && Number(shw.locationId) === Number(plsId)) {
            if (notice) notice.textContent = '你已经在该地区了。';
            return false;
        }

        var form = document.getElementById('gamecmd') || document.forms['gamecmd'];
        if (!form || typeof window.postCmd !== 'function') {
            return false;
        }

        var movetoSelect = form.querySelector('select[name="moveto"]');
        if (!movetoSelect) {
            if (notice) notice.textContent = '当前处于事件或子菜单中，请先完成当前指令后再点击地图移动。';
            return false;
        }

        if (notice) notice.textContent = '';

        var modeInput = document.getElementById('mode') || form.elements['mode'];
        if (modeInput) {
            modeInput.value = 'command';
        }

        var moveRadio = document.getElementById('move');
        if (moveRadio) {
            moveRadio.checked = true;
            if (typeof window.sl === 'function') {
                window.sl('move');
            }
        } else {
            var cmdInput = document.getElementById('command') || form.elements['command'];
            if (cmdInput) cmdInput.value = 'move';
        }

        var hasOption = false;
        for (var i = 0; i < movetoSelect.options.length; i += 1) {
            if (String(movetoSelect.options[i].value) === targetId) {
                hasOption = true;
                break;
            }
        }
        if (!hasOption) {
            var opt = document.createElement('option');
            opt.value = targetId;
            opt.textContent = targetId;
            movetoSelect.appendChild(opt);
        }
        movetoSelect.value = targetId;

        window.postCmd('gamecmd', 'command.php');
        movetoSelect.disabled = true;
        return false;
    }

    function refreshAll() {
        syncLocationFromAjax();
        if (window.NouveauWindows && typeof window.NouveauWindows.refresh === 'function') {
            window.NouveauWindows.refresh();
        }
        if (window.NouveauImageFallback && typeof window.NouveauImageFallback.refresh === 'function') {
            window.NouveauImageFallback.refresh();
        }
        if (window.PHPDTSPaperdoll && typeof window.PHPDTSPaperdoll.refresh === 'function') {
            window.PHPDTSPaperdoll.refresh();
        }
    }

    function observeAjaxUpdates() {
        if (typeof window.MutationObserver !== 'function') return;
        ['main', 'cmd', 'log', 'chatlist', 'notice'].forEach(function (id) {
            var target = document.getElementById(id);
            if (!target || target._nvObserved) return;
            target._nvObserved = true;
            var observer = new MutationObserver(function () {
                refreshAll();
            });
            observer.observe(target, { childList: true, subtree: false });
        });
    }

    window.NouveauPanels = {
        refresh: refreshAll,
        moveTo: moveTo
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            refreshAll();
            observeAjaxUpdates();
        });
    } else {
        refreshAll();
        observeAjaxUpdates();
    }
})();

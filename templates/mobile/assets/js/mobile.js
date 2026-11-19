(function () {
    'use strict';

    var THEME_KEY = 'phpdts:mobile:theme:v1';
    var NOUVEAU_THEME_KEY = 'phpdts:nouveau:theme:v1';

    var PRESETS = {
        'aero-emerald': { accent: '#3fe0a8', accent2: '#38c8ff', panel: '#0c151d' },
        'frutiger-aqua': { accent: '#38d6ff', accent2: '#5ce6b8', panel: '#081724' },
        'cobalt-ego': { accent: '#5a9bff', accent2: '#8f7dff', panel: '#0a1224' },
        'amethyst-glass': { accent: '#c07cff', accent2: '#52d1ff', panel: '#150e24' },
        'amber-pulse': { accent: '#f5b942', accent2: '#ff6b4a', panel: '#1c130b' },
        'crimson-core': { accent: '#ff5c7c', accent2: '#ffb347', panel: '#1e0d14' }
    };

    var lastState = null;
    var lastMapSignature = '';

    function hexToRgb(hex, fallback) {
        var clean = String(hex || '').replace('#', '').trim();
        if (!/^[0-9a-fA-F]{6}$/.test(clean)) return fallback;
        return [
            parseInt(clean.slice(0, 2), 16),
            parseInt(clean.slice(2, 4), 16),
            parseInt(clean.slice(4, 6), 16)
        ];
    }

    function rgba(rgb, alpha) {
        return 'rgba(' + rgb[0] + ', ' + rgb[1] + ', ' + rgb[2] + ', ' + alpha + ')';
    }

    function scaleRgb(rgb, factor) {
        return [
            Math.max(0, Math.min(255, Math.round(rgb[0] * factor))),
            Math.max(0, Math.min(255, Math.round(rgb[1] * factor))),
            Math.max(0, Math.min(255, Math.round(rgb[2] * factor)))
        ];
    }

    function rgbToHex(rgb) {
        return '#' + rgb.map(function (v) {
            var s = Math.max(0, Math.min(255, Math.round(v))).toString(16);
            return s.length === 1 ? '0' + s : s;
        }).join('');
    }

    function readTheme() {
        try {
            var raw = window.localStorage.getItem(THEME_KEY) || window.localStorage.getItem(NOUVEAU_THEME_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function writeTheme(cfg) {
        try {
            if (!cfg) {
                window.localStorage.removeItem(THEME_KEY);
            } else {
                window.localStorage.setItem(THEME_KEY, JSON.stringify(cfg));
            }
        } catch (e) {}
    }

    function applyPalette(cfg) {
        var base = PRESETS['aero-emerald'];
        var accentHex = (cfg && cfg.accent) || base.accent;
        var accent2Hex = (cfg && cfg.accent2) || base.accent2;
        var panelHex = (cfg && cfg.panel) || base.panel;

        var accentRgb = hexToRgb(accentHex, [63, 224, 168]);
        var accent2Rgb = hexToRgb(accent2Hex, [56, 200, 255]);
        var panelRgb = hexToRgb(panelHex, [12, 21, 29]);
        var bgRgb = scaleRgb(panelRgb, 0.62);
        var deepRgb = scaleRgb(panelRgb, 0.36);

        var root = document.documentElement;
        root.style.setProperty('--mb-accent', rgbToHex(accentRgb));
        root.style.setProperty('--mb-accent-2', rgbToHex(accent2Rgb));
        root.style.setProperty('--mb-glow', rgba(accentRgb, 0.34));
        root.style.setProperty('--mb-glow-2', rgba(accent2Rgb, 0.28));
        root.style.setProperty('--mb-border', rgba(accentRgb, 0.28));
        root.style.setProperty('--mb-border-lit', rgba(accentRgb, 0.56));
        root.style.setProperty('--mb-panel-solid', rgbToHex(panelRgb));
        root.style.setProperty('--mb-panel', rgba(panelRgb, 0.88));
        root.style.setProperty('--mb-panel-strong', rgba(scaleRgb(panelRgb, 0.82), 0.95));
        root.style.setProperty('--mb-bg', rgbToHex(bgRgb));
        root.style.setProperty('--mb-bg-deep', rgbToHex(deepRgb));

        var accentInput = document.getElementById('mb-color-accent');
        var accent2Input = document.getElementById('mb-color-accent2');
        var panelInput = document.getElementById('mb-color-panel');
        if (accentInput && accentHex) accentInput.value = accentHex;
        if (accent2Input && accent2Hex) accent2Input.value = accent2Hex;
        if (panelInput && panelHex) panelInput.value = panelHex;

        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-preset]'), function (btn) {
            var key = btn.getAttribute('data-mb-preset');
            btn.classList.toggle('is-active', Boolean(cfg && cfg.preset && cfg.preset === key));
        });
    }

    function setPreset(name) {
        var p = PRESETS[name];
        if (!p) return;
        var next = { preset: name, accent: p.accent, accent2: p.accent2, panel: p.panel };
        writeTheme(next);
        applyPalette(next);
    }

    function setCustomFromInputs() {
        var a1 = document.getElementById('mb-color-accent');
        var a2 = document.getElementById('mb-color-accent2');
        var pn = document.getElementById('mb-color-panel');
        var next = {
            preset: '',
            accent: a1 ? a1.value : PRESETS['aero-emerald'].accent,
            accent2: a2 ? a2.value : PRESETS['aero-emerald'].accent2,
            panel: pn ? pn.value : PRESETS['aero-emerald'].panel
        };
        writeTheme(next);
        applyPalette(next);
    }

    function resetTheme() {
        writeTheme(null);
        applyPalette({
            preset: 'aero-emerald',
            accent: PRESETS['aero-emerald'].accent,
            accent2: PRESETS['aero-emerald'].accent2,
            panel: PRESETS['aero-emerald'].panel
        });
    }

    function toggleNav(forceOpen) {
        var drawer = document.getElementById('mb-nav-drawer');
        var backdrop = document.getElementById('mb-nav-backdrop');
        var toggle = document.getElementById('mb-nav-toggle');
        if (!drawer) return;
        var open = (typeof forceOpen === 'boolean') ? forceOpen : drawer.hasAttribute('hidden');
        if (open) {
            toggleThemePanel(false);
            drawer.removeAttribute('hidden');
            if (backdrop) backdrop.removeAttribute('hidden');
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
        } else {
            drawer.setAttribute('hidden', 'hidden');
            if (backdrop) backdrop.setAttribute('hidden', 'hidden');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function toggleThemePanel(forceOpen) {
        var sheet = document.getElementById('mb-theme-panel');
        var backdrop = document.getElementById('mb-theme-backdrop');
        if (!sheet) return;
        var open = (typeof forceOpen === 'boolean') ? forceOpen : sheet.hasAttribute('hidden');
        if (open) {
            toggleNav(false);
            sheet.removeAttribute('hidden');
            if (backdrop) backdrop.removeAttribute('hidden');
        } else {
            sheet.setAttribute('hidden', 'hidden');
            if (backdrop) backdrop.setAttribute('hidden', 'hidden');
        }
    }

    function switchTab(tab) {
        var ws = document.getElementById('mb-workspace');
        if (!ws) return;
        ws.setAttribute('data-mb-active-tab', tab);
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-dock-btn]'), function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-mb-dock-btn') === tab);
        });
        if (tab === 'profile' || tab === 'map' || tab === 'chat') {
            switchTabletLeft(tab);
        }
    }

    function switchTabletLeft(pane) {
        var ws = document.getElementById('mb-workspace');
        if (!ws) return;
        ws.setAttribute('data-mb-tablet-left', pane);
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-tablet-btn]'), function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-mb-tablet-btn') === pane);
        });
    }

    function renderWoundPills(state) {
        var host = document.getElementById('mb-hud-wounds');
        if (!host) return;
        host.textContent = '';

        var wounds = Array.isArray(state.wounds) ? state.wounds : [];
        var ailments = Array.isArray(state.ailments) ? state.ailments : [];
        if (!wounds.length && !ailments.length) {
            host.setAttribute('hidden', 'hidden');
            return;
        }

        wounds.forEach(function (w) {
            var el = document.createElement(state.canBandage ? 'button' : 'span');
            if (state.canBandage) {
                el.type = 'button';
                el.textContent = w.label + ' [包扎]';
                el.addEventListener('click', function () {
                    if (typeof window.postCmd === 'function') {
                        window.postCmd('gamecmd', 'command.php', { data: { mode: 'special', command: 'inf' + w.key } });
                    }
                });
            } else {
                el.textContent = w.label;
            }
            el.className = 'mb-inf-pill is-wound';
            host.appendChild(el);
        });

        ailments.forEach(function (a) {
            var el = document.createElement(state.canCureAilment ? 'button' : 'span');
            if (state.canCureAilment) {
                el.type = 'button';
                el.textContent = a.label + ' [处理]';
                el.addEventListener('click', function () {
                    if (typeof window.postCmd === 'function') {
                        window.postCmd('gamecmd', 'command.php', { data: { mode: 'special', command: 'inf' + a.key } });
                    }
                });
            } else {
                el.textContent = a.label;
            }
            el.className = 'mb-inf-pill is-ailment';
            host.appendChild(el);
        });

        host.removeAttribute('hidden');
    }

    function syncMapGrid(state) {
        var grid = document.getElementById('mb-map-grid');
        if (!grid || !Array.isArray(state.mapNodes)) return;

        var sig = state.mapNodes.map(function (n) { return n.id + ':' + n.name; }).join('|');
        if (sig !== lastMapSignature) {
            lastMapSignature = sig;
            grid.textContent = '';
            state.mapNodes.forEach(function (n) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'mb-map-node';
                btn.setAttribute('data-pls-id', String(n.id));
                btn.addEventListener('click', function () {
                    moveTo(n.id);
                });

                var coord = document.createElement('span');
                coord.className = 'mb-map-node__coord';
                coord.textContent = n.coord;

                var name = document.createElement('span');
                name.className = 'mb-map-node__name';
                name.textContent = n.name;

                btn.appendChild(coord);
                btn.appendChild(name);
                grid.appendChild(btn);
            });
        }

        var byId = {};
        state.mapNodes.forEach(function (n) { byId[String(n.id)] = n; });
        Array.prototype.forEach.call(grid.querySelectorAll('.mb-map-node[data-pls-id]'), function (btn) {
            var info = byId[btn.getAttribute('data-pls-id')];
            if (!info) return;
            btn.classList.toggle('is-current', Boolean(info.current));
            btn.classList.toggle('is-danger', Boolean(info.danger));
            btn.classList.toggle('is-warn', Boolean(info.warn));
        });
    }

    function syncHudFromState() {
        var scriptEl = document.getElementById('mb-live-state');
        if (!scriptEl) return;

        var state;
        try {
            state = JSON.parse(scriptEl.textContent || '{}');
        } catch (e) {
            return;
        }
        lastState = state;

        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-hp-text]'), function (el) {
            el.textContent = state.hp + '/' + state.mhp;
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-hp-fill]'), function (el) {
            el.style.width = state.hpPct + '%';
            el.classList.toggle('is-low', state.hpPct <= 25);
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-sp-text]'), function (el) {
            el.textContent = state.sp + '/' + state.msp;
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-sp-fill]'), function (el) {
            el.style.width = state.spPct + '%';
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-rage-text]'), function (el) {
            el.textContent = String(state.rage);
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-rage-fill]'), function (el) {
            el.style.width = state.ragePct + '%';
        });

        var ssWrap = document.getElementById('mb-hud-ss-wrap');
        if (ssWrap) {
            if (state.mss > 0 || state.ss > 0) {
                ssWrap.removeAttribute('hidden');
                Array.prototype.forEach.call(document.querySelectorAll('[data-mb-ss-text]'), function (el) {
                    el.textContent = state.ss + '/' + state.mss;
                });
                Array.prototype.forEach.call(document.querySelectorAll('[data-mb-ss-fill]'), function (el) {
                    el.style.width = state.ssPct + '%';
                });
            } else {
                ssWrap.setAttribute('hidden', 'hidden');
            }
        }

        var plsEl = document.getElementById('pls');
        var plsText = (plsEl && plsEl.textContent) ? plsEl.textContent : (state.locationName || '');
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-pls-mirror]'), function (el) {
            el.textContent = plsText;
        });

        if (state.weatherName) {
            Array.prototype.forEach.call(document.querySelectorAll('[data-mb-weather]'), function (el) {
                el.textContent = state.weatherName;
            });
        }

        var zoneFlag = document.getElementById('mb-hud-zone-flag');
        var mapDot = document.getElementById('mb-dock-map-dot');
        if (zoneFlag) {
            zoneFlag.classList.remove('is-danger', 'is-warn');
            if (state.inDanger) {
                zoneFlag.textContent = '禁区!';
                zoneFlag.className = 'mb-hud__zone-flag is-danger';
                zoneFlag.removeAttribute('hidden');
            } else if (state.inWarn) {
                zoneFlag.textContent = '即将禁区';
                zoneFlag.className = 'mb-hud__zone-flag is-warn';
                zoneFlag.removeAttribute('hidden');
            } else if (state.isHiddenGroup) {
                zoneFlag.textContent = '特殊区域';
                zoneFlag.className = 'mb-hud__zone-flag is-warn';
                zoneFlag.removeAttribute('hidden');
            } else {
                zoneFlag.setAttribute('hidden', 'hidden');
            }
        }
        if (mapDot) {
            if (state.inDanger || state.inWarn) mapDot.removeAttribute('hidden');
            else mapDot.setAttribute('hidden', 'hidden');
        }

        var spDot = document.getElementById('mb-hud-sp-dot');
        if (spDot) {
            if (state.skillpoint > 0) {
                spDot.textContent = String(state.skillpoint);
                spDot.removeAttribute('hidden');
            } else {
                spDot.setAttribute('hidden', 'hidden');
            }
        }

        var profileDot = document.getElementById('mb-dock-profile-dot');
        if (profileDot) {
            if (state.skillpoint > 0 || state.club === 0) profileDot.removeAttribute('hidden');
            else profileDot.setAttribute('hidden', 'hidden');
        }

        var clubQuick = document.getElementById('mb-club-quick');
        if (clubQuick && state.club !== 0) {
            clubQuick.style.display = 'none';
        }

        if (typeof state.locationId === 'number') {
            Array.prototype.forEach.call(document.querySelectorAll('img[data-mb-location-img]'), function (img) {
                var nextSrc = 'img/location/' + state.locationId + '.jpg';
                if (img.getAttribute('src') !== nextSrc) {
                    img.removeAttribute('data-mb-fallback-ready');
                    img.setAttribute('src', nextSrc);
                }
            });
        }

        renderWoundPills(state);
        syncMapGrid(state);
    }

    function moveTo(plsId) {
        var targetId = String(plsId);
        var notice = document.getElementById('notice');

        if (lastState && Number(lastState.locationId) === Number(plsId)) {
            if (notice) notice.textContent = '你已经在该地区了。';
            switchTab('action');
            return false;
        }

        var form = document.getElementById('gamecmd') || document.forms['gamecmd'];
        if (!form || typeof window.postCmd !== 'function') return false;

        var movetoSelect = form.querySelector('select[name="moveto"]');
        if (!movetoSelect) {
            if (notice) notice.textContent = '当前处于事件、战斗或子菜单中，请先完成当前指令后再点击地图移动。';
            switchTab('action');
            return false;
        }

        if (notice) notice.textContent = '';

        var modeInput = document.getElementById('mode') || form.elements['mode'];
        if (modeInput) modeInput.value = 'command';
        var cmdInput = document.getElementById('command') || form.elements['command'];
        if (cmdInput) cmdInput.value = 'move';

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

        switchTab('action');
        window.postCmd('gamecmd', 'command.php');
        movetoSelect.disabled = true;
        return false;
    }

    function useBagItem(slot) {
        if (typeof window.postCmd !== 'function') return false;
        switchTab('action');
        window.postCmd('gamecmd', 'command.php', {
            data: { mode: 'command', command: 'itm' + slot }
        });
        return false;
    }

    function actAndShow(mode, command) {
        if (typeof window.postCmd !== 'function') return false;
        switchTab('action');
        window.postCmd('gamecmd', 'command.php', {
            data: { mode: mode, command: command }
        });
        return false;
    }

    function runSubcmd(cmdVal, subName, subVal) {
        var form = document.getElementById('gamecmd') || document.forms['gamecmd'];
        var subInput = document.getElementById('subcmd');
        var notice = document.getElementById('notice');
        if (!form || !subInput || typeof window.postCmd !== 'function') {
            if (notice) notice.textContent = '请先完成当前事件或返回主指令菜单。';
            switchTab('action');
            return false;
        }
        var modeInput = document.getElementById('mode') || form.elements['mode'];
        var cmdInput = document.getElementById('command') || form.elements['command'];
        if (modeInput) modeInput.value = 'command';
        if (cmdInput) cmdInput.value = cmdVal;
        subInput.name = subName;
        subInput.value = subVal;
        switchTab('action');
        window.postCmd('gamecmd', 'command.php');
        return false;
    }

    function openDialogSafe(id) {
        var dlg = document.getElementById(id);
        var notice = document.getElementById('notice');
        if (dlg && typeof dlg.showModal === 'function') {
            ensureDialogCloseHeader(dlg);
            dlg.showModal();
            return true;
        }
        if (notice) {
            notice.textContent = '当前处于子菜单或特殊事件中，请先返回主指令菜单后再打开该面板。';
        }
        switchTab('action');
        return false;
    }

    function ensureDialogCloseHeader(dlg) {
        if (!dlg || dlg._mbCloseReady) return;
        var id = dlg.id || '';
        // Mandatory story/dialogue modals manage their own progression buttons.
        if (id === 'dialogue' || id === 'opening' || id === 'fcg-dialog') {
            dlg._mbCloseReady = true;
            return;
        }
        if (id === 'attack_warning') {
            dlg._mbCloseReady = true;
            var cancelBtn = document.createElement('input');
            cancelBtn.type = 'button';
            cancelBtn.className = 'cmdbutton';
            cancelBtn.value = '放弃攻击';
            cancelBtn.addEventListener('click', function () {
                if (typeof window.closeDialog === 'function') window.closeDialog(dlg);
                else if (typeof dlg.close === 'function') dlg.close();
                if (typeof window.postCmd === 'function') window.postCmd('gamecmd', 'command.php');
            });
            var centerEl = dlg.querySelector('center') || dlg;
            centerEl.appendChild(cancelBtn);
            return;
        }
        if (dlg.querySelector('.mb-dialog-head')) {
            dlg._mbCloseReady = true;
            return;
        }
        dlg._mbCloseReady = true;
        var bar = document.createElement('div');
        bar.className = 'mb-dialog-head';
        var title = document.createElement('span');
        title.className = 'yellow';
        title.textContent = id === 'skillpage' ? '技能列表' : (id === 'roomlist' ? '房间列表' : (id === 'rulesetDialog' ? '选择模式' : '弹窗详情'));
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mb-icon-close';
        btn.setAttribute('aria-label', '关闭');
        btn.textContent = '×';
        btn.addEventListener('click', function () {
            if (typeof window.closeDialog === 'function') window.closeDialog(dlg);
            else if (typeof dlg.close === 'function') dlg.close();
        });
        bar.appendChild(title);
        bar.appendChild(btn);
        dlg.insertBefore(bar, dlg.firstChild);
    }

    function wrapWideTables() {
        var script = document.body ? document.body.getAttribute('data-mb-script') : '';
        if (script === 'game') {
            // Only wrap standalone non-profile tables inside #main (such as radar screen)
            var mainHost = document.getElementById('main');
            if (mainHost && !mainHost.querySelector('[data-mb-main-kind]')) {
                Array.prototype.forEach.call(mainHost.querySelectorAll('table'), function (tbl) {
                    if (tbl.parentElement && tbl.parentElement.classList.contains('mb-table-scroll')) return;
                    var wrap = document.createElement('div');
                    wrap.className = 'mb-table-scroll';
                    tbl.parentNode.insertBefore(wrap, tbl);
                    wrap.appendChild(tbl);
                });
            }
            return;
        }

        Array.prototype.forEach.call(document.querySelectorAll('table.infotable, #alivelist > table, #stage table.map2'), function (tbl) {
            if (!tbl.parentNode || (tbl.parentElement && tbl.parentElement.classList.contains('mb-table-scroll'))) return;
            var wrap = document.createElement('div');
            wrap.className = 'mb-table-scroll';
            tbl.parentNode.insertBefore(wrap, tbl);
            wrap.appendChild(tbl);
        });
    }

    function showTooltip(text) {
        var toast = document.getElementById('mb-tooltip-toast');
        var body = document.getElementById('mb-tooltip-text');
        if (!toast || !body || !text) return;
        body.textContent = text;
        toast.removeAttribute('hidden');
    }

    function hideTooltip() {
        var toast = document.getElementById('mb-tooltip-toast');
        if (toast) toast.setAttribute('hidden', 'hidden');
    }

    function refreshAll() {
        var ws = document.getElementById('mb-workspace');
        var mainEl = document.getElementById('main');
        if (ws && mainEl) {
            var prevSpecial = ws.getAttribute('data-mb-has-special-main') === '1';
            var isProfile = Boolean(mainEl.querySelector('[data-mb-main-kind="profile"]'));
            var hasSpecial = !isProfile && mainEl.children.length > 0;
            ws.setAttribute('data-mb-has-special-main', hasSpecial ? '1' : '0');
            if (hasSpecial && !prevSpecial) {
                switchTab('action');
                switchTabletLeft('profile');
            }
        }

        syncHudFromState();
        Array.prototype.forEach.call(document.querySelectorAll('dialog'), ensureDialogCloseHeader);
        wrapWideTables();

        if (window.MobileImageFallback && typeof window.MobileImageFallback.refresh === 'function') {
            window.MobileImageFallback.refresh();
        }
        if (window.PHPDTSPaperdoll && typeof window.PHPDTSPaperdoll.refresh === 'function') {
            window.PHPDTSPaperdoll.refresh();
        }
    }

    function observeAjax() {
        if (typeof window.MutationObserver !== 'function') return;
        ['main', 'cmd', 'log', 'notice'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || el._mbObserved) return;
            el._mbObserved = true;
            var obs = new MutationObserver(function () {
                refreshAll();
            });
            obs.observe(el, { childList: true, subtree: false });
        });
    }

    function bindGlobalInteractions() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-mb-preset]'), function (btn) {
            if (btn._mbBound) return;
            btn._mbBound = true;
            btn.addEventListener('click', function () {
                setPreset(btn.getAttribute('data-mb-preset'));
            });
        });

        ['mb-color-accent', 'mb-color-accent2', 'mb-color-panel'].forEach(function (id) {
            var input = document.getElementById(id);
            if (!input || input._mbBound) return;
            input._mbBound = true;
            input.addEventListener('input', setCustomFromInputs);
            input.addEventListener('change', setCustomFromInputs);
        });

        // iOS Safari <select> change fix for itemmerge.htm radio auto-selection
        document.addEventListener('change', function (e) {
            var target = e.target;
            if (!target || target.tagName !== 'SELECT' || typeof window.sl !== 'function') return;
            var nm = target.name || '';
            if (nm === 'merge1' || nm === 'merge2') window.sl('itemmerge');
            else if (nm === 'from' || nm === 'to') window.sl('itemmove');
        });

        // Backdrop tap to dismiss <dialog> modals on touch devices
        document.addEventListener('click', function (e) {
            var target = e.target;
            if (target && target.tagName === 'DIALOG' && target.open && (e.clientX || e.clientY)) {
                var rect = target.getBoundingClientRect();
                var outside = (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom);
                if (outside) {
                    if (target.id === 'dialogue') {
                        if (target.getAttribute('data-skippable') === '1' && typeof window.endSkippableDialogue === 'function') {
                            window.endSkippableDialogue();
                        }
                    } else if (target.id === 'attack_warning') {
                        if (typeof window.closeDialog === 'function') window.closeDialog(target);
                        else target.close();
                        if (typeof window.postCmd === 'function') window.postCmd('gamecmd', 'command.php');
                    } else if (target.id !== 'opening') {
                        if (typeof window.closeDialog === 'function') window.closeDialog(target);
                        else target.close();
                    }
                }
                return;
            }

            // Tap-to-inspect tooltip spans that do not wrap interactive form controls
            if (target && target.closest) {
                var tipHost = target.closest('[tooltip], [tooltip2]');
                if (tipHost && !target.closest('input, button, select, a, textarea')) {
                    var tip = tipHost.getAttribute('tooltip') || tipHost.getAttribute('tooltip2');
                    if (tip) showTooltip(tip.trim());
                }
            }
        });
    }

    // Apply saved theme immediately to avoid FOUC
    var initialTheme = readTheme();
    if (initialTheme) {
        applyPalette(initialTheme);
    }

    window.MobileUI = {
        toggleNav: toggleNav,
        toggleThemePanel: toggleThemePanel,
        setPreset: setPreset,
        resetTheme: resetTheme,
        switchTab: switchTab,
        switchTabletLeft: switchTabletLeft,
        moveTo: moveTo,
        useBagItem: useBagItem,
        actAndShow: actAndShow,
        runSubcmd: runSubcmd,
        openDialogSafe: openDialogSafe,
        hideTooltip: hideTooltip,
        refresh: refreshAll
    };

    function boot() {
        var saved = readTheme();
        applyPalette(saved || {
            preset: 'aero-emerald',
            accent: PRESETS['aero-emerald'].accent,
            accent2: PRESETS['aero-emerald'].accent2,
            panel: PRESETS['aero-emerald'].panel
        });
        bindGlobalInteractions();
        refreshAll();
        observeAjax();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

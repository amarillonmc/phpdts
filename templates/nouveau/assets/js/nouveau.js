(function () {
    'use strict';

    var THEME_KEY = 'phpdts:nouveau:theme:v1';

    var PRESETS = {
        'aero-emerald': {
            accent: '#3fe0a8',
            accent2: '#38c8ff',
            panel: '#0c151d'
        },
        'frutiger-aqua': {
            accent: '#38d6ff',
            accent2: '#5ce6b8',
            panel: '#081724'
        },
        'cobalt-ego': {
            accent: '#5a9bff',
            accent2: '#8f7dff',
            panel: '#0a1224'
        },
        'amethyst-glass': {
            accent: '#c07cff',
            accent2: '#52d1ff',
            panel: '#150e24'
        },
        'amber-pulse': {
            accent: '#f5b942',
            accent2: '#ff6b4a',
            panel: '#1c130b'
        },
        'crimson-core': {
            accent: '#ff5c7c',
            accent2: '#ffb347',
            panel: '#1e0d14'
        }
    };

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
            var raw = window.localStorage.getItem(THEME_KEY);
            if (!raw) return null;
            return JSON.parse(raw);
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
        } catch (e) {
            // Ignore localStorage quota/privacy errors.
        }
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
        root.style.setProperty('--nv-accent', rgbToHex(accentRgb));
        root.style.setProperty('--nv-accent-2', rgbToHex(accent2Rgb));
        root.style.setProperty('--nv-glow', rgba(accentRgb, 0.34));
        root.style.setProperty('--nv-glow-2', rgba(accent2Rgb, 0.28));
        root.style.setProperty('--nv-border', rgba(accentRgb, 0.28));
        root.style.setProperty('--nv-border-lit', rgba(accentRgb, 0.56));
        root.style.setProperty('--nv-panel-solid', rgbToHex(panelRgb));
        root.style.setProperty('--nv-panel', rgba(panelRgb, 0.86));
        root.style.setProperty('--nv-panel-strong', rgba(scaleRgb(panelRgb, 0.82), 0.94));
        root.style.setProperty('--nv-bg', rgbToHex(bgRgb));
        root.style.setProperty('--nv-bg-deep', rgbToHex(deepRgb));

        syncControls(cfg || { preset: 'aero-emerald', accent: accentHex, accent2: accent2Hex, panel: panelHex });
    }

    function syncControls(cfg) {
        var accentInput = document.getElementById('nv-color-accent');
        var accent2Input = document.getElementById('nv-color-accent2');
        var panelInput = document.getElementById('nv-color-panel');

        if (accentInput && cfg.accent) accentInput.value = cfg.accent;
        if (accent2Input && cfg.accent2) accent2Input.value = cfg.accent2;
        if (panelInput && cfg.panel) panelInput.value = cfg.panel;

        Array.prototype.forEach.call(document.querySelectorAll('[data-nv-preset]'), function (btn) {
            var key = btn.getAttribute('data-nv-preset');
            btn.classList.toggle('is-active', Boolean(cfg.preset && cfg.preset === key));
        });
    }

    function setPreset(name) {
        var p = PRESETS[name];
        if (!p) return;
        var next = {
            preset: name,
            accent: p.accent,
            accent2: p.accent2,
            panel: p.panel
        };
        writeTheme(next);
        applyPalette(next);
    }

    function setCustomFromInputs() {
        var accentInput = document.getElementById('nv-color-accent');
        var accent2Input = document.getElementById('nv-color-accent2');
        var panelInput = document.getElementById('nv-color-panel');
        var next = {
            preset: '',
            accent: accentInput ? accentInput.value : PRESETS['aero-emerald'].accent,
            accent2: accent2Input ? accent2Input.value : PRESETS['aero-emerald'].accent2,
            panel: panelInput ? panelInput.value : PRESETS['aero-emerald'].panel
        };
        writeTheme(next);
        applyPalette(next);
    }

    function resetTheme() {
        writeTheme(null);
        applyPalette(PRESETS['aero-emerald']);
    }

    function togglePanel(forceOpen) {
        var panel = document.getElementById('nv-theme-panel');
        if (!panel) return;
        var nextOpen = (typeof forceOpen === 'boolean') ? forceOpen : panel.hasAttribute('hidden');
        if (nextOpen) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', 'hidden');
        }
    }

    function bindThemeEvents() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-nv-preset]'), function (btn) {
            if (btn._nvPresetBound) return;
            btn._nvPresetBound = true;
            btn.addEventListener('click', function () {
                setPreset(btn.getAttribute('data-nv-preset'));
            });
        });

        ['nv-color-accent', 'nv-color-accent2', 'nv-color-panel'].forEach(function (id) {
            var input = document.getElementById(id);
            if (!input || input._nvColorBound) return;
            input._nvColorBound = true;
            input.addEventListener('input', setCustomFromInputs);
            input.addEventListener('change', setCustomFromInputs);
        });

        document.addEventListener('click', function (event) {
            var detailsList = document.querySelectorAll('details.nv-vnworld-menu[open]');
            Array.prototype.forEach.call(detailsList, function (details) {
                if (!details.contains(event.target)) {
                    details.removeAttribute('open');
                }
            });
        });
    }

    function boot() {
        document.documentElement.classList.add('nouveau-ready');
        var saved = readTheme();
        applyPalette(saved || {
            preset: 'aero-emerald',
            accent: PRESETS['aero-emerald'].accent,
            accent2: PRESETS['aero-emerald'].accent2,
            panel: PRESETS['aero-emerald'].panel
        });
        bindThemeEvents();

        if (window.NouveauPanels && typeof window.NouveauPanels.refresh === 'function') {
            window.NouveauPanels.refresh();
        }
    }

    // Apply saved theme immediately to avoid flash of default colors.
    var initial = readTheme();
    if (initial) {
        applyPalette(initial);
    }

    window.NouveauTheme = {
        setPreset: setPreset,
        reset: resetTheme,
        togglePanel: togglePanel
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

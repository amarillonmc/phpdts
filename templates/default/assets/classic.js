(function () {
    'use strict';

    var STORAGE_KEY = 'phpdts:classic:appearance';
    var PRESETS = {
        denpa: { background: '#111111', panel: '#111111', text: '#dddddd', accent: '#e17ce2', link: '#dddddd' },
        ocean: { background: '#0c1725', panel: '#15263a', text: '#dce8f5', accent: '#659fc5', link: '#9edbff' },
        pine: { background: '#101b17', panel: '#1a2d25', text: '#dfebe1', accent: '#72a88a', link: '#a4dfb5' },
        amber: { background: '#21180f', panel: '#33271a', text: '#f0e5d1', accent: '#c49a58', link: '#f2cc88' },
        violet: { background: '#1a1426', panel: '#2a223b', text: '#e9e0f3', accent: '#a68bc4', link: '#d4b7f3' },
        graphite: { background: '#17191c', panel: '#272b30', text: '#e2e5e9', accent: '#969fae', link: '#c7d7eb' }
    };
    var COLOR_KEYS = ['background', 'panel', 'text', 'accent', 'link'];
    var root = document.documentElement;

    function validPreset(name) {
        return Object.prototype.hasOwnProperty.call(PRESETS, name);
    }

    function normalize(saved) {
        saved = saved && typeof saved === 'object' ? saved : {};
        var next = {
            font: ['fusion', 'hzk', 'yahei'].indexOf(saved.font) !== -1 ? saved.font : 'fusion',
            preset: saved.preset === 'custom' || validPreset(saved.preset) ? saved.preset : 'denpa',
            colors: {}
        };
        var colors = next.preset === 'custom' ? (saved.colors || {}) : PRESETS[next.preset];
        COLOR_KEYS.forEach(function (key) {
            next.colors[key] = typeof colors[key] === 'string' && /^#[0-9a-f]{6}$/i.test(colors[key])
                ? colors[key].toLowerCase() : PRESETS.denpa[key];
        });
        return next;
    }

    var settings;
    try {
        settings = normalize(JSON.parse(window.localStorage.getItem(STORAGE_KEY)));
    } catch (e) {
        settings = normalize(null);
    }

    function apply() {
        // 默认选项不覆盖原版样式；根属性也会作用于 AJAX 后插入的内容。
        root.setAttribute('data-classic-font', settings.font);
        root.setAttribute('data-classic-palette', settings.preset);
        COLOR_KEYS.forEach(function (key) {
            root.style.setProperty('--classic-' + key, settings.colors[key]);
        });
        var hex = settings.colors.accent;
        var rgb = [1, 3, 5].map(function (offset) { return parseInt(hex.substr(offset, 2), 16); });
        // 表头保持深色，避免其中的灰色熟练度、红黄提示被亮底吞掉。
        var heading = rgb.map(function (value, index) {
            var panel = parseInt(settings.colors.panel.substr(1 + index * 2, 2), 16);
            return Math.round(value * 0.18 + panel * 0.82);
        });
        root.style.setProperty('--classic-heading', 'rgb(' + heading.join(',') + ')');
        root.style.setProperty('--classic-border', 'rgba(' + rgb.join(',') + ',0.45)');
    }

    function syncControls() {
        document.getElementById('classic-font').value = settings.font;
        document.getElementById('classic-preset').value = settings.preset;
        Array.prototype.forEach.call(document.querySelectorAll('[data-classic-color]'), function (input) {
            input.value = settings.colors[input.getAttribute('data-classic-color')];
        });
    }

    function save(reset) {
        apply();
        syncControls();
        var status = document.getElementById('classic-settings-status');
        try {
            if (reset) window.localStorage.removeItem(STORAGE_KEY);
            else window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
            status.textContent = reset ? '已恢复默认字体与配色。' : '已保存到当前浏览器。';
        } catch (e) {
            status.textContent = '已在本页生效；浏览器暂时无法保存设置。';
        }
    }

    function bindControls() {
        var panel = document.getElementById('classic-settings');
        if (!panel) return;
        syncControls();
        function positionPanel() {
            if (!panel.open) return;
            var button = panel.querySelector('summary').getBoundingClientRect();
            var body = panel.querySelector('.classic-settings-body');
            var above = button.top - 16;
            var below = window.innerHeight - button.bottom - 16;
            // 短页面的页脚可能靠近顶部，此时向下展开以免遮掉控件。
            var openBelow = above < body.scrollHeight + 2 && below > above;
            panel.classList.toggle('classic-settings-below', openBelow);
            body.style.maxHeight = Math.max(60, openBelow ? below : above) + 'px';
        }
        panel.addEventListener('toggle', positionPanel);
        window.addEventListener('resize', positionPanel);
        // 下拉选择与按钮上的字母键不能传到游戏的 body.onkeyup。
        panel.addEventListener('keyup', function (event) { event.stopPropagation(); });
        document.getElementById('classic-font').addEventListener('change', function () {
            settings.font = this.value;
            settings = normalize(settings);
            save(false);
        });
        document.getElementById('classic-preset').addEventListener('change', function () {
            settings.preset = this.value;
            settings = normalize(settings);
            save(false);
        });
        Array.prototype.forEach.call(panel.querySelectorAll('[data-classic-color]'), function (input) {
            input.addEventListener('input', function () {
                settings.preset = 'custom';
                settings.colors[this.getAttribute('data-classic-color')] = this.value;
                settings = normalize(settings);
                save(false);
            });
        });
        document.getElementById('classic-reset').addEventListener('click', function () {
            settings = normalize(null);
            save(true);
        });
    }

    // 在页头读取设置，避免先闪出默认配色。
    apply();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindControls);
    else bindControls();
}());

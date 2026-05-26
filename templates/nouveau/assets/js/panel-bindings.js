(function () {
    'use strict';

    function clampPercent(value) {
        var number = Number(value);
        if (!isFinite(number)) {
            return 0;
        }
        return Math.max(0, Math.min(100, number));
    }

    function initMeters() {
        document.querySelectorAll('[data-meter-value]').forEach(function (node) {
            node.style.width = clampPercent(node.getAttribute('data-meter-value')) + '%';
        });
    }

    function labelCommandButtons() {
        document.querySelectorAll('.nv-command-legacy input[type="button"]').forEach(function (button) {
            if (!button.title && button.value) {
                button.title = button.value.replace(/^\[[A-Z]\]/, '').trim();
            }
        });
    }

    function syncLocationFromAjax() {
        if (!window.shwData || !window.shwData.innerHTML || !window.shwData.innerHTML.pls) {
            return;
        }
        document.querySelectorAll('[data-nv-pls]').forEach(function (node) {
            node.innerHTML = window.shwData.innerHTML.pls;
        });
    }

    function refresh() {
        initMeters();
        labelCommandButtons();
        syncLocationFromAjax();
    }

    window.NouveauPanels = {
        refresh: refresh
    };

    document.addEventListener('DOMContentLoaded', function () {
        refresh();
    });
})();

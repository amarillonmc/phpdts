(function () {
    'use strict';

    function addReadyClass() {
        document.documentElement.classList.add('nouveau-ready');
    }

    function markExternalLinks() {
        document.querySelectorAll('.nouveau-ui a[target="_blank"]').forEach(function (link) {
            link.rel = 'noopener noreferrer';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        addReadyClass();
        markExternalLinks();
    });
})();

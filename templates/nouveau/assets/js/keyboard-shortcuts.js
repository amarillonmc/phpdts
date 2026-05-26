(function () {
    'use strict';

    document.addEventListener('keydown', function (event) {
        if (event.target && /INPUT|TEXTAREA|SELECT/.test(event.target.tagName)) {
            return;
        }
        if (event.altKey && event.key >= '1' && event.key <= '9') {
            var windows = document.querySelectorAll('.nv-window');
            var win = windows[Number(event.key) - 1];
            if (win && window.NouveauWindows) {
                event.preventDefault();
                window.NouveauWindows.restore(win);
            }
        }
    });
})();

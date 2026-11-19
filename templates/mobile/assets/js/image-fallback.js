(function () {
    'use strict';

    var fallbackMap = {
        avatar: 'img/nouveau/placeholders/avatar-default.svg',
        paperdoll: 'img/nouveau/placeholders/paperdoll-default.svg',
        item: 'img/nouveau/placeholders/item-default.svg',
        location: 'img/nouveau/placeholders/location-default.svg',
        node: 'img/nouveau/placeholders/map-node.svg'
    };

    function fallbackFor(img) {
        var key = img.getAttribute('data-fallback') || '';
        var src = img.getAttribute('src') || '';
        if (!key) {
            if (src.indexOf('icon') !== -1 || /img\/[mf]_\d+\.gif/.test(src)) {
                key = 'avatar';
            } else if (src.indexOf('item') !== -1) {
                key = 'item';
            } else {
                key = 'location';
            }
        }
        return fallbackMap[key] || fallbackMap.location;
    }

    function attach(img) {
        if (img.getAttribute('data-mb-fallback-ready')) return;
        img.setAttribute('data-mb-fallback-ready', '1');
        img.addEventListener('error', function () {
            var next = fallbackFor(img);
            if (img.getAttribute('src') !== next) {
                img.setAttribute('src', next);
            }
        });
    }

    function refresh() {
        Array.prototype.forEach.call(document.querySelectorAll('img'), attach);
    }

    window.MobileImageFallback = {
        refresh: refresh
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refresh);
    } else {
        refresh();
    }
})();

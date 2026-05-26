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
        var key = img.getAttribute('data-fallback') || 'location';
        var src = img.getAttribute('src') || '';
        if (!img.getAttribute('data-fallback')) {
            if (src.indexOf('icon') !== -1 || /img\/[mf]_\d+\.gif/.test(src)) {
                key = 'avatar';
            } else if (src.indexOf('item') !== -1) {
                key = 'item';
            }
        }
        return fallbackMap[key] || fallbackMap.location;
    }

    function attach(img) {
        if (img.getAttribute('data-nv-fallback-ready')) {
            return;
        }
        img.setAttribute('data-nv-fallback-ready', '1');
        img.addEventListener('error', function () {
            var next = fallbackFor(img);
            if (img.getAttribute('src') !== next) {
                img.setAttribute('src', next);
            }
        });
    }

    function refresh() {
        document.querySelectorAll('img').forEach(attach);
    }

    window.NouveauImages = {
        refresh: refresh
    };

    document.addEventListener('DOMContentLoaded', function () {
        refresh();
    });
})();

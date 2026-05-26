(function () {
    'use strict';

    var fallbackLocationBg = 'img/nouveau/placeholders/location-default.svg';

    function addReadyClass() {
        document.documentElement.classList.add('nouveau-ready');
    }

    function markExternalLinks() {
        document.querySelectorAll('.nouveau-ui a[target="_blank"]').forEach(function (link) {
            link.rel = 'noopener noreferrer';
        });
    }

    function setDesktopBackground(locationId) {
        if (!locationId) {
            return;
        }
        var bgUrl = 'img/location/' + locationId + '.jpg';
        var bg = document.querySelector('.nouveau-desktop-bg');
        var imageStack = [
            'linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px)',
            'linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px)',
            'linear-gradient(rgba(16,19,21,.54), rgba(16,19,21,.88))',
            'url("' + bgUrl + '")',
            'url("' + fallbackLocationBg + '")'
        ].join(',');

        if (bg) {
            bg.style.backgroundImage = imageStack;
            bg.style.backgroundSize = '32px 32px, 32px 32px, cover, cover, cover';
            bg.style.backgroundPosition = '0 0, 0 0, center, center, center';
        }
        document.body.style.backgroundImage = 'url("' + bgUrl + '")';
        document.body.style.backgroundPosition = 'center';
    }

    function patchBackgroundUpdater() {
        window.updateBackgroundImage = setDesktopBackground;
    }

    function afterAjaxRefresh() {
        if (window.NouveauWindows) {
            window.NouveauWindows.refresh({ activateFirst: false });
        }
        if (window.NouveauPanels) {
            window.NouveauPanels.refresh();
        }
        if (window.NouveauImages) {
            window.NouveauImages.refresh();
        }
        if (window.shwData && window.shwData.locationId) {
            setDesktopBackground(window.shwData.locationId);
        }
    }

    function patchShowData() {
        if (typeof window.showData !== 'function' || window.showData._nouveauPatched) {
            return;
        }
        var originalShowData = window.showData;
        window.showData = function (sdata) {
            var result = originalShowData.apply(this, arguments);
            afterAjaxRefresh();
            return result;
        };
        window.showData._nouveauPatched = true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        addReadyClass();
        markExternalLinks();
        patchBackgroundUpdater();
        patchShowData();
    });

    patchBackgroundUpdater();
    patchShowData();
})();

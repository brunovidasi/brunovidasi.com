/** The narrow-screen nav menu toggle in includes/layout_top.php. */
(function () {
    'use strict';

    var toggle = document.getElementById('navToggle');
    var menu = document.getElementById('navMenu');
    if (!toggle || !menu) {
        return;
    }
    toggle.addEventListener('click', function () {
        var isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    var sentinel = document.getElementById('navSentinel');
    var siteNav = document.getElementById('siteNav');
    if (sentinel && siteNav && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            siteNav.classList.toggle('is-stuck', !entries[0].isIntersecting);
        }, { threshold: 0 });
        observer.observe(sentinel);
    }
})();

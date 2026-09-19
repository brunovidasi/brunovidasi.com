/**
 * The log-in form in the logged-out masthead (includes/layout_top.php). It's always
 * visible on wide screens; on narrow ones it sits behind the "Log in" button, and
 * this opens it. Anything else on the page can point at it with a link to
 * #mastheadLoginEmail (the landing page's "Log in" button does).
 */
(function () {
    'use strict';

    var box = document.getElementById('mastheadLogin');
    var toggle = document.getElementById('mastheadLoginToggle');
    var email = document.getElementById('mastheadLoginEmail');
    if (!box || !toggle || !email) {
        return;
    }

    function setOpen(open) {
        box.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function () {
        var open = !box.classList.contains('is-open');
        setOpen(open);
        if (open) {
            email.focus();
        }
    });

    document.querySelectorAll('a[href="#mastheadLoginEmail"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setOpen(true);
            email.focus({ preventScroll: true });
        });
    });
})();

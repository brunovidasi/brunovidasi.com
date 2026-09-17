/**
 * The shared "are you sure?" dialog (markup lives in includes/layout_bottom.php)
 * and the wiring that puts it in front of any form[data-confirm] submit.
 */
(function (Bidwraith) {
    'use strict';

    /**
     * Resolves true if the user confirms, false otherwise. Pages without the
     * modal markup resolve true so a missing dialog can't block an action.
     */
    Bidwraith.showConfirmModal = function (message) {
        var overlay = document.getElementById('confirmModal');
        if (!overlay) {
            return Promise.resolve(true);
        }
        var messageEl = document.getElementById('confirmModalMessage');
        var okBtn = document.getElementById('confirmModalOk');
        var cancelBtn = document.getElementById('confirmModalCancel');

        return new Promise(function (resolve) {
            messageEl.textContent = message;
            overlay.hidden = false;
            okBtn.focus();

            function cleanup(result) {
                overlay.hidden = true;
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                overlay.removeEventListener('click', onOverlayClick);
                document.removeEventListener('keydown', onKeydown);
                resolve(result);
            }

            function onOk() { cleanup(true); }
            function onCancel() { cleanup(false); }
            function onOverlayClick(e) {
                if (e.target === overlay) {
                    cleanup(false);
                }
            }
            function onKeydown(e) {
                if (e.key === 'Escape') {
                    cleanup(false);
                }
            }

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            overlay.addEventListener('click', onOverlayClick);
            document.addEventListener('keydown', onKeydown);
        });
    };

    /**
     * Runs a confirmed submit through form.submit(), which deliberately skips
     * submit handlers — this one has already had its say, and the server
     * revalidates regardless.
     */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Bidwraith.showConfirmModal(form.dataset.confirm).then(function (confirmed) {
                if (confirmed) {
                    form.submit();
                }
            });
        });
    });
})(window.Bidwraith);

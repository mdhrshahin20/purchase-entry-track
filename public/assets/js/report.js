(function () {
    'use strict';

    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-detail-toggle]'));
    if (!buttons.length) {
        return;
    }

    function setOpen(btn, open) {
        var id = btn.getAttribute('data-detail-toggle');
        var detail = document.getElementById(id);
        var parent = btn.closest('tr.report-row');
        var label = btn.querySelector('.btn-detail-label');

        if (!detail) {
            return;
        }

        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('is-open', open);
        if (parent) {
            parent.classList.toggle('is-open', open);
        }

        if (label) {
            label.textContent = open ? 'Hide' : 'Details';
        }
        btn.setAttribute('title', open ? 'Hide details' : 'Show more details');

        if (open) {
            detail.hidden = false;
            // Force reflow so CSS transition runs.
            void detail.offsetHeight;
            detail.classList.add('is-visible');
        } else {
            detail.classList.remove('is-visible');
            window.setTimeout(function () {
                if (!detail.classList.contains('is-visible')) {
                    detail.hidden = true;
                }
            }, 180);
        }
    }

    function closeAll(exceptBtn) {
        buttons.forEach(function (btn) {
            if (btn !== exceptBtn && btn.getAttribute('aria-expanded') === 'true') {
                setOpen(btn, false);
            }
        });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = btn.getAttribute('aria-expanded') !== 'true';
            if (willOpen) {
                closeAll(btn);
            }
            setOpen(btn, willOpen);
        });
    });

    document.querySelectorAll('[data-detail-close]').forEach(function (closeBtn) {
        closeBtn.addEventListener('click', function () {
            var id = closeBtn.getAttribute('data-detail-close');
            var toggle = document.querySelector('[data-detail-toggle="' + id + '"]');
            if (toggle) {
                setOpen(toggle, false);
                toggle.focus();
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        closeAll(null);
    });
})();

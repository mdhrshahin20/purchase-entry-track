/**
 * Frontend validation + jQuery AJAX purchase submission.
 * Real-time field feedback; backend validation remains independent.
 */
(function ($) {
    'use strict';

    var items = [];
    var base = window.APP_BASE || '';
    var storeUrl = window.APP_STORE_URL || (base.replace(/\/?$/, '') + '/store');
    var submittedOnce = false;
    var touched = {};
    var lockTimer = null;

    var fieldOrder = [
        'amount',
        'buyer',
        'receipt_id',
        'buyer_email',
        'items',
        'note',
        'city',
        'phone',
        'entry_by'
    ];

    function formatRemaining(seconds) {
        seconds = Math.max(0, Math.floor(seconds));
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        if (hours > 0 && minutes > 0) {
            return hours + 'h ' + minutes + 'm';
        }
        if (hours > 0) {
            return hours + ' hour' + (hours === 1 ? '' : 's');
        }
        if (minutes > 0) {
            return minutes + ' minute' + (minutes === 1 ? '' : 's');
        }
        return secs + ' second' + (secs === 1 ? '' : 's');
    }

    function formatAvailableLabel(iso) {
        if (!iso) {
            return '';
        }
        var date = new Date(iso);
        if (isNaN(date.getTime())) {
            return iso;
        }
        try {
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var month = months[date.getMonth()];
            var day = date.getDate();
            var year = date.getFullYear();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            if (hours === 0) {
                hours = 12;
            }
            var min = minutes < 10 ? '0' + minutes : String(minutes);
            return month + ' ' + day + ', ' + year + ' at ' + hours + ':' + min + ' ' + ampm;
        } catch (e) {
            return date.toString();
        }
    }

    function showSubmitLock(lock) {
        var $banner = $('#submit-lock-banner');
        if (!$banner.length || !lock || !lock.locked) {
            return;
        }

        var availableLabel = lock.available_at_label || formatAvailableLabel(lock.available_at);
        var remainingLabel = lock.remaining_label || formatRemaining(lock.remaining_seconds || 0);
        var html = '<strong>Warning</strong><p class="submit-lock-copy">Next submission available after <strong id="submit-lock-available">' +
            $('<div>').text(availableLabel).html() +
            '</strong> <span id="submit-lock-remaining-wrap">(<span id="submit-lock-remaining">' +
            $('<div>').text(remainingLabel).html() +
            '</span> left)</span>.</p>';

        $banner
            .html(html)
            .attr('data-available-at', lock.available_at || '')
            .attr('data-remaining-seconds', lock.remaining_seconds != null ? lock.remaining_seconds : '')
            .prop('hidden', false);

        startLockCountdown(lock);
    }

    function startLockCountdown(lock) {
        if (lockTimer) {
            clearInterval(lockTimer);
            lockTimer = null;
        }
        if (!lock || !lock.available_at) {
            return;
        }

        var unlockAt = Date.parse(lock.available_at);
        if (isNaN(unlockAt)) {
            return;
        }

        function tick() {
            var remaining = Math.max(0, Math.floor((unlockAt - Date.now()) / 1000));
            var $remaining = $('#submit-lock-remaining');
            if ($remaining.length) {
                $remaining.text(formatRemaining(remaining));
            }
            if (remaining <= 0) {
                clearInterval(lockTimer);
                lockTimer = null;
                $('#submit-lock-banner')
                    .html('<strong>Warning</strong><p class="submit-lock-copy">You can submit again now.</p>')
                    .prop('hidden', false);
            }
        }

        tick();
        lockTimer = setInterval(tick, 1000);
    }

    function showAlert(type, message, details) {
        var $alert = $('#form-alert');
        var $title = $('#form-alert-title');
        var $list = $('#form-alert-list');

        $alert
            .removeClass('alert-success alert-error')
            .addClass(type === 'success' ? 'alert-success' : 'alert-error')
            .prop('hidden', false);

        $title.text(message || '');
        $list.empty();

        if (details && details.length) {
            details.forEach(function (item) {
                var $li = $('<li></li>');
                if (item.field) {
                    $li.append(
                        $('<a href="#"></a>')
                            .text(item.label + ': ' + item.message)
                            .attr('data-focus-field', item.field)
                    );
                } else {
                    $li.text(item.message || item);
                }
                $list.append($li);
            });
            $list.prop('hidden', false);
        } else {
            $list.prop('hidden', true);
        }

        if (type === 'error') {
            $alert[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearAlert() {
        $('#form-alert').prop('hidden', true);
        $('#form-alert-title').text('');
        $('#form-alert-list').empty().prop('hidden', true);
    }

    function clearFieldError(name) {
        var $field = fieldEl(name);
        $field.removeClass('has-error has-success');
        $('[data-error="' + name + '"]').text('');
        $field.find('input, textarea').removeAttr('aria-invalid');
    }

    function clearFieldErrors() {
        fieldOrder.forEach(clearFieldError);
    }

    function fieldEl(name) {
        if (name === 'items') {
            return $('#items').closest('.field');
        }
        return $('[name="' + name + '"]').closest('.field');
    }

    function setFieldError(name, message) {
        var $field = fieldEl(name);
        $field.removeClass('has-success').addClass('has-error');
        $('[data-error="' + name + '"]').text(message);
        $field.find('input, textarea').attr('aria-invalid', 'true');
    }

    function setFieldSuccess(name) {
        var $field = fieldEl(name);
        $field.removeClass('has-error').addClass('has-success');
        $('[data-error="' + name + '"]').text('');
        $field.find('input, textarea').attr('aria-invalid', 'false');
    }

    function fieldLabel(name) {
        var map = {
            amount: 'Amount',
            buyer: 'Buyer',
            receipt_id: 'Receipt ID',
            buyer_email: 'Buyer Email',
            items: 'Items',
            note: 'Note',
            city: 'City',
            phone: 'Phone',
            entry_by: 'Entry By'
        };
        return map[name] || name;
    }

    function syncItemsField() {
        $('#items').val(items.join(', '));
        renderItems();
        $('#items-count').text(items.length);
    }

    function renderItems() {
        var $list = $('#items-list').empty();
        if (!items.length) {
            $list.append('<p class="items-empty">No items added yet.</p>');
            return;
        }
        items.forEach(function (item, index) {
            var $chip = $('<span class="item-chip"></span>').append(
                $('<span class="item-chip-text"></span>').text(item)
            );
            var $remove = $('<button type="button" aria-label="Remove item">&times;</button>');
            $remove.on('click', function () {
                items.splice(index, 1);
                syncItemsField();
                if (submittedOnce || touched.items) {
                    validateField('items');
                    refreshAlertFromFields();
                }
            });
            $chip.append($remove);
            $list.append($chip);
        });
    }

    var NOTE_MAX_WORDS = 30;
    /** Last note value that stayed within the 30-word limit. */
    var lastValidNote = '';

    /**
     * Count words as letter/number groups (Unicode-aware).
     * Updates as soon as you type characters — not only when you press space.
     */
    function countWords(text) {
        var value = String(text || '');
        if (!value) {
            return 0;
        }

        var matches = null;
        try {
            // Letters or numbers in any language (e.g. English, Bangla).
            matches = value.match(/[\p{L}\p{N}]+/gu);
        } catch (e) {
            matches = null;
        }

        if (!matches) {
            // Fallback: any non-whitespace run.
            matches = value.match(/[^\s]+/g);
        }

        return matches ? matches.length : 0;
    }

    function updateWordCount() {
        var $note = $('#note');
        if (!$note.length) {
            return;
        }

        var count = countWords($note.val());
        var $wrap = $('#note-word-wrap');
        var $meta = $('#note-words');

        if ($meta.length) {
            $meta.text(String(count));
        } else if ($wrap.length) {
            $wrap.html('<span id="note-words">' + count + '</span> / 30 words');
        }

        $wrap.toggleClass('is-over', count >= NOTE_MAX_WORDS);
        $wrap.attr('data-count', String(count));
    }

    /**
     * Keep note at max 30 words — block extra typing/paste and show a warning.
     */
    function enforceNoteWordLimit() {
        var $note = $('#note');
        if (!$note.length) {
            return;
        }

        var value = $note.val();
        var count = countWords(value);

        if (count <= NOTE_MAX_WORDS) {
            lastValidNote = value;
            updateWordCount();
            if ($('[data-error="note"]').text().indexOf('30 words only') !== -1) {
                clearFieldError('note');
            }
            return;
        }

        $note.val(lastValidNote);
        updateWordCount();
        setFieldError('note', 'You can use up to 30 words only. Extra text was blocked.');
    }

    function addItem() {
        var value = $.trim($('#item-input').val());
        touched.items = true;

        if (!value) {
            setFieldError('items', 'Enter an item name before adding.');
            $('#item-input').trigger('focus');
            return;
        }
        if (!/^[a-zA-Z ]+$/.test(value)) {
            setFieldError('items', 'Items must contain text only (letters and spaces).');
            $('#item-input').trigger('focus');
            return;
        }

        items.push(value);
        $('#item-input').val('').removeClass('is-invalid');
        syncItemsField();
        validateField('items');
        if (submittedOnce) {
            refreshAlertFromFields();
        }
        $('#item-input').trigger('focus');
    }

    /**
     * Validate a single field. Returns error message or null.
     */
    function getFieldError(name) {
        var amount = $.trim($('#amount').val());
        var buyer = $.trim($('#buyer').val());
        var receiptId = $.trim($('#receipt_id').val());
        var email = $.trim($('#buyer_email').val());
        var note = $.trim($('#note').val());
        var city = $.trim($('#city').val());
        var phoneLocal = $.trim($('#phone').val());
        var entryBy = $.trim($('#entry_by').val());

        switch (name) {
            case 'amount':
                if (!amount) return 'Amount is required.';
                if (!/^\d+$/.test(amount)) return 'Amount must contain numbers only.';
                return null;
            case 'buyer':
                if (!buyer) return 'Buyer is required.';
                if (buyer.length > 20) return 'Buyer must be no more than 20 characters.';
                if (!/^[a-zA-Z0-9 ]+$/.test(buyer)) return 'Buyer may contain text, spaces, and numbers only.';
                return null;
            case 'receipt_id':
                if (!receiptId) return 'Receipt ID is required.';
                if (!/^[a-zA-Z]+$/.test(receiptId)) return 'Receipt ID must contain text (letters) only.';
                return null;
            case 'buyer_email':
                if (!email) return 'Buyer email is required.';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Buyer email must be a valid email address.';
                return null;
            case 'items':
                if (items.length === 0) return 'At least one item is required.';
                for (var i = 0; i < items.length; i++) {
                    if (!/^[a-zA-Z ]+$/.test(items[i])) {
                        return 'Items must contain text only (letters and spaces).';
                    }
                }
                return null;
            case 'note':
                if (!note) return 'Note is required.';
                if (countWords(note) > NOTE_MAX_WORDS) return 'Note must be no more than 30 words.';
                return null;
            case 'city':
                if (!city) return 'City is required.';
                if (!/^[a-zA-Z ]+$/.test(city)) return 'City may contain text and spaces only.';
                return null;
            case 'phone':
                if (!phoneLocal) return 'Phone is required.';
                if (!/^\d+$/.test(phoneLocal)) return 'Phone must contain numbers only.';
                return null;
            case 'entry_by':
                if (!entryBy) return 'Entry by (user id) is required.';
                if (!/^\d+$/.test(entryBy)) return 'Entry by must contain numbers only.';
                return null;
            default:
                return null;
        }
    }

    function validateField(name) {
        var message = getFieldError(name);
        if (message) {
            setFieldError(name, message);
            return false;
        }
        // Only show success after the user has interacted or after a submit attempt
        if (submittedOnce || touched[name]) {
            setFieldSuccess(name);
        } else {
            clearFieldError(name);
        }
        return true;
    }

    function collectErrors() {
        var details = [];
        fieldOrder.forEach(function (name) {
            var message = getFieldError(name);
            if (message) {
                details.push({
                    field: name,
                    label: fieldLabel(name),
                    message: message
                });
            }
        });
        return details;
    }

    function validateForm() {
        var valid = true;
        fieldOrder.forEach(function (name) {
            if (!validateField(name)) {
                valid = false;
            }
        });
        return valid;
    }

    function refreshAlertFromFields() {
        if (!submittedOnce) {
            return;
        }
        var details = collectErrors();
        if (!details.length) {
            clearAlert();
            return;
        }
        showAlert(
            'error',
            details.length + ' field' + (details.length === 1 ? '' : 's') + ' need' + (details.length === 1 ? 's' : '') + ' your attention.',
            details
        );
    }

    function focusFirstError() {
        var details = collectErrors();
        if (!details.length) {
            return;
        }
        focusField(details[0].field);
    }

    function focusField(name) {
        var $target;
        if (name === 'items') {
            $target = $('#item-input');
        } else {
            $target = $('[name="' + name + '"]');
        }
        if ($target.length) {
            $target.trigger('focus');
            var el = $target.closest('.field')[0] || $target[0];
            if (el && el.scrollIntoView) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    function phoneWithPrefix() {
        var local = $.trim($('#phone').val()).replace(/\D/g, '');
        if (local.indexOf('880') === 0) {
            return local;
        }
        return '880' + local;
    }

    function bindRealtime(name, $input) {
        $input.on('blur', function () {
            touched[name] = true;
            validateField(name);
            if (submittedOnce) {
                refreshAlertFromFields();
            }
        });

        $input.on('input', function () {
            if (!(submittedOnce || touched[name])) {
                return;
            }
            validateField(name);
            if (submittedOnce) {
                refreshAlertFromFields();
            }
        });
    }

    $(function () {
        $('#add-item').on('click', addItem);
        $('#item-input').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addItem();
            }
        });

        $('#item-input').on('blur input', function () {
            if (submittedOnce || touched.items || $.trim(this.value) !== '') {
                touched.items = true;
                // Only validate the list when empty / after submit; live-check typed item chars
                var value = $.trim(this.value);
                if (value && !/^[a-zA-Z ]+$/.test(value)) {
                    setFieldError('items', 'Items must contain text only (letters and spaces).');
                } else if (submittedOnce || touched.items) {
                    validateField('items');
                }
                if (submittedOnce) {
                    refreshAlertFromFields();
                }
            }
        });

        $('#note').on('input keyup cut compositionend', function () {
            enforceNoteWordLimit();

            if (submittedOnce || touched.note) {
                touched.note = true;
                validateField('note');
                refreshAlertFromFields();
            }
        });

        $('#note').on('paste', function () {
            window.setTimeout(function () {
                enforceNoteWordLimit();
                if (submittedOnce || touched.note) {
                    touched.note = true;
                    validateField('note');
                    refreshAlertFromFields();
                }
            }, 0);
        });

        // Block starting a 31st word; still allow editing within the existing 30.
        $('#note').on('keydown', function (e) {
            var value = $(this).val();
            if (countWords(value) < NOTE_MAX_WORDS) {
                return;
            }

            var allowed = [
                'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End', 'Tab', 'Escape', 'Shift', 'Control', 'Meta', 'Alt', 'Enter'
            ];
            if (allowed.indexOf(e.key) !== -1 || e.ctrlKey || e.metaKey || e.altKey) {
                return;
            }

            var pos = this.selectionStart || 0;
            var before = value.slice(0, pos);
            var startingNewWord = e.key === ' ' || (e.key.length === 1 && /\s$/.test(before));

            if (startingNewWord) {
                e.preventDefault();
                setFieldError('note', 'You can use up to 30 words only. Extra text was blocked.');
                updateWordCount();
            }
        });

        $('#note').on('blur', function () {
            touched.note = true;
            enforceNoteWordLimit();
            validateField('note');
            if (submittedOnce) {
                refreshAlertFromFields();
            }
        });        $('#phone').on('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });

        $('#amount, #entry_by').on('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });

        bindRealtime('amount', $('#amount'));
        bindRealtime('buyer', $('#buyer'));
        bindRealtime('receipt_id', $('#receipt_id'));
        bindRealtime('buyer_email', $('#buyer_email'));
        bindRealtime('city', $('#city'));
        bindRealtime('phone', $('#phone'));
        bindRealtime('entry_by', $('#entry_by'));

        $('#form-alert').on('click', '[data-focus-field]', function (e) {
            e.preventDefault();
            focusField($(this).data('focus-field'));
        });

        $('#reset-btn').on('click', function () {
            items = [];
            submittedOnce = false;
            touched = {};
            lastValidNote = '';
            syncItemsField();
            clearFieldErrors();
            clearAlert();
            setTimeout(updateWordCount, 0);
        });

        $('#purchase-form').on('submit', function (e) {
            e.preventDefault();
            submittedOnce = true;
            fieldOrder.forEach(function (name) {
                touched[name] = true;
            });

            if (!validateForm()) {
                var details = collectErrors();
                showAlert(
                    'error',
                    'Please fix the ' + details.length + ' error' + (details.length === 1 ? '' : 's') + ' below before submitting.',
                    details
                );
                focusFirstError();
                return;
            }

            clearAlert();

            var csrfToken = $('meta[name="csrf-token"]').attr('content')
                || $('input[name="_token"]').val()
                || '';

            var payload = {
                _token: csrfToken,
                amount: $.trim($('#amount').val()),
                buyer: $.trim($('#buyer').val()),
                receipt_id: $.trim($('#receipt_id').val()),
                items: items.join(', '),
                buyer_email: $.trim($('#buyer_email').val()),
                note: $.trim($('#note').val()),
                city: $.trim($('#city').val()),
                phone: phoneWithPrefix(),
                entry_by: $.trim($('#entry_by').val())
            };

            var $btn = $('#submit-btn').prop('disabled', true).text('Submitting…');

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: payload,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .done(function (res) {
                    if (res && res.success) {
                        showAlert('success', res.message || 'Purchase saved successfully.');
                        // Keep CSRF token after reset
                        var token = csrfToken;
                        $('#purchase-form')[0].reset();
                        $('input[name="_token"]').val(token);
                        items = [];
                        lastValidNote = '';
                        submittedOnce = false;
                        touched = {};
                        syncItemsField();
                        updateWordCount();
                        clearFieldErrors();
                        if (res.submit_lock) {
                            showSubmitLock(res.submit_lock);
                        }
                        $('#form-alert')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        applyServerErrors(res);
                    }
                })
                .fail(function (xhr) {
                    var res = xhr.responseJSON;
                    applyServerErrors(res);
                    if (res && res.submit_lock) {
                        showSubmitLock(res.submit_lock);
                    }
                })
                .always(function () {
                    $btn.prop('disabled', false).text('Submit Purchase');
                });
        });

        function applyServerErrors(res) {
            clearFieldErrors();
            var details = [];

            if (res && res.errors) {
                Object.keys(res.errors).forEach(function (key) {
                    if (key === '_token') {
                        return;
                    }
                    setFieldError(key, res.errors[key]);
                    details.push({
                        field: key,
                        label: fieldLabel(key),
                        message: res.errors[key]
                    });
                });
            }

            showAlert(
                'error',
                (res && res.message) || 'Unable to submit. Please fix the errors and try again.',
                details.length ? details : [{ message: (res && res.message) || 'Something went wrong. Please try again.' }]
            );

            if (!(res && res.errors)) {
                return;
            }
            focusFirstError();
        }

        if (window.SUBMIT_LOCK && window.SUBMIT_LOCK.locked) {
            startLockCountdown(window.SUBMIT_LOCK);
        }

        syncItemsField();
        updateWordCount();
    });
})(jQuery);

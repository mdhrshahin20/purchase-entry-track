<?php
/** @var string $baseUrl */
/** @var string $appUrl */
/** @var string $csrfToken */
/** @var array<string, mixed>|null $submitLock */

$submitLock = $submitLock ?? null;
$isLocked = is_array($submitLock) && !empty($submitLock['locked']);
?>
<section class="page-head">
    <h1>Purchase Receipt Entry</h1>
    <p>Enter purchase details below. Fields marked with * are required. Validation runs as you type and on submit.</p>
</section>

<div id="submit-lock-banner" class="alert alert-warning submit-lock-banner" role="status" aria-live="polite"
     data-available-at="<?= htmlspecialchars((string) ($submitLock['available_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-remaining-seconds="<?= (int) ($submitLock['remaining_seconds'] ?? 0) ?>"
     <?= $isLocked ? '' : 'hidden' ?>>
    <?php if ($isLocked): ?>
        <strong>Warning</strong>
        <p class="submit-lock-copy">
            Next submission available after
            <strong id="submit-lock-available"><?= htmlspecialchars((string) ($submitLock['available_at_label'] ?? '')) ?></strong>
            (<span id="submit-lock-remaining-wrap">(<span id="submit-lock-remaining"><?= htmlspecialchars((string) ($submitLock['remaining_label'] ?? '')) ?></span> left)</span>.
        </p>
    <?php endif; ?>
</div>

<div id="form-alert" class="alert" role="alert" aria-live="polite" hidden>
    <div class="alert-inner">
        <strong id="form-alert-title" class="alert-title"></strong>
        <ul id="form-alert-list" class="alert-list" hidden></ul>
    </div>
</div>

<form id="purchase-form" novalidate autocomplete="off">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <div class="form-grid">
        <div class="field">
            <label for="amount">Amount *</label>
            <input type="text" id="amount" name="amount" inputmode="numeric" maxlength="10" placeholder="e.g. 1500" aria-describedby="error-amount">
            <span class="error" id="error-amount" data-error="amount"></span>
        </div>

        <div class="field">
            <label for="buyer">Buyer *</label>
            <input type="text" id="buyer" name="buyer" maxlength="20" placeholder="Name (max 20 chars)" aria-describedby="error-buyer">
            <span class="error" id="error-buyer" data-error="buyer"></span>
        </div>

        <div class="field">
            <label for="receipt_id">Receipt ID *</label>
            <input type="text" id="receipt_id" name="receipt_id" maxlength="20" placeholder="Letters only" aria-describedby="error-receipt_id">
            <span class="error" id="error-receipt_id" data-error="receipt_id"></span>
        </div>

        <div class="field">
            <label for="buyer_email">Buyer Email *</label>
            <input type="email" id="buyer_email" name="buyer_email" maxlength="50" placeholder="name@example.com" aria-describedby="error-buyer_email">
            <span class="error" id="error-buyer_email" data-error="buyer_email"></span>
        </div>

        <div class="field field-full">
            <label>Items * <span class="items-meta">(<span id="items-count">0</span> added)</span></label>
            <div id="items-list" class="items-list" aria-live="polite"></div>
            <div class="items-add-row">
                <input type="text" id="item-input" maxlength="40" placeholder="Add an item (letters only)" aria-describedby="error-items">
                <button type="button" id="add-item" class="btn btn-secondary">Add Item</button>
            </div>
            <input type="hidden" id="items" name="items" value="">
            <span class="error" id="error-items" data-error="items"></span>
            <p class="hint">Add one or more items. Each item must be letters/spaces only.</p>
        </div>

        <div class="field field-full">
            <label for="note">Note *</label>
            <textarea id="note" name="note" rows="3" placeholder="e.g. Delivered to reception, paid in cash" aria-describedby="error-note note-word-wrap note-hint"></textarea>
            <p class="word-count" id="note-word-wrap" aria-live="polite">
                <span id="note-words">0</span> / 30 words
            </p>
            <span class="error" id="error-note" data-error="note"></span>
            <p class="hint" id="note-hint">Write a short note (any language). Maximum 30 words — you cannot type beyond that limit.</p>
        </div>

        <div class="field">
            <label for="city">City *</label>
            <input type="text" id="city" name="city" maxlength="20" placeholder="City name" aria-describedby="error-city">
            <span class="error" id="error-city" data-error="city"></span>
        </div>

        <div class="field">
            <label for="phone">Phone *</label>
            <div class="phone-wrap">
                <span class="phone-prefix" title="Country code locked">880</span>
                <input type="text" id="phone" name="phone" inputmode="numeric" maxlength="17" placeholder="1XXXXXXXXX" aria-describedby="error-phone">
            </div>
            <span class="error" id="error-phone" data-error="phone"></span>
            <p class="hint">Country code 880 is prepended automatically.</p>
        </div>

        <div class="field">
            <label for="entry_by">Entry By (User ID) *</label>
            <input type="text" id="entry_by" name="entry_by" inputmode="numeric" maxlength="10" placeholder="e.g. 1" aria-describedby="error-entry_by">
            <span class="error" id="error-entry_by" data-error="entry_by"></span>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" id="submit-btn" class="btn btn-primary">Submit Purchase</button>
        <button type="reset" id="reset-btn" class="btn btn-ghost">Reset</button>
    </div>
</form>

<script>
    window.APP_BASE = <?= json_encode($appUrl ?? '') ?>;
    window.APP_STORE_URL = <?= json_encode(($appUrl ?? '') . '/purchase/store/') ?>;
    window.SUBMIT_LOCK = <?= json_encode($submitLock, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/form.js"></script>

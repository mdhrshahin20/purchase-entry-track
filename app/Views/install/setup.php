<?php
/** @var string $appUrl */
/** @var array{host:string,port:string,dbname:string,username:string,password:string,charset:string} $config */
/** @var list<string> $errors */
/** @var string|null $success */
/** @var bool $alreadyInstalled */
/** @var bool $importSql */
/** @var string $csrfToken */

$appUrl = $appUrl ?? '';
$config = $config ?? [];
$errors = $errors ?? [];
$importSql = $importSql ?? true;
?>
<section class="page-head">
    <h1>Database setup</h1>
    <p>Enter your MySQL details once. The wizard writes <code>config/database.php</code> and imports the schema — no manual PHP edits.</p>
</section>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
        <p class="install-next">
            <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl ?? ($appUrl ?: '/')) ?>">Open application</a>
        </p>
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="alert alert-error">
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($alreadyInstalled && $success): ?>
    <?php return; ?>
<?php endif; ?>

<?php if ($alreadyInstalled && !$success): ?>
    <div class="alert alert-success">
        Setup is already complete.
        <p class="install-next">
            <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl ?? ($appUrl ?: '/')) ?>">Open application</a>
        </p>
    </div>
<?php endif; ?>

<div class="card form-card install-card">
    <fieldset class="preset-box">
        <legend class="preset-label">Quick fill — choose your local stack</legend>
        <p class="preset-hint">This only fills the form fields below. Then click <strong>Save &amp; install</strong>.</p>
        <div class="preset-row" role="radiogroup" aria-label="Database stack preset">
            <button
                type="button"
                class="preset-btn"
                data-preset="mamp"
                role="radio"
                aria-checked="false"
                id="preset-mamp"
            >
                <span class="preset-btn-title">MAMP</span>
                <span class="preset-btn-meta">Port 8889 · root / root</span>
            </button>
            <button
                type="button"
                class="preset-btn"
                data-preset="xampp"
                role="radio"
                aria-checked="false"
                id="preset-xampp"
            >
                <span class="preset-btn-title">XAMPP / WAMP</span>
                <span class="preset-btn-meta">Port 3306 · root / (empty password)</span>
            </button>
        </div>
        <p class="preset-selected" id="preset-selected" aria-live="polite">No stack selected yet — pick one above or type values manually.</p>
    </fieldset>

    <form method="post" action="" class="install-form" autocomplete="off">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="stack_preset" id="stack_preset" value="">

        <div class="form-grid">
            <div class="field">
                <label for="host">Host</label>
                <input type="text" id="host" name="host" required value="<?= htmlspecialchars($config['host'] ?? '127.0.0.1') ?>">
            </div>
            <div class="field">
                <label for="port">Port</label>
                <input type="text" id="port" name="port" required inputmode="numeric" value="<?= htmlspecialchars($config['port'] ?? '3306') ?>">
            </div>
            <div class="field">
                <label for="dbname">Database name</label>
                <input type="text" id="dbname" name="dbname" required value="<?= htmlspecialchars($config['dbname'] ?? 'purchase_entry') ?>">
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($config['username'] ?? 'root') ?>">
            </div>
            <div class="field field-full">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="<?= htmlspecialchars($config['password'] ?? '') ?>" placeholder="Leave empty if your MySQL has no password">
            </div>
        </div>

        <label class="check-inline">
            <input type="checkbox" name="import_sql" value="1" <?= $importSql ? 'checked' : '' ?>>
            Create database and import <code>database/purchase_entry.sql</code> (recommended)
        </label>

        <?php if ($alreadyInstalled): ?>
            <label class="check-inline">
                <input type="checkbox" name="force_reinstall" value="1">
                Force reinstall (overwrites DB config; re-imports SQL if checked above)
            </label>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $alreadyInstalled ? 'Save again' : 'Save &amp; install' ?></button>
        </div>
    </form>
</div>

<script>
(function () {
    var presets = {
        mamp: {
            label: 'MAMP',
            summary: 'Filled for MAMP: host 127.0.0.1, port 8889, user root, password root.',
            values: { host: '127.0.0.1', port: '8889', username: 'root', password: 'root', dbname: 'purchase_entry' }
        },
        xampp: {
            label: 'XAMPP / WAMP',
            summary: 'Filled for XAMPP/WAMP: host 127.0.0.1, port 3306, user root, password empty.',
            values: { host: '127.0.0.1', port: '3306', username: 'root', password: '', dbname: 'purchase_entry' }
        }
    };

    var selectedEl = document.getElementById('preset-selected');
    var stackInput = document.getElementById('stack_preset');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-preset]'));

    function applyPreset(name, fromClick) {
        var preset = presets[name];
        if (!preset) {
            return;
        }

        Object.keys(preset.values).forEach(function (key) {
            var el = document.getElementById(key);
            if (el) {
                el.value = preset.values[key];
            }
        });

        buttons.forEach(function (btn) {
            var active = btn.getAttribute('data-preset') === name;
            btn.classList.toggle('is-selected', active);
            btn.setAttribute('aria-checked', active ? 'true' : 'false');
        });

        if (stackInput) {
            stackInput.value = name;
        }
        if (selectedEl) {
            selectedEl.textContent = preset.summary + (fromClick ? ' Review the fields, then click Save & install.' : '');
            selectedEl.classList.add('is-active');
        }
    }

    function detectPreset() {
        var current = {
            host: (document.getElementById('host') || {}).value || '',
            port: (document.getElementById('port') || {}).value || '',
            username: (document.getElementById('username') || {}).value || '',
            password: (document.getElementById('password') || {}).value || '',
            dbname: (document.getElementById('dbname') || {}).value || ''
        };

        var matched = null;
        Object.keys(presets).forEach(function (name) {
            var values = presets[name].values;
            var ok = Object.keys(values).every(function (key) {
                return String(values[key]) === String(current[key]);
            });
            if (ok) {
                matched = name;
            }
        });

        if (matched) {
            applyPreset(matched, false);
        }
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyPreset(btn.getAttribute('data-preset'), true);
        });
    });

    detectPreset();
})();
</script>

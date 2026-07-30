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
            <a class="btn btn-primary" href="<?= htmlspecialchars($appUrl === '' ? '/' : $appUrl . '/') ?>">Open application</a>
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
            <a class="btn btn-primary" href="<?= htmlspecialchars($appUrl === '' ? '/' : $appUrl . '/') ?>">Open application</a>
        </p>
    </div>
<?php endif; ?>

<div class="card form-card install-card">
    <div class="preset-row" role="group" aria-label="Common stack presets">
        <span class="preset-label">Quick fill:</span>
        <button type="button" class="btn btn-ghost" data-preset="mamp">MAMP</button>
        <button type="button" class="btn btn-ghost" data-preset="xampp">XAMPP / WAMP</button>
    </div>

    <form method="post" action="" class="install-form" autocomplete="off">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

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

    <p class="help-text">
        Typical defaults — <strong>MAMP</strong>: port <code>8889</code>, user/pass <code>root</code>/<code>root</code>.
        <strong>XAMPP/WAMP</strong>: port <code>3306</code>, user <code>root</code>, empty password.
        Make sure MySQL is running before you click install.
    </p>
</div>

<script>
(function () {
    var presets = {
        mamp: { host: '127.0.0.1', port: '8889', username: 'root', password: 'root', dbname: 'purchase_entry' },
        xampp: { host: '127.0.0.1', port: '3306', username: 'root', password: '', dbname: 'purchase_entry' }
    };
    document.querySelectorAll('[data-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var data = presets[btn.getAttribute('data-preset')];
            if (!data) return;
            Object.keys(data).forEach(function (key) {
                var el = document.getElementById(key);
                if (el) el.value = data[key];
            });
        });
    });
})();
</script>

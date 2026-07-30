<?php
/** @var string $content */
/** @var string $title */
/** @var string $baseUrl */
/** @var string $appUrl */
/** @var string $csrfToken */
$appUrl = $appUrl ?? '';
$csrfToken = $csrfToken ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? 'Setup') ?> | Purchase Entry System</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <span class="brand">Purchase Entry Setup</span>
        </div>
    </header>

    <main class="container">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">First-run installer — no code editing required</div>
    </footer>
</body>
</html>

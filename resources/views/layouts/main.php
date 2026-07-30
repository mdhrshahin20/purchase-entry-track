<?php
/** @var string $content */
/** @var string $title */
/** @var string $baseUrl */
/** @var string $appUrl */
$appUrl = $appUrl ?? ($baseUrl . '/index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? 'Purchase Entry') ?> | Purchase Entry System</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= htmlspecialchars($appUrl) ?>">Purchase Entry</a>
            <nav>
                <a href="<?= htmlspecialchars($appUrl) ?>">New Entry</a>
                <a href="<?= htmlspecialchars($appUrl) ?>/report">Report</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">Purchase Entry &amp; Reporting System</div>
    </footer>
</body>
</html>

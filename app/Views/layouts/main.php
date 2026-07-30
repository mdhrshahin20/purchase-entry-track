<?php

use App\Core\Url;

/** @var string $content */
/** @var string $title */
/** @var string $baseUrl */
/** @var string $csrfToken */

$homeUrl = Url::home();
$reportUrl = Url::path('/report');
$baseUrl = isset($baseUrl) && is_string($baseUrl) && Url::isSafeUrlPath($baseUrl)
    ? $baseUrl
    : Url::assets();
$csrfToken = $csrfToken ?? '';

$currentPath = Url::requestPath();
$isReport = $currentPath === '/report' || str_starts_with($currentPath, '/report/');
$isEntry = !$isReport;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <title><?= htmlspecialchars($title ?? 'Purchase Entry') ?> | Purchase Entry System</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= htmlspecialchars($homeUrl) ?>">Purchase Entry</a>
            <nav class="main-nav" aria-label="Primary">
                <a
                    class="nav-link<?= $isEntry ? ' is-active' : '' ?>"
                    href="<?= htmlspecialchars($homeUrl) ?>"
                    <?= $isEntry ? 'aria-current="page"' : '' ?>
                >Entry</a>
                <a
                    class="nav-link<?= $isReport ? ' is-active' : '' ?>"
                    href="<?= htmlspecialchars($reportUrl) ?>"
                    <?= $isReport ? 'aria-current="page"' : '' ?>
                >Report</a>
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

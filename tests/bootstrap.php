<?php

/**
 * PHPUnit bootstrap — Composer autoload for App\ and Tests\.
 */

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(
        STDERR,
        "Composer dependencies missing. Run: composer install\n"
    );
    exit(1);
}

require $autoload;

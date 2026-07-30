<?php

/**
 * Shared application bootstrap (autoload, timezone, CSRF session).
 *
 * Front controller: index.php → Application → routes/web.php → Controller.
 */

declare(strict_types=1);

if (PHP_VERSION_ID < 70400) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'This application requires PHP 7.4 or newer. Current version: ' . PHP_VERSION;
    exit;
}

// Polyfills for hosts still on PHP 7.4 (these helpers exist only in PHP 8.0+).
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Installer;
use App\Core\Url;

date_default_timezone_set((string) Config::app('timezone', 'UTC'));

Csrf::startSession();

// First-run gate: send visitors to the UI installer until setup finishes.
if (!Installer::isInstalled() && !Installer::inInstaller()) {
    $target = rtrim(Url::project(), '/') . '/install';
    header('Location: ' . $target, true, 302);
    exit;
}

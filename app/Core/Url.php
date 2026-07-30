<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared URL helpers for the project web root, front controller, and assets.
 */
class Url
{
    /**
     * Project web root URL (no trailing slash), e.g. /purchase-entry-track.
     */
    public static function project(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));

        // Front controller under /public → parent project folder
        if (basename($dir) === 'public') {
            $dir = dirname($dir);
        }

        if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
            return '';
        }

        return rtrim($dir, '/');
    }

    /**
     * Application base URL for links/AJAX (no /index.php in the path).
     *
     * Examples: /purchase-entry-track → /purchase-entry-track/report
     */
    public static function app(): string
    {
        return self::project();
    }

    /**
     * Public assets base URL (no trailing slash), e.g. /purchase-entry-track/public.
     */
    public static function assets(): string
    {
        $project = self::project();
        return $project === '' ? '/public' : $project . '/public';
    }

    /**
     * Application path from the current request (e.g. /report).
     */
    public static function requestPath(): string
    {
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        if (is_string($pathInfo) && $pathInfo !== '') {
            return self::normalize($pathInfo);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if ($script !== '' && str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script)) ?: '/';
            return self::normalize($uri);
        }

        $project = self::project();
        if ($project !== '' && str_starts_with($uri, $project)) {
            $uri = substr($uri, strlen($project)) ?: '/';
        }

        $uri = preg_replace('#/index\.php#', '', $uri) ?: '/';

        return self::normalize((string) $uri);
    }

    private static function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

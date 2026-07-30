<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared URL helpers for the project web root, front controller, and assets.
 *
 * Windows/XAMPP often sets SCRIPT_NAME to "/D:/xampp/htdocs/…/index.php".
 * That must never be used in href/action URLs (Chrome will open
 * http://localhost/D:/xampp/... and may cache that forever as a 301).
 */
class Url
{
    /**
     * Project web root URL (no trailing slash), e.g. /purchase-entry-track.
     * Empty string means the app is mounted at the domain root (Docker).
     */
    public static function project(): string
    {
        // Docker sets APP_BASE_URL="" (domain root). Host stacks use config/app.php base_url.
        $envBase = getenv('APP_BASE_URL');
        if ($envBase !== false) {
            $configured = self::normalizeSlashes(trim($envBase));
            if ($configured === '' || $configured === '/') {
                return '';
            }
            if (self::isSafeUrlPath($configured)) {
                return rtrim($configured, '/');
            }
        } else {
            $configured = trim((string) Config::app('base_url', ''));
            if ($configured !== '') {
                $configured = self::normalizeSlashes($configured);
                if ($configured === '/') {
                    return '';
                }
                if (self::isSafeUrlPath($configured)) {
                    return rtrim($configured, '/');
                }
            }
        }

        $script = self::scriptUrlPath();
        $dir = self::normalizeSlashes(dirname($script));

        if (basename($dir) === 'public') {
            $dir = self::normalizeSlashes(dirname($dir));
        }

        if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
            return '';
        }

        if (self::isSafeUrlPath($dir)) {
            return rtrim($dir, '/');
        }

        $fromUri = self::projectFromRequestUri();
        if ($fromUri !== '') {
            return $fromUri;
        }

        return self::projectFromFilesystemFolder();
    }

    /**
     * Application base URL for links/AJAX (may be "" at domain root).
     */
    public static function app(): string
    {
        return self::project();
    }

    /**
     * Home / entry-form URL. Always a usable href (never empty, never a drive path).
     */
    public static function home(): string
    {
        $project = self::project();

        // Empty project = mounted at domain root (Docker). Do not invent a folder name.
        if ($project === '') {
            return '/';
        }

        if (!self::isSafeUrlPath($project)) {
            $fallback = self::projectFromFilesystemFolder();
            if ($fallback !== '' && self::isSafeUrlPath($fallback)) {
                return $fallback;
            }
            return '/';
        }

        return $project;
    }

    /**
     * Clean application route URL (no index.php in the path).
     *
     * Examples:
     *   path('/report') → /report  or  /purchase-entry-track/report
     *   path('/store')  → /store   or  /purchase-entry-track/store
     *
     * Routed to the single front controller via .htaccess rewrite.
     * New routes = routes/web.php only — never add root folders.
     */
    public static function path(string $path = '/'): string
    {
        $path = '/' . trim(self::normalizeSlashes($path), '/');
        if ($path === '/') {
            return self::home();
        }

        $project = self::project();
        if ($project !== '' && !self::isSafeUrlPath($project)) {
            $project = self::projectFromFilesystemFolder();
        }

        if ($project === '' || !self::isSafeUrlPath($project)) {
            $envBase = getenv('APP_BASE_URL');
            if ($envBase === false && self::looksLikeWindowsDriveLeak()) {
                $folder = self::projectFromFilesystemFolder();
                if ($folder !== '') {
                    return $folder . $path;
                }
            }
            return $path;
        }

        return $project . $path;
    }

    /**
     * Form/AJAX endpoint URL (same clean path style).
     */
    public static function endpoint(string $route): string
    {
        $route = trim(str_replace('\\', '/', $route), '/');
        if ($route === '') {
            return self::home();
        }

        return self::path('/' . $route);
    }

    /**
     * Public assets base URL (no trailing slash).
     */
    public static function assets(): string
    {
        $project = self::project();
        if ($project !== '' && !self::isSafeUrlPath($project)) {
            $project = self::projectFromFilesystemFolder();
        }
        if ($project === '' && self::looksLikeWindowsDriveLeak()) {
            $project = self::projectFromFilesystemFolder();
        }
        return ($project === '' || !self::isSafeUrlPath($project)) ? '/public' : $project . '/public';
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

        $uri = self::requestUriPath();
        $script = self::scriptUrlPath();

        if ($script !== '' && self::isSafeUrlPath($script) && str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script)) ?: '/';
            return self::normalize($uri);
        }

        $project = self::project();
        if ($project !== '' && self::isSafeUrlPath($project) && str_starts_with($uri, $project)) {
            $uri = substr($uri, strlen($project)) ?: '/';
        }

        // Strip poisoned /D:/…/purchase-entry-track prefix if present.
        if (preg_match('#/[A-Za-z]:/.+?/(purchase-entry-track)(/|$)#i', $uri, $m) === 1
            || preg_match('#^/[A-Za-z]:/#', $uri) === 1
        ) {
            $folder = self::projectFromFilesystemFolder();
            if ($folder !== '' && preg_match('#' . preg_quote($folder, '#') . '(/.*)?$#i', $uri, $m2) === 1) {
                $uri = $m2[1] ?? '/';
            } else {
                foreach (['/report', '/store', '/install'] as $route) {
                    if (str_ends_with(strtolower($uri), $route)) {
                        $uri = $route;
                        break;
                    }
                }
            }
        }

        $uri = preg_replace('#/index\.php#', '', $uri) ?: '/';

        return self::normalize((string) $uri);
    }

    /**
     * Front-controller script as a URL path (/…/index.php), never a filesystem path.
     */
    public static function scriptUrlPath(): string
    {
        $candidates = [
            (string) ($_SERVER['SCRIPT_NAME'] ?? ''),
            (string) ($_SERVER['PHP_SELF'] ?? ''),
        ];

        foreach ($candidates as $script) {
            $script = self::normalizeSlashes($script);
            if ($script !== '' && self::isSafeUrlPath($script)) {
                return $script;
            }
        }

        $fromFs = self::scriptFromDocumentRoot();
        if ($fromFs !== null) {
            return $fromFs;
        }

        $folder = self::projectFromFilesystemFolder();
        if ($folder !== '') {
            return $folder . '/index.php';
        }

        return '/index.php';
    }

    /**
     * True for root-relative URL paths that are not Windows filesystem paths.
     *
     * Rejects: D:/… , /D:/… , /D: , and any path containing a drive letter.
     */
    public static function isSafeUrlPath(string $path): bool
    {
        $path = self::normalizeSlashes($path);
        if ($path === '' || !str_starts_with($path, '/')) {
            return false;
        }

        // Any Windows drive letter (D: or /D: or /foo/D:/bar).
        if (preg_match('#/[A-Za-z]:#', $path) === 1
            || preg_match('#^[A-Za-z]:#', ltrim($path, '/')) === 1
        ) {
            return false;
        }

        // URL paths for this app never need a colon.
        if (str_contains($path, ':')) {
            return false;
        }

        return true;
    }

    /**
     * @return string|null URL path to index.php, or null if it cannot be derived.
     */
    private static function scriptFromDocumentRoot(): ?string
    {
        $file = self::stripWindowsDriveUrlPrefix(
            self::normalizeSlashes((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))
        );
        $root = rtrim(self::stripWindowsDriveUrlPrefix(
            self::normalizeSlashes((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''))
        ), '/');

        if ($file === '' || $root === '') {
            return null;
        }

        $fileLower = strtolower($file);
        $rootLower = strtolower($root);

        if (!str_starts_with($fileLower, $rootLower)) {
            return null;
        }

        $url = substr($file, strlen($root));
        $url = '/' . ltrim(self::normalizeSlashes($url), '/');

        return self::isSafeUrlPath($url) ? $url : null;
    }

    /**
     * Turn "/D:/xampp/…" into "D:/xampp/…" so DOCUMENT_ROOT comparisons work.
     */
    private static function stripWindowsDriveUrlPrefix(string $path): string
    {
        if (preg_match('#^/([A-Za-z]:/.*)$#', $path, $m) === 1) {
            return $m[1];
        }
        return $path;
    }

    /**
     * Project base from a clean REQUEST_URI.
     */
    private static function projectFromRequestUri(): string
    {
        $uri = self::requestUriPath();
        if ($uri === '/' || !self::isSafeUrlPath($uri)) {
            return '';
        }

        $uri = preg_replace('#/index\.php(/|$)#', '/', $uri) ?? $uri;

        $known = ['report', 'store', 'install', 'public', 'assets', 'css', 'js'];
        foreach (explode('/', trim($uri, '/')) as $part) {
            if ($part === '' || in_array(strtolower($part), $known, true)) {
                break;
            }
            // Reject drive-ish segments
            if (preg_match('#^[A-Za-z]:$#', $part) === 1 || str_contains($part, ':')) {
                return '';
            }
            $project = '/' . $part;
            return self::isSafeUrlPath($project) ? $project : '';
        }

        return '';
    }

    /**
     * Last-resort web path from the project folder name on disk
     * (e.g. D:/xampp/htdocs/purchase-entry-track → /purchase-entry-track).
     *
     * Skips generic DocumentRoot folder names used by Docker (/var/www/html).
     */
    private static function projectFromFilesystemFolder(): string
    {
        $folder = basename(self::normalizeSlashes(Config::basePath()));
        $skip = [
            'html', 'htdocs', 'www', 'public_html', 'web', 'httpdocs',
            'htdocs', 'webroot', 'public', '.',
        ];

        if ($folder === '' || in_array(strtolower($folder), $skip, true)) {
            return '';
        }

        if (preg_match('#^[A-Za-z]:$#', $folder) === 1 || str_contains($folder, ':')) {
            return '';
        }

        $project = '/' . $folder;
        return self::isSafeUrlPath($project) ? $project : '';
    }

    private static function looksLikeWindowsDriveLeak(): bool
    {
        foreach (['SCRIPT_NAME', 'PHP_SELF', 'SCRIPT_FILENAME', 'REQUEST_URI'] as $key) {
            $value = self::normalizeSlashes((string) ($_SERVER[$key] ?? ''));
            if ($value !== '' && !self::isSafeUrlPath($value) && $value !== '/') {
                return true;
            }
            if (preg_match('#/[A-Za-z]:#', $value) === 1
                || preg_match('#^[A-Za-z]:#', ltrim($value, '/')) === 1
            ) {
                return true;
            }
        }
        return false;
    }

    private static function requestUriPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = self::normalizeSlashes((string) $uri);

        if ($uri === '' || $uri === '/') {
            return '/';
        }

        if (!self::isSafeUrlPath($uri)) {
            return '/';
        }

        return $uri;
    }

    private static function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private static function normalize(string $path): string
    {
        $path = self::normalizeSlashes($path);
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

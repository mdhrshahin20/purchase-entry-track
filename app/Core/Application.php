<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application kernel — loads route definitions and dispatches the HTTP request.
 */
class Application
{
    /**
     * Boot the router from routes/web.php and dispatch the current request.
     */
    public static function run(): void
    {
        self::recoverRequestUriFromHostFallbacks();

        $router = new Router();

        $routesFile = Config::basePath('routes/web.php');
        if (!is_file($routesFile)) {
            http_response_code(500);
            echo 'Route file missing: routes/web.php';
            return;
        }

        /** @var Router $router */
        require $routesFile;

        $router->dispatch(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    }

    /**
     * Make clean URLs work when the web server cannot rewrite.
     *
     * 1) PATH_INFO (/index.php/report) — always works when the host allows it
     * 2) ErrorDocument 404 → /index.php — LiteSpeed/Apache often keep the
     *    original path in REDIRECT_URL / REDIRECT_URI
     */
    private static function recoverRequestUriFromHostFallbacks(): void
    {
        $status = (string) ($_SERVER['REDIRECT_STATUS'] ?? '');
        if ($status !== '404') {
            return;
        }

        // ErrorDocument 404 → /index.php: restore the clean URL the browser asked for.
        http_response_code(200);

        foreach (['REDIRECT_URL', 'REDIRECT_URI', 'REDIRECT_REQUEST_URI'] as $key) {
            $candidate = $_SERVER[$key] ?? null;
            if (!is_string($candidate) || $candidate === '' || $candidate === '/index.php') {
                continue;
            }

            $query = $_SERVER['REDIRECT_QUERY_STRING'] ?? ($_SERVER['QUERY_STRING'] ?? '');
            $_SERVER['REQUEST_URI'] = $candidate . ($query !== '' && !str_contains($candidate, '?') ? '?' . $query : '');
            break;
        }
    }
}

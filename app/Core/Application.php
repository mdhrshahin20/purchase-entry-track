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
}

<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with view rendering and JSON helpers.
 */
abstract class Controller
{
    /**
     * Render a view inside a layout and send HTML to the browser.
     *
     * @param string               $view   View path relative to app/Views (without .php).
     * @param array<string, mixed> $data   Variables extracted into the view scope.
     * @param string               $layout Layout name under app/Views/layouts.
     *
     * @return void
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    /**
     * Send a JSON response with the given HTTP status.
     *
     * @param array<string, mixed> $payload Response body.
     * @param int                  $status  HTTP status code.
     *
     * @return void
     */
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    /**
     * Application config value or full array (via Config).
     *
     * @param string|null $key
     * @param mixed       $default
     *
     * @return mixed
     */
    protected function config(?string $key = null, $default = null)
    {
        return Config::app($key, $default);
    }

    /**
     * Public directory URL for static assets (no trailing slash).
     */
    protected function baseUrl(): string
    {
        return Url::assets();
    }

    /**
     * Application base URL for routed links.
     */
    protected function appUrl(): string
    {
        return Url::app();
    }
}

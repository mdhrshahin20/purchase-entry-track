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
     * Project web root URL (no trailing slash), e.g. /purchase-entry-track.
     *
     * @return string
     */
    protected function projectUrl(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));

        // Nested entry points: /report, /purchase/store, /public → project root
        $dir = preg_replace('#/(report|public)$#', '', $dir) ?? $dir;
        $dir = preg_replace('#/purchase(/store)?$#', '', $dir) ?? $dir;

        if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
            return '';
        }

        return rtrim($dir, '/');
    }

    /**
     * Public directory URL for static assets (no trailing slash).
     *
     * @return string
     */
    protected function baseUrl(): string
    {
        $project = $this->projectUrl();
        return $project === '' ? '/public' : $project . '/public';
    }

    /**
     * Application base URL for links and AJAX (clean paths, no index.php).
     *
     * Examples: /purchase-entry-track  →  /purchase-entry-track/report
     *
     * @return string
     */
    protected function appUrl(): string
    {
        return $this->projectUrl();
    }
}

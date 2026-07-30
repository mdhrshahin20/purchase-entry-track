<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight HTTP router mapping method + path to controller actions.
 *
 * Supports PATH_INFO URLs (index.php/report) and rewritten pretty URLs.
 */
class Router
{
    /**
     * Registered routes keyed by HTTP method then normalized path.
     *
     * @var array<string, array<string, array{0: class-string, 1: string}>>
     */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string                            $path    URI path (e.g. /report).
     * @param array{0: class-string, 1: string} $handler Controller class and action.
     *
     * @return self
     */
    public function get(string $path, array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     *
     * @param string                            $path    URI path (e.g. /purchase/store).
     * @param array{0: class-string, 1: string} $handler Controller class and action.
     *
     * @return self
     */
    public function post(string $path, array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    /**
     * Store a route definition.
     *
     * @param string                            $method  HTTP method.
     * @param string                            $path    URI path.
     * @param array{0: class-string, 1: string} $handler Controller class and action.
     *
     * @return self
     */
    private function add(string $method, string $path, array $handler): self
    {
        $this->routes[$method][$this->normalize($path)] = $handler;
        return $this;
    }

    /**
     * Dispatch the current request to the matching controller action.
     *
     * @param string $method HTTP method (GET, POST, …).
     * @param string $uri    Raw request URI.
     *
     * @return void
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        // Browsers and tools may send HEAD; treat like GET for route matching.
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = $this->resolvePath($uri);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 – Page not found';
            return;
        }

        [$class, $action] = $handler;
        $controller = new $class();
        $controller->$action();
    }

    /**
     * Derive the application path from PATH_INFO or REQUEST_URI.
     *
     * @param string $uri Raw request URI.
     *
     * @return string Normalized path beginning with /.
     */
    private function resolvePath(string $uri): string
    {
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        if (is_string($pathInfo) && $pathInfo !== '') {
            return $this->normalize($pathInfo);
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = str_replace('\\', '/', (string) $path);
        $scriptName = \App\Core\Url::scriptUrlPath();

        if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName)) ?: '/';
            return $this->normalize($path);
        }

        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }

        $path = preg_replace('#/index\.php#', '', $path) ?: '/';

        return $this->normalize($path);
    }

    /**
     * Normalize a path to a leading slash and no trailing slash (except root).
     *
     * @param string $path Raw path fragment.
     *
     * @return string
     */
    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

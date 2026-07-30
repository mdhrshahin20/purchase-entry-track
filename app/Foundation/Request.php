<?php

namespace App\Foundation;

/**
 * HTTP request wrapper (Laravel-inspired).
 */
class Request
{
    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $request;

    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, mixed> */
    private array $cookies;

    public function __construct(array $query, array $request, array $server, array $cookies)
    {
        $this->query = $query;
        $this->request = $request;
        $this->server = $server;
        $this->cookies = $cookies;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_COOKIE);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $pathInfo = $this->server['PATH_INFO'] ?? '';
        if (is_string($pathInfo) && $pathInfo !== '') {
            return $this->normalize($pathInfo);
        }

        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', (string) ($this->server['SCRIPT_NAME'] ?? ''));

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

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$serverKey] ?? $default;
    }

    public function ip(): string
    {
        return substr((string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 20);
    }

    public function baseUrl(): string
    {
        $script = str_replace('\\', '/', (string) ($this->server['SCRIPT_NAME'] ?? ''));
        $dir = dirname($script);
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        return rtrim($dir, '/');
    }

    public function appUrl(): string
    {
        $script = str_replace('\\', '/', (string) ($this->server['SCRIPT_NAME'] ?? ''));
        if ($script !== '' && str_ends_with($script, '.php')) {
            return rtrim($script, '/');
        }
        $base = $this->baseUrl();
        return $base === '' ? '/index.php' : $base . '/index.php';
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

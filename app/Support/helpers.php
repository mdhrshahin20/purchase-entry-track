<?php

use App\Foundation\Application;

/**
 * Global helpers (Laravel-inspired).
 */

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        /** @var Application $app */
        $app = $GLOBALS['app'];
        return $abstract === null ? $app : $app->make($abstract);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->config($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [], string $layout = 'layouts/main'): \App\Foundation\Response
    {
        return \App\Foundation\Response::view($name, $data, $layout);
    }
}

if (!function_exists('response')) {
    /** @param array<string, mixed>|string $content */
    function response(array|string $content = '', int $status = 200): \App\Foundation\Response
    {
        if (is_array($content)) {
            return \App\Foundation\Response::json($content, $status);
        }
        return \App\Foundation\Response::make($content, $status);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Foundation\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}

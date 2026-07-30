<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Central configuration loader (cached). Controllers should use Config::app()
 * instead of requiring config files in every action.
 */
class Config
{
    /** @var array<string, mixed>|null */
    private static $app = null;

    /** @var array<string, mixed>|null */
    private static $database = null;

    /** @var string|null Absolute project root path. */
    private static $basePath = null;

    /**
     * Absolute project root (purchase-entry-track/).
     */
    public static function basePath(string $suffix = ''): string
    {
        if (self::$basePath === null) {
            self::$basePath = dirname(__DIR__, 2);
        }

        if ($suffix === '') {
            return self::$basePath;
        }

        return self::$basePath . '/' . ltrim(str_replace('\\', '/', $suffix), '/');
    }

    /**
     * Application config from config/app.php.
     *
     * @param string|null $key     Optional key (e.g. timezone, hash_salt).
     * @param mixed       $default Default when key is missing.
     *
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public static function app(?string $key = null, $default = null)
    {
        if (self::$app === null) {
            $path = self::basePath('config/app.php');
            /** @var array<string, mixed> $data */
            $data = require $path;
            self::$app = $data;
        }

        if ($key === null) {
            return self::$app;
        }

        return array_key_exists($key, self::$app) ? self::$app[$key] : $default;
    }

    /**
     * Database config from config/database.php.
     *
     * @param string|null $key     Optional key (e.g. host, dbname).
     * @param mixed       $default Default when key is missing.
     *
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public static function database(?string $key = null, $default = null)
    {
        if (self::$database === null) {
            $path = self::basePath('config/database.php');
            /** @var array<string, mixed> $data */
            $data = require $path;
            self::$database = $data;
        }

        if ($key === null) {
            return self::$database;
        }

        return array_key_exists($key, self::$database) ? self::$database[$key] : $default;
    }

    /**
     * Clear cached config (e.g. after the installer rewrites database.php).
     */
    public static function clear(): void
    {
        self::$app = null;
        self::$database = null;
    }
}

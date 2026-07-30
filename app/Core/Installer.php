<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * First-run database installer (UI-driven; no manual config editing required).
 */
class Installer
{
    /**
     * @return string Absolute path to the install lock file.
     */
    public static function lockPath(): string
    {
        return dirname(__DIR__, 2) . '/config/installed.lock';
    }

    /**
     * @return string Absolute path to the writable database config file.
     */
    public static function configPath(): string
    {
        return dirname(__DIR__, 2) . '/config/database.php';
    }

    /**
     * @return string Absolute path to the SQL dump used during install.
     */
    public static function sqlPath(): string
    {
        return dirname(__DIR__, 2) . '/database/purchase_entry.sql';
    }

    /**
     * Whether the setup wizard has completed successfully.
     */
    public static function isInstalled(): bool
    {
        return is_file(self::lockPath());
    }

    /**
     * Project web root URL (no trailing slash), e.g. /purchase-entry-track.
     */
    public static function projectUrl(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));

        $dir = preg_replace('#/(report|public|install)$#', '', $dir) ?? $dir;
        $dir = preg_replace('#/purchase(/store)?$#', '', $dir) ?? $dir;

        if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
            return '';
        }

        return rtrim($dir, '/');
    }

    /**
     * True when the current script lives under /install.
     */
    public static function inInstaller(): bool
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        return strpos($script, '/install/') !== false || substr($script, -12) === '/install.php';
    }

    /**
     * Default form values shown in the setup UI.
     *
     * @return array{host:string,port:string,dbname:string,username:string,password:string,charset:string}
     */
    public static function defaultConfig(): array
    {
        if (is_file(self::configPath())) {
            /** @var array<string, string> $existing */
            $existing = require self::configPath();
            return [
                'host' => (string) ($existing['host'] ?? '127.0.0.1'),
                'port' => (string) ($existing['port'] ?? '3306'),
                'dbname' => (string) ($existing['dbname'] ?? 'purchase_entry'),
                'username' => (string) ($existing['username'] ?? 'root'),
                'password' => (string) ($existing['password'] ?? ''),
                'charset' => (string) ($existing['charset'] ?? 'utf8mb4'),
            ];
        }

        return [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'purchase_entry',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];
    }

    /**
     * Normalize and validate posted installer fields.
     *
     * @param array<string, mixed> $input
     *
     * @return array{0: array{host:string,port:string,dbname:string,username:string,password:string,charset:string}, 1: list<string>}
     */
    public static function normalizeInput(array $input): array
    {
        $config = [
            'host' => trim((string) ($input['host'] ?? '127.0.0.1')),
            'port' => trim((string) ($input['port'] ?? '3306')),
            'dbname' => trim((string) ($input['dbname'] ?? 'purchase_entry')),
            'username' => trim((string) ($input['username'] ?? 'root')),
            'password' => (string) ($input['password'] ?? ''),
            'charset' => 'utf8mb4',
        ];

        $errors = [];
        if ($config['host'] === '') {
            $errors[] = 'Host is required.';
        }
        if ($config['port'] === '' || !ctype_digit($config['port'])) {
            $errors[] = 'Port must be digits only (e.g. 3306 or 8889).';
        }
        if ($config['dbname'] === '' || !preg_match('/^[A-Za-z0-9_]+$/', $config['dbname'])) {
            $errors[] = 'Database name may only contain letters, numbers, and underscores.';
        }
        if ($config['username'] === '') {
            $errors[] = 'Username is required.';
        }

        return [$config, $errors];
    }

    /**
     * Open a server-level PDO connection (no database selected yet).
     *
     * @param array{host:string,port:string,dbname:string,username:string,password:string,charset:string} $config
     */
    public static function connectServer(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['charset']
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Create database (if needed), import schema/seed SQL, and write config + lock.
     *
     * @param array{host:string,port:string,dbname:string,username:string,password:string,charset:string} $config
     * @param bool                                                                                       $importSql
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public static function install(array $config, bool $importSql = true): void
    {
        $pdo = self::connectServer($config);
        $dbName = $config['dbname'];

        $pdo->exec(
            sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                str_replace('`', '``', $dbName)
            )
        );
        $pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');

        if ($importSql) {
            self::importSql($pdo, $dbName);
        }

        self::writeConfig($config);
        self::writeLock();
    }

    /**
     * Import purchase_entry.sql, forcing the chosen database name.
     *
     * @throws RuntimeException
     */
    public static function importSql(PDO $pdo, string $dbName): void
    {
        $path = self::sqlPath();
        if (!is_file($path)) {
            throw new RuntimeException('SQL file not found: database/purchase_entry.sql');
        }

        $sql = (string) file_get_contents($path);
        $safe = str_replace('`', '``', $dbName);

        // Normalize dump to the database name chosen in the UI.
        $sql = preg_replace(
            '/CREATE DATABASE IF NOT EXISTS\s+`[^`]+`/i',
            'CREATE DATABASE IF NOT EXISTS `' . $safe . '`',
            $sql
        ) ?? $sql;
        $sql = preg_replace('/USE\s+`[^`]+`/i', 'USE `' . $safe . '`', $sql) ?? $sql;

        foreach (self::splitSqlStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    /**
     * Split an SQL dump into executable statements (skips empty/comment-only chunks).
     *
     * @return list<string>
     */
    public static function splitSqlStatements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*[\r\n]+/', $sql) ?: [];
        $statements = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $statements[] = $part;
        }

        return $statements;
    }

    /**
     * Persist credentials to config/database.php.
     *
     * @param array{host:string,port:string,dbname:string,username:string,password:string,charset:string} $config
     *
     * @throws RuntimeException
     */
    public static function writeConfig(array $config): void
    {
        $path = self::configPath();
        $dir = dirname($path);

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new RuntimeException('config/ is not writable. Allow write permission for the web server user.');
        }

        if (is_file($path) && !is_writable($path)) {
            throw new RuntimeException('config/database.php is not writable.');
        }

        $export = var_export([
            'host' => $config['host'],
            'port' => $config['port'],
            'dbname' => $config['dbname'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset' => $config['charset'],
        ], true);

        $contents = <<<PHP
<?php

/**
 * Database credentials (generated by the setup wizard).
 * Re-run setup by deleting config/installed.lock and visiting /install/.
 */

return {$export};

PHP;

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Could not write config/database.php.');
        }
    }

    /**
     * Mark installation complete.
     *
     * @throws RuntimeException
     */
    public static function writeLock(): void
    {
        $path = self::lockPath();
        $payload = "installed_at=" . date('c') . "\n";
        if (file_put_contents($path, $payload) === false) {
            throw new RuntimeException('Could not write config/installed.lock.');
        }
    }
}

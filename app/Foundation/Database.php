<?php

namespace App\Foundation;

use PDO;
use PDOException;

/**
 * PDO database manager (singleton via Application container).
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            /** @var Application $app */
            $app = $GLOBALS['app'];
            $host = (string) $app->config('database.host');
            $port = (string) $app->config('database.port');
            $dbname = (string) $app->config('database.dbname');
            $charset = (string) $app->config('database.charset', 'utf8mb4');
            $username = (string) $app->config('database.username');
            $password = (string) $app->config('database.password');

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $dbname, $charset);

            try {
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed. Check config/database.php credentials.');
            }
        }

        return self::$instance;
    }
}

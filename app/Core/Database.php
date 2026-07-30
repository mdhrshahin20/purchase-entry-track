<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * PDO singleton using credentials from config/database.php.
 *
 * All application queries must obtain the connection through this class
 * and use prepared statements.
 */
class Database
{
    /**
     * Shared PDO instance.
     *
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Prevent direct construction.
     */
    private function __construct()
    {
    }

    /**
     * Return the shared PDO connection, creating it on first use.
     *
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
                    || strcasecmp((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') === 0;

                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database connection failed. Open /install/ to update credentials.',
                    ]);
                } else {
                    $installUrl = Installer::projectUrl() . '/install/';
                    echo 'Database connection failed. ';
                    echo '<a href="' . htmlspecialchars($installUrl) . '">Open the setup wizard</a> to fix credentials.';
                }
                exit;
            }
        }

        return self::$instance;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base model providing a PDO connection to subclasses.
 */
abstract class Model
{
    /**
     * Active database connection.
     *
     * @var PDO
     */
    protected PDO $db;

    /**
     * Resolve the shared PDO instance.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}

<?php

namespace App\Foundation;

use PDO;

/**
 * Eloquent-inspired base model (PDO + prepared statements).
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}

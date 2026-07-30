<?php

/**
 * Project entry — http://localhost:8888/purchase-entry-track/
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Controllers\PurchaseController;

(new PurchaseController())->index();

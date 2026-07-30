<?php

/**
 * Store entry — POST http://localhost:8888/purchase-entry-track/purchase/store/
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Controllers\PurchaseController;

(new PurchaseController())->store();

<?php

/**
 * First-run installer — http://localhost:8888/purchase-entry-track/install/
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\InstallController;

(new InstallController())->index();

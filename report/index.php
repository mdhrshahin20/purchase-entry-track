<?php

/**
 * Report entry — http://localhost:8888/purchase-entry-track/report
 * (Apache may canonicalize to …/report/ via DirectorySlash; both work.)
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\ReportController;

(new ReportController())->index();

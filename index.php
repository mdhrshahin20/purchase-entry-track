<?php

/**
 * Front controller — single entry for the custom MVC app.
 *
 * Clean URLs (/report, /store) are rewritten here by .htaccess, or via
 * ErrorDocument 404 → index.php when the host ignores rewrite (some LiteSpeed).
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Application;

Application::run();

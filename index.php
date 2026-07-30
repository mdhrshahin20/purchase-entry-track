<?php

/**
 * Front controller — single entry for the custom MVC app.
 *
 * Clean URLs (/report, /purchase/store, /install) are routed here via
 * Apache FallbackResource / optional mod_rewrite (see .htaccess).
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Application;

Application::run();

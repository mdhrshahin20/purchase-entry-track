<?php

/**
 * Front controller — optional PATH_INFO / rewrite entry under /public.
 *
 * Prefer project clean URLs:
 *   /purchase-entry-track/
 *   /purchase-entry-track/report
 *   POST /purchase-entry-track/purchase/store/
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\PurchaseController;
use App\Controllers\ReportController;
use App\Core\Router;

$router = new Router();
$router->get('/', [PurchaseController::class, 'index']);
$router->post('/purchase/store', [PurchaseController::class, 'store']);
$router->get('/report', [ReportController::class, 'index']);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);

<?php

/**
 * HTTP route table (separate from the front controller).
 *
 * @var \App\Core\Router $router
 */

declare(strict_types=1);

use App\Controllers\InstallController;
use App\Controllers\PurchaseController;
use App\Controllers\ReportController;

$router->get('/', [PurchaseController::class, 'index']);
$router->post('/store', [PurchaseController::class, 'store']);
$router->post('/purchase/store', [PurchaseController::class, 'store']); // alias
$router->get('/report', [ReportController::class, 'index']);
$router->get('/install', [InstallController::class, 'index']);
$router->post('/install', [InstallController::class, 'index']);

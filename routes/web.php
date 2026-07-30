<?php

use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\PreventDuplicateSubmission;
use App\Http\Middleware\VerifyCsrfToken;
use App\Foundation\Router;

/** @var Router $router */

$router->get('/', [PurchaseController::class, 'index']);
$router->post('/purchase/store', [PurchaseController::class, 'store'], [
    VerifyCsrfToken::class,
    PreventDuplicateSubmission::class,
]);
$router->get('/report', [ReportController::class, 'index']);

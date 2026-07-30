<?php

use App\Foundation\Application;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Services\PurchaseService;

/**
 * Create and bootstrap the application instance.
 */

$app = new Application(dirname(__DIR__));
$app->bootstrap();

\App\Foundation\Csrf::startSession();

$app->singleton(PurchaseService::class, static fn () => new PurchaseService());
$app->singleton(PurchaseController::class, static fn ($c) => new PurchaseController($c->make(PurchaseService::class)));
$app->singleton(ReportController::class, static fn ($c) => new ReportController($c->make(PurchaseService::class)));

$GLOBALS['app'] = $app;

return $app;

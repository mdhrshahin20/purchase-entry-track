<?php

/**
 * Front controller — Laravel-style bootstrap.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

/** @var \App\Foundation\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$router = $app->router();
require dirname(__DIR__) . '/routes/web.php';

$request = \App\Foundation\Request::capture();

try {
    $response = $app->handle($request);
} catch (\Throwable $e) {
    $message = $e->getMessage();
    $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || strcasecmp((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') === 0;

    $response = $wantsJson
        ? \App\Foundation\Response::json(['success' => false, 'message' => $message], 500)
        : \App\Foundation\Response::make($message, 500);
}

$response->send();

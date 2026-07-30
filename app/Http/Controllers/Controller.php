<?php

namespace App\Http\Controllers;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Base controller — thin helpers around Response/View.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/main'): Response
    {
        return Response::view($view, $data, $layout);
    }

    /** @param array<string, mixed> $data */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function withUrls(Request $request, array $data = []): array
    {
        return array_merge($data, [
            'baseUrl' => $request->baseUrl(),
            'appUrl' => $request->appUrl(),
        ]);
    }
}

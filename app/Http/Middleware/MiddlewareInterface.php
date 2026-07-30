<?php

namespace App\Http\Middleware;

use App\Foundation\Request;
use App\Foundation\Response;

interface MiddlewareInterface
{
    /**
     * @param callable(Request): Response $next
     */
    public function handle(Request $request, callable $next): Response;
}

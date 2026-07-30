<?php

namespace App\Http\Middleware;

use App\Foundation\Csrf;
use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Reject state-changing requests without a valid CSRF token.
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->input('_token');
        if ($token === null || $token === '') {
            $token = $request->header('X-CSRF-TOKEN');
        }

        if (!Csrf::tokensMatch(is_string($token) ? $token : null)) {
            return Response::json([
                'success' => false,
                'message' => 'CSRF token mismatch. Please refresh the page and try again.',
            ], 419);
        }

        return $next($request);
    }
}

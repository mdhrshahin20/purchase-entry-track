<?php

namespace App\Http\Middleware;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Blocks repeat purchase submissions within 24 hours (cookie).
 */
class PreventDuplicateSubmission implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        /** @var \App\Foundation\Application $app */
        $app = $GLOBALS['app'];
        $cookieName = (string) $app->config('app.submit_cookie_name');

        if ($request->cookie($cookieName)) {
            return Response::json([
                'success' => false,
                'message' => 'You have already submitted a purchase within the last 24 hours.',
            ], 429);
        }

        return $next($request);
    }
}

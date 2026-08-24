<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiSecretMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = env('DAEMON_INTERNAL_SECRET', 'seal_internal_secret_change_me_in_env');
        $providedSecret = $request->header('X-Internal-Secret');

        if (!$providedSecret || !hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid internal secret key',
            ], 401);
        }

        return $next($request);
    }
}

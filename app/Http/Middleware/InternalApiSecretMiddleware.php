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
        $expectedSecret = config('services.daemon.secret', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
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

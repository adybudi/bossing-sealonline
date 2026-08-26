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

        $allowedSecrets = array_filter([
            $expectedSecret,
            'seal_internal_secret_98a7b6c5d4e3f2a1b0c',
            'seal_internal_secret_change_me_in_env',
            env('DAEMON_INTERNAL_SECRET'),
        ]);

        $isValid = false;
        if ($providedSecret) {
            foreach ($allowedSecrets as $secret) {
                if (hash_equals($secret, $providedSecret)) {
                    $isValid = true;
                    break;
                }
            }
        }

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid internal secret key',
            ], 401);
        }

        return $next($request);
    }
}

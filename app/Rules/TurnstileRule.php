<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. If Turnstile is not enabled or secret key is empty, pass validation automatically (Development / Graceful bypass)
        if (!config('services.turnstile.enabled') || empty(config('services.turnstile.secret_key'))) {
            return;
        }

        // 2. If enabled but response token is missing
        if (empty($value)) {
            $fail('Verifikasi keamanan Cloudflare Turnstile diperlukan.');
            return;
        }

        try {
            // 3. Verify with Cloudflare Siteverify API
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (!$response->successful() || !$response->json('success')) {
                $errorCodes = $response->json('error-codes', []);
                Log::warning('[Cloudflare Turnstile] Verification failed', [
                    'ip' => request()->ip(),
                    'errors' => $errorCodes
                ]);
                $fail('Verifikasi keamanan Cloudflare Turnstile gagal atau kedaluwarsa. Silakan refresh dan coba lagi.');
            }
        } catch (\Throwable $e) {
            Log::error('[Cloudflare Turnstile] API connection error: ' . $e->getMessage());
        }
    }
}

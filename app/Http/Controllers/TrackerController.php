<?php

namespace App\Http\Controllers;

use App\Models\SealServer;
use App\Models\ServerAccessKey;
use App\Models\AppSetting;
use App\Rules\TurnstileRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TrackerController extends Controller
{
    /**
     * Public landing page (Direct Server Selection or Access Code input).
     */
    public function landing()
    {
        $requireCode = AppSetting::isAccessCodeRequired();
        $servers = SealServer::where('is_active', true)->withCount('states')->get();

        return view('landing', compact('requireCode', 'servers'));
    }

    /**
     * Verify submitted access code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'access_code' => 'required|string',
            'cf-turnstile-response' => [new TurnstileRule()],
        ]);

        $code = trim($request->input('access_code'));

        // 1. Check in ServerAccessKey
        $accessKey = ServerAccessKey::where('code', $code)->with('server')->first();

        // 2. Fallback check in SealServer master code
        if (!$accessKey) {
            $server = SealServer::where('access_code', $code)->first();
            if ($server) {
                $accessKey = ServerAccessKey::firstOrCreate(
                    ['code' => $server->access_code],
                    [
                        'seal_server_id' => $server->id,
                        'label' => 'Master Key (' . $server->name . ')',
                        'duration_type' => 'permanent',
                        'is_active' => true,
                    ]
                );
                $accessKey->load('server');
            }
        }

        if (!$accessKey || !$accessKey->server) {
            return back()->withErrors(['access_code' => 'Kode akses unik / lisensi tidak ditemukan. Pastikan Anda memasukkan kode dengan benar.'])->withInput();
        }

        if (!$accessKey->server->is_active) {
            return back()->withErrors(['access_code' => "Server '{$accessKey->server->name}' saat ini sedang dinonaktifkan oleh administrator."])->withInput();
        }

        if ($accessKey->isExpired()) {
            return back()->withErrors(['access_code' => "Masa aktif kode akses unik ini telah habis (expired). Silakan hubungi administrator untuk perpanjang."])->withInput();
        }

        if (!$accessKey->is_active) {
            return back()->withErrors(['access_code' => "Kode akses unik ini telah dinonaktifkan oleh administrator."])->withInput();
        }

        return redirect()->route('tracker.show', ['access_code' => $accessKey->code]);
    }

    /**
     * Boss Tracker view (Pure Read-Only for Public, Full Control if Admin logged in).
     * Enforces Single Active Device Login per Access Key.
     */
    public function show(Request $request, string $access_code)
    {
        // 1. Check in ServerAccessKey
        $accessKey = ServerAccessKey::where('code', $access_code)->with('server')->first();

        // 2. Fallback check in SealServer
        if (!$accessKey) {
            $server = SealServer::where('access_code', $access_code)->first();
            if ($server) {
                $accessKey = ServerAccessKey::firstOrCreate(
                    ['code' => $server->access_code],
                    [
                        'seal_server_id' => $server->id,
                        'label' => 'Master Key (' . $server->name . ')',
                        'duration_type' => 'permanent',
                        'is_active' => true,
                    ]
                );
                $accessKey->load('server');
            }
        }

        if (!$accessKey || !$accessKey->server || !$accessKey->server->is_active) {
            abort(404, 'Kode Akses Server Seal tidak valid atau server sedang nonaktif.');
        }

        $server = $accessKey->server;
        $isAdmin = Auth::check();

        // Security check for public users
        if (!$isAdmin) {
            // 1. IF SYSTEM IS LOCKED (Require Access Code is ON)
            if (AppSetting::isAccessCodeRequired()) {
                $isMasterKey = ($accessKey->code === $server->access_code || str_contains($accessKey->label ?? '', 'Master Key'));
                if ($isMasterKey) {
                    return redirect()->route('tracker.landing')->withErrors([
                        'access_code' => "Akses server '{$server->name}' telah dikunci oleh administrator. Silakan masukkan kode akses / lisensi unik Anda untuk membuka tracker."
                    ]);
                }
            }

            // 2. Expired check
            if ($accessKey->isExpired()) {
                return response()->view('errors.key_expired', compact('accessKey', 'server'), 403);
            }

            // 3. Disabled check
            if (!$accessKey->is_active) {
                return redirect()->route('tracker.landing')->withErrors([
                    'access_code' => 'Kode akses lisensi ini telah dinonaktifkan oleh administrator.'
                ]);
            }
        }

        $wsPort = (int) env('WEBSOCKET_PORT', 3001);

        // Single Device Login Session Token
        $sessionToken = $isAdmin ? 'admin_session_' . Str::random(16) : $accessKey->createActiveSession($request->ip());

        // Notify Daemon to kick old sessions of this key in real-time
        if (!$isAdmin) {
            try {
                $secret = env('DAEMON_INTERNAL_SECRET', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
                Http::timeout(2)->withHeaders(['X-Internal-Secret' => $secret])->post("http://127.0.0.1:{$wsPort}/kick-session", [
                    'user_access_key' => $accessKey->code,
                    'active_session_token' => $sessionToken,
                    'server_access_code' => $server->access_code,
                ]);
            } catch (\Throwable $e) {}
        }

        $states = $server->states()->get();
        $configs = $server->configs()->get();
        $isPrivateMode = AppSetting::isAccessCodeRequired() || ($accessKey && $accessKey->code !== $server->access_code) || ($accessKey && $accessKey->duration_type !== 'permanent');

        return view('tracker', compact('server', 'accessKey', 'sessionToken', 'states', 'configs', 'wsPort', 'isAdmin', 'isPrivateMode'));
    }
}

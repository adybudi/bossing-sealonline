<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SealServer;
use App\Models\BossConfig;
use App\Models\BossState;
use App\Rules\TurnstileRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class ServerController extends Controller
{
    public function create()
    {
        return view('admin.servers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'discord_token' => 'nullable|string',
            'discord_channel_id' => 'required|string|max:64',
            'is_active' => 'nullable|boolean',
            'cf-turnstile-response' => [new TurnstileRule()],
        ]);

        unset($validated['cf-turnstile-response']);

        // If discord_token is not provided, use system default token
        if (empty($validated['discord_token'])) {
            $validated['discord_token'] = config('services.discord.default_token') 
                ?: env('DEFAULT_DISCORD_TOKEN', env('DISCORD_TOKEN'));
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['access_code'] = SealServer::generateAccessCode();
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['bot_status'] = (!empty($validated['discord_token']) && !empty($validated['discord_channel_id'])) ? 'STARTING' : 'STOPPED';

        $server = SealServer::create($validated);

        // Notify daemon to automatically start and screen Discord history
        $this->notifyDaemon('SERVER_CREATED', $server->id);

        return redirect()->route('admin.dashboard')->with('success', "Server {$server->name} berhasil ditambahkan dan bot otomatis membaca riwayat Discord!");
    }

    public function edit(SealServer $server)
    {
        return view('admin.servers.edit', compact('server'));
    }

    public function update(Request $request, SealServer $server)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'discord_token' => 'nullable|string',
            'discord_channel_id' => 'required|string|max:64',
            'is_active' => 'nullable|boolean',
            'cf-turnstile-response' => [new TurnstileRule()],
        ]);

        unset($validated['cf-turnstile-response']);

        $validated['is_active'] = $request->has('is_active');

        // Only update discord_token if provided (don't overwrite with empty)
        if (empty($validated['discord_token'])) {
            unset($validated['discord_token']);
        }

        $server->update($validated);

        // Notify daemon to refresh
        $this->notifyDaemon('SERVER_UPDATED', $server->id);

        return redirect()->route('admin.dashboard')->with('success', "Server {$server->name} berhasil diperbarui!");
    }

    public function destroy(SealServer $server)
    {
        $name = $server->name;
        $id = $server->id;
        $server->delete();

        // Notify daemon to stop bot for this server
        $this->notifyDaemon('SERVER_DELETED', $id);

        return redirect()->route('admin.dashboard')->with('success', "Server {$name} berhasil dihapus.");
    }

    public function generateCode(SealServer $server)
    {
        $server->access_code = SealServer::generateAccessCode();
        $server->save();

        $this->notifyDaemon('SERVER_UPDATED', $server->id);

        return back()->with('success', "Kode akses unik untuk {$server->name} berhasil di-generate ulang: {$server->access_code}");
    }

    public function toggleActive(SealServer $server)
    {
        $server->is_active = !$server->is_active;
        if (!$server->is_active) {
            $server->bot_status = 'STOPPED';
        }
        $server->save();

        $this->notifyDaemon('SERVER_UPDATED', $server->id);

        $statusText = $server->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Server {$server->name} berhasil {$statusText}.");
    }

    public function controlBot(Request $request, SealServer $server)
    {
        $action = $request->input('action'); // 'start', 'stop', 'restart', 'rescan'

        if (!in_array($action, ['start', 'stop', 'restart', 'rescan'])) {
            return back()->with('error', 'Aksi bot tidak valid.');
        }

        $daemonPort = env('WEBSOCKET_PORT', 3001);
        $daemonSecret = env('DAEMON_INTERNAL_SECRET', 'seal_internal_secret_change_me_in_env');

        try {
            $response = Http::timeout(3)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/control", [
                    'server_id' => $server->id,
                    'action' => $action,
                ]);

            if ($response->successful()) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => "Aksi {$action} berhasil dijalankan."]);
                }
                return back()->with('success', "Perintah {$action} bot berhasil dikirim ke daemon!");
            }
        } catch (\Exception $e) {
            if ($action === 'start') {
                $server->update(['bot_status' => 'STARTING']);
            } elseif ($action === 'stop') {
                $server->update(['bot_status' => 'STOPPED']);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Perintah {$action} telah dikirim."]);
        }

        return back()->with('info', "Perintah {$action} telah diproses.");
    }

    /**
     * Dedicated Admin Tracker View (Full Admin Mode, no access code needed).
     */
    public function showTracker(SealServer $server)
    {
        $states = $server->states()->get();
        $configs = $server->configs()->get();
        $wsPort = env('WEBSOCKET_PORT', 3001);
        $isAdmin = true;

        return view('tracker', compact('server', 'states', 'configs', 'wsPort', 'isAdmin'));
    }

    /**
     * Admin Quick Edit Interval on-the-fly.
     */
    public function quickInterval(Request $request, SealServer $server): JsonResponse
    {
        $validated = $request->validate([
            'boss_key' => 'required|string',
            'boss_name' => 'required|string',
            'map_name' => 'nullable|string',
            'interval_minutes' => 'required|integer|min:1|max:1440',
        ]);

        // 1. Update in MySQL BossConfig
        BossConfig::updateOrCreate(
            [
                'seal_server_id' => $server->id,
                'boss_name' => $validated['boss_name'],
                'map_name' => $validated['map_name'] ?: null,
            ],
            [
                'interval_minutes' => $validated['interval_minutes'],
                'is_auto_learned' => false,
            ]
        );

        // 2. Update existing state in DB if exists
        $state = BossState::where('seal_server_id', $server->id)
            ->where('boss_key', $validated['boss_key'])
            ->first();

        if ($state) {
            $state->interval_minutes = $validated['interval_minutes'];
            if ($state->status === 'COUNTDOWN' && $state->killed_at) {
                $state->target_respawn_at = $state->killed_at + ($validated['interval_minutes'] * 60 * 1000);
            }
            $state->save();
        }

        // 3. Inform daemon
        $daemonPort = config('services.daemon.port', 3001);
        $daemonSecret = config('services.daemon.secret', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
        try {
            Http::timeout(2)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/update-interval", [
                    'server_id' => $server->id,
                    'boss_key' => $validated['boss_key'],
                    'interval_minutes' => $validated['interval_minutes'],
                ]);
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Interval berhasil diperbarui.']);
    }

    /**
     * Admin Manual Event (Kill / Spawn).
     */
    public function manualEvent(Request $request, SealServer $server): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:KILL,SPAWN',
            'boss_name' => 'required|string|max:150',
            'location' => 'nullable|string|max:100',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $daemonPort = config('services.daemon.port', 3001);
        $daemonSecret = config('services.daemon.secret', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
        try {
            $res = Http::timeout(3)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/manual-event", [
                    'server_id' => $server->id,
                    'type' => $validated['type'],
                    'boss_name' => $validated['boss_name'],
                    'location' => $validated['location'] ?? '',
                    'duration_minutes' => $validated['duration_minutes'] ?? 30,
                ]);

            if ($res->successful()) {
                return response()->json($res->json());
            }
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Event manual berhasil dicatat.']);
    }

    /**
     * Admin Paste Discord Log modal.
     */
    public function parseLog(Request $request, SealServer $server): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string',
        ]);

        $daemonPort = config('services.daemon.port', 3001);
        $daemonSecret = config('services.daemon.secret', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
        try {
            $res = Http::timeout(5)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/parse-log", [
                    'server_id' => $server->id,
                    'text' => $validated['text'],
                ]);

            if ($res->successful()) {
                return response()->json($res->json());
            }
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Log berhasil diproses.']);
    }

    /**
     * Admin Reset Boss Timer.
     */
    public function resetBoss(Request $request, SealServer $server): JsonResponse
    {
        $validated = $request->validate([
            'boss_key' => 'required|string',
        ]);

        $daemonPort = config('services.daemon.port', 3001);
        $daemonSecret = config('services.daemon.secret', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
        try {
            Http::timeout(2)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/reset-boss", [
                    'server_id' => $server->id,
                    'boss_key' => $validated['boss_key'],
                ]);
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Timer boss berhasil direset.']);
    }

    /**
     * Admin Delete Boss State.
     */
    public function deleteBoss(Request $request, SealServer $server): JsonResponse
    {
        $validated = $request->validate([
            'boss_key' => 'required|string',
        ]);

        BossState::where('seal_server_id', $server->id)
            ->where('boss_key', $validated['boss_key'])
            ->delete();

        $daemonPort = config('services.daemon.port', 3001);
        $daemonSecret = config('services.daemon.secret', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
        try {
            Http::timeout(2)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/delete-boss", [
                    'server_id' => $server->id,
                    'boss_key' => $validated['boss_key'],
                ]);
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'message' => 'Boss berhasil dihapus.']);
    }

    private function notifyDaemon(string $event, int $serverId): void
    {
        $daemonPort = env('WEBSOCKET_PORT', 3001);
        $daemonSecret = env('DAEMON_INTERNAL_SECRET', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');

        try {
            Http::timeout(3)
                ->withHeaders(['X-Internal-Secret' => $daemonSecret])
                ->post("http://127.0.0.1:{$daemonPort}/sync-event", [
                    'event' => $event,
                    'server_id' => $serverId,
                ]);
        } catch (\Exception $e) {}
    }
}

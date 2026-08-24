<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SealServer;
use App\Models\BossConfig;
use App\Models\BossState;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InternalApiController extends Controller
{
    /**
     * Get all active servers for daemon initialization.
     */
    public function getActiveServers(): JsonResponse
    {
        $servers = SealServer::where('is_active', true)
            ->with('configs')
            ->get()
            ->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'slug' => $server->slug,
                    'access_code' => $server->access_code,
                    'discord_token' => $server->discord_token, // automatically decrypted by Eloquent cast
                    'discord_channel_id' => $server->discord_channel_id,
                    'is_active' => $server->is_active,
                    'configs' => $server->configs->map(function ($config) {
                        return [
                            'boss_name' => $config->boss_name,
                            'map_name' => $config->map_name,
                            'interval_minutes' => $config->interval_minutes,
                            'is_auto_learned' => $config->is_auto_learned,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $servers,
        ]);
    }

    /**
     * Update server bot status from daemon.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $server = SealServer::find($id);
        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server not found'], 404);
        }

        $validated = $request->validate([
            'bot_status' => 'required|string|in:STOPPED,STARTING,RUNNING,ERROR',
            'last_error' => 'nullable|string',
            'last_screened_at' => 'nullable|date',
        ]);

        $server->update($validated);

        return response()->json(['success' => true, 'data' => $server]);
    }

    /**
     * Sync boss states and learned intervals to MySQL database.
     */
    public function syncStates(Request $request, int $id): JsonResponse
    {
        $server = SealServer::find($id);
        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server not found'], 404);
        }

        $states = $request->input('states', []);
        $configs = $request->input('configs', []);

        // Sync Boss States
        $incomingKeys = [];
        foreach ($states as $state) {
            if (empty($state['boss_key'])) continue;

            $incomingKeys[] = $state['boss_key'];

            BossState::updateOrCreate(
                [
                    'seal_server_id' => $server->id,
                    'boss_key' => $state['boss_key'],
                ],
                [
                    'boss_name' => $state['boss_name'] ?? 'Unknown Boss',
                    'map_name' => $state['map_name'] ?? '',
                    'slot_index' => $state['slot_index'] ?? 1,
                    'status' => $state['status'] ?? 'UNKNOWN',
                    'killed_at' => $state['killed_at'] ?? null,
                    'target_respawn_at' => $state['target_respawn_at'] ?? null,
                    'interval_minutes' => $state['interval_minutes'] ?? 30,
                ]
            );
        }

        // ENGINE 7 (V3): State Parity Garbage Collector.
        // Daemon mengirim snapshot memori yang authoritative; baris DB yang
        // tidak ada lagi di memori daemon adalah orphaned state basi dan wajib dihapus.
        if ($request->has('states') && !empty($incomingKeys)) {
            BossState::where('seal_server_id', $server->id)
                ->whereNotIn('boss_key', $incomingKeys)
                ->delete();
        }

        // Sync Learned Boss Configs
        foreach ($configs as $cfg) {
            if (empty($cfg['boss_name'])) continue;

            BossConfig::updateOrCreate(
                [
                    'seal_server_id' => $server->id,
                    'boss_name' => $cfg['boss_name'],
                    'map_name' => $cfg['map_name'] ?? null,
                ],
                [
                    'interval_minutes' => $cfg['interval_minutes'] ?? 30,
                    'is_auto_learned' => $cfg['is_auto_learned'] ?? true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'States and configs synced successfully',
        ]);
    }
}

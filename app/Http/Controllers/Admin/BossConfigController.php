<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SealServer;
use App\Models\BossConfig;
use Illuminate\Http\Request;

class BossConfigController extends Controller
{
    public function index(SealServer $server)
    {
        $configs = $server->configs()->orderBy('boss_name')->get();
        return view('admin.servers.configs', compact('server', 'configs'));
    }

    public function store(Request $request, SealServer $server)
    {
        $validated = $request->validate([
            'boss_name' => 'required|string|max:150',
            'map_name' => 'nullable|string|max:100',
            'interval_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $server->configs()->updateOrCreate(
            [
                'boss_name' => $validated['boss_name'],
                'map_name' => $validated['map_name'] ?: null,
            ],
            [
                'interval_minutes' => $validated['interval_minutes'],
                'is_auto_learned' => false,
            ]
        );

        return back()->with('success', "Interval untuk {$validated['boss_name']} berhasil disimpan.");
    }

    public function destroy(SealServer $server, BossConfig $config)
    {
        $config->delete();
        return back()->with('success', 'Konfigurasi boss berhasil dihapus.');
    }
}

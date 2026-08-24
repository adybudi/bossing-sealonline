<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SealServer;
use App\Models\BossState;

class DashboardController extends Controller
{
    public function index()
    {
        $servers = SealServer::withCount(['states', 'configs'])->latest()->get();
        $totalServers = $servers->count();
        $activeBots = $servers->where('bot_status', 'RUNNING')->count();
        $activeCountdowns = BossState::where('status', 'COUNTDOWN')->count();
        $totalSpawned = BossState::where('status', 'SPAWNED')->count();
        $requireCode = \App\Models\AppSetting::isAccessCodeRequired();

        return view('admin.dashboard', compact('servers', 'totalServers', 'activeBots', 'activeCountdowns', 'totalSpawned', 'requireCode'));
    }
}

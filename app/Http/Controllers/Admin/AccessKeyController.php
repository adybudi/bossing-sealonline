<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SealServer;
use App\Models\ServerAccessKey;
use App\Rules\TurnstileRule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AccessKeyController extends Controller
{
    /**
     * Display listing of all access keys.
     */
    public function index(Request $request)
    {
        $serverId = $request->query('server_id');
        $servers = SealServer::orderBy('name')->get();

        $query = ServerAccessKey::with('server')->latest();
        if ($serverId) {
            $query->where('seal_server_id', $serverId);
        }

        $keys = $query->paginate(20);

        return view('admin.keys.index', compact('keys', 'servers', 'serverId'));
    }

    /**
     * Store newly generated access key.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'seal_server_id' => 'required|exists:seal_servers,id',
            'label' => 'nullable|string|max:100',
            'duration_type' => 'required|in:7_days,14_days,30_days,permanent,custom',
            'custom_days' => 'nullable|required_if:duration_type,custom|integer|min:1|max:365',
            'cf-turnstile-response' => [new TurnstileRule()],
        ]);

        unset($validated['cf-turnstile-response']);

        $server = SealServer::findOrFail($validated['seal_server_id']);
        $durationType = $validated['duration_type'];
        $days = null;

        if ($durationType === '7_days') $days = 7;
        elseif ($durationType === '14_days') $days = 14;
        elseif ($durationType === '30_days') $days = 30;
        elseif ($durationType === 'custom') $days = (int) $validated['custom_days'];

        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $server->name), 0, 4)) ?: 'SEAL';
        $code = ServerAccessKey::generateUniqueCode($prefix, $durationType);

        // Pre-calculate expires_at from creation time if days specified
        $now = Carbon::now();
        $expiresAt = $days ? $now->copy()->addDays($days) : null;

        $key = ServerAccessKey::create([
            'seal_server_id' => $server->id,
            'code' => $code,
            'label' => $validated['label'] ?: "Lisensi {$server->name} (" . ($days ? "{$days} Hari" : "Permanen") . ")",
            'duration_type' => $durationType,
            'duration_days' => $days,
            'activated_at' => $now,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return back()->with('success', "Kode akses baru '{$key->code}' berhasil dibuat untuk server {$server->name}!");
    }

    /**
     * Toggle active/inactive status of a key.
     */
    public function toggleActive(ServerAccessKey $key)
    {
        $key->is_active = !$key->is_active;
        $key->save();

        $status = $key->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Kode akses '{$key->code}' berhasil {$status}.");
    }

    /**
     * Extend key expiration by additional days.
     */
    public function extend(Request $request, ServerAccessKey $key)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $addDays = (int) $request->input('days');
        $base = ($key->expires_at && $key->expires_at->isFuture()) ? $key->expires_at : Carbon::now();
        $key->expires_at = $base->copy()->addDays($addDays);
        $key->is_active = true;
        $key->save();

        return back()->with('success', "Masa aktif kode '{$key->code}' berhasil diperpanjang +{$addDays} hari (Berlaku hingga {$key->expires_at->format('d M Y H:i')}).");
    }

    /**
     * Delete an access key.
     */
    public function destroy(ServerAccessKey $key)
    {
        $code = $key->code;
        $key->delete();

        return back()->with('success', "Kode akses '{$code}' berhasil dihapus secara permanen.");
    }
}

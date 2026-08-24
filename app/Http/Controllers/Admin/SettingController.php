<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Toggle require access code setting.
     */
    public function toggleAccessCode(Request $request)
    {
        $current = AppSetting::isAccessCodeRequired();
        $newVal = !$current;

        AppSetting::set(
            'require_access_code',
            $newVal,
            'Wajib memasukkan kode akses unik pada landing page untuk pemain umum'
        );

        // If locking down (requiring code), trigger real-time kickout of all public unauthenticated viewers
        if ($newVal) {
            try {
                $secret = env('DAEMON_INTERNAL_SECRET', 'seal_internal_secret_98a7b6c5d4e3f2a1b0c');
                \Illuminate\Support\Facades\Http::timeout(1)->withHeaders(['X-Internal-Secret' => $secret])->post('http://127.0.0.1:3001/lockdown', [
                    'action' => 'LOCKDOWN'
                ]);
            } catch (\Throwable $e) {}
        }

        $statusMsg = $newVal 
            ? 'Mode Kode Akses DIAKTIFKAN: Seluruh penonton publik telah di-kick secara real-time dan wajib memasukkan kode akses unik.'
            : 'Mode Publik Bebas DIAKTIFKAN: Pemain dapat langsung memilih server di landing page tanpa kode akses.';

        return back()->with('success', $statusMsg);
    }
}

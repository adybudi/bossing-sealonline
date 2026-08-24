@extends('layouts.admin')

@section('title', 'Interval Boss - ' . $server->name)

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>Dashboard</span>
                </a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-amber-400 font-semibold">{{ $server->name }}</span>
            </div>
            <h1 class="text-2xl font-bold font-cinzel text-white mt-1">Konfigurasi Durasi Interval Respawn</h1>
            <p class="text-xs text-slate-400 mt-1">Atur durasi respawn manual atau lihat interval yang dipelajari otomatis oleh sistem.</p>
        </div>
    </div>

    <!-- Form Tambah / Override Interval -->
    <div class="bg-seal-card border border-seal-border rounded-2xl p-6 shadow-xl">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider font-cinzel mb-4">Tambah / Ubah Interval Boss</h2>
        <form action="{{ route('admin.servers.configs.store', $server) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
            @csrf

            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Nama Boss <span class="text-rose-400">*</span>
                </label>
                <input 
                    type="text" 
                    name="boss_name" 
                    placeholder="Contoh: Knight of All-Evil" 
                    required 
                    class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-400"
                >
            </div>

            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Nama Map / Lokasi
                </label>
                <input 
                    type="text" 
                    name="map_name" 
                    placeholder="Contoh: Dungeon Silon-Aleph" 
                    class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-400"
                >
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Durasi (Menit) <span class="text-rose-400">*</span>
                </label>
                <input 
                    type="number" 
                    name="interval_minutes" 
                    value="30" 
                    min="1" 
                    max="1440" 
                    required 
                    class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-400 font-mono"
                >
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider font-rajdhani text-sm">
                    Simpan
                </button>
            </div>
        </form>
    </div>

    <!-- Table Configs -->
    <div class="bg-seal-card border border-seal-border rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-seal-border flex items-center justify-between">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider font-cinzel">Daftar Interval Boss Terdaftar</h2>
            <span class="text-xs text-slate-500">{{ $configs->count() }} boss</span>
        </div>

        @if($configs->isEmpty())
            <div class="py-12 text-center text-xs text-slate-500">
                Belum ada interval khusus. Sistem akan otomatis mempelajari interval (30m / 120m) saat Discord mendeteksi pola kill & spawn.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/60 text-slate-400 uppercase font-cinzel text-[11px] border-b border-seal-border">
                        <tr>
                            <th class="px-6 py-3.5">Nama Boss</th>
                            <th class="px-6 py-3.5">Lokasi (Map)</th>
                            <th class="px-6 py-3.5">Durasi Respawn</th>
                            <th class="px-6 py-3.5">Tipe Pembelajaran</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-seal-border font-mono">
                        @foreach($configs as $cfg)
                            <tr class="hover:bg-slate-900/30">
                                <td class="px-6 py-3.5 font-bold text-white font-sans">{{ $cfg->boss_name }}</td>
                                <td class="px-6 py-3.5 text-slate-400">{{ $cfg->map_name ?: 'Semua Map' }}</td>
                                <td class="px-6 py-3.5 font-bold text-amber-300">{{ $cfg->interval_minutes }} Menit</td>
                                <td class="px-6 py-3.5">
                                    @if($cfg->is_auto_learned)
                                        <span class="px-2 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-500/30 text-[10px]">Auto-Learned</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-amber-950 text-amber-400 border border-amber-500/30 text-[10px]">Manual Set</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <form action="{{ route('admin.servers.configs.destroy', [$server, $cfg]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus interval boss ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 text-xs inline-flex items-center gap-1">
                                            <i class="fa-solid fa-trash text-[11px]"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

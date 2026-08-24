@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold font-cinzel text-white">Daftar Server Seal Online</h1>
            <p class="text-xs text-slate-400 mt-1">Kelola token Discord, channel ID, kode akses, dan status bot per server.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.keys.index') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold rounded-xl text-xs flex items-center gap-2 border border-slate-700 shadow-lg transition-all font-rajdhani text-sm uppercase tracking-wider">
                <i class="fa-solid fa-key"></i>
                <span>Kelola Lisensi (Jual)</span>
            </a>
            <a href="{{ route('admin.servers.create') }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all font-rajdhani text-sm uppercase tracking-wider">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Server Baru</span>
            </a>
        </div>
    </div>

    <!-- Dynamic Setting: Public Access Mode Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-seal-card to-slate-900 border border-seal-border rounded-2xl p-5 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl {{ ($requireCode ?? false) ? 'bg-amber-500/10 border border-amber-500/30' : 'bg-emerald-500/10 border border-emerald-500/30' }} flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid {{ ($requireCode ?? false) ? 'fa-lock text-amber-400' : 'fa-earth-americas text-emerald-400' }}"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-white font-cinzel">Mode Akses Portal Pemain (Landing Page)</h3>
                    @if($requireCode ?? false)
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-950/80 border border-amber-500/40 text-amber-400 text-[10px] font-bold uppercase font-mono">
                            Mode Kode Akses (Private)
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 text-[10px] font-bold uppercase font-mono">
                            Mode Publik Bebas (Langsung Pilih Server)
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    @if($requireCode ?? false)
                        Saat ini pemain <strong>wajib memasukkan kode unik</strong> di halaman utama untuk membuka tracker server.
                    @else
                        Saat ini pemain <strong>bebas memilih server secara langsung</strong> di halaman utama tanpa perlu memasukkan kode akses unik.
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <form action="{{ route('admin.settings.toggle_access_code') }}" method="POST">
                @csrf
                <button 
                    type="submit" 
                    class="px-4 py-2 rounded-xl text-xs font-bold font-sans transition-all flex items-center gap-2 shadow-lg {{ ($requireCode ?? false) ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20' }}"
                >
                    @if($requireCode ?? false)
                        <i class="fa-solid fa-earth-americas"></i>
                        <span>Buka Akses Bebas (Tanpa Kode)</span>
                    @else
                        <i class="fa-solid fa-lock"></i>
                        <span>Aktifkan Wajib Kode Akses</span>
                    @endif
                </button>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-seal-card border border-seal-border rounded-2xl p-5 shadow-lg">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Server</div>
            <div class="text-3xl font-black font-rajdhani text-white mt-1">{{ $totalServers }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Server Seal terdaftar</div>
        </div>

        <div class="bg-seal-card border border-seal-border rounded-2xl p-5 shadow-lg">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Bot Discord Aktif</div>
            <div class="text-3xl font-black font-rajdhani text-emerald-400 mt-1">{{ $activeBots }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Instance bot online & listening</div>
        </div>

        <div class="bg-seal-card border border-seal-border rounded-2xl p-5 shadow-lg">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sedang Countdown</div>
            <div class="text-3xl font-black font-rajdhani text-blue-400 mt-1">{{ $activeCountdowns }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Boss dalam proses respawn</div>
        </div>

        <div class="bg-seal-card border border-seal-border rounded-2xl p-5 shadow-lg">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Boss Spawn / Ready</div>
            <div class="text-3xl font-black font-rajdhani text-amber-400 mt-1">{{ $totalSpawned }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Siap diburu sekarang</div>
        </div>
    </div>

    <!-- Servers Table Card -->
    <div class="bg-seal-card border border-seal-border rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-seal-border flex items-center justify-between">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider font-cinzel">Instance Server Seal</h2>
            <span class="text-xs text-slate-500">{{ $servers->count() }} instance</span>
        </div>

        @if($servers->isEmpty())
            <div class="py-16 text-center">
                <div class="text-3xl text-slate-500 mb-2">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-300">Belum Ada Server Seal</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4">Klik tombol di bawah untuk menambahkan server Seal pertama Anda.</p>
                <a href="{{ route('admin.servers.create') }}" class="px-4 py-2 bg-amber-500 text-slate-950 font-bold rounded-lg text-xs">
                    Tambah Server Baru
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/60 text-slate-400 uppercase font-cinzel text-[11px] border-b border-seal-border">
                        <tr>
                            <th class="px-6 py-4">Server</th>
                            <th class="px-6 py-4">Bot Status</th>
                            <th class="px-6 py-4">Channel ID</th>
                            <th class="px-6 py-4">Kode Akses Unik (Pemain)</th>
                            <th class="px-6 py-4 text-right">Tracker & Kontrol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-seal-border font-mono">
                        @foreach($servers as $srv)
                            <tr class="hover:bg-slate-900/30 transition-colors">
                                <!-- Server Info -->
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white font-sans text-sm">{{ $srv->name }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <form action="{{ route('admin.servers.toggle_active', $srv) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[10px] px-2 py-0.5 rounded-full {{ $srv->is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                                                {{ $srv->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <!-- Bot Status -->
                                <td class="px-6 py-4 font-sans">
                                    @if($srv->bot_status === 'RUNNING')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> RUNNING
                                        </span>
                                    @elseif($srv->bot_status === 'STARTING')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-950/80 text-amber-400 border border-amber-500/30">
                                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span> STARTING
                                        </span>
                                    @elseif($srv->bot_status === 'ERROR')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-950/80 text-rose-400 border border-rose-500/30">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> ERROR
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                            <span class="w-2 h-2 rounded-full bg-slate-500"></span> STOPPED
                                        </span>
                                    @endif
                                </td>

                                <!-- Channel ID -->
                                <td class="px-6 py-4">
                                    <span class="text-slate-400">{{ $srv->discord_channel_id ?: 'Belum diisi' }}</span>
                                </td>

                                <!-- Access Code & Copy -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 bg-slate-900 rounded-lg border border-slate-800 text-amber-300 text-xs select-all">
                                            {{ $srv->access_code }}
                                        </span>
                                        <button 
                                            onclick="copyToClipboard('{{ $srv->access_code }}', this)" 
                                            class="p-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 text-xs transition-all"
                                            title="Salin Kode Akses Pemain"
                                        >
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                        <form action="{{ route('admin.servers.generate_code', $srv) }}" method="POST" class="inline" onsubmit="return confirm('Generate ulang kode akses? Pemain yang memakai kode lama tidak akan bisa masuk lagi.');">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-400 text-xs" title="Generate Ulang Kode">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <!-- Controls & Actions -->
                                <td class="px-6 py-4 text-right font-sans">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        <!-- Primary Action: Open Tracker in Admin Mode -->
                                        <a href="{{ route('admin.servers.tracker', $srv) }}" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-md shadow-amber-500/10 transition-all font-rajdhani text-sm uppercase tracking-wider">
                                            <i class="fa-solid fa-shield-halved"></i>
                                            <span>Buka Tracker</span>
                                        </a>

                                        <!-- Bot Controls: Start / Stop -->
                                        <form action="{{ route('admin.servers.control_bot', $srv) }}" method="POST" class="inline">
                                            @csrf
                                            @if($srv->bot_status === 'RUNNING')
                                                <input type="hidden" name="action" value="stop">
                                                <button type="submit" class="p-1.5 rounded-lg bg-rose-950/60 hover:bg-rose-900 border border-rose-500/30 text-rose-300 text-xs font-semibold" title="Hentikan Bot">
                                                    <i class="fa-solid fa-stop text-[11px]"></i>
                                                </button>
                                            @else
                                                <input type="hidden" name="action" value="start">
                                                <button type="submit" class="p-1.5 rounded-lg bg-emerald-950/60 hover:bg-emerald-900 border border-emerald-500/30 text-emerald-300 text-xs font-semibold" title="Jalankan Bot">
                                                    <i class="fa-solid fa-play text-[11px]"></i>
                                                </button>
                                            @endif
                                        </form>

                                        <a href="{{ route('admin.servers.configs', $srv) }}" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs" title="Atur Interval Boss">
                                            <i class="fa-solid fa-sliders"></i>
                                        </a>

                                        <a href="{{ route('admin.servers.edit', $srv) }}" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs" title="Edit Server">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <form action="{{ route('admin.servers.destroy', $srv) }}" method="POST" class="inline" onsubmit="return confirm('Hapus server {{ $srv->name }}? Seluruh data status boss untuk server ini akan ikut terhapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-400 text-xs" title="Hapus Server">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>

                                    </div>
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

@section('scripts')
<script>
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i>';
            setTimeout(() => { btn.innerHTML = orig; }, 1500);
        });
    }
</script>
@endsection

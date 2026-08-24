@extends('layouts.app')

@section('title', 'Seal Online Boss Tracker - Portal Akses Server')

@section('content')
<div class="min-h-[85vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-4xl w-full">
        
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-tr from-amber-500/20 to-amber-300/10 border border-amber-500/30 mb-4 shadow-xl shadow-amber-500/10 pulse-gold">
                <i class="fa-solid fa-shield-halved text-amber-400 text-3xl"></i>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold font-cinzel text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-100 tracking-wider">
                SEAL ONLINE
            </h1>
            <p class="text-sm font-rajdhani text-slate-400 tracking-widest uppercase mt-1">
                Real-Time Multi-Server Boss Tracker & Respawn Alarm
            </p>
        </div>

        <!-- Notification Alert for Errors -->
        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-200 text-xs sm:text-sm flex items-start gap-3 shadow-xl backdrop-blur-md animate-shake">
                <div class="w-8 h-8 rounded-xl bg-rose-900/60 border border-rose-500/40 flex items-center justify-center text-rose-400 shrink-0 text-base">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="flex-1 mt-0.5">
                    <div class="font-bold text-white mb-0.5">Gagal Membuka Tracker</div>
                    <div>{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- LIST SERVER SEAL ONLINE (Tampil di Mode Publik Maupun Privat)             -->
        <!-- ========================================================================= -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden mb-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between pb-5 mb-6 border-b border-slate-800/90 gap-4">
                <div class="flex-1 min-w-0 pr-0 md:pr-4">
                    <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2.5 font-sans flex-wrap">
                        @if($requireCode ?? false)
                            <i class="fa-solid fa-lock text-amber-400"></i>
                            <span>Pilih Server Seal Online</span>
                            <span class="text-[11px] font-semibold text-amber-400 font-mono px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/25">Akses Terproteksi</span>
                        @else
                            <i class="fa-solid fa-earth-americas text-emerald-400"></i>
                            <span>Pilih Server Seal Online</span>
                        @endif
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1 leading-relaxed">
                        @if($requireCode ?? false)
                            Pilih server di bawah lalu masukkan kode akses / lisensi unik Anda untuk membuka live countdown.
                        @else
                            Klik server di bawah untuk langsung membuka dashboard live countdown boss tanpa kode akses.
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0 self-start md:self-center flex-wrap">
                    @if($requireCode ?? false)
                        <span class="px-3 py-1 rounded-full bg-amber-950/80 border border-amber-500/30 text-amber-400 text-xs font-bold font-mono flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-key text-[10px]"></i> Mode Lisensi
                        </span>
                    @endif
                    <span class="px-3 py-1 rounded-full bg-emerald-950/80 border border-emerald-500/30 text-emerald-400 text-xs font-bold font-mono shadow-sm">
                        {{ $servers->count() }} Server Aktif
                    </span>
                </div>
            </div>

            @if($servers->isEmpty())
                <div class="py-12 text-center">
                    <div class="text-3xl text-slate-500 mb-2">
                        <i class="fa-solid fa-scroll"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-300">Belum Ada Server Aktif</h3>
                    <p class="text-xs text-slate-500 mt-1">Administrator belum mengaktifkan instance server.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($servers as $srv)
                        @if(!($requireCode ?? false))
                            {{-- MODE PUBLIC: Direct Access --}}
                            <a 
                                href="{{ route('tracker.show', $srv->access_code) }}" 
                                class="group bg-slate-900/90 hover:bg-slate-800/90 border border-slate-700/80 hover:border-emerald-500/50 rounded-2xl p-5 shadow-lg transition-all duration-200 hover:-translate-y-1 block relative overflow-hidden"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                        @if(str_contains(strtolower($srv->name), 'hell'))
                                            <i class="fa-solid fa-fire text-amber-400"></i>
                                        @elseif(str_contains(strtolower($srv->name), 'maja'))
                                            <i class="fa-solid fa-crown text-amber-400"></i>
                                        @else
                                            <i class="fa-solid fa-shield-halved text-sky-400"></i>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Online
                                    </span>
                                </div>

                                <h3 class="text-base font-bold text-white group-hover:text-emerald-300 transition-colors font-sans truncate">
                                    {{ $srv->name }}
                                </h3>

                                <div class="flex items-center gap-2 mt-2 text-xs text-slate-400 font-mono">
                                    <span><i class="fa-solid fa-crosshairs text-amber-400 mr-1"></i> {{ $srv->states_count }} Boss Terpantau</span>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-semibold text-sky-400 group-hover:text-emerald-400">
                                    <span>Buka Live Tracker</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </a>
                        @else
                            {{-- MODE PRIVATE: Click to Open Key Verification Modal --}}
                            <button 
                                type="button"
                                onclick="openKeyModal('{{ $srv->id }}', '{{ addslashes($srv->name) }}', '{{ $srv->states_count }}')"
                                class="group text-left w-full bg-slate-900/90 hover:bg-slate-800/90 border border-slate-700/80 hover:border-amber-500/50 rounded-2xl p-5 shadow-lg transition-all duration-200 hover:-translate-y-1 block relative overflow-hidden"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                        @if(str_contains(strtolower($srv->name), 'hell'))
                                            <i class="fa-solid fa-fire text-amber-400"></i>
                                        @elseif(str_contains(strtolower($srv->name), 'maja'))
                                            <i class="fa-solid fa-crown text-amber-400"></i>
                                        @else
                                            <i class="fa-solid fa-shield-halved text-sky-400"></i>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Online
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-950/80 text-amber-400 border border-amber-500/30">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Private
                                        </span>
                                    </div>
                                </div>

                                <h3 class="text-base font-bold text-white group-hover:text-amber-300 transition-colors font-sans truncate">
                                    {{ $srv->name }}
                                </h3>

                                <div class="flex items-center gap-2 mt-2 text-xs text-slate-400 font-mono">
                                    <span><i class="fa-solid fa-crosshairs text-amber-400 mr-1"></i> {{ $srv->states_count }} Boss Terpantau</span>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-semibold text-amber-400 group-hover:text-amber-300">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-key text-[10px]"></i> Masukkan Kode Akses
                                    </span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Optional Direct Key Input Form at Bottom for Private Mode --}}
            @if($requireCode ?? false)
                <div class="mt-6 pt-6 border-t border-slate-800/90">
                    <form action="{{ route('tracker.verify') }}" method="POST" class="flex flex-col gap-3">
                        @csrf
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                    <i class="fa-solid fa-key text-xs text-amber-400"></i>
                                </div>
                                <input 
                                    type="text" 
                                    name="access_code" 
                                    placeholder="Atau langsung tempel kode akses / lisensi di sini..." 
                                    required
                                    class="w-full pl-9 pr-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs sm:text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/20 font-mono"
                                >
                            </div>
                            <button 
                                type="submit" 
                                class="px-5 py-2.5 bg-gradient-to-r from-amber-500 hover:from-amber-400 to-amber-600 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 transition-all text-xs uppercase tracking-wider font-rajdhani flex items-center justify-center gap-2 shrink-0 text-sm"
                            >
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Buka Tracker</span>
                            </button>
                        </div>
                        <x-turnstile />
                    </form>
                </div>
            @endif
        </div>

        <!-- Features Info Footer -->
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="glass-card rounded-xl p-3">
                <div class="text-amber-400 text-base"><i class="fa-solid fa-clock"></i></div>
                <div class="text-[11px] font-bold text-slate-300 mt-1">Anti-Drift</div>
                <div class="text-[9px] text-slate-500">Presisi Detik UTC</div>
            </div>
            <div class="glass-card rounded-xl p-3">
                <div class="text-sky-400 text-base"><i class="fa-solid fa-volume-high"></i></div>
                <div class="text-[11px] font-bold text-slate-300 mt-1">Audio Alarm</div>
                <div class="text-[9px] text-slate-500">Notifikasi Pop-Up</div>
            </div>
            <div class="glass-card rounded-xl p-3">
                <div class="text-emerald-400 text-base"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="text-[11px] font-bold text-slate-300 mt-1">Multi-Slot</div>
                <div class="text-[9px] text-slate-500">Boss Kembar/Map</div>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL MASUKKAN KODE AKSES LISENSI (Untuk Mode Private)                     -->
<!-- ========================================================================= -->
<div id="keyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden transition-opacity duration-200">
    <div class="bg-slate-900 border border-slate-700/90 rounded-2xl max-w-md w-full p-6 shadow-2xl relative transform transition-transform duration-200">
        <!-- Close Button -->
        <button 
            type="button" 
            onclick="closeKeyModal()" 
            class="absolute top-4 right-4 w-8 h-8 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-sm transition-colors"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Header -->
        <div class="flex items-center gap-3.5 mb-5 pb-4 border-b border-slate-800">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-xl shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h3 id="modalServerName" class="text-base font-bold text-white font-sans">
                    Seal Online
                </h3>
                <p id="modalServerBossCount" class="text-xs text-amber-400 font-mono mt-0.5">
                    <i class="fa-solid fa-lock text-[10px] mr-1"></i> Akses Terproteksi
                </p>
            </div>
        </div>

        <!-- Verification Form -->
        <form action="{{ route('tracker.verify') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="modalAccessCodeInput" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Kode Akses / Voucher Lisensi
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-key text-xs text-amber-400"></i>
                    </div>
                    <input 
                        type="text" 
                        name="access_code" 
                        id="modalAccessCodeInput" 
                        placeholder="Contoh: SEAL-7D-X9k$2p..." 
                        required
                        class="w-full pl-9 pr-4 py-3 bg-slate-950 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all font-mono"
                    >
                </div>
                <p class="text-[11px] text-slate-400 mt-2">
                    Masukkan kode lisensi atau voucher unik Anda untuk membuka live countdown server ini.
                </p>
            </div>

            <!-- Cloudflare Turnstile CAPTCHA -->
            <x-turnstile />

            <div class="flex items-center gap-3 pt-2">
                <button 
                    type="button" 
                    onclick="closeKeyModal()" 
                    class="flex-1 py-3 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl text-xs uppercase tracking-wider transition-colors"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="flex-1 py-3 px-4 bg-gradient-to-r from-amber-500 hover:from-amber-400 to-amber-600 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 transition-all text-xs uppercase tracking-wider font-rajdhani text-sm flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-unlock"></i>
                    <span>Buka Tracker</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openKeyModal(serverId, serverName, bossCount) {
        document.getElementById('modalServerName').textContent = serverName;
        document.getElementById('modalServerBossCount').innerHTML = `<i class="fa-solid fa-lock text-[10px] mr-1"></i> Terproteksi • ${bossCount} Boss Terpantau`;
        const modal = document.getElementById('keyModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            const input = document.getElementById('modalAccessCodeInput');
            if (input) input.focus();
            if (window.turnstile) {
                try { window.turnstile.reset(); } catch(e) {}
            }
        }, 100);
    }

    function closeKeyModal() {
        document.getElementById('keyModal').classList.add('hidden');
    }

    // Close on Escape key or backdrop click
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeKeyModal();
    });
    document.getElementById('keyModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeKeyModal();
    });
</script>
@endsection

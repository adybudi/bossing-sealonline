@extends('layouts.app')

@section('title', $server->name . ' - Seal Online Boss Timer')

@section('styles')
<style>
    /* Dark Theme Canvas */
    body {
        background-color: #0b0f19 !important;
        color: #f8fafc;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .tabular-nums {
        font-variant-numeric: tabular-nums;
    }

    /* Card & Row Base */
    .boss-row-card {
        background-color: #131b2e;
        border: 1px solid #1f2c47;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    .boss-row-card:hover {
        border-color: #2e3e60;
    }

    /* Spawned State (High-Contrast Pulsing Crimson) */
    .boss-row-spawned {
        border: 1.5px solid #ef4444 !important;
        box-shadow: 0 0 25px rgba(239, 68, 68, 0.35) !important;
        background: linear-gradient(135deg, #181d2f 0%, #26131c 100%) !important;
    }

    /* Neon Colors */
    .text-neon-green {
        color: #00f59b;
        text-shadow: 0 0 12px rgba(0, 245, 155, 0.45);
    }
    .text-neon-red {
        color: #ff4d4d;
        text-shadow: 0 0 15px rgba(255, 77, 77, 0.6);
    }
    .text-neon-amber {
        color: #fbbf24;
        text-shadow: 0 0 12px rgba(251, 191, 36, 0.45);
    }

    /* Progress bar smooth */
    .progress-bar-line {
        transition: width 1s linear;
    }

    .modal-backdrop-blur {
        background-color: rgba(11, 15, 25, 0.88);
        backdrop-filter: blur(8px);
    }
</style>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6" id="app">

    <!-- SPAWN BOSS POP-UP MODAL (Perfect Centered Layout) -->
    <div id="spawnModal" class="fixed inset-0 modal-backdrop-blur z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-[#131a2b] border-2 border-rose-500 rounded-3xl max-w-lg w-full p-8 text-center shadow-2xl shadow-rose-500/50 relative transform transition-all duration-300">
            <!-- Golden Swinging Bell -->
            <div class="text-6xl mb-3.5 animate-bounce">
                🔔
            </div>

            <!-- Title: Clean centered text block without broken flex splitting -->
            <div class="mb-3">
                <h3 class="text-xl sm:text-2xl font-black text-white tracking-wide font-sans leading-snug flex items-center justify-center gap-2">
                    <i class="fa-solid fa-bell text-rose-500 animate-bounce"></i>
                    <span><span id="spawnModalBossName" class="text-rose-400">Ice Queen</span> TELAH SPAWN!</span>
                </h3>
            </div>

            <!-- Location Subtitle Badge -->
            <div class="inline-flex items-center justify-center gap-1.5 text-sm text-slate-200 font-medium mb-3 bg-slate-900/90 px-4 py-1.5 rounded-full border border-slate-700/80">
                <i class="fa-solid fa-location-dot text-rose-500 text-sm"></i>
                <span>Lokasi: <span id="spawnModalLocation" class="text-white font-bold">Ice Castle</span></span>
            </div>

            <!-- Description -->
            <p class="text-xs text-slate-400 mb-6 font-sans">
                Waktu respawn telah tiba! Segera check spot bossing!
            </p>

            <!-- OK / Matikan Alarm Button -->
            <button onclick="dismissSpawnModal()" class="w-full py-3.5 px-6 bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm rounded-2xl shadow-lg shadow-blue-600/40 transition-all uppercase tracking-wider font-sans cursor-pointer">
                OK, Matikan Alarm
            </button>
        </div>
    </div>

    <!-- KICKOUT / SESSION REVOKED MODAL (Single Active Device Guard) -->
    <div id="kickoutModal" class="fixed inset-0 modal-backdrop-blur z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-[#131a2b] border-2 border-amber-500 rounded-3xl max-w-md w-full p-8 text-center shadow-2xl shadow-amber-500/50 relative">
            <div class="text-5xl text-amber-400 mb-4 animate-bounce">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-white tracking-wide font-sans mb-2">
                Ups! Kamu Harus Terlogout!
            </h3>
            <p class="text-xs text-slate-300 mb-6 font-sans leading-relaxed">
                Kode akses unik ini baru saja digunakan untuk login di <strong>perangkat / komputer lain</strong>.<br>
                Sesi Anda di perangkat ini telah dihentikan secara otomatis.
            </p>
            <a href="{{ route('tracker.landing') }}" class="block w-full py-3.5 px-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs rounded-2xl shadow-lg shadow-amber-500/20 transition-all uppercase tracking-wider font-rajdhani text-sm">
                ← Kembali ke Halaman Utama
            </a>
        </div>
    </div>

    <!-- GLOBAL LOCKDOWN MODAL (Triggered when Admin toggles Access Code Protection ON) -->
    <div id="lockdownModal" class="fixed inset-0 modal-backdrop-blur z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-[#131a2b] border-2 border-amber-500 rounded-3xl max-w-md w-full p-8 text-center shadow-2xl shadow-amber-500/50 relative">
            <div class="text-5xl text-amber-400 mb-4 animate-bounce">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-white tracking-wide font-sans mb-2">
                Akses Server Telah Dikunci!
            </h3>
            <p class="text-xs text-slate-300 mb-6 font-sans leading-relaxed">
                Administrator baru saja <strong>mengaktifkan sistem proteksi kode akses</strong>.<br>
                Sesi penonton publik telah dihentikan. Silakan masukkan kode akses / lisensi Anda untuk melanjutkan.
            </p>
            <a href="{{ route('tracker.landing') }}" class="block w-full py-3.5 px-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs rounded-2xl shadow-lg shadow-amber-500/20 transition-all uppercase tracking-wider font-rajdhani text-sm">
                ← Masukkan Kode Akses
            </a>
        </div>
    </div>

    <!-- 1. HEADER CARD (Pixel-Perfect from Screenshot) -->
    <div class="boss-row-card rounded-2xl p-5 sm:p-6 mb-5">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <!-- Left: Logo, Title & Subtitle -->
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-slate-900/80 border border-slate-700/80 flex items-center justify-center text-xl shrink-0 shadow-inner">
                    <i class="fa-solid fa-shield-halved text-sky-400"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-black text-white tracking-wide font-sans uppercase">
                            SEAL ONLINE BOSS TIMER
                        </h1>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-sky-400 font-bold">
                            {{ $server->name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Pengingat Waktu Respawn Boss & Alarm Countdown
                    </p>

                    <!-- Status & Controls Row -->
                    <div class="flex flex-wrap items-center gap-2 mt-3 text-xs">
                        <span id="discordStatusPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-900/90 text-slate-300 border border-slate-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Live Discord: Online
                        </span>

                        @if($isAdmin ?? false)
                            <button onclick="controlBot('rescan')" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-emerald-300 font-semibold border border-emerald-500/40 flex items-center gap-1.5 transition-all">
                                <i class="fa-solid fa-arrows-rotate text-emerald-400"></i>
                                <span>Scan Riwayat</span>
                            </button>

                            <button onclick="openPasteModal()" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-amber-300 font-semibold border border-amber-500/40 flex items-center gap-1.5 transition-all">
                                <i class="fa-solid fa-paste text-amber-400"></i>
                                <span>Paste Log Discord</span>
                            </button>
                        @endif

                        <!-- Sound Toggle -->
                        <button onclick="toggleSound()" id="soundToggleBtn" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 font-semibold border border-slate-700 flex items-center gap-1.5 transition-all">
                            <i id="soundIcon" class="fa-solid fa-volume-high text-sky-400"></i>
                            <span id="soundText">Suara: ON</span>
                        </button>

                        <!-- Test Alarm Audio -->
                        <button onclick="testAlarmSound()" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 font-semibold border border-slate-700 flex items-center gap-1.5 transition-all">
                            <i class="fa-solid fa-volume-high text-slate-400"></i>
                            <span>Tes Alarm</span>
                        </button>

                        <button onclick="requestPushNotification()" id="notifToggleBtn" class="px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 font-semibold border border-slate-700 flex items-center gap-1.5 transition-all">
                            <i class="fa-solid fa-bell text-slate-400"></i>
                            <span>Notif Web</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Dynamic Action Button (Public -> Kembali ke Landing Page, Private -> Logout, Admin -> Dashboard) -->
            <div class="flex items-center gap-2 self-start md:self-center shrink-0">
                @if($isAdmin ?? false)
                    <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1.5 shadow-md">
                        <i class="fa-solid fa-shield-halved text-amber-400"></i>
                        <span>Dashboard Admin</span>
                    </a>
                @elseif($isPrivateMode ?? false)
                    <a href="{{ route('tracker.landing') }}" class="px-3.5 py-2 rounded-xl bg-rose-950/80 hover:bg-rose-900/80 text-rose-300 hover:text-rose-200 text-xs font-semibold border border-rose-500/40 hover:border-rose-400 transition-all flex items-center gap-1.5 shadow-md" title="Keluar dan ganti kode akses lisensi">
                        <i class="fa-solid fa-right-from-bracket text-rose-400"></i>
                        <span>Logout</span>
                    </a>
                @else
                    <a href="{{ route('tracker.landing') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1.5 shadow-md" title="Kembali ke portal pemilihan server">
                        <i class="fa-solid fa-house text-slate-400"></i>
                        <span>Kembali ke Halaman Utama</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. TAMBAH DATA BOSS CARD (Only rendered for Administrator) -->
    @if($isAdmin ?? false)
    <div class="boss-row-card rounded-2xl p-5 sm:p-6 mb-5">
        <div class="flex items-center justify-between mb-4 border-b border-slate-700/50 pb-2.5">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-400"></i>
                <span>Tambah Data Boss</span>
            </h2>
            <span class="text-xs text-slate-500">Khusus Administrator</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start text-xs">
            <!-- Nama Boss -->
            <div class="md:col-span-4">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Boss <span class="text-rose-400">*</span></label>
                <input
                    type="text"
                    id="addBossName"
                    placeholder="Contoh: Queen Bee, Titan, dll."
                    class="w-full px-3.5 py-2.5 bg-slate-900/90 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-sky-400"
                >
            </div>

            <!-- Lokasi / Map -->
            <div class="md:col-span-4">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Lokasi / Map <span class="text-rose-400">*</span></label>
                <input
                    type="text"
                    id="addBossLocation"
                    placeholder="Contoh: Silon Cave, Mt. Cross, dll."
                    class="w-full px-3.5 py-2.5 bg-slate-900/90 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-sky-400"
                >
            </div>

            <!-- Waktu Spawn / Interval (3 inputs: Jam : Menit : Detik) -->
            <div class="md:col-span-4">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Waktu Spawn / Interval <span class="text-rose-400">*</span></label>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-slate-900 border border-slate-700 rounded-xl p-1.5">
                        <input type="number" id="addHours" value="0" min="0" max="24" class="w-full bg-transparent text-center text-sm font-bold text-white font-mono focus:outline-none">
                        <div class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">JAM</div>
                    </div>
                    <div class="bg-slate-900 border border-slate-700 rounded-xl p-1.5">
                        <input type="number" id="addMinutes" value="30" min="0" max="59" class="w-full bg-transparent text-center text-sm font-bold text-white font-mono focus:outline-none">
                        <div class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">MENIT</div>
                    </div>
                    <div class="bg-slate-900 border border-slate-700 rounded-xl p-1.5">
                        <input type="number" id="addSeconds" value="0" min="0" max="59" class="w-full bg-transparent text-center text-sm font-bold text-white font-mono focus:outline-none">
                        <div class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">DETIK</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preset Cepat & Submit Button -->
        <div class="mt-4 pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <span class="text-slate-400 font-medium mr-1">Preset Cepat:</span>
                <button type="button" onclick="setPreset(0, 30)" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300 font-mono text-[11px]">30m</button>
                <button type="button" onclick="setPreset(1, 0)" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300 font-mono text-[11px]">1 Jam</button>
                <button type="button" onclick="setPreset(2, 0)" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300 font-mono text-[11px]">2 Jam</button>
                <button type="button" onclick="setPreset(3, 0)" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300 font-mono text-[11px]">3 Jam</button>
                <button type="button" onclick="setPreset(4, 0)" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300 font-mono text-[11px]">4 Jam</button>
                <button type="button" onclick="setPreset(6, 0)" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-lg text-slate-300 font-mono text-[11px]">6 Jam</button>
            </div>

            <button onclick="submitAddBossForm()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl flex items-center gap-2 shadow-lg shadow-blue-600/30 transition-all">
                <i class="fa-solid fa-plus"></i>
                <span>Simpan Boss ke Daftar</span>
            </button>
        </div>
    </div>
    @endif

    <!-- 3. CONTROL & SORT BAR (Pixel-Perfect from Screenshot) -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3.5 mb-4">

        <!-- Left: Title, Counter Badge & Bulk Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-crosshairs text-rose-500 text-base"></i>
                <h2 class="text-base font-bold text-white font-sans">Daftar Countdown Boss</h2>
                <span id="badgeTotalBoss" class="px-2.5 py-0.5 rounded-full bg-sky-950/80 border border-sky-500/40 text-sky-400 font-bold text-xs font-mono">
                    0 Boss
                </span>
            </div>

            @if($isAdmin ?? false)
                <!-- Bulk Action Buttons for Admin -->
                <div class="flex items-center gap-1.5 ml-0 sm:ml-2">
                    <button onclick="adminStartAll()" class="px-2.5 py-1 rounded-lg bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/40 text-xs font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-play text-[10px]"></i> Mulai Semua
                    </button>
                    <button onclick="adminPauseAll()" class="px-2.5 py-1 rounded-lg bg-amber-950/80 hover:bg-amber-900 text-amber-300 border border-amber-500/40 text-xs font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-pause text-[10px]"></i> Berhenti Semua
                    </button>
                    <button onclick="adminResetAll()" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-600 text-xs font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i> Reset Semua
                    </button>
                    <button onclick="adminDeleteAll()" class="px-2.5 py-1 rounded-lg bg-rose-950/60 hover:bg-rose-900 text-rose-300 border border-rose-500/40 text-xs font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-trash text-[10px]"></i> Hapus Semua
                    </button>
                </div>
            @endif
        </div>

        <!-- Right: Search, Filter, Sort & View Mode Switcher -->
        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-end">
            <!-- Search -->
            <div class="relative w-full sm:w-44">
                <input
                    type="text"
                    id="searchInput"
                    oninput="applyFilters()"
                    placeholder="Cari boss / map..."
                    class="w-full pl-8 pr-3 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-sky-400"
                >
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-[11px] text-slate-500"></i>
            </div>

            <!-- View Switcher (Card vs List) -->
            <div class="bg-slate-900 p-1 rounded-xl border border-slate-800 flex items-center shrink-0">
                <button id="viewGridBtn" onclick="setViewMode('grid')" class="px-3 py-1 rounded-lg text-slate-400 hover:text-white font-medium text-xs flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-table-cells-large text-[11px]"></i> Card
                </button>
                <button id="viewListBtn" onclick="setViewMode('list')" class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-list-ul text-[11px]"></i> List
                </button>
            </div>

            <!-- Sort Dropdown with Lightning Icon -->
            <div class="flex items-center gap-1.5 text-xs text-slate-300">
                <i class="fa-solid fa-bolt text-amber-400"></i>
                <span>Urutkan:</span>
                <select
                    id="sortSelect"
                    onchange="applyFilters()"
                    class="bg-slate-900 border border-slate-700 text-xs text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-sky-400"
                >
                    <option value="SPAWN_FIRST_TIME_DESC">Spawn Teratas + Waktu Terbesar</option>
                    <option value="SPAWN_FIRST_TIME_ASC">Spawn Teratas + Waktu Terkecil</option>
                    <option value="TIME_ASC">Sisa Waktu Terkecil</option>
                    <option value="TIME_DESC">Sisa Waktu Terbesar</option>
                    <option value="NAME_ASC">Nama Boss (A-Z)</option>
                    <option value="MAP_ASC">Lokasi Map (A-Z)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 4. BOSS CONTAINER: LIST MODE (Default from Screenshot) -->
    <div id="listContainer" class="space-y-2.5">
        <!-- Javascript renders horizontal boss rows here -->
    </div>

    <!-- 5. BOSS CONTAINER: CARD MODE (Grid) -->
    <div id="gridContainer" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <!-- Javascript renders card items here -->
    </div>

    <!-- 6. EMPTY STATE -->
    <div id="emptyState" class="hidden py-24 text-center">
        <div class="text-4xl text-slate-500 mb-3">
            <i class="fa-solid fa-scroll"></i>
        </div>
        <h3 class="text-base font-bold text-slate-300 font-sans">Belum Ada Data Boss Terdeteksi</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
            Bot sedang mendengarkan channel Discord secara otomatis. Status boss akan langsung muncul begitu ada laporan spawn atau kill.
        </p>
    </div>

</div>

<!-- Floating Toast Notification -->
<div id="alarmToast" class="fixed bottom-6 right-6 max-w-sm w-full bg-slate-900 border-2 border-rose-500 rounded-2xl p-4 shadow-2xl shadow-rose-500/20 transform translate-y-32 opacity-0 transition-all duration-300 z-50 pointer-events-none">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-lg text-rose-400 shrink-0">
            <i class="fa-solid fa-dragon"></i>
        </div>
        <div class="overflow-hidden">
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-400 font-mono">BOSS SIAP DIBURU!</div>
            <div id="toastBossName" class="text-sm font-bold text-white truncate font-sans">Death Knight Yami</div>
            <div id="toastBossLoc" class="text-xs text-slate-400 truncate">Clements Mine</div>
        </div>
    </div>
</div>

@if($isAdmin ?? false)
<!-- ============================================================= -->
<!-- ADMIN MODALS                                                  -->
<!-- ============================================================= -->

<!-- 1. Modal Paste Log Discord -->
<div id="pasteModal" class="fixed inset-0 modal-backdrop-blur z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-700 relative">
        <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white font-sans uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-paste text-amber-400"></i>
                <span>Paste Log Chat Discord</span>
            </h3>
            <button onclick="closePasteModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <p class="text-xs text-slate-400 mb-3">
            Tempelkan teks riwayat chat Discord di bawah. Sistem otomatis mendeteksi boss yang muncul atau mati:
        </p>

        <textarea
            id="pasteLogText"
            rows="6"
            placeholder="[Monster]::[Knight of All-Evil] muncul di [Dungeon Silon-Aleph]&#10;[22-08-2026 14:00:00]&#10;[Boss]::[Rymos, Dragon of Destruction] Telah dibunuh oleh [Player]"
            class="w-full p-3 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-600 focus:outline-none focus:border-sky-400 font-mono"
        ></textarea>

        <div class="mt-4 flex items-center justify-end gap-2">
            <button onclick="closePasteModal()" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
                Batal
            </button>
            <button onclick="submitPasteLog()" class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-bold text-xs uppercase tracking-wider font-sans shadow-lg shadow-sky-500/20">
                Proses Log Chat
            </button>
        </div>
    </div>
</div>

<!-- 2. Modal Edit Interval On-The-Fly -->
<div id="intervalModal" class="fixed inset-0 modal-backdrop-blur z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-700 relative">
        <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white font-sans uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-400"></i>
                <span>Ubah Interval Respawn</span>
            </h3>
            <button onclick="closeIntervalModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <input type="hidden" id="editBossKey">
        <input type="hidden" id="editBossName">
        <input type="hidden" id="editMapName">

        <div class="space-y-3">
            <div id="editModalBossTitle" class="text-sm font-bold text-sky-400">Death Knight Yami</div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Durasi Respawn Baru (Menit)</label>
                <input type="number" id="editIntervalInput" min="1" max="1440" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white font-mono focus:outline-none focus:border-sky-400">
            </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
            <button onclick="closeIntervalModal()" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
                Batal
            </button>
            <button onclick="submitIntervalEdit()" class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-bold text-xs uppercase tracking-wider font-sans shadow-lg shadow-sky-500/20">
                Update Interval
            </button>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    // Configuration & State
    const SERVER_ACCESS_CODE = "{{ $server->access_code }}";
    const USER_ACCESS_KEY = "{{ $accessKey->code ?? $server->access_code }}";
    const CLIENT_SESSION_TOKEN = "{{ $sessionToken ?? '' }}";
    const SERVER_ID = {{ $server->id }};
    const WEBSOCKET_PORT = {{ $wsPort }};
    const IS_ADMIN = {{ ($isAdmin ?? false) ? 'true' : 'false' }};
    const CSRF_TOKEN = "{{ csrf_token() }}";

    let bosses = {};
    let viewMode = localStorage.getItem('seal_view_mode') || 'list'; // Default list matching screenshot
    let soundEnabled = localStorage.getItem('seal_sound_enabled') !== 'false';
    let pushNotifEnabled = false;
    let audioCtx = null;
    let ws = null;
    let reconnectTimer = null;
    let triggeredAlarms = new Set();

    // Initial server state injection
    @if(isset($states) && count($states) > 0)
        @foreach($states as $st)
            bosses["{{ $st->boss_key }}"] = {
                key: "{{ $st->boss_key }}",
                bossName: "{{ $st->boss_name }}",
                location: "{{ $st->map_name }}",
                slot: {{ $st->slot_index }},
                status: "{{ $st->status }}",
                isPaused: false,
                killedAt: {{ $st->killed_at ?: 'null' }},
                targetEndTime: {{ $st->target_respawn_at ?: 'null' }},
                durationMinutes: {{ $st->interval_minutes }}
            };
        @endforeach
    @endif

    // Web Audio Synthesizer (Zero CDN/External Dependencies)
    function getAudioContext() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                audioCtx = new AudioContext();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }
    let alarmLoopInterval = null;

    function playAlarmSound() {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const notes = [523.25, 659.25, 783.99, 1046.50];
            
            notes.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + idx * 0.11);
                gain.gain.setValueAtTime(0.35, now + idx * 0.11);
                gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.11 + 0.55);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(now + idx * 0.11);
                osc.stop(now + idx * 0.11 + 0.6);
            });
        } catch (e) {}
    }

    function startContinuousAlarm() {
        stopContinuousAlarm();
        playAlarmSound();
        alarmLoopInterval = setInterval(() => {
            playAlarmSound();
        }, 1800);
    }

    function stopContinuousAlarm() {
        if (alarmLoopInterval) {
            clearInterval(alarmLoopInterval);
            alarmLoopInterval = null;
        }
    }

    function testAlarmSound() {
        getAudioContext();
        showSpawnModal({
            bossName: 'Ice Queen',
            location: 'Ice Castle'
        });
        showToast("Test Alarm", "Sintesis suara Web Audio berhasil dibunyikan!");
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
        try { localStorage.setItem('seal_sound_enabled', soundEnabled); } catch (e) {}
        getAudioContext();
        document.getElementById('soundIcon').className = soundEnabled ? 'fa-solid fa-volume-high text-sky-400' : 'fa-solid fa-volume-xmark text-slate-500';
        document.getElementById('soundText').textContent = soundEnabled ? 'Suara: ON' : 'Suara: OFF';
    }

    function requestPushNotification() {
        if (!("Notification" in window)) {
            alert("Browser ini tidak mendukung desktop notification.");
            return;
        }
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                pushNotifEnabled = true;
                document.getElementById('notifToggleBtn').classList.add('border-emerald-500/50', 'text-emerald-300');
                new Notification("Seal Online Boss Timer", {
                    body: "Notifikasi desktop spawn boss aktif!"
                });
            }
        });
    }

    function setViewMode(mode) {
        viewMode = mode;
        try { localStorage.setItem('seal_view_mode', mode); } catch (e) {}
        const grid = document.getElementById('gridContainer');
        const list = document.getElementById('listContainer');
        const gridBtn = document.getElementById('viewGridBtn');
        const listBtn = document.getElementById('viewListBtn');

        if (mode === 'grid') {
            grid.classList.remove('hidden');
            list.classList.add('hidden');
            gridBtn.className = "px-3 py-1 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center gap-1 transition-all";
            listBtn.className = "px-3 py-1 rounded-lg text-slate-400 hover:text-white font-medium text-xs flex items-center gap-1 transition-all";
        } else {
            grid.classList.add('hidden');
            list.classList.remove('hidden');
            gridBtn.className = "px-3 py-1 rounded-lg text-slate-400 hover:text-white font-medium text-xs flex items-center gap-1 transition-all";
            listBtn.className = "px-3 py-1 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center gap-1 transition-all";
        }
        renderBosses();
    }

    function setPreset(h, m) {
        document.getElementById('addHours').value = h;
        document.getElementById('addMinutes').value = m;
        document.getElementById('addSeconds').value = 0;
    }

    // WebSocket Gateway Client
    function connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname;
        const wsUrl = `${protocol}//${host}:${WEBSOCKET_PORT}`;

        const pill = document.getElementById('discordStatusPill');

        try {
            ws = new WebSocket(wsUrl);

            ws.onopen = () => {
                pill.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Live Discord: Online`;

                ws.send(JSON.stringify({
                    type: 'SUBSCRIBE',
                    action: 'SUBSCRIBE',
                    accessCode: SERVER_ACCESS_CODE,
                    userAccessKey: USER_ACCESS_KEY,
                    sessionToken: CLIENT_SESSION_TOKEN
                }));
            };

            ws.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);
                    handleSocketMessage(msg);
                } catch (err) {}
            };

            ws.onclose = () => {
                pill.innerHTML = `<span class="w-2 h-2 rounded-full bg-rose-500"></span> Live Discord: Offline`;

                if (!reconnectTimer) {
                    reconnectTimer = setTimeout(() => {
                        reconnectTimer = null;
                        connectWebSocket();
                    }, 3000);
                }
            };

            ws.onerror = () => { ws.close(); };
        } catch (e) {}
    }

    function handleSessionKicked() {
        stopContinuousAlarm();
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
            reconnectTimer = null;
        }
        if (ws) {
            ws.onclose = null;
            ws.close();
        }
        console.warn('🔒 [Single-Device Guard] Session kicked because this access key was opened on another browser/device.');
        document.getElementById('kickoutModal').classList.remove('hidden');
    }

    function handleGlobalLockdown() {
        stopContinuousAlarm();
        if (ws) {
            ws.onclose = null;
            ws.close();
        }
        document.getElementById('lockdownModal').classList.remove('hidden');
        setTimeout(() => {
            window.location.href = "{{ route('tracker.landing') }}";
        }, 3000);
    }

    function handleSocketMessage(msg) {
        if (msg.type === 'GLOBAL_LOCKDOWN') {
            if (!IS_ADMIN) {
                handleGlobalLockdown();
                return;
            }
        } else if (msg.type === 'SESSION_REVOKED') {
            if (!IS_ADMIN && msg.userAccessKey === USER_ACCESS_KEY && msg.activeSessionToken !== CLIENT_SESSION_TOKEN) {
                handleSessionKicked();
                return;
            }
        } else if (msg.type === 'INITIAL_SYNC') {
            if (msg.bosses) {
                bosses = {};
                msg.bosses.forEach(b => {
                    bosses[b.key] = {
                        ...b,
                        isPaused: false
                    };
                });
                renderBosses();
            }
        } else if (msg.type === 'BOSS_UPDATE') {
            if (msg.boss) {
                bosses[msg.boss.key] = {
                    ...msg.boss,
                    isPaused: false
                };

                if (msg.boss.status === 'SPAWNED') {
                    triggerSpawnAlert(msg.boss);
                }

                renderBosses();
            }
        }
    }

    function triggerSpawnAlert(boss) {
        const alarmKey = `${boss.key}_${boss.targetEndTime}`;
        if (!triggeredAlarms.has(alarmKey)) {
            triggeredAlarms.add(alarmKey);
            showSpawnModal(boss);
            showToast(boss.bossName, `Telah SPAWN di ${boss.location || 'Unknown Map'}`);

            if (pushNotifEnabled && ("Notification" in window) && Notification.permission === "granted") {
                new Notification(`Boss Spawn: ${boss.bossName}`, {
                    body: `Lokasi: ${boss.location || 'Seal World'} (#${boss.slot || 1})`
                });
            }
        }
    }

    function showSpawnModal(boss) {
        const displayName = typeof getDisplayBossName === 'function' ? getDisplayBossName(boss, Object.values(bosses)) : (boss.bossName || 'Boss');
        document.getElementById('spawnModalBossName').textContent = displayName;
        document.getElementById('spawnModalLocation').textContent = boss.location || 'Unknown Map';
        document.getElementById('spawnModal').classList.remove('hidden');
        startContinuousAlarm();
    }

    function dismissSpawnModal() {
        document.getElementById('spawnModal').classList.add('hidden');
        stopContinuousAlarm();
    }

    function showToast(title, desc) {
        const toast = document.getElementById('alarmToast');
        document.getElementById('toastBossName').textContent = title;
        document.getElementById('toastBossLoc').textContent = desc;

        toast.classList.remove('translate-y-32', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');

        setTimeout(() => {
            toast.classList.add('translate-y-32', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }, 5000);
    }

    function formatTime(ms) {
        if (!ms || ms <= 0) return "00:00:00";
        const totalSec = Math.floor(ms / 1000);
        const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
        const s = String(totalSec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    function formatInterval(minutes) {
        const totalSec = (minutes || 30) * 60;
        const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
        const s = String(totalSec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    function getDisplayBossName(b, allList) {
        if (!b || !b.bossName) return '';
        if (b.bossName.includes('#')) return b.bossName;
        if (b.slot && b.slot > 1) return `${b.bossName} #${b.slot}`;
        if (allList && Array.isArray(allList)) {
            const rawBase = b.bossName.toLowerCase().trim();
            const hasTwin = allList.some(other => other.key !== b.key && other.bossName.replace(/\s*#\d+$/, '').toLowerCase().trim() === rawBase && (other.location || '') === (b.location || ''));
            if (hasTwin) {
                return `${b.bossName} #${b.slot || 1}`;
            }
        }
        return b.bossName;
    }

    function applyFilters() {
        renderBosses();
    }

    function renderBosses() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const sortBy = document.getElementById('sortSelect').value;
        const now = Date.now();

        let list = Object.values(bosses).filter(b => {
            return b.bossName.toLowerCase().includes(search) || (b.location && b.location.toLowerCase().includes(search));
        });

        // Sorting Logic (Matches Screenshot options)
        list.sort((a, b) => {
            const remA = a.targetEndTime ? Math.max(0, a.targetEndTime - now) : 0;
            const remB = b.targetEndTime ? Math.max(0, b.targetEndTime - now) : 0;
            const isSpawnA = a.status === 'SPAWNED' || remA <= 0;
            const isSpawnB = b.status === 'SPAWNED' || remB <= 0;

            if (sortBy === 'SPAWN_FIRST_TIME_DESC') {
                if (isSpawnA && !isSpawnB) return -1;
                if (!isSpawnA && isSpawnB) return 1;
                return remB - remA; // Waktu terbesar di atas
            } else if (sortBy === 'SPAWN_FIRST_TIME_ASC') {
                if (isSpawnA && !isSpawnB) return -1;
                if (!isSpawnA && isSpawnB) return 1;
                return remA - remB; // Waktu terkecil di atas
            } else if (sortBy === 'TIME_DESC') {
                return remB - remA;
            } else if (sortBy === 'TIME_ASC') {
                return remA - remB;
            } else if (sortBy === 'NAME_ASC') {
                return a.bossName.localeCompare(b.bossName);
            } else if (sortBy === 'MAP_ASC') {
                return (a.location || '').localeCompare(b.location || '');
            }
            return remA - remB;
        });

        document.getElementById('badgeTotalBoss').textContent = `${list.length} Boss`;

        const listContainer = document.getElementById('listContainer');
        const gridContainer = document.getElementById('gridContainer');
        const empty = document.getElementById('emptyState');

        if (list.length === 0) {
            listContainer.innerHTML = '';
            gridContainer.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');

        // =========================================================================
        // 1. RENDER LIST MODE (Horizontal Card Rows matching Screenshot 1 & 2)
        // =========================================================================
        listContainer.innerHTML = list.map(b => {
            const remMs = b.isPaused ? (b.pausedRemaining || 0) : (b.targetEndTime ? Math.max(0, b.targetEndTime - now) : 0);
            const isSpawned = (b.status === 'SPAWNED' || remMs <= 0) && !b.isPaused;
            const totalDurationMs = (b.durationMinutes || 30) * 60 * 1000;
            const progressPercent = isSpawned ? 0 : Math.min(100, Math.max(0, (remMs / totalDurationMs) * 100));
            const displayName = getDisplayBossName(b, list);

            return `
                <div class="boss-row-card rounded-2xl px-5 py-4 ${isSpawned ? 'boss-row-spawned' : ''}">
                    <!-- Bottom Progress Underline -->
                    <div class="absolute bottom-0 left-0 h-[3px] bg-slate-800 w-full">
                        <div class="h-full ${isSpawned ? 'bg-rose-500' : (b.isPaused ? 'bg-amber-400' : 'bg-[#00f59b]')} progress-bar-line" style="width: ${progressPercent}%"></div>
                    </div>

                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">

                        <!-- Left: Monster Sprite, Name & Location -->
                        <div class="flex items-center gap-3.5 min-w-[280px]">
                            <div class="w-10 h-10 rounded-xl bg-purple-950/60 border border-purple-500/30 flex items-center justify-center text-purple-400 shrink-0">
                                <i class="fa-solid fa-dragon text-base"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h3 class="text-[17px] font-bold text-white font-sans tracking-tight truncate" title="${displayName}">
                                    ${displayName}
                                </h3>
                                <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5 font-sans">
                                    <i class="fa-solid fa-location-dot text-rose-500 text-[11px]"></i>
                                    <span class="text-slate-300 font-medium">${b.location || 'Lokasi Unknown'}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Center-Left: Status Badge Pill -->
                        <div class="shrink-0">
                            ${isSpawned
                                ? `<span class="px-3 py-1 rounded-full bg-rose-950/80 border border-rose-500 text-rose-400 text-xs font-bold uppercase tracking-wider font-mono">SPAWN / READY</span>`
                                : (b.isPaused
                                    ? `<span class="px-3 py-1 rounded-full bg-amber-950/80 border border-amber-500 text-amber-400 text-xs font-bold uppercase tracking-wider font-mono">JEDA</span>`
                                    : `<span class="px-3 py-1 rounded-full bg-emerald-950/80 border border-emerald-500/50 text-emerald-400 text-xs font-bold uppercase tracking-wider font-mono">BERJALAN</span>`
                                )
                            }
                        </div>

                        <!-- Center: BIG COUNTDOWN TIMER & INTERVAL (Pixel-Perfect from Screenshot) -->
                        <div class="flex items-center gap-3">
                            <div class="text-[28px] sm:text-[32px] font-black font-mono tabular-nums tracking-wide ${isSpawned ? 'text-neon-red animate-pulse' : (b.isPaused ? 'text-neon-amber' : 'text-neon-green')}">
                                ${isSpawned ? '00:00:00' : formatTime(remMs)}
                            </div>

                            <!-- Interval Tag with Clickable Pencil for Admin -->
                            <div class="px-2.5 py-1 bg-slate-900/90 border border-slate-700/80 rounded-xl text-xs font-mono text-slate-300 flex items-center gap-1.5 shadow-inner">
                                ${IS_ADMIN ? `
                                    <button onclick="openIntervalModal('${b.key}', '${b.bossName}', '${b.location || ''}', ${b.durationMinutes || 30})" class="hover:text-amber-300 transition-colors flex items-center gap-1.5" title="Klik untuk mengubah interval menit">
                                        <span>Interval: ${formatInterval(b.durationMinutes)}</span>
                                        <i class="fa-solid fa-pen-to-square text-amber-400 text-[11px]"></i>
                                    </button>
                                ` : `
                                    <span>Interval: ${formatInterval(b.durationMinutes)}</span>
                                `}
                            </div>
                        </div>

                        <!-- Right: Action Buttons (For Admin Only - Hidden in Read-Only) -->
                        ${IS_ADMIN ? `
                        <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                            <!-- Start / Pause Toggle -->
                            <button onclick="adminTogglePause('${b.key}')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-md transition-all ${b.isPaused ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-amber-500 hover:bg-amber-400 text-slate-950'}">
                                <i class="fa-solid ${b.isPaused ? 'fa-play' : 'fa-pause'} text-[10px]"></i>
                                <span>${b.isPaused ? 'Start' : 'Berhenti'}</span>
                            </button>

                            <!-- Reset Button -->
                            <button onclick="adminResetBoss('${b.key}')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs border border-slate-600 flex items-center gap-1.5 transition-all" title="Reset countdown">
                                <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                <span>Reset</span>
                            </button>

                            <!-- Delete Button -->
                            <button onclick="adminDeleteBoss('${b.key}')" class="p-2 rounded-xl bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 text-xs border border-slate-700 transition-all flex items-center justify-center" title="Hapus boss dari daftar">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                        ` : ''}

                    </div>
                </div>
            `;
        }).join('');

        // =========================================================================
        // 2. RENDER CARD MODE (Grid Mode)
        // =========================================================================
        gridContainer.innerHTML = list.map(b => {
            const remMs = b.isPaused ? (b.pausedRemaining || 0) : (b.targetEndTime ? Math.max(0, b.targetEndTime - now) : 0);
            const isSpawned = (b.status === 'SPAWNED' || remMs <= 0) && !b.isPaused;
            const totalDurationMs = (b.durationMinutes || 30) * 60 * 1000;
            const progressPercent = isSpawned ? 0 : Math.min(100, Math.max(0, (remMs / totalDurationMs) * 100));
            const displayName = getDisplayBossName(b, list);

            return `
                <div class="boss-row-card rounded-2xl p-5 ${isSpawned ? 'boss-row-spawned' : ''}">
                    <!-- Bottom Progress Underline -->
                    <div class="absolute bottom-0 left-0 h-[3px] bg-slate-800 w-full">
                        <div class="h-full ${isSpawned ? 'bg-rose-500' : (b.isPaused ? 'bg-amber-400' : 'bg-[#00f59b]')} progress-bar-line" style="width: ${progressPercent}%"></div>
                    </div>

                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="overflow-hidden">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-dragon text-purple-400 text-base shrink-0"></i>
                                <h3 class="text-base font-bold text-white font-sans truncate" title="${displayName}">
                                    ${displayName}
                                </h3>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-1">
                                <i class="fa-solid fa-location-dot text-rose-500 text-[10px]"></i>
                                <span class="text-slate-300 font-medium truncate">${b.location || 'Lokasi Unknown'}</span>
                            </div>
                        </div>

                        <div>
                            ${isSpawned
                                ? `<span class="px-2 py-0.5 rounded-full bg-rose-950 border border-rose-500 text-rose-400 text-[10px] font-bold uppercase">SPAWN</span>`
                                : (b.isPaused
                                    ? `<span class="px-2 py-0.5 rounded-full bg-amber-950 border border-amber-500 text-amber-400 text-[10px] font-bold uppercase">JEDA</span>`
                                    : `<span class="px-2 py-0.5 rounded-full bg-emerald-950 border border-emerald-500/50 text-emerald-400 text-[10px] font-bold uppercase">BERJALAN</span>`
                                )
                            }
                        </div>
                    </div>

                    <!-- Countdown Digital Center -->
                    <div class="my-4 text-center">
                        <div class="text-[32px] font-black font-mono tabular-nums ${isSpawned ? 'text-neon-red' : (b.isPaused ? 'text-neon-amber' : 'text-neon-green')}">
                            ${isSpawned ? '00:00:00' : formatTime(remMs)}
                        </div>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-slate-900 border border-slate-700/80 rounded-lg text-xs font-mono text-slate-300 mt-1">
                            ${IS_ADMIN ? `
                                <button onclick="openIntervalModal('${b.key}', '${b.bossName}', '${b.location || ''}', ${b.durationMinutes || 30})" class="hover:text-amber-300 flex items-center gap-1.5">
                                    <span>Interval: ${formatInterval(b.durationMinutes)}</span>
                                    <i class="fa-solid fa-pen-to-square text-amber-400 text-[10px]"></i>
                                </button>
                            ` : `
                                <span>Interval: ${formatInterval(b.durationMinutes)}</span>
                            `}
                        </div>
                    </div>

                    ${IS_ADMIN ? `
                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between gap-1.5 text-xs font-sans">
                        <button onclick="adminTogglePause('${b.key}')" class="px-2.5 py-1.5 rounded-xl font-bold text-[11px] flex items-center justify-center gap-1 flex-1 ${b.isPaused ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-slate-950'}">
                            <i class="fa-solid ${b.isPaused ? 'fa-play' : 'fa-pause'} text-[9px]"></i>
                            <span>${b.isPaused ? 'Start' : 'Berhenti'}</span>
                        </button>
                        <button onclick="adminResetBoss('${b.key}')" class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-[11px] border border-slate-600 flex items-center gap-1">
                            <i class="fa-solid fa-rotate-left text-[9px]"></i>
                            <span>Reset</span>
                        </button>
                        <button onclick="adminDeleteBoss('${b.key}')" class="p-1.5 rounded-xl bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 text-[11px]">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    // 1-Second Absolute Timer Loop
    setInterval(() => {
        const now = Date.now();
        Object.values(bosses).forEach(b => {
            if (!b.isPaused && b.targetEndTime && b.targetEndTime <= now && b.status !== 'SPAWNED') {
                b.status = 'SPAWNED';
                triggerSpawnAlert(b);
            }
        });
        renderBosses();
    }, 1000);

    // =============================================================
    // ADMIN ACTIONS & MUTATIONS (AJAX)
    // =============================================================
    function submitAddBossForm() {
        const name = document.getElementById('addBossName').value;
        const loc = document.getElementById('addBossLocation').value;
        const h = parseInt(document.getElementById('addHours').value || '0', 10);
        const m = parseInt(document.getElementById('addMinutes').value || '0', 10);
        const totalMinutes = Math.max(1, (h * 60) + m);

        if (!name.trim()) {
            alert("Nama boss harus diisi.");
            return;
        }

        fetch(`{{ url('admin/servers') }}/${SERVER_ID}/manual-event`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: 'KILL',
                boss_name: name,
                location: loc,
                duration_minutes: totalMinutes
            })
        }).then(r => r.json()).then(() => {
            document.getElementById('addBossName').value = '';
            document.getElementById('addBossLocation').value = '';
            showToast("➕ Boss Ditambahkan", `${name} berhasil ditambahkan ke daftar!`);
        });
    }

    function controlBot(action) {
        fetch(`{{ url('admin/servers') }}/${SERVER_ID}/control-bot`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ action: action })
        }).then(r => r.json()).then(data => {
            showToast("🤖 Bot Discord", data.message || `Perintah ${action} terkirim`);
        });
    }

    function adminTogglePause(key) {
        if (!bosses[key]) return;
        const b = bosses[key];
        const now = Date.now();

        if (b.isPaused) {
            b.targetEndTime = now + (b.pausedRemaining || 0);
            b.isPaused = false;
            b.pausedRemaining = null;
            showToast("Timer Dimulai", `${b.bossName} timer kembali berjalan.`);
        } else {
            b.pausedRemaining = b.targetEndTime ? Math.max(0, b.targetEndTime - now) : 0;
            b.isPaused = true;
            showToast("Timer Diberhentikan", `${b.bossName} timer dihentikan sementara.`);
        }
        renderBosses();
    }

    function adminStartAll() {
        const now = Date.now();
        Object.values(bosses).forEach(b => {
            if (b.isPaused) {
                b.targetEndTime = now + (b.pausedRemaining || 0);
                b.isPaused = false;
                b.pausedRemaining = null;
            }
        });
        renderBosses();
        showToast("Mulai Semua", "Seluruh timer countdown telah dijalankan.");
    }

    function adminPauseAll() {
        const now = Date.now();
        Object.values(bosses).forEach(b => {
            if (!b.isPaused && b.targetEndTime) {
                b.pausedRemaining = Math.max(0, b.targetEndTime - now);
                b.isPaused = true;
            }
        });
        renderBosses();
        showToast("Berhenti Semua", "Seluruh timer countdown telah dihentikan sementara.");
    }

    function adminResetBoss(key) {
        fetch(`{{ url('admin/servers') }}/${SERVER_ID}/reset-boss`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ boss_key: key })
        }).then(r => r.json()).then(() => {
            showToast("Reset Timer", "Timer countdown berhasil direset ke durasi penuh.");
        });
    }

    function adminResetAll() {
        if (!confirm("Reset seluruh timer boss ke durasi penuh?")) return;
        Object.keys(bosses).forEach(k => {
            adminResetBoss(k);
        });
    }

    function adminDeleteBoss(key) {
        if (!confirm("Hapus kartu boss ini dari tracker?")) return;
        fetch(`{{ url('admin/servers') }}/${SERVER_ID}/delete-boss`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ boss_key: key })
        }).then(r => r.json()).then(() => {
            delete bosses[key];
            renderBosses();
            showToast("Boss Dihapus", "Kartu boss berhasil dihapus dari tracker.");
        });
    }

    function adminDeleteAll() {
        if (!confirm("Hapus SELURUH daftar boss saat ini?")) return;
        Object.keys(bosses).forEach(k => {
            adminDeleteBoss(k);
        });
    }

    function openPasteModal() {
        document.getElementById('pasteModal').classList.remove('hidden');
    }
    function closePasteModal() {
        document.getElementById('pasteModal').classList.add('hidden');
    }
    function submitPasteLog() {
        const text = document.getElementById('pasteLogText').value;
        if (!text.trim()) return;

        fetch(`{{ url('admin/servers') }}/${SERVER_ID}/parse-log`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ text: text })
        }).then(r => r.json()).then(() => {
            closePasteModal();
            document.getElementById('pasteLogText').value = '';
            showToast("Log Diproses", "Log Discord berhasil dianalisis & diupdate!");
        });
    }

    function openIntervalModal(key, name, loc, dur) {
        document.getElementById('editBossKey').value = key;
        document.getElementById('editBossName').value = name;
        document.getElementById('editMapName').value = loc;
        document.getElementById('editIntervalInput').value = dur;
        document.getElementById('editModalBossTitle').textContent = `${name} (${loc || 'Semua Map'})`;
        document.getElementById('intervalModal').classList.remove('hidden');
    }
    function closeIntervalModal() {
        document.getElementById('intervalModal').classList.add('hidden');
    }
    function submitIntervalEdit() {
        const key = document.getElementById('editBossKey').value;
        const name = document.getElementById('editBossName').value;
        const loc = document.getElementById('editMapName').value;
        const dur = document.getElementById('editIntervalInput').value;

        fetch(`{{ url('admin/servers') }}/${SERVER_ID}/quick-interval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                boss_key: key,
                boss_name: name,
                map_name: loc,
                interval_minutes: dur
            })
        }).then(r => r.json()).then(() => {
            closeIntervalModal();
            if (bosses[key]) {
                bosses[key].durationMinutes = parseInt(dur, 10);
                renderBosses();
            }
            showToast("Interval Diperbarui", `Interval untuk ${name} diubah menjadi ${dur} menit.`);
        });
    }

    // Page Initialization
    document.addEventListener('DOMContentLoaded', () => {
        const savedView = localStorage.getItem('seal_view_mode') || 'list';
        setViewMode(savedView);

        const savedSound = localStorage.getItem('seal_sound_enabled');
        if (savedSound !== null) {
            soundEnabled = savedSound === 'true';
            document.getElementById('soundIcon').className = soundEnabled ? 'fa-solid fa-volume-high text-sky-400' : 'fa-solid fa-volume-xmark text-slate-500';
            document.getElementById('soundText').textContent = soundEnabled ? 'Suara: ON' : 'Suara: OFF';
        }

        renderBosses();
        connectWebSocket();

        document.body.addEventListener('click', () => {
            getAudioContext();
        }, { once: true });
    });
</script>
@endsection

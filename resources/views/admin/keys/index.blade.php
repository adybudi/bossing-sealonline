@extends('layouts.admin')

@section('title', 'Kelola Kode Akses & Lisensi Penjualan')

@section('content')
<div class="space-y-8">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold font-cinzel text-white">Kelola Kode Akses & Lisensi Pemain</h1>
            <p class="text-xs text-slate-400 mt-1">
                Atur kode akses unik per server untuk dijual dengan masa aktif (7 Hari, 14 Hari, 30 Hari, atau Permanen) & pembatasan 1 login per perangkat.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Dashboard</span>
            </a>
            <button onclick="openCreateKeyModal()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all font-rajdhani text-sm uppercase tracking-wider">
                <i class="fa-solid fa-plus"></i>
                <span>Buat Kode Akses Baru</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-seal-card border border-seal-border rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <form action="{{ route('admin.keys.index') }}" method="GET" class="flex items-center gap-3">
            <label for="server_id" class="text-xs font-bold text-slate-300 uppercase tracking-wider">Filter Server:</label>
            <select name="server_id" id="server_id" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400">
                <option value="">Semua Server Seal</option>
                @foreach($servers as $srv)
                    <option value="{{ $srv->id }}" {{ ($serverId == $srv->id) ? 'selected' : '' }}>
                        {{ $srv->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <span class="text-xs text-slate-400 font-mono">
            Total: <strong class="text-white">{{ $keys->total() }}</strong> Kode Akses Terdata
        </span>
    </div>

    <!-- Keys Table Card -->
    <div class="bg-seal-card border border-seal-border rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-seal-border flex items-center justify-between">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider font-cinzel">Daftar Lisensi Kode Akses</h2>
            <span class="text-xs text-slate-500">Anti-Share 1 Perangkat Aktif & Auto-Kick</span>
        </div>

        @if($keys->isEmpty())
            <div class="py-16 text-center">
                <div class="text-3xl text-slate-500 mb-2">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-300">Belum Ada Kode Akses</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4">Buat kode akses pertama untuk server Seal Anda dengan durasi 7, 14, 30 hari atau permanen.</p>
                <button onclick="openCreateKeyModal()" class="px-4 py-2 bg-amber-500 text-slate-950 font-bold rounded-lg text-xs inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Buat Kode Akses Baru
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/60 text-slate-400 uppercase font-cinzel text-[11px] border-b border-seal-border">
                        <tr>
                            <th class="px-6 py-4">Server</th>
                            <th class="px-6 py-4">Kode Akses Unik (Lisensi)</th>
                            <th class="px-6 py-4">Catatan / Pembeli</th>
                            <th class="px-6 py-4">Masa Aktif & Sisa Waktu</th>
                            <th class="px-6 py-4">Status & Sesi Perangkat</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-seal-border font-mono">
                        @foreach($keys as $k)
                            <tr class="hover:bg-slate-900/30 transition-colors {{ $k->isExpired() ? 'opacity-60 bg-rose-950/10' : '' }}">
                                <!-- Server Info -->
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white font-sans text-sm">{{ $k->server->name ?? 'Unknown' }}</div>
                                    <span class="text-[10px] text-slate-500 font-mono">ID Server: {{ $k->seal_server_id }}</span>
                                </td>

                                <!-- Code with Copy Button -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-amber-300 font-mono tracking-wider bg-slate-900/90 px-2.5 py-1 rounded-lg border border-slate-700/80">
                                            {{ $k->code }}
                                        </span>
                                        <button onclick="copyToClipboard('{{ $k->code }}', this)" class="p-1 rounded hover:bg-slate-700 text-slate-400 hover:text-white text-xs transition-all" title="Salin Kode Akses">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Label / Buyer Note -->
                                <td class="px-6 py-4 font-sans">
                                    <div class="text-white font-medium">{{ $k->label ?: '-' }}</div>
                                    <div class="text-[10px] text-slate-500">Dibuat: {{ $k->created_at->format('d M Y') }}</div>
                                </td>

                                <!-- Duration & Remaining -->
                                <td class="px-6 py-4 font-sans">
                                    @if($k->duration_type === 'permanent')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-950 text-blue-400 border border-blue-500/30">
                                            <i class="fa-solid fa-infinity text-[10px]"></i> Permanen
                                        </span>
                                    @elseif($k->isExpired())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950 text-rose-400 border border-rose-500/30">
                                            <i class="fa-solid fa-hourglass-end text-[10px]"></i> Kadaluarsa (Expired)
                                        </span>
                                        <div class="text-[10px] text-rose-400/80 mt-1 font-mono">Habis: {{ $k->expires_at->format('d M Y H:i') }}</div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950 text-emerald-400 border border-emerald-500/30">
                                            <i class="fa-solid fa-clock text-[10px]"></i> Sisa: {{ $k->remaining_time_human }}
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-1 font-mono">Hingga: {{ $k->expires_at->format('d M Y H:i') }}</div>
                                    @endif
                                </td>

                                <!-- Status & Single Device Session Info -->
                                <td class="px-6 py-4 font-sans">
                                    <div class="flex items-center gap-2 mb-1">
                                        <form action="{{ route('admin.keys.toggle_active', $k) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[10px] px-2.5 py-0.5 rounded-full {{ $k->is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                                                {{ $k->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    </div>
                                    @if($k->last_ip_address)
                                        <div class="text-[10px] text-slate-400 font-mono flex items-center gap-1.5">
                                            <i class="fa-solid fa-laptop text-slate-500"></i>
                                            <span>IP: {{ $k->last_ip_address }}</span>
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-500">Belum pernah login</div>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">

                                        <!-- Extend Duration Modal Trigger -->
                                        @if($k->duration_type !== 'permanent')
                                            <button onclick="openExtendModal({{ $k->id }}, '{{ $k->code }}')" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs border border-slate-700 flex items-center gap-1.5 transition-all" title="Perpanjang Masa Aktif">
                                                <i class="fa-solid fa-calendar-plus text-amber-400 text-[11px]"></i>
                                                <span>Tambah Hari</span>
                                            </button>
                                        @endif

                                        <!-- Delete Button -->
                                        <form action="{{ route('admin.keys.destroy', $k) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kode akses {{ $k->code }}? Pengguna dengan kode ini akan langsung terlogout permanen.')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs transition-all" title="Hapus Permanen">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-seal-border">
                {{ $keys->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL: BUAT KODE AKSES BARU -->
<div id="createKeyModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-seal-card border border-seal-border rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative">
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold font-cinzel text-white flex items-center gap-2">
                <i class="fa-solid fa-key text-amber-400"></i>
                <span>Buat Kode Akses / Lisensi Baru</span>
            </h3>
            <button onclick="document.getElementById('createKeyModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">
                &times;
            </button>
        </div>

        <form action="{{ route('admin.keys.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Select Server -->
            <div>
                <label for="seal_server_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Server Seal Online <span class="text-rose-400">*</span>
                </label>
                <select name="seal_server_id" id="seal_server_id" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400">
                    @foreach($servers as $srv)
                        <option value="{{ $srv->id }}" {{ ($serverId == $srv->id) ? 'selected' : '' }}>
                            {{ $srv->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Select Duration Preset -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Pilihan Masa Aktif Lisensi <span class="text-rose-400">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-900 border border-slate-700 cursor-pointer hover:border-amber-500/50">
                        <input type="radio" name="duration_type" value="7_days" checked onchange="toggleCustomDays(false)" class="text-amber-500">
                        <span class="text-xs text-white font-semibold">7 Hari</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-900 border border-slate-700 cursor-pointer hover:border-amber-500/50">
                        <input type="radio" name="duration_type" value="14_days" onchange="toggleCustomDays(false)" class="text-amber-500">
                        <span class="text-xs text-white font-semibold">14 Hari</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-900 border border-slate-700 cursor-pointer hover:border-amber-500/50">
                        <input type="radio" name="duration_type" value="30_days" onchange="toggleCustomDays(false)" class="text-amber-500">
                        <span class="text-xs text-white font-semibold">30 Hari (1 Bulan)</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-900 border border-slate-700 cursor-pointer hover:border-amber-500/50">
                        <input type="radio" name="duration_type" value="permanent" onchange="toggleCustomDays(false)" class="text-amber-500">
                        <span class="text-xs text-white font-semibold">Permanen</span>
                    </label>
                </div>
            </div>

            <!-- Label / Note -->
            <div>
                <label for="label" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Catatan / Nama Pembeli (Opsional)
                </label>
                <input
                    type="text"
                    name="label"
                    id="label"
                    placeholder="Masukkan Nama Pembeli"
                    class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400"
                >
            </div>

            <!-- Cloudflare Turnstile CAPTCHA -->
            <x-turnstile />

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('createKeyModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs font-medium border border-slate-700">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-amber-500/20 transition-all uppercase tracking-wider font-rajdhani text-sm flex items-center gap-2">
                    <i class="fa-solid fa-key"></i>
                    <span>Generate Kode Akses</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: PERPANJANG MASA AKTIF -->
<div id="extendModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-seal-card border border-seal-border rounded-3xl max-w-sm w-full p-6 shadow-2xl relative">
        <h3 class="text-base font-bold font-cinzel text-white mb-2">Perpanjang Masa Aktif</h3>
        <p class="text-xs text-slate-400 mb-4">Tambahkan durasi hari untuk kode <span id="extendKeyCode" class="text-amber-400 font-mono font-bold"></span></p>

        <form id="extendForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="extendDays" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Jumlah Hari Tambahan <span class="text-rose-400">*</span>
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="document.getElementById('extendDays').value = 7" class="py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-xs text-slate-200 border border-slate-700 font-bold">
                        +7 Hari
                    </button>
                    <button type="button" onclick="document.getElementById('extendDays').value = 14" class="py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-xs text-slate-200 border border-slate-700 font-bold">
                        +14 Hari
                    </button>
                    <button type="button" onclick="document.getElementById('extendDays').value = 30" class="py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-xs text-slate-200 border border-slate-700 font-bold">
                        +30 Hari
                    </button>
                </div>
                <input
                    type="number"
                    name="days"
                    id="extendDays"
                    value="7"
                    min="1"
                    max="365"
                    required
                    class="w-full px-4 py-2.5 mt-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-amber-400"
                >
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('extendModal').classList.add('hidden')" class="px-3 py-2 rounded-xl bg-slate-800 text-slate-400 text-xs font-medium">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl">
                    Perpanjang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i>';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        });
    }
    function openCreateKeyModal() {
        document.getElementById('createKeyModal').classList.remove('hidden');
        setTimeout(() => {
            if (window.turnstile) {
                try { window.turnstile.reset(); } catch(e) {}
            }
        }, 100);
    }
    function openExtendModal(id, code) {
        document.getElementById('extendKeyCode').textContent = code;
        document.getElementById('extendForm').action = `/admin/keys/${id}/extend`;
        document.getElementById('extendModal').classList.remove('hidden');
    }
</script>
@endsection

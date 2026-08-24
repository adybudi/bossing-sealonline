@extends('layouts.admin')

@section('title', 'Tambah Server Seal Baru')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold font-cinzel text-white">Tambah Server Seal Baru</h1>
            <p class="text-xs text-slate-400 mt-1">Masukkan token Discord dan channel ID untuk memulai screening boss.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>

    <div class="bg-seal-card border border-seal-border rounded-2xl p-6 sm:p-8 shadow-xl">
        <form action="{{ route('admin.servers.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Nama Server Seal <span class="text-rose-400">*</span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    value="{{ old('name') }}" 
                    placeholder="Contoh: Seal BOD Classic, Seal Nostalgia Reborn" 
                    required
                    class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400"
                >
                <p class="text-[11px] text-slate-500 mt-1.5">Nama ini akan ditampilkan pada header dashboard pemain.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Discord User Token (Self-Bot) <span class="text-slate-400 text-[10px] font-normal normal-case ml-1">(Opsional - Default Sistem)</span>
                </label>
                <input 
                    type="password" 
                    name="discord_token" 
                    value="{{ old('discord_token') }}" 
                    placeholder="Kosongkan untuk menggunakan Token Default sistem..." 
                    class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400 font-mono"
                >
                <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-amber-400 text-xs"></i>
                    <span>Jika dikosongkan, sistem otomatis menggunakan token default. Token disimpan terenkripsi AES-256.</span>
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Discord Channel ID `#boss` <span class="text-rose-400">*</span>
                </label>
                <input 
                    type="text" 
                    name="discord_channel_id" 
                    value="{{ old('discord_channel_id') }}" 
                    placeholder="Contoh: 1340714356706639873" 
                    required
                    class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400 font-mono"
                >
                <p class="text-[11px] text-slate-500 mt-1.5">ID Channel Discord tempat pesan spawn/kill boss dikirim (Wajib diisi).</p>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0">
                    <div>
                        <span class="text-sm font-semibold text-white">Aktifkan Server Sekarang</span>
                        <p class="text-[11px] text-slate-400">Server aktif dapat diakses via kode akses dan dipantau oleh daemon bot.</p>
                    </div>
                </label>
            </div>

            <!-- Cloudflare Turnstile CAPTCHA -->
            <x-turnstile />

            <div class="pt-4 border-t border-seal-border flex items-center justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider font-rajdhani text-sm">
                    Simpan Server
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

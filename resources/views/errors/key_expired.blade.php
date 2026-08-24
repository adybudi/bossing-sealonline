@extends('layouts.app')

@section('title', 'Masa Aktif Kode Akses Berakhir')

@section('content')
<div class="min-h-[85vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full text-center">
        
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-rose-500/10 border border-rose-500/30 mb-5 shadow-xl shadow-rose-500/10">
            <i class="fa-solid fa-hourglass-end text-3xl text-rose-500"></i>
        </div>

        <div class="glass-card rounded-2xl p-7 shadow-2xl border border-rose-500/30">
            <h1 class="text-xl font-bold text-white mb-2 font-sans">
                Masa Aktif Kode Akses Telah Berakhir
            </h1>

            <p class="text-xs text-slate-400 mb-6 leading-relaxed">
                Kode akses unik <strong class="text-rose-400 font-mono">{{ $accessKey->code }}</strong> untuk server <strong class="text-white">{{ $server->name }}</strong> telah habis masa berlakunya.
            </p>

            <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 text-xs text-slate-400 mb-6 space-y-1.5 font-mono text-left">
                <div class="flex justify-between">
                    <span>Durasi Lisensi:</span>
                    <span class="text-white font-bold">{{ ucfirst(str_replace('_', ' ', $accessKey->duration_type)) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kadaluarsa Pada:</span>
                    <span class="text-rose-400 font-bold">{{ $accessKey->expires_at ? $accessKey->expires_at->format('d M Y H:i') : '-' }}</span>
                </div>
            </div>

            <div class="space-y-3">
                <a 
                    href="{{ route('tracker.landing') }}" 
                    class="block w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-xl shadow-lg transition-all text-xs uppercase tracking-wider font-rajdhani text-sm flex items-center justify-center gap-1.5"
                >
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Kembali ke Halaman Utama</span>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

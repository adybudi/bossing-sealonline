@extends('layouts.app')

@section('title', 'Admin Login - Seal Boss Tracker')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 mb-3">
                <i class="fa-solid fa-shield-halved text-2xl text-amber-400"></i>
            </div>
            <h1 class="text-2xl font-bold font-cinzel text-amber-300">
                ADMINISTRATOR LOGIN
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Masuk untuk mengelola server Seal, token Discord, dan interval boss.
            </p>
        </div>

        <div class="glass-card rounded-2xl p-6 sm:p-8 shadow-2xl">
            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-950/50 border border-rose-500/30 text-rose-300 text-xs flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400 mt-0.5"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Email Administrator
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="admin@seal.local" 
                        required 
                        autofocus
                        class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="••••••••" 
                        required 
                        class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400"
                    >
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <!-- Cloudflare Turnstile CAPTCHA -->
                <x-turnstile />

                <button 
                    type="submit" 
                    class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 text-sm transition-all uppercase tracking-wider font-rajdhani text-base"
                >
                    Masuk ke Admin Panel
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('tracker.landing') }}" class="text-xs text-slate-500 hover:text-slate-400 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>Kembali ke Portal Akses Pengguna</span>
            </a>
        </div>

    </div>
</div>
@endsection

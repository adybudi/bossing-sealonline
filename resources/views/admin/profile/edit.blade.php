@extends('layouts.admin')

@section('title', 'Profil & Keamanan Administrator')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold font-cinzel text-white">Profil & Keamanan Administrator</h1>
            <p class="text-xs text-slate-400 mt-1">Ubah nama akun, alamat email, dan kata sandi login administrator.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
            <span>Dashboard</span>
        </a>
    </div>

    <!-- Edit Profile Card -->
    <div class="bg-seal-card border border-seal-border rounded-2xl p-6 sm:p-8 shadow-xl">
        <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Akun -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-amber-400 uppercase tracking-wider font-cinzel border-b border-slate-800 pb-2 flex items-center gap-2">
                    <i class="fa-solid fa-user text-amber-400"></i>
                    <span>Informasi Administrator</span>
                </h3>

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Nama Lengkap <span class="text-rose-400">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        value="{{ old('name', $user->name) }}"
                        required
                        class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400"
                    >
                    @error('name')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Alamat Email Login <span class="text-rose-400">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email', $user->email) }}"
                        required
                        class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400 font-mono"
                    >
                    @error('email')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Section 2: Ganti Password -->
            <div class="space-y-4 pt-4 border-t border-slate-800">
                <h3 class="text-xs font-bold text-amber-400 uppercase tracking-wider font-cinzel border-b border-slate-800 pb-2 flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-400"></i>
                    <span>Ganti Kata Sandi (Password)</span>
                </h3>
                <p class="text-[11px] text-slate-400">
                    Kosongkan kolom password di bawah jika Anda hanya ingin mengubah nama atau alamat email.
                </p>

                <div>
                    <label for="current_password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Password Saat Ini (Konfirmasi Keamanan)
                    </label>
                    <input 
                        type="password" 
                        name="current_password" 
                        id="current_password" 
                        placeholder="Masukkan password lama jika ingin mengganti password baru"
                        class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400 font-mono"
                    >
                    @error('current_password')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Password Baru
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            placeholder="Minimal 6 karakter"
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400 font-mono"
                        >
                        @error('password')
                            <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Ulangi Password Baru
                        </label>
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            id="password_confirmation" 
                            placeholder="Konfirmasi password baru"
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-amber-400 font-mono"
                        >
                    </div>
                </div>
            </div>

            <!-- Cloudflare Turnstile CAPTCHA -->
            <x-turnstile />

            <!-- Submit Button -->
            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs font-medium border border-slate-700 transition-all">
                    Batal
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-amber-500/20 transition-all uppercase tracking-wider font-rajdhani text-sm flex items-center gap-2"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

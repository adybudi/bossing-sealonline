<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - Seal Boss Tracker</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 (Vector Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Cloudflare Turnstile CAPTCHA API -->
    @if(config('services.turnstile.enabled') && config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        seal: {
                            bg: '#090d16',
                            sidebar: '#0f1422',
                            card: '#161c2e',
                            border: '#222b42',
                            gold: '#fbbf24',
                        }
                    },
                    fontFamily: {
                        cinzel: ['Cinzel', 'serif'],
                        rajdhani: ['Rajdhani', 'sans-serif'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #090d16; color: #f1f5f9; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Top Navbar -->
    <header class="bg-seal-sidebar border-b border-seal-border sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                    <span class="font-cinzel font-black text-lg text-transparent bg-clip-text bg-gradient-to-r from-amber-200 to-amber-400">
                        SEAL ADMIN
                    </span>
                </a>
                <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold font-mono">v2.0</span>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-300 hover:text-white font-medium flex items-center gap-1.5">
                    <i class="fa-solid fa-server text-sky-400"></i> Server List
                </a>

                <a href="{{ route('admin.keys.index') }}" class="text-xs text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-key text-amber-400"></i> Kelola Lisensi (Jual)
                </a>

                <div class="h-4 w-px bg-slate-700"></div>

                <a href="{{ route('tracker.landing') }}" target="_blank" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-slate-400"></i> Portal Tracker
                </a>

                <div class="h-4 w-px bg-slate-700"></div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.profile.edit') }}" class="text-xs text-slate-300 hover:text-amber-400 font-medium flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-800 border border-slate-700 transition-all" title="Ubah Nama, Email & Password">
                        <i class="fa-solid fa-user-gear text-slate-400"></i>
                        <span>{{ Auth::user()->name ?? 'Admin' }}</span>
                    </a>
                    <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-300 text-xs font-semibold transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-right-from-bracket"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-400 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 p-4 rounded-xl bg-blue-950/60 border border-blue-500/40 text-blue-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-blue-400 text-base"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-seal-border py-4 text-center text-xs text-slate-500">
        Seal Online Boss Tracker &copy; {{ date('Y') }} • Laravel 12 + Multi-Tenant Node Daemon
    </footer>

    @yield('scripts')
</body>
</html>

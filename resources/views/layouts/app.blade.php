<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Seal Online Boss Timer & Auto-Screening Tracker')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Rajdhani:wght@500;600;700&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 (Vector Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS (CDN) -->
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
                            bg: '#0f172a',        // Deep Navy Slate
                            card: '#1e293b',      // Surface Card
                            surface: '#334155',   // Surface Hover / Highlight
                            cyan: '#38bdf8',      // Neon Cyan Accent
                            emerald: '#22c55e',   // Running status
                            crimson: '#ef4444',   // Ready / Spawned status
                            gold: '#f59e0b',      // Paused / Warning
                            text: '#f8fafc',      // Primary Text
                            muted: '#94a3b8'      // Secondary Text
                        }
                    },
                    fontFamily: {
                        cinzel: ['Cinzel', 'serif'],
                        rajdhani: ['Rajdhani', 'sans-serif'],
                        mono: ['JetBrains Mono', 'Menlo', 'monospace'],
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(56, 189, 248, 0.08), transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(30, 41, 59, 0.4), transparent 50%);
            min-height: 100vh;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        /* PRD 3.3 Glassmorphism & Elevation */
        .glass-card {
            background: #1e293b;
            border: 1px solid rgba(51, 65, 85, 0.7);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* PRD 3.3 High-Contrast Pulsing Animation on SPAWN */
        @keyframes spawnPulse {
            0%, 100% {
                box-shadow: 0 0 15px rgba(239, 68, 68, 0.45);
                border-color: rgba(239, 68, 68, 0.85);
            }
            50% {
                box-shadow: 0 0 28px rgba(239, 68, 68, 0.85);
                border-color: rgba(239, 68, 68, 1);
            }
        }

        .card-spawn-pulse {
            animation: spawnPulse 2s infinite ease-in-out;
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(45, 18, 24, 0.95)) !important;
        }

        .progress-bar-smooth {
            transition: width 1s linear, background-color 1s ease;
        }

        .glow-cyan {
            text-shadow: 0 0 12px rgba(56, 189, 248, 0.6);
        }

        .glow-crimson {
            text-shadow: 0 0 15px rgba(239, 68, 68, 0.7);
        }

        .modal-backdrop-blur {
            background-color: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
        }
    </style>
    @yield('styles')
</head>
<body class="antialiased flex flex-col min-h-screen">

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-5 text-center text-xs text-slate-500 mt-12 bg-slate-950/40">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-sky-400"></i>
                <span class="font-bold text-slate-300 font-sans">Seal Online Boss Timer & Tracker</span>
                <span class="text-slate-600">|</span>
                <span class="text-slate-500 text-[11px]">v2.0.0 (High Precision Engine)</span>
            </div>
            <div class="flex items-center gap-4 text-[11px] font-sans">
                <span class="text-slate-400">High-Precision UTC Epoch Engine</span>
            </div>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $appSettings->app_name ?? 'NFUH DMV' }}</title>
        @if(isset($appSettings) && $appSettings->favicon_path)
            <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $appSettings->favicon_path) }}">
        @endif

        <!-- Google Fonts: Inter Tight, Outfit, and Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Alpine.js CDN -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Custom Tailwind Config -->
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter Tight', 'system-ui', 'sans-serif'],
                            display: ['Outfit', 'sans-serif'],
                        },
                        colors: {
                            brand: {
                                50: '#f4f4f5',
                                100: '#e4e4e7',
                                200: '#d4d4d8',
                                300: '#a1a1aa',
                                400: '#71717a',
                                500: '#27272a',
                                600: '#18181b',
                                700: '#0f0f11',
                                800: '#000000',
                                900: '#000000',
                            },
                            darkBg: '#09090b',
                            darkCard: '#18181b',
                            darkBorder: '#27272a',
                        }
                    }
                }
            }
        </script>

        <!-- Block to prevent dark mode flash -->
        @include('partials.theme-script')

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased bg-zinc-50 dark:bg-darkBg text-zinc-900 dark:text-zinc-100 min-h-screen flex flex-col justify-between transition-colors duration-200"
          x-data="{ 
              darkMode: localStorage.getItem('theme') === 'dark',
              toggleTheme() {
                  this.darkMode = !this.darkMode;
                  if (this.darkMode) {
                      document.documentElement.classList.add('dark');
                      localStorage.setItem('theme', 'dark');
                  } else {
                      document.documentElement.classList.remove('dark');
                      localStorage.setItem('theme', 'light');
                  }
              }
          }">

        <!-- Navigation Header -->
        <header class="w-full max-w-7xl mx-auto px-6 lg:px-8 py-5 flex items-center justify-between z-10">
            <!-- Logo & Brand Name -->
            <a href="/" class="flex items-center select-none group">
                <span class="text-lg font-bold tracking-tight text-zinc-950 dark:text-white">{{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}</span>
            </a>

            <!-- Right Nav Links & Theme Toggle -->
            <div class="flex items-center gap-4">
                <!-- Theme Switcher -->
                <button @click="toggleTheme()" class="p-2 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 shadow-sm transition-all cursor-pointer">
                    <!-- Sun Icon -->
                    <svg x-show="darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                    </svg>
                    <!-- Moon Icon -->
                    <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center rounded-xl text-xs font-bold px-4 py-2 bg-zinc-950 text-white hover:bg-zinc-900 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-100 shadow-sm transition-all select-none">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl text-xs font-semibold px-4 py-2 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all select-none">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl text-xs font-bold px-4 py-2 bg-zinc-950 text-white hover:bg-zinc-900 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-100 shadow-sm transition-all select-none">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <!-- Main Hero & Feature Sections -->
        <main class="w-full max-w-7xl mx-auto px-6 lg:px-8 py-10 lg:py-16 flex flex-col gap-16 lg:gap-24">
            
            <!-- Hero Container -->
            <div class="text-center max-w-3xl mx-auto flex flex-col items-center gap-6">
                <!-- Premium Badge -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-zinc-950/5 dark:bg-zinc-50/5 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 select-none">
                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Community Co-op Workspace
                </div>

                <!-- Big Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight font-display text-zinc-950 dark:text-white leading-tight">
                    Financial Solidarity, <br class="hidden sm:inline">
                    <span class="text-zinc-500 dark:text-zinc-400">Reimagined & Verified.</span>
                </h1>

                <!-- Description -->
                <p class="text-base sm:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed font-medium">
                    The official digital hub for the members of the {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }} association. Organize rotational savings cycles (Njangi), view live reciprocal refund tables, and track contributions with complete, auditable security.
                </p>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3 items-center mt-2 w-full sm:w-auto">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl text-sm font-bold px-6 py-3.5 bg-zinc-950 text-white hover:bg-zinc-900 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-100 shadow-md transition-all">
                            Enter Dashboard
                            <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl text-sm font-bold px-6 py-3.5 bg-zinc-950 text-white hover:bg-zinc-900 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-100 shadow-md transition-all">
                            Get Started
                            <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M3 12h18"></path></svg>
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl text-sm font-bold px-6 py-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 shadow-sm transition-all">
                                Request Membership
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1: Njangi Cycles -->
                <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 flex flex-col gap-4 shadow-sm hover:shadow-md dark:hover:shadow-zinc-950/20 transition-all duration-300">
                    <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold group-hover:scale-105 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17m-.002-4h.002v4m-.002 0H13"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-950 dark:text-white">Njangi Rotations</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 leading-relaxed">
                            Organize cycles with structured session targets. Track members participating, enforce strict cycle counts, and manage rotation payouts.
                        </p>
                    </div>
                </div>

                <!-- Card 2: Reciprocal Balances -->
                <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 flex flex-col gap-4 shadow-sm hover:shadow-md dark:hover:shadow-zinc-950/20 transition-all duration-300">
                    <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold group-hover:scale-105 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-950 dark:text-white">Reciprocal Standings</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 leading-relaxed">
                            Get immediate clarity on refund balances. View real-time, mutual contributions between members to track debts and outstanding settlements cleanly.
                        </p>
                    </div>
                </div>

                <!-- Card 3: Dynamic Policies -->
                <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 flex flex-col gap-4 shadow-sm hover:shadow-md dark:hover:shadow-zinc-950/20 transition-all duration-300">
                    <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold group-hover:scale-105 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-950 dark:text-white">Enforced Policy Settings</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 leading-relaxed">
                            Central settings configure application branding (custom logos, names, icons) and enforce validation rules like single-benefit constraints.
                        </p>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="w-full border-t border-zinc-200 dark:border-zinc-900 bg-white/50 dark:bg-zinc-950/40 backdrop-blur-xs select-none">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                <div class="flex items-center gap-1.5">
                    &copy; {{ date('Y') }} {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}. All rights reserved.
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-bold">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Secure Server Connected
                    </span>
                </div>
            </div>
        </footer>

    </body>
</html>

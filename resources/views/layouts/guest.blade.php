<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}</title>
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
                            display: ['Inter Tight', 'sans-serif'],
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
                                primary: 'var(--brand-primary)',
                                'primary-text': 'var(--brand-primary-text)',
                                secondary: 'var(--brand-secondary)',
                                'secondary-text': 'var(--brand-secondary-text)',
                            },
                            darkBg: '#0f1011',
                            darkCard: '#191a1b',
                            darkBorder: '#262729',
                            zinc: {
                                950: '#0f1011',
                                905: '#141516',
                                900: '#191a1b',
                                850: '#212224',
                                800: '#2a2b2d',
                                750: '#333437',
                                700: '#3d3f42',
                                650: '#484a4d',
                                600: '#55575b',
                                555: '#66686d',
                                500: '#787a80',
                                450: '#8c8f95',
                                400: '#a3a6ac',
                                350: '#b4b7bd',
                                300: '#c2c5cb',
                                255: '#d0d3d9',
                                200: '#e4e6eb',
                                100: '#f0f2f5',
                                50: '#f7f8fa',
                            },
                        }
                    }
                }
            }
        </script>

        <!-- Dynamic Theme Engine -->
        @include('partials.theme-styles')

        <!-- Block to prevent dark mode flash -->
        @include('partials.theme-script')
        
        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased bg-zinc-50 dark:bg-[#090a0b] text-zinc-900 dark:text-zinc-100 min-h-screen flex items-center justify-center p-4 relative overflow-x-hidden transition-colors duration-300">

        <!-- Modern Blurred Ambient Mesh & Glow System (Linear / Vercel Style) -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10 select-none">
            <!-- Top Hero Radial Aura -->
            <div 
                class="absolute -top-[12%] left-1/2 -translate-x-1/2 w-[130%] max-w-[1200px] h-[680px] opacity-45 dark:opacity-65 blur-[110px]"
                style="background: radial-gradient(ellipse 65% 55% at 50% 18%, rgba(var(--brand-primary-rgb), 0.42), rgba(var(--brand-secondary-rgb), 0.22) 48%, transparent 75%);"
            ></div>

            <!-- Floating Brand Primary Orb (Left) -->
            <div 
                class="absolute top-[12%] -left-[10%] w-[540px] h-[540px] rounded-full blur-[140px] opacity-25 dark:opacity-35"
                style="background: radial-gradient(circle, rgba(var(--brand-primary-rgb), 0.35) 0%, transparent 70%);"
            ></div>

            <!-- Floating Brand Secondary Orb (Right) -->
            <div 
                class="absolute top-[28%] -right-[10%] w-[480px] h-[480px] rounded-full blur-[140px] opacity-20 dark:opacity-30"
                style="background: radial-gradient(circle, rgba(var(--brand-secondary-rgb), 0.35) 0%, transparent 70%);"
            ></div>

            <!-- Bottom Center Subtle Mesh Glow -->
            <div 
                class="absolute bottom-[5%] left-[25%] w-[620px] h-[460px] rounded-full blur-[160px] opacity-15 dark:opacity-25"
                style="background: radial-gradient(circle, rgba(var(--brand-primary-rgb), 0.25) 0%, rgba(var(--brand-secondary-rgb), 0.15) 50%, transparent 70%);"
            ></div>

            <!-- Subtle Radial Dot Matrix Grid Pattern Overlay -->
            <div 
                class="absolute inset-0 opacity-15 dark:opacity-25"
                style="background-image: radial-gradient(rgba(var(--brand-primary-rgb), 0.22) 1px, transparent 1px); background-size: 28px 28px; mask-image: radial-gradient(ellipse 70% 55% at 50% 30%, #000 20%, transparent 80%); -webkit-mask-image: radial-gradient(ellipse 70% 55% at 50% 30%, #000 20%, transparent 80%);"
            ></div>
        </div>
        
        <!-- Alpine-powered Theme Toggler -->
        <div x-data="{ 
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
        }" class="absolute top-4 right-4 z-50">
            <button @click="toggleTheme()" class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 shadow-sm transition-all select-none focus:outline-none cursor-pointer">
                <!-- Sun Icon -->
                <svg x-show="darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                <!-- Moon Icon -->
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>

        <div class="w-full max-w-md bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl shadow-2xl dark:shadow-black/40 p-6 md:p-8 flex flex-col gap-6 relative overflow-hidden transition-all duration-300 max-h-[90vh] overflow-y-auto">
            <!-- Branding Header -->
            <div class="flex flex-col items-center text-center">
                <a href="/" class="flex flex-col items-center group">
                    <span class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}</span>
                </a>
            </div>

            <!-- Page Slot Content -->
            <div class="w-full">
                {{ $slot }}
            </div>
        </div>

    </body>
</html>

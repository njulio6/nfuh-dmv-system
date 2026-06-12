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
    <body class="font-sans antialiased bg-zinc-50 dark:bg-darkBg text-zinc-900 dark:text-zinc-100 min-h-screen flex items-center justify-center p-4 relative transition-colors duration-200">
        
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

        <div class="w-full max-w-md bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/85 rounded-2xl shadow-xl dark:shadow-zinc-950/40 p-6 md:p-8 flex flex-col gap-6 relative overflow-hidden transition-all duration-300">
            <!-- Branding Header -->
            <div class="flex flex-col items-center text-center">
                <a href="/" class="flex flex-col items-center select-none group">
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

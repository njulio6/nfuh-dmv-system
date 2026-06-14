<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Setup Wizard | Portal Installation</title>

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
    <body class="font-sans antialiased bg-zinc-50 dark:bg-darkBg text-zinc-900 dark:text-zinc-100 min-h-screen flex items-center justify-center p-4 relative transition-colors duration-200"
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
        
        <!-- Alpine-powered Theme Toggler -->
        <div class="absolute top-4 right-4 z-50">
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

        <div class="w-full max-w-2xl bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/85 rounded-2xl shadow-xl dark:shadow-zinc-950/40 p-6 md:p-8 flex flex-col gap-6 relative overflow-hidden transition-all duration-300">
            <!-- Branding Header -->
            <div class="flex flex-col items-center text-center">
                <span class="text-xs font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Installation Setup Wizard</span>
                <h1 class="text-2xl font-black tracking-tight text-zinc-950 dark:text-white mt-1">Portal Configuration</h1>
            </div>

            <!-- Steps Progress Bar -->
            @php
                $steps = [
                    'install.welcome' => 'Start',
                    'install.requirements' => 'Server Check',
                    'install.permissions' => 'Permissions',
                    'install.database' => 'Database',
                    'install.admin' => 'Admin User',
                    'install.complete' => 'Finish'
                ];
                $currentRouteName = Route::currentRouteName();
                $keys = array_keys($steps);
                $currentIndex = array_search($currentRouteName, $keys);
                if ($currentIndex === false) $currentIndex = 0;
            @endphp
            <div class="w-full flex items-center justify-between relative px-2 mb-2">
                <!-- Line background -->
                <div class="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-0.5 bg-zinc-100 dark:bg-zinc-800 -z-10"></div>
                <!-- Line active progress -->
                @php
                    $progressWidth = count($steps) > 1 ? ($currentIndex / (count($steps) - 1)) * 100 : 0;
                @endphp
                <div class="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-0.5 bg-zinc-900 dark:bg-zinc-100 -z-10 transition-all duration-300" style="width: calc({{ $progressWidth }}% - 2rem)"></div>

                @foreach($steps as $route => $label)
                    @php
                        $index = array_search($route, $keys);
                        $isCompleted = $index < $currentIndex;
                        $isActive = $index === $currentIndex;
                    @endphp
                    <div class="flex flex-col items-center gap-1.5 relative">
                        <div class="size-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300
                            {{ $isCompleted ? 'bg-zinc-950 dark:bg-zinc-50 border-zinc-950 dark:border-zinc-50 text-white dark:text-zinc-950 shadow-sm' : '' }}
                            {{ $isActive ? 'bg-white dark:bg-zinc-900 border-zinc-950 dark:border-zinc-100 text-zinc-950 dark:text-zinc-100 ring-2 ring-zinc-950/20 dark:ring-zinc-100/20 shadow-sm scale-110' : '' }}
                            {{ !$isCompleted && !$isActive ? 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-400 dark:text-zinc-600' : '' }}
                        ">
                            @if($isCompleted)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <span class="text-[10px] font-bold tracking-tight uppercase 
                            {{ $isActive ? 'text-zinc-950 dark:text-white font-extrabold' : 'text-zinc-400 dark:text-zinc-600' }}
                        ">{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <hr class="border-zinc-100 dark:border-zinc-850/60 my-1">

            <!-- Page Slot Content -->
            <div class="w-full">
                {{ $slot }}
            </div>
        </div>

    </body>
</html>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meeting Check-In • {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}</title>

    @if(isset($appSettings) && $appSettings->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $appSettings->favicon_path) }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Dynamic Theme Engine -->
    @include('partials.theme-styles')
    @include('partials.theme-script')

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
                            primary: 'var(--brand-primary)',
                            'primary-text': 'var(--brand-primary-text)',
                            secondary: 'var(--brand-secondary)',
                            'secondary-text': 'var(--brand-secondary-text)',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
<body class="font-sans antialiased bg-zinc-50 dark:bg-[#090a0b] text-zinc-900 dark:text-zinc-100 min-h-screen py-5 sm:py-8 pb-32 px-3 sm:px-6 relative overflow-x-hidden transition-colors duration-300">

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

    <!-- Theme Toggler (Top Right) -->
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
    }" class="fixed top-4 right-4 z-50">
        <button 
            type="button"
            @click="toggleTheme()" 
            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 shadow-sm transition-all select-none cursor-pointer"
            title="Toggle Light/Dark Theme"
        >
            <i x-show="darkMode" data-lucide="sun" class="w-4 h-4 text-amber-400"></i>
            <i x-show="!darkMode" data-lucide="moon" class="w-4 h-4 text-zinc-600" x-cloak></i>
        </button>
    </div>

    <!-- Main Container -->
    <div class="max-w-xl mx-auto">

        <!-- Top Header & Branding -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center size-12 rounded-2xl bg-brand-primary text-brand-primary-text mb-3 shadow-md">
                @if(isset($appSettings) && $appSettings->logo_light_path && $appSettings->logo_dark_path)
                    <img src="{{ asset('storage/' . $appSettings->logo_light_path) }}" alt="Logo" class="w-8 h-8 object-contain dark:hidden">
                    <img src="{{ asset('storage/' . $appSettings->logo_dark_path) }}" alt="Logo" class="w-8 h-8 object-contain hidden dark:block">
                @elseif(isset($appSettings) && $appSettings->logo_light_path)
                    <img src="{{ asset('storage/' . $appSettings->logo_light_path) }}" alt="Logo" class="w-8 h-8 object-contain">
                @else
                    <i data-lucide="qr-code" class="w-6 h-6"></i>
                @endif
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV System' }}
            </p>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight mt-1">
                {{ $session->title ?: 'Session #' . $session->session_number }}
            </h1>

            <div class="flex items-center justify-center gap-2 mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-zinc-400"></i>
                <span>{{ $session->session_date ? $session->session_date->format('l, F j, Y') : 'Session Meeting' }}</span>
            </div>

            <!-- Time Window Status Badge -->
            <div class="mt-3 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold border shadow-2xs
                @if($windowStatus['status'] === 'open') bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400
                @elseif($windowStatus['status'] === 'not_started') bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400
                @else bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 @endif">
                <span class="w-2 h-2 rounded-full @if($windowStatus['status'] === 'open') bg-emerald-500 animate-pulse @elseif($windowStatus['status'] === 'not_started') bg-amber-500 @else bg-rose-500 @endif"></span>
                <span>
                    @if($windowStatus['status'] === 'open')
                        Check-in Open • Closes at {{ $windowStatus['closes_at']->format('g:i A T') }}
                    @elseif($windowStatus['status'] === 'not_started')
                        Check-in Opens at {{ $windowStatus['opens_at']->format('g:i A T') }}
                    @else
                        Check-in Closed at {{ $windowStatus['closes_at']->format('g:i A T') }}
                    @endif
                </span>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-4 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-start gap-3 text-sm shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                <div class="flex-1 font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-4 p-4 rounded-2xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 text-sky-800 dark:text-sky-300 flex items-start gap-3 text-sm shadow-sm">
                <i data-lucide="info" class="w-5 h-5 shrink-0 text-sky-600 dark:text-sky-400 mt-0.5"></i>
                <div class="flex-1 font-semibold">{{ session('info') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 flex items-start gap-3 text-sm shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5"></i>
                <div class="flex-1 font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if(session('guest_success'))
            <div class="mb-4 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-start gap-3 text-sm shadow-sm">
                <i data-lucide="party-popper" class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                <div class="flex-1 font-semibold">{{ session('guest_success') }}</div>
            </div>
        @endif

        <!-- Card Container with Dual Tabs -->
        <div 
            class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 rounded-3xl shadow-2xl relative" 
            x-data="{ tab: '{{ session('guest_success') || $errors->has('guest_name') ? 'guest' : 'member' }}' }"
        >
            <!-- Tab Navigation Header -->
            <div class="grid grid-cols-2 border-b border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/70 dark:bg-zinc-900/50 p-1.5 sm:p-2 gap-1.5 sm:gap-2 rounded-t-3xl backdrop-blur-xs">
                <button 
                    type="button" 
                    @click="tab = 'member'"
                    :class="tab === 'member' ? 'bg-brand-primary text-brand-primary-text shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-semibold'"
                    class="py-2.5 sm:py-3 px-2 sm:px-3 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all cursor-pointer select-none whitespace-nowrap"
                >
                    <i data-lucide="user-check" class="w-4 h-4 shrink-0"></i>
                    <span>Member Check-In</span>
                </button>
                <button 
                    type="button" 
                    @click="tab = 'guest'"
                    :class="tab === 'guest' ? 'bg-brand-primary text-brand-primary-text shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-semibold'"
                    class="py-2.5 sm:py-3 px-2 sm:px-3 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all cursor-pointer select-none whitespace-nowrap"
                >
                    <i data-lucide="user-plus" class="w-4 h-4 shrink-0"></i>
                    <span>Guest Check-In</span>
                </button>
            </div>

            <!-- TAB 1: MEMBER CHECK-IN -->
            <div x-show="tab === 'member'" class="p-4 sm:p-8 space-y-6">
                @guest
                    <!-- Unauthenticated View -->
                    <div class="text-center py-6 space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-3xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shadow-inner">
                            <i data-lucide="shield-check" class="w-8 h-8 text-brand-primary"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Sign In to Check In</h3>
                            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1.5 max-w-sm mx-auto leading-relaxed">
                                Please sign in to verify your official presence for roll-call.
                            </p>
                        </div>
                        <div class="pt-2">
                            <a 
                                href="{{ route('login', ['redirect' => route('attendance.checkin.show', $session->qr_token)]) }}" 
                                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-brand-primary hover:opacity-95 text-brand-primary-text font-bold text-sm shadow-lg shadow-brand-primary/20 transition-all w-full sm:w-auto"
                            >
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Sign In to Continue</span>
                            </a>
                        </div>
                        <div class="text-xs text-zinc-400 dark:text-zinc-500 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            Visiting or not a member? Click <button type="button" @click="tab = 'guest'" class="text-brand-primary font-bold underline cursor-pointer">Guest Check-In</button> above.
                        </div>
                    </div>
                @else
                    @if($member)
                        <!-- Authenticated Member Recognized -->
                        <div class="p-3.5 sm:p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40 flex items-center gap-3 sm:gap-4">
                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-extrabold flex items-center justify-center text-sm sm:text-base shrink-0 border border-emerald-200 dark:border-emerald-800">
                                {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white truncate">
                                        {{ $member->name }}
                                    </h3>
                                    @if($member->traditional_title)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800 shrink-0">
                                            {{ $member->traditional_title }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span class="whitespace-nowrap shrink-0">ID: <strong class="text-zinc-700 dark:text-zinc-300 font-mono font-bold">{{ $member->member_code }}</strong></span>
                                    <span class="text-zinc-300 dark:text-zinc-600 hidden sm:inline">•</span>
                                    <span class="truncate text-zinc-400 block sm:inline max-w-[200px] sm:max-w-none">{{ Auth::user()->email }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Check-in Status Action Box -->
                        @if($isAlreadyCheckedIn)
                            <!-- Already Checked In -->
                            <div class="p-6 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-center space-y-3">
                                <div class="w-12 h-12 rounded-full bg-emerald-600 text-white mx-auto flex items-center justify-center shadow-md">
                                    <i data-lucide="check" class="w-6 h-6 stroke-[3]"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-extrabold text-emerald-900 dark:text-emerald-200">You Are Checked In!</h4>
                                    <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                                        Recorded as <strong class="uppercase tracking-wide">Present</strong>
                                        @if($existingAttendance && $existingAttendance->check_in_at)
                                            at {{ $existingAttendance->check_in_at->setTimezone($session->check_in_timezone)->format('g:i A T') }}
                                        @endif
                                    </p>
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 pt-2">
                                    Your roll-call presence is securely verified for this meeting.
                                </p>
                            </div>
                        @elseif(!$isEnrolledInCycle)
                            <!-- Not Enrolled in This Cycle Warning -->
                            <div class="p-6 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-center space-y-3">
                                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 mx-auto flex items-center justify-center">
                                    <i data-lucide="shield-alert" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                                        Not Enrolled in This Njangi Cycle
                                    </h4>
                                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1 max-w-sm mx-auto leading-relaxed">
                                        Check-in for this meeting is restricted to active participants of <strong>{{ $session->cycle?->name ?? 'this cycle' }}</strong>. Your member account is active, but you are not registered in this specific cycle.
                                    </p>
                                </div>
                                <div class="pt-2">
                                    <button 
                                        type="button" 
                                        @click="tab = 'guest'" 
                                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-brand-primary hover:opacity-95 text-brand-primary-text font-bold text-xs shadow-xs transition-all cursor-pointer"
                                    >
                                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                        <span>Check In as Visiting Guest &rarr;</span>
                                    </button>
                                </div>
                            </div>
                        @elseif(!$windowStatus['is_open'])
                            <!-- Window Closed / Not Started -->
                            <div class="p-6 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 text-center space-y-3">
                                <div class="w-12 h-12 rounded-full @if($windowStatus['status'] === 'not_started') bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 @else bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 @endif mx-auto flex items-center justify-center">
                                    <i data-lucide="clock" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-zinc-900 dark:text-white">
                                        @if($windowStatus['status'] === 'not_started')
                                            Check-In Not Yet Open
                                        @else
                                            Self Check-In Window Closed
                                        @endif
                                    </h4>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-1 max-w-sm mx-auto leading-relaxed font-medium">
                                        @if($windowStatus['status'] === 'not_started')
                                            Check-in is not yet open. Check-in begins on the scheduled meeting date at <strong>{{ $windowStatus['opens_at']->format('g:i A T') }}</strong> (Eastern Time).
                                        @else
                                            Check-in is closed for this session (Cutoff was <strong>{{ $windowStatus['closes_at']->format('g:i A T') }}</strong>). If you attended this meeting, please see an officer at the Attendance Desk to have your attendance recorded.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @else
                            <!-- Ready to Check In -->
                            <div class="space-y-4">
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 text-center">
                                    By tapping below, your official attendance for <strong>{{ $session->title }}</strong> will be recorded as <em>Present</em>.
                                </div>

                                <form action="{{ route('attendance.checkin.member', $session->qr_token) }}" method="POST">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-full py-4 px-6 rounded-2xl bg-brand-primary hover:opacity-95 active:scale-[0.99] text-brand-primary-text font-extrabold text-sm sm:text-base flex items-center justify-center gap-3 shadow-lg shadow-brand-primary/20 transition-all cursor-pointer"
                                    >
                                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                                        <span>Tap to Check In as Present</span>
                                    </button>
                                </form>
                            </div>
                        @endif

                    @else
                        <!-- Logged in user has no member profile -->
                        <div class="p-6 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-center space-y-3">
                            <i data-lucide="alert-circle" class="w-8 h-8 text-amber-600 mx-auto"></i>
                            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">No Member Profile Linked</h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                Your account ({{ Auth::user()->email }}) is not linked to an active NFUH member profile. Please contact an officer or check in as a guest.
                            </p>
                            <button type="button" @click="tab = 'guest'" class="mt-2 text-xs font-semibold text-brand-primary underline cursor-pointer">
                                Continue as Guest Attendee &rarr;
                            </button>
                        </div>
                    @endif
                @endguest
            </div>

            <!-- TAB 2: GUEST CHECK-IN -->
            <div x-show="tab === 'guest'" class="p-6 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Welcome, Guest Attendee!</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        We are honored to have you join our session. Please fill in your details below so we can acknowledge your presence.
                    </p>
                </div>

                @if(!$windowStatus['is_open'])
                    @if($windowStatus['status'] === 'not_started')
                        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2.5">
                            <i data-lucide="clock" class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5"></i>
                            <div>
                                <strong>Check-In Not Yet Open:</strong> Guest registration will open on the scheduled meeting date at {{ $windowStatus['opens_at']->format('g:i A T') }} (Eastern Time).
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs flex items-start gap-2.5">
                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5"></i>
                            <div>
                                <strong>Check-In Closed:</strong> The self check-in window closed at {{ $windowStatus['closes_at']->format('g:i A T') }}. Please speak with an officer at the Attendance Desk to record your guest attendance.
                            </div>
                        </div>
                    @endif
                @endif

                <form action="{{ route('attendance.checkin.guest', $session->qr_token) }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Your Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="guest_name" 
                            value="{{ old('guest_name') }}"
                            placeholder="e.g. John Fru Ndi" 
                            required 
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-xs font-medium focus:outline-none focus:border-brand-primary transition-all"
                        >
                        @error('guest_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Phone Number <span class="text-zinc-400 font-normal">(Optional)</span>
                            </label>
                            <input 
                                type="tel" 
                                name="guest_phone" 
                                value="{{ old('guest_phone') }}"
                                placeholder="e.g. (240) 555-0199" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-xs font-medium focus:outline-none focus:border-brand-primary transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Email Address <span class="text-zinc-400 font-normal">(Optional)</span>
                            </label>
                            <input 
                                type="email" 
                                name="guest_email" 
                                value="{{ old('guest_email') }}"
                                placeholder="e.g. john@example.com" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-xs font-medium focus:outline-none focus:border-brand-primary transition-all"
                            >
                        </div>
                    </div>

                    @php
                        $membersJson = $activeMembers->map(function($m) {
                            return [
                                'id' => (string) $m->id,
                                'name' => trim($m->first_name . ' ' . $m->last_name),
                                'code' => $m->member_code,
                                'label' => trim($m->first_name . ' ' . $m->last_name) . ' (' . $m->member_code . ')',
                            ];
                        })->values();
                    @endphp

                    <div 
                        x-data="{
                            open: false,
                            search: '',
                            selectedId: '{{ old('invited_by_member_id', '') }}',
                            members: {{ Js::from($membersJson) }},
                            get selectedMember() {
                                return this.members.find(m => String(m.id) === String(this.selectedId)) || null;
                            },
                            get filteredMembers() {
                                if (!this.search.trim()) return this.members;
                                const q = this.search.toLowerCase().trim();
                                return this.members.filter(m => 
                                    m.name.toLowerCase().includes(q) || 
                                    m.code.toLowerCase().includes(q)
                                );
                            },
                            select(id) {
                                this.selectedId = id;
                                this.open = false;
                                this.search = '';
                            },
                            clear() {
                                this.selectedId = '';
                                this.open = false;
                                this.search = '';
                            }
                        }"
                        @click.outside="open = false"
                        class="flex flex-col relative w-full"
                    >
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Who Invited You? <span class="text-zinc-400 font-normal">(Optional)</span>
                        </label>

                        <input type="hidden" name="invited_by_member_id" :value="selectedId">

                        <!-- Trigger Button -->
                        <button 
                            type="button" 
                            @click="open = !open; if(open) $nextTick(() => $refs.memberSearchInput?.focus())"
                            class="w-full h-11 px-3.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-xs font-medium flex items-center justify-between gap-2 focus:outline-none focus:border-brand-primary transition-all cursor-pointer select-none text-left shadow-2xs"
                            :class="open ? 'border-brand-primary ring-1 ring-brand-primary/20' : ''"
                        >
                            <span class="truncate" :class="selectedMember ? 'font-semibold text-zinc-900 dark:text-white' : 'text-zinc-400 dark:text-zinc-500'" x-text="selectedMember ? selectedMember.label : '-- Select Member (or leave blank) --'"></span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span 
                                    x-show="selectedId" 
                                    @click.stop="clear()" 
                                    class="p-0.5 rounded text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                                    title="Clear selection"
                                >
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <!-- Dropdown Popover with Search Field -->
                        <div 
                            x-show="open" 
                            x-cloak 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute top-full left-0 right-0 mt-1.5 z-50 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl overflow-hidden p-2 flex flex-col gap-2"
                        >
                            <!-- Search Input Field -->
                            <div class="relative w-full">
                                <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                <input 
                                    x-ref="memberSearchInput"
                                    type="text" 
                                    x-model="search" 
                                    placeholder="Search by name or code..." 
                                    class="w-full pl-8 pr-3 py-2 text-xs rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:border-brand-primary placeholder:text-zinc-400"
                                    @keydown.escape.prevent="open = false"
                                >
                            </div>

                            <!-- Options List -->
                            <div class="max-h-56 overflow-y-auto flex flex-col gap-0.5 pr-1">
                                <!-- Option to leave blank -->
                                <button 
                                    type="button" 
                                    @click="clear()" 
                                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors cursor-pointer text-left"
                                    :class="!selectedId ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/60'"
                                >
                                    <span>-- None / Leave Blank --</span>
                                    <svg x-show="!selectedId" class="w-3.5 h-3.5 text-brand-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>

                                <!-- Member list -->
                                <template x-for="m in filteredMembers" :key="m.id">
                                    <button 
                                        type="button" 
                                        @click="select(m.id)" 
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors cursor-pointer text-left"
                                        :class="String(selectedId) === String(m.id) ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60'"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span x-text="m.name" class="font-medium truncate"></span>
                                            <span x-text="m.code" class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 font-mono"></span>
                                        </div>
                                        <svg x-show="String(selectedId) === String(m.id)" class="w-3.5 h-3.5 text-brand-primary shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </button>
                                </template>

                                <!-- Empty state when search matches nothing -->
                                <div x-show="filteredMembers.length === 0" class="py-4 text-center text-xs text-zinc-400">
                                    No members found matching "<span x-text="search" class="font-bold"></span>"
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="submit" 
                            @if(!$windowStatus['is_open']) disabled @endif
                            class="w-full py-3.5 px-6 rounded-2xl bg-brand-primary hover:opacity-95 text-brand-primary-text font-bold text-xs sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-brand-primary/20 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                        >
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span>Register Guest Attendance</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-zinc-400 dark:text-zinc-500">
            {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV System' }} • Meeting Roll-Call Desk
        </div>

    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>

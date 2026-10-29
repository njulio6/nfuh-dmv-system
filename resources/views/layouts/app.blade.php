<!DOCTYPE html>
<html lang="en" class="h-full w-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'NFUH DMV System') }}</title>
    @if(isset($appSettings) && $appSettings->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $appSettings->favicon_path) }}">
    @endif

    <!-- Google Fonts: Inter Tight, Outfit, and Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js Collapse Plugin CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- Dynamic Theme Engine -->
    @include('partials.theme-styles')

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
                            50: '#f4f4f5', // Zinc-100 equivalent
                            100: '#e4e4e7',
                            200: '#d4d4d8',
                            300: '#a1a1aa',
                            400: '#71717a',
                            500: '#27272a', // Zinc-800
                            600: '#18181b', // Zinc-900 (Monochrome primary)
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

    <!-- Block to prevent dark mode flash -->
    @include('partials.theme-script')

    <!-- Block to prevent sidebar layout flash -->
    <script>
        (function() {
            try {
                var collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                var style = document.createElement('style');
                style.id = 'initial-sidebar-style';
                if (collapsed) {
                    style.innerHTML = `
                        #desktop-sidebar { width: 48px !important; }
                        #desktop-sidebar [x-show*="sidebarCollapsed"] { display: none !important; }
                        #desktop-sidebar .sidebar-logo-container { justify-content: center !important; padding: 0 !important; }
                        #desktop-sidebar nav a { width: 2rem !important; height: 2rem !important; padding: 0.5rem !important; justify-content: center !important; }
                        #desktop-sidebar .sidebar-user-card { justify-content: center !important; padding: 0 !important; }
                    `;
                } else {
                    style.innerHTML = `
                        #desktop-sidebar { width: 256px !important; }
                        #desktop-sidebar nav a { height: 2rem !important; padding-left: 0.5rem !important; padding-right: 0.5rem !important; width: 100% !important; }
                        #desktop-sidebar .sidebar-logo-container { gap: 0.5rem !important; padding: 0.5rem !important; }
                        #desktop-sidebar .sidebar-user-card { gap: 0.5rem !important; padding: 0.5rem !important; }
                    `;
                }
                document.head.appendChild(style);
            } catch (e) {}
        })();
    </script>

    <style>
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
        }

        [x-cloak] { display: none !important; }
        
        /* Smooth reports dropdown collapse transition */
        .dropdown-collapse {
            display: grid;
            grid-template-rows: 0fr;
            overflow: hidden;
            transition: grid-template-rows 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease-out, margin-top 0.25s ease-out;
        }
        .dropdown-collapse.open {
            grid-template-rows: 1fr;
        }
        
        /* Prevent layout transitions during initial hydration */
        #desktop-sidebar:not(.transitions-enabled), 
        #desktop-sidebar:not(.transitions-enabled) * {
            transition: none !important;
        }
        
        /* Smooth page scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 10px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #2a2b2d;
        }
        
        /* Base input styles — no !important so Tailwind focus classes can win */
        input[type="text"], input[type="email"], input[type="number"], 
        input[type="password"], input[type="date"], input[type="tel"], 
        select, textarea {
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            color: #09090b;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.15s ease-in-out, background-color 0.15s ease-in-out;
            box-shadow: none;
        }
        .dark input[type="text"], .dark input[type="email"], .dark input[type="number"], 
        .dark input[type="password"], .dark input[type="date"], .dark input[type="tel"], 
        .dark select, .dark textarea {
            background-color: #2a2b2d;
            border-color: #3d3f42;
            color: #e4e6eb;
        }
        .dark input::placeholder, .dark textarea::placeholder {
            color: #a3a6ac;
        }
        /* Global focus reset to prevent browser default outlines and blue border flashing */
        input:focus, select:focus, textarea:focus, button:focus, [role="button"]:focus,
        input:focus-visible, select:focus-visible, textarea:focus-visible, button:focus-visible, [role="button"]:focus-visible {
            outline: none !important;
            box-shadow: none; /* No important to allow Tailwind focus rings to override */
        }

        /* legacy elements styling */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 0.75rem;
        }
        .dark .page-header {
            border-color: #262729;
        }
        .page-header h1, .page-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #09090b;
            margin: 0;
        }
        .dark .page-header h1, .dark .page-header h2 {
            color: #e4e6eb;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.72) !important;
            backdrop-filter: blur(20px) saturate(190%);
            -webkit-backdrop-filter: blur(20px) saturate(190%);
            border: 1px solid rgba(255, 255, 255, 0.85) !important;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 1px 3px 0 rgba(0, 0, 0, 0.02), inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .dark .card {
            background: #191a1b !important;
            border: 1px solid #262729 !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
        }
        
        .btn, button.btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #09090b; /* Deep Dark */
            color: #ffffff;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: all 0.15s;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover, button.btn:hover {
            background-color: #27272a;
            color: #ffffff;
        }
        .dark .btn, .dark button.btn {
            background-color: #f4f4f5; /* Pure White/Light Zinc */
            color: #09090b;
        }
        .dark .btn:hover, .dark button.btn:hover {
            background-color: #e4e4e7;
            color: #09090b;
        }
        
        .btn-secondary, a.btn-secondary {
            background-color: #f4f4f5;
            color: #27272a;
            border: 1px solid #d4d4d8;
        }
        .dark .btn-secondary, .dark a.btn-secondary {
            background-color: #2a2b2d;
            color: #e4e6eb;
            border-color: #3d3f42;
        }
        .dark .btn-secondary:hover, .dark a.btn-secondary:hover {
            background-color: #3d3f42;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        th {
            background-color: #f4f4f5;
            color: #71717a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.725rem;
            letter-spacing: 0.05em;
            padding: 0.625rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e4e4e7;
        }
        .dark th {
            background-color: #191a1b;
            color: #a3a6ac;
            border-color: #262729;
        }
        td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid #e4e4e7;
            color: #27272a;
        }
        .dark td {
            border-color: #262729;
            color: #e4e6eb;
        }
        tr:hover td {
            background-color: #f4f4f5;
        }
        .dark tr:hover td {
            background-color: #212224;
        }
        
        /* Premium custom sidebar classes (Same to same as TCG Agency) */
        .sidebar-text-primary { color: #18181b !important; }
        .dark .sidebar-text-primary { color: #e4e6eb !important; }
        .sidebar-text-secondary { color: #18181b !important; }
        .dark .sidebar-text-secondary { color: #e4e6eb !important; }
        
        @keyframes swift-up-premium {
            0% {
                opacity: 0;
                transform: scale(0.92) translateY(20px);
                filter: blur(6px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0);
            }
        }
        .animate-swift-up-premium {
            animation: swift-up-premium 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            opacity: 0;
        }
    </style>
</head>
<body 
    x-data="{ 
        sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        transitionActive: false,
        mobileSidebarOpen: false,
        darkMode: localStorage.getItem('theme') === 'dark',
        userMenuOpen: false,
        searchQuery: '',
        init() {
            this.$watch('sidebarCollapsed', val => localStorage.setItem('sidebar-collapsed', val));
            this.$watch('darkMode', val => {
                localStorage.setItem('theme', val ? 'dark' : 'light');
                if (val) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
            var initialStyle = document.getElementById('initial-sidebar-style');
            if (initialStyle) initialStyle.remove();
            setTimeout(() => this.transitionActive = true, 100);
        }
    }" 
    class="h-full w-full overflow-hidden bg-zinc-50 dark:bg-darkBg text-zinc-800 dark:text-zinc-200 font-sans antialiased transition-colors duration-200"
>
    <!-- Dynamic helper PHP properties -->
    @php
        $authUser = Auth::user();
        $member = $authUser ? \App\Support\MemberResolver::fromUser($authUser) : null;
        $roleName = $member ? $member->roles()->first()?->name : 'Warrior';
        $titleName = ($member && $member->rank) ? $member->rank->name : 'Warrior';
        $userInitials = $authUser 
            ? collect(explode(' ', $authUser->name))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('') 
            : 'US';

        $canSettings = $authUser && ($authUser->hasRole('admin') || $authUser->can('settings.manage'));
        $canRoles = $authUser && ($authUser->hasRole('admin') || $authUser->can('roles.manage'));
        $canTools = $authUser && ($authUser->hasRole('admin') || $authUser->can('tools.manage'));
        $canAdministration = $canSettings || $canRoles || $canTools;

        $isAdminUser = false;
        if ($authUser) {
            if (!$member) {
                $isAdminUser = true;
            } else {
                $adminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi'];
                $hasLegacyRole = $member->roles()->whereIn('name', $adminRoles)->exists();
                $hasSpatieRole = method_exists($authUser, 'hasAnyRole') && $authUser->hasAnyRole(['admin', 'Treasurer', 'Financial Secretary', 'Loan Committee', 'General Secretary']);
                $isAdminUser = $hasLegacyRole || $hasSpatieRole || $canAdministration;
            }
        }

        // Dynamic Route-based Page Title
        $routeName = Route::currentRouteName();
        $isMemberPortal = str_starts_with($routeName ?? '', 'member.') || ($routeName === 'dashboard' && !$isAdminUser);
        $pageTitle = 'Dashboard';
        if ($routeName) {
            if (str_starts_with($routeName, 'members')) $pageTitle = 'Members';
            elseif (str_starts_with($routeName, 'titles')) $pageTitle = 'Traditional Titles';
            elseif (str_starts_with($routeName, 'njangi-cycles')) $pageTitle = 'Cycles';
            elseif (str_starts_with($routeName, 'njangi-submissions')) $pageTitle = 'Audit Submissions';
            elseif (str_starts_with($routeName, 'njangi-contributions')) $pageTitle = 'Ledger';
            elseif (str_starts_with($routeName, 'profile')) $pageTitle = 'Profile';
            elseif ($routeName === 'member.njangi-payments') $pageTitle = 'My Njangi Payments';
            elseif ($routeName === 'member.njangi-report') $pageTitle = 'Njangi Report';
            elseif ($routeName === 'savings.index') $pageTitle = 'Savings Balances';
            elseif ($routeName === 'savings.transactions') $pageTitle = 'Savings Transactions';
            elseif ($routeName === 'savings.requests') $pageTitle = 'Deposit Requests';
            elseif ($routeName === 'savings.withdrawal-requests') $pageTitle = 'Withdrawal Requests';
            elseif ($routeName === 'member.savings') $pageTitle = 'My Savings';
            elseif ($routeName === 'member.savings.requests') $pageTitle = 'My Deposit Requests';
            elseif ($routeName === 'member.savings.withdrawals') $pageTitle = 'My Withdrawal Requests';
            elseif ($routeName === 'loans.index') $pageTitle = 'Loan Management';
            elseif ($routeName === 'loans.sub-statuses') $pageTitle = 'Loan Sub Statuses';
            elseif ($routeName === 'loans.status-list') {
                $status = request()->route('status');
                $pageTitle = match($status) {
                    'pending_guarantors' => 'Guarantor Signatures Queue',
                    'pending_committee' => 'Committee Review Queue',
                    'approved' => 'Approved Loans Queue',
                    'active' => 'Active Loan Ledger',
                    'completed' => 'Completed Loans Ledger',
                    'rejected' => 'Rejected Loans Archive',
                    'defaulted' => 'Defaulted Accounts List',
                    default => 'Custom Loans Queue'
                };
            }
            elseif ($routeName === 'loans.repayments-log') $pageTitle = 'Repayments Transaction Log';
            elseif ($routeName === 'member.loans') $pageTitle = 'My Loans';
            elseif ($routeName === 'member.loans.applications') $pageTitle = 'My Loan Applications';
            elseif (str_starts_with($routeName, 'loans.statement')) $pageTitle = 'Member Statement';
            elseif ($routeName === 'settings.edit') $pageTitle = 'System Settings';
            elseif ($routeName === 'admin.tools') $pageTitle = 'System Tools';
            elseif ($routeName === 'attendance.desk') $pageTitle = 'Attendance Desk';
            elseif ($routeName === 'admin.roles.index') $pageTitle = 'Roles & Segregation of Duties';
            elseif ($routeName === 'member.attendance') $pageTitle = 'Meeting Attendance';
        }
    @endphp

    <!-- Ambient Frosted Glass Depth Mesh (Reacts to Primary/Secondary brand palette across full system) -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10 select-none">
        <div class="absolute -top-28 -left-24 w-[38rem] h-[38rem] rounded-full bg-[rgba(var(--brand-primary-rgb),0.04)] dark:bg-[rgba(var(--brand-primary-rgb),0.10)] blur-[150px] transition-all duration-700"></div>
        <div class="absolute top-1/3 -right-28 w-[36rem] h-[36rem] rounded-full bg-[rgba(var(--brand-secondary-rgb),0.03)] dark:bg-[rgba(var(--brand-secondary-rgb),0.08)] blur-[160px] transition-all duration-700"></div>
        <div class="absolute -bottom-24 left-1/4 w-[32rem] h-[32rem] rounded-full bg-[rgba(var(--brand-primary-rgb),0.025)] dark:bg-[rgba(var(--brand-primary-rgb),0.07)] blur-[150px] transition-all duration-700"></div>
    </div>

    <div class="flex h-full w-full overflow-hidden relative">
        
        <!-- ================= SIDEBAR (DESKTOP) ================= -->
        <aside 
            id="desktop-sidebar"
            class="hidden lg:flex flex-col h-full overflow-hidden glass-surface border-r border-zinc-200/50 dark:border-white/10 shrink-0 relative z-30 font-sans shadow-xs"
            :class="[sidebarCollapsed ? 'w-12' : 'w-[256px]', transitionActive ? 'transition-all duration-300 transitions-enabled' : '']"
        >
            <!-- Sidebar Header / Logo -->
            <div class="h-14 flex items-center border-b border-zinc-200/60 dark:border-zinc-800/60 px-2 flex-shrink-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                <div class="sidebar-logo-container flex items-center w-full rounded-[10px] transition-all duration-200 cursor-pointer select-none" :class="sidebarCollapsed ? 'justify-center p-0' : 'gap-2 p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800/60'">
                    <div class="flex aspect-square size-8 items-center justify-center rounded-[10px] bg-brand-primary text-brand-primary-text flex-shrink-0 overflow-hidden shadow-2xs">
                        @if(isset($appSettings) && $appSettings->logo_light_path && $appSettings->logo_dark_path)
                            <img src="{{ asset('storage/' . $appSettings->logo_light_path) }}" alt="Logo" class="w-full h-full object-cover dark:hidden">
                            <img src="{{ asset('storage/' . $appSettings->logo_dark_path) }}" alt="Logo" class="w-full h-full object-cover hidden dark:block">
                        @elseif(isset($appSettings) && $appSettings->logo_light_path)
                            <img src="{{ asset('storage/' . $appSettings->logo_light_path) }}" alt="Logo" class="w-full h-full object-cover">
                        @elseif(isset($appSettings) && $appSettings->logo_dark_path)
                            <img src="{{ asset('storage/' . $appSettings->logo_dark_path) }}" alt="Logo" class="w-full h-full object-cover">
                        @else
                            <i data-lucide="command" class="w-4 h-4"></i>
                        @endif
                    </div>
                    <div class="grid flex-grow text-start text-sm leading-tight min-w-0" x-show="!sidebarCollapsed">
                        <span class="truncate font-semibold text-zinc-900 dark:text-white leading-none">{{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}</span>
                        <span class="truncate text-[10px] font-medium text-zinc-500 dark:text-zinc-400 leading-none mt-1">Membership Portal</span>
                    </div>
                    <i data-lucide="chevrons-up-down" class="ms-auto w-3.5 h-3.5 text-zinc-400 shrink-0" x-show="!sidebarCollapsed"></i>
                </div>
            </div>

            <!-- Scrollable Navigation Items (Exact layout/spacing as TCG Agency) -->
            <nav class="flex-1 overflow-y-auto scrollbar-hide flex flex-col gap-1">
                <!-- Group 1: Overview -->
                <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                    <div 
                        class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                        x-show="!sidebarCollapsed && (!searchQuery || 'dashboard'.includes(searchQuery.toLowerCase()))"
                    >
                        Overview
                    </div>
                    <div class="flex w-full min-w-0 flex-col gap-1">
                        <a 
                            href="{{ route('dashboard') }}" 
                            class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('dashboard') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                            :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                            title="Dashboard"
                            x-show="!searchQuery || 'dashboard'.includes(searchQuery.toLowerCase())"
                        >
                            <i data-lucide="layout-grid" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('dashboard') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                            <span x-show="!sidebarCollapsed" class="truncate">Dashboard</span>
                        </a>
                    </div>
                </div>

                @if($isAdminUser)
                    <!-- Group 2: Core Directory -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'members'.includes(searchQuery.toLowerCase()))"
                        >
                            Membership
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <a 
                                href="{{ route('members.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('members.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Members"
                                x-show="!searchQuery || 'members'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="users" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('members.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Members</span>
                            </a>

                            <a 
                                href="{{ route('titles.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('titles.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Traditional Titles"
                                x-show="!searchQuery || 'traditional titles'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="award" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('titles.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Traditional Titles</span>
                            </a>
                        </div>
                    </div>

                    <!-- Group 3: Financial & Njangi -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'cycles'.includes(searchQuery.toLowerCase()) || 'audit submissions'.includes(searchQuery.toLowerCase()) || 'ledger'.includes(searchQuery.toLowerCase()) || 'attendance'.includes(searchQuery.toLowerCase()))"
                        >
                            Njangi
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <!-- Njangi Cycles -->
                            <a 
                                href="{{ route('njangi-cycles.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('njangi-cycles.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Cycles"
                                x-show="!searchQuery || 'cycles'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="refresh-cw" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('njangi-cycles.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Cycles</span>
                            </a>

                            <!-- Submissions -->
                            <a 
                                href="{{ route('njangi-submissions.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('njangi-submissions.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Audit Submissions"
                                x-show="!searchQuery || 'audit submissions'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="receipt" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('njangi-submissions.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Audit Submissions</span>
                            </a>

                            <!-- Ledger Contributions -->
                            <a 
                                href="{{ route('njangi-contributions.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('njangi-contributions.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Ledger"
                                x-show="!searchQuery || 'ledger'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="wallet" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('njangi-contributions.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Ledger</span>
                            </a>

                            <!-- Attendance Desk -->
                            <a 
                                href="{{ route('attendance.desk') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('attendance.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Attendance Desk"
                                x-show="!searchQuery || 'attendance desk roll call'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="clipboard-check" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('attendance.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Attendance Desk</span>
                            </a>
                        </div>
                    </div>

                    <!-- Group: Savings (Admin) -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'savings'.includes(searchQuery.toLowerCase()) || 'balances'.includes(searchQuery.toLowerCase()) || 'transactions'.includes(searchQuery.toLowerCase()))"
                        >
                            Savings
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <!-- Balances -->
                            <a 
                                href="{{ route('savings.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('savings.index') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Balances"
                                x-show="!searchQuery || 'savings balances'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="piggy-bank" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('savings.index') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Balances</span>
                            </a>
                            <!-- Deposit Requests -->
                            <a 
                                href="{{ route('savings.requests') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('savings.requests') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Deposit Requests"
                                x-show="!searchQuery || 'deposit requests'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="inbox" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('savings.requests') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Deposit Requests</span>
                            </a>
                            <!-- Withdrawal Requests -->
                            <a 
                                href="{{ route('savings.withdrawal-requests') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('savings.withdrawal-requests') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Withdrawal Requests"
                                x-show="!searchQuery || 'withdrawal requests'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="arrow-up-right" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('savings.withdrawal-requests') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Withdrawals</span>
                            </a>
                            <!-- Transactions -->
                            <a 
                                href="{{ route('savings.transactions') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('savings.transactions') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Transactions"
                                x-show="!searchQuery || 'transactions'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="history" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('savings.transactions') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Transactions</span>
                            </a>
                        </div>
                    </div>

                    <!-- Group: Loans (Admin) -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'loans'.includes(searchQuery.toLowerCase()) || 'overview'.includes(searchQuery.toLowerCase()) || 'repay requests'.includes(searchQuery.toLowerCase()))"
                        >
                            Loans
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <!-- Overview -->
                            <a 
                                href="{{ route('loans.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('loans.index') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Loans Overview"
                                x-show="!searchQuery || 'loans overview'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="landmark" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('loans.index') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Overview</span>
                            </a>
                            <!-- Sub Statuses -->
                            <a 
                                href="{{ route('loans.sub-statuses') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('loans.sub-statuses') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Sub Statuses"
                                x-show="!searchQuery || 'sub statuses'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="tag" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('loans.sub-statuses') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Sub Statuses</span>
                            </a>
                            <!-- Repay Requests -->
                            @php
                                $adminPendingRepayCount = 0;
                                try {
                                    if (\Illuminate\Support\Facades\Schema::hasTable('loan_repayment_requests')) {
                                        $adminPendingRepayCount = \App\Models\LoanRepaymentRequest::where('status', 'pending')->count();
                                    }
                                } catch (\Throwable $e) {
                                    $adminPendingRepayCount = 0;
                                }
                            @endphp
                            <a 
                                href="{{ route('loans.repayment-requests') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('loans.repayment-requests') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Repay Requests"
                                x-show="!searchQuery || 'repay requests'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="inbox" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('loans.repayment-requests') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Repay Requests</span>
                                @if($adminPendingRepayCount > 0)
                                    <span x-show="!sidebarCollapsed" class="ml-auto bg-amber-500/10 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 text-[10px] font-black px-1.5 py-0.5 rounded-full border border-amber-500/20">
                                        {{ $adminPendingRepayCount }}
                                    </span>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- Group 4: Administration (Hybrid: Role 'admin' OR Module Permission) -->
                    @if($canAdministration)
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'settings roles tools duties'.includes(searchQuery.toLowerCase()))"
                        >
                            Administration
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            @if($canSettings)
                            <a 
                                href="{{ route('settings.edit') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('settings.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Settings"
                                x-show="!searchQuery || 'settings'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="settings" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('settings.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Settings</span>
                            </a>
                            @endif

                            @if($canRoles)
                            <a 
                                href="{{ route('admin.roles.index') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('admin.roles.*') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Roles & Duties"
                                x-show="!searchQuery || 'roles permissions duties rbac'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="shield-check" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('admin.roles.*') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Roles & Duties</span>
                            </a>
                            @endif

                            @if($canTools)
                            <a 
                                href="{{ route('admin.tools') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('admin.tools') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="System Tools"
                                x-show="!searchQuery || 'system tools terminal cache migration'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="terminal" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('admin.tools') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">System Tools</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                @else
                    <!-- Group: Njangi (Member) -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'njangi'.includes(searchQuery.toLowerCase()) || 'njangi payments'.includes(searchQuery.toLowerCase()) || 'njangi report'.includes(searchQuery.toLowerCase()) || 'attendance'.includes(searchQuery.toLowerCase()) || 'rsvp'.includes(searchQuery.toLowerCase()))"
                        >
                            Njangi
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <!-- My Njangi Payment -->
                            <a 
                                href="{{ route('member.njangi-payments') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.njangi-payments') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="My Njangi Payment"
                                x-show="!searchQuery || 'njangi payments'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="credit-card" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.njangi-payments') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">My Njangi Payment</span>
                            </a>
                            <!-- Njangi Report -->
                            <a 
                                href="{{ route('member.njangi-report') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.njangi-report') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Njangi Report"
                                x-show="!searchQuery || 'njangi report'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="file-text" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.njangi-report') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Njangi Report</span>
                            </a>
                            <!-- Meeting Attendance -->
                            <a 
                                href="{{ route('member.attendance') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.attendance') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Meeting Attendance"
                                x-show="!searchQuery || 'attendance checkin meeting'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="calendar-check" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.attendance') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Meeting Attendance</span>
                            </a>
                        </div>
                    </div>
                    <!-- Group: Savings (Member) -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'my savings'.includes(searchQuery.toLowerCase()) || 'deposit requests'.includes(searchQuery.toLowerCase()))"
                        >
                            Savings
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <!-- My Savings -->
                            <a 
                                href="{{ route('member.savings') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.savings') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="My Savings"
                                x-show="!searchQuery || 'my savings'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="piggy-bank" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.savings') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">My Savings</span>
                            </a>
                            <!-- Deposit Requests -->
                            <a 
                                href="{{ route('member.savings.requests') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.savings.requests') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Deposit Requests"
                                x-show="!searchQuery || 'deposit requests'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="inbox" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.savings.requests') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Deposit Requests</span>
                            </a>
                            <!-- Withdrawal Requests -->
                            <a 
                                href="{{ route('member.savings.withdrawals') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.savings.withdrawals') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Withdrawal Requests"
                                x-show="!searchQuery || 'withdrawal requests'.includes(searchQuery.toLowerCase()) || 'my withdrawals'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="arrow-up-right" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.savings.withdrawals') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Withdrawals</span>
                            </a>
                        </div>
                    </div>

                    <!-- Group: Loans (Member) -->
                    <div class="relative flex w-full min-w-0 flex-col px-2 py-1">
                        <div 
                            class="flex h-8 shrink-0 items-center rounded-[10px] px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 select-none"
                            x-show="!sidebarCollapsed && (!searchQuery || 'my loans'.includes(searchQuery.toLowerCase()) || 'applications'.includes(searchQuery.toLowerCase()) || 'repay requests'.includes(searchQuery.toLowerCase()))"
                        >
                            Loans
                        </div>
                        <div class="flex w-full min-w-0 flex-col gap-1">
                            <!-- Overview -->
                            <a 
                                href="{{ route('member.loans') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.loans') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="My Loans"
                                x-show="!searchQuery || 'my loans overview'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="trending-up" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.loans') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Overview</span>
                            </a>
                            <!-- My Applications -->
                            <a 
                                href="{{ route('member.loans.applications') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.loans.applications') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="My Applications"
                                x-show="!searchQuery || 'applications'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="inbox" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.loans.applications') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">My Applications</span>
                            </a>
                            <!-- Repay Requests -->
                            <a 
                                href="{{ route('member.loans.repayment-requests') }}" 
                                class="group flex items-center gap-2 overflow-hidden rounded-[10px] text-sm outline-none transition-all duration-200 relative select-none cursor-pointer {{ Route::is('member.loans.repayment-requests') ? 'bg-zinc-100 dark:bg-zinc-800 sidebar-text-primary font-medium' : 'sidebar-text-secondary hover:sidebar-text-primary hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}"
                                :class="sidebarCollapsed ? 'size-8 p-2 justify-center' : 'h-7 px-2 w-full'"
                                title="Repay Requests"
                                x-show="!searchQuery || 'repay requests'.includes(searchQuery.toLowerCase())"
                            >
                                <i data-lucide="history" class="w-4 h-4 shrink-0 transition-colors {{ Route::is('member.loans.repayment-requests') ? 'sidebar-text-primary' : 'sidebar-text-secondary group-hover:sidebar-text-primary' }}"></i>
                                <span x-show="!sidebarCollapsed" class="truncate">Repay Requests</span>
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            <!-- Sidebar Footer User Card -->
            <div class="p-2 border-t border-zinc-200/60 dark:border-zinc-800/60 bg-white dark:bg-zinc-900 flex-shrink-0 relative">
                <div 
                    @click="userMenuOpen = !userMenuOpen"
                    class="sidebar-user-card flex items-center w-full rounded-[10px] transition-all duration-200 cursor-pointer"
                    :class="sidebarCollapsed ? 'justify-center p-0' : 'gap-2 p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                >
                    <div class="w-8 h-8 rounded-[10px] bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {{ $userInitials }}
                    </div>
                    <div class="flex flex-col min-w-0 flex-1 text-start leading-tight" x-show="!sidebarCollapsed">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate leading-none">{{ $authUser->name ?? 'User' }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate leading-none mt-1">{{ $authUser->email ?? 'user@gmail.com' }}</span>
                    </div>
                    <i data-lucide="chevrons-up-down" class="text-zinc-400 flex-shrink-0 ms-auto size-3.5" x-show="!sidebarCollapsed"></i>
                </div>

                <!-- Account Actions Popup Menu -->
                <div 
                    x-show="userMenuOpen" 
                    x-cloak
                    @click.outside="userMenuOpen = false"
                    class="absolute bottom-[100%] mb-2 left-2 right-2 bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800 rounded-xl p-1.5 shadow-xl z-50 flex flex-col gap-1"
                >
                    <div class="px-2.5 py-1.5 text-[10px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 select-none">
                        User Account
                    </div>
                    <div class="h-[1px] bg-zinc-100 dark:bg-zinc-800 mx-1"></div>
                    <a 
                        href="{{ route('profile.edit') }}" 
                        class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                    >
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        Edit Profile
                    </a>
                    <div class="h-[1px] bg-zinc-100 dark:bg-zinc-800 mx-1"></div>
                    
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button 
                            type="submit" 
                            class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 text-left transition-colors cursor-pointer select-none"
                        >
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- ================= SIDEBAR (MOBILE DRAWER) ================= -->
        <div 
            x-show="mobileSidebarOpen" 
            x-cloak
            @keydown.escape.window="mobileSidebarOpen = false"
            class="lg:hidden fixed inset-0 z-50 flex font-sans"
            role="dialog"
            aria-modal="true"
        >
            <!-- Overlay Backing (Clicking outside closes drawer) -->
            <div 
                x-show="mobileSidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="mobileSidebarOpen = false" 
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
            ></div>

            <!-- Drawer Panel -->
            <div 
                x-show="mobileSidebarOpen"
                x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="relative flex w-full max-w-xs flex-1 flex-col glass-surface pb-4 border-r border-zinc-200/40 dark:border-white/10 shadow-2xl bg-white dark:bg-zinc-900"
            >
                <!-- Drawer Header: Brand + Close Button -->
                <div class="flex shrink-0 items-center justify-between px-4 py-3.5 mb-2 border-b border-zinc-200/60 dark:border-zinc-800/80">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-primary text-brand-primary-text font-bold shadow-xs overflow-hidden">
                            @if(isset($appSettings) && $appSettings->logo_light_path && $appSettings->logo_dark_path)
                                <img src="{{ asset('storage/' . $appSettings->logo_light_path) }}" alt="Logo" class="w-full h-full object-cover dark:hidden">
                                <img src="{{ asset('storage/' . $appSettings->logo_dark_path) }}" alt="Logo" class="w-full h-full object-cover hidden dark:block">
                            @elseif(isset($appSettings) && $appSettings->logo_light_path)
                                <img src="{{ asset('storage/' . $appSettings->logo_light_path) }}" alt="Logo" class="w-full h-full object-cover">
                            @elseif(isset($appSettings) && $appSettings->logo_dark_path)
                                <img src="{{ asset('storage/' . $appSettings->logo_dark_path) }}" alt="Logo" class="w-full h-full object-cover">
                            @else
                                <i data-lucide="command" class="w-4 h-4"></i>
                            @endif
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-bold text-sm text-zinc-950 dark:text-white leading-tight truncate">{{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }}</span>
                            <span class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 leading-none mt-0.5">Membership Portal</span>
                        </div>
                    </a>

                    <!-- Prominent In-Drawer Close Button -->
                    <button 
                        type="button"
                        @click="mobileSidebarOpen = false" 
                        class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer shrink-0"
                        title="Close Menu"
                        aria-label="Close navigation menu"
                    >
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Mobile Menu Scrollable Nav -->
                <nav class="flex-1 overflow-y-auto px-3 space-y-4">
                    <div class="space-y-1">
                        <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Overview</span>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('dashboard') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                            <i data-lucide="layout-grid" class="w-[18px] h-[18px] shrink-0"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    @if(!$isAdminUser)
                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Njangi</span>
                            <a href="{{ route('member.njangi-payments') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.njangi-payments') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="credit-card" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>My Njangi Payment</span>
                            </a>
                            <a href="{{ route('member.njangi-report') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.njangi-report') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="file-text" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Njangi Report</span>
                            </a>
                            <a href="{{ route('member.attendance') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.attendance') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="calendar-check" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Meeting Attendance</span>
                            </a>
                        </div>

                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Savings</span>
                            <a href="{{ route('member.savings') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.savings') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="piggy-bank" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>My Savings</span>
                            </a>
                            <a href="{{ route('member.savings.requests') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.savings.requests') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="inbox" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Deposit Requests</span>
                            </a>
                        </div>

                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Loans</span>
                            <a href="{{ route('member.loans') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.loans') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="trending-up" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Overview</span>
                            </a>
                            <a href="{{ route('member.loans.applications') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.loans.applications') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="inbox" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>My Applications</span>
                            </a>
                            <a href="{{ route('member.loans.repayment-requests') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('member.loans.repayment-requests') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="history" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Repay Requests</span>
                            </a>
                        </div>
                    @endif

                    @if($isAdminUser)
                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Membership</span>
                            <a href="{{ route('members.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('members.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="users" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Members</span>
                            </a>
                            <a href="{{ route('titles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('titles.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="award" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Traditional Titles</span>
                            </a>
                        </div>

                        {{-- Admin: Njangi flat section (no collapsible, same as desktop) --}}

                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Njangi</span>
                            <a href="{{ route('njangi-cycles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('njangi-cycles.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="refresh-cw" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Cycles</span>
                            </a>
                            <a href="{{ route('njangi-submissions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('njangi-submissions.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="receipt" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Audit Submissions</span>
                            </a>
                            <a href="{{ route('njangi-contributions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('njangi-contributions.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="wallet" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Ledger</span>
                            </a>
                            <a href="{{ route('attendance.desk') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('attendance.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="clipboard-check" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Attendance Desk</span>
                            </a>
                        </div>

                        {{-- Admin: Savings flat section --}}
                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Savings</span>
                            <a href="{{ route('savings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('savings.index') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="piggy-bank" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Balances</span>
                            </a>
                            <a href="{{ route('savings.requests') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('savings.requests') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="inbox" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Deposit Requests</span>
                            </a>
                            <a href="{{ route('savings.transactions') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('savings.transactions') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="history" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Transactions</span>
                            </a>
                        </div>

                        {{-- Admin: Loans flat section --}}
                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Loans</span>
                            <a href="{{ route('loans.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('loans.index') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="landmark" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Overview</span>
                            </a>
                            <a href="{{ route('loans.sub-statuses') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('loans.sub-statuses') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="tag" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Sub Statuses</span>
                            </a>
                            <a href="{{ route('loans.repayment-requests') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('loans.repayment-requests') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="inbox" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Repay Requests</span>
                            </a>
                        </div>

                        {{-- Admin: Administration flat (Hybrid: Role 'admin' OR Module Permission) --}}
                        @if($canAdministration)
                        <div class="space-y-1">
                            <span class="px-3 text-[11px] font-medium text-slate-400 dark:text-zinc-500 block mb-1.5">Administration</span>
                            @if($canSettings)
                            <a href="{{ route('settings.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('settings.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="settings" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Settings</span>
                            </a>
                            @endif
                            @if($canRoles)
                            <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('admin.roles.*') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="shield-check" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>Roles & Duties</span>
                            </a>
                            @endif
                            @if($canTools)
                            <a href="{{ route('admin.tools') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium {{ Route::is('admin.tools') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-900 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40' }}">
                                <i data-lucide="terminal" class="w-[18px] h-[18px] shrink-0"></i>
                                <span>System Tools</span>
                            </a>
                            @endif
                        </div>
                        @endif
                    @endif
                </nav>

                <!-- Mobile User Footer Info -->
                <div class="p-4 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-black dark:bg-zinc-800 text-white font-semibold shadow-sm">
                            {{ $userInitials }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-black dark:text-white leading-tight mb-0.5">{{ $authUser->name ?? 'User' }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-zinc-500 leading-none truncate block">{{ $authUser->email ?? 'user@gmail.com' }}</p>
                        </div>
                    </div>
                    
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-zinc-800 transition-colors">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        Edit Profile
                    </a>
                    
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 text-left transition-colors">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MAIN BODY CONTAINER ================= -->
        <div class="flex-grow flex flex-col h-full min-w-0 min-h-0 overflow-hidden bg-transparent transition-colors duration-300">
            
            <!-- HEADER -->
            <header class="relative h-14 sm:h-[65px] border-b border-zinc-200/50 dark:border-white/10 px-3 sm:px-6 flex items-center justify-between flex-shrink-0 z-40 glass-surface shadow-xs">
                <!-- Left: Burger Menu, Collapse Toggle & Page Title -->
                <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                    <button 
                        @click="mobileSidebarOpen = !mobileSidebarOpen" 
                        class="lg:hidden p-1.5 sm:p-2 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0 cursor-pointer"
                        aria-label="Toggle navigation menu"
                    >
                        <i data-lucide="menu" class="w-4 h-4"></i>
                    </button>
                    
                    <!-- Sidebar Collapse Button inside the Header (Panel Toggle) -->
                    <button 
                        @click="sidebarCollapsed = !sidebarCollapsed" 
                        class="hidden lg:flex p-2.5 border border-zinc-200/80 dark:border-zinc-800/80 rounded-xl text-zinc-700 dark:text-zinc-300 glass-button hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all cursor-pointer shadow-xs"
                        title="Toggle Sidebar"
                    >
                        <i data-lucide="sidebar" class="w-[15px] h-[15px]"></i>
                    </button>
                    
                    <div class="flex items-center lg:border-l lg:border-zinc-200/60 lg:dark:border-zinc-800/60 lg:pl-4 select-none min-w-0">
                        <span class="font-semibold text-xs sm:text-sm text-zinc-900 dark:text-white leading-none truncate">
                            {{ $pageTitle }}
                        </span>
                    </div>
                </div>

                <!-- Right: Search, Theme Toggle & Avatar Circle -->
                <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    
                    @if($member && $isMemberPortal)
                        <!-- Member Current Balance Header Widget (Desktop & Tablet) -->
                        <div 
                            x-data="{ showBalance: localStorage.getItem('member_balance_visible') !== 'false' }"
                            class="hidden sm:flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 h-8 sm:h-9 rounded-lg border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/90 dark:bg-zinc-950/90 backdrop-blur-md shadow-xs select-none"
                        >
                            <a 
                                href="{{ Route::has('member.savings') ? route('member.savings') : '#' }}" 
                                class="flex items-center gap-1.5 sm:gap-2 text-zinc-600 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer group"
                                title="View Current Savings Balance details"
                            >
                                <i data-lucide="wallet" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-brand-primary shrink-0"></i>
                                <span class="hidden sm:inline text-xs font-medium text-zinc-500 dark:text-zinc-400">Balance:</span>
                                <span 
                                    x-show="showBalance" 
                                    class="text-xs font-bold text-zinc-950 dark:text-white font-display tracking-tight flex items-center"
                                >
                                    <span>$</span><span>{{ number_format($member->savings_balance, 2) }}</span>
                                </span>
                                <span 
                                    x-show="!showBalance" 
                                    x-cloak 
                                    class="text-xs font-black tracking-widest text-zinc-400 dark:text-zinc-500"
                                >
                                    ••••••
                                </span>
                            </a>

                            <div class="h-3.5 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                            <button 
                                type="button" 
                                @click="showBalance = !showBalance; localStorage.setItem('member_balance_visible', showBalance)"
                                class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors cursor-pointer p-0.5 rounded flex items-center justify-center"
                                :title="showBalance ? 'Hide balance' : 'Show balance'"
                                aria-label="Toggle balance visibility"
                            >
                                <i data-lucide="eye" x-show="!showBalance" x-cloak class="w-3.5 h-3.5"></i>
                                <i data-lucide="eye-off" x-show="showBalance" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    @endif

                    <!-- Search menus input (functional) -->
                    <div class="relative w-44 md:w-60 hidden md:block">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input
                            type="text"
                            placeholder="Search menus..."
                            x-model="searchQuery"
                            class="w-full !bg-zinc-50 dark:!bg-zinc-950 !text-zinc-800 dark:!text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 !pr-12 !py-2 !rounded-lg border !border-zinc-200/80 dark:!border-zinc-800/60 !text-xs font-bold tracking-wide focus:outline-none focus:border-zinc-400 transition-colors outline-none focus:ring-1 focus:ring-zinc-400"
                        />
                        <button
                            x-show="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                        >
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                        <span
                            x-show="!searchQuery"
                            class="absolute right-3 top-1/2 -translate-y-1/2 h-5 px-1.5 border border-zinc-200 dark:border-zinc-800 rounded bg-zinc-100/50 dark:bg-zinc-900 text-[10px] font-black text-zinc-400 dark:text-zinc-500 flex items-center justify-center pointer-events-none select-none"
                        >
                            ⌘K
                        </span>
                    </div>

                    <!-- Dark Mode Toggle Button -->
                    <button 
                        id="theme-toggle-btn"
                        @click="darkMode = !darkMode"
                        class="p-2 rounded-lg border border-zinc-200/50 dark:border-zinc-800/50 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                        title="Toggle Theme"
                    >
                        <i data-lucide="sun" class="w-[15px] h-[15px]" x-show="darkMode" x-cloak></i>
                        <i data-lucide="moon" class="w-[15px] h-[15px]" x-show="!darkMode"></i>
                    </button>

                    <!-- User Initials Circle Avatar Dropdown -->
                    <div class="relative shrink-0" x-data="{ open: false }">
                        <button 
                            @click="open = !open" 
                            @click.outside="open = false"
                            class="w-8 h-8 rounded-full border border-black/10 dark:border-white/10 bg-brand-primary text-brand-primary-text font-bold text-xs flex items-center justify-center cursor-pointer select-none shadow-sm"
                        >
                            {{ $userInitials }}
                        </button>
                        
                        <div 
                            x-show="open"
                            x-cloak
                            @click.outside="open = false"
                            class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800 rounded-xl shadow-xl z-50 overflow-hidden flex flex-col"
                        >
                            <!-- Profile Info Section (Same to same as TCG Agency) -->
                            <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-brand-primary text-brand-primary-text font-bold text-sm flex items-center justify-center flex-shrink-0 select-none">
                                    {{ $userInitials }}
                                </div>
                                <div class="min-w-0 flex flex-col text-start">
                                    <p class="text-xs font-bold text-zinc-900 dark:text-white truncate leading-none">
                                        {{ $authUser->name ?? 'Admin' }}
                                    </p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate mt-1 leading-none">
                                        {{ $authUser->email ?? '' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="p-1.5 flex flex-col gap-1">
                                <a 
                                    href="{{ route('profile.edit') }}" 
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition-colors"
                                >
                                    <i data-lucide="user" class="w-3.5 h-3.5 shrink-0 text-zinc-450 dark:text-zinc-550"></i>
                                    <span>Edit Profile</span>
                                </a>
                                <div class="h-[1px] bg-zinc-100 dark:bg-zinc-800 mx-1"></div>
                                
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors cursor-pointer text-left"
                                    >
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 shrink-0 text-red-400 dark:text-red-500"></i>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <!-- MAIN WORKSPACE CONTENT -->
            <main class="flex-grow min-h-0 px-3 sm:px-5 md:px-6 py-3.5 sm:py-5 md:py-6 overflow-y-auto w-full bg-transparent">
                <!-- Toast Status Notification -->
                @if(session('success'))
                    <div 
                        x-data="{ show: true }" 
                        x-show="show" 
                        x-transition 
                        x-init="setTimeout(() => show = false, 5000)" 
                        class="mb-6 flex items-center justify-between p-3.5 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-emerald-800 dark:text-emerald-400 text-xs font-medium shadow-sm"
                    >
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-200 shrink-0">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div 
                        x-data="{ show: true }" 
                        x-show="show" 
                        x-transition 
                        x-init="setTimeout(() => show = false, 7000)" 
                        class="mb-6 flex items-center justify-between p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-medium shadow-sm animate-fadeIn"
                    >
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="alert-circle" class="w-4.5 h-4.5 text-red-650 dark:text-red-400"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-red-500 hover:text-red-700 dark:hover:text-red-200 shrink-0">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                @endif

                @yield('content')

            </main>

            <!-- Footer -->
            <footer class="flex-shrink-0 px-4 md:px-6 py-3.5 bg-white dark:bg-zinc-900 text-left text-xs text-zinc-400 dark:text-zinc-500 border-t border-zinc-200/50 dark:border-zinc-800/50 select-none">
                &copy; {{ now()->year }} {{ !empty($appSettings->app_name) ? $appSettings->app_name : config('app.name', 'NFUH DMV System') }}. All rights reserved.
            </footer>

        </div>

    </div>

    <!-- Lucide icons replacement instantiation -->
    <script>
        lucide.createIcons();
    </script>

    <!-- Global Alpine Components Registration -->
    <script>
        document.addEventListener('alpine:init', () => {
            // Register datepicker component globally
            Alpine.data('datepicker', (config) => ({
                value: config.value || '',
                name: config.name || '',
                required: config.required || false,
                open: false,
                monthOpen: false,
                yearOpen: false,
                month: null, // 0-11
                year: null,
                days: [],
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                
                init() {
                    let initialDate = new Date();
                    if (this.value) {
                        const parts = this.value.split('-');
                        if (parts.length === 3) {
                            initialDate = new Date(parts[0], parts[1] - 1, parts[2]);
                        } else {
                            initialDate = new Date(this.value);
                        }
                    }
                    this.month = initialDate.getMonth();
                    this.year = initialDate.getFullYear();
                    this.generateCalendar();
                    
                    this.$watch('value', (val) => {
                        if (val) {
                            const parts = val.split('-');
                            if (parts.length === 3) {
                                const y = parseInt(parts[0], 10);
                                const m = parseInt(parts[1], 10) - 1;
                                if (!isNaN(y) && !isNaN(m)) {
                                    this.month = m;
                                    this.year = y;
                                    this.generateCalendar();
                                }
                            }
                        }
                    });
                },
                
                get displayValue() {
                    if (!this.value) return 'Select Date';
                    const parts = this.value.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parts[0], parts[1] - 1, parts[2]);
                        if (!isNaN(d.getTime())) {
                            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        }
                    }
                    return 'Select Date';
                },
                
                getYears() {
                    const currentYear = new Date().getFullYear();
                    const initialYear = this.value ? parseInt(this.value.split('-')[0], 10) : currentYear;
                    const minYear = Math.min(currentYear - 50, initialYear - 10);
                    const maxYear = Math.max(currentYear + 10, initialYear + 10);
                    const years = [];
                    for (let y = minYear; y <= maxYear; y++) {
                        years.push(y);
                    }
                    return years;
                },
                
                generateCalendar() {
                    const firstDayOfMonth = new Date(this.year, this.month, 1).getDay();
                    const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                    const daysInPrevMonth = new Date(this.year, this.month, 0).getDate();
                    
                    const days = [];
                    
                    // Previous month trailing days
                    for (let i = firstDayOfMonth - 1; i >= 0; i--) {
                        days.push({
                            day: daysInPrevMonth - i,
                            month: this.month === 0 ? 11 : this.month - 1,
                            year: this.month === 0 ? this.year - 1 : this.year,
                            currentMonth: false
                        });
                    }
                    
                    // Current month days
                    for (let i = 1; i <= daysInMonth; i++) {
                        days.push({
                            day: i,
                            month: this.month,
                            year: this.year,
                            currentMonth: true
                        });
                    }
                    
                    // Next month leading days
                    const totalCells = 42;
                    const nextMonthDays = totalCells - days.length;
                    for (let i = 1; i <= nextMonthDays; i++) {
                        days.push({
                            day: i,
                            month: this.month === 11 ? 0 : this.month + 1,
                            year: this.month === 11 ? this.year + 1 : this.year,
                            currentMonth: false
                        });
                    }
                    
                    this.days = days;
                },
                
                prevMonth() {
                    if (this.month === 0) {
                        this.month = 11;
                        this.year--;
                    } else {
                        this.month--;
                    }
                    this.generateCalendar();
                },
                
                nextMonth() {
                    if (this.month === 11) {
                        this.month = 0;
                        this.year++;
                    } else {
                        this.month++;
                    }
                    this.generateCalendar();
                },
                
                selectDate(dateObj) {
                    const pad = (n) => String(n).padStart(2, '0');
                    this.value = `${dateObj.year}-${pad(dateObj.month + 1)}-${pad(dateObj.day)}`;
                    this.open = false;
                    this.monthOpen = false;
                    this.yearOpen = false;
                },
                
                isSelected(dateObj) {
                    if (!this.value) return false;
                    const parts = this.value.split('-');
                    if (parts.length === 3) {
                        return parseInt(parts[2], 10) === dateObj.day &&
                               (parseInt(parts[1], 10) - 1) === dateObj.month &&
                               parseInt(parts[0], 10) === dateObj.year;
                    }
                    return false;
                },
                
                isToday(dateObj) {
                    const today = new Date();
                    return today.getDate() === dateObj.day &&
                           today.getMonth() === dateObj.month &&
                           today.getFullYear() === dateObj.year;
                }
            }));

            // Register timepicker component globally
            Alpine.data('timepicker', (config) => ({
                value: config.value || '',
                name: config.name || '',
                required: config.required || false,
                open: false,
                hour12: 5,
                minute: '00',
                period: 'PM',
                hours: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                minutes: ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'],

                init() {
                    this.parseValue(this.value);
                    this.$watch('value', (val) => {
                        this.parseValue(val);
                    });
                },

                parseValue(val) {
                    if (!val) {
                        this.hour12 = 5;
                        this.minute = '00';
                        this.period = 'PM';
                        return;
                    }
                    const parts = String(val).trim().split(':');
                    if (parts.length >= 2) {
                        let h = parseInt(parts[0], 10);
                        let m = parseInt(parts[1], 10);
                        if (isNaN(h)) h = 12;
                        if (isNaN(m)) m = 0;

                        this.period = h >= 12 ? 'PM' : 'AM';
                        this.hour12 = h % 12 === 0 ? 12 : h % 12;
                        this.minute = String(m).padStart(2, '0');
                    }
                },

                updateValue() {
                    let h24;
                    if (this.period === 'PM') {
                        h24 = this.hour12 === 12 ? 12 : this.hour12 + 12;
                    } else {
                        h24 = this.hour12 === 12 ? 0 : this.hour12;
                    }
                    this.value = String(h24).padStart(2, '0') + ':' + this.minute;
                    if (this.$refs.hiddenInput) {
                        this.$refs.hiddenInput.value = this.value;
                    }
                },

                setHour(h) {
                    this.hour12 = h;
                    this.updateValue();
                },

                setMinute(m) {
                    this.minute = String(m).padStart(2, '0');
                    this.updateValue();
                },

                setPeriod(p) {
                    this.period = p;
                    this.updateValue();
                },

                setPreset(timeStr) {
                    this.parseValue(timeStr);
                    this.updateValue();
                },

                get displayValue() {
                    if (!this.hour12) return 'Select Time';
                    const hStr = String(this.hour12).padStart(2, '0');
                    return `${hStr}:${this.minute} ${this.period}`;
                }
            }));
            
            // Register customSelect component globally
            Alpine.data('customSelect', (config) => ({
                open: false,
                value: config.value || '',
                label: config.label || '',
                options: config.options || [],
                activeIndex: -1,

                init() {
                    if (!this.label) {
                        const opt = this.options.find(o => String(o.value) === String(this.value));
                        if (opt) {
                            this.label = opt.label;
                        } else if (this.value === '') {
                            this.label = config.defaultLabel || 'Select Option';
                        }
                    }
                    this.$watch('value', (val) => {
                        const opt = this.options.find(o => String(o.value) === String(val));
                        if (opt) {
                            this.label = opt.label;
                        } else if (val === '') {
                            this.label = config.defaultLabel || 'Select Option';
                        }
                        this.$refs.hiddenInput.value = val;
                        this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                },
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.activeIndex = this.options.findIndex(o => String(o.value) === String(this.value));
                        if (this.activeIndex === -1) this.activeIndex = 0;
                        this.$nextTick(() => {
                            this.scrollToActive();
                        });
                    }
                },
                close() {
                    this.open = false;
                    this.activeIndex = -1;
                },
                select(val) {
                    this.value = val;
                    this.close();
                },
                selectActive() {
                    if (this.activeIndex >= 0 && this.activeIndex < this.options.length) {
                        this.select(this.options[this.activeIndex].value);
                    }
                },
                focusNext() {
                    if (!this.open) {
                        this.toggle();
                        return;
                    }
                    this.activeIndex = (this.activeIndex + 1) % this.options.length;
                    this.scrollToActive();
                },
                focusPrev() {
                    if (!this.open) {
                        this.toggle();
                        return;
                    }
                    this.activeIndex = (this.activeIndex - 1 + this.options.length) % this.options.length;
                    this.scrollToActive();
                },
                scrollToActive() {
                    this.$nextTick(() => {
                        const activeEl = this.$refs.optionsList.children[this.activeIndex];
                        if (activeEl) {
                            activeEl.scrollIntoView({ block: 'nearest' });
                        }
                    });
                }
            }));
        });
    </script>
</body>
</html>
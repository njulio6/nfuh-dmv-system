@extends('layouts.app')

@section('content')

    @php
        // Calculate monthly submissions and contributions for current year
        $selectedYear = date('Y');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        foreach ($months as $idx => $mName) {
            $mNum = $idx + 1;
            // Contributions count in that month
            $contributionsCount = \App\Models\NjangiContribution::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $mNum)
                ->count();
            // Submissions count in that month
            $submissionsCount = \App\Models\NjangiPaymentSubmission::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $mNum)
                ->count();
                
            $chartData[] = [
                'month' => $mName,
                'contributions' => $contributionsCount,
                'submissions' => $submissionsCount,
            ];
        }
        $maxVal = max(collect($chartData)->map(fn($d) => max($d['contributions'], $d['submissions']))->toArray());
        
        // Ensure yAxisMax is a multiple of 4 to have clean, distinct whole integer steps
        if ($maxVal == 0) {
            $yAxisMax = 4;
        } else {
            $yAxisMax = (int)ceil($maxVal / 4) * 4;
        }
        
        $yAxisStep = $yAxisMax / 4;
        $yLabels = [
            $yAxisMax, 
            $yAxisStep * 3, 
            $yAxisStep * 2, 
            $yAxisStep, 
            0
        ];

        // Recent Activity: Submissions (latest 5)
        $recentSubmissions = \App\Models\NjangiPaymentSubmission::with('member')
            ->latest()
            ->take(5)
            ->get();
            
        // Recent Activity: Registered Members (latest 5)
        $recentMembers = \App\Models\Member::latest()
            ->take(5)
            ->get();
    @endphp

<div x-data="{ receiptModalOpen: false, receiptUrl: '' }" class="flex flex-col gap-6 w-full relative">
    <!-- Welcome Greeting Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <!-- Dynamic Time-of-Day Icon -->
                <span class="text-zinc-900 dark:text-zinc-100 shrink-0">
                    <i id="welcome-greeting-icon" data-lucide="sun" class="w-6 h-6 text-brand-primary"></i>
                </span>
                <h1 class="text-2xl font-bold text-zinc-950 dark:text-white tracking-tight flex items-center gap-2 font-display">
                    <span id="welcome-greeting-text">Good Afternoon</span>, {{ explode(' ', Auth::user()->name)[0] }}
                    <!-- Sparkles pulse icon -->
                    <i data-lucide="sparkles" class="w-4 h-4 text-brand-primary animate-pulse hidden sm:inline"></i>
                </h1>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Here's what's happening across the {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }} Membership System today.
            </p>
        </div>
        
        <script>
            (function() {
                try {
                    const hour = new Date().getHours();
                    let greeting = 'Good Evening';
                    let iconName = 'moon';
                    if (hour < 12) {
                        greeting = 'Good Morning';
                        iconName = 'sunrise';
                    } else if (hour < 18) {
                        greeting = 'Good Afternoon';
                        iconName = 'sun';
                    }
                    document.getElementById('welcome-greeting-text').textContent = greeting;
                    document.getElementById('welcome-greeting-icon').setAttribute('data-lucide', iconName);
                } catch(e) {}
            })();
        </script>
        
        <div class="flex items-center gap-2.5">
            <button 
                onclick="window.location.reload()"
                class="glass-button p-2 text-zinc-600 dark:text-zinc-300 rounded-xl flex items-center justify-center cursor-pointer disabled:opacity-50"
                title="Refresh Analytics"
            >
                <i data-lucide="refresh-cw" class="w-[15px] h-[15px]"></i>
            </button>
            
            @if(Auth::user() && Auth::user()->hasRole('admin'))
                <a
                    href="{{ route('admin.tools') }}"
                    class="glass-button px-4 py-2 text-zinc-700 dark:text-zinc-200 rounded-xl flex items-center justify-center cursor-pointer"
                    title="System Tools"
                >
                    <i data-lucide="terminal" class="w-[15px] h-[15px] mr-2 text-brand-primary"></i>
                    <span class="text-sm font-semibold">System Tools</span>
                </a>
            @endif

            <button 
                onclick="window.print()"
                class="px-4 py-2 bg-brand-primary text-brand-primary-text rounded-xl text-sm font-semibold hover:opacity-90 transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
            >
                <i data-lucide="download" class="w-[14px] h-[14px]"></i>
                <span>Export Report</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid (Aeroco Frosted Glass Gradient System) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Members -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group cursor-default">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2.5 shrink-0">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Total Members</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-2xl lg:text-3xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight font-display">
                    {{ \App\Models\Member::count() }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Registered DMV members
                </span>
            </div>
        </div>

        <!-- Stat 2: Active Cycles -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2 shrink-0">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Active Cycles</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-2xl lg:text-3xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight font-display">
                    {{ \App\Models\NjangiCycle::where('status', 'active')->count() }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Ongoing rotational rounds
                </span>
            </div>
        </div>

        <!-- Stat 3: Pending Payments -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2 shrink-0">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Pending Payments</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-2xl lg:text-3xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight font-display">
                    {{ \App\Models\NjangiPaymentSubmission::where('status', 'pending')->count() }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Submissions awaiting review
                </span>
            </div>
        </div>

        <!-- Stat 4: Total Volume -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2 shrink-0">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Total Contributions</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-2xl lg:text-3xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight font-display">
                    ${{ number_format(\App\Models\NjangiContribution::sum('amount'), 2) }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Aggregated ledger volume
                </span>
            </div>
        </div>
    </div>

    <!-- Overview Analytics Chart + Tabbed Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Left: Frosted Glass Chart Card -->
        <div class="lg:col-span-7 glass-surface rounded-2xl p-6 shadow-xl flex flex-col gap-3 h-full">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-200/50 dark:border-white/10 pb-4">
                <div class="flex flex-col gap-0.5">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Monthly Activity</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Contributions & payment submissions per month</p>
                </div>
                
                <div class="flex items-center gap-4 flex-shrink-0">
                    <!-- Legend -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-sm shadow-xs" style="background-color: var(--brand-primary);"></div>
                            <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-300">Contributions</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-sm shadow-xs" style="background-color: var(--brand-secondary);"></div>
                            <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-300">Submissions</span>
                        </div>
                    </div>
                    <!-- Year display -->
                    <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 glass-button rounded-lg px-2.5 py-1.5">
                        {{ $selectedYear }}
                    </div>
                </div>
            </div>

            <div class="w-full overflow-x-auto pb-1">
                <div class="relative w-full min-w-[400px] flex-1 min-h-[270px]">
                    <!-- Y-axis labels -->
                    <div class="absolute left-0 top-12 bottom-6 flex flex-col justify-between text-[11px] text-zinc-400 dark:text-zinc-500 pointer-events-none select-none z-10 w-8 text-right pr-1.5">
                        @foreach ($yLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>

                    <!-- Guide lines -->
                    <div class="absolute left-8 right-0 top-12 bottom-6 flex flex-col justify-between pointer-events-none">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="w-full border-t border-dashed border-zinc-200/50 dark:border-white/5 h-0"></div>
                        @endfor
                        <div class="w-full border-t border-zinc-200 dark:border-white/10 h-0"></div>
                    </div>

                    <!-- Bars -->
                    <div class="absolute left-8 right-0 top-12 bottom-6 flex items-end justify-between gap-0.5">
                        @foreach ($chartData as $data)
                            @php
                                $contribPct = $yAxisMax > 0 ? ($data['contributions'] / $yAxisMax) * 100 : 0;
                                $subPct = $yAxisMax > 0 ? ($data['submissions'] / $yAxisMax) * 100 : 0;
                            @endphp
                            <div class="flex-1 flex items-end justify-center gap-0.5 group relative h-full">
                                <!-- Tooltip on hover -->
                                <div class="absolute bottom-[calc(100%+6px)] left-1/2 -translate-x-1/2 z-30 pointer-events-none bg-zinc-950/90 dark:bg-zinc-900/90 backdrop-blur-md text-zinc-100 text-[11px] font-medium p-2.5 rounded-xl shadow-xl opacity-0 group-hover:opacity-100 transition-opacity duration-150 whitespace-nowrap border border-white/10">
                                    <div class="font-bold text-[11px] mb-0.5 text-white">{{ $data['month'] }} {{ $selectedYear }}</div>
                                    <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-sm" style="background-color: var(--brand-primary);"></div> Contributions: <b>{{ $data['contributions'] }}</b></div>
                                    <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-sm" style="background-color: var(--brand-secondary);"></div> Submissions: <b>{{ $data['submissions'] }}</b></div>
                                </div>

                                <!-- Contribution Bar -->
                                <div 
                                    class="w-[35%] max-w-[18px] min-w-[6px] shrink-0 rounded-t-sm transition-all duration-300 ease-out group-hover:opacity-80 shadow-xs" 
                                    style="height: {{ $contribPct }}%; min-height: {{ $data['contributions'] > 0 ? '3px' : '0' }}; background: linear-gradient(to top, var(--brand-primary), rgba(var(--brand-primary-rgb), 0.72));"
                                ></div>
                                <!-- Submission Bar -->
                                <div 
                                    class="w-[35%] max-w-[18px] min-w-[6px] shrink-0 rounded-t-sm transition-all duration-300 ease-out group-hover:opacity-80 shadow-xs" 
                                    style="height: {{ $subPct }}%; min-height: {{ $data['submissions'] > 0 ? '3px' : '0' }}; background: linear-gradient(to top, var(--brand-secondary), rgba(var(--brand-secondary-rgb), 0.65));"
                                ></div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Month labels -->
                    <div class="absolute left-8 right-0 bottom-0 h-5 flex justify-between">
                        @foreach ($chartData as $data)
                            <div class="flex-1 text-center">
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">{{ $data['month'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Frosted Glass Tabbed Activities -->
        <div 
            x-data="{ activeTab: 'submissions' }"
            class="lg:col-span-5 glass-surface rounded-2xl shadow-xl overflow-hidden flex flex-col h-full"
        >
            <!-- Tab selectors with glassy backing -->
            <div class="flex border-b border-zinc-200/50 dark:border-white/10 bg-white/40 dark:bg-white/[0.03] px-3 backdrop-blur-md">
                <button 
                    @click="activeTab = 'submissions'"
                    class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'submissions' ? 'border-brand-primary text-brand-primary font-bold' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                >
                    Submissions ({{ $recentSubmissions->count() }})
                </button>
                <button 
                    @click="activeTab = 'members'"
                    class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'members' ? 'border-brand-primary text-brand-primary font-bold' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                >
                    Members ({{ $recentMembers->count() }})
                </button>
            </div>

            <!-- Tab panels -->
            <div class="p-4 flex flex-col flex-1 overflow-y-auto">
                <!-- Submissions panel -->
                <div x-show="activeTab === 'submissions'" class="flex flex-col gap-1 w-full">
                    @if ($recentSubmissions->isEmpty())
                        <div class="flex-grow flex flex-col items-center justify-center text-center p-8 gap-3">
                            <div class="p-4 bg-white/40 dark:bg-white/5 rounded-full text-zinc-400 dark:text-zinc-500 border border-white/20">
                                <i data-lucide="inbox" class="w-8 h-8"></i>
                            </div>
                            <span class="text-xs text-zinc-500 font-medium">No submissions found.</span>
                        </div>
                    @else
                        @foreach ($recentSubmissions as $sub)
                            @php
                                $subInitials = collect(explode(' ', $sub->member->name ?? 'User'))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/60 dark:hover:bg-white/[0.05] transition-all duration-200 border-b border-zinc-100/60 dark:border-white/[0.04] last:border-0">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-white/60 dark:bg-white/10 border border-white/40 dark:border-white/10 flex items-center justify-center font-bold text-xs text-zinc-800 dark:text-zinc-200 flex-shrink-0 shadow-2xs">
                                        {{ $subInitials }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                                                {{ $sub->member->name ?? 'Unknown Member' }}
                                            </span>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">•</span>
                                            <span class="text-xs text-zinc-600 dark:text-zinc-300 font-semibold truncate">
                                                ${{ number_format($sub->amount, 2) }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium truncate leading-relaxed">
                                            {{ $sub->session->title ?? 'Session' }} • <span class="font-semibold text-brand-primary">{{ ucfirst($sub->status) }}</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0 ml-3">
                                    @if ($sub->screenshot_path)
                                        <button 
                                            @click="receiptUrl = '{{ asset('storage/' . $sub->screenshot_path) }}'; receiptModalOpen = true"
                                            class="glass-button p-1.5 rounded-lg text-zinc-600 dark:text-zinc-300 flex items-center justify-center cursor-pointer"
                                            title="View Receipt"
                                        >
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                    @endif
                                    <a 
                                        href="{{ route('njangi-submissions.index') }}"
                                        class="glass-button p-1.5 rounded-lg text-zinc-700 dark:text-zinc-200 flex items-center justify-center"
                                        title="View Submissions"
                                    >
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Members panel -->
                <div x-show="activeTab === 'members'" class="flex flex-col gap-1 w-full" x-cloak>
                    @if ($recentMembers->isEmpty())
                        <div class="flex-grow flex flex-col items-center justify-center text-center p-8 gap-3">
                            <div class="p-4 bg-white/40 dark:bg-white/5 rounded-full text-zinc-400 dark:text-zinc-500 border border-white/20">
                                <i data-lucide="users" class="w-8 h-8"></i>
                            </div>
                            <span class="text-xs text-zinc-500 font-medium">No members found.</span>
                        </div>
                    @else
                        @foreach ($recentMembers as $mb)
                            @php
                                $mbInitials = collect(explode(' ', $mb->name ?? 'User'))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/60 dark:hover:bg-white/[0.05] transition-all duration-200 border-b border-zinc-100/60 dark:border-white/[0.04] last:border-0">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-white/60 dark:bg-white/10 border border-white/40 dark:border-white/10 flex items-center justify-center font-bold text-xs text-zinc-800 dark:text-zinc-200 flex-shrink-0 shadow-2xs">
                                        {{ $mbInitials }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                                                {{ $mb->name }}
                                            </span>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">•</span>
                                            <span class="text-xs text-zinc-600 dark:text-zinc-300 font-semibold truncate">
                                                {{ $mb->member_code }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium truncate leading-relaxed">
                                            Joined {{ $mb->join_date ? \Carbon\Carbon::parse($mb->join_date)->format('M d, Y') : 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                    <a 
                                        href="{{ route('members.show', $mb->id) }}"
                                        class="glass-button p-1.5 rounded-lg text-zinc-700 dark:text-zinc-200 flex items-center justify-center"
                                        title="View Member Profile"
                                    >
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

    <!-- Frosted Glass Receipt Modal -->
    <div 
        x-show="receiptModalOpen" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 font-sans"
    >
        <!-- Backdrop -->
        <div 
            x-show="receiptModalOpen"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="receiptModalOpen = false"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-md"
        ></div>

        <!-- Modal Content with Glass Surface -->
        <div 
            x-show="receiptModalOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative max-w-2xl w-full glass-surface rounded-2xl p-5 shadow-2xl flex flex-col gap-4 max-h-[90vh]"
        >
            <div class="flex items-center justify-between border-b border-zinc-200/50 dark:border-white/10 pb-3">
                <h3 class="font-bold text-zinc-900 dark:text-white text-sm">Receipt Image Preview</h3>
                <button 
                    @click="receiptModalOpen = false"
                    class="glass-button p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 cursor-pointer flex items-center justify-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="flex-grow overflow-auto flex items-center justify-center bg-white/30 dark:bg-black/30 backdrop-blur-sm rounded-xl border border-zinc-200/40 dark:border-white/10 p-2 min-h-[300px]">
                <img :src="receiptUrl" alt="Receipt Upload" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-sm">
            </div>
        </div>
    </div>

<!-- Auto-instantiation of Lucide Icons -->
<script>
    lucide.createIcons();
</script>
@endsection

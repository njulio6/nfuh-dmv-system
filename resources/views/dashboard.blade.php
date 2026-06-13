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

<div x-data="{ receiptModalOpen: false, receiptUrl: '' }" class="flex flex-col gap-6 w-full">
    <!-- Welcome Greeting Row (Exact same as TCG Agency Admin) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <!-- Dynamic Time-of-Day Icon -->
                <span class="text-zinc-900 dark:text-zinc-100 shrink-0">
                    <i id="welcome-greeting-icon" data-lucide="sun" class="w-6 h-6"></i>
                </span>
                <h1 class="text-2xl font-bold text-zinc-950 dark:text-white tracking-tight flex items-center gap-2 font-display">
                    <span id="welcome-greeting-text">Good Afternoon</span>, {{ explode(' ', Auth::user()->name)[0] }}
                    <!-- Sparkles pulse icon -->
                    <i data-lucide="sparkles" class="w-4 h-4 text-zinc-400 dark:text-zinc-600 animate-pulse hidden sm:inline"></i>
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
                class="p-2 border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all flex items-center justify-center cursor-pointer active:scale-95 disabled:opacity-50"
                title="Refresh Analytics"
            >
                <i data-lucide="refresh-cw" class="w-[15px] h-[15px]"></i>
            </button>
            
            <button 
                onclick="window.print()"
                class="px-4 py-2 bg-zinc-950 text-white dark:bg-zinc-50 dark:text-zinc-950 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer active:scale-95"
            >
                <i data-lucide="download" class="w-[13px] h-[13px]"></i>
                <span>Export Report</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid (Compact) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Members -->
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-sm flex flex-col gap-2.5 group hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            <div class="flex items-center justify-between z-10">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-445">Total Members</span>
                <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="flex flex-col gap-0.5 mt-0.5 z-10">
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    {{ \App\Models\Member::count() }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Registered DMV members
                </span>
            </div>
        </div>

        <!-- Stat 2: Active Cycles -->
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-sm flex flex-col gap-2.5 group hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            <div class="flex items-center justify-between z-10">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Active Cycles</span>
                <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="flex flex-col gap-0.5 mt-0.5 z-10">
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    {{ \App\Models\NjangiCycle::where('status', 'active')->count() }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Ongoing rotational rounds
                </span>
            </div>
        </div>

        <!-- Stat 3: Pending Payments -->
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-sm flex flex-col gap-2.5 group hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            <div class="flex items-center justify-between z-10">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pending Payments</span>
                <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="flex flex-col gap-0.5 mt-0.5 z-10">
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    {{ \App\Models\NjangiPaymentSubmission::where('status', 'pending')->count() }}
                </span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Submissions awaiting review
                </span>
            </div>
        </div>

        <!-- Stat 4: Total Volume -->
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-sm flex flex-col gap-2.5 group hover:border-zinc-300 dark:hover:border-zinc-700 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            <div class="flex items-center justify-between z-10">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-450">Total Contributions</span>
                <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="flex flex-col gap-0.5 mt-0.5 z-10">
                <span class="text-2xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
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
        
        <!-- Left: Chart -->
        <div class="lg:col-span-7 bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-sm flex flex-col gap-3 h-full">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800/50 pb-4">
                <div class="flex flex-col gap-0.5">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Monthly Activity</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Contributions & payment submissions per month</p>
                </div>
                
                <div class="flex items-center gap-4 flex-shrink-0 select-none">
                    <!-- Legend -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-sm bg-zinc-900 dark:bg-zinc-100"></div>
                            <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Contributions</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-sm bg-zinc-300 dark:bg-zinc-600"></div>
                            <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Submissions</span>
                        </div>
                    </div>
                    <!-- Year display -->
                    <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg px-2.5 py-1.5">
                        {{ $selectedYear }}
                    </div>
                </div>
            </div>

            <div class="w-full overflow-x-auto overflow-y-hidden pb-1 select-none">
                <div class="relative w-full min-w-[400px] flex-1 min-h-[220px]">
                    <!-- Y-axis labels -->
                    <div class="absolute left-0 top-0 bottom-6 flex flex-col justify-between text-[11px] text-zinc-400 dark:text-zinc-500 pointer-events-none select-none z-10 w-8 text-right pr-1.5">
                        @foreach ($yLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>

                    <!-- Guide lines -->
                    <div class="absolute left-8 right-0 top-0 bottom-6 flex flex-col justify-between pointer-events-none">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="w-full border-t border-dashed border-zinc-200/50 dark:border-zinc-800/40 h-0"></div>
                        @endfor
                        <div class="w-full border-t border-zinc-200 dark:border-zinc-800 h-0"></div>
                    </div>

                    <!-- Bars (Exactly gap-0.5 spacing as TCG Agency) -->
                    <div class="absolute left-8 right-0 top-0 bottom-6 flex items-end justify-between gap-0.5">
                        @foreach ($chartData as $data)
                            @php
                                $contribPct = $yAxisMax > 0 ? ($data['contributions'] / $yAxisMax) * 100 : 0;
                                $subPct = $yAxisMax > 0 ? ($data['submissions'] / $yAxisMax) * 100 : 0;
                            @endphp
                            <div class="flex-1 flex items-end justify-center gap-0.5 group relative h-full">
                                <!-- Tooltip on hover -->
                                <div class="absolute bottom-[calc(100%+6px)] left-1/2 -translate-x-1/2 z-30 pointer-events-none bg-zinc-900 text-zinc-100 text-[11px] font-medium p-2 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity duration-150 whitespace-nowrap border border-zinc-800">
                                    <div class="font-bold text-[11px] mb-0.5 text-white">{{ $data['month'] }} {{ $selectedYear }}</div>
                                    <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-sm bg-zinc-100 border border-transparent"></div> Contributions: <b>{{ $data['contributions'] }}</b></div>
                                    <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-sm bg-zinc-400"></div> Submissions: <b>{{ $data['submissions'] }}</b></div>
                                </div>

                                <!-- Contribution Bar -->
                                <div class="w-[11px] bg-zinc-900 dark:bg-zinc-100 rounded-t-sm transition-all duration-500 ease-out group-hover:opacity-70" style="height: {{ $contribPct }}%; min-height: {{ $data['contributions'] > 0 ? '3px' : '0' }};"></div>
                                <!-- Submission Bar -->
                                <div class="w-[11px] bg-zinc-350 dark:bg-zinc-600 rounded-t-sm transition-all duration-500 ease-out group-hover:opacity-70" style="height: {{ $subPct }}%; min-height: {{ $data['submissions'] > 0 ? '3px' : '0' }};"></div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Month labels -->
                    <div class="absolute left-8 right-0 bottom-0 h-5 flex justify-between">
                        @foreach ($chartData as $data)
                            <div class="flex-1 text-center">
                                <span class="text-[11px] text-zinc-550 dark:text-zinc-400 select-none font-medium">{{ $data['month'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Tabbed Activities -->
        <div 
            x-data="{ activeTab: 'submissions' }"
            class="lg:col-span-5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-sm overflow-hidden flex flex-col h-full"
        >
            <!-- Tab selectors -->
            <div class="flex border-b border-zinc-100 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-zinc-900/60 px-3">
                <button 
                    @click="activeTab = 'submissions'"
                    class="px-3 py-3 text-[11px] font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'submissions' ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                >
                    Submissions ({{ $recentSubmissions->count() }})
                </button>
                <button 
                    @click="activeTab = 'members'"
                    class="px-3 py-3 text-[11px] font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'members' ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
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
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-full text-zinc-300 dark:text-zinc-700">
                                <i data-lucide="inbox" class="w-8 h-8"></i>
                            </div>
                            <span class="text-xs text-zinc-500">No submissions found.</span>
                        </div>
                    @else
                        @foreach ($recentSubmissions as $sub)
                            @php
                                $subInitials = collect(explode(' ', $sub->member->name ?? 'User'))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors border-b border-zinc-50 dark:border-zinc-800/40 last:border-0">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/20 dark:border-white/5 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-300 flex-shrink-0">
                                        {{ $subInitials }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                                                {{ $sub->member->name ?? 'Unknown Member' }}
                                            </span>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">•</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                                ${{ number_format($sub->amount, 2) }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-zinc-600 dark:text-zinc-300 font-bold truncate leading-relaxed">
                                            {{ $sub->session->title ?? 'Session' }} • {{ ucfirst($sub->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0 ml-3">
                                    @if ($sub->screenshot_path)
                                        <button 
                                            @click="receiptUrl = '{{ asset('storage/' . $sub->screenshot_path) }}'; receiptModalOpen = true"
                                            class="p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 rounded cursor-pointer transition-colors flex items-center justify-center select-none focus:outline-none"
                                            title="View Receipt"
                                        >
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                    @endif
                                    <a 
                                        href="{{ route('njangi-submissions.index') }}"
                                        class="p-1 text-zinc-900 hover:bg-zinc-100 dark:text-white dark:hover:bg-zinc-800 rounded cursor-pointer transition-colors flex items-center justify-center"
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
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-full text-zinc-300 dark:text-zinc-700">
                                <i data-lucide="users" class="w-8 h-8"></i>
                            </div>
                            <span class="text-xs text-zinc-500">No members found.</span>
                        </div>
                    @else
                        @foreach ($recentMembers as $mb)
                            @php
                                $mbInitials = collect(explode(' ', $mb->name ?? 'User'))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors border-b border-zinc-50 dark:border-zinc-800/40 last:border-0">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/20 dark:border-white/5 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-300 flex-shrink-0">
                                        {{ $mbInitials }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                                                {{ $mb->name }}
                                            </span>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">•</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                                {{ $mb->member_code }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-zinc-600 dark:text-zinc-300 font-bold truncate leading-relaxed">
                                            Joined {{ $mb->join_date ? \Carbon\Carbon::parse($mb->join_date)->format('M d, Y') : 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                    <a 
                                        href="{{ route('members.show', $mb->id) }}"
                                        class="p-1 text-zinc-900 hover:bg-zinc-100 dark:text-white dark:hover:bg-zinc-800 rounded cursor-pointer transition-colors"
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

    <!-- Receipt Modal -->
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
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
        ></div>

        <!-- Modal Content -->
        <div 
            x-show="receiptModalOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative max-w-2xl w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl p-4 flex flex-col gap-4 max-h-[90vh]"
        >
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2">
                <h3 class="font-bold text-zinc-900 dark:text-white text-sm">Receipt Image Preview</h3>
                <button 
                    @click="receiptModalOpen = false"
                    class="p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 dark:text-zinc-400 cursor-pointer flex items-center justify-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="flex-grow overflow-auto flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 rounded-xl border border-zinc-100 dark:border-zinc-800 p-2 min-h-[300px]">
                <img :src="receiptUrl" alt="Receipt Upload" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-sm">
            </div>
        </div>
    </div>

<!-- Auto-instantiation of Lucide Icons -->
<script>
    lucide.createIcons();
</script>
@endsection

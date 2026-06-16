@extends('layouts.app')

@section('content')
<style>
    .scrollbar-thin::-webkit-scrollbar {
        height: 4px;
        width: 4px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 99px;
    }
    .dark .scrollbar-thin::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
    }
</style>

    <!-- Welcome Greeting Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <span class="text-zinc-900 dark:text-zinc-100 shrink-0">
                    <i id="welcome-greeting-icon" data-lucide="sun" class="w-6 h-6"></i>
                </span>
                <h1 class="text-2xl font-bold text-zinc-950 dark:text-white tracking-tight flex items-center gap-2 font-display">
                    <span id="welcome-greeting-text">Good Afternoon</span>, {{ $member->first_name }}
                    <i data-lucide="sparkles" class="w-4 h-4 text-zinc-400 dark:text-zinc-600 animate-pulse hidden sm:inline"></i>
                </h1>
            </div>
            <p class="text-xs text-zinc-550 dark:text-zinc-400">
                Welcome to your personal {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }} Member Portal.
            </p>
        </div>

        @if(isset($memberCycles) && $memberCycles->count() > 1)
            <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-center"
                 x-data="{ 
                     dropdownOpen: false, 
                     activeCycleId: '{{ $activeCycle->id ?? '' }}', 
                     cycles: {
                         @foreach($memberCycles as $mc)
                             '{{ $mc->id }}': { id: '{{ $mc->id }}', name: '{{ $mc->name }}', year: '{{ $mc->year }}' },
                         @endforeach
                     }
                 }"
            >
                <div class="relative inline-block text-left w-full sm:w-auto">
                    <button 
                        type="button"
                        id="cycle_switcher_trigger"
                        @click="dropdownOpen = !dropdownOpen"
                        @click.away="dropdownOpen = false"
                        class="w-full sm:w-auto inline-flex items-center justify-between gap-3 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 px-4 py-2 rounded-xl text-xs font-bold text-zinc-800 dark:text-zinc-150 hover:border-zinc-400 dark:hover:border-zinc-700 focus:outline-none focus:ring-0 transition-all cursor-pointer shadow-sm select-none"
                        :class="dropdownOpen ? 'border-zinc-950 dark:border-zinc-50 ring-2 ring-zinc-950/10 dark:ring-white/10' : ''"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="text-zinc-400 dark:text-zinc-500 font-medium">Cycle:</span>
                            <span x-text="activeCycleId && cycles[activeCycleId] ? cycles[activeCycleId].name + ' (' + cycles[activeCycleId].year + ')' : 'Select Cycle'"></span>
                        </div>
                        <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-600 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div 
                        x-show="dropdownOpen"
                        x-transition:enter="transition ease-out duration-120"
                        x-transition:enter-start="opacity-0 transform scale-95 -translate-y-2"
                        x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-80"
                        x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 transform scale-95 -translate-y-2"
                        class="absolute right-0 z-50 mt-2 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl w-64 overflow-hidden py-1.5"
                        style="display: none;"
                    >
                        <div class="px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-800 mb-1">
                            Switch Njangi Cycle
                        </div>
                        
                        <template x-for="cId in Object.keys(cycles)">
                            <div 
                                @click="window.location.href = '?cycle_id=' + cId; dropdownOpen = false"
                                class="px-4 py-3 text-xs text-zinc-750 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 cursor-pointer transition-colors flex items-center justify-between font-medium"
                                :class="activeCycleId == cId ? 'font-bold text-zinc-950 dark:text-white bg-zinc-50/80 dark:bg-zinc-900/40' : ''"
                            >
                                <div class="flex flex-col gap-0.5">
                                    <span x-text="cycles[cId].name"></span>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-mono" x-text="'Year: ' + cycles[cId].year"></span>
                                </div>
                                <template x-if="activeCycleId == cId">
                                    <svg class="w-4 h-4 text-zinc-900 dark:text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endif
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

    @if(!$activeCycle || !$cycleMember)
        <!-- Enrollment Warning Card -->
        <div class="mb-6 bg-amber-50 dark:bg-amber-955/20 border border-amber-200 dark:border-amber-900 rounded-2xl p-4 flex items-start gap-3.5 animate-fadeIn">
            <div class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider">Not Enrolled in Njangi</h4>
                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1 font-semibold">
                    You are not currently enrolled in an active Njangi rotational cycle. Please contact the Financial Secretary or Treasurer to register your participation.
                </p>
            </div>
        </div>
    @endif

    @if(isset($pendingGuarantees) && $pendingGuarantees->isNotEmpty())
        <!-- Guarantor Alert Banner -->
        <div class="mb-6 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/60 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-450 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-905 dark:text-amber-300">Pending Guarantor Requests</h4>
                    <p class="text-[11px] text-amber-700 dark:text-amber-405 mt-0.5 font-semibold">
                        You have {{ $pendingGuarantees->count() }} request(s) to guarantee loans for other members.
                    </p>
                </div>
            </div>
            <a href="{{ route('member.loans') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold rounded-xl transition-all cursor-pointer shadow-xs flex items-center gap-1.5 self-stretch md:self-auto text-center justify-center select-none">
                <span>Respond Now</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    @endif

    <!-- Overview Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @if($activeCycle && $cycleMember)
            <!-- Current Njangi Cycle -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Current Njangi Cycle</span>
                <div class="text-lg font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                    {{ $activeCycle->name }}
                </div>
                <p class="text-[11px] text-zinc-555 dark:text-zinc-400 mt-2 font-semibold">
                    Active Round Year: {{ $activeCycle->year }}
                </p>
            </div>

            <!-- Benefit Draw Position -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Benefit Position</span>
                <div class="text-xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                    #{{ $benefitOrder ?? '-' }}
                </div>
                <p class="text-[11px] text-zinc-555 dark:text-zinc-400 mt-2 font-semibold">
                    @if($hasBenefited)
                        Status: <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase">Benefited</span>
                    @else
                        Status: <span class="text-zinc-500 font-bold uppercase">Awaiting Draw</span>
                    @endif
                </p>
            </div>

            <!-- Next Hosting Date / Repayments -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">
                    {{ $hasBenefited ? 'Repayments Status' : 'My Payout Date' }}
                </span>
                <div class="text-base font-display font-bold text-zinc-800 dark:text-white leading-none tracking-tight truncate">
                    @if($hasBenefited)
                        @php
                            $totalOwedPlays = \App\Models\NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                                ->where('session_number', '>', $benefitSession?->session_number ?? 0)
                                ->count();
                            $paidOwedPlays = \App\Models\NjangiPaymentSubmission::where('member_id', $member->id)
                                ->where('njangi_cycle_id', $activeCycle->id)
                                ->whereHas('session', function($q) use ($benefitSession) {
                                    $q->where('session_number', '>', $benefitSession?->session_number ?? 0);
                                })
                                ->where('status', 'approved')
                                ->count();
                            $remainingOwedPlays = max(0, $totalOwedPlays - $paidOwedPlays);
                        @endphp
                        {{ $remainingOwedPlays }} left of {{ $totalOwedPlays }} sessions
                    @else
                        {{ $benefitSession ? $benefitSession->session_date->format('M d, Y') : 'Not scheduled' }}
                    @endif
                </div>
                <p class="text-[11px] text-zinc-555 dark:text-zinc-400 mt-2.5 font-semibold">
                    @if($hasBenefited)
                        Post-benefit repayment count
                    @else
                        Upcoming benefit session
                    @endif
                </p>
            </div>
        @endif

        <!-- Savings Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Savings Balance</span>
            <div class="text-xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                ${{ number_format($member->savings_balance, 2) }}
            </div>
            <p class="text-[11px] text-zinc-555 dark:text-zinc-400 mt-2 font-semibold">
                @if($member->participates_in_savings)
                    @if($member->savings_balance >= ($appSettings->min_savings_for_loan ?? 500))
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Loan Eligible
                        </span>
                    @else
                        <span class="text-amber-600 dark:text-amber-500 font-bold uppercase">Under ${{ number_format($appSettings->min_savings_for_loan ?? 500, 0) }} Limit</span>
                    @endif
                @else
                    <span class="text-zinc-500 font-bold uppercase">Not Enrolled</span>
                @endif
            </p>
        </div>

        <!-- Active Loan Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Active Loan Balance</span>
            <div class="text-xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                ${{ number_format($activeLoans->sum('remaining_balance'), 2) }}
            </div>
            <p class="text-[11px] text-zinc-555 dark:text-zinc-400 mt-2 font-semibold">
                @if($activeLoans->isNotEmpty())
                    <span class="text-zinc-805 dark:text-zinc-200 font-bold uppercase inline-flex items-center gap-1">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> {{ $activeLoans->count() }} Active {{ Str::plural('Loan', $activeLoans->count()) }}
                    </span>
                @else
                    <span class="text-zinc-500 font-bold uppercase inline-flex items-center gap-1">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> No Active Loans
                    </span>
                @endif
            </p>
        </div>

        <!-- Pending Requests -->
        @php
            $pendingCount = ($pendingSavingsRequests?->count() ?? 0) + ($pendingRepayRequests?->count() ?? 0) + ($pendingLoanRequests?->count() ?? 0);
        @endphp
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Pending Requests</span>
            <div class="text-xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                {{ $pendingCount }}
            </div>
            <p class="text-[11px] text-zinc-555 dark:text-zinc-400 mt-2 font-semibold">
                @if($pendingCount > 0)
                    <span class="text-amber-600 dark:text-amber-500 font-bold uppercase inline-flex items-center gap-1">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Awaiting Approval
                    </span>
                @else
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> All Caught Up
                    </span>
                @endif
            </p>
        </div>
    </div>


        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 select-none">
            
            <!-- Left: Past Payment Submissions Table -->
            <div class="lg:col-span-7 xl:col-span-8 order-2 lg:order-1 w-full flex flex-col gap-6">
                
                @if($activeCycle && $cycleMember && $activeSession)
                    <x-premium-card title="Next Njangi Session Details">
                        <div class="flex flex-col gap-4 py-2">
                            <!-- Title & Status -->
                            <div class="flex justify-between items-start text-xs gap-3">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider">Session Name</span>
                                    <span class="text-xl font-black text-zinc-955 dark:text-white mt-1 leading-none tracking-tight truncate" title="{{ $activeSession->title ?: 'Session #' . $activeSession->session_number }}">
                                        {{ $activeSession->title ?: "Session #{$activeSession->session_number}" }}
                                    </span>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block mb-1">Status</span>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-purple-50 text-purple-750 dark:bg-purple-955/20 dark:text-purple-400 border border-purple-200/50 dark:border-purple-800/40">
                                        {{ ucfirst($activeSession->status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Date & Time Info -->
                            <div class="grid grid-cols-2 gap-4 text-xs pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                                <div>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block">Session Date</span>
                                    <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                                        {{ $activeSession->session_date->format('M d, Y') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block">Target Year</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                                        {{ $activeCycle->year }}
                                    </span>
                                </div>
                            </div>

                            <!-- Beneficiaries / Hosts list -->
                            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block mb-1.5">Scheduled Hosts / Beneficiaries</span>
                                @if($activeSession->beneficiaries->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($activeSession->beneficiaries as $b)
                                            @if($b->cycleMember && $b->cycleMember->member)
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300 border border-zinc-200/50 dark:border-zinc-700/40">
                                                    {{ $b->cycleMember->member->first_name }} {{ $b->cycleMember->member->last_name }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-450 dark:text-zinc-500 italic block">No hosts assigned for this session.</span>
                                @endif
                            </div>

                            <!-- Session Notes -->
                            @if($activeSession->notes)
                                <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block mb-1">Agenda / Session Notes</span>
                                    <p class="text-xs text-zinc-650 dark:text-zinc-400 leading-relaxed italic">
                                        "{{ $activeSession->notes }}"
                                    </p>
                                </div>
                            @endif

                            <!-- Quick Action Buttons -->
                            <div class="mt-2 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-col sm:flex-row items-center gap-3">
                                <a
                                    href="{{ route('member.njangi-payments') }}"
                                    class="w-full text-center py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-955 text-xs font-bold rounded-xl transition-all shadow-xs"
                                >
                                    Submit Njangi Play
                                </a>
                                <a
                                    href="{{ route('member.njangi-report') }}"
                                    class="w-full text-center py-2 border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-955/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold rounded-xl transition-all"
                                >
                                    View Njangi Report
                                </a>
                            </div>
                        </div>
                    </x-premium-card>
                @endif

                <!-- Profile Summary Card -->
                <x-premium-card title="My Profile Summary">
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center py-2">
                        <!-- Avatar & Title Column -->
                        <div class="flex flex-col items-center gap-2.5 text-center shrink-0 w-full md:w-auto md:border-r md:border-zinc-200/60 dark:md:border-zinc-800/60 md:pr-8">
                            <div class="w-16 h-16 rounded-full bg-zinc-100/60 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 flex items-center justify-center text-zinc-400 dark:text-zinc-500 shrink-0 shadow-3xs">
                                <i data-lucide="user" class="w-8 h-8"></i>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ $member->name }}</span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                                    <i data-lucide="shield" class="w-3 h-3"></i>
                                    {{ $member->rank->name ?? 'Warrior' }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Grid Column -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 flex-1 w-full text-xs">
                            <!-- Member Code -->
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-zinc-500 border border-zinc-100 dark:border-zinc-800 shrink-0">
                                    <i data-lucide="hash" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider">Member ID</span>
                                    <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $member->member_code }}</span>
                                </div>
                            </div>

                            <!-- Join Date -->
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-zinc-500 border border-zinc-100 dark:border-zinc-800 shrink-0">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider">Join Date</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate">
                                        {{ $member->join_date ? $member->join_date->format('M d, Y') : 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-zinc-500 border border-zinc-100 dark:border-zinc-800 shrink-0">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider">Email Address</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $member->email }}</span>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-zinc-500 border border-zinc-100 dark:border-zinc-800 shrink-0">
                                    <i data-lucide="phone" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider">Phone Number</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $member->phone }}</span>
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="flex items-center gap-3 sm:col-span-2">
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-zinc-500 border border-zinc-100 dark:border-zinc-800 shrink-0">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider">Residential Address</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $member->address ?: 'No address provided' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Participation Badges -->
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wider">
                        <span class="px-2.5 py-1 rounded-lg border {{ $member->participates_in_njangi ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-900/50 dark:border-zinc-800' }}">
                            Njangi: {{ $member->participates_in_njangi ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg border {{ $member->participates_in_savings ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-900/50 dark:border-zinc-800' }}">
                            Savings: {{ $member->participates_in_savings ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                    </div>
                </x-premium-card>

            </div>
            
            <!-- Right: Active Loans & Njangi Form -->
            <div class="lg:col-span-5 xl:col-span-4 order-1 lg:order-2 w-full flex flex-col gap-6">
                
                @if(isset($activeLoans) && $activeLoans->isNotEmpty())

                    @foreach($activeLoans as $loan)
                        <x-premium-card title="Active Loan Progress">
                            <div class="flex flex-col gap-4 py-2">
                                <!-- Top details -->
                                <div class="flex justify-between items-center text-xs">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider">Remaining Balance</span>
                                        <span class="text-2xl font-black text-zinc-955 dark:text-white mt-1 leading-none tracking-tight">
                                            ${{ number_format($loan->remaining_balance, 2) }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block">Total Borrowed</span>
                                        <span class="text-sm font-bold text-zinc-850 dark:text-zinc-200 mt-1 block">
                                            ${{ number_format($loan->amount, 2) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div>
                                    @php
                                        $repaidAmount = $loan->amount - $loan->remaining_balance;
                                        $percentage = $loan->amount > 0 ? min(105, max(0, ($repaidAmount / $loan->amount) * 100)) : 0;
                                    @endphp
                                    <div class="flex justify-between items-center text-[10px] font-bold text-zinc-400 uppercase mb-1">
                                        <span>Repayment Progress</span>
                                        <span>{{ number_format($percentage, 0) }}% Paid</span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-950 rounded-full h-1.5 overflow-hidden border border-zinc-200/40 dark:border-zinc-800/40">
                                        <div class="bg-zinc-950 dark:bg-zinc-50 h-full rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>

                                <!-- Bottom row -->
                                <div class="grid grid-cols-2 gap-4 text-xs pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                                    <div>
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block">Duration</span>
                                        <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">{{ $loan->duration_months }} Months</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block">Repayment Due</span>
                                        <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                                            {{ $loan->repayment_due_date ? $loan->repayment_due_date->format('M d, Y') : '-' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Guarantors -->
                                @if($loan->guarantors && $loan->guarantors->isNotEmpty())
                                    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase font-black tracking-wider block mb-1">Guarantors</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($loan->guarantors as $g)
                                                @if($g->guarantorMember)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-zinc-150 text-zinc-850 dark:bg-zinc-800/60 dark:text-zinc-300 border border-zinc-200/50 dark:border-zinc-700/40">
                                                        {{ $g->guarantorMember->name }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Statement Action Link -->
                                <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                                    <a
                                        href="{{ route('member.loans.statement', $loan->id) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-900 dark:text-white hover:text-zinc-950 dark:hover:text-zinc-100 transition-colors"
                                    >
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                        <span>View & Print Statement</span>
                                    </a>
                                </div>
                            </div>
                        </x-premium-card>
                    @endforeach
                @endif

                <!-- My Pending Requests Widget -->
                @php
                    $allPending = collect();
                    
                    if(isset($pendingSavingsRequests)) {
                        foreach($pendingSavingsRequests as $psr) {
                            $allPending->push((object)[
                                'type' => 'Savings Deposit',
                                'amount' => $psr->amount,
                                'date' => $psr->created_at,
                                'status' => 'pending',
                                'status_label' => 'Pending Approval',
                                'icon' => 'piggy-bank',
                                'icon_color' => 'text-amber-500 bg-amber-500/10'
                            ]);
                        }
                    }
                    
                    if(isset($pendingRepayRequests)) {
                        foreach($pendingRepayRequests as $prr) {
                            $allPending->push((object)[
                                'type' => 'Loan Repayment',
                                'amount' => $prr->amount,
                                'date' => $prr->created_at,
                                'status' => 'pending',
                                'status_label' => 'Pending Approval',
                                'icon' => 'wallet',
                                'icon_color' => 'text-blue-500 bg-blue-500/10'
                            ]);
                        }
                    }
                    
                    if(isset($pendingLoanRequests)) {
                        foreach($pendingLoanRequests as $plr) {
                            $statusLabel = $plr->status === 'pending_guarantors' ? 'Guarantor Review' : 'Committee Review';
                            $allPending->push((object)[
                                'type' => 'Loan Application',
                                'amount' => $plr->amount,
                                'date' => $plr->created_at,
                                'status' => $plr->status,
                                'status_label' => $statusLabel,
                                'icon' => 'file-text',
                                'icon_color' => 'text-purple-500 bg-purple-500/10'
                            ]);
                        }
                    }
                    
                    $allPending = $allPending->sortByDesc('date');
                @endphp

                <x-premium-card title="My Pending Requests">
                    <div class="flex flex-col gap-4 py-2">
                        @forelse($allPending as $req)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="p-2 rounded-xl {{ $req->icon_color }} shrink-0">
                                        <i data-lucide="{{ $req->icon }}" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-zinc-850 dark:text-zinc-200 truncate">{{ $req->type }}</span>
                                        <span class="font-mono text-[10px] text-zinc-450 dark:text-zinc-500 font-bold mt-0.5">
                                            {{ $req->date->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end shrink-0 text-right">
                                    <span class="font-black text-zinc-950 dark:text-white">${{ number_format($req->amount, 2) }}</span>
                                    <div class="mt-1">
                                        @if($req->status === 'pending')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-150 text-zinc-650 dark:bg-zinc-800/60 dark:text-zinc-400 border border-zinc-250/50 dark:border-zinc-700/60">
                                                Pending
                                            </span>
                                        @elseif($req->status === 'pending_guarantors')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400 border border-zinc-200/60 dark:border-zinc-700/60">
                                                Guarantor Review
                                            </span>
                                        @elseif($req->status === 'pending_committee')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800/40">
                                                Committee Review
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div class="border-b border-zinc-100 dark:border-zinc-850 my-0.5"></div>
                            @endif
                        @empty
                            <div class="text-center text-zinc-450 dark:text-zinc-600 py-6">
                                <i data-lucide="check-circle" class="w-8 h-8 mx-auto mb-2 text-zinc-300 dark:text-zinc-700"></i>
                                <span class="text-xs font-semibold">All caught up! No pending requests.</span>
                            </div>
                        @endforelse
                    </div>
                </x-premium-card>

            </div>
        </div>


@endsection

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

<div class="space-y-6 pb-12">
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 1. CLEAN EXECUTIVE MEMBER HEADER & QUICK SHORTCUTS                  -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-5 w-full max-w-full overflow-hidden">
        <div class="flex flex-col min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-brand-primary/10 text-brand-primary border border-brand-primary/20 shrink-0">
                    <i data-lucide="shield" class="w-3 h-3"></i>
                    {{ $member->rank->name ?? 'Active Member' }}
                </span>
                <span class="font-mono text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700 shrink-0">
                    #{{ $member->member_code }}
                </span>
            </div>
                <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-zinc-950 dark:text-white tracking-tight font-display leading-tight truncate">
                    <span id="welcome-greeting-text">Good Afternoon</span>, {{ $member->first_name }}
                </h1>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate sm:whitespace-normal">
                    Welcome to your {{ !empty($appSettings->app_name) ? $appSettings->app_name : 'NFUH DMV' }} Member Portal. Overview of your rotational rounds, savings, and civic standing.
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full md:w-auto pt-3 md:pt-0 border-t border-zinc-100 dark:border-zinc-800/80 md:border-0 shrink-0">
            @if(isset($memberCycles) && $memberCycles->count() > 1)
                <div 
                    x-data="{ 
                        dropdownOpen: false, 
                        activeCycleId: '{{ $activeCycle->id ?? '' }}', 
                        cycles: {
                            @foreach($memberCycles as $mc)
                                '{{ $mc->id }}': { id: '{{ $mc->id }}', name: '{{ $mc->name }}', year: '{{ $mc->year }}' },
                            @endforeach
                        }
                    }"
                    class="relative inline-block text-left w-full sm:w-auto"
                >
                    <button 
                        type="button"
                        id="cycle_switcher_trigger"
                        @click="dropdownOpen = !dropdownOpen"
                        @click.away="dropdownOpen = false"
                        class="w-full sm:w-auto inline-flex items-center justify-between sm:justify-start gap-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-3.5 py-2 rounded-xl text-xs font-semibold text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all cursor-pointer shadow-2xs select-none"
                    >
                        <span class="inline-flex items-center gap-1.5 truncate">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-brand-primary shrink-0"></i>
                            <span>Cycle: <span class="font-bold" x-text="activeCycleId && cycles[activeCycleId] ? cycles[activeCycleId].name + ' (' + cycles[activeCycleId].year + ')' : 'Select Cycle'"></span></span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-zinc-400 transition-transform duration-200 shrink-0" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div 
                        x-show="dropdownOpen"
                        x-transition:enter="transition ease-out duration-120"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-80"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                        class="absolute right-0 z-50 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-xl w-64 overflow-hidden py-1.5"
                        style="display: none;"
                    >
                        <div class="px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-100 dark:border-zinc-800">
                            Switch Njangi Cycle
                        </div>
                        <template x-for="cId in Object.keys(cycles)">
                            <div 
                                @click="window.location.href = '?cycle_id=' + cId; dropdownOpen = false"
                                class="px-4 py-2.5 text-xs text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors flex items-center justify-between"
                                :class="activeCycleId == cId ? 'font-bold text-zinc-950 dark:text-white bg-zinc-50 dark:bg-zinc-800/60' : ''"
                            >
                                <div class="flex flex-col">
                                    <span x-text="cycles[cId].name"></span>
                                    <span class="text-[10px] text-zinc-400 font-mono" x-text="'Year: ' + cycles[cId].year"></span>
                                </div>
                                <template x-if="activeCycleId == cId">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            @endif

            <!-- 3 Symmetrical Mobile Action Buttons (1/3 width each on phones, clean pills on desktop) -->
            <div class="grid grid-cols-3 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                @if($member->participates_in_njangi)
                    <a href="{{ route('member.njangi-payments') }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 sm:py-2 rounded-xl text-xs font-bold bg-brand-primary text-brand-primary-text hover:opacity-95 shadow-2xs transition-all text-center select-none active:scale-95">
                        <i data-lucide="send" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>Njangi</span>
                    </a>
                @endif
                @if($member->participates_in_savings)
                    <a href="{{ route('member.savings') }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 sm:py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition-all text-center select-none active:scale-95">
                        <i data-lucide="arrow-down-circle" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>Savings</span>
                    </a>
                @endif
                <a href="{{ route('member.loans') }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 sm:py-2 rounded-xl text-xs font-bold bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700 transition-all text-center select-none active:scale-95">
                    <i data-lucide="banknote" class="w-3.5 h-3.5 shrink-0"></i>
                    <span>Loans</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Dynamic Greeting Script -->
    <script>
        (function() {
            try {
                const hour = new Date().getHours();
                let greeting = 'Good Evening';
                if (hour < 12) {
                    greeting = 'Good Morning';
                } else if (hour < 18) {
                    greeting = 'Good Afternoon';
                }
                const textEl = document.getElementById('welcome-greeting-text');
                if (textEl) textEl.textContent = greeting;
            } catch(e) {}
        })();
    </script>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 2. CRITICAL ACTION BANNERS                                          -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    @if(!$activeCycle || !$cycleMember)
        <!-- Enrollment Warning Card (Strict text preservation for tests) -->
        <div class="bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-2xl p-4 flex items-start gap-3.5 shadow-2xs">
            <div class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider">Not Enrolled in Njangi</h4>
                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1 font-semibold">
                    @if($member->participates_in_njangi)
                        You are registered for Njangi participation but have not yet been assigned to an active Njangi cycle. Please contact the Financial Secretary or Treasurer.
                    @else
                        You are not currently enrolled in an active Njangi rotational cycle. Please contact the Financial Secretary or Treasurer to register your participation.
                    @endif
                </p>
            </div>
        </div>
    @endif

    @if(isset($pendingGuarantees) && $pendingGuarantees->isNotEmpty())
        <!-- Guarantor Alert Banner (Strict text preservation for tests) -->
        <div class="bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300">Pending Guarantor Requests</h4>
                    <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5 font-semibold">
                        You have {{ $pendingGuarantees->count() }} request(s) to guarantee loans for other members.
                    </p>
                </div>
            </div>
            <a href="{{ route('member.loans') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold rounded-xl transition-all cursor-pointer shadow-xs flex items-center gap-1.5 self-stretch md:self-auto text-center justify-center select-none">
                <span>Respond Now</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    @endif

    @if(isset($upcomingSession) && (!isset($upcomingAttendance) || $upcomingAttendance->attendance_status !== 'present'))
        <!-- Meeting Check-In Banner -->
        <div class="bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl shrink-0">
                    <i data-lucide="qr-code" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-emerald-900 dark:text-emerald-300">
                        Upcoming Meeting: {{ $upcomingSession->title ?? 'Session #' . $upcomingSession->session_number }}
                    </h4>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5 font-semibold">
                        Scheduled for {{ $upcomingSession->session_date ? $upcomingSession->session_date->format('l, F d, Y') : '' }}. Check-in is open 5:00 PM – 9:00 PM on meeting day.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-stretch md:self-auto">
                <a href="{{ route('attendance.checkin.show', $upcomingSession->ensureQrToken()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-xl transition-all cursor-pointer shadow-xs select-none inline-flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span>Open Check-In Pass</span>
                </a>
                <a href="{{ route('member.attendance') }}" class="px-3.5 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-[11px] font-bold rounded-xl transition-all hover:bg-zinc-50 dark:hover:bg-zinc-700 select-none">
                    Attendance History
                </a>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 3. THE FOUR CORE MEMBERSHIP PILLARS                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pillar 1: Savings Vault (Nkap) -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        Savings Balance (Nkap)
                    </span>
                    <x-info-tooltip 
                        title="Savings Balance (Nkap)"
                        description="The total money you currently have saved in the credit union vault."
                        formula="Your Total Deposits − Your Total Withdrawals"
                        source="Your Personal Savings Account"
                        color="emerald"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="piggy-bank" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($member->savings_balance, 2) }}
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                @if($member->participates_in_savings)
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Avail: ${{ number_format($member->available_savings_balance, 2) }}
                    </span>
                @else
                    <span class="text-zinc-400 font-bold uppercase">Not Enrolled</span>
                @endif
                <a href="{{ route('member.savings') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 2: Active Loan Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        Active Loan Balance
                    </span>
                    <x-info-tooltip 
                        title="Active Loan Balance"
                        description="The remaining amount you still owe across all your current ongoing loans."
                        formula="Remaining balance across your active loans"
                        source="Your Active Loans"
                        color="amber"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($activeLoans->sum('remaining_balance'), 2) }}
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                @if($activeLoans->isNotEmpty())
                    <span class="text-amber-600 dark:text-amber-400 font-bold uppercase inline-flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> {{ $activeLoans->count() }} Active {{ Str::plural('Loan', $activeLoans->count()) }}
                    </span>
                @else
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1.5">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> No Active Loans
                    </span>
                @endif
                <a href="{{ route('member.loans') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 3: Current Njangi Cycle -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        {{ $nfuhTerms['njangi'] ?? 'Njangi' }} Cycle
                    </span>
                    <x-info-tooltip 
                        title="Njangi Rotational Cycle"
                        description="The rotational round you are currently in, your payout position, and whether you have received your lump sum."
                        formula="Your turn in the round payout queue"
                        source="Current Njangi Cycle"
                        color="purple"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight truncate">
                    {{ $activeCycle ? $activeCycle->name : 'None Active' }}
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                @if($activeCycle && $cycleMember)
                    <span class="text-brand-primary font-bold uppercase inline-flex items-center gap-1.5">
                        <i data-lucide="award" class="w-3.5 h-3.5"></i> Pos #{{ $benefitOrder ?? '-' }} ({{ $hasBenefited ? 'Benefited' : 'Awaiting' }})
                    </span>
                @else
                    <span class="text-zinc-400">Not enrolled in round</span>
                @endif
                <a href="{{ route('member.njangi-report') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 4: Attendance & Assembly Score -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">Attendance Score</span>
                    <x-info-tooltip 
                        title="Assembly Attendance Score"
                        description="Your meeting attendance record for official union general assemblies."
                        formula="(Meetings You Attended ÷ Total Meetings Held) × 100"
                        source="Official Meeting Roll-Call"
                        color="blue"
                    />
                </div>
                <span class="text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/60">Verified</span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight flex items-baseline gap-2">
                    @if(($verifiedAttendanceCount ?? 0) === 0)
                        <span class="text-zinc-400">0%</span>
                        <span class="text-[11px] text-zinc-400 font-normal font-sans">(No verified sessions yet)</span>
                    @else
                        <span>{{ $attendanceRate ?? 0 }}%</span>
                        <span class="text-[11px] text-zinc-400 font-normal font-sans">({{ $verifiedAttendanceCount }} sessions)</span>
                    @endif
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ ($verifiedAttendanceCount ?? 0) === 0 ? 0 : min(100, $attendanceRate ?? 0) }}%"></div>
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                <a href="{{ route('member.attendance') }}" class="text-brand-primary hover:underline text-[11px] font-bold inline-flex items-center gap-1">
                    <span>View Record</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
                <span class="text-[10px] text-zinc-400 font-medium">
                    {{ $upcomingSession ? ($upcomingAttendance?->attendance_status === 'present' ? 'Check-in: Present' : 'Next: ' . ($upcomingSession->session_date ? $upcomingSession->session_date->format('M d') : 'Pending')) : 'No meeting' }}
                </span>
            </div>
        </div>

        <!-- Pillar 5: Net Financial Standing -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        Net Capital Standing
                    </span>
                    <x-info-tooltip 
                        title="Net Capital Standing"
                        description="Your overall financial equity with the union: positive means you have more saved than you owe; negative means you owe more in loans than you have saved."
                        formula="Your Savings − Your Total Active Loan Debt"
                        source="Personal Equity Calculation"
                        :color="$netFinancialPosition >= 0 ? 'emerald' : 'rose'"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl {{ $netFinancialPosition >= 0 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400' }} flex items-center justify-center shrink-0">
                    <i data-lucide="scale" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold {{ $netFinancialPosition >= 0 ? 'text-zinc-950 dark:text-white' : 'text-rose-600 dark:text-rose-400' }} leading-none tracking-tight">
                    {{ $netFinancialPosition < 0 ? '-$' . number_format(abs($netFinancialPosition), 2) : '$' . number_format($netFinancialPosition, 2) }}
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                @if($netFinancialPosition >= 0)
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i> Net Surplus Equity
                    </span>
                @else
                    <span class="text-rose-600 dark:text-rose-400 font-bold uppercase inline-flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Outstanding Net Debt
                    </span>
                @endif
                <a href="{{ route('member.savings') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 6: Lifetime Njangi Contributed -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        Lifetime {{ $nfuhTerms['njangi'] ?? 'Njangi' }}
                    </span>
                    <x-info-tooltip 
                        title="Lifetime Njangi Plays"
                        description="Total money you have contributed into rotational rounds since joining the union."
                        formula="Sum of all your completed Njangi contributions"
                        source="Your Njangi Contribution History"
                        color="blue"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="repeat" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($memberTotalContributions, 2) }}
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                <span class="text-blue-600 dark:text-blue-400 font-bold inline-flex items-center gap-1">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Cumulative Inputs
                </span>
                <a href="{{ route('member.njangi-report') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 7: Total Loan Repayments -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        Repaid Loans Total
                    </span>
                    <x-info-tooltip 
                        title="Repaid Loans Total"
                        description="Total amount of loan principal and interest you have successfully paid back over time."
                        formula="Sum of all loan payments you have made"
                        source="Your Loan Repayment History"
                        color="violet"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                    <i data-lucide="badge-check" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($memberLoanRepaidTotal, 2) }}
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                <span class="text-violet-600 dark:text-violet-400 font-bold inline-flex items-center gap-1">
                    ${{ number_format($memberTotalBorrowed, 0) }} Lifetime Borrowed
                </span>
                <a href="{{ route('member.loans') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 8: Guarantorship & Trust -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 sm:p-6 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <div class="flex items-center min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">
                        Guarantorship Trust
                    </span>
                    <x-info-tooltip 
                        title="Guarantorship Trust"
                        description="The number of loans for other members where you have agreed to stand as a guarantor."
                        formula="Active loans where you are an approved guarantor"
                        source="Guarantor Agreements"
                        color="teal"
                    />
                </div>
                <span class="w-9 h-9 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="my-3">
                <div class="text-2xl sm:text-3xl font-display font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight">
                    {{ $activeGuarantorCount }} Active
                </div>
            </div>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] font-semibold">
                <span class="text-teal-600 dark:text-teal-400 font-bold">
                    ${{ number_format($totalGuaranteedAmount, 2) }} Underwritten
                </span>
                <a href="{{ route('member.loans') }}" class="text-zinc-400 group-hover:text-brand-primary transition-colors flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 4. VISUAL ANALYTICS & PIE/DONUT BREAKDOWN CHARTS (MEMBER SIDE)      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Donut 1: Personal Financial Standing Breakdown -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="pie-chart" class="w-3.5 h-3.5"></i>
                    </span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">Capital Standing</h3>
                    <x-info-tooltip 
                        title="Capital Standing Breakdown"
                        description="A visual snapshot of your finances: what you have saved vs what you owe vs what you played in Njangi."
                        formula="Your Savings : Your Loan Debt : Your Njangi Plays"
                        source="Your Personal Accounts"
                        color="emerald"
                    />
                </div>
                <span class="text-[11px] font-mono font-bold text-zinc-500">Net: {{ ($netFinancialPosition ?? 0) < 0 ? '-$' . number_format(abs($netFinancialPosition), 2) : '$' . number_format($netFinancialPosition ?? 0, 2) }}</span>
            </div>

            <div class="relative py-3 flex items-center justify-center">
                <div class="w-52 h-52">
                    <canvas id="memberCapitalDonutChart"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-center text-xs">
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Savings</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono text-[11px]">${{ number_format($member->savings_balance, 0) }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Debt</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 font-mono text-[11px]">${{ number_format($totalActiveLoanDebt ?? 0, 0) }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Njangi Plays</span>
                    <span class="font-bold text-purple-600 dark:text-purple-400 font-mono text-[11px]">${{ number_format($memberTotalContributions ?? 0, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Donut 2: Loan Repayment Health & Progress -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400">
                        <i data-lucide="circle-dot" class="w-3.5 h-3.5"></i>
                    </span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">Loan Repayment Health</h3>
                    <x-info-tooltip 
                        title="Loan Repayment Health"
                        description="Shows how much of your loan debt you have paid off compared to what is still remaining."
                        formula="Amount You Paid Off vs Amount Remaining Owed"
                        source="Your Loan Progress"
                        color="blue"
                    />
                </div>
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $activeLoans->isNotEmpty() ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' }}">
                    {{ $activeLoans->isNotEmpty() ? $activeLoans->count() . ' Active' : 'Debt Free' }}
                </span>
            </div>

            <div class="relative py-3 flex items-center justify-center">
                <div class="w-52 h-52">
                    <canvas id="memberLoanDonutChart"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-center text-xs">
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Total Repaid</span>
                    <span class="font-bold text-blue-600 dark:text-blue-400 font-mono text-[11px]">${{ number_format($memberLoanRepaidTotal ?? 0, 0) }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Remaining Principal</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400 font-mono text-[11px]">${{ number_format($totalActiveLoanDebt ?? 0, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Upcoming Assembly & Attendance Tracker (Replaces Empty Donut) -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                    </span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">Assembly & Roll-Call</h3>
                    <x-info-tooltip 
                        title="Assembly Attendance Record"
                        description="Your verified attendance counts: meetings present, absent, and excused."
                        formula="Meetings Attended vs Missed"
                        source="Assembly Roll-Call Logs"
                        color="purple"
                    />
                </div>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ ($attendanceRate ?? 0) >= 80 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                    {{ $attendanceRate ?? 0 }}% Score
                </span>
            </div>

            <!-- Main Interactive / Informational Body -->
            <div class="py-3 flex flex-col gap-3">
                @if($upcomingSession)
                    <!-- Next Meeting Highlight Box -->
                    <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/70 dark:border-zinc-800/70 flex flex-col gap-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-brand-primary flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3"></i> Next Assembly
                            </span>
                            <span class="text-[11px] font-bold text-zinc-900 dark:text-white font-mono">
                                {{ $upcomingSession->session_date ? $upcomingSession->session_date->format('M d, Y') : 'Scheduled' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-zinc-900 dark:text-white truncate">
                                {{ $upcomingSession->title ?? 'General Monthly Assembly' }}
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5">
                                Session #{{ $upcomingSession->session_number ?? '1' }} • Status: {{ ucfirst($upcomingSession->status) }}
                            </div>
                        </div>

                        <!-- Check-In Status Badge / Quick Action -->
                        <div class="pt-2 border-t border-zinc-200/50 dark:border-zinc-800/50 flex items-center justify-between">
                            <span class="text-[10px] text-zinc-500 font-semibold">Your Status:</span>
                            @if($upcomingAttendance?->attendance_status === 'present')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Checked In Present
                                </span>
                            @else
                                <a href="{{ route('attendance.checkin.show', $upcomingSession->ensureQrToken()) }}" class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 hover:underline">
                                    <i data-lucide="qr-code" class="w-3 h-3"></i> Open Check-In
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/70 dark:border-zinc-800/70 text-center text-xs text-zinc-400">
                        <i data-lucide="calendar-off" class="w-6 h-6 mx-auto mb-1 text-zinc-300 dark:text-zinc-600"></i>
                        No scheduled assembly at this time.
                    </div>
                @endif

                <!-- Attendance Progress Bar -->
                <div>
                    <div class="flex items-center justify-between text-[11px] mb-1 font-semibold">
                        <span class="text-zinc-500 dark:text-zinc-400">Participation Standing</span>
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $verifiedAttendanceCount ?? 0 }} Verified {{ Str::plural('Session', $verifiedAttendanceCount ?? 0) }}</span>
                    </div>
                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $attendanceRate ?? 0) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Counters Footer -->
            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-center text-xs">
                <div class="p-1.5 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20">
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Present</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono text-xs">{{ $memberPresentCount ?? 0 }}</span>
                </div>
                <div class="p-1.5 rounded-xl bg-rose-50/50 dark:bg-rose-950/20">
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Absent</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 font-mono text-xs">{{ $memberAbsentCount ?? 0 }}</span>
                </div>
                <div class="p-1.5 rounded-xl bg-amber-50/50 dark:bg-amber-950/20">
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Excused</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400 font-mono text-xs">{{ $memberExcusedCount ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Render Script for Member Dashboards -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            // Chart 1: Capital Composition Donut
            const capCtx = document.getElementById('memberCapitalDonutChart');
            if (capCtx) {
                const savingsVal = {{ (float) ($member->savings_balance ?? 0) }};
                const debtVal = {{ (float) ($totalActiveLoanDebt ?? 0) }};
                const njangiVal = {{ (float) ($memberTotalContributions ?? 0) }};
                const hasCapitalData = (savingsVal + debtVal + njangiVal) > 0;

                new Chart(capCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Savings (Nkap)', 'Active Debt', 'Njangi Plays'],
                        datasets: [{
                            data: hasCapitalData ? [savingsVal, debtVal, njangiVal] : [1, 0, 0],
                            backgroundColor: hasCapitalData ? ['#10b981', '#f43f5e', '#8b5cf6'] : ['#e4e4e7'],
                            borderWidth: 2,
                            borderColor: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: hasCapitalData }
                        },
                        cutout: '68%'
                    }
                });
            }

            // Chart 2: Loan Health Donut (Blue for Repaid, Amber for Remaining)
            const loanCtx = document.getElementById('memberLoanDonutChart');
            if (loanCtx) {
                const repaidVal = {{ (float) ($memberLoanRepaidTotal ?? 0) }};
                const remainVal = {{ (float) ($totalActiveLoanDebt ?? 0) }};
                const hasLoanData = (repaidVal + remainVal) > 0;

                new Chart(loanCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Repaid', 'Remaining'],
                        datasets: [{
                            data: hasLoanData ? [repaidVal, remainVal] : [1, 0],
                            backgroundColor: hasLoanData ? ['#3b82f6', '#f59e0b'] : ['#3b82f6'],
                            borderWidth: 2,
                            borderColor: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: hasLoanData }
                        },
                        cutout: '68%'
                    }
                });
            }
        });
    </script>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 5. MAIN OPERATIONAL GRID                                            -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 select-none">
        <!-- LEFT COLUMN (Col 8): Session Brief, Activity Stream, Refund Matrix -->
        <div class="lg:col-span-7 xl:col-span-8 flex flex-col gap-6">
            <!-- Next Session Details -->
            @if($activeCycle && $cycleMember && $activeSession)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3.5 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                <i data-lucide="calendar-check" class="w-4 h-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Next Njangi Session Details</h3>
                        </div>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-400 border border-purple-200/50 dark:border-purple-800/40">
                            {{ ucfirst($activeSession->status) }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-4 pt-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex flex-col min-w-0">
                                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider">Session Name</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-white mt-0.5 leading-snug truncate" title="{{ $activeSession->title ?: 'Session #' . $activeSession->session_number }}">
                                    {{ $activeSession->title ?: "Session #{$activeSession->session_number}" }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs shrink-0">
                                <div>
                                    <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Session Date</span>
                                    <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                                        {{ $activeSession->session_date ? $activeSession->session_date->format('M d, Y') : 'TBD' }}
                                    </span>
                                </div>
                                <div class="border-l border-zinc-100 dark:border-zinc-800 pl-4">
                                    <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Target Year</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                                        {{ $activeCycle->year }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Beneficiaries / Hosts -->
                        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block mb-1.5">Scheduled Hosts / Beneficiaries</span>
                            @if($activeSession->beneficiaries->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($activeSession->beneficiaries as $b)
                                        @if($b->cycleMember && $b->cycleMember->member)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/60 dark:border-zinc-700/60">
                                                <i data-lucide="user" class="w-3 h-3 text-brand-primary"></i>
                                                {{ $b->cycleMember->member->first_name }} {{ $b->cycleMember->member->last_name }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-zinc-400 italic block">No hosts assigned for this session.</span>
                            @endif
                        </div>

                        @if($activeSession->notes)
                            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block mb-1">Agenda / Session Notes</span>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed italic bg-zinc-50 dark:bg-zinc-850 p-3 rounded-xl border border-zinc-100 dark:border-zinc-800">
                                    "{{ $activeSession->notes }}"
                                </p>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row items-center gap-3">
                            <a href="{{ route('member.njangi-payments') }}" class="w-full sm:w-auto text-center px-4 py-2.5 bg-brand-primary text-brand-primary-text hover:opacity-95 text-xs font-bold rounded-xl transition-all shadow-2xs">
                                Submit Njangi Play
                            </a>
                            <a href="{{ route('member.njangi-report') }}" class="w-full sm:w-auto text-center px-4 py-2.5 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-xs font-bold rounded-xl transition-all">
                                View Njangi Report
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Personal Financial Activity Ledger -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Personal Activity Ledger</h3>
                            <p class="text-[11px] text-zinc-400">Recent transactions across your savings, loans, and Njangi accounts.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Verified</span>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse($personalActivity as $act)
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $act['is_credit'] ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                    <i data-lucide="{{ $act['type'] === 'savings' ? 'piggy-bank' : ($act['type'] === 'njangi' ? 'layers' : 'credit-card') }}" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $act['title'] }}</span>
                                    <span class="text-[10px] text-zinc-400 font-mono">{{ $act['date'] }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0 text-right">
                                <span class="font-black text-xs font-mono {{ $act['is_credit'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-white' }}">
                                    {{ $act['is_credit'] ? '+' : '-' }}${{ number_format($act['amount'], 2) }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider {{ $act['badge_color'] === 'emerald' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : ($act['badge_color'] === 'amber' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : ($act['badge_color'] === 'blue' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400' : 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-400')) }}">
                                    {{ $act['badge'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            <p class="text-xs">No recent ledger activity recorded yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Njangi Rotational Refund Matrix -->
            @if(isset($refundSummary) && $refundSummary->isNotEmpty())
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3.5 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="repeat" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Round Refund Obligations Matrix</h3>
                                <p class="text-[11px] text-zinc-400">Direct reciprocal refund reconciliation for the active Njangi cycle.</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-0.5 rounded-full border border-emerald-200/50">
                            {{ $refundSummary->count() }} Peers
                        </span>
                    </div>

                    <div class="overflow-x-auto scrollbar-thin mt-3">
                        <table class="w-full text-xs text-left">
                            <thead class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-100 dark:border-zinc-800">
                                <tr>
                                    <th class="py-2.5 px-3">Beneficiary</th>
                                    <th class="py-2.5 px-3">Expected Refund</th>
                                    <th class="py-2.5 px-3">Refunded</th>
                                    <th class="py-2.5 px-3">Outstanding</th>
                                    <th class="py-2.5 px-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 font-medium">
                                @foreach($refundSummary as $ref)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-850 transition-colors">
                                        <td class="py-3 px-3 font-bold text-zinc-800 dark:text-zinc-200">
                                            {{ $ref['beneficiary_name'] }}
                                        </td>
                                        <td class="py-3 px-3 font-mono">${{ number_format($ref['expected_to_refund'], 2) }}</td>
                                        <td class="py-3 px-3 font-mono text-emerald-600 dark:text-emerald-400">${{ number_format($ref['amount_refunded'], 2) }}</td>
                                        <td class="py-3 px-3 font-mono {{ $ref['outstanding'] > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-zinc-400' }}">
                                            ${{ number_format($ref['outstanding'], 2) }}
                                        </td>
                                        <td class="py-3 px-3 text-right">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider {{ $ref['status'] === 'Settled' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : ($ref['status'] === 'Partial' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400') }}">
                                                {{ $ref['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Additional Active Loans Flow (Fills Left Column Space) -->
            @if(isset($activeLoans) && $activeLoans->count() > 1)
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-1 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                <i data-lucide="layers" class="w-4 h-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">
                                Active Loan Facilities ({{ $activeLoans->count() - 1 }} Additional)
                            </h3>
                        </div>
                        <span class="text-xs text-zinc-400 font-medium">Ongoing Amortizations</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($activeLoans->slice(1) as $loan)
                            @include('dashboard.partials.active-loan-card', ['loan' => $loan])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- RIGHT COLUMN (Col 4): Primary Active Loan, Pending Requests, Profile Standing -->
        <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-6">
            <!-- Primary Active Loan Progress Widget -->
            @if(isset($activeLoans) && $activeLoans->isNotEmpty())
                @include('dashboard.partials.active-loan-card', ['loan' => $activeLoans->first()])
            @endif

            <!-- My Pending Requests Tracker -->
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

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">My Pending Requests</h3>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $allPending->isNotEmpty() ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' }}">
                        {{ $allPending->count() }} Pending
                    </span>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60 pt-1">
                    @forelse($allPending as $req)
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="p-2 rounded-xl {{ $req->icon_color }} shrink-0">
                                    <i data-lucide="{{ $req->icon }}" class="w-4 h-4"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $req->type }}</span>
                                    <span class="font-mono text-[10px] text-zinc-400">{{ $req->date->format('M d, Y') }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end shrink-0 text-right">
                                <span class="font-black text-xs font-mono text-zinc-900 dark:text-white">${{ number_format($req->amount, 2) }}</span>
                                <span class="inline-block mt-0.5 px-2 py-0.2 rounded-full text-[9px] font-bold uppercase tracking-wider {{ $req->status === 'pending' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : ($req->status === 'pending_guarantors' ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-400') }}">
                                    {{ $req->status_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-400 py-6">
                            <i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-1 text-emerald-500 opacity-60"></i>
                            <span class="text-xs font-semibold">All caught up! No pending requests.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Profile Summary Card -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">My Profile Summary</h3>
                    </div>
                    <span class="text-[10px] font-bold uppercase text-brand-primary">Verified Standing</span>
                </div>

                <div class="pt-4 flex flex-col gap-3.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-zinc-400 uppercase font-bold tracking-wider">Full Name</span>
                        <span class="font-bold text-zinc-900 dark:text-white">{{ $member->first_name }} {{ $member->last_name }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-zinc-400 uppercase font-bold tracking-wider">Member ID</span>
                        <span class="font-mono font-bold text-zinc-900 dark:text-white">#{{ $member->member_code }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-zinc-400 uppercase font-bold tracking-wider">Rank / Standing</span>
                        <span class="inline-flex items-center gap-1 font-bold text-brand-primary">
                            <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                            {{ $member->rank->name ?? 'Warrior' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-zinc-400 uppercase font-bold tracking-wider">Join Date</span>
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">
                            {{ $member->join_date ? $member->join_date->format('M d, Y') : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-zinc-400 uppercase font-bold tracking-wider">Contact Phone</span>
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $member->phone ?: 'N/A' }}</span>
                    </div>

                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wider">
                        <span class="px-2.5 py-1 rounded-lg border {{ $member->participates_in_njangi ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-900/50 dark:border-zinc-800' }}">
                            Njangi: {{ $member->participates_in_njangi ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg border {{ $member->participates_in_savings ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-900/50 dark:border-zinc-800' }}">
                            Savings: {{ $member->participates_in_savings ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

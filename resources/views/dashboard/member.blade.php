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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-none mb-4">
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
            <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-center select-none"
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
                        class="absolute right-0 z-50 mt-2 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl w-64 overflow-hidden py-1.5 select-none"
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
        <x-premium-card class="-mt-1">
            <div class="flex flex-col items-center justify-center text-center p-8 gap-3 select-none">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-full text-zinc-300 dark:text-zinc-700">
                    <i data-lucide="alert-circle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-sm font-bold text-zinc-800 dark:text-white">Not Enrolled in Njangi</h3>
                <p class="text-xs text-zinc-550 dark:text-zinc-400 max-w-md">
                    You are not currently enrolled in an active Njangi rotational cycle. Please contact the Financial Secretary or Treasurer to register your participation.
                </p>
            </div>
        </x-premium-card>
    @else
        @php
            $sessionsData = [];
            foreach ($sessions as $s) {
                $bNames = [];
                foreach ($s->beneficiaries as $b) {
                    $bNames[] = $b->cycleMember->member->first_name . ' ' . $b->cycleMember->member->last_name;
                }
                $sessionsData[$s->id] = [
                    'id' => $s->id,
                    'title' => $s->title ?: "Session #{$s->session_number}",
                    'date' => $s->session_date->format('Y-m-d'),
                    'beneficiaries' => $bNames,
                ];
            }
        @endphp
        <!-- Njangi Overview Stats Grid (Full Width) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 select-none mb-6">
            
            <!-- Current Cycle -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Current Cycle</span>
                <div class="text-lg font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                    {{ $activeCycle->name }}
                </div>
                <p class="text-[11px] text-zinc-550 dark:text-zinc-400 mt-2 font-semibold">Active Round Year: {{ $activeCycle->year }}</p>
            </div>

            <!-- Benefit Draw Position -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Benefit Position</span>
                <div class="text-xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
                    #{{ $benefitOrder ?? '-' }}
                </div>
                <p class="text-[11px] text-zinc-550 dark:text-zinc-400 mt-2 font-semibold">
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
                <p class="text-[11px] text-zinc-550 dark:text-zinc-400 mt-2.5 font-semibold">
                    @if($hasBenefited)
                        Post-benefit repayment count
                    @else
                        Upcoming benefit session
                    @endif
                </p>
            </div>
        </div>

        <!-- Premium Action Card Wrapper (Table Left, Form Right) -->
        <div x-data="{ 
            selectedSessionId: '{{ $activeSession->id ?? '' }}',
            sessions: {{ json_encode($sessionsData) }},
            sessionDropdownOpen: false
        }" class="grid grid-cols-1 lg:grid-cols-12 gap-6 select-none">
            
            <!-- Left: Past Payment Submissions Table -->
            <div class="lg:col-span-7 xl:col-span-8 order-2 lg:order-1 w-full flex flex-col gap-6">
                <!-- Profile Summary Card -->
                <x-premium-card title="My Profile Summary">
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center py-2 select-none">
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
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wider select-none">
                        <span class="px-2.5 py-1 rounded-lg border {{ $member->participates_in_njangi ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-900/50 dark:border-zinc-800' }}">
                            Njangi: {{ $member->participates_in_njangi ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg border {{ $member->participates_in_savings ? 'bg-emerald-50/50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-900/50 dark:border-zinc-800' }}">
                            Savings: {{ $member->participates_in_savings ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                    </div>
                </x-premium-card>

                <x-premium-card title="My Payment Submissions">
                    <x-premium-table :headers="[
                        'Submitted Date',
                        'Session',
                        'Amount',
                        'Attendance',
                        'Status',
                        ['label' => 'Zelle Image', 'align' => 'center']
                    ]">
                        @forelse($submissions as $sub)
                            <x-premium-table-row :is-even="$loop->index % 2 === 1">
                                <td class="py-2.5 px-3 text-zinc-555 dark:text-zinc-400 font-mono">
                                    {{ $sub->submitted_at ? $sub->submitted_at->format('M d, Y') : $sub->created_at->format('M d, Y') }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-200">
                                    {{ $sub->session->title ?: "Session #{$sub->session->session_number}" }}
                                </td>
                                <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">
                                    ${{ number_format($sub->amount, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-zinc-700 dark:text-zinc-300">
                                    @if($sub->is_attending)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Attending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] text-zinc-400">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Playing Only
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($sub->status === 'approved')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                            Approved
                                        </span>
                                    @elseif($sub->status === 'pending')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-zinc-150 text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-400 border border-zinc-250/50 dark:border-zinc-700/60">
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-red-50 text-red-750 dark:bg-red-950/20 dark:text-red-400 border border-red-250/50 dark:border-red-800/40" @if($sub->review_note) title="Reason: {{ $sub->review_note }}" @endif>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <a 
                                        href="{{ asset('storage/' . $sub->screenshot_path) }}" 
                                        target="_blank" 
                                        class="inline-flex items-center gap-1 text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer select-none"
                                        title="View Receipt Screenshot"
                                    >
                                        <i data-lucide="external-link" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </x-premium-table-row>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                                    No payment submissions found.
                                </td>
                            </tr>
                        @endforelse
                    </x-premium-table>
                    
                    @if($submissions->hasPages())
                        <div class="border-t border-zinc-100 dark:border-zinc-800/80 pt-4 mt-2">
                            {{ $submissions->links() }}
                        </div>
                    @endif
                </x-premium-card>
            </div>
            
            <!-- Right: Submit Njangi Play Form -->
            <div class="lg:col-span-5 xl:col-span-4 order-1 lg:order-2 w-full">
                <x-premium-card title="Submit Njangi Play">
                    <div class="w-full py-2">
                        <form action="{{ route('member.submissions.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
                            @csrf

                            <!-- Session Selector Dropdown -->
                            <div class="flex flex-col w-full relative">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
                                    Njangi Session <span class="text-red-500">*</span>
                                </label>
                                
                                <!-- Hidden input to hold the actual value for form submission -->
                                <input type="hidden" name="njangi_session_id" :value="selectedSessionId" required>

                                <!-- Dropdown Container -->
                                <div class="relative w-full">
                                    <!-- Dropdown Trigger Button -->
                                    <button 
                                        type="button"
                                        @click="sessionDropdownOpen = !sessionDropdownOpen"
                                        @click.outside="sessionDropdownOpen = false"
                                        class="w-full inline-flex items-center justify-between bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 px-4 py-3 rounded-xl text-sm font-semibold text-zinc-800 dark:text-zinc-200 hover:border-zinc-400 dark:hover:border-zinc-700 focus:outline-none focus:ring-0 transition-all cursor-pointer shadow-3xs"
                                        :class="sessionDropdownOpen ? 'border-zinc-950 dark:border-zinc-50 ring-2 ring-zinc-950/10 dark:ring-white/10' : ''"
                                    >
                                        <div class="flex flex-col text-left">
                                            <span class="text-xs font-bold" x-text="selectedSessionId && sessions[selectedSessionId] ? sessions[selectedSessionId].title : 'Select Njangi Session'"></span>
                                            <span class="text-[11px] font-mono text-zinc-450 dark:text-zinc-500 mt-0.5" x-show="selectedSessionId && sessions[selectedSessionId]" x-text="selectedSessionId && sessions[selectedSessionId] ? 'Date: ' + sessions[selectedSessionId].date : ''"></span>
                                        </div>
                                        <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-600 transition-transform duration-200" :class="sessionDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </button>

                                    <!-- Dropdown Menu Options -->
                                    <div 
                                        x-show="sessionDropdownOpen"
                                        x-transition:enter="transition ease-out duration-120"
                                        x-transition:enter-start="opacity-0 transform scale-98 -translate-y-2"
                                        x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-80"
                                        x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 transform scale-98 -translate-y-2"
                                        class="absolute left-0 right-0 z-40 mt-2 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl max-h-60 overflow-y-auto py-1.5 select-none scrollbar-thin"
                                        style="display: none;"
                                    >
                                        <template x-for="sId in Object.keys(sessions)">
                                            <div 
                                                @click="selectedSessionId = sId; sessionDropdownOpen = false"
                                                class="px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 cursor-pointer transition-colors flex items-center justify-between font-medium border-b border-zinc-50 dark:border-zinc-900/20 last:border-0 text-zinc-700 dark:text-zinc-300"
                                                :class="selectedSessionId == sId ? 'font-bold text-zinc-950 dark:text-white bg-zinc-50 dark:bg-zinc-900/40' : ''"
                                            >
                                                <div class="flex flex-col gap-0.5 min-w-0">
                                                    <span class="text-xs font-bold" x-text="sessions[sId].title"></span>
                                                    <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-mono" x-text="'Date: ' + sessions[sId].date"></span>
                                                </div>
                                                
                                                <template x-if="selectedSessionId == sId">
                                                    <svg class="w-4 h-4 text-zinc-950 dark:text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Beneficiaries information panel -->
                            <template x-if="selectedSessionId && sessions[selectedSessionId]">
                                <div class="text-xs font-semibold text-zinc-550 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/40 dark:border-zinc-800/40 rounded-xl p-3.5 flex flex-col gap-1 select-none animate-fadeIn">
                                    <span class="text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Beneficiaries in this Session:</span>
                                    <span class="text-zinc-800 dark:text-zinc-200 text-xs font-bold" x-text="sessions[selectedSessionId].beneficiaries.join(', ') || 'No Beneficiaries Assigned'"></span>
                                </div>
                            </template>

                            <!-- Play Amount -->
                            <x-premium-input 
                                label="Play Amount ($)" 
                                name="amount" 
                                type="number" 
                                step="0.01" 
                                min="0.01"
                                required 
                                placeholder="e.g. 400.00" 
                            />

                            <!-- Attendance Toggle -->
                            <div class="flex flex-col w-full">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
                                    Will you attend physically? <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <label class="flex-1">
                                        <input type="radio" name="is_attending" value="1" class="hidden peer" required checked>
                                        <div class="w-full text-center py-2.5 px-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-950/20 peer-checked:border-zinc-950 dark:peer-checked:border-zinc-50 peer-checked:bg-zinc-950 peer-checked:text-white dark:peer-checked:bg-zinc-50 dark:peer-checked:text-zinc-950 text-xs font-bold transition-all cursor-pointer">
                                            Yes, Attending
                                        </div>
                                    </label>
                                    <label class="flex-1">
                                        <input type="radio" name="is_attending" value="0" class="hidden peer">
                                        <div class="w-full text-center py-2.5 px-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-950/20 peer-checked:border-zinc-950 dark:peer-checked:border-zinc-50 peer-checked:bg-zinc-950 peer-checked:text-white dark:peer-checked:bg-zinc-50 dark:peer-checked:text-zinc-950 text-xs font-bold transition-all cursor-pointer">
                                            No, Playing Only
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Zelle Screenshot File Input with preview -->
                            <div class="flex flex-col w-full" x-data="{ imagePreview: null }">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
                                    Zelle Screenshot <span class="text-red-500">*</span>
                                </label>
                                <div class="relative w-full">
                                    <input 
                                        type="file" 
                                        name="screenshot" 
                                        id="screenshot"
                                        required
                                        accept="image/*"
                                        class="hidden"
                                        @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                const reader = new FileReader();
                                                reader.onload = (e) => { imagePreview = e.target.result; };
                                                reader.readAsDataURL(file);
                                            } else {
                                                imagePreview = null;
                                            }
                                        "
                                    >
                                    <label 
                                        for="screenshot"
                                        class="w-full flex flex-col items-center justify-center bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl p-5 cursor-pointer text-center select-none transition-all group"
                                        :class="imagePreview ? 'border-zinc-950 dark:border-zinc-50' : 'hover:border-zinc-450 dark:hover:border-zinc-700'"
                                    >
                                        <template x-if="!imagePreview">
                                            <div class="flex flex-col items-center gap-2">
                                                <i data-lucide="upload-cloud" class="w-8 h-8 text-zinc-400 group-hover:text-zinc-555 transition-colors"></i>
                                                <span class="text-xs font-semibold text-zinc-805 dark:text-white">Upload receipt screenshot</span>
                                                <span class="text-[11px] text-zinc-400">PNG, JPG, JPEG up to 5MB</span>
                                            </div>
                                        </template>
                                        <template x-if="imagePreview">
                                            <div class="flex flex-col items-center gap-3 w-full">
                                                <img :src="imagePreview" class="max-h-44 object-contain rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-800">
                                                <div class="flex items-center gap-1.5 text-[11px] font-bold text-zinc-500 dark:text-zinc-400">
                                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                                    <span>Click to replace image</span>
                                                </div>
                                            </div>
                                        </template>
                                    </label>
                                </div>
                            </div>

                            <!-- Optional Note -->
                            <div class="flex flex-col w-full">
                                <label for="member_note" class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
                                    Note to Treasurer (Optional)
                                </label>
                                <textarea 
                                    name="member_note" 
                                    id="member_note" 
                                    rows="3"
                                    placeholder="Add optional reference details or notes..."
                                    class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                                ></textarea>
                            </div>

                            <!-- Action Submit Button -->
                            <x-premium-button type="submit" variant="primary" class="w-full py-3">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Submit Payment</span>
                            </x-premium-button>

                        </form>
                    </div>
                </x-premium-card>
    @endif

@endsection

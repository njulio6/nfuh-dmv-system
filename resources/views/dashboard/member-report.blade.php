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

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <x-premium-header 
            title="My Njangi Ledger & Refund Report" 
            subtitle="View expected contributions, dynamic standings, and contribution history."
            back-url="{{ route('dashboard') }}"
            back-title="Back to Dashboard"
            class="mb-0"
        />

        @if(isset($memberCycles) && $memberCycles->count() > 1)
            <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-center animate-fadeIn"
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
                        class="w-full sm:w-auto inline-flex items-center justify-between gap-3 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 px-4 py-2.5 rounded-xl text-xs font-bold text-zinc-800 dark:text-zinc-150 hover:border-zinc-400 dark:hover:border-zinc-700 focus:outline-none focus:ring-0 transition-all cursor-pointer shadow-sm select-none"
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
                                class="px-4 py-3 text-xs text-zinc-755 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 cursor-pointer transition-colors flex items-center justify-between font-medium"
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

    @if(!$activeCycle || !$cycleMember)
        <!-- Enrollment Warning Card -->
        <x-premium-card class="-mt-1">
            <div class="flex flex-col items-center justify-center text-center p-8 gap-3">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-full text-zinc-300 dark:text-zinc-700">
                    <i data-lucide="alert-circle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-sm font-bold text-zinc-800 dark:text-white">Not Enrolled in Njangi</h3>
                <p class="text-xs text-zinc-555 dark:text-zinc-400 max-w-md">
                    You are not currently enrolled in this active Njangi rotational cycle. Please contact the Financial Secretary or Treasurer to register your participation.
                </p>
            </div>
        </x-premium-card>
    @else
        <!-- Njangi Ledger & Refund Report Layout -->
        <div x-data="{ activeReportTab: 'refunds' }" class="flex flex-col gap-6">
            
            <!-- Top Summary Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl flex items-center gap-3.5 shadow-3xs hover:shadow-md transition-all duration-200">
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 rounded-lg shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider">Total Paid Contributions</span>
                        <span class="text-lg font-display font-black text-zinc-800 dark:text-white leading-none mt-1.5">
                            ${{ number_format($contributionsMade->sum('amount'), 2) }}
                        </span>
                    </div>
                </div>

                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl flex items-center gap-3.5 shadow-3xs hover:shadow-md transition-all duration-200">
                    <div class="p-2.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 rounded-lg shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0h11.25m-11.25 0V8.25"/></svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider">Total Payouts Received</span>
                        <span class="text-lg font-display font-black text-zinc-800 dark:text-white leading-none mt-1.5">
                            ${{ number_format($contributionsReceived->sum('amount'), 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Detailed Tables Card -->
            <x-premium-card title="Ledger & Refund Statement Summary">
                <!-- Tab Selectors -->
                <div class="flex border-b border-zinc-100 dark:border-zinc-800/60 mb-4">
                    <button 
                        @click="activeReportTab = 'refunds'"
                        class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer focus:outline-none"
                        :class="activeReportTab === 'refunds' ? 'border-zinc-950 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300'"
                    >
                        Refund Standings
                    </button>
                    <button 
                        @click="activeReportTab = 'paid'"
                        class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer focus:outline-none"
                        :class="activeReportTab === 'paid' ? 'border-zinc-950 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300'"
                    >
                        Contributions Paid
                    </button>
                    <button 
                        @click="activeReportTab = 'received'"
                        class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 transition-all cursor-pointer focus:outline-none"
                        :class="activeReportTab === 'received' ? 'border-zinc-950 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300'"
                    >
                        Payouts Received
                    </button>
                </div>

                <!-- Tab Panels -->
                <div>
                    <!-- Refunds Standings -->
                    <div x-show="activeReportTab === 'refunds'" class="w-full">
                        <x-premium-table :headers="['Beneficiary', 'Expected to Refund', 'Amount Refunded', 'Outstanding Balance', 'Status']">
                            @forelse ($refundSummary as $idx => $item)
                                <x-premium-table-row :is-even="$idx % 2 === 1">
                                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ $item['beneficiary_name'] }}</td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">${{ number_format($item['expected_to_refund'], 2) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">${{ number_format($item['amount_refunded'], 2) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">${{ number_format($item['outstanding'], 2) }}</td>
                                    <td class="py-2.5 px-3">
                                        @if ($item['status'] === 'Settled')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                                Settled
                                            </span>
                                        @elseif ($item['status'] === 'Partial')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-750 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40">
                                                Partial
                                            </span>
                                        @elseif ($item['status'] === 'Pending')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-150 text-zinc-700 dark:bg-zinc-850 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-850/40">
                                                Pending
                                            </span>
                                        @elseif ($item['status'] === 'Paid')
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-sky-50 text-sky-750 dark:bg-sky-950/20 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800/40">
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-750 dark:bg-red-950/10 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                                Not Paid
                                            </span>
                                        @endif
                                    </td>
                                </x-premium-table-row>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-zinc-400 dark:text-zinc-600 py-12">
                                        No refund history available for this cycle.
                                    </td>
                                </tr>
                            @endforelse
                        </x-premium-table>
                    </div>

                    <!-- Paid Contributions -->
                    <div x-show="activeReportTab === 'paid'" class="w-full" x-cloak>
                        <x-premium-table :headers="['Session', 'Beneficiary', 'Amount Paid', 'Reference ID', 'Cleared Date']">
                            @forelse ($contributionsMade as $idx => $con)
                                <x-premium-table-row :is-even="$idx % 2 === 1">
                                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ $con->session->title ?: "Session #{$con->session->session_number}" }}
                                    </td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-700 dark:text-zinc-300">
                                        {{ $con->beneficiary->name }}
                                    </td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">
                                        ${{ number_format($con->amount, 2) }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-mono text-[11px] px-2 py-0.5 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-450 rounded-lg border border-zinc-200/40 dark:border-zinc-800/40 shadow-3xs">
                                            #{{ $con->payment_submission_id }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-zinc-550 dark:text-zinc-400 font-mono">
                                        {{ $con->created_at->format('M d, Y') }}
                                    </td>
                                </x-premium-table-row>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-zinc-400 dark:text-zinc-600 py-12">
                                        No paid contributions logged in this cycle.
                                    </td>
                                </tr>
                            @endforelse
                        </x-premium-table>
                    </div>

                    <!-- Received Payouts -->
                    <div x-show="activeReportTab === 'received'" class="w-full" x-cloak>
                        <x-premium-table :headers="['Session', 'Contributor', 'Amount Received', 'Cleared Date']">
                            @forelse ($contributionsReceived as $idx => $con)
                                <x-premium-table-row :is-even="$idx % 2 === 1">
                                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ $con->session->title ?: "Session #{$con->session->session_number}" }}
                                    </td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-700 dark:text-zinc-300">
                                        {{ $con->contributor->name }}
                                    </td>
                                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">
                                        ${{ number_format($con->amount, 2) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-zinc-550 dark:text-zinc-400 font-mono">
                                        {{ $con->created_at->format('M d, Y') }}
                                    </td>
                                </x-premium-table-row>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-zinc-400 dark:text-zinc-600 py-12">
                                        No received contributions logged in this cycle.
                                    </td>
                                </tr>
                            @endforelse
                        </x-premium-table>
                    </div>
                </div>
            </x-premium-card>
        </div>
    @endif

@endsection

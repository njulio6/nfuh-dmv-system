@extends('layouts.app')

@section('content')
<div 
    x-data="{ 
        showWithdrawalModal: false,
        gross: '',
        feePercent: {{ $appSettings->savings_withdrawal_fee_percent ?? 2.00 }},
        payoutMethod: 'zelle',
        get feeAmount() {
            let val = parseFloat(this.gross) || 0;
            return (val * (this.feePercent / 100)).toFixed(2);
        },
        get netAmount() {
            let val = parseFloat(this.gross) || 0;
            let fee = parseFloat(this.feeAmount) || 0;
            return (Math.max(0, val - fee)).toFixed(2);
        }
    }" 
    class="flex flex-col gap-6 animate-fadeIn"
>
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 shadow-xs">
        <div class="flex flex-col">
            <h2 class="text-base font-bold text-zinc-900 dark:text-white font-display">My Savings Account</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">View balances, deposit receipts, and submit savings withdrawal requests.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a 
                href="{{ route('member.savings.requests') }}"
                class="px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-all cursor-pointer flex items-center gap-1.5"
            >
                <i data-lucide="arrow-down-left" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>Deposit Requests</span>
            </a>
            <a 
                href="{{ route('member.savings.withdrawals') }}"
                class="px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-all cursor-pointer flex items-center gap-1.5"
            >
                <i data-lucide="history" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                <span>Withdrawal History</span>
            </a>
            <button 
                type="button" 
                @click="showWithdrawalModal = true"
                class="px-4 py-2 rounded-xl text-xs font-bold bg-zinc-950 hover:bg-zinc-900 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-950 shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer flex items-center gap-1.5"
            >
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                <span>Request Withdrawal</span>
            </button>
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Net Savings Balance -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2 shrink-0">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Total Savings Balance</span>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-2xl lg:text-3xl font-display font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($member->savings_balance, 2) }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                    Available: <strong class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">${{ number_format($member->available_savings_balance, 2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Loan Eligibility -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2 shrink-0">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Loan Status</span>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-xl lg:text-2xl font-display font-bold text-zinc-950 dark:text-white">
                    @if($member->savings_balance >= ($appSettings->min_savings_for_loan ?? 500))
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i> Loan Eligible
                        </span>
                    @else
                        <span class="text-amber-600 dark:text-amber-500 font-bold uppercase inline-flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i> Under Limit (${{ number_format($appSettings->min_savings_for_loan ?? 500, 0) }})
                        </span>
                    @endif
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">
                    Min required: ${{ number_format($appSettings->min_savings_for_loan ?? 500, 2) }}
                </div>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="stat-card-gradient p-5 flex flex-col justify-between gap-3.5 group">
            <div class="flex items-center gap-3">
                <div class="stat-icon-badge p-2 shrink-0">
                    <i data-lucide="list-ordered" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">Total Entries</span>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-2xl lg:text-3xl font-display font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    {{ $transactions->total() }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">
                    Audited ledger records on file
                </div>
            </div>
        </div>
    </div>

    <!-- Savings Ledger Table -->
    <x-premium-card title="Historical Savings Statements">
        <x-premium-table :headers="[
            'Transaction Date',
            'Type',
            'Reference Note',
            ['label' => 'Amount', 'align' => 'right']
        ]">
            @forelse($transactions as $t)
                <x-premium-table-row :is-even="$loop->index % 2 === 1">
                    <td class="py-3 px-3 font-mono text-zinc-555 dark:text-zinc-450 text-xs">
                        {{ $t->transaction_date->format('M d, Y') }}
                    </td>
                    <td class="py-3 px-3 text-xs">
                        @if($t->type === 'deposit')
                            <span class="inline-block px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                Deposit
                            </span>
                        @elseif($t->type === 'withdrawal')
                            <span class="inline-block px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider bg-red-50 text-red-750 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                Withdrawal
                            </span>
                        @else
                            <span class="inline-block px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider bg-zinc-100 text-zinc-650 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200/60 dark:border-zinc-700/60">
                                Adjustment
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-3 text-zinc-700 dark:text-zinc-350 text-xs italic">
                        {{ $t->notes ?: '-' }}
                    </td>
                    <td class="py-3 px-3 text-right font-bold text-sm">
                        @if($t->type === 'deposit')
                            <span class="text-emerald-600 dark:text-emerald-400">
                                +${{ number_format($t->amount, 2) }}
                            </span>
                        @elseif($t->type === 'withdrawal')
                            <span class="text-red-650 dark:text-red-400">
                                -${{ number_format($t->amount, 2) }}
                            </span>
                        @else
                            <span class="text-zinc-800 dark:text-zinc-200">
                                ${{ number_format($t->amount, 2) }}
                            </span>
                        @endif
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        No transactions registered to your savings account.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>

        @if($transactions->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800/80 pt-4 mt-2">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-premium-card>

    <!-- Request Withdrawal Modal -->
    <div 
        x-show="showWithdrawalModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
        @keydown.escape.window="showWithdrawalModal = false"
    >
        <div 
            @click.away="showWithdrawalModal = false" 
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl max-w-lg w-full p-6 flex flex-col gap-5 animate-scaleIn"
        >
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex flex-col">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Request Savings Withdrawal</h3>
                    <span class="text-xs text-zinc-500">Calculate net payout and submit payout instructions</span>
                </div>
                <button type="button" @click="showWithdrawalModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('member.savings.withdraw') }}" class="flex flex-col gap-4">
                @csrf

                <!-- Available Balance Hint -->
                <div class="p-3 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Verified Available Balance:</span>
                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400 font-mono text-sm">
                        ${{ number_format($member->available_savings_balance, 2) }}
                    </span>
                </div>

                <!-- Gross Amount Input -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Gross Requested Amount ($)
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="1" 
                        max="{{ $member->available_savings_balance }}"
                        name="gross_amount" 
                        x-model="gross" 
                        required 
                        placeholder="e.g. 500.00"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                    />
                </div>

                <!-- Dynamic Live Calculation Card -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-950/70 border border-zinc-200/80 dark:border-zinc-800/80 rounded-xl flex flex-col gap-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400">Processing Fee (<span x-text="feePercent"></span>%):</span>
                        <span class="font-bold font-mono text-amber-600 dark:text-amber-400">-$<span x-text="feeAmount"></span></span>
                    </div>
                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-sm">
                        <span class="font-bold text-zinc-900 dark:text-white">Estimated Net Payout:</span>
                        <span class="font-black font-mono text-emerald-600 dark:text-emerald-400 text-base">$<span x-text="netAmount"></span></span>
                    </div>
                </div>

                <!-- Payout Method -->
                <div class="flex flex-col gap-1.5 w-full">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Payout Method <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="payout_method" :value="payoutMethod" required>
                    <x-dropdown align="left" width="w-full" contentClasses="p-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl flex flex-col gap-0.5">
                        <x-slot name="trigger">
                            <button 
                                type="button" 
                                class="w-full flex items-center justify-between bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/40 text-zinc-900 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left"
                            >
                                <span x-text="payoutMethod === 'zelle' ? 'Zelle' : (payoutMethod === 'bank_transfer' ? 'Direct Bank Transfer' : 'Paper Check')"></span>
                                <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-500 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <button 
                                type="button" 
                                @click="payoutMethod = 'zelle'" 
                                class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                :class="payoutMethod === 'zelle' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                            >
                                <span>Zelle</span>
                                <span x-show="payoutMethod === 'zelle'" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                            </button>
                            <button 
                                type="button" 
                                @click="payoutMethod = 'bank_transfer'" 
                                class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                :class="payoutMethod === 'bank_transfer' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                            >
                                <span>Direct Bank Transfer</span>
                                <span x-show="payoutMethod === 'bank_transfer'" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                            </button>
                            <button 
                                type="button" 
                                @click="payoutMethod = 'check'" 
                                class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                :class="payoutMethod === 'check' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                            >
                                <span>Paper Check</span>
                                <span x-show="payoutMethod === 'check'" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Payout Details -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Payout Destination & Details
                    </label>
                    <textarea 
                        name="payout_details" 
                        rows="2" 
                        required
                        :placeholder="payoutMethod === 'zelle' ? 'Enter Zelle Phone Number or Email Address' : (payoutMethod === 'bank_transfer' ? 'Bank Name, Routing Number, Account Number, Account Type' : 'Mailing address or pickup contact')"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                    ></textarea>
                </div>

                <!-- Member Notes -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Additional Notes (Optional)
                    </label>
                    <input 
                        type="text" 
                        name="notes" 
                        placeholder="Any special remarks or urgency"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3.5 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                    />
                </div>

                <!-- Submit Buttons (half-half on desktop/tablet, stacked 1-per-row on mobile) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <button 
                        type="button" 
                        @click="showWithdrawalModal = false"
                        class="w-full py-2.5 px-4 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all cursor-pointer flex items-center justify-center order-2 sm:order-1"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="w-full py-2.5 px-4 bg-zinc-950 hover:bg-zinc-900 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl shadow-xs transition-all hover:scale-[1.01] active:scale-[0.98] cursor-pointer flex items-center justify-center order-1 sm:order-2"
                    >
                        Submit Withdrawal Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

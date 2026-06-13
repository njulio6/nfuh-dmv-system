@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 select-none">
        
        <!-- Net Savings Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Savings Balance</span>
                <div class="text-2xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight mt-1">
                    ${{ number_format($member->savings_balance, 2) }}
                </div>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-2xl border border-zinc-100 dark:border-zinc-800/80 text-zinc-655 dark:text-zinc-350 shrink-0">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Loan Eligibility -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Loan Status</span>
                <div class="text-base font-display font-bold text-zinc-800 dark:text-white mt-1">
                    @if($member->savings_balance >= ($appSettings->min_savings_for_loan ?? 500))
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase inline-flex items-center gap-1 select-none">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i> Loan Eligible
                        </span>
                    @else
                        <span class="text-amber-600 dark:text-amber-500 font-bold uppercase inline-flex items-center gap-1 select-none">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i> Under Limit (${{ number_format($appSettings->min_savings_for_loan ?? 500, 0) }})
                        </span>
                    @endif
                </div>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-2xl border border-zinc-100 dark:border-zinc-800/80 text-zinc-655 dark:text-zinc-350 shrink-0">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 hover:shadow-md transition-all duration-200 flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Entries</span>
                <div class="text-2xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight mt-1">
                    {{ $transactions->total() }}
                </div>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-2xl border border-zinc-100 dark:border-zinc-800/80 text-zinc-655 dark:text-zinc-350 shrink-0">
                <i data-lucide="list-ordered" class="w-6 h-6"></i>
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
</div>


@endsection

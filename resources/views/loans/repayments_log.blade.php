@extends('layouts.app')

@section('content')
<div x-data="{}" class="w-full">
    <!-- Filter State Form -->
    <form id="repayments-filter-form" method="GET" action="{{ route('loans.repayments-log') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $repayments->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- Filters Control Bar -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-3xs mb-6">
        <!-- Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search repayments by borrower, check #, notes..."
                    value="{{ request('search') }}"
                    @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                    @blur="$refs.searchInput.value = $el.value"
                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors"
                    style="padding-left: 2.25rem;"
                />
            </div>
        </div>

        <!-- Reload / Clear Button & Back Button -->
        <div class="flex items-center gap-2">
            <a 
                href="{{ route('loans.repayments-log') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-955/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                title="Clear Search"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>
            <a 
                href="{{ route('loans.index') }}" 
                class="px-3.5 py-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-955/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none text-xs font-bold inline-flex items-center gap-1.5"
                title="Back to Loan Dashboard"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Back</span>
            </a>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-3xs overflow-x-auto mb-6">
        <x-premium-table :headers="[
            ['label' => 'Payment Date', 'width' => 'min-w-[120px]'],
            ['label' => 'Borrower', 'width' => 'min-w-[150px]'],
            ['label' => 'Repaid Amount ($)', 'width' => 'min-w-[120px]'],
            ['label' => 'Method', 'width' => 'min-w-[130px]'],
            ['label' => 'Reference Number', 'width' => 'min-w-[130px]'],
            ['label' => 'Notes', 'width' => 'min-w-[200px]']
        ]">
            @forelse($repayments as $repay)
                <x-premium-table-row :is-even="$loop->index % 2 === 1">
                    <!-- Date -->
                    <td class="py-3 px-3 font-mono text-xs text-zinc-850 dark:text-zinc-200 font-semibold">
                        {{ $repay->payment_date ? $repay->payment_date->format('Y-m-d') : '-' }}
                    </td>

                    <!-- Borrower Info -->
                    <td class="py-3 px-3">
                        @if($repay->loanRequest && $repay->loanRequest->member)
                            <div class="flex flex-col">
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $repay->loanRequest->member->name }}</span>
                                <span class="font-mono font-bold text-zinc-400 dark:text-zinc-500 text-[10px] mt-0.5">{{ $repay->loanRequest->member->member_code }}</span>
                            </div>
                        @else
                            <span class="text-zinc-400 text-xs italic">Unknown Member</span>
                        @endif
                    </td>

                    <!-- Repaid Amount -->
                    <td class="py-3 px-3">
                        <span class="font-extrabold text-zinc-950 dark:text-white text-sm">${{ number_format($repay->amount, 2) }}</span>
                    </td>

                    <!-- Method -->
                    <td class="py-3 px-3 capitalize text-zinc-700 dark:text-zinc-300 text-xs font-semibold">
                        {{ str_replace('_', ' ', $repay->payment_method) }}
                    </td>

                    <!-- Reference Number -->
                    <td class="py-3 px-3 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $repay->reference_number ?: '-' }}
                    </td>

                    <!-- Notes -->
                    <td class="py-3 px-3 text-zinc-500 dark:text-zinc-400 text-xs max-w-[220px] truncate" title="{{ $repay->notes }}">
                        {{ $repay->notes ?: '-' }}
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-zinc-400 dark:text-zinc-650 py-16">
                        No repayment transactions found.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </div>

    <!-- Pagination Footer -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-3xs mb-6">
        <!-- Rows count -->
        <div class="flex items-center gap-3">
            <span class="text-[11px] font-black uppercase text-zinc-550 dark:text-zinc-400">
                Rows Per Page
            </span>
            <div class="flex items-center gap-1.5">
                @foreach([5, 10, 20, 30, 50] as $size)
                    @php
                        $isActive = request('per_page', 10) == $size;
                    @endphp
                    <button
                        type="button"
                        @click="$refs.perPageInput.value = {{ $size }}; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer
                            {{ $isActive 
                                ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                                : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                            }}"
                    >
                        {{ $size }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Pages control -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <button
                type="button"
                @click="$refs.pageInput.value = 1; $refs.form.submit()"
                @if($repayments->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <button
                type="button"
                @if($repayments->onFirstPage()) disabled @endif
                @if(!$repayments->onFirstPage())
                    @click="$refs.pageInput.value = {{ $repayments->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-955 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            @php
                $startPage = max(1, $repayments->currentPage() - 2);
                $endPage = min($repayments->lastPage(), $repayments->currentPage() + 2);
            @endphp
            @for ($page = $startPage; $page <= $endPage; $page++)
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                    class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                        {{ $page == $repayments->currentPage()
                            ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                            : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                        }}"
                >
                    {{ $page }}
                </button>
            @endfor

            <button
                type="button"
                @if($repayments->hasMorePages())
                    @click="$refs.pageInput.value = {{ $repayments->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$repayments->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-955 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <button
                type="button"
                @click="$refs.pageInput.value = {{ $repayments->lastPage() }}; $refs.form.submit()"
                @if(!$repayments->hasMorePages()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-955 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                Last
            </button>
        </div>
    </div>
</div>
@endsection

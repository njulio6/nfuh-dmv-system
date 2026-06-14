@extends('layouts.app')

@section('content')
<div 
    x-data="{ 
        showRepayModal: false, 
        loanId: '',
        loanAmount: '',
        loanRemaining: '',
        loanBorrower: '',
        showFilterModal: false,
        showReviewModal: false,
        reviewActionUrl: '',
        reviewType: 'approve',
        reviewBorrower: '',
        reviewAmount: '',
        reviewNotes: '',
        openReviewModal(actionUrl, type, borrower, amount) {
            this.reviewActionUrl = actionUrl;
            this.reviewType = type;
            this.reviewBorrower = borrower;
            this.reviewAmount = amount;
            this.reviewNotes = '';
            this.showReviewModal = true;
        }
    }"
    class="w-full"
>
    <!-- Filter State Form -->
    <form id="loans-filter-form" method="GET" action="{{ route('loans.index') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="status" value="{{ request('status') }}" x-ref="statusInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $loans->currentPage() }}" x-ref="pageInput">
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Active Loans -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex items-center gap-4">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Active Disbursements</span>
                <span class="text-xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($totalActiveAmount, 2) }}
                </span>
            </div>
        </div>

        <!-- Total Outstanding -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex items-center gap-4">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Total Outstanding Principal</span>
                <span class="text-xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($totalRemainingBalance, 2) }}
                </span>
            </div>
        </div>

        <!-- Pending Committee Reviews -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex items-center gap-4">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Pending Committee Reviews</span>
                <span class="text-xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    {{ $pendingReviewsCount }} Requests
                </span>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-3xs mb-6">
        <!-- Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search borrower by ID, name..."
                    value="{{ request('search') }}"
                    @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                    @blur="$refs.searchInput.value = $el.value"
                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors"
                    style="padding-left: 2.25rem;"
                />
            </div>
        </div>

        <!-- Right Side Button Controls -->
        <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:justify-end">

            <!-- Clear Filter / Reload Button -->
            <a 
                href="{{ route('loans.index') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                title="Reload & Clear Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>

            <!-- Status Trigger Filter Modal -->
            <button 
                type="button"
                @click="showFilterModal = true"
                class="p-2.5 border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative select-none {{ request('status') ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                title="Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                @if(request('status'))
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-3xs overflow-hidden mb-6">
        <x-premium-table :headers="[
            'Borrower',
            'Amount ($)',
            'Duration',
            'Status',
            'Admin Note',
            'Repay Progress',
            'Statement',
            ['label' => 'Actions', 'align' => 'center']
        ]">
            @forelse($loans as $loan)
                <x-premium-table-row :is-even="$loop->index % 2 === 1">
                    <!-- Borrower Info -->
                    <td class="py-3.5 px-3">
                        <div class="flex flex-col">
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $loan->member->name }}</span>
                            <span class="font-mono font-bold text-zinc-400 dark:text-zinc-500 text-[10px] mt-0.5">{{ $loan->member->member_code }}</span>
                        </div>
                    </td>

                    <!-- Loan Amount -->
                    <td class="py-3.5 px-3">
                        <div class="flex flex-col">
                            <span class="font-extrabold text-zinc-950 dark:text-white text-sm">${{ number_format($loan->amount, 2) }}</span>
                            @if($loan->status === 'active' || $loan->status === 'completed')
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">Owed: ${{ number_format($loan->remaining_balance, 2) }}</span>
                            @endif
                        </div>
                    </td>

                    <!-- Duration / Terms -->
                    <td class="py-3.5 px-3">
                        <span class="font-bold text-zinc-700 dark:text-zinc-300 text-xs">{{ $loan->duration_months }} Months</span>
                    </td>

                    <!-- Status Badge -->
                    <td class="py-3.5 px-3">
                        <div class="flex flex-col items-start gap-1">
                            @if($loan->status === 'pending_guarantors')
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-400 border border-zinc-200/60 dark:border-zinc-700/60">
                                    Guarantor Review
                                </span>
                            @elseif($loan->status === 'pending_committee')
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800/40">
                                    Committee Review
                                </span>
                            @elseif($loan->status === 'approved')
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/40">
                                    Approved
                                </span>
                            @elseif($loan->status === 'active')
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                    Active
                                </span>
                            @elseif($loan->status === 'completed')
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-950 text-white dark:bg-white dark:text-zinc-950 border border-zinc-900/60 dark:border-zinc-300/40">
                                    Completed
                                </span>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                    Rejected
                                </span>
                            @endif
                        </div>
                    </td>

                    <!-- Admin Note -->
                    <td class="py-3.5 px-3 max-w-[160px]">
                        @if($loan->admin_notes)
                            <div class="flex items-start gap-1">
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 italic leading-relaxed" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    "{{ $loan->admin_notes }}"
                                </span>
                                @if(strlen($loan->admin_notes) > 60)
                                    <span title="{{ $loan->admin_notes }}" class="cursor-help flex-shrink-0 mt-0.5 text-zinc-400 dark:text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    </span>
                                @endif
                            </div>
                        @else
                            <span class="text-zinc-300 dark:text-zinc-700 text-xs">—</span>
                        @endif
                    </td>

                    <!-- Repayment Progress -->
                    <td class="py-3.5 px-3">
                        @if($loan->status === 'active' || $loan->status === 'completed')
                            @php
                                $repaidAmount = $loan->amount - $loan->remaining_balance;
                                $percentage = $loan->amount > 0 ? min(100, max(0, ($repaidAmount / $loan->amount) * 100)) : 0;
                            @endphp
                            <div class="flex items-center gap-2 w-32">
                                <div class="w-full bg-zinc-105 dark:bg-zinc-950 rounded-full h-1 overflow-hidden border border-zinc-200/20 dark:border-zinc-800/40">
                                    <div class="bg-zinc-950 dark:bg-zinc-50 h-full rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-zinc-500">{{ number_format($percentage, 0) }}%</span>
                            </div>
                        @else
                            <span class="text-zinc-400 text-xs">-</span>
                        @endif
                    </td>

                    <!-- Statement View -->
                    <td class="py-3.5 px-3">
                        <a 
                            href="{{ route('loans.statement', $loan->member_id) }}" 
                            target="_blank"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-zinc-650 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white hover:underline transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span>Statement</span>
                        </a>
                    </td>

                    <!-- Action Controls -->
                    <td class="py-3.5 px-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                             @if($loan->status === 'pending_committee')
                                 <!-- Approve request -->
                                 <button 
                                     type="button" 
                                     @click="openReviewModal('{{ route('loans.approve', $loan->id) }}', 'approve', '{{ addslashes($loan->member->name) }}', '{{ $loan->amount }}')"
                                     class="px-2.5 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-955 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center select-none active:scale-[0.96]"
                                 >
                                     Approve
                                 </button>

                                 <!-- Reject request -->
                                 <button 
                                     type="button" 
                                     @click="openReviewModal('{{ route('loans.reject', $loan->id) }}', 'reject', '{{ addslashes($loan->member->name) }}', '{{ $loan->amount }}')"
                                     class="px-2.5 py-1.5 border border-red-200 dark:border-red-950/60 bg-red-50 hover:bg-red-100 dark:bg-red-950/10 text-red-650 dark:text-red-400 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center select-none active:scale-[0.96]"
                                 >
                                     Reject
                                 </button>
                            @elseif($loan->status === 'approved')
                                <!-- Disburse Funds -->
                                <form method="POST" action="{{ route('loans.disburse', $loan->id) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center gap-1 select-none active:scale-[0.96]">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                        Disburse
                                    </button>
                                </form>
                            @elseif($loan->status === 'active')
                                <!-- Post manual repayment -->
                                <button 
                                    @click="
                                        loanId = '{{ $loan->id }}';
                                        loanAmount = '{{ $loan->amount }}';
                                        loanRemaining = '{{ $loan->remaining_balance }}';
                                        loanBorrower = '{{ $loan->member->name }}';
                                        showRepayModal = true;
                                    " 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-[11px] font-bold rounded-[8px] transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer shadow-3xs select-none"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 text-zinc-500"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    <span>Log Repay</span>
                                </button>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-600 text-xs">-</span>
                            @endif
                        </div>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-zinc-400 dark:text-zinc-650 py-16">
                        No loans found matching the filters.
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
                @if($loans->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <button
                type="button"
                @if(!$loans->onFirstPage())
                    @click="$refs.pageInput.value = {{ $loans->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                @if($loans->onFirstPage()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            @php
                $startPage = max(1, $loans->currentPage() - 2);
                $endPage = min($loans->lastPage(), $loans->currentPage() + 2);
            @endphp
            @for ($page = $startPage; $page <= $endPage; $page++)
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                    class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                        {{ $page == $loans->currentPage()
                            ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                            : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                        }}"
                >
                    {{ $page }}
                </button>
            @endfor

            <button
                type="button"
                @if($loans->hasMorePages())
                    @click="$refs.pageInput.value = {{ $loans->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$loans->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-955 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <button
                type="button"
                @click="$refs.pageInput.value = {{ $loans->lastPage() }}; $refs.form.submit()"
                @if(!$loans->hasMorePages()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                Last
            </button>
        </div>
    </div>

    <!-- LOG MANUAL REPAYMENT MODAL -->
    <div 
        x-show="showRepayModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Overlay Backing -->
        <div 
            @click="showRepayModal = false" 
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"
            x-show="showRepayModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showRepayModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-2xl max-w-md w-full p-6 relative z-10"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-5">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider">Log Manual Repayment</h3>
                <button @click="showRepayModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="mb-4 bg-zinc-50 dark:bg-zinc-950/40 p-3.5 rounded-xl border border-zinc-200/40 dark:border-zinc-800/40 text-xs">
                <div class="flex justify-between mb-1">
                    <span class="text-zinc-400">Borrower:</span>
                    <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="loanBorrower"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">Outstanding Balance:</span>
                    <span class="font-bold text-zinc-950 dark:text-white" x-text="'$' + parseFloat(loanRemaining).toFixed(2)"></span>
                </div>
            </div>

            <!-- Form -->
            <form :action="'{{ url('/loans') }}/' + loanId + '/repay'" method="POST" class="flex flex-col gap-5">
                @csrf

                <!-- Amount -->
                <x-premium-input 
                    label="Repayment Amount ($)" 
                    name="amount" 
                    type="number" 
                    step="0.01" 
                    min="0.01"
                    ::max="loanRemaining"
                    required 
                    placeholder="e.g. 200.00" 
                />

                <!-- Payment Date -->
                <x-premium-datepicker 
                    label="Payment Date" 
                    name="payment_date" 
                    required 
                    value="{{ date('Y-m-d') }}"
                />

                <!-- Payment Method -->
                <div 
                    x-data="{ 
                        isOpen: false, 
                        selectedMethod: 'zelle', 
                        methods: [
                            { value: 'zelle', label: 'Zelle Transfer' },
                            { value: 'cash', label: 'Cash Payment' },
                            { value: 'njangi_deduction', label: 'Njangi Disbursement Deduction' },
                            { value: 'check', label: 'Check' },
                            { value: 'other', label: 'Other' }
                        ],
                        get label() {
                            return this.methods.find(m => m.value === this.selectedMethod)?.label || '';
                        }
                    }"
                    class="flex flex-col w-full relative"
                    @click.outside="isOpen = false"
                >
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="payment_method" :value="selectedMethod" required />

                    <div class="relative w-full">
                        <button 
                            type="button"
                            @click="isOpen = !isOpen"
                            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                            :class="isOpen ? 'border-zinc-950 dark:border-zinc-50 bg-white dark:bg-zinc-900' : ''"
                        >
                            <span x-text="label" class="truncate"></span>
                            <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-500 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Options List -->
                        <div 
                            x-show="isOpen"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-50 mt-1.5 w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl p-1.5 flex flex-col gap-0.5"
                            style="display: none;"
                        >
                            <template x-for="item in methods" :key="item.value">
                                <button
                                    type="button"
                                    @click="selectedMethod = item.value; isOpen = false"
                                    class="w-full text-left px-3 py-2.5 text-xs font-semibold rounded-lg transition-colors flex items-center justify-between cursor-pointer"
                                    :class="selectedMethod === item.value ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-650 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-900 dark:hover:text-zinc-200'"
                                >
                                    <span x-text="item.label"></span>
                                    <span x-show="selectedMethod === item.value" class="text-zinc-950 dark:text-white font-black">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>


                <!-- Reference Number -->
                <x-premium-input 
                    label="Reference Number (Optional)" 
                    name="reference_number" 
                    type="text" 
                    placeholder="e.g. Transaction ID, Check #" 
                />

                <!-- Notes -->
                <x-premium-textarea 
                    label="Internal Notes (Optional)" 
                    name="notes" 
                    rows="3" 
                    placeholder="Add repayment receipt details or memo..." 
                />

                <div class="flex gap-2 pt-2">
                    <x-premium-button type="button" variant="secondary" class="flex-1 py-2.5" @click="showRepayModal = false">
                        Cancel
                    </x-premium-button>
                    <x-premium-button type="submit" variant="primary" class="flex-1 py-2.5">
                        Log Payment
                    </x-premium-button>
                </div>
            </form>
        </div>
    </div>

    <!-- FILTER STATE SELECTOR MODAL -->
    <div 
        x-show="showFilterModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Overlay Backing -->
        <div 
            @click="showFilterModal = false" 
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"
            x-show="showFilterModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showFilterModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-2xl max-w-xs w-full p-5 relative z-10"
        >
            <div class="flex justify-between items-center pb-3 border-b border-zinc-100 dark:border-zinc-800 mb-4">
                <h3 class="text-xs font-black text-zinc-950 dark:text-white uppercase tracking-wider">Filter Loans</h3>
                <button @click="showFilterModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4.5 h-4.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Filter select -->
            <div class="flex flex-col gap-4">
                <x-premium-select 
                    label="Filter Status" 
                    id="filter-status-select" 
                    @change="$refs.statusInput.value = $el.value"
                >
                    <option value="">All Loan Statuses</option>
                    <option value="pending_guarantors" {{ request('status') === 'pending_guarantors' ? 'selected' : '' }}>Pending Guarantors</option>
                    <option value="pending_committee" {{ request('status') === 'pending_committee' ? 'selected' : '' }}>Pending Committee</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </x-premium-select>

                <div class="flex gap-2 pt-2">
                    <x-premium-button type="button" variant="secondary" class="flex-1 py-2 text-xs" @click="showFilterModal = false">
                        Cancel
                    </x-premium-button>
                    <x-premium-button type="button" variant="primary" class="flex-1 py-2 text-xs" @click="$refs.pageInput.value = 1; $refs.form.submit()">
                        Apply
                    </x-premium-button>
                </div>
            </div>
        </div>
    </div>

    <!-- ADMIN LOAN DECISION (APPROVE/REJECT) MODAL -->
    <div 
        x-show="showReviewModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Overlay Backing -->
        <div 
            @click="showReviewModal = false" 
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"
            x-show="showReviewModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showReviewModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-2xl max-w-md w-full p-6 relative z-10"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-5">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider" x-text="reviewType === 'approve' ? 'Approve Loan Request' : 'Reject Loan Request'"></h3>
                <button @click="showReviewModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-250">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form method="POST" :action="reviewActionUrl" class="flex flex-col gap-5">
                @csrf
                
                <div class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                    <template x-if="reviewType === 'approve'">
                        <p>
                            Are you sure you want to approve the loan request of <strong class="text-zinc-900 dark:text-white font-bold" x-text="reviewBorrower"></strong> for a loan of <strong class="text-zinc-955 dark:text-white font-extrabold text-sm" x-text="'$' + Number(reviewAmount).toLocaleString('en-US', {minimumFractionDigits: 2})"></strong>?
                        </p>
                    </template>
                    <template x-if="reviewType === 'reject'">
                        <p class="text-red-600 dark:text-red-400 font-medium">
                            Are you sure you want to reject the loan request of <strong class="text-zinc-900 dark:text-white font-bold" x-text="reviewBorrower"></strong> for a loan of <strong class="text-zinc-955 dark:text-white font-extrabold text-sm" x-text="'$' + Number(reviewAmount).toLocaleString('en-US', {minimumFractionDigits: 2})"></strong>?
                        </p>
                    </template>
                </div>

                <div>
                    <label for="review_notes" class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 block">
                        Decision Comments / Reason <span class="text-zinc-400 dark:text-zinc-500 font-medium">(Optional)</span>
                    </label>
                    <textarea 
                        id="review_notes"
                        name="notes"
                        x-model="reviewNotes"
                        placeholder="Add comments or rejection reasons here..."
                        class="w-full h-24 bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-650 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs p-3 focus:outline-none focus:border-zinc-450 dark:focus:border-zinc-750 transition-all outline-none"
                    ></textarea>
                </div>

                <div class="flex gap-2 pt-2">
                    <x-premium-button type="button" variant="secondary" class="flex-1 py-2.5" @click="showReviewModal = false">
                        Cancel
                    </x-premium-button>
                    <button 
                        type="submit" 
                        class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-center shadow-xs active:scale-95"
                        :class="reviewType === 'approve' ? 'bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950' : 'bg-red-650 hover:bg-red-750 text-white dark:text-white'"
                        x-text="reviewType === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'"
                    >
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@extends('layouts.app')

@section('content')
<div 
    x-data="{ 
        showRepayModal: false, 
        loanId: '',
        loanAmount: '',
        loanRemaining: '',
        loanBorrower: '',
        showReviewModal: false,
        reviewActionUrl: '',
        reviewType: 'approve',
        reviewBorrower: '',
        reviewAmount: '',
        reviewNotes: '',
        showDefaultModal: false,
        showRestoreModal: false,
        statusActionUrl: '',
        statusBorrower: '',
        statusAmount: '',
        openReviewModal(actionUrl, type, borrower, amount) {
            this.reviewActionUrl = actionUrl;
            this.reviewType = type;
            this.reviewBorrower = borrower;
            this.reviewAmount = amount;
            this.reviewNotes = '';
            this.showReviewModal = true;
        },
        openStatusModal(actionUrl, isDefault, borrower, amount) {
            this.statusActionUrl = actionUrl;
            this.statusBorrower = borrower;
            this.statusAmount = amount;
            if (isDefault) {
                this.showDefaultModal = true;
            } else {
                this.showRestoreModal = true;
            }
        }
    }"
    class="w-full"
>
    <!-- Filter State Form -->
    <form id="loans-filter-form" method="GET" action="{{ route('loans.status-list', $status) }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $loans->currentPage() }}" x-ref="pageInput">
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
                    placeholder="Search borrower by ID, name..."
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
                href="{{ route('loans.status-list', $status) }}" 
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
        @php
            $headers = [
                ['label' => 'Borrower', 'width' => 'min-w-[140px]'],
                ['label' => 'Amount ($)', 'width' => 'min-w-[110px]'],
                ['label' => 'Duration', 'width' => 'min-w-[90px]'],
            ];

            if ($status === 'pending_guarantors') {
                $headers[] = ['label' => 'Guarantors Signatures', 'width' => 'min-w-[200px]'];
            } elseif ($status === 'pending_committee') {
                $headers[] = ['label' => 'Purpose', 'width' => 'min-w-[180px]'];
            } elseif ($status === 'active' || $status === 'defaulted' || $status === 'completed') {
                $headers[] = ['label' => 'Repay Progress', 'width' => 'min-w-[140px]'];
                $headers[] = ['label' => 'Tag/Sub-status', 'width' => 'min-w-[130px]'];
                $headers[] = ['label' => 'Statement', 'width' => 'min-w-[90px]'];
            } elseif ($status === 'rejected') {
                $headers[] = ['label' => 'Rejection Notes', 'width' => 'min-w-[180px]'];
            }

            if (in_array($status, ['pending_committee', 'approved', 'active', 'defaulted'])) {
                $headers[] = ['label' => 'Actions', 'align' => 'center', 'width' => 'min-w-[160px]'];
            }
        @endphp

        <x-premium-table :headers="$headers">
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
                            @if(in_array($status, ['active', 'defaulted', 'completed']))
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">Owed: ${{ number_format($loan->remaining_balance, 2) }}</span>
                            @endif
                        </div>
                    </td>

                    <!-- Duration / Terms -->
                    <td class="py-3.5 px-3">
                        <span class="font-bold text-zinc-700 dark:text-zinc-300 text-xs">{{ $loan->duration_months }} Months</span>
                    </td>

                    <!-- Status Specific Columns -->
                    @if($status === 'pending_guarantors')
                        <!-- Guarantor Signatures List -->
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <div class="flex flex-col gap-1 text-[10px]">
                                @foreach($loan->guarantors as $g)
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full 
                                            {{ $g->status === 'approved' ? 'bg-emerald-500' : ($g->status === 'declined' ? 'bg-red-500' : 'bg-zinc-400') }}">
                                        </span>
                                        <span class="font-semibold text-zinc-800 dark:text-zinc-250">{{ $g->guarantorMember->name }}</span>
                                        <span class="text-zinc-400 uppercase font-bold text-[8px]">({{ $g->status }})</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    @elseif($status === 'pending_committee')
                        <!-- Purpose -->
                        <td class="py-3.5 px-3 max-w-[200px] truncate text-zinc-700 dark:text-zinc-300 text-xs italic" title="{{ $loan->purpose }}">
                            "{{ $loan->purpose ?: 'No purpose specified' }}"
                        </td>
                    @elseif(in_array($status, ['active', 'defaulted', 'completed']))
                        <!-- Repayment Progress -->
                        <td class="py-3.5 px-3">
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
                        </td>

                        <!-- Tag / Custom Sub-status -->
                        <td class="py-3.5 px-3">
                            @if(in_array($status, ['active', 'defaulted']))
                                <div x-data="{ open: false, openUp: false, dropdownTop: 0, dropdownLeft: 0 }" class="relative inline-block text-left" @click.outside="open = false" @scroll.window="open = false">
                                    @if($loan->subStatus)
                                        @php
                                            $subStatusColorClass = match($loan->subStatus->color) {
                                                'red' => 'bg-red-50 text-red-700 dark:bg-red-955/20 dark:text-red-400 border-red-200/60 dark:border-red-800/40 hover:bg-red-100/40 dark:hover:bg-red-955/30',
                                                'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-955/20 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/40 hover:bg-amber-100/40 dark:hover:bg-amber-955/30',
                                                'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-955/20 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40 hover:bg-emerald-100/40 dark:hover:bg-emerald-955/30',
                                                'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-955/20 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/40 hover:bg-blue-100/40 dark:hover:bg-blue-955/30',
                                                'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-955/20 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/40 hover:bg-indigo-100/40 dark:hover:bg-indigo-955/30',
                                                default => 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 border-zinc-200/60 dark:border-zinc-700/60 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/40',
                                            };
                                        @endphp
                                        <button 
                                            type="button" 
                                            @click="open = !open; if (open) { $nextTick(() => { const rect = $el.getBoundingClientRect(); openUp = (window.innerHeight - rect.bottom) < 220; dropdownLeft = rect.left; if (openUp) { dropdownTop = rect.top - $refs.menu.offsetHeight - 6; } else { dropdownTop = rect.bottom + 6; } }) }"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border cursor-pointer select-none transition-all duration-200 shadow-3xs {{ $subStatusColorClass }}"
                                        >
                                            <span>{{ $loan->subStatus->name }}</span>
                                            <svg class="w-2.5 h-2.5 shrink-0 transition-transform duration-250 opacity-60" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                                        </button>
                                    @else
                                        <!-- Plus button for assigning tag -->
                                        <button 
                                            type="button" 
                                            @click="open = !open; if (open) { $nextTick(() => { const rect = $el.getBoundingClientRect(); openUp = (window.innerHeight - rect.bottom) < 220; dropdownLeft = rect.left; if (openUp) { dropdownTop = rect.top - $refs.menu.offsetHeight - 6; } else { dropdownTop = rect.bottom + 6; } }) }"
                                            class="inline-flex items-center justify-center size-5 rounded-full border border-dashed border-zinc-300 dark:border-zinc-700 bg-transparent text-zinc-400 dark:text-zinc-500 hover:text-zinc-650 dark:hover:text-zinc-300 hover:bg-zinc-100/50 dark:hover:bg-zinc-850/50 hover:border-zinc-450 dark:hover:border-zinc-600 transition-all duration-200 cursor-pointer shadow-3xs"
                                            title="Add Sub-Status Tag"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" class="w-2.5 h-2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        </button>
                                    @endif

                                    <!-- Dropdown Options Menu -->
                                    <div 
                                        x-ref="menu"
                                        x-show="open"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="fixed z-50 w-44 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl p-1.5 flex flex-col gap-0.5"
                                        :style="`top: ${dropdownTop}px; left: ${dropdownLeft}px;`"
                                        style="display: none;"
                                    >
                                        <div class="text-[8px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-2.5 py-1 select-none">
                                            Select Tag
                                        </div>
                                        
                                        <form method="POST" action="{{ route('loans.update-sub-status', $loan->id) }}" id="subStatusForm{{ $loan->id }}" class="m-0 p-0">
                                            @csrf
                                            <input type="hidden" name="sub_status_id" id="sub_status_id_{{ $loan->id }}" value="">
                                            
                                            <!-- Option: No Tag -->
                                            <button
                                                type="button"
                                                @click="document.getElementById('sub_status_id_{{ $loan->id }}').value = ''; document.getElementById('subStatusForm{{ $loan->id }}').submit()"
                                                class="w-full text-left px-2.5 py-2 text-[10px] font-bold uppercase rounded-lg transition-colors flex items-center justify-between cursor-pointer border border-transparent {{ !$loan->sub_status_id ? 'bg-zinc-50 dark:bg-zinc-900 text-zinc-955 dark:text-white' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 hover:text-zinc-900 dark:hover:text-zinc-250' }}"
                                            >
                                                <span>No Tag</span>
                                                @if(!$loan->sub_status_id)
                                                    <span class="text-zinc-955 dark:text-white">✓</span>
                                                @endif
                                            </button>
                                            
                                            @foreach($subStatuses as $ss)
                                                @php
                                                    $optionBadgeColor = match($ss->color) {
                                                        'red' => 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border-red-200/60 dark:border-red-800/40',
                                                        'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/40',
                                                        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40',
                                                        'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-955/20 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/40',
                                                        'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/40',
                                                        default => 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 border-zinc-200/60 dark:border-zinc-700/60',
                                                    };
                                                @endphp
                                                <button
                                                    type="button"
                                                    @click="document.getElementById('sub_status_id_{{ $loan->id }}').value = '{{ $ss->id }}'; document.getElementById('subStatusForm{{ $loan->id }}').submit()"
                                                    class="w-full text-left px-2.5 py-1.5 text-[10px] font-bold uppercase rounded-lg transition-colors flex items-center justify-between cursor-pointer border border-transparent {{ $loan->sub_status_id == $ss->id ? 'bg-zinc-50 dark:bg-zinc-900 text-zinc-955 dark:text-white' : 'text-zinc-550 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 hover:text-zinc-900 dark:hover:text-zinc-250' }}"
                                                >
                                                    <span class="inline-block px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider border {{ $optionBadgeColor }}">{{ $ss->name }}</span>
                                                    @if($loan->sub_status_id == $ss->id)
                                                        <span class="text-zinc-955 dark:text-white">✓</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="print-badge inline-block px-2.5 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 text-[9px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    {{ $loan->subStatus->name ?? '-' }}
                                </span>
                            @endif
                        </td>

                        <!-- Statement -->
                        <td class="py-3.5 px-3">
                            <a 
                                href="{{ route('loans.statement', $loan->id) }}" 
                                target="_blank"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-zinc-650 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white hover:underline transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                <span>Statement</span>
                            </a>
                        </td>
                    @elseif($status === 'rejected')
                        <!-- Rejection Note -->
                        <td class="py-3.5 px-3 max-w-[220px] truncate text-red-600 dark:text-red-400 text-xs italic" title="{{ $loan->admin_notes }}">
                            "{{ $loan->admin_notes ?: 'No rejection note recorded' }}"
                        </td>
                    @endif

                    <!-- Action Controls -->
                    @if(in_array($status, ['pending_committee', 'approved', 'active', 'defaulted']))
                        <td class="py-3.5 px-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                 @if($status === 'pending_committee')
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
                                         class="px-2.5 py-1.5 border border-red-200 dark:border-red-950/60 bg-red-50 hover:bg-red-100 dark:bg-red-955/10 text-red-650 dark:text-red-400 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center select-none active:scale-[0.96]"
                                     >
                                         Reject
                                     </button>
                                @elseif($status === 'approved')
                                    <!-- Disburse Funds -->
                                    <form method="POST" action="{{ route('loans.disburse', $loan->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-zinc-955 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center gap-1 select-none active:scale-[0.96]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                            Disburse
                                        </button>
                                    </form>
                                @elseif($status === 'active')
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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-zinc-550"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        <span>Log Repay</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="openStatusModal('{{ route('loans.mark-defaulted', $loan->id) }}', true, '{{ addslashes($loan->member->name) }}', '{{ $loan->amount }}')"
                                        class="px-2.5 py-1.5 border border-red-200 dark:border-red-950/60 bg-red-50 hover:bg-red-100 dark:bg-red-950/10 text-red-655 dark:text-red-400 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center select-none active:scale-[0.96]"
                                        title="Mark Loan as Defaulted"
                                    >
                                        Mark Defaulted
                                    </button>
                                @elseif($status === 'defaulted')
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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-zinc-550"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        <span>Log Repay</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="openStatusModal('{{ route('loans.mark-active', $loan->id) }}', false, '{{ addslashes($loan->member->name) }}', '{{ $loan->amount }}')"
                                        class="px-2.5 py-1.5 border border-emerald-200 dark:border-emerald-950/60 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/10 text-emerald-655 dark:text-emerald-400 text-[11px] font-bold rounded-[8px] cursor-pointer shadow-3xs transition-all flex items-center justify-center select-none active:scale-[0.96]"
                                        title="Restore Loan to Active"
                                    >
                                        Restore Active
                                    </button>
                                @endif
                            </div>
                        </td>
                    @endif
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-zinc-400 dark:text-zinc-650 py-16">
                        No loans found in this queue.
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
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-955 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
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
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-955 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
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
                <h3 class="text-sm font-black text-zinc-955 dark:text-white uppercase tracking-wider">Log Manual Repayment</h3>
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
                    <span class="font-bold text-zinc-955 dark:text-white" x-text="'$' + parseFloat(loanRemaining).toFixed(2)"></span>
                </div>
            </div>

            <form :action="'{{ url('/loans') }}/' + loanId + '/repay'" method="POST" class="flex flex-col gap-5">
                @csrf
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

                <x-premium-datepicker 
                    label="Payment Date" 
                    name="payment_date" 
                    required 
                    value="{{ date('Y-m-d') }}"
                />

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
                    <input type="hidden" name="payment_method" :value="selectedMethod">
                    
                    <button 
                        type="button" 
                        @click="isOpen = !isOpen"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white pl-3.5 pr-10 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none transition-colors text-left flex items-center justify-between shadow-2xs select-none cursor-pointer"
                    >
                        <span x-text="label"></span>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200 opacity-60" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div 
                        x-show="isOpen" 
                        x-transition
                        class="absolute z-55 w-full bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl p-1.5 flex flex-col gap-0.5 mt-1 top-full"
                        style="display: none;"
                    >
                        <template x-for="method in methods">
                            <button 
                                type="button" 
                                @click="selectedMethod = method.value; isOpen = false"
                                class="w-full text-left px-3.5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer select-none"
                                :class="selectedMethod === method.value ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900'"
                                x-text="method.label"
                            ></button>
                        </template>
                    </div>
                </div>

                <x-premium-input 
                    label="Reference Number (Zelle ID/Check #)" 
                    name="reference_number" 
                    type="text" 
                    placeholder="e.g. TXN10293847" 
                />

                <x-premium-textarea 
                    label="Transaction Memo / Repayment Notes" 
                    name="notes" 
                    rows="3" 
                    placeholder="Add manual payment notes or ledger adjustments details..." 
                />

                <div class="flex gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/60">
                    <button type="button" class="flex-1 py-2.5 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors select-none" @click="showRepayModal = false">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl transition-all cursor-pointer shadow-xs active:scale-95 text-center select-none">
                        Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DECISION REVIEW MODAL (Approve / Reject review note) -->
    <div 
        x-show="showReviewModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div @click="showReviewModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>

        <div 
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-2xl max-w-md w-full p-6 relative z-10"
            x-show="showReviewModal"
            x-transition
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-4">
                <h3 class="text-sm font-black text-zinc-955 dark:text-white uppercase tracking-wider" x-text="reviewType === 'approve' ? 'Approve Loan Request' : 'Reject Loan Request'"></h3>
                <button @click="showReviewModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="mb-4 bg-zinc-50 dark:bg-zinc-950/40 p-3.5 rounded-xl border border-zinc-200/40 dark:border-zinc-800/40 text-xs">
                <div class="flex justify-between mb-1">
                    <span class="text-zinc-400">Borrower:</span>
                    <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="reviewBorrower"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400">Requested Amount:</span>
                    <span class="font-bold text-zinc-955 dark:text-white" x-text="'$' + parseFloat(reviewAmount).toFixed(2)"></span>
                </div>
            </div>

            <form :action="reviewActionUrl" method="POST" class="flex flex-col gap-4">
                @csrf
                <x-premium-textarea 
                    label="Decision Notes / Rejection Reason" 
                    name="notes" 
                    rows="3" 
                    x-model="reviewNotes"
                    placeholder="Enter decision comments or review details..." 
                />

                <div class="flex gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/60">
                    <button type="button" class="flex-1 py-2.5 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors select-none" @click="showReviewModal = false">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all cursor-pointer shadow-xs active:scale-95 text-center select-none"
                        :class="reviewType === 'approve' 
                            ? 'bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950' 
                            : 'bg-red-600 hover:bg-red-500 text-white'"
                        x-text="reviewType === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'"
                    >
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DEFAULTING ALERT MODALS -->
    <!-- Mark Defaulted Modal -->
    <div x-show="showDefaultModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div @click="showDefaultModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-2xl max-w-sm w-full p-6 relative z-10 text-center flex flex-col gap-4" x-show="showDefaultModal" x-transition>
            <div class="mx-auto size-12 rounded-full bg-red-100 dark:bg-red-950/20 text-red-600 dark:text-red-400 flex items-center justify-center border border-red-200/50">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-black text-zinc-955 dark:text-white uppercase tracking-wider mb-1">Mark as Defaulted?</h3>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 leading-normal">
                    Are you sure you want to mark the active loan of <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="statusBorrower"></span> ($<span x-text="parseFloat(statusAmount).toFixed(2)"></span>) as <strong class="text-red-600 dark:text-red-400">Defaulted</strong>?
                </p>
            </div>
            <form :action="statusActionUrl" method="POST" class="flex gap-3 mt-2">
                @csrf
                <button type="button" class="flex-1 py-2 text-xs font-bold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 select-none" @click="showDefaultModal = false">Cancel</button>
                <button type="submit" class="flex-1 py-2 text-xs font-bold bg-red-650 hover:bg-red-600 text-white rounded-lg select-none">Yes, Default</button>
            </form>
        </div>
    </div>

    <!-- Restore Active Modal -->
    <div x-show="showRestoreModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div @click="showRestoreModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-2xl max-w-sm w-full p-6 relative z-10 text-center flex flex-col gap-4" x-show="showRestoreModal" x-transition>
            <div class="mx-auto size-12 rounded-full bg-emerald-100 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-200/50">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-black text-zinc-955 dark:text-white uppercase tracking-wider mb-1">Restore Active Status?</h3>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 leading-normal">
                    Are you sure you want to restore the defaulted loan of <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="statusBorrower"></span> ($<span x-text="parseFloat(statusAmount).toFixed(2)"></span>) back to <strong class="text-emerald-650 dark:text-emerald-400">Active</strong>?
                </p>
            </div>
            <form :action="statusActionUrl" method="POST" class="flex gap-3 mt-2">
                @csrf
                <button type="button" class="flex-1 py-2 text-xs font-bold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 select-none" @click="showRestoreModal = false">Cancel</button>
                <button type="submit" class="flex-1 py-2 text-xs font-bold bg-emerald-650 hover:bg-emerald-600 text-white rounded-lg select-none">Yes, Restore</button>
            </form>
        </div>
    </div>
</div>
@endsection

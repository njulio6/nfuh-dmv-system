@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{
        showFilterModal: false,
        showWithdrawalModal: false,
        showRemarkModal: false,
        showPayoutModal: false,
        activePayoutMethod: '',
        activePayoutDetails: '',
        copiedDetails: false,
        openPayoutModal(method, details) {
            this.activePayoutMethod = method;
            this.activePayoutDetails = details;
            this.copiedDetails = false;
            this.showPayoutModal = true;
        },
        activeRemark: {
            title: '',
            status: '',
            date: '',
            content: ''
        },
        openRemarkModal(title, status, date, content) {
            this.activeRemark = { title, status, date, content };
            this.showRemarkModal = true;
        },
        gross: '',
        feePercent: {{ $appSettings->savings_withdrawal_fee_percent ?? 2.00 }},
        payoutMethod: 'zelle',
        bankAccountType: 'checking',
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
    class="w-full flex flex-col gap-6 animate-fadeIn"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="savings-withdrawal-filter-form" method="GET" action="{{ route('member.savings.withdrawals') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="status" value="{{ request('status') }}" x-ref="statusInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $requests->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- Top Control Bar -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search method or destination..."
                    value="{{ request('search') }}"
                    @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                    @blur="$refs.searchInput.value = $el.value"
                    class="w-full h-9 bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors"
                    style="padding-left: 2.25rem;"
                />
            </div>
        </div>

        <!-- Right: Action Buttons Group -->
        <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:justify-end">
            <!-- Available Balance Pill -->
            <div class="h-9 px-3.5 bg-zinc-100 dark:bg-zinc-800 rounded-[10px] flex items-center gap-1.5 border border-zinc-200 dark:border-zinc-700 select-none">
                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Available:</span>
                <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">${{ number_format($member->available_savings_balance, 2) }}</span>
            </div>

            <!-- Request Withdrawal Button -->
            <button 
                type="button" 
                @click="showWithdrawalModal = true"
                class="h-9 px-4 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 shadow-xs transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
            >
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                <span>Request Withdrawal</span>
            </button>

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('member.savings.withdrawals') }}" 
                class="h-9 w-9 flex items-center justify-center border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96]"
                title="Reload & Clear Filters"
            >
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
            </a>

            <!-- Filter Button -->
            <button 
                type="button"
                @click="showFilterModal = true"
                class="h-9 w-9 flex items-center justify-center border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative {{ request('status') ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                title="Filters"
            >
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                @if(request('status'))
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative">
        <x-premium-table :headers="[
            ['label' => 'SI', 'width' => 'w-12', 'align' => 'center'],
            ['label' => 'Date Requested'],
            ['label' => 'Gross Amount'],
            ['label' => 'Fee Rate'],
            ['label' => 'Net Payout'],
            ['label' => 'Payout Method'],
            ['label' => 'Status', 'width' => 'w-24', 'align' => 'center'],
            ['label' => 'Remarks'],
        ]" class="min-w-[700px]">
            @forelse ($requests as $index => $req)
                @php
                    $serialIndex = $index + 1 + ($requests->currentPage() - 1) * $requests->perPage();
                    $isEven = $index % 2 === 1;
                @endphp
                <x-premium-table-row :is-even="$isEven">
                    <td class="text-center font-mono text-xs sm:text-sm text-black dark:text-white font-bold px-3.5 py-3.5">
                        {{ sprintf('%02d', $serialIndex) }}
                    </td>
                    <td class="px-3.5 py-3.5 font-mono text-black dark:text-white font-medium text-xs sm:text-sm">
                        {{ $req->created_at->format('M d, Y h:i A') }}
                    </td>
                    <td class="px-3.5 py-3.5 font-bold text-black dark:text-white text-xs sm:text-sm">
                        ${{ number_format($req->gross_amount, 2) }}
                    </td>
                    <td class="px-3.5 py-3.5 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                        <span class="font-semibold text-amber-600 dark:text-amber-400 font-mono text-xs sm:text-sm">{{ number_format($req->fee_rate, 2) }}%</span>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block font-mono">(-${{ number_format($req->fee_amount, 2) }})</span>
                    </td>
                    <td class="px-3.5 py-3.5 font-bold text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-mono">
                        ${{ number_format($req->net_amount, 2) }}
                    </td>
                    <td class="px-3.5 py-3.5 text-xs sm:text-sm">
                        <button 
                            type="button" 
                            @click="openPayoutModal({{ json_encode(ucfirst(str_replace('_', ' ', $req->payout_method))) }}, {{ json_encode($req->masked_payout_details) }})"
                            class="group inline-flex items-center gap-1.5 text-left cursor-pointer focus:outline-none"
                            title="View Payout Destination Details"
                        >
                            <span class="font-semibold text-black dark:text-white group-hover:underline underline-offset-2 transition-colors text-xs sm:text-sm">
                                {{ ucfirst(str_replace('_', ' ', $req->payout_method)) }}
                            </span>
                            <span class="inline-flex items-center justify-center size-6 rounded-md text-black dark:text-white group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800 transition-colors">
                                <i data-lucide="eye" class="w-4 h-4 stroke-[2.2]"></i>
                            </span>
                        </button>
                    </td>
                    <td class="text-center px-3.5 py-3.5">
                        @if ($req->status === 'disbursed')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                Disbursed / Completed
                            </span>
                        @elseif ($req->status === 'approved')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-500/20">
                                Approved – Pending Disbursement
                            </span>
                        @elseif ($req->status === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                Rejected
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                Pending Approval
                            </span>
                        @endif
                    </td>
                    <td class="px-3.5 py-3.5 text-xs sm:text-sm">
                        @php
                            $hasRemark = ($req->status === 'rejected' && $req->rejection_reason) || $req->admin_notes;
                            $remarkText = $req->status === 'rejected' ? $req->rejection_reason : $req->admin_notes;
                        @endphp
                        @if($req->status === 'disbursed')
                            <div class="flex items-center gap-2">
                                @if($req->disbursement_proof)
                                    <a 
                                        href="{{ asset('storage/' . $req->disbursement_proof) }}" 
                                        target="_blank" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[8px] text-xs font-semibold cursor-pointer transition-all border shadow-2xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-900/50"
                                        title="View Payout Confirmation Receipt"
                                    >
                                        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                                        <span>Receipt</span>
                                    </a>
                                @endif
                                @if($hasRemark)
                                    <button 
                                        type="button" 
                                        @click="openRemarkModal({{ json_encode('Disbursement Note') }}, {{ json_encode($req->status) }}, {{ json_encode($req->disbursed_at ? $req->disbursed_at->format('M d, Y h:i A') : $req->created_at->format('M d, Y h:i A')) }}, {{ json_encode($remarkText) }})"
                                        class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 text-xs font-medium underline"
                                    >
                                        Notes
                                    </button>
                                @endif
                            </div>
                        @elseif($hasRemark)
                            <button 
                                type="button" 
                                @click="openRemarkModal({{ json_encode($req->status === 'rejected' ? 'Rejection Reason' : 'Admin Review Remarks') }}, {{ json_encode($req->status) }}, {{ json_encode($req->updated_at ? $req->updated_at->format('M d, Y h:i A') : $req->created_at->format('M d, Y h:i A')) }}, {{ json_encode($remarkText) }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[8px] text-xs sm:text-[13px] font-semibold cursor-pointer transition-all border shadow-2xs {{ $req->status === 'rejected' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200/80 dark:bg-rose-950/30 dark:text-rose-300 dark:border-rose-900/50' : 'bg-zinc-100 hover:bg-zinc-200 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700' }}"
                                title="Click to view full remarks"
                            >
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                <span>View Remarks</span>
                            </button>
                        @elseif($req->status === 'approved')
                            <span class="text-sky-600 dark:text-sky-400 font-medium italic text-xs">Awaiting Payout Release</span>
                        @elseif($req->status === 'pending')
                            <span class="text-zinc-400 dark:text-zinc-500 italic text-xs">Under Review</span>
                        @else
                            <span class="text-zinc-400 text-sm">-</span>
                        @endif
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-zinc-400 dark:text-zinc-600 text-xs">
                        No savings withdrawal requests recorded.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>

    </div>

    <!-- ─── Pagination Footer ─── -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
        <!-- Left: Rows per Page buttons -->
        <div class="flex items-center gap-3">
            <span class="text-[11px] font-black uppercase text-zinc-500 dark:text-zinc-400">
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

        <!-- Right: Pagination links -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <!-- Go to first page button -->
            <button
                type="button"
                @click="$refs.pageInput.value = 1; $refs.form.submit()"
                @if($requests->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <!-- Previous button -->
            <button
                type="button"
                @if(!$requests->onFirstPage())
                    @click="$refs.pageInput.value = {{ $requests->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                @if($requests->onFirstPage()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Previous Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <!-- Page numbers window loop -->
            @php
                $startPage = max(1, $requests->currentPage() - 2);
                $endPage = min($requests->lastPage(), $requests->currentPage() + 2);
            @endphp
            @for ($page = $startPage; $page <= $endPage; $page++)
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                    class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                        {{ $page == $requests->currentPage()
                            ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                            : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                        }}"
                >
                    {{ $page }}
                </button>
            @endfor

            <!-- Next button -->
            <button
                type="button"
                @if($requests->hasMorePages())
                    @click="$refs.pageInput.value = {{ $requests->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$requests->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Next Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <!-- Last page button -->
            <button
                type="button"
                @click="$refs.pageInput.value = {{ $requests->lastPage() }}; $refs.form.submit()"
                @if($requests->currentPage() == $requests->lastPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                Last
            </button>
        </div>
    </div>

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

                <!-- Method-Specific Payout Details (Mandatory) -->
                <div class="flex flex-col gap-2 p-3.5 bg-zinc-50/70 dark:bg-zinc-950/50 border border-zinc-200 dark:border-zinc-800 rounded-xl">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 flex items-center gap-1">
                            <span>Payout Destination Details <span class="text-red-500">*</span></span>
                        </label>
                        <span class="text-[10px] text-red-500 font-semibold uppercase tracking-wider">Required</span>
                    </div>

                    <!-- Zelle Inputs -->
                    <div x-show="payoutMethod === 'zelle'" class="flex flex-col gap-2.5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Recipient Full Name <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                name="zelle_name" 
                                :required="payoutMethod === 'zelle'"
                                placeholder="Full legal name on Zelle account"
                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                            />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Registered Zelle Phone or Email <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                name="zelle_contact" 
                                :required="payoutMethod === 'zelle'"
                                placeholder="e.g. member@email.com or (555) 123-4567"
                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                            />
                        </div>
                    </div>

                    <!-- Direct Bank Transfer Inputs -->
                    <div x-show="payoutMethod === 'bank_transfer'" class="flex flex-col gap-2.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Account Holder Name <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="bank_account_holder" 
                                    :required="payoutMethod === 'bank_transfer'"
                                    placeholder="Account holder name"
                                    class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Bank Name <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="bank_name" 
                                    :required="payoutMethod === 'bank_transfer'"
                                    placeholder="e.g. Chase, Wells Fargo, BoA"
                                    class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Routing Number <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="bank_routing_number" 
                                    maxlength="9"
                                    pattern="[0-9]{9}"
                                    :required="payoutMethod === 'bank_transfer'"
                                    placeholder="Routing number"
                                    class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-mono font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Account Number <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="bank_account_number" 
                                    :required="payoutMethod === 'bank_transfer'"
                                    placeholder="Full account number"
                                    class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-mono font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Account Type <span class="text-red-500">*</span></label>
                            <input type="hidden" name="bank_account_type" :value="bankAccountType" :required="payoutMethod === 'bank_transfer'">
                            <x-dropdown align="left" width="w-full" contentClasses="p-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl flex flex-col gap-0.5">
                                <x-slot name="trigger">
                                    <button 
                                        type="button" 
                                        class="w-full flex items-center justify-between bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 text-zinc-900 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-700 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left"
                                    >
                                        <span x-text="bankAccountType === 'checking' ? 'Checking Account' : 'Savings Account'"></span>
                                        <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-500 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <button 
                                        type="button" 
                                        @click="bankAccountType = 'checking'" 
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                        :class="bankAccountType === 'checking' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                                    >
                                        <span>Checking Account</span>
                                        <span x-show="bankAccountType === 'checking'" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="bankAccountType = 'savings'" 
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                        :class="bankAccountType === 'savings' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                                    >
                                        <span>Savings Account</span>
                                        <span x-show="bankAccountType === 'savings'" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                                    </button>
                                </x-slot>
                            </x-dropdown>
                        </div>
                        <div class="text-[10px] text-zinc-400 dark:text-zinc-500 flex items-center gap-1 pt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-500"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Bank account numbers are protected and accessible only to authorized financial officers.</span>
                        </div>
                    </div>

                    <!-- Paper Check Inputs -->
                    <div x-show="payoutMethod === 'check'" class="flex flex-col gap-2.5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Check Payee Name <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                name="check_payee_name" 
                                :required="payoutMethod === 'check'"
                                placeholder="Legal name to print on the check"
                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500"
                            />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400">Mailing Address / Pickup Instructions <span class="text-red-500">*</span></label>
                            <textarea 
                                name="check_delivery_address" 
                                rows="2"
                                :required="payoutMethod === 'check'"
                                placeholder="Street address, City, State, ZIP code, or specific in-person collection request"
                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-500 resize-none"
                            ></textarea>
                        </div>
                    </div>
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

    <!-- Filter Modal -->
    <div 
        x-show="showFilterModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
        @keydown.escape.window="showFilterModal = false"
    >
        <div 
            @click.away="showFilterModal = false" 
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl max-w-lg w-full p-6 flex flex-col gap-4 animate-scaleIn"
        >
            <h4 class="text-base font-bold text-zinc-900 dark:text-white">Filter Requests</h4>
            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold text-zinc-600 dark:text-zinc-400">Status</label>
                <select 
                    id="status-filter-select"
                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold"
                >
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <button type="button" @click="showFilterModal = false" class="px-3 py-1.5 text-xs text-zinc-500 font-medium">Cancel</button>
                <button 
                    type="button" 
                    @click="$refs.statusInput.value = document.getElementById('status-filter-select').value; $refs.pageInput.value = 1; $refs.form.submit()"
                    class="px-4 py-1.5 bg-zinc-950 dark:bg-white text-white dark:text-zinc-950 text-xs font-bold rounded-lg"
                >
                    Apply Filter
                </button>
            </div>
        </div>
    </div>

    <!-- View Remarks Modal -->
    <div 
        x-show="showRemarkModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
        @keydown.escape.window="showRemarkModal = false"
    >
        <div 
            @click.away="showRemarkModal = false"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 max-w-lg w-full shadow-2xl flex flex-col gap-5 animate-scaleUp"
        >
            <div class="flex items-start justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div 
                        class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0"
                        :class="activeRemark.status === 'rejected' ? 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50' : 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50'"
                    >
                        <i data-lucide="message-square-text" class="w-4 h-4"></i>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white" x-text="activeRemark.title"></h3>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500 font-mono" x-text="activeRemark.date"></span>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="showRemarkModal = false"
                    class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg cursor-pointer transition-colors"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Card -->
            <div 
                class="p-4 rounded-xl border text-xs sm:text-sm leading-relaxed whitespace-pre-wrap break-words min-h-[90px]"
                :class="activeRemark.status === 'rejected' ? 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-200/60 dark:border-rose-900/40 text-rose-900 dark:text-rose-200' : 'bg-zinc-50 dark:bg-zinc-950 border-zinc-200/60 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200'"
                x-text="activeRemark.content"
            ></div>

            <div class="pt-2 flex justify-end">
                <button 
                    type="button" 
                    @click="showRemarkModal = false"
                    class="w-full sm:w-auto px-5 py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer flex items-center justify-center"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- View Payout Destination Modal -->
    <div 
        x-show="showPayoutModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
        @keydown.escape.window="showPayoutModal = false"
    >
        <div 
            @click.away="showPayoutModal = false"
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 max-w-lg w-full shadow-2xl flex flex-col gap-5 animate-scaleUp"
        >
            <div class="flex items-start justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl border bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900/50 flex items-center justify-center shrink-0">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Payout Destination Details</h3>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium" x-text="activePayoutMethod"></span>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="showPayoutModal = false"
                    class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg cursor-pointer transition-colors"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Card -->
            <div class="p-4 rounded-xl border bg-zinc-50/50 dark:bg-zinc-950/40 border-zinc-200/80 dark:border-zinc-800/80 flex flex-col gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Destination Account / Address:</span>
                <p class="text-xs sm:text-sm font-mono text-zinc-900 dark:text-zinc-100 leading-relaxed whitespace-pre-wrap break-words" x-text="activePayoutDetails"></p>
            </div>

            <div class="pt-2 flex items-center justify-between gap-3">
                <button 
                    type="button" 
                    @click="navigator.clipboard.writeText(activePayoutDetails); copiedDetails = true; setTimeout(() => copiedDetails = false, 2000)"
                    class="px-4 py-2 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all cursor-pointer flex items-center gap-1.5"
                >
                    <i data-lucide="copy" class="w-3.5 h-3.5" x-show="!copiedDetails"></i>
                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" x-show="copiedDetails" x-cloak></i>
                    <span x-text="copiedDetails ? 'Copied!' : 'Copy Details'"></span>
                </button>
                <button 
                    type="button" 
                    @click="showPayoutModal = false"
                    class="px-5 py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

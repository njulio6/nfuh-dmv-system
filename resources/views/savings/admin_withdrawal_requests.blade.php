@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{ 
        showFilterModal: false,
        showApproveModal: false,
        showRejectModal: false,
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
        selectedRequestId: '',
        selectedGrossAmount: '',
        selectedFeeAmount: '',
        selectedNetAmount: '',
        selectedMemberName: '',
        selectedPayoutMethod: '',
        selectedPayoutDetails: '',
        adminNotes: '',
        rejectionReason: '',
        activeRemark: {
            title: '',
            status: '',
            memberName: '',
            date: '',
            content: ''
        },
        openRemarkModal(title, status, memberName, date, content) {
            this.activeRemark = { title, status, memberName, date, content };
            this.showRemarkModal = true;
        },
        getPayoutPairs(raw) {
            if (!raw) return [];
            if (!raw.includes('|') && !raw.includes(':')) {
                return [{ label: 'Details', value: raw }];
            }
            return raw.split('|').map(part => {
                let idx = part.indexOf(':');
                if (idx !== -1) {
                    return {
                        label: part.substring(0, idx).trim(),
                        value: part.substring(idx + 1).trim()
                    };
                }
                return { label: '', value: part.trim() };
            }).filter(item => item.value && item.value.length > 0);
        }
    }"
    class="w-full flex flex-col gap-6 animate-fadeIn"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="savings-admin-withdrawal-filter-form" method="GET" action="{{ route('savings.withdrawal-requests') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="status" value="{{ request('status') }}" x-ref="statusInput">
        <input type="hidden" name="member_id" value="{{ request('member_id') }}" x-ref="memberIdInput">
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
                    placeholder="Search member, method, destination..."
                    value="{{ request('search') }}"
                    @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                    @blur="$refs.searchInput.value = $el.value"
                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors"
                    style="padding-left: 2.25rem;"
                />
            </div>
        </div>

        <!-- Right: Action Buttons Group -->
        <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:justify-end">
            @if($pendingCount > 0)
                <div class="px-3 py-1.5 bg-amber-500/10 border border-amber-500/20 rounded-[10px] flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">{{ $pendingCount }} Pending Approval</span>
                </div>
            @endif

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('savings.withdrawal-requests') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96]"
                title="Reload & Clear Filters"
            >
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
            </a>

            <!-- Filter Popover Button -->
            <button 
                type="button"
                @click="showFilterModal = true"
                class="p-2.5 border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative {{ (request('status') || request('member_id')) ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                title="Filters"
            >
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                @if(request('status') || request('member_id'))
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
            ['label' => 'Member'],
            ['label' => 'Gross Amount'],
            ['label' => 'Processing Fee'],
            ['label' => 'Net Payout'],
            ['label' => 'Payout Method'],
            ['label' => 'Status', 'width' => 'w-24', 'align' => 'center'],
            ['label' => 'Actions / Review', 'width' => 'w-36', 'align' => 'center']
        ]" class="min-w-[850px]">
            @forelse($requests as $index => $req)
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
                    <td class="px-3.5 py-3.5 text-xs sm:text-sm">
                        <div class="flex flex-col">
                            <span class="font-bold text-black dark:text-white text-xs sm:text-sm">{{ $req->member->name }}</span>
                            <span class="font-mono text-[11px] text-zinc-500 dark:text-zinc-400">{{ $req->member->member_code }}</span>
                        </div>
                    </td>
                    <td class="px-3.5 py-3.5 font-bold text-black dark:text-white text-xs sm:text-sm">
                        ${{ number_format($req->gross_amount, 2) }}
                    </td>
                    <td class="px-3.5 py-3.5 text-xs sm:text-sm text-amber-600 dark:text-amber-400 font-medium">
                        -${{ number_format($req->fee_amount, 2) }}
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block font-mono">({{ number_format($req->fee_rate, 2) }}%)</span>
                    </td>
                    <td class="px-3.5 py-3.5 font-bold text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-mono">
                        ${{ number_format($req->net_amount, 2) }}
                    </td>
                    <td class="px-3.5 py-3.5 text-xs sm:text-sm">
                        <button 
                            type="button" 
                            @click="openPayoutModal({{ json_encode(ucfirst(str_replace('_', ' ', $req->payout_method))) }}, {{ json_encode($req->payout_details) }})"
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
                        @if ($req->status === 'approved')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                Approved
                            </span>
                        @elseif ($req->status === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                Rejected
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                Pending
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center">
                        @if ($req->status === 'pending')
                            <div class="flex items-center justify-center gap-1.5">
                                <button 
                                    type="button" 
                                    @click="
                                        selectedRequestId = '{{ $req->id }}';
                                        selectedGrossAmount = '{{ number_format($req->gross_amount, 2) }}';
                                        selectedFeeAmount = '{{ number_format($req->fee_amount, 2) }}';
                                        selectedNetAmount = '{{ number_format($req->net_amount, 2) }}';
                                        selectedMemberName = {{ json_encode($req->member->name) }};
                                        selectedPayoutMethod = {{ json_encode(ucfirst(str_replace('_', ' ', $req->payout_method))) }};
                                        selectedPayoutDetails = {{ json_encode($req->payout_details) }};
                                        showApproveModal = true;
                                    "
                                    class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 transition-all cursor-pointer"
                                    title="Approve & Debit Savings Balance"
                                >
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </button>
                                <button 
                                    type="button" 
                                    @click="
                                        selectedRequestId = '{{ $req->id }}';
                                        selectedMemberName = {{ json_encode($req->member->name) }};
                                        selectedGrossAmount = '{{ number_format($req->gross_amount, 2) }}';
                                        rejectionReason = '';
                                        showRejectModal = true;
                                    "
                                    class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 transition-all cursor-pointer"
                                    title="Reject Request"
                                >
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @else
                            @php
                                $hasAdminRemark = ($req->status === 'rejected' && $req->rejection_reason) || $req->admin_notes;
                                $adminRemarkText = $req->status === 'rejected' ? $req->rejection_reason : $req->admin_notes;
                            @endphp
                            @if($hasAdminRemark)
                                <button 
                                    type="button" 
                                    @click="openRemarkModal({{ json_encode($req->status === 'rejected' ? 'Rejection Reason' : 'Admin Review Remarks') }}, {{ json_encode($req->status) }}, {{ json_encode($req->member->name) }}, {{ json_encode($req->reviewed_at ? $req->reviewed_at->format('M d, Y h:i A') : ($req->updated_at ? $req->updated_at->format('M d, Y h:i A') : $req->created_at->format('M d, Y h:i A'))) }}, {{ json_encode($adminRemarkText) }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[8px] text-[11px] font-semibold cursor-pointer transition-all border shadow-2xs {{ $req->status === 'rejected' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200/80 dark:bg-rose-950/30 dark:text-rose-300 dark:border-rose-900/50' : 'bg-zinc-100 hover:bg-zinc-200 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700' }}"
                                    title="View Recorded Remarks"
                                >
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    <span>Remarks</span>
                                </button>
                            @else
                                <span class="text-[11px] text-zinc-400 dark:text-zinc-600 font-mono">Reviewed</span>
                            @endif
                        @endif
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-12 text-zinc-400 dark:text-zinc-600 text-xs">
                        No withdrawal requests found matching criteria.
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

    <!-- Approve Modal -->
    <div 
        x-show="showApproveModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
        @keydown.escape.window="showApproveModal = false"
    >
        <div 
            @click.away="showApproveModal = false" 
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl max-w-lg w-full p-6 flex flex-col gap-5 animate-scaleIn"
        >
            <!-- Header -->
            <div class="flex items-start justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-900 dark:text-white shrink-0 border border-zinc-200/60 dark:border-zinc-700/60">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Approve Withdrawal Request</h3>
                        <span class="text-xs text-zinc-500">Review amounts and payout destination before debiting savings</span>
                    </div>
                </div>
                <button type="button" @click="showApproveModal = false" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Recipient & Financial Summary Card -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden bg-zinc-50/50 dark:bg-zinc-950/40">
                <div class="px-4 py-2.5 bg-zinc-100/60 dark:bg-zinc-900/60 border-b border-zinc-200/80 dark:border-zinc-800/80 flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Recipient Member</span>
                    <span class="text-xs font-bold text-zinc-900 dark:text-white" x-text="selectedMemberName"></span>
                </div>
                <div class="p-3.5 flex flex-col gap-2.5 text-xs">
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Gross Debit Amount</span>
                        <span class="font-bold font-mono text-zinc-900 dark:text-white text-xs">$<span x-text="selectedGrossAmount"></span></span>
                    </div>
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Processing Fee</span>
                        <span class="font-bold font-mono text-amber-600 dark:text-amber-400 text-xs">-$<span x-text="selectedFeeAmount"></span></span>
                    </div>
                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <span class="font-bold text-zinc-900 dark:text-white">Net Disbursable Payout</span>
                        <span class="font-extrabold font-mono text-emerald-600 dark:text-emerald-400 text-base">$<span x-text="selectedNetAmount"></span></span>
                    </div>
                </div>
            </div>

            <!-- Payout Destination Details Card -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden bg-zinc-50/50 dark:bg-zinc-950/40 p-4 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-500"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Disbursement Destination
                        </span>
                    </div>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-800 shadow-3xs" x-text="selectedPayoutMethod"></span>
                </div>

                <!-- Structured Grid -->
                <template x-if="getPayoutPairs(selectedPayoutDetails).length > 0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <template x-for="(pair, idx) in getPayoutPairs(selectedPayoutDetails)" :key="idx">
                            <div 
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 flex flex-col gap-0.5 shadow-3xs"
                                :class="(pair.label.toLowerCase() === 'delivery' || pair.label.toLowerCase() === 'address' || getPayoutPairs(selectedPayoutDetails).length === 1) ? 'sm:col-span-2' : ''"
                            >
                                <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500" x-text="pair.label || 'Detail'"></span>
                                <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100 text-xs break-all" x-text="pair.value"></span>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="getPayoutPairs(selectedPayoutDetails).length === 0">
                    <div class="text-xs text-zinc-500 italic">No payout details provided.</div>
                </template>
            </div>

            <!-- Form -->
            <form :action="'{{ url('/savings/withdrawal-requests') }}/' + selectedRequestId + '/approve'" method="POST" class="flex flex-col gap-4">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Admin Note / Confirmation Ref
                        </label>
                        <span class="text-[10px] text-zinc-400 font-medium uppercase tracking-wider">Optional</span>
                    </div>
                    <input 
                        type="text" 
                        name="admin_notes" 
                        placeholder="e.g. Disbursed via Zelle confirmation #12345"
                        class="w-full bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all shadow-2xs"
                    />
                </div>

                <div class="flex items-center gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <button 
                        type="button" 
                        @click="showApproveModal = false" 
                        class="flex-1 py-2.5 text-xs font-bold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all cursor-pointer select-none"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 py-2.5 text-xs font-bold bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 rounded-xl shadow-xs transition-all active:scale-[0.98] cursor-pointer select-none"
                    >
                        Confirm & Debit Savings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div 
        x-show="showRejectModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
        @keydown.escape.window="showRejectModal = false"
    >
        <div 
            @click.away="showRejectModal = false" 
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl max-w-md w-full p-6 flex flex-col gap-4 animate-scaleIn"
        >
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">Reject Withdrawal Request</h3>
                <button type="button" @click="showRejectModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                Please provide a mandatory reason for rejecting this withdrawal request for <strong class="text-zinc-900 dark:text-white" x-text="selectedMemberName"></strong>. The member will be able to see this reason.
            </p>

            <form :action="'{{ url('/savings/withdrawal-requests') }}/' + selectedRequestId + '/reject'" method="POST" class="flex flex-col gap-3.5">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                        Mandatory Rejection Reason *
                    </label>
                    <textarea 
                        name="rejection_reason" 
                        rows="3" 
                        required 
                        minlength="3"
                        placeholder="Explain clearly why this withdrawal cannot be approved (e.g. Invalid Zelle info, unconfirmed identity)"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs focus:border-rose-500 shadow-2xs resize-none"
                    ></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2.5 border-t border-zinc-100 dark:border-zinc-800">
                    <button type="button" @click="showRejectModal = false" class="flex-1 py-2.5 text-xs font-bold border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all cursor-pointer select-none">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all active:scale-[0.98] cursor-pointer select-none">
                        Confirm Rejection
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
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xl max-w-sm w-full p-5 flex flex-col gap-4 animate-scaleIn"
        >
            <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Filter Withdrawal Requests</h4>
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-zinc-600 dark:text-zinc-400">Status</label>
                    <select 
                        id="admin-withdrawal-status-filter"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-zinc-600 dark:text-zinc-400">Member</label>
                    <select 
                        id="admin-withdrawal-member-filter"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold"
                    >
                        <option value="">All Members</option>
                        @foreach($allMembers as $m)
                            <option value="{{ $m->id }}" {{ request('member_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->first_name }} {{ $m->last_name }} ({{ $m->member_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <button type="button" @click="showFilterModal = false" class="px-3 py-1.5 text-xs text-zinc-500 font-medium">Cancel</button>
                <button 
                    type="button" 
                    @click="
                        $refs.statusInput.value = document.getElementById('admin-withdrawal-status-filter').value; 
                        $refs.memberIdInput.value = document.getElementById('admin-withdrawal-member-filter').value; 
                        $refs.pageInput.value = 1; 
                        $refs.form.submit()
                    "
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
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 max-w-md w-full shadow-2xl flex flex-col gap-4 animate-scaleUp"
        >
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div 
                        class="p-2.5 rounded-xl border flex items-center justify-center"
                        :class="activeRemark.status === 'rejected' ? 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50' : 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50'"
                    >
                        <i data-lucide="message-square-text" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white" x-text="activeRemark.title"></h3>
                            <span 
                                class="px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                :class="activeRemark.status === 'rejected' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'"
                                x-text="activeRemark.status"
                            ></span>
                        </div>
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                            Member: <strong class="text-zinc-700 dark:text-zinc-300" x-text="activeRemark.memberName"></strong> • <span class="font-mono" x-text="activeRemark.date"></span>
                        </span>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="showRemarkModal = false"
                    class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg cursor-pointer transition-colors"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Card -->
            <div 
                class="p-4 rounded-xl border text-xs sm:text-sm leading-relaxed whitespace-pre-wrap break-words min-h-[80px]"
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
            class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 max-w-md w-full shadow-2xl flex flex-col gap-4 animate-scaleUp"
        >
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl border bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900/50 flex items-center justify-center">
                        <i data-lucide="wallet" class="w-5 h-5"></i>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Payout Destination Details</h3>
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium" x-text="activePayoutMethod"></span>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="showPayoutModal = false"
                    class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg cursor-pointer transition-colors"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Card -->
            <div class="p-3.5 rounded-xl border bg-zinc-50/50 dark:bg-zinc-950/40 border-zinc-200/80 dark:border-zinc-800/80 flex flex-col gap-2.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Destination Account / Address:</span>
                <template x-if="getPayoutPairs(activePayoutDetails).length > 0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <template x-for="(pair, idx) in getPayoutPairs(activePayoutDetails)" :key="idx">
                            <div 
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 flex flex-col gap-0.5 shadow-3xs"
                                :class="(pair.label.toLowerCase() === 'delivery' || pair.label.toLowerCase() === 'address' || getPayoutPairs(activePayoutDetails).length === 1) ? 'sm:col-span-2' : ''"
                            >
                                <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500" x-text="pair.label || 'Detail'"></span>
                                <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100 text-xs break-all" x-text="pair.value"></span>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="getPayoutPairs(activePayoutDetails).length === 0">
                    <p class="text-xs font-mono text-zinc-900 dark:text-zinc-100" x-text="activePayoutDetails"></p>
                </template>
            </div>

            <div class="pt-2 flex items-center justify-between gap-2.5">
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

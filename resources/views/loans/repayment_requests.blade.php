@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{ 
        showFilterModal: false,
        showReceiptModal: false,
        receiptUrl: '',
        showApproveModal: false,
        showRejectModal: false,
        selectedRequestId: '',
        selectedRequestAmount: '',
        selectedRequestMemberName: '',
        selectedRequestProofUrl: '',
        selectedRequestNotes: '',
        reviewNote: ''
    }"
    class="w-full flex flex-col gap-6"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="loan-repayment-admin-filter-form" method="GET" action="{{ route('loans.repayment-requests') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="status" value="{{ request('status') }}" x-ref="statusInput">
        <input type="hidden" name="member_id" value="{{ request('member_id') }}" x-ref="memberIdInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $requests->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- ─── Top Control Bar ─── -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search borrower, reference, amount..."
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
                href="{{ route('loans.repayment-requests') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96]"
                title="Reload & Clear Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>

            <!-- Filter Popover Button -->
            <button 
                type="button"
                @click="showFilterModal = true"
                class="p-2.5 border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative {{ (request('status') || request('member_id')) ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                title="Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                @if(request('status') || request('member_id'))
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- ─── Data Table Card ─── -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative">
        <x-premium-table :headers="[
            ['label' => 'SI', 'width' => 'w-12', 'align' => 'center'],
            ['label' => 'Date Submitted'],
            ['label' => 'Borrower'],
            ['label' => 'Loan Info'],
            ['label' => 'Repayment Amount'],
            ['label' => 'Payment Method'],
            ['label' => 'Reference #'],
            ['label' => 'Proof Receipt'],
            ['label' => 'Description Notes'],
            ['label' => 'Actions / Review', 'width' => 'w-44', 'align' => 'center']
        ]" class="min-w-[900px]">
            @forelse($requests as $index => $req)
                @php
                    $serialIndex = $index + 1 + ($requests->currentPage() - 1) * $requests->perPage();
                    $isEven = $index % 2 === 1;
                    $methodLabel = match($req->payment_method) {
                        'zelle' => 'Zelle Transfer',
                        'cash' => 'Cash Payment',
                        'njangi_deduction' => 'Njangi Disbursement Deduction',
                        'check' => 'Check',
                        default => ucfirst($req->payment_method)
                    };
                @endphp
                <x-premium-table-row :is-even="$isEven">
                    <!-- SI index cell -->
                    <td class="py-2.5 px-3 text-center font-bold text-zinc-500 dark:text-zinc-400 tabular-nums">
                        {{ $serialIndex }}
                    </td>
                    
                    <!-- Date Submitted -->
                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-250 select-text">
                        {{ $req->submitted_at ? $req->submitted_at->format('M d, Y') : $req->created_at->format('M d, Y') }}
                    </td>

                    <!-- Borrower -->
                    <td class="py-2.5 px-3 select-text">
                        <div class="flex flex-col">
                            <span class="font-bold text-zinc-900 dark:text-white">{{ $req->member->first_name }} {{ $req->member->last_name }}</span>
                            <span class="text-[10px] text-zinc-400 font-mono font-bold">/{{ $req->member->member_code }}</span>
                        </div>
                    </td>

                    <!-- Loan Info -->
                    <td class="py-2.5 px-3 select-text">
                        <div class="flex flex-col text-xs text-zinc-700 dark:text-zinc-350">
                            <span class="font-bold">Loan #{{ $req->loanRequest->id }}</span>
                            <span class="text-[10px] text-zinc-400 font-semibold font-mono">Bal: ${{ number_format($req->loanRequest->remaining_balance, 2) }}</span>
                        </div>
                    </td>

                    <!-- Amount -->
                    <td class="py-2.5 px-3 font-extrabold text-zinc-900 dark:text-white select-text text-sm">
                        ${{ number_format($req->amount, 2) }}
                    </td>

                    <!-- Method -->
                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-250">
                        {{ $methodLabel }}
                    </td>

                    <!-- Reference Number -->
                    <td class="py-2.5 px-3 font-mono text-xs font-semibold text-zinc-700 dark:text-zinc-350 select-text">
                        {{ $req->reference_number ?: '-' }}
                    </td>

                    <!-- Proof Receipt Link -->
                    <td class="py-2.5 px-3">
                        @if($req->screenshot_path)
                            <button 
                                type="button"
                                @click="
                                    receiptUrl = '{{ asset('storage/' . $req->screenshot_path) }}';
                                    showReceiptModal = true;
                                "
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-650 dark:text-purple-400 hover:underline cursor-pointer border-none bg-transparent"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                View Receipt
                            </button>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-650">-</span>
                        @endif
                    </td>

                    <!-- Notes / Memo -->
                    <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-400 text-xs italic truncate max-w-[150px] select-text" title="{{ $req->notes }}">
                        {{ $req->notes ?: '-' }}
                    </td>

                    <!-- Actions / Review -->
                    <td class="py-2.5 px-3">
                        <div class="flex items-center justify-center">
                            @if($req->status === 'pending')
                                <div class="flex items-center gap-1.5">
                                    <x-premium-button
                                        type="button"
                                        variant="primary"
                                        @click="
                                            selectedRequestId = '{{ $req->id }}';
                                            selectedRequestAmount = '{{ number_format($req->amount, 2) }}';
                                            selectedRequestMemberName = '{{ addslashes($req->member->first_name . ' ' . $req->member->last_name) }}';
                                            selectedRequestProofUrl = '{{ asset('storage/' . $req->screenshot_path) }}';
                                            selectedRequestNotes = '{{ addslashes($req->notes) }}';
                                            reviewNote = '';
                                            showApproveModal = true;
                                        "
                                        class="text-[10px] py-1 px-2.5"
                                    >
                                        Approve
                                    </x-premium-button>
                                    <x-premium-button
                                        type="button"
                                        variant="danger"
                                        @click="
                                            selectedRequestId = '{{ $req->id }}';
                                            selectedRequestAmount = '{{ number_format($req->amount, 2) }}';
                                            selectedRequestMemberName = '{{ addslashes($req->member->first_name . ' ' . $req->member->last_name) }}';
                                            selectedRequestProofUrl = '{{ asset('storage/' . $req->screenshot_path) }}';
                                            selectedRequestNotes = '{{ addslashes($req->notes) }}';
                                            reviewNote = '';
                                            showRejectModal = true;
                                        "
                                        class="text-[10px] py-1 px-2.5"
                                    >
                                        Reject
                                    </x-premium-button>
                                </div>
                            @else
                                <div class="text-xs">
                                    @if($req->status === 'approved')
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Approved
                                        </span>
                                    @elseif($req->status === 'rejected')
                                        <span class="text-red-650 dark:text-red-400 font-bold uppercase tracking-wider inline-flex items-center gap-1" title="Reason: {{ $req->review_note }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg> Rejected
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        <div class="flex flex-col items-center justify-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-zinc-300 dark:text-zinc-700"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span class="text-xs font-semibold text-zinc-500">No loan repayment requests found in this queue state.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </div>

    <!-- ─── Pagination Footer Card ─── -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs">
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

    <!-- ─── Filter Popover Modal ─── -->
    <div 
        x-show="showFilterModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Modal Backdrop -->
        <div
            @click="showFilterModal = false"
            class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-xs transition-opacity duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>
        
        <!-- Modal Container -->
        <div 
            class="relative w-full max-w-md bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl p-7 text-left z-10 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-4">
                Filter Repayment Requests
            </h3>

            <!-- Member JSON Data Store (avoids double quotes in HTML attributes) -->
            <script id="members-data" type="application/json">
                {!! json_encode($allMembers->map(fn($m) => ['id' => $m->id, 'name' => $m->first_name . ' ' . $m->last_name, 'code' => $m->member_code])) !!}
            </script>

            <div 
                class="space-y-4" 
                x-data="{ 
                    statusVal: '{{ request('status') }}',
                    memberIdVal: '{{ request('member_id') }}',
                    memberSearch: '',
                    memberOpen: false,
                    memberNameVal: '',
                    members: JSON.parse(document.getElementById('members-data').textContent),
                    get filteredMembers() {
                        if (this.memberSearch.trim() === '') return this.members;
                        return this.members.filter(m => 
                            m.name.toLowerCase().includes(this.memberSearch.toLowerCase()) || 
                            m.code.toLowerCase().includes(this.memberSearch.toLowerCase())
                        );
                    },
                    selectMember(id, name, code) {
                        this.memberIdVal = id;
                        this.memberNameVal = name + ' (' + code + ')';
                        this.memberOpen = false;
                        this.memberSearch = '';
                    },
                    clearMember() {
                        this.memberIdVal = '';
                        this.memberNameVal = '';
                        this.memberOpen = false;
                        this.memberSearch = '';
                    },
                    init() {
                        if (this.memberIdVal) {
                            const match = this.members.find(m => m.id == this.memberIdVal);
                            if (match) {
                                this.memberNameVal = match.name + ' (' + match.code + ')';
                            }
                        }
                    }
                }"
                @click.outside="memberOpen = false"
            >
                <!-- Status filter -->
                <div class="relative animate-fadeIn" x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-xs font-black uppercase text-zinc-500 dark:text-zinc-400 mb-2">
                        Status Queue
                    </label>
                    <div class="relative w-full">
                        <!-- Trigger Button -->
                        <button
                            type="button"
                            @click="open = !open"
                            class="w-full text-left bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white px-4 py-2.5 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors cursor-pointer flex justify-between items-center"
                        >
                            <span x-text="statusVal === 'pending' ? 'Pending Review' : (statusVal === 'approved' ? 'Approved' : (statusVal === 'rejected' ? 'Rejected' : 'All Statuses'))"></span>
                            <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500 transition-transform" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Dropdown Panel -->
                        <div
                            x-show="open"
                            x-cloak
                            class="absolute z-50 w-full mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg p-1 flex flex-col gap-0.5"
                        >
                            <button
                                type="button"
                                @click="statusVal = ''; open = false"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold transition-colors cursor-pointer"
                                :class="statusVal === '' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'"
                            >
                                All Statuses
                            </button>
                            <button
                                type="button"
                                @click="statusVal = 'pending'; open = false"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold transition-colors cursor-pointer"
                                :class="statusVal === 'pending' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'"
                            >
                                Pending Review
                            </button>
                            <button
                                type="button"
                                @click="statusVal = 'approved'; open = false"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold transition-colors cursor-pointer"
                                :class="statusVal === 'approved' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'"
                            >
                                Approved
                            </button>
                            <button
                                type="button"
                                @click="statusVal = 'rejected'; open = false"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold transition-colors cursor-pointer"
                                :class="statusVal === 'rejected' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'"
                            >
                                Rejected
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Member Filter (Searchable Dropdown) -->
                <div class="relative">
                    <label class="block text-xs font-black uppercase text-zinc-500 dark:text-zinc-400 mb-2">
                        Filter by Member
                    </label>
                    <div class="relative w-full">
                        <!-- Trigger Button -->
                        <button
                            type="button"
                            @click="memberOpen = !memberOpen"
                            class="w-full text-left bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white px-4 py-2.5 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors cursor-pointer flex justify-between items-center"
                        >
                            <span x-text="memberNameVal || 'All Members'" :class="!memberNameVal && 'text-zinc-400 dark:text-zinc-500'"></span>
                            <div class="flex items-center gap-1.5">
                                <span x-show="memberNameVal" @click.stop="clearMember()" class="hover:text-zinc-900 dark:hover:text-white p-0.5 cursor-pointer text-zinc-400 hover:scale-105 transition-all animate-fadeIn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </span>
                                <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500 transition-transform" :class="memberOpen && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>

                        <!-- Dropdown Panel -->
                        <div
                            x-show="memberOpen"
                            x-cloak
                            class="absolute z-50 w-full mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg max-h-56 overflow-hidden flex flex-col"
                        >
                            <!-- Search Input -->
                            <div class="p-2 border-b border-zinc-100 dark:border-zinc-800/80">
                                <div class="relative flex items-center">
                                    <span class="absolute left-2.5 text-zinc-400 dark:text-zinc-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </span>
                                    <input
                                        type="text"
                                        x-model="memberSearch"
                                        placeholder="Search name or code..."
                                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-8 pr-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                                        style="padding-left: 2rem;"
                                    />
                                </div>
                            </div>

                            <!-- Options List -->
                            <div class="overflow-y-auto max-h-40 flex-1">
                                <button
                                    type="button"
                                    @click="clearMember()"
                                    class="w-full text-left px-4 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold text-zinc-555 dark:text-zinc-400 cursor-pointer"
                                >
                                    All Members
                                </button>
                                <template x-for="m in filteredMembers" :key="m.id">
                                    <button
                                        type="button"
                                        @click="selectMember(m.id, m.name, m.code)"
                                        class="w-full text-left px-4 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold text-zinc-750 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white cursor-pointer flex justify-between items-center"
                                    >
                                        <span x-text="m.name"></span>
                                        <span class="font-mono text-[10px] text-zinc-450 dark:text-zinc-550 bg-zinc-100 dark:bg-zinc-950 px-2 py-0.5 rounded" x-text="m.code"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-7">
                    <button
                        type="button"
                        @click="statusVal = ''; memberIdVal = ''; $refs.statusInput.value = ''; $refs.memberIdInput.value = ''; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="flex-1 py-2 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer select-none"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="$refs.statusInput.value = statusVal; $refs.memberIdInput.value = memberIdVal; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="flex-1 py-2 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-lg transition-all cursor-pointer shadow-xs active:scale-95 select-none"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div 
        x-show="showReceiptModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Overlay Backdrop -->
        <div @click="showReceiptModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Modal Content Container -->
        <div 
            x-show="showReceiptModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-250 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-2xl relative z-50 overflow-hidden flex flex-col max-h-[90vh] overflow-y-auto"
        >
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Proof of Repayment Receipt</h3>
                <button @click="showReceiptModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-white transition-colors cursor-pointer select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="p-6 flex items-center justify-center bg-zinc-50 dark:bg-zinc-900 max-h-[70vh] overflow-y-auto">
                <img :src="receiptUrl" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-md border border-zinc-200 dark:border-zinc-800" />
            </div>
        </div>
    </div>

    <!-- Approve Request Modal -->
    <div 
        x-show="showApproveModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Overlay Backing -->
        <div @click="showApproveModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showApproveModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-250 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-lg relative z-50 animate-fadeIn max-h-[90vh] overflow-y-auto"
        >
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Approve Repayment Request</h3>
                <button @click="showApproveModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-white transition-colors cursor-pointer select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form :action="`{{ url('loans/repayment-requests') }}/${selectedRequestId}/approve`" method="POST" class="p-6 flex flex-col gap-4">
                @csrf

                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs flex flex-col gap-2">
                    <div><strong>Borrower Name:</strong> <span x-text="selectedRequestMemberName"></span></div>
                    <div><strong>Repayment Amount:</strong> <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="`$${selectedRequestAmount}`"></span></div>
                    <div x-show="selectedRequestNotes"><strong>Notes:</strong> <span x-text="selectedRequestNotes"></span></div>
                    <div class="mt-2" x-show="selectedRequestProofUrl">
                        <button 
                            type="button" 
                            @click="
                                receiptUrl = selectedRequestProofUrl;
                                showApproveModal = false;
                                showReceiptModal = true;
                            " 
                            class="inline-flex items-center gap-1 font-bold text-purple-650 dark:text-purple-400 hover:underline cursor-pointer border-none bg-transparent"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            Open Receipt Proof Image
                        </button>
                    </div>
                </div>

                <!-- Review Note -->
                <x-premium-textarea 
                    label="Review Response Notes (Optional)" 
                    name="review_note" 
                    id="review_note_approve" 
                    rows="2" 
                    placeholder="Approval confirmation note or transaction log ref..."
                />

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 mt-2 w-full">
                    <x-premium-button type="button" variant="secondary" @click="showApproveModal = false" class="py-2.5 w-full sm:w-1/2">
                        Cancel
                    </x-premium-button>
                    <x-premium-button type="submit" variant="primary" class="py-2.5 w-full sm:w-1/2 bg-emerald-600 hover:bg-emerald-500 border-emerald-600 hover:border-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-zinc-950">
                        Approve Repayment
                    </x-premium-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Request Modal -->
    <div 
        x-show="showRejectModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Overlay Backing -->
        <div @click="showRejectModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showRejectModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-250 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-lg relative z-50 animate-fadeIn max-h-[90vh] overflow-y-auto"
        >
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Reject Repayment Request</h3>
                <button @click="showRejectModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-white transition-colors cursor-pointer select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form :action="`{{ url('loans/repayment-requests') }}/${selectedRequestId}/reject`" method="POST" class="p-6 flex flex-col gap-4">
                @csrf

                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs flex flex-col gap-2">
                    <div><strong>Borrower Name:</strong> <span x-text="selectedRequestMemberName"></span></div>
                    <div><strong>Requested Amount:</strong> <span class="font-bold text-red-655 dark:text-red-400" x-text="`$${selectedRequestAmount}`"></span></div>
                    <div x-show="selectedRequestNotes"><strong>Notes:</strong> <span x-text="selectedRequestNotes"></span></div>
                </div>

                <!-- Rejection Reason (Required) -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Reason for Rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        name="review_note" 
                        required 
                        rows="3" 
                        placeholder="Please state the reason for rejecting this repayment request..."
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs p-3 focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-750 transition-all outline-none"
                    ></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 mt-2 w-full">
                    <x-premium-button type="button" variant="secondary" @click="showRejectModal = false" class="py-2.5 w-full sm:w-1/2">
                        Cancel
                    </x-premium-button>
                    <x-premium-button type="submit" variant="danger" class="py-2.5 w-full sm:w-1/2 bg-red-600 hover:bg-red-550 dark:bg-red-500 dark:hover:bg-red-450 text-white dark:text-zinc-950">
                        Confirm Rejection
                    </x-premium-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

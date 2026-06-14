@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{
        showFilterModal: false,
        showRequestModal: false,
        showReceiptModal: false,
        receiptUrl: '',
        fileName: '',
        previewUrl: ''
    }"
    class="w-full flex flex-col gap-6"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="savings-member-filter-form" method="GET" action="{{ route('member.savings.requests') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="status" value="{{ request('status') }}" x-ref="statusInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $requests->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- ─── Sibling Block 1: Top Control Bar ─── -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search notes or amount..."
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
            <!-- Request Deposit Button -->
            <button 
                type="button" 
                @click="showRequestModal = true"
                class="px-5 py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Request Deposit</span>
            </button>

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('member.savings.requests') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96]"
                title="Reload & Clear Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>

            <!-- Filter Popover Button -->
            <button 
                type="button"
                @click="showFilterModal = true"
                class="p-2.5 border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative {{ request('status') ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                title="Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                @if(request('status'))
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- ─── Sibling Block 2: Data Table Card ─── -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative">
        <x-premium-table :headers="[
            ['label' => 'SI', 'width' => 'w-12', 'align' => 'center'],
            ['label' => 'Date Requested'],
            ['label' => 'Amount'],
            ['label' => 'Status', 'width' => 'w-24', 'align' => 'center'],
            ['label' => 'Proof Receipt'],
            ['label' => 'Description Notes'],
            ['label' => 'Admin Review Notes'],
            ['label' => 'Actions', 'width' => 'w-28', 'align' => 'center']
        ]" class="min-w-[700px]">
            @forelse ($requests as $index => $req)
                @php
                    $serialIndex = $index + 1 + ($requests->currentPage() - 1) * $requests->perPage();
                    $isEven = $index % 2 === 1;
                @endphp
                <x-premium-table-row :is-even="$isEven">
                    <!-- SI index cell -->
                    <td class="py-2.5 px-3 text-center font-bold text-zinc-500 dark:text-zinc-400 tabular-nums">
                        {{ $serialIndex }}
                    </td>
                    
                    <!-- Date Cell -->
                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-250">
                        {{ $req->submitted_at->format('M d, Y') }}
                    </td>
                    
                    <!-- Amount Cell -->
                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">
                        ${{ number_format($req->amount, 2) }}
                    </td>

                    <!-- Status Cell -->
                    <td class="py-2.5 px-3">
                        <div class="flex justify-center">
                            @if($req->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Pending
                                </span>
                            @elseif($req->status === 'approved')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                    Approved
                                </span>
                            @elseif($req->status === 'rejected')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-50 text-red-750 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                    Rejected
                                </span>
                            @endif
                        </div>
                    </td>

                    <!-- Proof Link Cell -->
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

                    <!-- Description Notes -->
                    <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-400 text-xs italic truncate max-w-[150px]" title="{{ $req->notes }}">
                        {{ $req->notes ?: '-' }}
                    </td>

                    <!-- Admin Review Notes -->
                    <td class="py-2.5 px-3 text-xs max-w-[150px]">
                        @if($req->status === 'rejected' && $req->review_note)
                            <span class="text-red-650 dark:text-red-400 font-medium">Reason:</span>
                            <span class="text-zinc-700 dark:text-zinc-350 italic" title="{{ $req->review_note }}">"{{ $req->review_note }}"</span>
                        @elseif($req->status === 'approved' && $req->review_note)
                            <span class="text-zinc-700 dark:text-zinc-350 italic" title="{{ $req->review_note }}">"{{ $req->review_note }}"</span>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-650">-</span>
                        @endif
                    </td>

                    <!-- Actions cell -->
                    <td class="py-2.5 px-3">
                        <div class="flex items-center justify-center">
                            @if($req->screenshot_path)
                                <button 
                                    type="button"
                                    @click="
                                        receiptUrl = '{{ asset('storage/' . $req->screenshot_path) }}';
                                        showReceiptModal = true;
                                    "
                                    class="p-1.5 rounded-[10px] text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                    title="View Receipt Image"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-655">-</span>
                            @endif
                        </div>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        <div class="flex flex-col items-center justify-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-zinc-300 dark:text-zinc-700"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span class="text-xs font-semibold text-zinc-500">No deposit requests found.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </div>

    <!-- ─── Sibling Block 3: Pagination Footer Card ─── -->
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
            class="relative w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl p-6 text-left z-10 transition-transform duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-4">
                Filter Deposit Requests
            </h3>

            <div class="space-y-4" x-data="{ statusVal: '{{ request('status') }}' }">
                <div>
                    <label class="block text-[11px] font-black uppercase text-zinc-500 dark:text-zinc-400 mb-1.5">
                        Status
                    </label>
                    <div class="relative flex items-center">
                        <select 
                            x-model="statusVal"
                            class="appearance-none w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white pl-3 pr-8 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 cursor-pointer"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-500 absolute right-3 pointer-events-none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                <div class="flex gap-2.5 mt-6">
                    <button
                        type="button"
                        @click="statusVal = ''; $refs.statusInput.value = ''; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="flex-1 py-2 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer select-none"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="$refs.statusInput.value = statusVal; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="flex-1 py-2 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-lg transition-all cursor-pointer shadow-xs active:scale-95 select-none"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Deposit Modal -->
    <div 
        x-show="showRequestModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Overlay Backing -->
        <div @click="showRequestModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showRequestModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-250 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-md relative z-50 animate-fadeIn"
        >
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Request Savings Deposit</h3>
                <button @click="showRequestModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-white transition-colors cursor-pointer select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form action="{{ route('member.savings.request') }}" method="POST" enctype="multipart/form-data" class="p-6 flex flex-col gap-5">
                @csrf

                <!-- Transaction Amount -->
                <x-premium-input 
                    label="Transaction Amount ($)" 
                    name="amount" 
                    type="number" 
                    step="0.01" 
                    min="0.01" 
                    required 
                    placeholder="e.g. 150.00" 
                />

                <!-- Date -->
                <x-premium-datepicker 
                    label="Transaction Date" 
                    name="transaction_date" 
                    required 
                    value="{{ now()->toDateString() }}" 
                />

                <!-- Screenshot Proof -->
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 block">
                        Proof of Payment Receipt <span class="text-red-500">*</span>
                    </label>
                    <div class="relative border border-dashed border-zinc-350 dark:border-zinc-800 rounded-xl p-4 bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 transition-all flex flex-col items-center justify-center gap-2 cursor-pointer group">
                        <input 
                            type="file" 
                            name="screenshot" 
                            id="screenshot" 
                            required 
                            accept="image/*"
                            class="absolute inset-0 opacity-0 cursor-pointer"
                            @change="const file = $event.target.files[0]; if (file) { fileName = file.name; previewUrl = URL.createObjectURL(file); }"
                        />
                        <div class="p-3 bg-zinc-100 dark:bg-zinc-900 rounded-2xl border border-zinc-200/50 dark:border-zinc-800 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-zinc-555 dark:text-zinc-450"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        </div>
                        <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200 text-center" x-text="fileName || 'Upload Screenshot / Receipt'"></div>
                        <div class="text-[10px] text-zinc-400 dark:text-zinc-500 text-center">PNG, JPG, JPEG up to 2MB</div>
                        <template x-if="previewUrl">
                            <div class="mt-2 w-full max-h-32 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 flex items-center justify-center p-1 relative z-10">
                                <img :src="previewUrl" class="max-h-28 object-contain rounded" />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Notes -->
                <x-premium-textarea 
                    label="Reference Notes / Description" 
                    name="notes" 
                    id="notes" 
                    rows="2" 
                    placeholder="Provide details about the Zelle or bank transfer..."
                />

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 mt-2 w-full">
                    <x-premium-button type="button" variant="secondary" @click="showRequestModal = false" class="py-2.5 w-full sm:w-1/2">
                        Cancel
                    </x-premium-button>
                    <x-premium-button type="submit" variant="primary" class="py-2.5 w-full sm:w-1/2">
                        Submit Request
                    </x-premium-button>
                </div>
            </form>
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
            class="bg-white dark:bg-zinc-950 border border-zinc-250 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-2xl relative z-50 overflow-hidden flex flex-col"
        >
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Proof of Payment Receipt</h3>
                <button @click="showReceiptModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-white transition-colors cursor-pointer select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="p-6 flex items-center justify-center bg-zinc-50 dark:bg-zinc-900 max-h-[70vh] overflow-y-auto">
                <img :src="receiptUrl" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-md border border-zinc-200 dark:border-zinc-800" />
            </div>
        </div>
    </div>
</div>
@endsection

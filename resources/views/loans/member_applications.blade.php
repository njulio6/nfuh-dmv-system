@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{
        showFilterModal: false,
        showGuarantorsModal: false,
        showDetailsModal: false,
        activeGuarantors: [],
        activeAdminNote: '',
        showRequestModal: false,
        guarantors: {{ Js::from(array_fill(0, $minGuarantors, '')) }},
        minGuarantors: {{ $minGuarantors }},
        maxGuarantors: {{ $maxGuarantors }},
        addGuarantor() {
            if (this.guarantors.length < this.maxGuarantors) {
                this.guarantors.push('');
            }
        },
        removeGuarantor(index) {
            if (this.guarantors.length > this.minGuarantors) {
                this.guarantors.splice(index, 1);
            }
        }
    }"
    class="w-full font-sans"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="loans-filter-form" method="GET" action="{{ route('member.loans.applications') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="status" value="{{ request('status') }}" x-ref="statusInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $loans->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- ─── Top Control Bar ─── -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search by purpose or amount..."
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
            <!-- Apply For Loan Button -->
            @if($member->savings_balance >= $minSavings)
                <button 
                    type="button"
                    @click="showRequestModal = true"
                    class="px-5 py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer select-none"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Apply For Loan</span>
                </button>
            @else
                <button 
                    type="button"
                    disabled
                    class="px-5 py-2 bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-650 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 cursor-not-allowed select-none"
                >
                    <span>Threshold Not Met</span>
                </button>
            @endif

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('member.loans.applications') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                title="Reload & Clear Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>

            <!-- Filter Popover Button -->
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

    <!-- ─── Data Table Card ─── -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-x-auto relative mb-6">
        <x-premium-table :headers="[
            ['label' => 'SI', 'width' => 'min-w-[40px]', 'align' => 'center'],
            ['label' => 'Application Date', 'width' => 'min-w-[130px]'],
            ['label' => 'Amount Requested', 'width' => 'min-w-[130px]'],
            ['label' => 'Term', 'width' => 'min-w-[90px]'],
            ['label' => 'Purpose', 'width' => 'min-w-[100px]'],
            ['label' => 'Outstanding Balance', 'width' => 'min-w-[140px]'],
            ['label' => 'Statement', 'width' => 'min-w-[90px]'],
            ['label' => 'Status', 'width' => 'min-w-[190px]'],
            ['label' => 'Actions', 'width' => 'min-w-[90px]', 'align' => 'center']
        ]">  
            @forelse($loans as $index => $loan)
                @php
                    $serialIndex = $index + 1 + ($loans->currentPage() - 1) * $loans->perPage();
                    $isEven = $index % 2 === 1;
                @endphp
                <x-premium-table-row :is-even="$isEven">
                    <!-- SI Index -->
                    <td class="py-3 px-3 text-center font-bold text-zinc-500 dark:text-zinc-400 tabular-nums">
                        {{ $serialIndex }}
                    </td>
                    
                    <!-- Date -->
                    <td class="py-3 px-3 text-zinc-800 dark:text-zinc-200 font-semibold select-text">
                        {{ $loan->created_at->format('Y-m-d') }}
                    </td>

                    <!-- Amount -->
                    <td class="py-3 px-3 text-zinc-900 dark:text-white font-extrabold select-text">
                        ${{ number_format($loan->amount, 2) }}
                    </td>

                    <!-- Term -->
                    <td class="py-3 px-3 text-zinc-800 dark:text-zinc-200 font-semibold">
                        {{ $loan->duration_months }} Months
                    </td>

                    <!-- Purpose -->
                    <td class="py-3 px-3 text-zinc-800 dark:text-zinc-250 font-semibold max-w-[180px] truncate select-text" title="{{ $loan->purpose }}">
                        {{ $loan->purpose ?: '-' }}
                    </td>

                    <!-- Outstanding -->
                    <td class="py-3 px-3 text-zinc-900 dark:text-white font-extrabold">
                        ${{ number_format($loan->remaining_balance, 2) }}
                    </td>

                    <!-- Statement Link -->
                    <td class="py-3 px-3">
                        <a
                            href="{{ route('member.loans.statement') }}"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-zinc-650 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white hover:underline transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span>Statement</span>
                        </a>
                    </td>

                    <!-- Status -->
                    <td class="py-3 px-3 whitespace-nowrap">
                        <div class="flex flex-nowrap items-center gap-1.5">
                                @if($loan->status === 'pending_guarantors')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400 border border-zinc-200/60 dark:border-zinc-700/60">
                                        Guarantor Review
                                    </span>
                                @elseif($loan->status === 'pending_committee')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800/40">
                                        Committee Review
                                    </span>
                                @elseif($loan->status === 'approved')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/40">
                                        Approved
                                    </span>
                                @elseif($loan->status === 'active')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                        Active
                                    </span>
                                @elseif($loan->status === 'completed')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-650 dark:bg-zinc-800/60 dark:text-zinc-400 border border-zinc-200/60 dark:border-zinc-700/60">
                                        Completed
                                    </span>
                                @elseif($loan->status === 'rejected')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                        Rejected
                                    </span>
                                @elseif($loan->status === 'defaulted')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400 border border-red-200 dark:border-red-900/40">
                                        Defaulted
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-655 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                        {{ $loan->status }}
                                    </span>
                                @endif

                                @if($loan->subStatus)
                                    @php
                                        $subStatusColorClass = match($loan->subStatus->color) {
                                            'red' => 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border-red-200/60 dark:border-red-800/40',
                                            'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/40',
                                            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40',
                                            'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/40',
                                            'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/40',
                                            default => 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 border-zinc-200/60 dark:border-zinc-700/60',
                                        };
                                    @endphp
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border {{ $subStatusColorClass }}">
                                        {{ $loan->subStatus->name }}
                                    </span>
                                @endif
                        </div>
                    </td>

                    <!-- Actions Button -->
                    <td class="py-3 px-3">
                        <div class="flex items-center justify-center gap-1.5">
                            @php
                                $encodedGuarantors = base64_encode(json_encode($loan->guarantors->map(fn($g) => [
                                    'name' => $g->guarantorMember->name,
                                    'code' => $g->guarantorMember->member_code,
                                    'status' => $g->status,
                                    'responded_at' => $g->responded_at ? $g->responded_at->format('Y-m-d H:i:s') : null,
                                    'notes' => $g->notes
                                ])));
                                $encodedAdminNote = base64_encode($loan->admin_notes ?? '');
                            @endphp
                            <button 
                                type="button"
                                @click="
                                    activeGuarantors = JSON.parse(atob('{{ $encodedGuarantors }}'));
                                    activeAdminNote = atob('{{ $encodedAdminNote }}');
                                    showDetailsModal = true;
                                "
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-[11px] font-bold rounded-[8px] transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer shadow-3xs select-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Details
                            </button>
                        </div>
                    </td>

                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        <div class="flex flex-col items-center justify-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-zinc-300 dark:text-zinc-700"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span class="text-xs font-semibold text-zinc-500">No applications found matching filters.</span>
                        </div>
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
                @if($loans->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <!-- Previous button -->
            <button
                type="button"
                @if(!$loans->onFirstPage())
                    @click="$refs.pageInput.value = {{ $loans->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                @if($loans->onFirstPage()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Previous Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <!-- Page numbers window loop -->
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

            <!-- Next button -->
            <button
                type="button"
                @if($loans->hasMorePages())
                    @click="$refs.pageInput.value = {{ $loans->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$loans->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Next Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <!-- Last page button -->
            <button
                type="button"
                @click="$refs.pageInput.value = {{ $loans->lastPage() }}; $refs.form.submit()"
                @if($loans->currentPage() == $loans->lastPage()) disabled @endif
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
                Filter Applications
            </h3>

            <div class="space-y-4" x-data="{ statusVal: '{{ request('status') }}' }">
                <div>
                    <label class="block text-[11px] font-black uppercase text-zinc-550 dark:text-zinc-400 mb-1.5">
                        Status
                    </label>
                    <div class="relative flex items-center">
                        <select 
                            x-model="statusVal"
                            class="appearance-none w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-850 dark:text-white pl-3 pr-8 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 cursor-pointer"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending_guarantors">Guarantor Review</option>
                            <option value="pending_committee">Committee Review</option>
                            <option value="approved">Approved</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                            <option value="defaulted">Defaulted</option>
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

    <!-- ─── LOAN DETAILS MODAL ─── -->
    <div 
        x-show="showDetailsModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Modal Backdrop -->
        <div
            @click="showDetailsModal = false"
            class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm transition-opacity duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Modal Content Container -->
        <div 
            class="relative w-full max-w-md bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl z-10 p-6 flex flex-col gap-4 transition-transform duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <!-- Header -->
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800/80 mb-1">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider">Loan Details</h3>
                <button @click="showDetailsModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex flex-col gap-5 overflow-y-auto max-h-[70vh] pr-1">

                <!-- Admin Decision Note -->
                <div x-show="activeAdminNote && activeAdminNote.trim() !== ''">
                    <p class="text-[11px] font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">Admin Decision Note</p>
                    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/40 rounded-xl px-4 py-3">
                        <p class="text-xs text-amber-800 dark:text-amber-300 italic font-semibold" x-text="'&quot;' + activeAdminNote + '&quot;'"></p>
                    </div>
                </div>

                <!-- Guarantors Section -->
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">Guarantors</p>
                    <div class="flex flex-col gap-3">
                        <template x-if="activeGuarantors.length === 0">
                            <p class="text-xs text-zinc-400 dark:text-zinc-600 italic">No guarantors assigned to this application.</p>
                        </template>
                        <template x-for="(g, index) in activeGuarantors" :key="index">
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-2.5">
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-zinc-900 dark:text-zinc-100 text-sm truncate" x-text="g.name"></span>
                                        <span class="font-mono text-[10px] text-zinc-400 dark:text-zinc-500 font-bold mt-0.5" x-text="'/' + g.code"></span>
                                    </div>
                                    <!-- Status Badge -->
                                    <template x-if="g.status === 'approved'">
                                        <span class="inline-flex items-center text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">Approved</span>
                                    </template>
                                    <template x-if="g.status === 'declined'">
                                        <span class="inline-flex items-center text-[10px] font-black uppercase tracking-wider text-red-650 dark:text-red-400 bg-red-500/10 px-2 py-0.5 rounded-full border border-red-500/20">Declined</span>
                                    </template>
                                    <template x-if="g.status === 'pending'">
                                        <span class="inline-flex items-center text-[10px] font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400 bg-zinc-500/10 dark:bg-zinc-800/80 px-2 py-0.5 rounded-full border border-zinc-200/60 dark:border-zinc-700/60">Pending</span>
                                    </template>
                                </div>
                                <!-- Response Date / Notes -->
                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 border-t border-zinc-200/30 dark:border-zinc-800/20 pt-2 flex flex-col gap-1">
                                    <div class="flex justify-between" x-show="g.responded_at">
                                        <span>Responded At:</span>
                                        <span class="font-mono font-bold" x-text="g.responded_at"></span>
                                    </div>
                                    <div class="flex flex-col gap-1 mt-1.5" x-show="g.notes">
                                        <span class="font-bold">Comments/Notes:</span>
                                        <p class="text-xs bg-white dark:bg-zinc-950 p-2.5 rounded-lg border border-zinc-200/40 dark:border-zinc-850 text-zinc-700 dark:text-zinc-300 italic" x-text="g.notes"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/60">
                <button type="button" class="w-full py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-lg transition-all cursor-pointer shadow-xs active:scale-95 text-center select-none" @click="showDetailsModal = false">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- REQUEST NEW LOAN MODAL -->
    <div 
        x-show="showRequestModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
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
            class="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-md relative z-50 p-6 flex flex-col gap-4"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-1">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider">Apply for a Loan</h3>
                <button @click="showRequestModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Request Form -->
            <form action="{{ route('member.loans.request') }}" method="POST" class="flex flex-col gap-5">
                @csrf

                <!-- Loan Amount -->
                <x-premium-input 
                    label="Requested Amount ($)" 
                    name="amount" 
                    type="number" 
                    step="0.01" 
                    min="0.01"
                    required 
                    placeholder="e.g. 1000.00" 
                />

                <!-- Duration / Term (Months) -->
                <x-premium-input 
                    label="Repayment Duration (Months)" 
                    name="duration_months" 
                    type="number" 
                    min="1" 
                    max="60"
                    required 
                    placeholder="e.g. 12" 
                />

                <!-- Purpose memo -->
                <x-premium-textarea 
                    label="Loan Purpose / Description" 
                    name="purpose" 
                    rows="3" 
                    placeholder="Explain the purpose of the requested loan..." 
                />

                <!-- Guarantors Selection Section (Alpine state lists) -->
                <div class="flex flex-col w-full">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Designated Guarantors <span class="text-red-500">*</span>
                        </label>
                        <button 
                            type="button" 
                            @click="addGuarantor()" 
                            x-show="guarantors.length < maxGuarantors"
                            class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white cursor-pointer select-none"
                        >
                            + Add (Max <span x-text="maxGuarantors"></span>)
                        </button>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <template x-for="(gItem, index) in guarantors" :key="index">
                            <div class="flex items-center gap-2">
                                <div class="flex-grow relative">
                                    <!-- Searchable Dropdown Wrapper -->
                                    <div 
                                        x-data="{ 
                                            isOpen: false, 
                                            search: '', 
                                            members: @js($otherMembers->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->member_code])),
                                            get selectedMember() {
                                                return this.members.find(m => m.id == guarantors[index]);
                                            },
                                            get displayText() {
                                                const m = this.selectedMember;
                                                return m ? m.name + ' (' + m.code + ')' : 'Choose Guarantor Member';
                                            },
                                            get filteredMembers() {
                                                const currentSearch = this.search.toLowerCase().trim();
                                                return this.members.filter(m => {
                                                    const matchesSearch = !currentSearch || 
                                                        m.name.toLowerCase().includes(currentSearch) || 
                                                        m.code.toLowerCase().includes(currentSearch);
                                                    
                                                    const isSelectedHere = m.id == guarantors[index];
                                                    const isSelectedElsewhere = guarantors.some((gid, idx) => idx !== index && gid == m.id);
                                                    
                                                    return matchesSearch && (isSelectedHere || !isSelectedElsewhere);
                                                });
                                            },
                                            selectMember(m) {
                                                guarantors[index] = m.id;
                                                this.isOpen = false;
                                                this.search = '';
                                            }
                                        }"
                                        class="relative w-full"
                                        @click.outside="isOpen = false"
                                    >
                                        <!-- Hidden Input for Form Submission -->
                                        <input type="hidden" name="guarantors[]" :value="guarantors[index]" required />

                                        <!-- Dropdown Trigger Button -->
                                        <button 
                                            type="button"
                                            @click="isOpen = !isOpen"
                                            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-850 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none transition-all cursor-pointer text-left"
                                            :class="isOpen ? 'border-zinc-950 dark:border-zinc-50 bg-white dark:bg-zinc-900' : ''"
                                        >
                                            <span x-text="displayText" class="truncate"></span>
                                            <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-500 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <!-- Search dropdown panel -->
                                        <div 
                                            x-show="isOpen"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute z-50 mt-1.5 w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl p-2.5 flex flex-col gap-2"
                                            style="display: none;"
                                        >
                                            <!-- Search Input -->
                                            <div class="relative w-full">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                </span>
                                                <input 
                                                    type="text" 
                                                    x-model="search"
                                                    @keydown.escape.stop="isOpen = false"
                                                    placeholder="Type member name or code to search..."
                                                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-650 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-all"
                                                    style="padding-left: 2.25rem;"
                                                />
                                            </div>

                                            <!-- Scrollable Options List -->
                                            <div class="max-h-48 overflow-y-auto flex flex-col gap-0.5 pr-1">
                                                <button
                                                    type="button"
                                                    @click="selectMember({id: '', name: 'Choose Guarantor Member', code: ''})"
                                                    class="w-full text-left px-3 py-2 text-xs font-semibold rounded-lg transition-colors flex items-center justify-between cursor-pointer"
                                                    :class="!guarantors[index] ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-900 dark:hover:text-zinc-200'"
                                                >
                                                    <span>Choose Guarantor Member</span>
                                                </button>
                                                
                                                <template x-for="m in filteredMembers" :key="m.id">
                                                    <button
                                                        type="button"
                                                        @click="selectMember(m)"
                                                        class="w-full text-left px-3 py-2.5 rounded-lg transition-colors flex items-center justify-between cursor-pointer"
                                                        :class="guarantors[index] == m.id ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-900 dark:hover:text-zinc-200'"
                                                    >
                                                        <div class="flex flex-col min-w-0">
                                                            <span x-text="m.name" class="font-bold text-xs truncate"></span>
                                                            <span x-text="m.code" class="text-[9px] text-zinc-400 dark:text-zinc-550 font-bold font-mono mt-0.5"></span>
                                                        </div>
                                                        <span x-show="guarantors[index] == m.id" class="text-zinc-950 dark:text-white font-black text-xs">✓</span>
                                                    </button>
                                                </template>
                                                
                                                <div x-show="filteredMembers.length === 0" class="text-center text-zinc-400 dark:text-zinc-600 py-6 text-xs font-semibold">
                                                    No members found
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    @click="removeGuarantor(index)" 
                                    class="p-2 border border-zinc-200 dark:border-zinc-800 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-850 text-red-500 transition-colors select-none cursor-pointer"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" class="flex-grow py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-lg transition-all cursor-pointer shadow-xs active:scale-95 text-center select-none" @click="showRequestModal = false">
                        Cancel
                    </button>
                    <button type="submit" class="flex-grow py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-lg transition-all cursor-pointer shadow-xs active:scale-95 text-center select-none">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

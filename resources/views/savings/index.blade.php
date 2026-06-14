@extends('layouts.app')

@section('content')
<!-- Wrapper for Alpine state context -->
<div 
    x-data="{ 
        showModal: false, 
        selectedMemberId: '',
        showFilterModal: false
    }"
    class="w-full"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="savings-filter-form" method="GET" action="{{ route('savings.index') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="eligibility" value="{{ request('eligibility') }}" x-ref="eligibilityInput">
        <input type="hidden" name="state_code" value="{{ request('state_code') }}" x-ref="stateInput">
        <input type="hidden" name="member_id" value="{{ request('member_id') }}" x-ref="memberIdInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $members->currentPage() }}" x-ref="pageInput">
    </form>


    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search by ID, name, or email..."
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
            <!-- Post Transaction Button -->
            <button 
                type="button"
                @click="showModal = true; selectedMemberId = ''"
                class="px-5 py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer select-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Post Transaction</span>
            </button>

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('savings.index') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                title="Reload & Clear Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>

            <!-- Filter Popover Button -->
            <button 
                type="button"
                @click="showFilterModal = true"
                class="p-2.5 border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative select-none {{ request('eligibility') || request('state_code') || request('member_id') ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                title="Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                @if(request('eligibility') || request('state_code') || request('member_id'))
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- ─── Sibling Block 2: Data Table Card ─── -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative mb-6">
        <x-premium-table :headers="[
            'Member ID',
            'Name',
            'State',
            'Savings Balance',
            'Loan Status',
            ['label' => 'Actions', 'align' => 'center']
        ]">
            @forelse($members as $m)
                <x-premium-table-row :is-even="$loop->index % 2 === 1">
                    <td class="py-3 px-3 font-mono font-bold text-zinc-500 dark:text-zinc-450 text-xs">
                        {{ $m->member_code }}
                    </td>
                    <td class="py-3 px-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ $m->name }}
                    </td>
                    <td class="py-3 px-3 font-mono font-bold text-zinc-500 text-xs">
                        {{ $m->state_code ?: '-' }}
                    </td>
                    <td class="py-3 px-3 font-bold text-zinc-950 dark:text-white text-sm">
                        ${{ number_format($m->savings_balance, 2) }}
                    </td>
                    <td class="py-3 px-3">
                        @if($m->savings_balance >= ($appSettings->min_savings_for_loan ?? 500))
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Loan Eligible
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-600 dark:text-amber-500" title="Under ${{ number_format($appSettings->min_savings_for_loan ?? 500, 0) }} Limit">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Under Limit
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-3 text-center">
                        <button 
                            @click="showModal = true; selectedMemberId = '{{ $m->id }}'" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-[11px] font-bold rounded-[8px] transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer shadow-3xs select-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 text-zinc-500"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Log Transaction</span>
                        </button>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        No members found matching the specified parameters.
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
                @if($members->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <!-- Previous button -->
            <button
                type="button"
                @if(!$members->onFirstPage())
                    @click="$refs.pageInput.value = {{ $members->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                @if($members->onFirstPage()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Previous Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <!-- Page numbers window loop -->
            @php
                $startPage = max(1, $members->currentPage() - 2);
                $endPage = min($members->lastPage(), $members->currentPage() + 2);
            @endphp
            @for ($page = $startPage; $page <= $endPage; $page++)
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                    class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                        {{ $page == $members->currentPage()
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
                @if($members->hasMorePages())
                    @click="$refs.pageInput.value = {{ $members->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$members->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Next Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <!-- Last page button -->
            <button
                type="button"
                @click="$refs.pageInput.value = {{ $members->lastPage() }}; $refs.form.submit()"
                @if($members->currentPage() == $members->lastPage()) disabled @endif
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
            class="relative w-full max-w-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl p-7 text-left z-10 transition-transform duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-5">
                Filter Savings Members
            </h3>

            <!-- Member JSON Data Store for filter -->
            <script id="filter-members-data" type="application/json">
                {!! json_encode($allMembers->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->member_code])) !!}
            </script>

            <div 
                class="space-y-4" 
                x-data="{ 
                    eligibilityVal: '{{ request('eligibility') }}', 
                    stateVal: '{{ request('state_code') }}',
                    memberIdVal: '{{ request('member_id') }}',
                    memberSearch: '',
                    memberOpen: false,
                    memberNameVal: '',
                    members: JSON.parse(document.getElementById('filter-members-data').textContent),
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
                            <span x-text="memberNameVal || 'All Members'" :class="!memberNameVal && 'text-zinc-400 dark:text-zinc-550'"></span>
                            <div class="flex items-center gap-1.5">
                                <span x-show="memberNameVal" @click.stop="clearMember()" class="hover:text-zinc-900 dark:hover:text-white p-0.5 cursor-pointer text-zinc-400 hover:scale-105 transition-all">
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
                            class="absolute z-50 w-full mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg max-h-72 overflow-hidden flex flex-col"
                        >
                            <!-- Search Input -->
                            <div class="p-2 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="relative flex items-center">
                                    <span class="absolute left-2.5 text-zinc-400 dark:text-zinc-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </span>
                                    <input
                                        type="text"
                                        x-model="memberSearch"
                                        placeholder="Search name or code..."
                                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-650 !pl-8 pr-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                                        style="padding-left: 2rem;"
                                    />
                                </div>
                            </div>

                            <!-- Options List -->
                            <div class="overflow-y-auto max-h-56 flex-1">
                                <!-- Option to clear/All -->
                                <button
                                    type="button"
                                    @click="clearMember()"
                                    class="w-full text-left px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-sm font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white cursor-pointer transition-colors"
                                >
                                    All Members
                                </button>
                                <template x-for="m in filteredMembers" :key="m.id">
                                    <button
                                        type="button"
                                        @click="selectMember(m.id, m.name, m.code)"
                                        class="w-full text-left px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-sm font-semibold text-zinc-750 dark:text-zinc-300 hover:text-zinc-955 dark:hover:text-white cursor-pointer flex justify-between items-center transition-colors"
                                    >
                                        <span x-text="m.name"></span>
                                        <span class="font-mono text-xs text-zinc-450 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-950 px-2 py-0.5 rounded" x-text="m.code"></span>
                                    </button>
                                </template>
                                <div x-show="filteredMembers.length === 0" class="px-3 py-2 text-center text-xs text-zinc-400 dark:text-zinc-600 font-semibold">
                                    No members found
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-zinc-500 dark:text-zinc-400 mb-2">
                        Loan Status
                    </label>
                    <div class="relative flex items-center">
                        <select 
                            x-model="eligibilityVal"
                            class="appearance-none w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white pl-4 pr-8 py-2.5 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 cursor-pointer"
                        >
                            <option value="">All Statuses</option>
                            <option value="eligible">Loan Eligible</option>
                            <option value="under_limit">Under Limit</option>
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-500 absolute right-3 pointer-events-none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-zinc-500 dark:text-zinc-400 mb-2">
                        State Code
                    </label>
                    <div class="relative flex items-center">
                        <select 
                            x-model="stateVal"
                            class="appearance-none w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white pl-4 pr-8 py-2.5 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 cursor-pointer"
                        >
                            <option value="">All States</option>
                            <option value="MD">Maryland (MD)</option>
                            <option value="VA">Virginia (VA)</option>
                            <option value="DC">District of Columbia (DC)</option>
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-500 absolute right-4 pointer-events-none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                <div class="flex gap-3 mt-7">
                    <button
                        type="button"
                        @click="eligibilityVal = ''; stateVal = ''; memberIdVal = ''; $refs.eligibilityInput.value = ''; $refs.stateInput.value = ''; $refs.memberIdInput.value = ''; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="flex-1 py-3 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-sm font-bold rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer select-none"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="$refs.eligibilityInput.value = eligibilityVal; $refs.stateInput.value = stateVal; $refs.memberIdInput.value = memberIdVal; $refs.pageInput.value = 1; $refs.form.submit()"
                        class="flex-1 py-3 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-sm font-bold rounded-lg transition-all cursor-pointer shadow-xs active:scale-95 select-none"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Post Modal -->
    <div 
        x-show="showModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Overlay Backing -->
        <div @click="showModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-250 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-md relative z-50 animate-fadeIn"
        >
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Post Savings Transaction</h3>
                <button @click="showModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-white transition-colors cursor-pointer select-none">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('savings.store') }}" method="POST" class="p-6 flex flex-col gap-5">
                @csrf

                <!-- Member JSON Data Store (avoids double quotes in HTML attributes) -->
                <script id="members-data" type="application/json">
                    {!! json_encode($allMembers->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->member_code])) !!}
                </script>

                <!-- Member Selection (Searchable Dropdown) -->
                <div 
                    x-data="{
                        open: false,
                        search: '',
                        selectedId: '',
                        selectedName: '',
                        members: JSON.parse(document.getElementById('members-data').textContent),
                        get filteredMembers() {
                            if (this.search.trim() === '') return this.members;
                            return this.members.filter(m => 
                                m.name.toLowerCase().includes(this.search.toLowerCase()) || 
                                m.code.toLowerCase().includes(this.search.toLowerCase())
                            );
                        },
                        selectMember(id, name, code) {
                            this.selectedId = id;
                            this.selectedName = name + ' (' + code + ')';
                            selectedMemberId = id;
                            this.open = false;
                            this.search = '';
                        },
                        init() {
                            this.$watch('selectedMemberId', value => {
                                if (!value) {
                                    this.selectedId = '';
                                    this.selectedName = '';
                                } else {
                                    const match = this.members.find(m => m.id == value);
                                    if (match) {
                                        this.selectedId = match.id;
                                        this.selectedName = match.name + ' (' + match.code + ')';
                                    }
                                }
                            });
                        }
                    }"
                    class="flex flex-col w-full relative"
                    @click.outside="open = false"
                >
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Member <span class="text-red-500">*</span>
                    </label>

                    <!-- Hidden input to hold the value for form submission -->
                    <input type="hidden" name="member_id" :value="selectedId" required />

                    <div class="relative w-full">
                        <!-- Trigger Button -->
                        <button
                            type="button"
                            @click="open = !open"
                            class="w-full text-left bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer flex justify-between items-center"
                        >
                            <span x-text="selectedName || 'Select Member...'" :class="!selectedName && 'text-zinc-400 dark:text-zinc-650'"></span>
                            <svg class="h-4 w-4 text-zinc-450 transition-transform" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Dropdown Panel -->
                        <div
                            x-show="open"
                            x-cloak
                            class="absolute z-50 w-full mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg max-h-60 overflow-hidden flex flex-col"
                        >
                            <!-- Search Input -->
                            <div class="p-2 border-b border-zinc-100 dark:border-zinc-800">
                                <div class="relative flex items-center">
                                    <span class="absolute left-3 text-zinc-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </span>
                                    <input
                                        type="text"
                                        x-model="search"
                                        placeholder="Type name or code to search..."
                                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 !pl-8 pr-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-805 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                                        style="padding-left: 2rem;"
                                    />
                                </div>
                            </div>

                            <!-- Options List -->
                            <div class="overflow-y-auto max-h-48 flex-1">
                                <template x-for="m in filteredMembers" :key="m.id">
                                    <button
                                        type="button"
                                        @click="selectMember(m.id, m.name, m.code)"
                                        class="w-full text-left px-4 py-2 hover:bg-zinc-55 dark:hover:bg-zinc-800 text-xs font-semibold text-zinc-750 dark:text-zinc-350 cursor-pointer flex justify-between items-center transition-colors"
                                    >
                                        <span x-text="m.name"></span>
                                        <span class="font-mono text-[10px] text-zinc-450 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-950 px-1.5 py-0.5 rounded" x-text="m.code"></span>
                                    </button>
                                </template>
                                <div x-show="filteredMembers.length === 0" class="px-4 py-3 text-center text-xs text-zinc-400 dark:text-zinc-600 font-semibold">
                                    No members found
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Amount -->
                <x-premium-input 
                    label="Transaction Amount ($)" 
                    name="amount" 
                    type="number" 
                    step="0.01" 
                    min="0.01" 
                    required 
                    placeholder="e.g. 500.00" 
                />

                <!-- Transaction Type -->
                <x-premium-select 
                    label="Transaction Type" 
                    name="type" 
                    id="type" 
                    required
                >
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                    <option value="adjustment">Adjustment (Balance Override)</option>
                </x-premium-select>

                <!-- Transaction Date -->
                <x-premium-datepicker 
                    label="Transaction Date" 
                    name="transaction_date" 
                    required 
                    value="{{ now()->toDateString() }}" 
                />

                <!-- Optional Note -->
                <x-premium-textarea 
                    label="Reference Notes" 
                    name="notes" 
                    id="notes" 
                    rows="2" 
                    placeholder="Zelle transaction ID, bank reference, etc..."
                />

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 mt-2 w-full">
                    <x-premium-button type="button" variant="secondary" @click="showModal = false" class="py-2.5 w-full sm:w-1/2">
                        Cancel
                    </x-premium-button>
                    <x-premium-button type="submit" variant="primary" class="py-2.5 w-full sm:w-1/2">
                        Save Transaction
                    </x-premium-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

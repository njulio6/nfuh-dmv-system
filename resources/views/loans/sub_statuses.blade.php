@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{
        showAddModal: false,
        showEditModal: false,
        showDeleteModal: false,
        deleteStatusId: null,
        deleteStatusName: '',
        editStatusId: null,
        editStatusName: '',
        editStatusColor: 'slate'
    }"
    class="w-full font-sans"
>
    <!-- Main Form container tracking filters, search, page & size -->
    <form id="sub-statuses-filter-form" method="GET" action="{{ route('loans.sub-statuses') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $subStatuses->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- Validation Errors Alert Block -->
    @if ($errors->any())
        <div class="p-3.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-1.5 mb-6">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-650 shrink-0"></i>
                <span class="font-bold">Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside pl-1 text-[11px] font-medium flex flex-col gap-0.5 mt-1 text-red-700 dark:text-red-400/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ─── Sibling Block 1: Top Control Bar ─── -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search by sub-status name..."
                    value="{{ request('search') }}"
                    @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                    @blur="$refs.searchInput.value = $el.value"
                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-650 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-450 dark:focus:border-zinc-700 transition-colors"
                    style="padding-left: 2.25rem;"
                />
            </div>
        </div>

        <!-- Right: Action Buttons Group -->
        <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:justify-end">
            <!-- Add Action Button -->
            <button 
                type="button"
                @click="showAddModal = true"
                class="px-5 py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer select-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Sub-Status</span>
            </button>

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('loans.sub-statuses') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                title="Reload & Clear Filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>
        </div>
    </div>

    <!-- ─── Sibling Block 2: Data Table Card ─── -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative mb-6">
        <x-premium-table :headers="[
            ['label' => 'SI', 'width' => 'w-12', 'align' => 'center'],
            ['label' => 'Indicator', 'width' => 'w-20', 'align' => 'center'],
            ['label' => 'Sub-Status Name'],
            ['label' => 'Badge Preview'],
            ['label' => 'Created At'],
            ['label' => 'Actions', 'width' => 'w-28', 'align' => 'center']
        ]" class="min-w-[600px]">
            @forelse ($subStatuses as $index => $ss)
                @php
                    $serialIndex = $index + 1 + ($subStatuses->currentPage() - 1) * $subStatuses->perPage();
                    $isEven = $index % 2 === 1;
                    $badgeColor = match($ss->color) {
                        'red' => 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border-red-200/60 dark:border-red-800/40',
                        'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/40',
                        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40',
                        'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/40',
                        'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/40',
                        default => 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 border-zinc-200/60 dark:border-zinc-700/60',
                    };
                    $dotColor = match($ss->color) {
                        'red' => '#ef4444',
                        'amber' => '#f59e0b',
                        'emerald' => '#10b981',
                        'blue' => '#3b82f6',
                        'indigo' => '#6366f1',
                        default => '#71717a'
                    };
                @endphp
                <x-premium-table-row :is-even="$isEven">
                    <!-- SI serial index cell -->
                    <td class="py-2.5 px-3 text-center font-bold text-zinc-500 dark:text-zinc-400 tabular-nums">
                        {{ $serialIndex }}
                    </td>
                    
                    <!-- Indicator Dot Cell -->
                    <td class="py-2.5 px-3 text-center">
                        <span class="inline-block size-3.5 rounded-full border border-white dark:border-zinc-850 shadow-3xs" style="background-color: {{ $dotColor }}"></span>
                    </td>
                    
                    <!-- Name Cell -->
                    <td class="py-2.5 px-3 text-zinc-900 dark:text-white font-bold text-sm select-text">
                        {{ $ss->name }}
                    </td>

                    <!-- Preview Badge Cell -->
                    <td class="py-2.5 px-3">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border {{ $badgeColor }}">
                            {{ $ss->name }}
                        </span>
                    </td>

                    <!-- Created At Cell -->
                    <td class="py-2.5 px-3 text-zinc-800 dark:text-zinc-250 font-semibold select-text">
                        {{ $ss->created_at->format('Y-m-d H:i') }}
                    </td>

                    <!-- Actions cell -->
                    <td class="py-2.5 px-3">
                        <div class="flex items-center justify-center gap-1.5">
                            <button 
                                type="button"
                                @click="editStatusId = {{ $ss->id }}; editStatusName = '{{ addslashes($ss->name) }}'; editStatusColor = '{{ $ss->color }}'; showEditModal = true"
                                class="p-1.5 rounded-[10px] text-zinc-650 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="Edit Sub-Status"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4z"></path></svg>
                            </button>
                            <button 
                                type="button"
                                @click="deleteStatusId = {{ $ss->id }}; deleteStatusName = '{{ addslashes($ss->name) }}'; showDeleteModal = true"
                                class="p-1.5 rounded-[10px] text-red-500 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="Delete Sub-Status"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </div>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        <div class="flex flex-col items-center justify-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-zinc-300 dark:text-zinc-700"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                            <span class="text-xs font-semibold text-zinc-550">No custom sub-statuses found matching filters.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </div>

    <!-- ─── Sibling Block 3: Pagination Footer Card ─── -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
        <!-- Left: Rows per Page buttons -->
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
                                : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-650 dark:text-zinc-400' 
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
                @if($subStatuses->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <!-- Previous button -->
            <button
                type="button"
                @if(!$subStatuses->onFirstPage())
                    @click="$refs.pageInput.value = {{ $subStatuses->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                @if($subStatuses->onFirstPage()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-750 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Previous Page"
            >
                <svg xmlns="http://www.w3.org/2050/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <!-- Page numbers window loop -->
            @php
                $startPage = max(1, $subStatuses->currentPage() - 2);
                $endPage = min($subStatuses->lastPage(), $subStatuses->currentPage() + 2);
            @endphp
            @for ($page = $startPage; $page <= $endPage; $page++)
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                    class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                        {{ $page == $subStatuses->currentPage()
                            ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                            : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-650 dark:text-zinc-400' 
                        }}"
                >
                    {{ $page }}
                </button>
            @endfor

            <!-- Next button -->
            <button
                type="button"
                @if($subStatuses->hasMorePages())
                    @click="$refs.pageInput.value = {{ $subStatuses->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$subStatuses->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-750 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Next Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <!-- Last page button -->
            <button
                type="button"
                @click="$refs.pageInput.value = {{ $subStatuses->lastPage() }}; $refs.form.submit()"
                @if($subStatuses->currentPage() == $subStatuses->lastPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                Last
            </button>
        </div>
    </div>

    <!-- ─── ADD SUB-STATUS POPUP MODAL ─── -->
    <div 
        x-show="showAddModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Modal Backdrop -->
        <div
            @click="showAddModal = false"
            class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm transition-opacity duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>
        
        <!-- Modal Container -->
        <div 
            class="relative w-full max-w-md bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl z-10 p-6 flex flex-col gap-4 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800/80 mb-1">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider">Add Custom Sub-Status</h3>
                <button @click="showAddModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Add Sub-status Form -->
            <form method="POST" action="{{ route('admin.settings.store-sub-status') }}" class="flex flex-col gap-5">
                @csrf
                
                <x-premium-input 
                    label="Sub-Status Name" 
                    name="name" 
                    placeholder="e.g. Grace Period"
                    required 
                />
                
                <!-- Custom Premium Color Dropdown -->
                <div class="flex flex-col w-full" x-data="{
                    open: false,
                    selected: 'slate',
                    colors: [
                        { value: 'slate', label: 'Slate (Default)', badgeText: 'Slate', dotClass: 'bg-zinc-400 dark:bg-zinc-500', badgeClass: 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 border-zinc-200/60 dark:border-zinc-700/60' },
                        { value: 'blue', label: 'Blue', badgeText: 'Blue', dotClass: 'bg-blue-500', badgeClass: 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/40' },
                        { value: 'indigo', label: 'Indigo', badgeText: 'Indigo', dotClass: 'bg-indigo-500', badgeClass: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/40' },
                        { value: 'emerald', label: 'Emerald', badgeText: 'Emerald', dotClass: 'bg-emerald-500', badgeClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40' },
                        { value: 'amber', label: 'Amber', badgeText: 'Amber', dotClass: 'bg-amber-500', badgeClass: 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/40' },
                        { value: 'red', label: 'Red', badgeText: 'Red', dotClass: 'bg-red-500', badgeClass: 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border-red-200/60 dark:border-red-800/40' }
                    ],
                    get current() {
                        return this.colors.find(c => c.value === this.selected) || this.colors[0];
                    }
                }">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Badge Color Indicator <span class="text-red-500">*</span>
                    </label>
                    
                    <input type="hidden" name="color" :value="selected">
                    
                    <div class="relative w-full">
                        <!-- Trigger Button -->
                        <button 
                            type="button"
                            @click="open = !open"
                            @click.away="open = false"
                            class="w-full inline-flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer shadow-sm select-none"
                            :class="open ? 'border-zinc-950 dark:border-zinc-50 ring-2 ring-zinc-950/10 dark:ring-white/10' : ''"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="inline-block size-3 rounded-full border border-white dark:border-zinc-800 shadow-3xs" :class="current.dotClass"></span>
                                <span x-text="current.label" class="font-semibold text-sm"></span>
                            </div>
                            
                            <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-650 transition-transform duration-250" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        
                        <!-- Dropdown Menu Options -->
                        <div 
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 transform scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 transform scale-95 -translate-y-2"
                            class="absolute left-0 right-0 z-50 mt-1.5 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl max-h-60 overflow-y-auto py-1 flex flex-col gap-0.5"
                            style="display: none;"
                        >
                            <template x-for="color in colors" :key="color.value">
                                <button 
                                    type="button"
                                    @click="selected = color.value; open = false"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 hover:text-zinc-950 dark:hover:text-white transition-all text-xs font-semibold text-left select-none cursor-pointer flex-row gap-2"
                                    :class="selected === color.value ? 'bg-zinc-50 dark:bg-zinc-900/40 text-zinc-950 dark:text-white font-bold' : ''"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-block size-3 rounded-full border border-white dark:border-zinc-800 shadow-3xs" :class="color.dotClass"></span>
                                        <div class="flex items-center gap-1.5">
                                            <span x-text="color.label"></span>
                                            <svg x-show="selected === color.value" class="w-3.5 h-3.5 text-zinc-950 dark:text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Preview Badge -->
                                    <span 
                                        class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border"
                                        :class="color.badgeClass"
                                        x-text="color.badgeText"
                                    ></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800/60 mt-2">
                    <button 
                        type="button" 
                        @click="showAddModal = false" 
                        class="flex-1 py-2.5 border border-zinc-200/80 dark:border-zinc-800/85 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer text-center"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl transition-all cursor-pointer text-center shadow-xs active:scale-95"
                    >
                        Create Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── EDIT SUB-STATUS POPUP MODAL ─── -->
    <div 
        x-show="showEditModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Modal Backdrop -->
        <div
            @click="showEditModal = false"
            class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm transition-opacity duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>
        
        <!-- Modal Container -->
        <div 
            class="relative w-full max-w-md bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl z-10 p-6 flex flex-col gap-4 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800/80 mb-1">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider">Edit Custom Sub-Status</h3>
                <button @click="showEditModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Edit Sub-status Form -->
            <form method="POST" :action="'{{ url('/settings/loan-sub-statuses') }}/' + editStatusId" class="flex flex-col gap-5">
                @csrf
                @method('PATCH')
                
                <x-premium-input 
                    label="Sub-Status Name" 
                    name="name" 
                    x-model="editStatusName"
                    placeholder="e.g. Grace Period"
                    required 
                />
                
                <!-- Custom Premium Color Dropdown -->
                <div class="flex flex-col w-full" x-data="{
                    open: false,
                    colors: [
                        { value: 'slate', label: 'Slate (Default)', badgeText: 'Slate', dotClass: 'bg-zinc-400 dark:bg-zinc-500', badgeClass: 'bg-zinc-50 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 border-zinc-200/60 dark:border-zinc-700/60' },
                        { value: 'blue', label: 'Blue', badgeText: 'Blue', dotClass: 'bg-blue-500', badgeClass: 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/40' },
                        { value: 'indigo', label: 'Indigo', badgeText: 'Indigo', dotClass: 'bg-indigo-500', badgeClass: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/40' },
                        { value: 'emerald', label: 'Emerald', badgeText: 'Emerald', dotClass: 'bg-emerald-500', badgeClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40' },
                        { value: 'amber', label: 'Amber', badgeText: 'Amber', dotClass: 'bg-amber-500', badgeClass: 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/40' },
                        { value: 'red', label: 'Red', badgeText: 'Red', dotClass: 'bg-red-500', badgeClass: 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border-red-200/60 dark:border-red-800/40' }
                    ],
                    get current() {
                        return this.colors.find(c => c.value === editStatusColor) || this.colors[0];
                    }
                }">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Badge Color Indicator <span class="text-red-500">*</span>
                    </label>
                    
                    <input type="hidden" name="color" :value="editStatusColor">
                    
                    <div class="relative w-full">
                        <!-- Trigger Button -->
                        <button 
                            type="button"
                            @click="open = !open"
                            @click.away="open = false"
                            class="w-full inline-flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer shadow-sm select-none"
                            :class="open ? 'border-zinc-950 dark:border-zinc-50 ring-2 ring-zinc-950/10 dark:ring-white/10' : ''"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="inline-block size-3 rounded-full border border-white dark:border-zinc-800 shadow-3xs" :class="current.dotClass"></span>
                                <span x-text="current.label" class="font-semibold text-sm"></span>
                            </div>
                            
                            <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-650 transition-transform duration-250" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        
                        <!-- Dropdown Menu Options -->
                        <div 
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 transform scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 transform scale-95 -translate-y-2"
                            class="absolute left-0 right-0 z-50 mt-1.5 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl max-h-60 overflow-y-auto py-1 flex flex-col gap-0.5"
                            style="display: none;"
                        >
                            <template x-for="color in colors" :key="color.value">
                                <button 
                                    type="button"
                                    @click="editStatusColor = color.value; open = false"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/60 hover:text-zinc-950 dark:hover:text-white transition-all text-xs font-semibold text-left select-none cursor-pointer flex-row gap-2"
                                    :class="editStatusColor === color.value ? 'bg-zinc-50 dark:bg-zinc-900/40 text-zinc-950 dark:text-white font-bold' : ''"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-block size-3 rounded-full border border-white dark:border-zinc-800 shadow-3xs" :class="color.dotClass"></span>
                                        <div class="flex items-center gap-1.5">
                                            <span x-text="color.label"></span>
                                            <svg x-show="editStatusColor === color.value" class="w-3.5 h-3.5 text-zinc-950 dark:text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Preview Badge -->
                                    <span 
                                        class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border"
                                        :class="color.badgeClass"
                                        x-text="color.badgeText"
                                    ></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800/60 mt-2">
                    <button 
                        type="button" 
                        @click="showEditModal = false" 
                        class="flex-1 py-2.5 border border-zinc-200/80 dark:border-zinc-800/85 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer text-center"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl transition-all cursor-pointer text-center shadow-xs active:scale-95"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Delete Confirmation Modal ─── -->
    <div 
        x-show="showDeleteModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Modal Backdrop -->
        <div
            @click="showDeleteModal = false"
            class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm transition-opacity duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Modal Container -->
        <div 
            class="relative w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl p-6 text-center z-10 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <div class="w-11 h-11 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-500 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
            </div>
            
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Delete this sub-status?</h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 leading-relaxed">
                Permanently delete custom sub-status <span class="font-semibold text-zinc-800 dark:text-zinc-200" x-text="deleteStatusName"></span>? Any loans currently assigned this status will have it set to null.
            </p>
            
            <div class="flex gap-2.5 mt-5">
                <button
                    type="button"
                    @click="showDeleteModal = false"
                    class="flex-1 py-2.5 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                >
                    Keep it
                </button>
                <form :action="'{{ url('/settings/loan-sub-statuses') }}/' + deleteStatusId" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="w-full py-2.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 transition-all cursor-pointer active:scale-95"
                    >
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

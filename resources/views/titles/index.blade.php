@extends('layouts.app')

@section('content')

<!-- Wrapper for Alpine state context -->
<div 
    x-data="{
        showDeleteModal: false,
        deleteTitleId: null,
        deleteTitleName: ''
    }"
    class="w-full"
>
    <!-- Main Form container tracking search, page & size -->
    <form id="titles-filter-form" method="GET" action="{{ route('titles.index') }}" x-ref="form" class="hidden">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $titles->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- Top Control Bar -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
        
        <!-- Left: Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
            <div class="relative w-full">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input
                    type="text"
                    placeholder="Search titles by name..."
                    value="{{ request('search') }}"
                    @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                    @blur="$refs.searchInput.value = $el.value"
                    class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors"
                    style="padding-left: 2.25rem;"
                />
            </div>
        </div>

        <!-- Right: Action Buttons Group -->
        <div class="flex items-center gap-2">
            <!-- Add Action Button -->
            <a 
                href="{{ route('titles.create') }}" 
                class="px-5 py-2 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] flex items-center justify-center gap-1.5 shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer select-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Title</span>
            </a>

            <!-- Reload / Clear Button -->
            <a 
                href="{{ route('titles.index') }}" 
                class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                title="Reload & Clear Search"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative mb-6">
        @php
            $headers = [
                ['label' => 'Title Name', 'width' => 'min-w-[200px]'],
                ['label' => 'Level (Hierarchy Rank)', 'width' => 'min-w-[150px]'],
                ['label' => 'Assigned Members', 'width' => 'min-w-[150px]'],
                ['label' => 'Actions', 'align' => 'center', 'width' => 'min-w-[160px]']
            ];
        @endphp

        <x-premium-table :headers="$headers">
            @forelse($titles as $title)
                <x-premium-table-row :is-even="$loop->index % 2 === 1">
                    <!-- Title Name -->
                    <td class="py-3.5 px-3">
                        <div class="flex items-center gap-3">
                            <div class="size-8 rounded-full bg-zinc-50 dark:bg-zinc-950 text-zinc-650 dark:text-zinc-400 border border-zinc-200/40 dark:border-zinc-800/40 flex items-center justify-center font-bold text-xs font-mono uppercase">
                                {{ mb_substr($title->name, 0, 2) }}
                            </div>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $title->name }}</span>
                        </div>
                    </td>

                    <!-- Level -->
                    <td class="py-3.5 px-3">
                        <span class="font-mono font-bold text-zinc-700 dark:text-zinc-300 text-xs">{{ $title->level }}</span>
                    </td>

                    <!-- Member Count badge -->
                    <td class="py-3.5 px-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/40 dark:border-zinc-800/40 text-zinc-650 dark:text-zinc-400">
                            {{ $title->members_count }} Members
                        </span>
                    </td>

                    <td class="py-3.5 px-3">
                        <div class="flex items-center justify-center gap-1.5">
                            <a 
                                href="{{ route('titles.show', $title->id) }}" 
                                class="p-1.5 rounded-[10px] text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="View Title Details"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                            <a 
                                href="{{ route('titles.edit', $title->id) }}" 
                                class="p-1.5 rounded-[10px] text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="Edit Title Settings"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4z"></path></svg>
                            </a>
                            <button 
                                type="button" 
                                @click="
                                    deleteTitleId = '{{ $title->id }}';
                                    deleteTitleName = '{{ addslashes($title->name) }}';
                                    showDeleteModal = true;
                                "
                                class="p-1.5 rounded-[10px] text-red-500 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/20 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="Delete Title"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </div>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-zinc-400 dark:text-zinc-650 py-16">
                        No traditional titles found.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </div>

    <!-- Pagination Footer Card -->
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
            <button
                type="button"
                @click="$refs.pageInput.value = 1; $refs.form.submit()"
                @if($titles->onFirstPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                First
            </button>

            <button
                type="button"
                @if(!$titles->onFirstPage())
                    @click="$refs.pageInput.value = {{ $titles->currentPage() - 1 }}; $refs.form.submit()"
                @endif
                @if($titles->onFirstPage()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Previous Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            @php
                $startPage = max(1, $titles->currentPage() - 2);
                $endPage = min($titles->lastPage(), $titles->currentPage() + 2);
            @endphp
            @for ($page = $startPage; $page <= $endPage; $page++)
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                    class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                        {{ $page == $titles->currentPage()
                            ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                            : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                        }}"
                >
                    {{ $page }}
                </button>
            @endfor

            <button
                type="button"
                @if($titles->hasMorePages())
                    @click="$refs.pageInput.value = {{ $titles->currentPage() + 1 }}; $refs.form.submit()"
                @endif
                @if(!$titles->hasMorePages()) disabled @endif
                class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                title="Next Page"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <button
                type="button"
                @click="$refs.pageInput.value = {{ $titles->lastPage() }}; $refs.form.submit()"
                @if($titles->currentPage() == $titles->lastPage()) disabled @endif
                class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
            >
                Last
            </button>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div 
        x-show="showDeleteModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
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
            
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Delete this title?</h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 leading-relaxed">
                Permanently delete title <span class="font-semibold text-zinc-800 dark:text-zinc-200" x-text="deleteTitleName"></span>? Any member holding this title will revert to Warrior (Default).
            </p>
            
            <div class="flex gap-2.5 mt-5">
                <button
                    type="button"
                    @click="showDeleteModal = false"
                    class="flex-1 py-2.5 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                >
                    Cancel
                </button>
                <form :action="'{{ url('/titles') }}/' + deleteTitleId" method="POST" class="flex-1">
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

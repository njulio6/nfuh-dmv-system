@extends('layouts.app')

@section('content')

    <!-- Header area with Back Button -->
    <x-premium-header 
        title="Create Njangi Cycle" 
        subtitle="Start a new rotational cycle program" 
        back-url="{{ route('njangi-cycles.index') }}" 
    />

    <form action="{{ route('njangi-cycles.store') }}" method="POST" class="flex flex-col gap-6 w-full -mt-3">
        @csrf

        <!-- Validation Errors Alert Block -->
        @if ($errors->any())
            <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-1.5 mb-2">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-red-650 dark:text-red-500 shrink-0"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span class="font-bold">Please correct the following errors:</span>
                </div>
                <ul class="list-disc list-inside pl-1 text-[11px] font-medium flex flex-col gap-0.5 mt-1 text-red-700 dark:text-red-400/90">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Cycle Details Card -->
        <x-premium-card title="Cycle Information">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                
                <!-- Organization Dropdown -->
                <div class="flex flex-col relative md:col-span-2" x-data="customSelect({
                    value: '{{ old('organization_id') }}',
                    defaultLabel: 'Select organization',
                    options: [
                        { value: '', label: 'Select organization' },
                        @foreach ($organizations as $organization)
                            { value: '{{ $organization->id }}', label: '{{ addslashes($organization->name) }}' },
                        @endforeach
                    ]
                })" @click.outside="close()">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">Organization <span class="text-red-500">*</span></label>
                    <input type="hidden" name="organization_id" x-ref="hiddenInput" :value="value">
                    
                    <div class="relative">
                        <button 
                            type="button"
                            @click="toggle()"
                            @keydown.down.prevent="focusNext()"
                            @keydown.up.prevent="focusPrev()"
                            @keydown.enter.prevent="selectActive()"
                            @keydown.escape.prevent="close()"
                            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                            :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                        >
                            <span x-text="label"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    </div>
                    
                    <div 
                        x-show="open" 
                        x-cloak
                        x-ref="optionsList"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute top-[100%] left-0 right-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto"
                    >
                        <template x-for="(opt, idx) in options" :key="opt.value">
                            <button 
                                type="button" 
                                @click="select(opt.value)"
                                @mouseenter="activeIndex = idx"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors font-semibold text-left"
                                :class="{
                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white': activeIndex === idx || value === opt.value,
                                    'text-zinc-700 dark:text-zinc-300': activeIndex !== idx && value !== opt.value
                                }"
                            >
                                <span x-text="opt.label"></span>
                                <span x-show="value === opt.value" class="text-zinc-900 dark:text-white shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Cycle Name -->
                <x-premium-input 
                    label="Cycle Name" 
                    name="name" 
                    required 
                    value="{{ old('name') }}" 
                    placeholder="e.g. Rotational Program A" 
                />

                <!-- Year -->
                <x-premium-input 
                    type="number" 
                    label="Year" 
                    name="year" 
                    required 
                    value="{{ old('year', date('Y')) }}" 
                    placeholder="e.g. 2026" 
                />

                <!-- Start Date Dropdown -->
                <x-premium-datepicker 
                    label="Start Date" 
                    name="start_date" 
                    value="{{ old('start_date') }}" 
                />

                <!-- End Date Dropdown -->
                <x-premium-datepicker 
                    label="End Date" 
                    name="end_date" 
                    value="{{ old('end_date') }}" 
                />

                <!-- Status Dropdown -->
                <div class="flex flex-col relative md:col-span-2" x-data="customSelect({
                    value: '{{ old('status', 'draft') }}',
                    defaultLabel: 'Draft',
                    options: [
                        { value: 'draft', label: 'Draft' },
                        { value: 'active', label: 'Active' },
                        { value: 'closed', label: 'Closed' },
                        { value: 'cancelled', label: 'Cancelled' }
                    ]
                })" @click.outside="close()">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                    <input type="hidden" name="status" x-ref="hiddenInput" :value="value">
                    
                    <div class="relative">
                        <button 
                            type="button"
                            @click="toggle()"
                            @keydown.down.prevent="focusNext()"
                            @keydown.up.prevent="focusPrev()"
                            @keydown.enter.prevent="selectActive()"
                            @keydown.escape.prevent="close()"
                            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                            :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                        >
                            <span x-text="label"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    </div>
                    
                    <div 
                        x-show="open" 
                        x-cloak
                        x-ref="optionsList"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute top-[100%] left-0 right-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 p-1 flex flex-col gap-0.5"
                    >
                        <template x-for="(opt, idx) in options" :key="opt.value">
                            <button 
                                type="button" 
                                @click="select(opt.value)"
                                @mouseenter="activeIndex = idx"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors font-semibold text-left"
                                :class="{
                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white': activeIndex === idx || value === opt.value,
                                    'text-zinc-700 dark:text-zinc-300': activeIndex !== idx && value !== opt.value
                                }"
                            >
                                <span x-text="opt.label"></span>
                                <span x-show="value === opt.value" class="text-zinc-900 dark:text-white shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <x-premium-textarea 
                        label="Notes" 
                        name="notes" 
                        placeholder="Add optional notes about this cycle..."
                    >{{ old('notes') }}</x-premium-textarea>
                </div>

            </div>
        </x-premium-card>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 dark:border-zinc-800 pt-5 mt-2">
            <x-premium-button variant="secondary" href="{{ route('njangi-cycles.index') }}">
                Cancel
            </x-premium-button>
            <x-premium-button type="submit" variant="primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>Create Cycle</span>
            </x-premium-button>
        </div>
    </form>



@endsection
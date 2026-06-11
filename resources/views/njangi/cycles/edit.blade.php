@extends('layouts.app')

@section('content')

    <!-- Header area with Back Button -->
    <x-premium-header 
        title="Edit Njangi Cycle" 
        subtitle="Edit cycle details for {{ $njangiCycle->name }}" 
        back-url="{{ route('njangi-cycles.show', $njangiCycle) }}" 
    />

    <form action="{{ route('njangi-cycles.update', $njangiCycle) }}" method="POST" class="flex flex-col gap-6 w-full -mt-3">
        @csrf
        @method('PUT')

        <!-- Validation Errors Alert Block -->
        @if ($errors->any())
            <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-1.5 mb-2 select-none">
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
                <div class="flex flex-col relative select-none md:col-span-2" x-data="customSelect({
                    value: '{{ old('organization_id', $njangiCycle->organization_id) }}',
                    defaultLabel: 'Select organization',
                    options: [
                        { value: '', label: 'Select organization' },
                        @foreach ($organizations as $organization)
                            { value: '{{ $organization->id }}', label: '{{ addslashes($organization->name) }}' },
                        @endforeach
                    ]
                })" @click.outside="close()">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Organization <span class="text-red-500">*</span></label>
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
                    value="{{ old('name', $njangiCycle->name) }}" 
                    placeholder="e.g. Rotational Program A" 
                />

                <!-- Year -->
                <x-premium-input 
                    type="number" 
                    label="Year" 
                    name="year" 
                    required 
                    value="{{ old('year', $njangiCycle->year) }}" 
                    placeholder="e.g. 2026" 
                />

                <!-- Start Date Dropdown -->
                <div class="flex flex-col relative select-none" x-data="datepicker({
                    name: 'start_date',
                    value: '{{ old('start_date', $njangiCycle->start_date?->format('Y-m-d')) }}',
                    required: false
                })" @click.outside="open = false; monthOpen = false; yearOpen = false">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Start Date</label>
                    <input type="hidden" :name="name" x-ref="hiddenInput" :value="value">
                    
                    <div class="relative">
                        <button 
                            type="button"
                            @click="open = !open"
                            @keydown.escape.prevent="open = false"
                            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                            :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                        >
                            <span x-text="displayValue"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    </div>

                    <!-- Calendar Popover Panel -->
                    <div 
                        x-show="open" 
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute top-[100%] left-0 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl z-50 p-4 flex flex-col gap-3 w-72 shadow-xl"
                    >
                        <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                            <button type="button" @click="prevMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                            
                            <div class="flex items-center gap-1.5 font-bold text-xs">
                                <div class="relative select-none">
                                    <button type="button" @click.stop="monthOpen = !monthOpen; yearOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                                        <span x-text="months[month]"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-550 transition-transform" :class="monthOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    
                                    <div 
                                        x-show="monthOpen" 
                                        x-cloak
                                        @click.outside="monthOpen = false"
                                        class="absolute top-[100%] left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg z-[60] p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto w-32"
                                    >
                                        <template x-for="(m, index) in months" :key="index">
                                            <button 
                                                type="button" 
                                                @click="month = index; generateCalendar(); monthOpen = false"
                                                class="w-full px-2.5 py-1.5 rounded-lg text-[11px] transition-colors font-semibold text-left"
                                                :class="month === index ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                                            >
                                                <span x-text="m"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                
                                <span class="text-zinc-300 dark:text-zinc-600 font-bold select-none">/</span>
                                
                                <div class="relative select-none">
                                    <button type="button" @click.stop="yearOpen = !yearOpen; monthOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                                        <span x-text="year"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-550 transition-transform" :class="yearOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    
                                    <div 
                                        x-show="yearOpen" 
                                        x-cloak
                                        @click.outside="yearOpen = false"
                                        class="absolute top-[100%] left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg z-[60] p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto w-24"
                                    >
                                        <template x-for="y in getYears()" :key="y">
                                            <button 
                                                type="button" 
                                                @click="year = y; generateCalendar(); yearOpen = false"
                                                class="w-full px-2.5 py-1.5 rounded-lg text-[11px] transition-colors font-semibold text-left"
                                                :class="year === y ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                                            >
                                                <span x-text="y"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="nextMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 py-1 select-none">
                            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center">
                            <template x-for="(dayObj, index) in days" :key="index">
                                <button 
                                    type="button"
                                    @click="selectDate(dayObj)"
                                    class="h-8 w-8 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors cursor-pointer"
                                    :class="{
                                        'bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 font-bold': isSelected(dayObj),
                                        'text-zinc-300 dark:text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800/50': !dayObj.currentMonth && !isSelected(dayObj),
                                        'text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800': dayObj.currentMonth && !isSelected(dayObj),
                                        'border border-zinc-200 dark:border-zinc-800': isToday(dayObj) && !isSelected(dayObj)
                                    }"
                                    x-text="dayObj.day"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- End Date Dropdown -->
                <div class="flex flex-col relative select-none" x-data="datepicker({
                    name: 'end_date',
                    value: '{{ old('end_date', $njangiCycle->end_date?->format('Y-m-d')) }}',
                    required: false
                })" @click.outside="open = false; monthOpen = false; yearOpen = false">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">End Date</label>
                    <input type="hidden" :name="name" x-ref="hiddenInput" :value="value">
                    
                    <div class="relative">
                        <button 
                            type="button"
                            @click="open = !open"
                            @keydown.escape.prevent="open = false"
                            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                            :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                        >
                            <span x-text="displayValue"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    </div>

                    <!-- Calendar Popover Panel -->
                    <div 
                        x-show="open" 
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute top-[100%] left-0 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl z-50 p-4 flex flex-col gap-3 w-72 shadow-xl"
                    >
                        <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                            <button type="button" @click="prevMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                            
                            <div class="flex items-center gap-1.5 font-bold text-xs">
                                <div class="relative select-none">
                                    <button type="button" @click.stop="monthOpen = !monthOpen; yearOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                                        <span x-text="months[month]"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-550 transition-transform" :class="monthOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    
                                    <div 
                                        x-show="monthOpen" 
                                        x-cloak
                                        @click.outside="monthOpen = false"
                                        class="absolute top-[100%] left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg z-[60] p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto w-32"
                                    >
                                        <template x-for="(m, index) in months" :key="index">
                                            <button 
                                                type="button" 
                                                @click="month = index; generateCalendar(); monthOpen = false"
                                                class="w-full px-2.5 py-1.5 rounded-lg text-[11px] transition-colors font-semibold text-left"
                                                :class="month === index ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                                            >
                                                <span x-text="m"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                
                                <span class="text-zinc-300 dark:text-zinc-600 font-bold select-none">/</span>
                                
                                <div class="relative select-none">
                                    <button type="button" @click.stop="yearOpen = !yearOpen; monthOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                                        <span x-text="year"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-550 transition-transform" :class="yearOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    
                                    <div 
                                        x-show="yearOpen" 
                                        x-cloak
                                        @click.outside="yearOpen = false"
                                        class="absolute top-[100%] left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg z-[60] p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto w-24"
                                    >
                                        <template x-for="y in getYears()" :key="y">
                                            <button 
                                                type="button" 
                                                @click="year = y; generateCalendar(); yearOpen = false"
                                                class="w-full px-2.5 py-1.5 rounded-lg text-[11px] transition-colors font-semibold text-left"
                                                :class="year === y ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                                            >
                                                <span x-text="y"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="nextMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 py-1 select-none">
                            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center">
                            <template x-for="(dayObj, index) in days" :key="index">
                                <button 
                                    type="button"
                                    @click="selectDate(dayObj)"
                                    class="h-8 w-8 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors cursor-pointer"
                                    :class="{
                                        'bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 font-bold': isSelected(dayObj),
                                        'text-zinc-300 dark:text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800/50': !dayObj.currentMonth && !isSelected(dayObj),
                                        'text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800': dayObj.currentMonth && !isSelected(dayObj),
                                        'border border-zinc-200 dark:border-zinc-800': isToday(dayObj) && !isSelected(dayObj)
                                    }"
                                    x-text="dayObj.day"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Status Dropdown -->
                <div class="flex flex-col relative select-none md:col-span-2" x-data="customSelect({
                    value: '{{ old('status', $njangiCycle->status) }}',
                    defaultLabel: 'Draft',
                    options: [
                        { value: 'draft', label: 'Draft' },
                        { value: 'active', label: 'Active' },
                        { value: 'closed', label: 'Closed' },
                        { value: 'cancelled', label: 'Cancelled' }
                    ]
                })" @click.outside="close()">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Status <span class="text-red-500">*</span></label>
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
                    >{{ old('notes', $njangiCycle->notes) }}</x-premium-textarea>
                </div>

            </div>
        </x-premium-card>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 dark:border-zinc-800 pt-5 mt-2">
            <x-premium-button variant="secondary" href="{{ route('njangi-cycles.show', $njangiCycle) }}">
                Cancel
            </x-premium-button>
            <x-premium-button type="submit" variant="primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>Save Changes</span>
            </x-premium-button>
        </div>
    </form>

<!-- Alpine Registration Scripts for Premium Datepicker and Dropdowns -->
<script>
    (function() {
        function registerAlpineComponents() {
            if (window.AlpineCustomSelectRegistered) return;
            window.AlpineCustomSelectRegistered = true;

            // Custom Select component
            Alpine.data('customSelect', (config) => ({
                open: false,
                value: config.value || '',
                label: config.label || '',
                options: config.options || [],
                activeIndex: -1,

                init() {
                    if (!this.label) {
                        const opt = this.options.find(o => String(o.value) === String(this.value));
                        if (opt) {
                            this.label = opt.label;
                        } else if (this.value === '') {
                            this.label = config.defaultLabel || 'Select Option';
                        }
                    }
                    this.$watch('value', (val) => {
                        const opt = this.options.find(o => String(o.value) === String(val));
                        if (opt) {
                            this.label = opt.label;
                        } else if (val === '') {
                            this.label = config.defaultLabel || 'Select Option';
                        }
                        this.$refs.hiddenInput.value = val;
                        this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                },
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.activeIndex = this.options.findIndex(o => String(o.value) === String(this.value));
                        if (this.activeIndex === -1) this.activeIndex = 0;
                        this.$nextTick(() => {
                            this.scrollToActive();
                        });
                    }
                },
                close() {
                    this.open = false;
                    this.activeIndex = -1;
                },
                select(val) {
                    this.value = val;
                    this.close();
                },
                selectActive() {
                    if (this.activeIndex >= 0 && this.activeIndex < this.options.length) {
                        this.select(this.options[this.activeIndex].value);
                    }
                },
                focusNext() {
                    if (!this.open) {
                        this.toggle();
                        return;
                    }
                    this.activeIndex = (this.activeIndex + 1) % this.options.length;
                    this.scrollToActive();
                },
                focusPrev() {
                    if (!this.open) {
                        this.toggle();
                        return;
                    }
                    this.activeIndex = (this.activeIndex - 1 + this.options.length) % this.options.length;
                    this.scrollToActive();
                },
                scrollToActive() {
                    this.$nextTick(() => {
                        const activeEl = this.$refs.optionsList.children[this.activeIndex];
                        if (activeEl) {
                            activeEl.scrollIntoView({ block: 'nearest' });
                        }
                    });
                }
            }));

            // Custom Datepicker component
            Alpine.data('datepicker', (config) => ({
                value: config.value || '',
                name: config.name || '',
                required: config.required || false,
                open: false,
                monthOpen: false,
                yearOpen: false,
                month: null, // 0-11
                year: null,
                days: [],
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                
                init() {
                    let initialDate = new Date();
                    if (this.value) {
                        const parts = this.value.split('-');
                        if (parts.length === 3) {
                            initialDate = new Date(parts[0], parts[1] - 1, parts[2]);
                        } else {
                            initialDate = new Date(this.value);
                        }
                    }
                    this.month = initialDate.getMonth();
                    this.year = initialDate.getFullYear();
                    this.generateCalendar();
                    
                    this.$watch('value', (val) => {
                        if (val) {
                            const parts = val.split('-');
                            if (parts.length === 3) {
                                const y = parseInt(parts[0], 10);
                                const m = parseInt(parts[1], 10) - 1;
                                if (!isNaN(y) && !isNaN(m)) {
                                    this.month = m;
                                    this.year = y;
                                    this.generateCalendar();
                                }
                            }
                        }
                    });
                },
                
                get displayValue() {
                    if (!this.value) return 'Select Date';
                    const parts = this.value.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parts[0], parts[1] - 1, parts[2]);
                        if (!isNaN(d.getTime())) {
                            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        }
                    }
                    return 'Select Date';
                },
                
                getYears() {
                    const currentYear = new Date().getFullYear();
                    const initialYear = this.value ? parseInt(this.value.split('-')[0], 10) : currentYear;
                    const minYear = Math.min(currentYear - 50, initialYear - 10);
                    const maxYear = Math.max(currentYear + 10, initialYear + 10);
                    const years = [];
                    for (let y = minYear; y <= maxYear; y++) {
                        years.push(y);
                    }
                    return years;
                },
                
                generateCalendar() {
                    const firstDayOfMonth = new Date(this.year, this.month, 1).getDay();
                    const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                    const daysInPrevMonth = new Date(this.year, this.month, 0).getDate();
                    
                    const days = [];
                    
                    // Previous month trailing days
                    for (let i = firstDayOfMonth - 1; i >= 0; i--) {
                        days.push({
                            day: daysInPrevMonth - i,
                            month: this.month === 0 ? 11 : this.month - 1,
                            year: this.month === 0 ? this.year - 1 : this.year,
                            currentMonth: false
                        });
                    }
                    
                    // Current month days
                    for (let i = 1; i <= daysInMonth; i++) {
                        days.push({
                            day: i,
                            month: this.month,
                            year: this.year,
                            currentMonth: true
                        });
                    }
                    
                    // Next month leading days
                    const totalCells = 42;
                    const nextMonthDays = totalCells - days.length;
                    for (let i = 1; i <= nextMonthDays; i++) {
                        days.push({
                            day: i,
                            month: this.month === 11 ? 0 : this.month + 1,
                            year: this.month === 11 ? this.year + 1 : this.year,
                            currentMonth: false
                        });
                    }
                    
                    this.days = days;
                },
                
                prevMonth() {
                    if (this.month === 0) {
                        this.month = 11;
                        this.year--;
                    } else {
                        this.month--;
                    }
                    this.generateCalendar();
                },
                
                nextMonth() {
                    if (this.month === 11) {
                        this.month = 0;
                        this.year++;
                    } else {
                        this.month++;
                    }
                    this.generateCalendar();
                },
                
                selectDate(dateObj) {
                    const pad = (n) => String(n).padStart(2, '0');
                    this.value = `${dateObj.year}-${pad(dateObj.month + 1)}-${pad(dateObj.day)}`;
                    this.open = false;
                    this.monthOpen = false;
                    this.yearOpen = false;
                },
                
                isSelected(dateObj) {
                    if (!this.value) return false;
                    const parts = this.value.split('-');
                    if (parts.length === 3) {
                        return parseInt(parts[2], 10) === dateObj.day &&
                               (parseInt(parts[1], 10) - 1) === dateObj.month &&
                               parseInt(parts[0], 10) === dateObj.year;
                    }
                    return false;
                },
                
                isToday(dateObj) {
                    const today = new Date();
                    return today.getDate() === dateObj.day &&
                           today.getMonth() === dateObj.month &&
                           today.getFullYear() === dateObj.year;
                }
            }));
        }

        if (window.Alpine) {
            registerAlpineComponents();
        } else {
            document.addEventListener('alpine:init', registerAlpineComponents);
        }
    })();
</script>

@endsection

@props([
    'label' => null,
    'name' => null,
    'value' => '',
    'required' => false
])
<div class="flex flex-col relative w-full" x-data="datepicker({
    name: '{{ $name }}',
    value: '{{ $value }}',
    required: {{ $required ? 'true' : 'false' }}
})" @click.outside="open = false; monthOpen = false; yearOpen = false">
    @if($label)
        <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <input type="hidden" :name="name" x-ref="hiddenInput" :value="value" @if($required) required @endif>
    
    <div class="relative w-full">
        <button 
            type="button"
            @click="open = !open"
            @keydown.escape.prevent="open = false"
            class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
            :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
        >
            <span x-text="displayValue"></span>
            <i data-lucide="calendar" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0"></i>
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
        <!-- Header month/year picker -->
        <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
            <button type="button" @click="prevMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </button>
            
            <div class="flex items-center gap-1.5 font-bold text-xs">
                <!-- Custom Month Dropdown -->
                <div class="relative">
                    <button type="button" @click.stop="monthOpen = !monthOpen; yearOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                        <span x-text="months[month]"></span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-zinc-450 dark:text-zinc-550 transition-transform" :class="monthOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"></i>
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
                
                <span class="text-zinc-300 dark:text-zinc-600 font-bold">/</span>
                
                <!-- Custom Year Dropdown -->
                <div class="relative">
                    <button type="button" @click.stop="yearOpen = !yearOpen; monthOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                        <span x-text="year"></span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-zinc-450 dark:text-zinc-550 transition-transform" :class="yearOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"></i>
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
                                :class="year === y ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                            >
                                <span x-text="y"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            
            <button type="button" @click="nextMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Weekdays -->
        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 py-1">
            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
        </div>

        <!-- Days Grid -->
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

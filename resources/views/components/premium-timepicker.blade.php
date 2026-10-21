@props([
    'label' => null,
    'name' => null,
    'value' => '',
    'required' => false,
    'helperText' => null,
    'labelClass' => null,
    'inputClass' => null,
])

<div 
    class="flex flex-col relative w-full" 
    x-data="timepicker({
        name: '{{ $name }}',
        value: '{{ $value }}',
        required: {{ $required ? 'true' : 'false' }}
    })" 
    @click.outside="open = false"
>
    @if($label)
        <label class="{{ $labelClass ?? 'block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5' }}">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <input type="hidden" :name="name" x-ref="hiddenInput" :value="value" @if($required) required @endif>

    <!-- Trigger Button -->
    <div class="relative w-full">
        <button 
            type="button"
            @click="open = !open"
            @keydown.escape.prevent="open = false"
            class="{{ $inputClass ?? 'w-full h-11 flex items-center justify-between bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 text-zinc-900 dark:text-white px-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-brand-primary transition-all cursor-pointer select-none shadow-2xs' }}"
            :class="open ? 'border-brand-primary ring-1 ring-brand-primary/20' : ''"
        >
            <div class="flex items-center gap-2">
                <span class="p-1 rounded-md bg-zinc-100 dark:bg-zinc-700/60 text-zinc-600 dark:text-zinc-300 shrink-0">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                </span>
                <span class="font-bold text-zinc-900 dark:text-white text-xs" x-text="displayValue"></span>
            </div>
            <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"></i>
        </button>
    </div>

    @if($helperText)
        <span class="text-[10px] text-zinc-400 mt-1 block">{{ $helperText }}</span>
    @endif

    <!-- Timepicker Popover Panel -->
    <div 
        x-show="open" 
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute top-[100%] left-0 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl z-50 p-4 flex flex-col gap-3.5 w-full sm:w-80 shadow-2xl"
    >
        <!-- Header: Selected Time Display & AM/PM Toggle -->
        <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100 dark:border-zinc-800">
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-black font-mono tracking-tight text-zinc-900 dark:text-white" x-text="String(hour12).padStart(2, '0')"></span>
                <span class="text-lg font-bold text-zinc-400 dark:text-zinc-500 animate-pulse">:</span>
                <span class="text-xl font-black font-mono tracking-tight text-zinc-900 dark:text-white" x-text="minute"></span>
                <span class="text-xs font-bold text-brand-primary ml-1" x-text="period"></span>
            </div>

            <!-- Segmented AM / PM Switch -->
            <div class="inline-flex p-0.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/80 dark:border-zinc-700/80">
                <button 
                    type="button" 
                    @click="setPeriod('AM')" 
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition-all cursor-pointer"
                    :class="period === 'AM' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'"
                >
                    AM
                </button>
                <button 
                    type="button" 
                    @click="setPeriod('PM')" 
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition-all cursor-pointer"
                    :class="period === 'PM' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'"
                >
                    PM
                </button>
            </div>
        </div>

        <!-- Hours Selector (1 - 12) -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Hour</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500" x-text="period === 'PM' && hour12 !== 12 ? (hour12 + 12) + ':00 (24h)' : ''"></span>
            </div>
            <div class="grid grid-cols-6 gap-1">
                <template x-for="h in hours" :key="h">
                    <button 
                        type="button" 
                        @click="setHour(h)" 
                        class="h-8 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center"
                        :class="hour12 === h 
                            ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-xs' 
                            : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                        x-text="String(h).padStart(2, '0')"
                    ></button>
                </template>
            </div>
        </div>

        <!-- Minutes Selector (00 - 55 in 5m steps) -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Minute</span>
            </div>
            <div class="grid grid-cols-6 gap-1">
                <template x-for="m in minutes" :key="m">
                    <button 
                        type="button" 
                        @click="setMinute(m)" 
                        class="h-8 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center"
                        :class="minute === m 
                            ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-xs' 
                            : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                        x-text="m"
                    ></button>
                </template>
            </div>
        </div>

        <!-- Quick Presets -->
        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1.5">Common Hours</span>
            <div class="flex flex-wrap gap-1">
                <button type="button" @click="setPreset('17:00')" class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-[10px] font-semibold text-zinc-700 dark:text-zinc-300 transition-colors">5:00 PM</button>
                <button type="button" @click="setPreset('18:00')" class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-[10px] font-semibold text-zinc-700 dark:text-zinc-300 transition-colors">6:00 PM</button>
                <button type="button" @click="setPreset('19:00')" class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-[10px] font-semibold text-zinc-700 dark:text-zinc-300 transition-colors">7:00 PM</button>
                <button type="button" @click="setPreset('20:00')" class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-[10px] font-semibold text-zinc-700 dark:text-zinc-300 transition-colors">8:00 PM</button>
                <button type="button" @click="setPreset('21:00')" class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-[10px] font-semibold text-zinc-700 dark:text-zinc-300 transition-colors">9:00 PM</button>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end pt-2 border-t border-zinc-100 dark:border-zinc-800">
            <button 
                type="button" 
                @click="open = false" 
                class="w-full py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-bold transition-all shadow-xs cursor-pointer"
            >
                Done
            </button>
        </div>
    </div>
</div>

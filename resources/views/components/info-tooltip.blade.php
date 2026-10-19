@props([
    'title' => null,
    'formula' => null,
    'source' => null,
    'description' => null,
    'color' => 'blue',
])

@php
    $c = $color ?? 'blue';
    
    $colorStyles = [
        'blue' => [
            'icon' => 'text-blue-600 dark:text-blue-400',
            'btnHover' => 'hover:text-blue-600 dark:hover:text-blue-400',
            'topBorder' => 'border-t-blue-500',
            'badge' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/60',
            'calcBox' => 'bg-blue-50/60 dark:bg-blue-950/30 border-blue-200/70 dark:border-blue-900/50',
            'calcLabel' => 'text-blue-700 dark:text-blue-400',
            'calcText' => 'text-blue-950 dark:text-blue-200',
            'sourceDot' => 'bg-blue-500',
        ],
        'amber' => [
            'icon' => 'text-amber-600 dark:text-amber-400',
            'btnHover' => 'hover:text-amber-600 dark:hover:text-amber-400',
            'topBorder' => 'border-t-amber-500',
            'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/60',
            'calcBox' => 'bg-amber-50/60 dark:bg-amber-950/30 border-amber-200/70 dark:border-amber-900/50',
            'calcLabel' => 'text-amber-700 dark:text-amber-400',
            'calcText' => 'text-amber-950 dark:text-amber-200',
            'sourceDot' => 'bg-amber-500',
        ],
        'emerald' => [
            'icon' => 'text-emerald-600 dark:text-emerald-400',
            'btnHover' => 'hover:text-emerald-600 dark:hover:text-emerald-400',
            'topBorder' => 'border-t-emerald-500',
            'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/60',
            'calcBox' => 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-200/70 dark:border-emerald-900/50',
            'calcLabel' => 'text-emerald-700 dark:text-emerald-400',
            'calcText' => 'text-emerald-950 dark:text-emerald-200',
            'sourceDot' => 'bg-emerald-500',
        ],
        'purple' => [
            'icon' => 'text-purple-600 dark:text-purple-400',
            'btnHover' => 'hover:text-purple-600 dark:hover:text-purple-400',
            'topBorder' => 'border-t-purple-500',
            'badge' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-400 border-purple-200/60 dark:border-purple-800/60',
            'calcBox' => 'bg-purple-50/60 dark:bg-purple-950/30 border-purple-200/70 dark:border-purple-900/50',
            'calcLabel' => 'text-purple-700 dark:text-purple-400',
            'calcText' => 'text-purple-950 dark:text-purple-200',
            'sourceDot' => 'bg-purple-500',
        ],
        'teal' => [
            'icon' => 'text-teal-600 dark:text-teal-400',
            'btnHover' => 'hover:text-teal-600 dark:hover:text-teal-400',
            'topBorder' => 'border-t-teal-500',
            'badge' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400 border-teal-200/60 dark:border-teal-800/60',
            'calcBox' => 'bg-teal-50/60 dark:bg-teal-950/30 border-teal-200/70 dark:border-teal-900/50',
            'calcLabel' => 'text-teal-700 dark:text-teal-400',
            'calcText' => 'text-teal-950 dark:text-teal-200',
            'sourceDot' => 'bg-teal-500',
        ],
        'indigo' => [
            'icon' => 'text-indigo-600 dark:text-indigo-400',
            'btnHover' => 'hover:text-indigo-600 dark:hover:text-indigo-400',
            'topBorder' => 'border-t-indigo-500',
            'badge' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-800/60',
            'calcBox' => 'bg-indigo-50/60 dark:bg-indigo-950/30 border-indigo-200/70 dark:border-indigo-900/50',
            'calcLabel' => 'text-indigo-700 dark:text-indigo-400',
            'calcText' => 'text-indigo-950 dark:text-indigo-200',
            'sourceDot' => 'bg-indigo-500',
        ],
        'rose' => [
            'icon' => 'text-rose-600 dark:text-rose-400',
            'btnHover' => 'hover:text-rose-600 dark:hover:text-rose-400',
            'topBorder' => 'border-t-rose-500',
            'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 border-rose-200/60 dark:border-rose-800/60',
            'calcBox' => 'bg-rose-50/60 dark:bg-rose-950/30 border-rose-200/70 dark:border-rose-900/50',
            'calcLabel' => 'text-rose-700 dark:text-rose-400',
            'calcText' => 'text-rose-950 dark:text-rose-200',
            'sourceDot' => 'bg-rose-500',
        ],
        'cyan' => [
            'icon' => 'text-cyan-600 dark:text-cyan-400',
            'btnHover' => 'hover:text-cyan-600 dark:hover:text-cyan-400',
            'topBorder' => 'border-t-cyan-500',
            'badge' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/50 dark:text-cyan-400 border-cyan-200/60 dark:border-cyan-800/60',
            'calcBox' => 'bg-cyan-50/60 dark:bg-cyan-950/30 border-cyan-200/70 dark:border-cyan-900/50',
            'calcLabel' => 'text-cyan-700 dark:text-cyan-400',
            'calcText' => 'text-cyan-950 dark:text-cyan-200',
            'sourceDot' => 'bg-cyan-500',
        ],
        'violet' => [
            'icon' => 'text-violet-600 dark:text-violet-400',
            'btnHover' => 'hover:text-violet-600 dark:hover:text-violet-400',
            'topBorder' => 'border-t-violet-500',
            'badge' => 'bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-400 border-violet-200/60 dark:border-violet-800/60',
            'calcBox' => 'bg-violet-50/60 dark:bg-violet-950/30 border-violet-200/70 dark:border-violet-900/50',
            'calcLabel' => 'text-violet-700 dark:text-violet-400',
            'calcText' => 'text-violet-950 dark:text-violet-200',
            'sourceDot' => 'bg-violet-500',
        ],
    ];

    $theme = $colorStyles[$c] ?? $colorStyles['blue'];
@endphp

<div class="relative inline-flex items-center ml-1.5" 
     x-data="{ 
         open: false, 
         dropUp: false,
         alignRight: false,
         updatePosition() {
             const rect = this.$el.getBoundingClientRect();
             // Vertical: If less than 260px space above the icon, always drop down
             this.dropUp = rect.top > 260 && (window.innerHeight - rect.bottom < 260);
             // Horizontal: If closer than 340px to the right edge of viewport, align to right
             this.alignRight = (window.innerWidth - rect.left < 340);
         },
         toggle() {
             if (!this.open) this.updatePosition();
             this.open = !this.open;
         },
         show() {
             this.updatePosition();
             this.open = true;
         }
     }" 
     @mouseenter="show" 
     @mouseleave="open = false" 
     @click.stop="toggle" 
     @click.outside="open = false">
    <button type="button" class="text-zinc-400 {{ $theme['btnHover'] }} transition-colors focus:outline-hidden p-0.5 rounded-md cursor-pointer" aria-label="Information and calculation details">
        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
    </button>
    
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
         :class="[
             dropUp ? 'bottom-full mb-2.5' : 'top-full mt-2.5',
             alignRight ? 'right-0' : 'left-0 sm:-left-3'
         ]"
         class="absolute z-50 w-72 sm:w-80 p-4 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700/80 border-t-2 {{ $theme['topBorder'] }} rounded-2xl shadow-xl shadow-zinc-900/10 dark:shadow-black/60 text-left pointer-events-auto select-none"
         style="display: none;"
         @click.stop>
        @if($title)
            <div class="text-[11px] font-bold text-zinc-900 dark:text-white uppercase tracking-wider pb-2.5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <span class="flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full {{ $theme['sourceDot'] }}"></span>
                    {{ $title }}
                </span>
                <span class="text-[9px] px-2 py-0.5 rounded-full border {{ $theme['badge'] }} font-bold uppercase tracking-wider">Info</span>
            </div>
        @endif
        
        @if($description)
            <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-2.5 leading-relaxed font-normal">
                {{ $description }}
            </p>
        @endif

        @if($formula)
            <div class="mt-3 p-2.5 rounded-xl border {{ $theme['calcBox'] }}">
                <span class="text-[10px] font-bold uppercase tracking-wider block mb-1 {{ $theme['calcLabel'] }}">How It's Calculated:</span>
                <div class="text-xs font-bold font-sans leading-snug {{ $theme['calcText'] }}">
                    {{ $formula }}
                </div>
            </div>
        @endif

        @if($source)
            <div class="mt-3 pt-2.5 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center gap-1.5 text-[11px]">
                <span class="font-bold text-zinc-400 uppercase tracking-wider text-[10px]">Source:</span>
                <span class="text-zinc-700 dark:text-zinc-200 font-medium">{{ $source }}</span>
            </div>
        @endif
    </div>
</div>

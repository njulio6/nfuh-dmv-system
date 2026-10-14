@props([
    'title' => '',
    'value' => '',
    'subtitle' => null,
    'icon' => null,
    'currency' => null,
])

<div {{ $attributes->merge(['class' => 'stat-card-gradient p-5 flex flex-col justify-between gap-3 group']) }}>
    <div class="flex items-center gap-3">
        @if($icon)
            <div class="stat-icon-badge p-2 shrink-0">
                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
            </div>
        @endif
        <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">{{ $title }}</span>
    </div>
    
    <div class="flex flex-col gap-1">
        <div class="flex items-baseline gap-1.5 flex-wrap">
            <span class="text-2xl lg:text-3xl font-extrabold text-zinc-950 dark:text-white leading-none tracking-tight font-display">
                {{ $value }}
            </span>
            @if($currency)
                <span class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider font-mono">
                    {{ $currency }}
                </span>
            @endif
        </div>
        
        @if($subtitle)
            <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium leading-relaxed">
                {{ $subtitle }}
            </span>
        @endif
        
        {{ $slot }}
    </div>
</div>

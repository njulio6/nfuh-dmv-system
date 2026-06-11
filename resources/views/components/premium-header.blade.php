@props(['title', 'subtitle' => null, 'backUrl' => null, 'backTitle' => 'Go Back'])
<div class="flex items-center gap-4 border-b border-zinc-100 dark:border-zinc-800/60 pb-3 mb-0 select-none {{ $attributes->get('class') }}">
    @if($backUrl)
        <a 
            href="{{ $backUrl }}" 
            class="p-2 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 rounded-[10px] hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer active:scale-95 shadow-2xs flex items-center justify-center flex-shrink-0"
            title="{{ $backTitle }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </a>
    @endif
    <div class="flex flex-col gap-0.5">
        <h1 class="text-xl font-bold text-zinc-950 dark:text-white tracking-tight font-display">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="text-xs text-zinc-550 dark:text-zinc-400 font-medium">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    @if(isset($actions))
        <div class="ml-auto flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>

@props(['title' => null])
<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none']) }}>
    @if($title)
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            {{ $title }}
        </h3>
    @endif
    {{ $slot }}
</div>

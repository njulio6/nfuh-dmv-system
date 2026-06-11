@props(['isEven' => false])
<tr {{ $attributes->merge(['class' => 'transition-colors duration-150 hover:bg-zinc-100/30 dark:hover:bg-zinc-950/20 ' . ($isEven ? 'bg-zinc-50/50 dark:bg-zinc-900/30' : 'bg-white dark:bg-zinc-900')]) }}>
    {{ $slot }}
</tr>

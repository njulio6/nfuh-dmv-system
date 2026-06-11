@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white focus:border-zinc-950 dark:focus:border-white focus:ring-zinc-950 dark:focus:ring-white rounded-lg shadow-sm transition-all']) }}>

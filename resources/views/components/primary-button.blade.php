<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-zinc-950 dark:bg-white border border-transparent rounded-lg font-semibold text-xs text-white dark:text-zinc-950 uppercase tracking-widest hover:bg-zinc-800 dark:hover:bg-zinc-100 focus:bg-zinc-800 dark:focus:bg-zinc-200 active:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

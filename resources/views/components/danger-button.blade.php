<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-zinc-100 hover:bg-red-50 dark:bg-zinc-800 dark:hover:bg-red-950/20 border border-zinc-200 dark:border-zinc-700 rounded-lg font-semibold text-xs text-red-650 dark:text-red-400 uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

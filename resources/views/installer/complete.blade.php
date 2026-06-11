<x-installer-layout>
    <div class="flex flex-col gap-6 text-center items-center py-4">
        <!-- Success Check Icon -->
        <div class="flex aspect-square size-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shadow-sm animate-bounce">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Installation Completed Successfully!</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto leading-relaxed">
                The database credentials have been configured, tables migrated/seeded, and your administrator account initialized. The installer is now locked.
            </p>
        </div>

        <div class="w-full mt-4">
            <x-premium-button href="/" variant="primary" class="w-full py-3.5 rounded-xl font-bold">
                Go to Portal Homepage
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M3 12h18"></path>
                </svg>
            </x-premium-button>
        </div>
    </div>
</x-installer-layout>

<x-installer-layout>
    <div class="flex flex-col gap-5 text-center items-center">
        <div class="flex aspect-square size-12 items-center justify-center rounded-2xl bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 shadow-md">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Welcome to the Installation Wizard</h2>
        
        <p class="text-sm text-zinc-650 dark:text-zinc-400 leading-relaxed max-w-md">
            This setup wizard will guide you through configuring your database connection, initializing the schema tables, and registering your administrator dashboard account.
        </p>

        <div class="w-full mt-4">
            <x-premium-button href="{{ route('install.requirements') }}" variant="primary" class="w-full py-3.5 rounded-xl font-bold">
                Let's Begin Setup
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                </svg>
            </x-premium-button>
        </div>
    </div>
</x-installer-layout>

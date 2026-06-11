<x-guest-layout>
    <div class="mb-4 text-xs font-semibold leading-relaxed text-zinc-500 dark:text-zinc-400">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-premium-input 
                id="password" 
                label="{{ __('Password') }}" 
                type="password" 
                name="password" 
                placeholder="••••••••"
                required 
                autocomplete="current-password" 
            />
            <x-input-error :messages="$errors->get('password')" class="text-xs font-semibold text-red-500" />
        </div>

        <div>
            <x-premium-button type="submit" variant="primary" class="w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2" ::disabled="loading">
                <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">{{ __('Confirm') }}</span>
                <span x-show="loading" x-cloak>{{ __('Confirming...') }}</span>
            </x-premium-button>
        </div>
    </form>
</x-guest-layout>

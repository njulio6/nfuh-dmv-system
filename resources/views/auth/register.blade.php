<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <!-- Name -->
        <div class="flex flex-col gap-1.5">
            <x-premium-input 
                id="name" 
                label="{{ __('Full Name') }}" 
                type="text" 
                name="name" 
                placeholder="John Doe"
                :value="old('name')" 
                required 
                autofocus 
                autocomplete="name" 
            />
            <x-input-error :messages="$errors->get('name')" class="text-xs font-semibold text-red-500" />
        </div>

        <!-- Email Address -->
        <div class="flex flex-col gap-1.5">
            <x-premium-input 
                id="email" 
                label="{{ __('Email Address') }}" 
                type="email" 
                name="email" 
                placeholder="you@example.com"
                :value="old('email')" 
                required 
                autocomplete="username" 
            />
            <x-input-error :messages="$errors->get('email')" class="text-xs font-semibold text-red-500" />
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-premium-input 
                id="password" 
                label="{{ __('Password') }}" 
                type="password" 
                name="password" 
                placeholder="••••••••"
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password')" class="text-xs font-semibold text-red-500" />
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1.5">
            <x-premium-input 
                id="password_confirmation" 
                label="{{ __('Confirm Password') }}" 
                type="password" 
                name="password_confirmation" 
                placeholder="••••••••"
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="text-xs font-semibold text-red-500" />
        </div>

        <!-- Register Button -->
        <div class="mt-1">
            <x-premium-button type="submit" variant="primary" class="w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2" ::disabled="loading">
                <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">{{ __('Register') }}</span>
                <span x-show="loading" x-cloak>{{ __('Registering...') }}</span>
            </x-premium-button>
        </div>

        <!-- Login Backlink -->
        <div class="text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400">
            Already registered? 
            <a href="{{ route('login') }}" class="font-bold text-zinc-950 dark:text-white hover:underline transition-all">
                Log in here
            </a>
        </div>
    </form>
</x-guest-layout>

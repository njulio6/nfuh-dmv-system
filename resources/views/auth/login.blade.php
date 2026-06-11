<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-sm font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-500/5 px-4 py-2.5 rounded-xl border border-emerald-500/20" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5" x-data="{ loading: false }" @submit="loading = true">
        @csrf

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
                autofocus 
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
                autocomplete="current-password" 
            />
            <x-input-error :messages="$errors->get('password')" class="text-xs font-semibold text-red-500" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center select-none cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-zinc-200 dark:border-zinc-800 text-zinc-900 focus:ring-zinc-950 dark:focus:ring-zinc-50 dark:bg-zinc-950 w-4 h-4 transition-all" name="remember">
                <span class="ms-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs font-bold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white transition-colors" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <div class="mt-1">
            <x-premium-button type="submit" variant="primary" class="w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2" ::disabled="loading">
                <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">{{ __('Log in') }}</span>
                <span x-show="loading" x-cloak>{{ __('Logging in...') }}</span>
            </x-premium-button>
        </div>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                Don't have an account? 
                <a href="{{ route('register') }}" class="font-bold text-zinc-950 dark:text-white hover:underline transition-all">
                    Create one here
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>

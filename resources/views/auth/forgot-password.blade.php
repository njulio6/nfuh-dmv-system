<x-guest-layout>
    <div class="mb-4 text-xs font-semibold leading-relaxed text-zinc-500 dark:text-zinc-400">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-sm font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-500/5 px-4 py-2.5 rounded-xl border border-emerald-500/20" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5" x-data="{ loading: false }" @submit="loading = true">
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
            />
            <x-input-error :messages="$errors->get('email')" class="text-xs font-semibold text-red-500" />
        </div>

        <div class="flex flex-col gap-3">
            <x-premium-button type="submit" variant="primary" class="w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2" ::disabled="loading">
                <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">{{ __('Email Password Reset Link') }}</span>
                <span x-show="loading" x-cloak>{{ __('Sending Link...') }}</span>
            </x-premium-button>

            <a href="{{ route('login') }}" class="text-center text-xs font-bold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white transition-all">
                Back to Login
            </a>
        </div>
    </form>
</x-guest-layout>

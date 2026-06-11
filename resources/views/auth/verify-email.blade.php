<x-guest-layout>
    <div class="mb-4 text-xs font-semibold leading-relaxed text-zinc-500 dark:text-zinc-400">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-500/5 px-4 py-2.5 rounded-xl border border-emerald-500/20">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <x-premium-button type="submit" variant="primary" class="font-bold py-2 rounded-lg flex items-center justify-center gap-2" ::disabled="loading">
                <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">{{ __('Resend Verification Email') }}</span>
                <span x-show="loading" x-cloak>{{ __('Sending...') }}</span>
            </x-premium-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs font-bold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white transition-all cursor-pointer underline hover:no-underline">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>

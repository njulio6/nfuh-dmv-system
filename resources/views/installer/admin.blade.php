<x-installer-layout>
    <div class="flex flex-col gap-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-950 dark:text-white tracking-tight">Step 4: Create Administrator Account</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Define credentials for the initial super administrative account to access the dashboard portal settings.</p>
        </div>

        @if(session('error'))
            <div class="text-xs font-semibold text-red-600 dark:text-red-400 bg-red-500/10 dark:bg-red-500/5 px-4 py-3 rounded-xl border border-red-500/20 leading-relaxed">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('install.admin.save') }}" class="flex flex-col gap-5" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <!-- Name -->
            <div class="flex flex-col gap-1.5">
                <x-premium-input 
                    id="name" 
                    label="Administrator Full Name" 
                    type="text" 
                    name="name" 
                    placeholder="e.g. System Admin" 
                    :value="old('name')" 
                    required 
                />
                <x-input-error :messages="$errors->get('name')" class="text-xs font-semibold text-red-500" />
            </div>

            <!-- Email Address -->
            <div class="flex flex-col gap-1.5">
                <x-premium-input 
                    id="email" 
                    label="Email Address" 
                    type="email" 
                    name="email" 
                    placeholder="admin@example.com" 
                    :value="old('email')" 
                    required 
                />
                <x-input-error :messages="$errors->get('email')" class="text-xs font-semibold text-red-500" />
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <x-premium-input 
                    id="password" 
                    label="Password" 
                    type="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required 
                />
                <x-input-error :messages="$errors->get('password')" class="text-xs font-semibold text-red-500" />
            </div>

            <!-- Password Confirmation -->
            <div class="flex flex-col gap-1.5">
                <x-premium-input 
                    id="password_confirmation" 
                    label="Confirm Password" 
                    type="password" 
                    name="password_confirmation" 
                    placeholder="••••••••" 
                    required 
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="text-xs font-semibold text-red-500" />
            </div>

            <!-- Submit Button -->
            <div class="mt-2">
                <x-premium-button type="submit" variant="primary" class="w-full py-3.5 rounded-xl font-bold flex items-center justify-center gap-2" ::disabled="loading">
                    <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!loading">Create Account & Complete Setup</span>
                    <span x-show="loading" x-cloak>Creating Administrator Profile...</span>
                </x-premium-button>
            </div>
        </form>
    </div>
</x-installer-layout>

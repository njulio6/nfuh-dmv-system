<x-installer-layout>
    <div class="flex flex-col gap-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-950 dark:text-white tracking-tight">Step 3: Database Settings</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Enter your MySQL database details. We will test connectivity, write configuration parameters, and run migrations.</p>
        </div>

        <!-- Session Error Alert -->
        @if(session('error'))
            <div class="text-xs font-semibold text-red-600 dark:text-red-400 bg-red-500/10 dark:bg-red-500/5 px-4 py-3 rounded-xl border border-red-500/20 leading-relaxed">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('install.database.save') }}" class="flex flex-col gap-5" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <!-- Host & Port Grid -->
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2 flex flex-col gap-1.5">
                    <x-premium-input 
                        id="host" 
                        label="Database Host" 
                        type="text" 
                        name="host" 
                        placeholder="127.0.0.1" 
                        :value="old('host', '127.0.0.1')" 
                        required 
                    />
                    <x-input-error :messages="$errors->get('host')" class="text-xs font-semibold text-red-500" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <x-premium-input 
                        id="port" 
                        label="Port" 
                        type="number" 
                        name="port" 
                        placeholder="3306" 
                        :value="old('port', '3306')" 
                        required 
                    />
                    <x-input-error :messages="$errors->get('port')" class="text-xs font-semibold text-red-500" />
                </div>
            </div>

            <!-- Database Name -->
            <div class="flex flex-col gap-1.5">
                <x-premium-input 
                    id="database" 
                    label="Database Name" 
                    type="text" 
                    name="database" 
                    placeholder="e.g. njangi" 
                    :value="old('database', 'njangi')" 
                    required 
                />
                <x-input-error :messages="$errors->get('database')" class="text-xs font-semibold text-red-500" />
            </div>

            <!-- Username & Password Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <x-premium-input 
                        id="username" 
                        label="Username" 
                        type="text" 
                        name="username" 
                        placeholder="e.g. njangi" 
                        :value="old('username', 'njangi')" 
                        required 
                    />
                    <x-input-error :messages="$errors->get('username')" class="text-xs font-semibold text-red-500" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <x-premium-input 
                        id="password" 
                        label="Password" 
                        type="password" 
                        name="password" 
                        placeholder="••••••••" 
                        :value="old('password')" 
                    />
                    <x-input-error :messages="$errors->get('password')" class="text-xs font-semibold text-red-500" />
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-2">
                <x-premium-button type="submit" variant="primary" class="w-full py-3.5 rounded-xl font-bold flex items-center justify-center gap-2" ::disabled="loading">
                    <svg x-show="loading" class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!loading">Test & Save Database Settings</span>
                    <span x-show="loading" x-cloak>Connecting & Running Migrations...</span>
                </x-premium-button>
            </div>
        </form>
    </div>
</x-installer-layout>

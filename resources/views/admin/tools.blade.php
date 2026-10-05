@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 animate-fadeIn">
    <!-- Header Row -->
    <x-premium-header 
        title="System Tools & Maintenance" 
        subtitle="Manage database migrations, clear system caches, and verify environment health without terminal access."
    />

    <!-- Session Feedback Alerts -->
    @if (session('success'))
        <div class="p-3.5 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800/60 rounded-xl text-green-850 dark:text-green-400 text-xs font-semibold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-850 dark:text-red-400 text-xs font-semibold flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- System Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- PHP Version Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex items-center gap-4">
            <div class="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                <i data-lucide="code" class="w-6 h-6 text-zinc-655 dark:text-zinc-400"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider font-bold text-zinc-400 dark:text-zinc-500">PHP Version</p>
                <p class="text-lg font-bold text-zinc-800 dark:text-white mt-0.5">{{ $phpVersion }}</p>
            </div>
        </div>

        <!-- DB Connection Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex items-center gap-4">
            <div class="p-3 rounded-xl {{ $dbConnected ? 'bg-green-50 dark:bg-green-950/20 text-green-600' : 'bg-red-50 dark:bg-red-950/20 text-red-655' }}">
                <i data-lucide="database" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider font-bold text-zinc-400 dark:text-zinc-500">Database Status</p>
                <p class="text-sm font-bold text-zinc-800 dark:text-white mt-0.5">
                    @if($dbConnected)
                        Connected <span class="text-xs font-normal text-zinc-400 dark:text-zinc-500">({{ $dbName }})</span>
                    @else
                        Disconnected
                    @endif
                </p>
            </div>
        </div>

        <!-- Storage Status Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex items-center gap-4">
            <div class="p-3 rounded-xl {{ $storageLinkExists ? 'bg-green-50 dark:bg-green-950/20 text-green-600' : 'bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600' }}">
                <i data-lucide="link" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider font-bold text-zinc-400 dark:text-zinc-500">Storage Link</p>
                <p class="text-sm font-bold text-zinc-800 dark:text-white mt-0.5">
                    {{ $storageLinkExists ? 'Symlink Active' : 'Missing Symlink' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Action Tools Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Database Migration Tool -->
        <x-premium-card title="Database Migrations">
            <div class="flex flex-col h-full justify-between gap-5">
                <div class="flex flex-col gap-2.5">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                        Applies any pending database structural updates safely to the database. This does <strong>NOT</strong> delete or wipe any data.
                    </p>
                    
                    <div class="border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-3 bg-zinc-50 dark:bg-zinc-950/50 mt-1 max-h-[160px] overflow-y-auto">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Migration Status</span>
                        <pre class="text-[10px] font-mono text-zinc-600 dark:text-zinc-400 whitespace-pre-line leading-normal">{{ $migrationStatus }}</pre>
                    </div>
                </div>

                <form method="POST" action="{{ route('install.database.save') /* We use post route on admin tools controller */ }}" class="hidden">@csrf</form>
                
                <form method="POST" action="{{ route('admin.tools.migrate') }}" class="w-full mt-2">
                    @csrf
                    <x-premium-button type="submit" variant="primary" class="w-full justify-center">
                        <i data-lucide="play" class="w-4 h-4 mr-2 shrink-0"></i>
                        Run Pending Migrations
                    </x-premium-button>
                </form>
            </div>
        </x-premium-card>

        <!-- Cache Clear Tool -->
        <x-premium-card title="Clear Application Caches">
            <div class="flex flex-col h-full justify-between gap-5">
                <div class="flex flex-col gap-2.5">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                        Clears the application cache, configuration cache, route definitions, and compiled Blade views. Run this if settings adjustments or code edits are not reflecting.
                    </p>
                    <div class="flex flex-col gap-2 mt-2">
                        <div class="flex items-center gap-2 text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-green-500"></i>
                            <span>Config Cache</span>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-green-500"></i>
                            <span>Application Cache</span>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-green-500"></i>
                            <span>Route Registry</span>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-green-500"></i>
                            <span>Compiled Views</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.tools.clear-cache') }}" class="w-full mt-2">
                    @csrf
                    <x-premium-button type="submit" variant="secondary" class="w-full justify-center">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2 shrink-0"></i>
                        Clear All Caches
                    </x-premium-button>
                </form>
            </div>
        </x-premium-card>

        <!-- Storage Link Tool -->
        <x-premium-card title="Storage Link Management">
            <div class="flex flex-col h-full justify-between gap-5">
                <div class="flex flex-col gap-2.5">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                        Establishes a symbolic link from the public folder to the internal private storage. Rebuild this if images, receipts, screenshots, or uploaded attachments fail to load in member listings.
                    </p>
                    <div class="mt-4 p-3 bg-zinc-50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-800/80 rounded-xl text-center">
                        <span class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 block">Current Public Link Target</span>
                        <code class="text-[10px] font-mono text-zinc-700 dark:text-zinc-350 block mt-1 truncate">/public/storage/ &rarr; /storage/app/public/</code>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.tools.storage-link') }}" class="w-full mt-2">
                    @csrf
                    <x-premium-button type="submit" variant="secondary" class="w-full justify-center" :disabled="$storageLinkExists">
                        <i data-lucide="link" class="w-4 h-4 mr-2 shrink-0"></i>
                        Generate Storage Link
                    </x-premium-button>
                </form>
            </div>
        </x-premium-card>
    </div>

    <!-- Console Log Panel -->
    @if ($cmdLog)
        <div class="w-full">
            <x-premium-card title="Command Output Log">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-450 dark:text-zinc-500">Execution Stream Output</span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $cmdStatus === 'success' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500' }}">
                            {{ $cmdStatus === 'success' ? 'Completed Successfully' : 'Execution Failed' }}
                        </span>
                    </div>
                    
                    <div class="border border-zinc-300 dark:border-zinc-800 bg-zinc-950 text-zinc-200 font-mono text-[11px] p-4 rounded-xl overflow-x-auto leading-relaxed shadow-inner max-h-[300px]">
                        <pre class="whitespace-pre-wrap">{{ $cmdLog }}</pre>
                    </div>
                </div>
            </x-premium-card>
        </div>
    @endif
</div>
@endsection

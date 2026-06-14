<x-installer-layout>
    <div class="flex flex-col gap-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-950 dark:text-white tracking-tight">Step 1: PHP Server Requirements</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">We need to verify that your PHP installation meets the minimum server extensions criteria.</p>
        </div>

        <div class="border border-zinc-200/80 dark:border-zinc-800/80 rounded-xl overflow-hidden shadow-xs bg-zinc-50/30 dark:bg-zinc-950/10">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-zinc-100/50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-bold border-b border-zinc-200/60 dark:border-zinc-800/60">
                        <th class="p-3">Requirement</th>
                        <th class="p-3">Current</th>
                        <th class="p-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/50 dark:divide-zinc-800/50 font-medium">
                    @foreach($requirements as $label => $req)
                        <tr class="text-zinc-800 dark:text-zinc-200">
                            <td class="p-3 font-semibold">{{ $label }}</td>
                            <td class="p-3 text-zinc-500 dark:text-zinc-400 font-mono">{{ $req['current'] }}</td>
                            <td class="p-3 text-right">
                                @if($req['status'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                        <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                        Passed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/10 text-red-600 dark:text-red-400">
                                        <span class="h-1 w-1 rounded-full bg-red-500"></span>
                                        Failed
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="w-full mt-2">
            @if($allPassed)
                <x-premium-button href="{{ route('install.permissions') }}" variant="primary" class="w-full py-3.5 rounded-xl font-bold">
                    Check Permissions
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </x-premium-button>
            @else
                <div class="mb-4 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-500/10 dark:bg-red-500/5 px-4 py-3 rounded-xl border border-red-500/20 leading-relaxed">
                    <strong>Warning:</strong> One or more server requirements failed. Please enable the missing extension(s) inside your loaded `php.ini` configuration and refresh the page to continue.
                </div>
                <x-premium-button @click="window.location.reload()" variant="secondary" class="w-full py-3.5 rounded-xl font-bold">
                    Refresh Check
                </x-premium-button>
            @endif
        </div>
    </div>
</x-installer-layout>

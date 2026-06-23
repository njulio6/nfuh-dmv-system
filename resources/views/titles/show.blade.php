@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 animate-fadeIn">
    <!-- Header Row -->
    <x-premium-header 
        title="Traditional Title Details" 
        subtitle="Detailed configuration and assigned members for: {{ $title->name }}"
        backUrl="{{ route('titles.index') }}"
    />

    <!-- Main Split Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start -mt-3">
        
        <!-- Left: Quick Info Badge Card -->
        <x-premium-card class="lg:col-span-4 items-center text-center">
            <!-- Initials/Abbreviation Avatar -->
            <div class="w-20 h-20 rounded-2xl bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 font-black text-3xl flex items-center justify-center shadow-md mb-4 uppercase">
                {{ mb_substr($title->name, 0, 2) }}
            </div>
            
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight font-display mb-1">
                {{ $title->name }}
            </h2>
            
            <div class="flex items-center gap-1.5 text-xs text-zinc-550 dark:text-zinc-400 font-medium font-mono mb-4">
                <span>Traditional Title ID:</span>
                <span class="font-bold text-zinc-800 dark:text-zinc-200">#{{ $title->id }}</span>
            </div>

            <div class="h-[1px] bg-zinc-100 dark:bg-zinc-800/60 w-full mb-5"></div>

            <!-- Profile Attributes list -->
            <div class="flex flex-col gap-3.5 w-full text-left">
                <!-- Level -->
                <div class="flex items-center justify-between text-xs border-b border-zinc-50 dark:border-zinc-800/40 pb-2.5">
                    <span class="font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider text-[9px]">Hierarchy Level</span>
                    <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ $title->level }}</span>
                </div>
                <!-- Assigned Members Count -->
                <div class="flex items-center justify-between text-xs border-b border-zinc-50 dark:border-zinc-800/40 pb-2.5">
                    <span class="font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider text-[9px]">Assigned Members</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $title->members->count() }} Members</span>
                </div>
            </div>

            <!-- Actions Panel -->
            <div class="mt-6 flex flex-col gap-2 w-full">
                <x-premium-button variant="primary" href="{{ route('titles.edit', $title->id) }}" class="w-full text-xs py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4z"></path></svg>
                    <span>Edit Title Settings</span>
                </x-premium-button>
                <x-premium-button variant="secondary" href="{{ route('titles.index') }}" class="w-full text-xs py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    <span>Back to Titles</span>
                </x-premium-button>
            </div>
        </x-premium-card>

        <!-- Right: Assigned Members List Card -->
        <div class="lg:col-span-8 flex flex-col gap-5">
            <x-premium-card title="Assigned Members List">
                @if($title->members->isEmpty())
                    <div class="text-center py-12 text-zinc-400 dark:text-zinc-650">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-zinc-300 dark:text-zinc-700 mx-auto mb-3.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <p class="text-xs font-semibold text-zinc-550 dark:text-zinc-400">No members currently hold this traditional title.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 text-[10px] font-black uppercase text-zinc-400 dark:text-zinc-500 tracking-wider">
                                    <th class="py-2.5 px-3">Member ID</th>
                                    <th class="py-2.5 px-3">Name</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/40 text-xs">
                                @foreach($title->members as $member)
                                    <tr class="hover:bg-zinc-50/40 dark:hover:bg-zinc-800/10 transition-colors">
                                        <!-- ID -->
                                        <td class="py-3 px-3">
                                            <span class="font-mono text-[11px] text-zinc-600 dark:text-zinc-400 font-semibold">
                                                /{{ $member->member_code }}
                                            </span>
                                        </td>
                                        <!-- Name -->
                                        <td class="py-3 px-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $member->first_name }} {{ $member->last_name }}
                                        </td>
                                        <!-- Status -->
                                        <td class="py-3 px-3">
                                            @if ($member->status === 'active')
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                                    Active
                                                </span>
                                            @elseif ($member->status === 'suspended')
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-850/40">
                                                    Suspended
                                                </span>
                                            @else
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-450 border border-zinc-200 dark:border-zinc-700">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <!-- Action link to Member Profile -->
                                        <td class="py-3 px-3 text-center">
                                            <a 
                                                href="{{ route('members.show', $member->id) }}" 
                                                class="inline-flex items-center justify-center p-1.5 rounded-[10px] text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95"
                                                title="View Member Profile"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-premium-card>
        </div>

    </div>
</div>
@endsection

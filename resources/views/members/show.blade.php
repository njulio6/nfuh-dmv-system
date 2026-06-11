@extends('layouts.app')

@section('content')

    <!-- Header area with Back Button -->
    <x-premium-header 
        title="Member Profile" 
        subtitle="Profile details: {{ $member->first_name }} {{ $member->last_name }}" 
        back-url="{{ route('members.index') }}" 
    />

    <!-- Main Split Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start -mt-3">
        
        <!-- Left: Quick Info Badge Card -->
        <x-premium-card class="lg:col-span-4 items-center text-center">
            <!-- Large Initials Avatar -->
            <div class="w-20 h-20 rounded-2xl bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 font-black text-3xl flex items-center justify-center select-none shadow-md mb-4">
                {{ mb_substr($member->first_name, 0, 1) }}{{ mb_substr($member->last_name, 0, 1) }}
            </div>
            
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight font-display mb-1">
                {{ $member->first_name }} {{ $member->last_name }}
            </h2>
            
            <div class="flex items-center gap-1.5 text-xs text-zinc-550 dark:text-zinc-400 font-medium font-mono mb-4">
                <span>Member ID:</span>
                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $member->member_code }}</span>
            </div>

            <!-- Status Badge -->
            <div class="mb-5 select-none">
                @if ($member->status === 'active')
                    <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                        Active Member
                    </span>
                @elseif ($member->status === 'suspended')
                    <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200 dark:border-red-800/40">
                        Suspended
                    </span>
                @else
                    <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                        Inactive
                    </span>
                @endif
            </div>

            <div class="h-[1px] bg-zinc-100 dark:bg-zinc-800/60 w-full mb-5"></div>

            <!-- Profile Attributes list -->
            <div class="flex flex-col gap-3.5 w-full text-left">
                <!-- Rank -->
                <div class="flex items-center justify-between text-xs border-b border-zinc-50 dark:border-zinc-800/40 pb-2.5">
                    <span class="font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider text-[9px]">Rank Title</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->rank->name ?? 'Warrior' }}</span>
                </div>
                <!-- Join Date -->
                <div class="flex items-center justify-between text-xs border-b border-zinc-50 dark:border-zinc-800/40 pb-2.5">
                    <span class="font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider text-[9px]">Join Date</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->join_date ? $member->join_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <!-- Board Roles -->
                <div class="flex flex-col gap-1.5 text-xs">
                    <span class="font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider text-[9px] mb-0.5">Assigned Roles</span>
                    <div class="flex flex-wrap gap-1">
                        @forelse($member->roles as $role)
                            <span class="inline-block bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 px-2.5 py-0.5 rounded-full text-[9px] font-bold border border-zinc-200/10 dark:border-white/5">
                                {{ $role->name }}
                            </span>
                        @empty
                            <span class="text-zinc-400 dark:text-zinc-600 font-medium">None</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Actions Panel -->
            <div class="mt-6 flex flex-col gap-2 w-full">
                <x-premium-button variant="primary" href="{{ route('members.edit', $member) }}" class="w-full text-xs py-2">
                    <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                    <span>Edit Member Profile</span>
                </x-premium-button>
                <x-premium-button variant="secondary" href="{{ route('members.index') }}" class="w-full text-xs py-2">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Directory</span>
                </x-premium-button>
            </div>
        </x-premium-card>

        <!-- Right: Detail Sheets Card -->
        <div class="lg:col-span-8 flex flex-col gap-5">
            
            <!-- Card Section 1: Contact Details -->
            <x-premium-card title="Contact & Location Details">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- Email -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Email Address</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->email ?: 'N/A' }}</span>
                    </div>

                    <!-- Phone -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Phone Number</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->phone ?: 'N/A' }}</span>
                    </div>

                    <!-- Address -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2 md:col-span-2">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Address</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $member->address }}{{ $member->state_code ? ", {$member->state_code}" : '' }}
                        </span>
                    </div>
                </div>
            </x-premium-card>

            <!-- Card Section 2: Kin Details -->
            <x-premium-card title="Emergency & Next of Kin Information">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <!-- Name -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Kin Name</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->next_of_kin_name ?: 'N/A' }}</span>
                    </div>

                    <!-- Phone -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Kin Phone</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->next_of_kin_phone ?: 'N/A' }}</span>
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Kin Email</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->next_of_kin_email ?: 'N/A' }}</span>
                    </div>

                    <!-- Address -->
                    <div class="flex flex-col border-b border-zinc-50 dark:border-zinc-800/40 pb-2 md:col-span-3">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1 select-none">Kin Address</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $member->next_of_kin_address ?: 'N/A' }}</span>
                    </div>
                </div>
            </x-premium-card>

            <!-- Card Section 3: Programs & Groups -->
            <x-premium-card title="Program Participation">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Njangi Rotation -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/20 dark:bg-zinc-950/20">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 select-none">Njangi Rotations</span>
                        @if ($member->participates_in_njangi)
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                        @else
                            <i data-lucide="x-circle" class="w-4 h-4 text-zinc-300 dark:text-zinc-700 shrink-0"></i>
                        @endif
                    </div>

                    <!-- Savings Program -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/20 dark:bg-zinc-950/20">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 select-none">Savings Account</span>
                        @if ($member->participates_in_savings)
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                        @else
                            <i data-lucide="x-circle" class="w-4 h-4 text-zinc-300 dark:text-zinc-700 shrink-0"></i>
                        @endif
                    </div>
                </div>
            </x-premium-card>

        </div>

    </div>

@endsection
@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 animate-fadeIn">
    <!-- Header Row -->
    <x-premium-header 
        title="System & Branding Settings" 
        subtitle="Manage global branding assets, dynamic validation constraints, and system parameters."
    />

    <!-- Validation Errors Alert Block -->
    @if ($errors->any())
        <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-1.5">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-650 shrink-0"></i>
                <span class="font-bold">Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside pl-1 text-[11px] font-medium flex flex-col gap-0.5 mt-1 text-red-700 dark:text-red-400/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="w-full flex flex-col gap-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column: Branding Settings -->
            <x-premium-card title="Branding & Appearance Settings">
                <div class="flex flex-col gap-5">
                    <!-- App Name -->
                    <x-premium-input 
                        label="Application Name" 
                        name="app_name" 
                        value="{{ old('app_name', $settings->app_name) }}" 
                        placeholder="e.g. NFUH DMV System"
                        required 
                    />

                    <!-- Light Logo File Upload -->
                    <div class="flex flex-col w-full">
                        <label for="logo_light" class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Logo (Light Theme)
                        </label>
                        <div class="flex items-center gap-4">
                            @if($settings->logo_light_path)
                                <div class="size-12 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white p-1 overflow-hidden shrink-0 flex items-center justify-center shadow-3xs">
                                    <img src="{{ asset('storage/' . $settings->logo_light_path) }}" alt="Logo Light Preview" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif
                            <input 
                                type="file" 
                                name="logo_light" 
                                id="logo_light"
                                accept="image/*"
                                class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none transition-all file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-950 file:text-white dark:file:bg-white dark:file:text-zinc-950 file:cursor-pointer"
                            >
                        </div>
                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Recommended format: PNG/SVG transparent background, max 2MB. Used in Light mode.</span>
                    </div>

                    <!-- Dark Logo File Upload -->
                    <div class="flex flex-col w-full">
                        <label for="logo_dark" class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Logo (Dark Theme)
                        </label>
                        <div class="flex items-center gap-4">
                            @if($settings->logo_dark_path)
                                <div class="size-12 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-950 p-1 overflow-hidden shrink-0 flex items-center justify-center shadow-3xs">
                                    <img src="{{ asset('storage/' . $settings->logo_dark_path) }}" alt="Logo Dark Preview" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif
                            <input 
                                type="file" 
                                name="logo_dark" 
                                id="logo_dark"
                                accept="image/*"
                                class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none transition-all file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-950 file:text-white dark:file:bg-white dark:file:text-zinc-950 file:cursor-pointer"
                            >
                        </div>
                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Recommended format: PNG/SVG transparent background, max 2MB. Used in Dark mode.</span>
                    </div>

                    <!-- Favicon File Upload -->
                    <div class="flex flex-col w-full">
                        <label for="favicon" class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Favicon
                        </label>
                        <div class="flex items-center gap-4">
                            @if($settings->favicon_path)
                                <div class="size-10 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-1 overflow-hidden shrink-0 flex items-center justify-center shadow-3xs">
                                    <img src="{{ asset('storage/' . $settings->favicon_path) }}" alt="Favicon Preview" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif
                            <input 
                                type="file" 
                                name="favicon" 
                                id="favicon"
                                accept="image/x-icon,image/png,image/jpg"
                                class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none transition-all file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-950 file:text-white dark:file:bg-white dark:file:text-zinc-950 file:cursor-pointer"
                            >
                        </div>
                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Acceptable formats: .ico or .png file, max 1MB.</span>
                    </div>
                </div>
            </x-premium-card>

            <!-- Right Column: System Rules & Constraints -->
            <x-premium-card title="System Rules & Constraints">
                <div class="flex flex-col gap-6">
                    <!-- Beneficiary Count -->
                    <x-premium-input 
                        type="number"
                        label="Session Beneficiary Count Threshold" 
                        name="beneficiary_count" 
                        value="{{ old('beneficiary_count', $settings->beneficiary_count) }}" 
                        placeholder="e.g. 4"
                        min="1"
                        required 
                    />
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 -mt-4">Defines the minimum number of members that must be marked as beneficiaries for each session.</span>

                    <!-- Minimum Savings for Loan Eligibility -->
                    <x-premium-input 
                        type="number"
                        step="0.01"
                        label="Minimum Savings for Loan Eligibility ($)" 
                        name="min_savings_for_loan" 
                        value="{{ old('min_savings_for_loan', $settings->min_savings_for_loan ?? 500.00) }}" 
                        placeholder="e.g. 500.00"
                        min="0"
                        required 
                    />
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 -mt-4">Defines the minimum savings balance required for members to become eligible to request a loan.</span>

                    <!-- Single Benefit Constraint -->
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Benefit Restrictions
                        </span>
                        
                        <label 
                            x-data="{ checked: {{ old('single_benefit_constraint', $settings->single_benefit_constraint) ? 'true' : 'false' }} }"
                            class="group relative flex items-center justify-between p-4 rounded-xl border cursor-pointer select-none transition-all duration-200"
                            :class="checked ? 'border-zinc-950 bg-zinc-50/40 dark:border-zinc-50 dark:bg-zinc-900/40 font-medium' : 'border-zinc-200 dark:border-zinc-800/80 bg-zinc-50/10 dark:bg-zinc-950/10 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/30'"
                        >
                            <div class="flex flex-col gap-1 pr-6">
                                <span class="text-xs font-bold leading-none transition-colors" :class="checked ? 'text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'">
                                    Single-Benefit Constraint per Cycle
                                </span>
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 leading-tight">
                                    When enabled, a member cannot be selected as a beneficiary in multiple sessions of the same rotational cycle.
                                </span>
                            </div>
                            <input
                                type="checkbox"
                                name="single_benefit_constraint"
                                value="1"
                                @change="checked = $el.checked"
                                class="rounded border-zinc-300 dark:border-zinc-800 text-zinc-950 focus:ring-0 focus:ring-offset-0 size-5 dark:bg-zinc-950 transition-colors cursor-pointer shrink-0"
                                {{ old('single_benefit_constraint', $settings->single_benefit_constraint) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </x-premium-card>
        </div>

        <!-- Submit Button Row -->
        <div class="flex items-center justify-end gap-3 mt-2">
            <x-premium-button href="{{ route('dashboard') }}" variant="secondary">
                Cancel
            </x-premium-button>
            <x-premium-button type="submit" variant="primary">
                Save System Settings
            </x-premium-button>
        </div>
    </form>
</div>
@endsection

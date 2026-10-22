@extends('layouts.app')

@section('content')
<div 
    x-data="{
        activeTab: (['branding', 'financial', 'njangi'].includes(new URLSearchParams(window.location.search).get('tab')))
            ? new URLSearchParams(window.location.search).get('tab')
            : '{{ in_array(request('tab'), ['branding', 'financial', 'njangi']) ? request('tab') : 'branding' }}',
        appName: '{{ addslashes(old('app_name', $settings->app_name)) }}',
        minSavings: {{ old('min_savings_for_loan', $settings->min_savings_for_loan ?? 500.00) }},
        defaultInterest: {{ old('default_loan_interest', $settings->default_loan_interest ?? 1.00) }},
        withdrawalFee: {{ old('savings_withdrawal_fee_percent', $settings->savings_withdrawal_fee_percent ?? 2.00) }},
        guarantorMin: {{ old('loan_guarantor_min', $settings->loan_guarantor_min ?? 1) }},
        guarantorMax: {{ old('loan_guarantor_max', $settings->loan_guarantor_max ?? 3) }},
        singleBenefit: {{ old('single_benefit_constraint', $settings->single_benefit_constraint) ? 'true' : 'false' }},
        midCycleEnrollment: {{ old('allow_mid_cycle_enrollment', $settings->allow_mid_cycle_enrollment) ? 'true' : 'false' }},
        midCycleRemoval: {{ old('allow_mid_cycle_removal', $settings->allow_mid_cycle_removal) ? 'true' : 'false' }},
        restrictCheckinToCycle: {{ old('restrict_checkin_to_cycle_members', $settings->restrict_checkin_to_cycle_members) ? 'true' : 'false' }},
        scheduleOrdinal: '{{ old('session_schedule_ordinal', $settings->session_schedule_ordinal ?? 'first') }}',
        scheduleWeekday: '{{ old('session_schedule_weekday', $settings->session_schedule_weekday ?? 'Saturday') }}',
        previews: {
            logo_light: '{{ $settings->logo_light_path ? asset('storage/' . $settings->logo_light_path) : '' }}',
            logo_dark: '{{ $settings->logo_dark_path ? asset('storage/' . $settings->logo_dark_path) : '' }}',
            favicon: '{{ $settings->favicon_path ? asset('storage/' . $settings->favicon_path) : '' }}'
        },
        filenames: {
            logo_light: '',
            logo_dark: '',
            favicon: ''
        },
        handleFile(e, key) {
            const file = e.target.files[0];
            if (file) {
                this.previews[key] = URL.createObjectURL(file);
                this.filenames[key] = file.name;
            }
        },
        themePresetLight: '{{ old('theme_preset_light', $settings->theme_preset_light ?? 'zinc') }}',
        primaryColorLight: '{{ old('primary_color_light', $settings->primary_color_light ?? '#18181b') }}',
        secondaryColorLight: '{{ old('secondary_color_light', $settings->secondary_color_light ?? '#52525b') }}',
        themePresetDark: '{{ old('theme_preset_dark', $settings->theme_preset_dark ?? 'zinc') }}',
        primaryColorDark: '{{ old('primary_color_dark', $settings->primary_color_dark ?? '#ffffff') }}',
        secondaryColorDark: '{{ old('secondary_color_dark', $settings->secondary_color_dark ?? '#a1a1aa') }}',
        presets: [
            { id: 'zinc', name: 'Monochrome Zinc', light: '#18181b', lightSec: '#52525b', dark: '#ffffff', darkSec: '#a1a1aa' },
            { id: 'indigo', name: 'Royal Indigo', light: '#4f46e5', lightSec: '#0284c7', dark: '#6366f1', darkSec: '#38bdf8' },
            { id: 'blue', name: 'Cobalt Blue', light: '#2563eb', lightSec: '#4f46e5', dark: '#3b82f6', darkSec: '#60a5fa' },
            { id: 'emerald', name: 'Emerald Growth', light: '#059669', lightSec: '#0d9488', dark: '#10b981', darkSec: '#14b8a6' },
            { id: 'rose', name: 'Crimson Rose', light: '#e11d48', lightSec: '#f59e0b', dark: '#f43f5e', darkSec: '#fbbf24' },
            { id: 'purple', name: 'Electric Violet', light: '#7c3aed', lightSec: '#ec4899', dark: '#a855f7', darkSec: '#f472b6' }
        ],
        selectPreset(mode, preset) {
            if (mode === 'light') {
                this.themePresetLight = preset.id;
                this.primaryColorLight = preset.light;
                this.secondaryColorLight = preset.lightSec;
            } else {
                this.themePresetDark = preset.id;
                this.primaryColorDark = preset.dark;
                this.secondaryColorDark = preset.darkSec;
            }
            this.updateLiveStyles();
        },
        applyPresetToBoth(preset) {
            this.themePresetLight = preset.id;
            this.primaryColorLight = preset.light;
            this.secondaryColorLight = preset.lightSec;
            this.themePresetDark = preset.id;
            this.primaryColorDark = preset.dark;
            this.secondaryColorDark = preset.darkSec;
            this.updateLiveStyles();
        },
        syncDarkWithLight() {
            this.themePresetDark = this.themePresetLight;
            if (this.themePresetLight === 'custom') {
                this.primaryColorDark = this.primaryColorLight;
                this.secondaryColorDark = this.secondaryColorLight;
            } else {
                const preset = this.presets.find(p => p.id === this.themePresetLight);
                if (preset) {
                    this.primaryColorDark = preset.dark;
                    this.secondaryColorDark = preset.darkSec;
                } else {
                    this.primaryColorDark = this.primaryColorLight;
                    this.secondaryColorDark = this.secondaryColorLight;
                }
            }
            this.updateLiveStyles();
        },
        syncLightWithDark() {
            this.themePresetLight = this.themePresetDark;
            if (this.themePresetDark === 'custom') {
                this.primaryColorLight = this.primaryColorDark;
                this.secondaryColorLight = this.secondaryColorDark;
            } else {
                const preset = this.presets.find(p => p.id === this.themePresetDark);
                if (preset) {
                    this.primaryColorLight = preset.light;
                    this.secondaryColorLight = preset.lightSec;
                } else {
                    this.primaryColorLight = this.primaryColorDark;
                    this.secondaryColorLight = this.secondaryColorDark;
                }
            }
            this.updateLiveStyles();
        },
        getContrast(hex) {
            if (!hex) return '#ffffff';
            hex = hex.replace('#', '');
            if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            if (hex.length !== 6) return '#ffffff';
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            return yiq >= 155 ? '#09090b' : '#ffffff';
        },
        getHexToRgb(hex) {
            if (!hex) return '24, 24, 27';
            hex = hex.replace('#', '');
            if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            if (hex.length !== 6) return '24, 24, 27';
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            return `${r}, ${g}, ${b}`;
        },
        updateLiveStyles() {
            const isDark = document.documentElement.classList.contains('dark');
            const activeColor = isDark ? this.primaryColorDark : this.primaryColorLight;
            const activeText = this.getContrast(activeColor);
            const activeRgb = this.getHexToRgb(activeColor);
            document.documentElement.style.setProperty('--brand-primary', activeColor);
            document.documentElement.style.setProperty('--brand-primary-rgb', activeRgb);
            document.documentElement.style.setProperty('--brand-primary-text', activeText);

            const activeSecColor = isDark ? this.secondaryColorDark : this.secondaryColorLight;
            const activeSecText = this.getContrast(activeSecColor);
            const activeSecRgb = this.getHexToRgb(activeSecColor);
            document.documentElement.style.setProperty('--brand-secondary', activeSecColor);
            document.documentElement.style.setProperty('--brand-secondary-rgb', activeSecRgb);
            document.documentElement.style.setProperty('--brand-secondary-text', activeSecText);
        },
        init() {
            this.$watch('activeTab', (val) => {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', val);
                window.history.replaceState({}, '', url);
            });
            window.addEventListener('popstate', () => {
                const tab = new URLSearchParams(window.location.search).get('tab');
                if (['branding', 'financial', 'njangi'].includes(tab)) {
                    this.activeTab = tab;
                }
            });
            this.$watch('primaryColorLight', () => this.updateLiveStyles());
            this.$watch('secondaryColorLight', () => this.updateLiveStyles());
            this.$watch('primaryColorDark', () => this.updateLiveStyles());
            this.$watch('secondaryColorDark', () => this.updateLiveStyles());
        }
    }" 
    class="flex flex-col gap-6 w-full animate-fadeIn"
>
    <!-- Header Block -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200/80 dark:border-zinc-800/80 pb-5">
        <div class="flex items-center gap-3.5">
            <div class="size-11 rounded-2xl bg-brand-primary text-brand-primary-text flex items-center justify-center shadow-sm shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-950 dark:text-white font-display">
                    System & Branding Settings
                </h1>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Configure organizational branding assets, financial policies, interest rates, and rotational Njangi constraints.
                </p>
            </div>
        </div>

        <!-- Quick Header Save Button -->
        <div class="flex items-center gap-2.5 shrink-0">
            <a 
                href="{{ route('dashboard') }}" 
                class="px-3.5 py-2 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 transition-colors"
            >
                Cancel
            </a>
            <button 
                type="submit" 
                form="settings-form"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brand-primary hover:bg-brand-primary text-brand-primary-text shadow-sm transition-all active:scale-[0.98]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                <span>Save System Settings</span>
            </button>
        </div>
    </div>

    <!-- Validation Errors Alert Block -->
    @if ($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-2xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-2 shadow-3xs">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-red-600 shrink-0">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span class="font-bold">Please correct the following errors before saving:</span>
            </div>
            <ul class="list-disc list-inside pl-1 text-[11px] font-medium flex flex-col gap-1 text-red-700 dark:text-red-400/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Modern Navigation Tabs -->
    <div class="bg-zinc-100/80 dark:bg-zinc-900/90 p-1.5 rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 flex flex-wrap sm:flex-nowrap gap-1.5 shadow-3xs">
        <!-- Tab 1: Branding -->
        <button 
            type="button"
            @click="activeTab = 'branding'"
            class="flex-1 flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-bold transition-all"
            :class="activeTab === 'branding' 
                ? 'bg-brand-primary text-brand-primary-text shadow-xs' 
                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/>
                <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/>
                <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>
                <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/>
                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>
            </svg>
            <span>Branding & Appearance</span>
            <span 
                class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md hidden md:inline transition-colors"
                :class="activeTab === 'branding' ? 'bg-black/15 dark:bg-white/20 text-current' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300'"
            >
                Logo & Assets
            </span>
        </button>

        <!-- Tab 2: Financial & Loans -->
        <button 
            type="button"
            @click="activeTab = 'financial'"
            class="flex-1 flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-bold transition-all"
            :class="activeTab === 'financial' 
                ? 'bg-brand-primary text-brand-primary-text shadow-xs' 
                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            <span>Financial Policies & Loans</span>
            <span 
                class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md hidden md:inline transition-colors"
                :class="activeTab === 'financial' ? 'bg-black/15 dark:bg-white/20 text-current' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300'"
            >
                Fees & Rates
            </span>
        </button>

        <!-- Tab 3: Njangi & Governance -->
        <button 
            type="button"
            @click="activeTab = 'njangi'"
            class="flex-1 flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-bold transition-all"
            :class="activeTab === 'njangi' 
                ? 'bg-brand-primary text-brand-primary-text shadow-xs' 
                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>Njangi & Governance</span>
            <span 
                class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md hidden md:inline transition-colors"
                :class="activeTab === 'njangi' ? 'bg-black/15 dark:bg-white/20 text-current' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300'"
            >
                Rotational Rules
            </span>
        </button>
    </div>

    <!-- Main Settings Form -->
    <form id="settings-form" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="w-full flex flex-col gap-6">
        @csrf
        <input type="hidden" name="active_tab" :value="activeTab">
        <input type="hidden" name="theme_preset_light" :value="themePresetLight">
        <input type="hidden" name="primary_color_light" :value="primaryColorLight">
        <input type="hidden" name="secondary_color_light" :value="secondaryColorLight">
        <input type="hidden" name="theme_preset_dark" :value="themePresetDark">
        <input type="hidden" name="primary_color_dark" :value="primaryColorDark">
        <input type="hidden" name="secondary_color_dark" :value="secondaryColorDark">

        <!-- ========================================== -->
        <!-- TAB 1: BRANDING & APPEARANCE               -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'branding'" x-cloak class="flex flex-col gap-6 animate-fadeIn">
            <!-- App Name & General Identity -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col gap-4">
                <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2"/>
                            <path d="M3 9h18"/>
                            <path d="M9 21V9"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-zinc-950 dark:text-white">Organization & System Title</h2>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">The primary brand name displayed across the portal, navbars, and generated statements.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="lg:col-span-2 flex flex-col gap-1.5">
                        <label for="app_name" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                            Application Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="app_name" 
                                name="app_name" 
                                x-model="appName"
                                value="{{ old('app_name', $settings->app_name) }}" 
                                placeholder="e.g. NFUH DMV System" 
                                required
                                class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                            >
                        </div>
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                            Used as default page title, emails, PDF reports, and top navigation badge.
                        </span>
                    </div>

                    <!-- Live Identity Badge Card -->
                    <div class="bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/60 dark:border-zinc-800/60 rounded-xl p-4 flex flex-col gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            Preview in Navbar
                        </span>
                        <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 shadow-2xs">
                            <div class="size-7 rounded-md bg-brand-primary text-brand-primary-text flex items-center justify-center p-1 font-black text-xs shrink-0">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-zinc-950 dark:text-white truncate" x-text="appName || 'NFUH DMV System'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theme Accent Colors & Mode Presets Card -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col gap-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/>
                                <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/>
                                <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>
                                <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-zinc-950 dark:text-white">Theme Accent Colors & Mode Presets</h2>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Choose the same preset for both modes, configure presets separately, or manually define custom hex values for each mode.</p>
                        </div>
                    </div>
                    <!-- Quick Bi-Directional Sync Buttons -->
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <button 
                            type="button" 
                            @click="syncDarkWithLight()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 transition-colors shadow-3xs shrink-0"
                            title="Copy Light Mode color & preset to Dark Mode"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                            <span>Sync Dark to Light</span>
                        </button>
                        <button 
                            type="button" 
                            @click="syncLightWithDark()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 transition-colors shadow-3xs shrink-0"
                            title="Copy Dark Mode color & preset to Light Mode"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                            <span>Sync Light to Dark</span>
                        </button>
                    </div>
                </div>

                <!-- Unified 1-Click Bar: Set Same Preset For Both Modes -->
                <div class="bg-zinc-50/80 dark:bg-zinc-950/40 border border-zinc-200/80 dark:border-zinc-800/80 rounded-xl p-3.5 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-3xs">
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="size-2 rounded-full bg-indigo-500"></span>
                        <span class="text-xs font-bold text-zinc-900 dark:text-white">Apply Same Preset to Both Modes:</span>
                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 hidden sm:inline">(1-click setup)</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template x-for="p in presets" :key="'both-' + p.id">
                            <button 
                                type="button" 
                                @click="applyPresetToBoth(p)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition-all shadow-3xs cursor-pointer"
                                :class="(themePresetLight === p.id && themePresetDark === p.id)
                                    ? 'border-zinc-950 bg-zinc-950 text-white dark:border-white dark:bg-white dark:text-zinc-950 font-bold shadow-2xs ring-1 ring-zinc-950/20 dark:ring-white/20' 
                                    : 'border-zinc-200/90 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            >
                                <div class="flex items-center -space-x-1 shrink-0">
                                    <span class="size-2.5 rounded-full shadow-2xs border border-black/10 z-10" :style="'background-color: ' + p.light"></span>
                                    <span class="size-2.5 rounded-full shadow-2xs border border-black/10" :style="'background-color: ' + p.lightSec"></span>
                                </div>
                                <span x-text="p.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- 1. LIGHT MODE COLOR CARD -->
                    <div class="border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 rounded-xl p-5 flex flex-col justify-between gap-5">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-amber-500"></span>
                                    <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Light Mode (Primary & Secondary)</h3>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="syncDarkWithLight()" 
                                        class="text-[10px] font-bold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors mr-1 cursor-pointer"
                                        title="Copy this light palette to Dark Mode"
                                    >
                                        Copy to Dark →
                                    </button>
                                    <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono" x-text="primaryColorLight"></span>
                                    <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono" x-text="secondaryColorLight"></span>
                                </div>
                            </div>

                            <!-- Presets Grid -->
                            <div class="flex flex-col gap-2">
                                <span class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400">Curated Presets (Light):</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <template x-for="p in presets" :key="'light-' + p.id">
                                        <button 
                                            type="button" 
                                            @click="selectPreset('light', p)"
                                            class="flex items-center gap-2 px-2.5 py-2 rounded-xl border text-xs font-semibold transition-all text-left cursor-pointer"
                                            :class="themePresetLight === p.id 
                                                ? 'border-zinc-950 bg-white dark:border-white dark:bg-zinc-900 shadow-2xs font-bold text-zinc-950 dark:text-white ring-1 ring-zinc-950/20 dark:ring-white/20' 
                                                : 'border-zinc-200/80 dark:border-zinc-800 bg-white/70 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-900 text-zinc-700 dark:text-zinc-300'"
                                        >
                                            <div class="flex items-center -space-x-1 shrink-0">
                                                <span class="size-3.5 rounded-full shadow-2xs border border-black/10 z-10" :style="'background-color: ' + p.light"></span>
                                                <span class="size-3.5 rounded-full shadow-2xs border border-black/10" :style="'background-color: ' + p.lightSec"></span>
                                            </div>
                                            <span class="truncate" x-text="p.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Dual Manual Custom Hex Inputs (Primary & Secondary) -->
                            <div class="flex flex-col gap-2.5 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/60">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400">Manual Custom Light Palette:</span>
                                    <span class="text-[10px] font-semibold text-zinc-400" x-show="themePresetLight === 'custom'">Custom Active</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Primary Color Picker -->
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Primary Color</label>
                                        <div class="flex items-center gap-2">
                                            <div class="relative size-9 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shrink-0 shadow-2xs cursor-pointer">
                                                <input 
                                                    type="color" 
                                                    x-model="primaryColorLight"
                                                    @input="themePresetLight = 'custom'; updateLiveStyles()"
                                                    class="absolute -top-2 -left-2 size-14 cursor-pointer border-0"
                                                >
                                            </div>
                                            <input 
                                                type="text" 
                                                x-model="primaryColorLight"
                                                @input="themePresetLight = 'custom'; updateLiveStyles()"
                                                placeholder="#18181b" 
                                                maxlength="7"
                                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-mono font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200"
                                            >
                                        </div>
                                    </div>

                                    <!-- Secondary Color Picker -->
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Secondary Color</label>
                                        <div class="flex items-center gap-2">
                                            <div class="relative size-9 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shrink-0 shadow-2xs cursor-pointer">
                                                <input 
                                                    type="color" 
                                                    x-model="secondaryColorLight"
                                                    @input="themePresetLight = 'custom'; updateLiveStyles()"
                                                    class="absolute -top-2 -left-2 size-14 cursor-pointer border-0"
                                                >
                                            </div>
                                            <input 
                                                type="text" 
                                                x-model="secondaryColorLight"
                                                @input="themePresetLight = 'custom'; updateLiveStyles()"
                                                placeholder="#52525b" 
                                                maxlength="7"
                                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-mono font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Live Preview -->
                        <div class="p-3.5 bg-white border border-zinc-200/80 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-2xs">
                            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Preview on Light</span>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all shadow-2xs"
                                    :style="'background-color: ' + primaryColorLight + '; color: ' + getContrast(primaryColorLight)"
                                >
                                    Primary Button
                                </span>
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all shadow-2xs border"
                                    :style="'background-color: ' + secondaryColorLight + '15; color: ' + secondaryColorLight + '; border-color: ' + secondaryColorLight + '40'"
                                >
                                    Secondary Button
                                </span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :style="'background-color: ' + secondaryColorLight + '20; color: ' + secondaryColorLight"
                                >
                                    Pill
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. DARK MODE COLOR CARD -->
                    <div class="border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 rounded-xl p-5 flex flex-col justify-between gap-5">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-indigo-500"></span>
                                    <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Dark Mode (Primary & Secondary)</h3>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="syncLightWithDark()" 
                                        class="text-[10px] font-bold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors mr-1 cursor-pointer"
                                        title="Copy this dark palette to Light Mode"
                                    >
                                        ← Copy to Light
                                    </button>
                                    <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono" x-text="primaryColorDark"></span>
                                    <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono" x-text="secondaryColorDark"></span>
                                </div>
                            </div>

                            <!-- Presets Grid -->
                            <div class="flex flex-col gap-2">
                                <span class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400">Curated Presets (Dark):</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <template x-for="p in presets" :key="'dark-' + p.id">
                                        <button 
                                            type="button" 
                                            @click="selectPreset('dark', p)"
                                            class="flex items-center gap-2 px-2.5 py-2 rounded-xl border text-xs font-semibold transition-all text-left cursor-pointer"
                                            :class="themePresetDark === p.id 
                                                ? 'border-zinc-950 bg-white dark:border-white dark:bg-zinc-900 shadow-2xs font-bold text-zinc-950 dark:text-white ring-1 ring-zinc-950/20 dark:ring-white/20' 
                                                : 'border-zinc-200/80 dark:border-zinc-800 bg-white/70 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-900 text-zinc-700 dark:text-zinc-300'"
                                        >
                                            <div class="flex items-center -space-x-1 shrink-0">
                                                <span class="size-3.5 rounded-full shrink-0 shadow-2xs border border-white/20 z-10" :style="'background-color: ' + p.dark"></span>
                                                <span class="size-3.5 rounded-full shrink-0 shadow-2xs border border-white/20" :style="'background-color: ' + p.darkSec"></span>
                                            </div>
                                            <span class="truncate" x-text="p.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Dual Manual Custom Hex Inputs (Primary & Secondary) -->
                            <div class="flex flex-col gap-2.5 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/60">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400">Manual Custom Dark Palette:</span>
                                    <span class="text-[10px] font-semibold text-zinc-400" x-show="themePresetDark === 'custom'">Custom Active</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Primary Color Picker -->
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Primary Color</label>
                                        <div class="flex items-center gap-2">
                                            <div class="relative size-9 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shrink-0 shadow-2xs cursor-pointer">
                                                <input 
                                                    type="color" 
                                                    x-model="primaryColorDark"
                                                    @input="themePresetDark = 'custom'; updateLiveStyles()"
                                                    class="absolute -top-2 -left-2 size-14 cursor-pointer border-0"
                                                >
                                            </div>
                                            <input 
                                                type="text" 
                                                x-model="primaryColorDark"
                                                @input="themePresetDark = 'custom'; updateLiveStyles()"
                                                placeholder="#ffffff" 
                                                maxlength="7"
                                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-mono font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200"
                                            >
                                        </div>
                                    </div>

                                    <!-- Secondary Color Picker -->
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[10px] font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Secondary Color</label>
                                        <div class="flex items-center gap-2">
                                            <div class="relative size-9 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shrink-0 shadow-2xs cursor-pointer">
                                                <input 
                                                    type="color" 
                                                    x-model="secondaryColorDark"
                                                    @input="themePresetDark = 'custom'; updateLiveStyles()"
                                                    class="absolute -top-2 -left-2 size-14 cursor-pointer border-0"
                                                >
                                            </div>
                                            <input 
                                                type="text" 
                                                x-model="secondaryColorDark"
                                                @input="themePresetDark = 'custom'; updateLiveStyles()"
                                                placeholder="#a1a1aa" 
                                                maxlength="7"
                                                class="w-full bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-mono font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Live Preview -->
                        <div class="p-3.5 bg-zinc-950 border border-zinc-800 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-2xs">
                            <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Preview on Dark</span>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all shadow-2xs"
                                    :style="'background-color: ' + primaryColorDark + '; color: ' + getContrast(primaryColorDark)"
                                >
                                    Primary Button
                                </span>
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all shadow-2xs border"
                                    :style="'background-color: ' + secondaryColorDark + '20; color: ' + secondaryColorDark + '; border-color: ' + secondaryColorDark + '40'"
                                >
                                    Secondary Button
                                </span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :style="'background-color: ' + secondaryColorDark + '25; color: ' + secondaryColorDark"
                                >
                                    Pill
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brand Assets Grid (Light Logo, Dark Logo, Favicon) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- 1. Light Theme Logo Card -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex flex-col justify-between gap-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-amber-500"></span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Logo (Light Theme)</span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                PNG / SVG
                            </span>
                        </div>

                        <!-- Canvas Preview Box -->
                        <div class="w-full h-32 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/20 p-4 flex flex-col items-center justify-center relative overflow-hidden group">
                            <template x-if="previews.logo_light">
                                <img :src="previews.logo_light" alt="Light Logo Preview" class="max-h-full max-w-full object-contain drop-shadow-2xs">
                            </template>
                            <template x-if="!previews.logo_light">
                                <div class="flex flex-col items-center gap-1.5 text-zinc-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="18" x="3" y="3" rx="2"/>
                                        <circle cx="9" cy="9" r="2"/>
                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                    </svg>
                                    <span class="text-[11px] font-medium">No light logo uploaded</span>
                                </div>
                            </template>
                            <span class="absolute bottom-1.5 right-2 text-[9px] font-bold text-zinc-400 bg-white/80 dark:bg-zinc-900/80 px-1.5 py-0.5 rounded">Light Surface</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <template x-if="filenames.logo_light">
                                <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 truncate flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span x-text="filenames.logo_light"></span>
                                </p>
                            </template>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Rendered on light backgrounds & PDF print exports. Max 2MB.
                            </p>
                        </div>
                    </div>

                    <!-- Upload Input Trigger -->
                    <div>
                        <label 
                            for="logo_light" 
                            class="w-full flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/60 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold text-zinc-800 dark:text-zinc-200 cursor-pointer transition-all shadow-3xs"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span x-text="previews.logo_light ? 'Replace Light Logo' : 'Upload Light Logo'"></span>
                        </label>
                        <input 
                            type="file" 
                            name="logo_light" 
                            id="logo_light" 
                            accept="image/*"
                            @change="handleFile($event, 'logo_light')"
                            class="sr-only"
                        >
                    </div>
                </div>

                <!-- 2. Dark Theme Logo Card -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex flex-col justify-between gap-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-indigo-500"></span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Logo (Dark Theme)</span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                PNG / SVG
                            </span>
                        </div>

                        <!-- Canvas Preview Box (Always Dark) -->
                        <div class="w-full h-32 rounded-xl border border-dashed border-zinc-700 bg-zinc-950 p-4 flex flex-col items-center justify-center relative overflow-hidden group">
                            <template x-if="previews.logo_dark">
                                <img :src="previews.logo_dark" alt="Dark Logo Preview" class="max-h-full max-w-full object-contain drop-shadow-2xs">
                            </template>
                            <template x-if="!previews.logo_dark">
                                <div class="flex flex-col items-center gap-1.5 text-zinc-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="18" x="3" y="3" rx="2"/>
                                        <circle cx="9" cy="9" r="2"/>
                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                    </svg>
                                    <span class="text-[11px] font-medium">No dark logo uploaded</span>
                                </div>
                            </template>
                            <span class="absolute bottom-1.5 right-2 text-[9px] font-bold text-zinc-400 bg-zinc-900 px-1.5 py-0.5 rounded border border-zinc-800">Dark Surface</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <template x-if="filenames.logo_dark">
                                <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 truncate flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span x-text="filenames.logo_dark"></span>
                                </p>
                            </template>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Rendered when users activate dark theme mode. Max 2MB.
                            </p>
                        </div>
                    </div>

                    <!-- Upload Input Trigger -->
                    <div>
                        <label 
                            for="logo_dark" 
                            class="w-full flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/60 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold text-zinc-800 dark:text-zinc-200 cursor-pointer transition-all shadow-3xs"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span x-text="previews.logo_dark ? 'Replace Dark Logo' : 'Upload Dark Logo'"></span>
                        </label>
                        <input 
                            type="file" 
                            name="logo_dark" 
                            id="logo_dark" 
                            accept="image/*"
                            @change="handleFile($event, 'logo_dark')"
                            class="sr-only"
                        >
                    </div>
                </div>

                <!-- 3. System Favicon Card -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs flex flex-col justify-between gap-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Browser Favicon</span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                .ICO / .PNG
                            </span>
                        </div>

                        <!-- Browser Mockup Preview Box -->
                        <div class="w-full h-32 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-950 p-3 flex flex-col justify-start relative overflow-hidden">
                            <!-- Browser tab chrome bar -->
                            <div class="flex items-center gap-1.5 pb-2 border-b border-zinc-200/70 dark:border-zinc-800/70">
                                <div class="size-2 rounded-full bg-red-400"></div>
                                <div class="size-2 rounded-full bg-amber-400"></div>
                                <div class="size-2 rounded-full bg-emerald-400"></div>
                            </div>
                            <!-- Active Tab simulation -->
                            <div class="mt-2 inline-flex items-center gap-2 bg-white dark:bg-zinc-900 px-3 py-1.5 rounded-lg border border-zinc-200/90 dark:border-zinc-800 shadow-2xs max-w-full">
                                <template x-if="previews.favicon">
                                    <img :src="previews.favicon" alt="Favicon" class="size-4 object-contain rounded-xs shrink-0">
                                </template>
                                <template x-if="!previews.favicon">
                                    <div class="size-4 rounded-xs bg-zinc-950 dark:bg-white text-white dark:text-zinc-950 flex items-center justify-center text-[9px] font-bold shrink-0">
                                        N
                                    </div>
                                </template>
                                <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200 truncate" x-text="appName || 'NFUH DMV System'"></span>
                            </div>
                            <span class="absolute bottom-1.5 right-2 text-[9px] font-bold text-zinc-400">Tab Simulator</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <template x-if="filenames.favicon">
                                <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 truncate flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span x-text="filenames.favicon"></span>
                                </p>
                            </template>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Browser tab shortcut icon. Acceptable formats: .ico or .png, max 1MB.
                            </p>
                        </div>
                    </div>

                    <!-- Upload Input Trigger -->
                    <div>
                        <label 
                            for="favicon" 
                            class="w-full flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/60 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold text-zinc-800 dark:text-zinc-200 cursor-pointer transition-all shadow-3xs"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span x-text="previews.favicon ? 'Replace Favicon' : 'Upload Favicon'"></span>
                        </label>
                        <input 
                            type="file" 
                            name="favicon" 
                            id="favicon" 
                            accept="image/x-icon,image/png,image/jpg"
                            @change="handleFile($event, 'favicon')"
                            class="sr-only"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: FINANCIAL POLICIES & LOANS          -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'financial'" x-cloak class="flex flex-col gap-6 animate-fadeIn">
            <!-- Top Row: Minimum Savings & Default Loan Interest -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Minimum Savings for Loan Eligibility -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col justify-between gap-5">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="1" x2="12" y2="23"/>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">
                                        Loan Eligibility Balance
                                    </h3>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Savings Account Threshold</span>
                                </div>
                            </div>
                            <span class="text-xs font-black px-2.5 py-1 rounded-lg bg-emerald-100/60 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                $<span x-text="Number(minSavings || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="min_savings_for_loan" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                Minimum Savings Required ($) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0"
                                id="min_savings_for_loan" 
                                name="min_savings_for_loan" 
                                x-model="minSavings"
                                value="{{ old('min_savings_for_loan', $settings->min_savings_for_loan ?? 500.00) }}" 
                                placeholder="e.g. 500.00"
                                required
                                class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                            >
                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Members must maintain at least this balance in savings to qualify for loan applications.
                            </span>
                        </div>

                        <!-- Quick presets -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Presets:</span>
                            <button type="button" @click="minSavings = 250" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">$250</button>
                            <button type="button" @click="minSavings = 500" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">$500</button>
                            <button type="button" @click="minSavings = 1000" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">$1,000</button>
                        </div>
                    </div>
                </div>

                <!-- 2. Default Loan Interest Rate -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col justify-between gap-5">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="19" y1="5" x2="5" y2="19"/>
                                        <circle cx="6.5" cy="6.5" r="2.5"/>
                                        <circle cx="17.5" cy="17.5" r="2.5"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">
                                        Default Loan Interest Rate
                                    </h3>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Loan Approval Default</span>
                                </div>
                            </div>
                            <span class="text-xs font-black px-2.5 py-1 rounded-lg bg-blue-100/60 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                <span x-text="Number(defaultInterest || 0).toFixed(2)"></span>%
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="default_loan_interest" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                Default Interest Rate (%) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                max="100"
                                id="default_loan_interest" 
                                name="default_loan_interest" 
                                x-model="defaultInterest"
                                value="{{ old('default_loan_interest', $settings->default_loan_interest ?? 1.00) }}" 
                                placeholder="e.g. 1.00"
                                required
                                class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                            >
                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Automatically pre-populated in the approval modal when evaluating member loans.
                            </span>
                        </div>

                        <!-- Quick presets -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Presets:</span>
                            <button type="button" @click="defaultInterest = 1.00" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">1.0%</button>
                            <button type="button" @click="defaultInterest = 2.00" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">2.0%</button>
                            <button type="button" @click="defaultInterest = 5.00" class="px-2 py-0.5 rounded-md text-[11px] font-semibold border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">5.0%</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Savings Withdrawal Administrative Fee & Guarantors Limits -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 3. Savings Withdrawal Administrative Fee -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col justify-between gap-5">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                                        <line x1="2" x2="22" y1="10" y2="10"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">
                                        Savings Withdrawal Admin Fee
                                    </h3>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Deduction on Payout</span>
                                </div>
                            </div>
                            <span class="text-xs font-black px-2.5 py-1 rounded-lg bg-amber-100/60 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                <span x-text="Number(withdrawalFee || 0).toFixed(2)"></span>%
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="savings_withdrawal_fee_percent" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                Administrative Fee Rate (%) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                max="100"
                                id="savings_withdrawal_fee_percent" 
                                name="savings_withdrawal_fee_percent" 
                                x-model="withdrawalFee"
                                value="{{ old('savings_withdrawal_fee_percent', $settings->savings_withdrawal_fee_percent ?? 2.00) }}" 
                                placeholder="e.g. 2.00"
                                required
                                class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                            >
                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Deducted automatically from member savings upon withdrawal request preview and admin approval.
                            </span>
                        </div>

                        <!-- Dynamic Calculation Simulation -->
                        <div class="bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/60 dark:border-zinc-800/60 rounded-xl p-3 flex items-center justify-between text-xs font-medium">
                            <span class="text-zinc-500 dark:text-zinc-400 text-[11px]">Example on $1,000 withdrawal:</span>
                            <div class="flex items-center gap-3 font-semibold">
                                <span class="text-amber-600 dark:text-amber-400">
                                    Fee: $<span x-text="(1000 * ((withdrawalFee || 0) / 100)).toFixed(2)"></span>
                                </span>
                                <span class="text-emerald-600 dark:text-emerald-400">
                                    Net: $<span x-text="(1000 - (1000 * ((withdrawalFee || 0) / 100))).toFixed(2)"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Loan Guarantor Limits -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col justify-between gap-5">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">
                                        Guarantor Governance Limits
                                    </h3>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Per Loan Application</span>
                                </div>
                            </div>
                            <span class="text-xs font-black px-2.5 py-1 rounded-lg bg-purple-100/60 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                <span x-text="guarantorMin"></span> to <span x-text="guarantorMax"></span> Guarantors
                            </span>
                        </div>

                        <!-- Paired Inputs -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="loan_guarantor_min" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                    Min Required <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    min="1" 
                                    id="loan_guarantor_min" 
                                    name="loan_guarantor_min" 
                                    x-model.number="guarantorMin"
                                    value="{{ old('loan_guarantor_min', $settings->loan_guarantor_min ?? 1) }}" 
                                    required
                                    class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                                >
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Minimum to submit</span>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="loan_guarantor_max" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                    Max Allowed <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    min="1" 
                                    id="loan_guarantor_max" 
                                    name="loan_guarantor_max" 
                                    x-model.number="guarantorMax"
                                    value="{{ old('loan_guarantor_max', $settings->loan_guarantor_max ?? 3) }}" 
                                    required
                                    class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                                >
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Upper threshold</span>
                            </div>
                        </div>

                        <!-- Validation Warning -->
                        <div x-show="guarantorMax < guarantorMin" class="p-2.5 rounded-lg bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 text-[11px] font-semibold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>Maximum allowed guarantors must be greater than or equal to minimum required.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: NJANGI & GOVERNANCE                 -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'njangi'" x-cloak class="flex flex-col gap-6 animate-fadeIn">
            <!-- Beneficiary Count Card -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col gap-4">
                <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-zinc-950 dark:text-white">Session Pot Distribution Rules</h2>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Controls how many members benefit simultaneously during each rotational meeting session.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="lg:col-span-2 flex flex-col gap-1.5">
                        <label for="beneficiary_count" class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                            Session Beneficiary Count Threshold <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="beneficiary_count" 
                                name="beneficiary_count" 
                                value="{{ old('beneficiary_count', $settings->beneficiary_count) }}" 
                                placeholder="e.g. 4" 
                                min="1"
                                required
                                class="w-full bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-50 dark:hover:bg-zinc-950 focus:bg-white dark:focus:bg-zinc-900 text-zinc-900 dark:text-white px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-bold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-200 transition-all"
                            >
                        </div>
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                            Defines the number of members marked as session beneficiaries when finalizing pot distribution.
                        </span>
                    </div>

                    <!-- Explainer Pill -->
                    <div class="bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/60 dark:border-zinc-800/60 rounded-xl p-4 flex flex-col gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            Session Governance
                        </span>
                        <p class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed font-medium">
                            Ensures payout validation checks confirm exactly this quota of beneficiaries before session closure is allowed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Regular Meeting Schedule Pattern Card -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex flex-col gap-4">
                <div class="flex items-center gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-zinc-950 dark:text-white">Regular Meeting Schedule Pattern</h2>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Controls the monthly recurring meeting day used when automatically generating cycle sessions (e.g. First Saturday).</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <!-- Hidden Form Inputs for Schedule Settings -->
                    <input type="hidden" name="session_schedule_ordinal" :value="scheduleOrdinal" required>
                    <input type="hidden" name="session_schedule_weekday" :value="scheduleWeekday" required>

                    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Occurrence / Week of Month -->
                        <div class="flex flex-col gap-1.5 w-full relative z-20">
                            <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1 leading-none h-4">
                                <span>Week of Month</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <x-dropdown align="left" width="w-full" contentClasses="p-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl flex flex-col gap-0.5 max-h-60 overflow-y-auto">
                                <x-slot name="trigger">
                                    <button 
                                        type="button" 
                                        class="w-full h-11 flex items-center justify-between bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/40 text-zinc-900 dark:text-white px-4 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left"
                                    >
                                        <span class="truncate font-semibold text-sm leading-none" x-text="{
                                            'first': 'First (1st)',
                                            'second': 'Second (2nd)',
                                            'third': 'Third (3rd)',
                                            'fourth': 'Fourth (4th)',
                                            'last': 'Last'
                                        }[scheduleOrdinal] || 'First (1st)'"></span>
                                        <svg class="h-4 w-4 shrink-0 text-zinc-400 dark:text-zinc-500 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <template x-for="item in [
                                        { id: 'first', label: 'First (1st)' },
                                        { id: 'second', label: 'Second (2nd)' },
                                        { id: 'third', label: 'Third (3rd)' },
                                        { id: 'fourth', label: 'Fourth (4th)' },
                                        { id: 'last', label: 'Last' }
                                    ]" :key="item.id">
                                        <button 
                                            type="button" 
                                            @click="scheduleOrdinal = item.id" 
                                            class="w-full flex items-center justify-between px-3.5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                            :class="scheduleOrdinal === item.id ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                                        >
                                            <span x-text="item.label"></span>
                                            <span x-show="scheduleOrdinal === item.id" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                                        </button>
                                    </template>
                                </x-slot>
                            </x-dropdown>
                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500 leading-normal min-h-[16px]">
                                Occurrence of the weekday within each month.
                            </span>
                        </div>

                        <!-- Weekday -->
                        <div class="flex flex-col gap-1.5 w-full relative z-10">
                            <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1 leading-none h-4">
                                <span>Meeting Weekday</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <x-dropdown align="left" width="w-full" contentClasses="p-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl flex flex-col gap-0.5 max-h-60 overflow-y-auto">
                                <x-slot name="trigger">
                                    <button 
                                        type="button" 
                                        class="w-full h-11 flex items-center justify-between bg-zinc-50/60 dark:bg-zinc-950/40 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/40 text-zinc-900 dark:text-white px-4 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-semibold focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left"
                                    >
                                        <span class="truncate font-semibold text-sm leading-none" x-text="scheduleWeekday || 'Saturday'"></span>
                                        <svg class="h-4 w-4 shrink-0 text-zinc-400 dark:text-zinc-500 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <template x-for="day in ['Saturday', 'Sunday', 'Friday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']" :key="day">
                                        <button 
                                            type="button" 
                                            @click="scheduleWeekday = day" 
                                            class="w-full flex items-center justify-between px-3.5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left select-none"
                                            :class="scheduleWeekday === day ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                                        >
                                            <span x-text="day"></span>
                                            <span x-show="scheduleWeekday === day" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                                        </button>
                                    </template>
                                </x-slot>
                            </x-dropdown>
                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500 leading-normal min-h-[16px]">
                                The recurring day of the week for general meetings.
                            </span>
                        </div>
                    </div>

                    <!-- Explainer Pill & Live Rule Preview -->
                    <div class="bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/60 dark:border-zinc-800/60 rounded-xl p-4 flex flex-col gap-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                Rule Preview
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/40">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </div>
                        <p class="text-xs text-zinc-700 dark:text-zinc-300 font-semibold leading-relaxed">
                            Meetings recur on the <span class="capitalize underline decoration-zinc-400 font-bold" x-text="{ 'first': 'First (1st)', 'second': 'Second (2nd)', 'third': 'Third (3rd)', 'fourth': 'Fourth (4th)', 'last': 'Last' }[scheduleOrdinal] || 'First (1st)'"></span> <span class="font-bold underline decoration-zinc-400" x-text="scheduleWeekday"></span> of each month.
                        </p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-normal">
                            Session generation will automatically calculate the exact calendar dates (e.g. Nov 7, 2026). Individual session dates can still be adjusted if needed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Policies & Constraints (Modern Switch Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <!-- 1. Single-Benefit Constraint per Cycle -->
                <div 
                    @click="singleBenefit = !singleBenefit"
                    class="bg-white dark:bg-zinc-900 border rounded-2xl p-5 shadow-3xs cursor-pointer select-none transition-all flex flex-col justify-between gap-4 group"
                    :class="singleBenefit 
                        ? 'border-zinc-950 dark:border-white shadow-xs' 
                        : 'border-zinc-200/80 dark:border-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700'"
                >
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </div>
                            <!-- Modern Switch -->
                            <button 
                                type="button" 
                                role="switch"
                                :aria-checked="singleBenefit"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="singleBenefit ? 'bg-zinc-950 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700'"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-950 shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="singleBenefit ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold text-zinc-950 dark:text-white">
                                    Single-Benefit Constraint
                                </h3>
                                <span 
                                    class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider"
                                    :class="singleBenefit ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'"
                                    x-text="singleBenefit ? 'Enforced' : 'Disabled'"
                                ></span>
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed font-medium">
                                When enabled, a member cannot be selected as a beneficiary in multiple sessions of the same rotational cycle.
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Form Input -->
                    <input 
                        type="checkbox" 
                        name="single_benefit_constraint" 
                        value="1" 
                        :checked="singleBenefit"
                        class="hidden"
                    >
                </div>

                <!-- 2. Allow Mid-Cycle Enrollment -->
                <div 
                    @click="midCycleEnrollment = !midCycleEnrollment"
                    class="bg-white dark:bg-zinc-900 border rounded-2xl p-5 shadow-3xs cursor-pointer select-none transition-all flex flex-col justify-between gap-4 group"
                    :class="midCycleEnrollment 
                        ? 'border-zinc-950 dark:border-white shadow-xs' 
                        : 'border-zinc-200/80 dark:border-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700'"
                >
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <line x1="19" y1="8" x2="19" y2="14"/>
                                    <line x1="22" y1="11" x2="16" y2="11"/>
                                </svg>
                            </div>
                            <!-- Modern Switch -->
                            <button 
                                type="button" 
                                role="switch"
                                :aria-checked="midCycleEnrollment"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="midCycleEnrollment ? 'bg-zinc-950 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700'"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-950 shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="midCycleEnrollment ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold text-zinc-950 dark:text-white">
                                    Mid-Cycle Enrollment
                                </h3>
                                <span 
                                    class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider"
                                    :class="midCycleEnrollment ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'"
                                    x-text="midCycleEnrollment ? 'Permitted' : 'Prohibited'"
                                ></span>
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed font-medium">
                                When enabled, administrators can add new participants into a cycle even after sessions are active.
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Form Input -->
                    <input 
                        type="checkbox" 
                        name="allow_mid_cycle_enrollment" 
                        value="1" 
                        :checked="midCycleEnrollment"
                        class="hidden"
                    >
                </div>

                <!-- 3. Allow Mid-Cycle Removal -->
                <div 
                    @click="midCycleRemoval = !midCycleRemoval"
                    class="bg-white dark:bg-zinc-900 border rounded-2xl p-5 shadow-3xs cursor-pointer select-none transition-all flex flex-col justify-between gap-4 group"
                    :class="midCycleRemoval 
                        ? 'border-zinc-950 dark:border-white shadow-xs' 
                        : 'border-zinc-200/80 dark:border-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700'"
                >
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <line x1="22" y1="11" x2="16" y2="11"/>
                                </svg>
                            </div>
                            <!-- Modern Switch -->
                            <button 
                                type="button" 
                                role="switch"
                                :aria-checked="midCycleRemoval"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="midCycleRemoval ? 'bg-zinc-950 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700'"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-950 shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="midCycleRemoval ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold text-zinc-950 dark:text-white">
                                    Mid-Cycle Removal
                                </h3>
                                <span 
                                    class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider"
                                    :class="midCycleRemoval ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'"
                                    x-text="midCycleRemoval ? 'Permitted' : 'Prohibited'"
                                ></span>
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed font-medium">
                                When enabled, administrators can remove participants from a cycle provided they have not yet benefited.
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Form Input -->
                    <input 
                        type="checkbox" 
                        name="allow_mid_cycle_removal" 
                        value="1" 
                        :checked="midCycleRemoval"
                        class="hidden"
                    >
                </div>

                <!-- 4. Restrict Check-in to Cycle Members -->
                <div 
                    @click="restrictCheckinToCycle = !restrictCheckinToCycle"
                    class="bg-white dark:bg-zinc-900 border rounded-2xl p-5 shadow-3xs cursor-pointer select-none transition-all flex flex-col justify-between gap-4 group"
                    :class="restrictCheckinToCycle 
                        ? 'border-zinc-950 dark:border-white shadow-xs' 
                        : 'border-zinc-200/80 dark:border-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700'"
                >
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                            </div>
                            <!-- Modern Switch -->
                            <button 
                                type="button" 
                                role="switch"
                                :aria-checked="restrictCheckinToCycle"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="restrictCheckinToCycle ? 'bg-zinc-950 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700'"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-950 shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="restrictCheckinToCycle ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold text-zinc-950 dark:text-white">
                                    Strict Cycle Roll-Call
                                </h3>
                                <span 
                                    class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider"
                                    :class="restrictCheckinToCycle ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'"
                                    x-text="restrictCheckinToCycle ? 'Restricted' : 'Open to All'"
                                ></span>
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed font-medium">
                                When enabled, only members registered in the session's active Njangi cycle can check in as members.
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Form Input -->
                    <input 
                        type="checkbox" 
                        name="restrict_checkin_to_cycle_members" 
                        value="1" 
                        :checked="restrictCheckinToCycle"
                        class="hidden"
                    >
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Actions Bar -->
        <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
            <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>System configuration changes take effect immediately across all user portals.</span>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <a 
                    href="{{ route('dashboard') }}" 
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 transition-colors"
                >
                    Cancel
                </a>
                <button 
                    type="submit" 
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-brand-primary hover:bg-brand-primary text-brand-primary-text shadow-sm transition-all active:scale-[0.98]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span>Save System Settings</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

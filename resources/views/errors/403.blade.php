@extends('layouts.app')

@section('content')
<div class="min-h-[65vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md text-center">
        
        <!-- Colored Icon (Not funky, clean and tinted) -->
        <div class="mx-auto w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-5 ring-1 ring-amber-500/20">
            <i data-lucide="shield-alert" class="w-7 h-7"></i>
        </div>

        <!-- Status Tag -->
        <span class="inline-block text-xs font-semibold tracking-wider text-amber-600 dark:text-amber-400 uppercase mb-2">
            Error 403
        </span>

        <!-- Main Headline -->
        <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight mb-2">
            Not Permitted
        </h1>

        <!-- Client Requested Subhead -->
        <p class="text-base font-semibold text-zinc-700 dark:text-zinc-200 mb-2">
            You do not have access to this feature.
        </p>

        <!-- Descriptive Body -->
        <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed mb-8 max-w-sm mx-auto">
            Your assigned role does not have authorization to view this area. If you believe this is an error, please contact a system administrator.
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a 
                href="{{ route('dashboard') }}" 
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-white dark:hover:bg-zinc-100 dark:text-zinc-900 transition-colors shadow-2xs"
            >
                <i data-lucide="layout-grid" class="w-4 h-4"></i>
                <span>Return to Dashboard</span>
            </a>
            
            <button 
                type="button"
                onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ route('dashboard') }}'" 
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-white hover:bg-zinc-50 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition-colors"
            >
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Go Back</span>
            </button>
        </div>

    </div>
</div>
@endsection



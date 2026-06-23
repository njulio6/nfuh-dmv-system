@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 animate-fadeIn">
    <!-- Header Row -->
    <x-premium-header 
        title="Edit Traditional Title" 
        subtitle="Modify the traditional rank/title settings."
        backUrl="{{ route('titles.index') }}"
    />

    <!-- Validation Errors Alert Block -->
    @if ($errors->any())
        <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-1.5 mb-2">
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

    <form method="POST" action="{{ route('titles.update', $title->id) }}" class="w-full max-w-2xl flex flex-col gap-6">
        @csrf
        @method('PUT')

        <x-premium-card title="Title Information">
            <div class="flex flex-col gap-5">
                <!-- Title Name -->
                <x-premium-input 
                    label="Title Name" 
                    name="name" 
                    value="{{ old('name', $title->name) }}" 
                    placeholder="e.g. Nformi" 
                    required="true"
                />
                
                <!-- Level / Rank Priority -->
                <x-premium-input 
                    type="number"
                    label="Level (Hierarchy Rank)" 
                    name="level" 
                    value="{{ old('level', $title->level) }}" 
                    placeholder="e.g. 0" 
                    min="0"
                    required="true"
                />
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 -mt-3">A higher level number represents a higher status rank/priority in listings.</span>
            </div>
        </x-premium-card>

        <!-- Submit Button Row -->
        <div class="flex items-center justify-end gap-3 mt-2">
            <x-premium-button href="{{ route('titles.index') }}" variant="secondary">
                Cancel
            </x-premium-button>
            <x-premium-button type="submit" variant="primary">
                Save Changes
            </x-premium-button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')

    <!-- Header area with Back Button -->
    <x-premium-header 
        title="Register New Member" 
        subtitle="Add a new member to the portal directory" 
        back-url="{{ route('members.index') }}" 
    />

    <form action="{{ route('members.store') }}" method="POST" class="flex flex-col gap-6 w-full -mt-3">
        @csrf

        @include('members.partials.form')

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 dark:border-zinc-800 pt-5 mt-2">
            <x-premium-button variant="secondary" href="{{ route('members.index') }}">
                Cancel
            </x-premium-button>
            <x-premium-button type="submit" variant="primary">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Save Member</span>
            </x-premium-button>
        </div>
    </form>

@endsection
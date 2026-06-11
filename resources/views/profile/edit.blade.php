@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Profile Settings</h1>
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="space-y-6">
    <div class="card max-w-2xl">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card max-w-2xl">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="card max-w-2xl">
        <div class="max-w-xl">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection

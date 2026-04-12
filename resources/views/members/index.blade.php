@extends('layouts.app')

@section('content')

<div class="page-header">
    <h1>Members</h1>
    <a href="{{ route('members.create') }}" class="btn">+ Add Member</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('members.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 20px;">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                placeholder="Member ID, name, phone, email"
                value="{{ request('search') }}"
                style="min-width: 280px;"
            >
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="status">Status</label>
            <select name="status" id="status" style="min-width: 180px;">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="state_code">State</label>
            <select name="state_code" id="state_code" style="min-width: 180px;">
                <option value="">All States</option>
                <option value="MD" {{ request('state_code') == 'MD' ? 'selected' : '' }}>Maryland (MD)</option>
                <option value="VA" {{ request('state_code') == 'VA' ? 'selected' : '' }}>Virginia (VA)</option>
                <option value="DC" {{ request('state_code') == 'DC' ? 'selected' : '' }}>District of Columbia (DC)</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 10px;">
            <button type="submit" class="btn">Apply</button>
            <a href="{{ route('members.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    @include('members.partials.table')
</div>

@endsection
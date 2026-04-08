@extends('layouts.app')

@section('content')

<div class="page-header">
    <h1>Member Profile</h1>
    <a href="{{ route('members.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="card">

    <h2 style="margin-bottom: 5px;">
        {{ $member->first_name }} {{ $member->last_name }}
    </h2>

    <p style="margin-top: 0; color: #6b7280;">
        Member ID: <strong>{{ $member->member_code }}</strong>
    </p>

    <div style="margin: 10px 0 20px;">
        @if($member->status === 'active')
            <span class="badge badge-active">Active</span>
        @elseif($member->status === 'inactive')
            <span class="badge badge-inactive">Inactive</span>
        @elseif($member->status === 'suspended')
            <span class="badge badge-suspended">Suspended</span>
        @endif
    </div>

    <div class="profile-grid">
        <div class="profile-label">Email</div>
        <div>{{ $member->email ?? 'N/A' }}</div>

        <div class="profile-label">Phone</div>
        <div>{{ $member->phone ?? 'N/A' }}</div>

        <div class="profile-label">Rank</div>
        <div>{{ $member->rank->name ?? 'No Rank' }}</div>

        <div class="profile-label">Address</div>
        <div>{{ $member->address ?? 'N/A' }}</div>

        <div class="profile-label">Next of Kin</div>
        <div>{{ $member->next_of_kin_name ?? 'N/A' }}</div>

        <div class="profile-label">Next of Kin Phone</div>
        <div>{{ $member->next_of_kin_phone ?? 'N/A' }}</div>
    </div>

    <div class="actions">
        <a href="{{ route('members.edit', $member) }}" class="btn">Edit Member</a>
    </div>

</div>

@endsection
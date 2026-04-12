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
        <div>{{ $member->email ?: 'N/A' }}</div>

        <div class="profile-label">Phone</div>
        <div>{{ $member->phone ?: 'N/A' }}</div>

        <div class="profile-label">Title</div>
        <div>{{ $member->rank->name ?? 'Warrior' }}</div>

        <div class="profile-label">Roles</div>
        <div>
            @forelse($member->roles as $role)
                <span style="
                    display: inline-block;
                    background: #eff6ff;
                    color: #1d4ed8;
                    padding: 4px 8px;
                    border-radius: 9999px;
                    font-size: 12px;
                    margin: 2px 4px 2px 0;
                ">
                    {{ $role->name }}
                </span>
            @empty
                None
            @endforelse
        </div>

        <div class="profile-label">State</div>
        <div>{{ $member->state_code ?: 'N/A' }}</div>

        <div class="profile-label">Join Date</div>
        <div>{{ $member->join_date ? $member->join_date->format('Y-m-d') : 'N/A' }}</div>

        <div class="profile-label">Address</div>
        <div>{{ $member->address ?: 'N/A' }}</div>

        <div class="profile-label">Next of Kin Name</div>
        <div>{{ $member->next_of_kin_name ?: 'N/A' }}</div>

        <div class="profile-label">Next of Kin Phone</div>
        <div>{{ $member->next_of_kin_phone ?: 'N/A' }}</div>

        <div class="profile-label">Next of Kin Email</div>
        <div>{{ $member->next_of_kin_email ?: 'N/A' }}</div>

        <div class="profile-label">Next of Kin Address</div>
        <div>{{ $member->next_of_kin_address ?: 'N/A' }}</div>

        <div class="profile-label">Participation</div>
        <div>
            @php
                $participation = [];

                if ($member->participates_in_njangi) {
                    $participation[] = 'Njangi';
                }

                if ($member->participates_in_savings) {
                    $participation[] = 'Savings';
                }

                if ($member->participates_in_cultural) {
                    $participation[] = 'Cultural';
                }
            @endphp

            {{ count($participation) ? implode(', ', $participation) : 'N/A' }}
        </div>
    </div>

    <div class="actions">
        <a href="{{ route('members.edit', $member) }}" class="btn">Edit Member</a>
    </div>

</div>

@endsection
@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Njangi Cycle</h1>
    <a href="{{ route('njangi-cycles.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if (session('success'))
<div style="margin-bottom: 15px; padding: 10px; background: #d1fae5; color: #065f46; border-radius: 6px;">
    {{ session('success') }}
</div>
@endif

@if (session('error'))
<div style="margin-bottom: 15px; padding: 10px; background: #fee2e2; color: #991b1b; border-radius: 6px;">
    {{ session('error') }}
</div>
@endif

<div class="card">
    <h2 style="margin-bottom: 5px;">
        {{ $njangiCycle->name }}
    </h2>

    <p style="margin-top: 0; color: #6b7280;">
        Year: <strong>{{ $njangiCycle->year }}</strong>
    </p>

    <div class="profile-grid">
        <div class="profile-label">Organization</div>
        <div>{{ $njangiCycle->organization->name ?? 'N/A' }}</div>

        <div class="profile-label">Start Date</div>
        <div>{{ $njangiCycle->start_date?->format('Y-m-d') ?? 'N/A' }}</div>

        <div class="profile-label">End Date</div>
        <div>{{ $njangiCycle->end_date?->format('Y-m-d') ?? 'N/A' }}</div>

        <div class="profile-label">Status</div>
        <div>{{ ucfirst($njangiCycle->status) }}</div>

        <div class="profile-label">Notes</div>
        <div>{{ $njangiCycle->notes ?: 'N/A' }}</div>
    </div>
       <a href="{{ route('njangi-submissions.index') }}" class="btn">Payment Submissions
        </a>
        <a href="{{ route('njangi-contributions.index') }}" class="btn">Contributions / Refunds
        </a>
    <div class="actions" style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        @if ($njangiCycle->sessions->isEmpty())
        <form action="{{ route('njangi-cycles.add-members', $njangiCycle) }}" method="POST">
            @csrf
            <button type="submit" class="btn">Add Members to Cycle</button>
        </form>

        <form action="{{ route('njangi-cycles.assign-benefit-order', $njangiCycle) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning">Assign Benefit Order</button>
        </form>

        <a href="{{ route('njangi-cycles.edit', $njangiCycle) }}" class="btn btn-secondary">Edit Cycle</a>

        <form action="{{ route('njangi-cycles.destroy', $njangiCycle) }}" method="POST"
            onsubmit="return confirm('Delete this cycle?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete Cycle</button>
        </form>
        @endif

        <form action="{{ route('njangi-cycles.generate-sessions', $njangiCycle) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Generate Sessions</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Cycle Members</h3>

    <table class="table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Member ID</th>
                <th>Benefit Order</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($njangiCycle->cycleMembers as $cycleMember)
            <tr>
                <td>{{ $cycleMember->member->first_name }} {{ $cycleMember->member->last_name }}</td>
                <td>{{ $cycleMember->member->member_code }}</td>
                <td>{{ $cycleMember->benefit_order ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3">No members added to this cycle yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Sessions</h3>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Title</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($njangiCycle->sessions as $session)
            <tr>
                <td>{{ $session->session_number }}</td>
                <td>{{ $session->session_date->format('Y-m-d') }}</td>
                <td>{{ $session->title ?: '-' }}</td>
                <td>{{ ucfirst($session->status) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4">No sessions generated yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
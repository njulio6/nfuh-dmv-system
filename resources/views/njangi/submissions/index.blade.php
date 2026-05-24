@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Njangi Payment Submissions</h1>
    <p>
        <a href="{{ route('njangi-cycles.show', 2) }}">← Back to Cycle</a>
    </p>

    @if (session('success'))
    <div style="color: green; margin-bottom: 15px;">
        {{ session('success') }}
    </div>
    @endif

    @if (session('error'))
    <div style="color: red; margin-bottom: 15px;">
        {{ session('error') }}
    </div>
    @endif

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Cycle</th>
                <th>Session</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($submissions as $submission)
            <tr>
                <td>{{ $submission->id }}</td>
                <td>
                    {{ optional($submission->member)->first_name }}
                    {{ optional($submission->member)->last_name }}
                </td>
                <td>{{ optional($submission->cycle)->name ?? $submission->njangi_cycle_id }}</td>
                <td>{{ optional($submission->session)->name ?? $submission->njangi_session_id }}</td>
                <td>{{ number_format($submission->amount, 2) }}</td>
                <td>{{ ucfirst($submission->status) }}</td>
                <td>{{ $submission->submitted_at }}</td>
                <td>
                    @if ($submission->status === 'pending')
                    <form method="POST" action="{{ route('njangi-submissions.approve', $submission) }}">
                        @csrf
                        <button type="submit">
                            Approve
                        </button>
                    </form>
                    @else
                    Approved
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8">No payment submissions found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $submissions->links() }}
    </div>
</div>
@endsection
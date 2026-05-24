@extends('layouts.app')

@section('content')

<div class="card">
    <h1>Njangi Contributions / Refund Tracking</h1>

    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
        <div style="border: 1px solid #222; padding: 15px; flex: 1;">
            <strong>Total Records</strong>
            <div style="font-size: 24px;">{{ $totalContributions }}</div>
        </div>

        <div style="border: 1px solid #222; padding: 15px; flex: 1;">
            <strong>Total Amount</strong>
            <div style="font-size: 24px;">{{ number_format($totalAmount, 2) }}</div>
        </div>
    </div>

    <h2>Refund Summary by Beneficiary</h2>

<table border="1" cellpadding="10" cellspacing="0" width="100%" style="margin-bottom: 30px;">
    <thead>
        <tr>
            <th>Beneficiary</th>
            <th>Expected Refund</th>
            <th>Received</th>
            <th>Remaining</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($memberBalances as $balance)
            <tr>
                <td>{{ $balance['beneficiary'] }}</td>
                <td>{{ number_format($balance['expected'], 2) }}</td>
                <td>{{ number_format($balance['received'], 2) }}</td>
                <td>{{ number_format($balance['remaining'], 2) }}</td>
                <td>{{ $balance['status'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No refund summary available.</td>
            </tr>
        @endforelse
    </tbody>
</table>

    <h2>Contribution Records</h2>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Contributor</th>
                <th>Beneficiary</th>
                <th>Cycle</th>
                <th>Session</th>
                <th>Amount</th>
                <th>Payment Ref</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contributions as $contribution)
                <tr>
                    <td>{{ $contribution->id }}</td>
                    <td>{{ $contribution->contributor->first_name }} {{ $contribution->contributor->last_name }}</td>
                    <td>{{ $contribution->beneficiary->first_name }} {{ $contribution->beneficiary->last_name }}</td>
                    <td>{{ $contribution->cycle->name ?? 'N/A' }}</td>
                    <td>{{ $contribution->session->session_number ?? $contribution->njangi_session_id }}</td>
                    <td>{{ number_format($contribution->amount, 2) }}</td>
                    <td>#{{ $contribution->payment_submission_id }}</td>
                    <td>{{ $contribution->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No contributions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $contributions->links() }}
    </div>
</div>

@endsection
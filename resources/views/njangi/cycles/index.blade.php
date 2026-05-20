@extends('layouts.app')

@section('content')

<div class="page-header">
    <h1>Njangi Cycles</h1>
    <a href="{{ route('njangi-cycles.create') }}" class="btn btn-primary">+ New Cycle</a>
</div>

<div class="card">

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Year</th>
                <th>Organization</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cycles as $cycle)
                <tr>
                    <td>{{ $cycle->name }}</td>
                    <td>{{ $cycle->year }}</td>
                    <td>{{ $cycle->organization->name ?? '-' }}</td>
                    <td>{{ ucfirst($cycle->status) }}</td>
                    <td>
                        <a href="{{ route('njangi-cycles.show', $cycle) }}" class="btn btn-sm">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No cycles found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $cycles->links() }}
    </div>

</div>

@endsection
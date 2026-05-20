@extends('layouts.app')

@section('content')

<div class="page-header">
    <h1>Create Njangi Cycle</h1>
    <a href="{{ route('njangi-cycles.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="card">
    <form action="{{ route('njangi-cycles.store') }}" method="POST">
        @csrf

        <div style="display: grid; gap: 16px;">

            <div>
                <label for="organization_id">Organization</label>
                <select name="organization_id" id="organization_id" class="input" required>
                    <option value="">Select organization</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" {{ old('organization_id') == $organization->id ? 'selected' : '' }}>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </select>
                @error('organization_id')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="name">Cycle Name</label>
                <input type="text" name="name" id="name" class="input" value="{{ old('name') }}" required>
                @error('name')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="year">Year</label>
                <input type="number" name="year" id="year" class="input" value="{{ old('year') }}" required>
                @error('year')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="input" value="{{ old('start_date') }}">
                @error('start_date')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" class="input" value="{{ old('end_date') }}">
                @error('end_date')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="status">Status</label>
                <select name="status" id="status" class="input" required>
                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('status')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="4" class="input">{{ old('notes') }}</textarea>
                @error('notes')
                    <div style="color: #b91c1c; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Create Cycle</button>
            </div>

        </div>
    </form>
</div>

@endsection
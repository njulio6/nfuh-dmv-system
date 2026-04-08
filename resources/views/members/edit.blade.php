@extends('layouts.app')

@section('content')
    <h1>Edit Member</h1>

    <form action="{{ route('members.update', $member) }}" method="POST">
        @csrf
        @method('PUT')

        @include('members.partials.form')

        <button type="submit">Update Member</button>
    </form>

    <a href="{{ route('members.index') }}">Back</a>
@endsection
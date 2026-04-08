@extends('layouts.app')

@section('content')
    <h1>Create Member</h1>

    <form action="{{ route('members.store') }}" method="POST">
        @csrf

        @include('members.partials.form')

        <button type="submit">Save Member</button>
    </form>

    <a href="{{ route('members.index') }}">Back</a>
@endsection
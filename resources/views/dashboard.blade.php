@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold text-gray-900">Welcome, {{ auth()->user()->first_name }}</h1>
    <p class="text-gray-600 mt-2">
        You're logged in via the web session guard. Projects and tasks UI land in later days.
    </p>
</div>
@endsection
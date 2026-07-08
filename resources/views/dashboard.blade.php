@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold text-gray-900">Welcome, {{ auth()->user()->first_name }}</h1>
    <p class="text-gray-600 mt-2">Jump back into your workspace:</p>

    <div class="flex gap-3 mt-4">
        <a href="{{ route('projects.index') }}" class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5">
            Projects
        </a>
        @can('viewAny', \App\Models\Invitation::class)
            <a href="{{ route('invitations.index') }}" class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5">
                Invitations
            </a>
        @endcan
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Invitation')

@section('content')
<div class="max-w-sm mx-auto bg-white p-8 rounded-lg border border-gray-200 text-center">
    <h1 class="text-xl font-semibold text-gray-900 mb-2">You've been invited</h1>
    <p class="text-gray-600 mb-6">
        Join <span class="font-medium text-gray-900">{{ $invitation->organisation->name }}</span>
        as {{ $invitation->role->value }}.
    </p>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded text-left">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="flex gap-3 justify-center">
        <form method="POST" action="{{ route('web.invitations.accept', $token) }}">
            @csrf
            <button type="submit" class="bg-gray-900 text-white text-sm rounded px-4 py-2 hover:bg-gray-800">
                Accept
            </button>
        </form>
        <form method="POST" action="{{ route('web.invitations.reject', $token) }}">
            @csrf
            <button type="submit" class="border border-gray-300 text-gray-700 text-sm rounded px-4 py-2 hover:bg-gray-50">
                Decline
            </button>
        </form>
    </div>
</div>
@endsection
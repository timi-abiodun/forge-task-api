@extends('layouts.app')

@section('title', 'Invitations')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-xl font-semibold text-gray-900">Invitations</h1>
    @can('create', \App\Models\Invitation::class)
        <a href="{{ route('invitations.create') }}" class="bg-gray-900 text-white text-sm rounded px-4 py-2 hover:bg-gray-800">
            Invite someone
        </a>
    @endcan
</div>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-100">
    @forelse ($invitations as $invitation)
        <div class="flex justify-between items-center px-5 py-4">
            <div>
                <p class="text-sm text-gray-900">{{ $invitation->email }}</p>
                <p class="text-xs text-gray-500">{{ $invitation->role->value }} &middot; expires {{ $invitation->expires_at->format('M j, Y') }}</p>
            </div>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('invitations.resend', $invitation) }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Resend</button>
                </form>
                <form method="POST" action="{{ route('invitations.destroy', $invitation) }}" onsubmit="return confirm('Revoke this invitation?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">Revoke</button>
                </form>
            </div>
        </div>
    @empty
        <p class="px-5 py-8 text-sm text-gray-500 text-center">No pending invitations.</p>
    @endforelse
</div>

<div class="mt-4">
    {{ $invitations->links() }}
</div>
@endsection
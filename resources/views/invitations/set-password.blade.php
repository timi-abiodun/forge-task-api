@extends('layouts.app')

@section('title', 'Set your password')

@section('content')
<div class="max-w-sm mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-2 text-gray-900">Welcome!</h1>
    <p class="text-gray-600 text-sm mb-6">Set a password to finish creating your account.</p>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.set.submit') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" value="{{ $email }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-50" disabled>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required autofocus>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800">
            Set password &amp; continue
        </button>
    </form>
</div>
@endsection
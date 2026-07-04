@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="max-w-sm mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-6 text-gray-900">Log in</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                required
                autofocus
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input
                type="password"
                name="password"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                required
            >
        </div>

        <button
            type="submit"
            class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800"
        >
            Log in
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-600">
        No account? <a href="{{ route('register') }}" class="text-gray-900 underline">Register</a>
    </p>
</div>
@endsection
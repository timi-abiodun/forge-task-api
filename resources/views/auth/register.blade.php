@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="max-w-sm mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-6 text-gray-900">Create your account</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First name</label>
                <input
                    type="text"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                    required
                    autofocus
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last name</label>
                <input
                    type="text"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                    required
                >
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                required
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Organisation name</label>
            <input
                type="text"
                name="organisation_name"
                value="{{ old('organisation_name') }}"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                required
            >
            <p class="text-xs text-gray-500 mt-1">You'll be the owner of this organisation.</p>
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

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
            <input
                type="password"
                name="password_confirmation"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                required
            >
        </div>

        <button
            type="submit"
            class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800"
        >
            Create account
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-600">
        Already have an account? <a href="{{ route('login') }}" class="text-gray-900 underline">Log in</a>
    </p>
</div>
@endsection
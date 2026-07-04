@extends('layouts.app')

@section('title', 'New project')

@section('content')
<div class="max-w-lg mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-6 text-gray-900">New project</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
                required
                autofocus
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
                name="description"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                @foreach (\App\Enums\ProjectStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <button
            type="submit"
            class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800"
        >
            Create project
        </button>
    </form>
</div>
@endsection
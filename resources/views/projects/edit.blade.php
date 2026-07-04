@extends('layouts.app')

@section('title', 'Edit project')

@section('content')
<div class="max-w-lg mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-6 text-gray-900">Edit project</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $project->name) }}"
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
            >{{ old('description', $project->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
                @foreach (\App\Enums\ProjectStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project->status?->value) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <button
            type="submit"
            class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800"
        >
            Save changes
        </button>
    </form>
</div>
@endsection
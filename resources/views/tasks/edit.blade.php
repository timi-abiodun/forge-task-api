@extends('layouts.app')

@section('title', 'Edit task')

@section('content')
<div class="max-w-lg mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-6 text-gray-900">Edit task</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- $restricted is a heuristic - see controller note. Disabled fields are
         never submitted by the browser, so this fails safe either way; the
         server-side UpdateTaskRequest is the real enforcement point. --}}
    @if ($restricted)
        <p class="mb-4 text-xs text-gray-500">You can only update the status of this task.</p>
    @endif

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $task->name) }}"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm disabled:bg-gray-100"
                @disabled($restricted)
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
                name="description"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm disabled:bg-gray-100"
                @disabled($restricted)
            >{{ old('description', $task->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $task->status?->value) === $status->value)>
                        {{ ucwords(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
            <input
                type="date"
                name="due_date"
                value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm disabled:bg-gray-100"
                @disabled($restricted)
            >
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800">
            Save changes
        </button>
    </form>
</div>
@endsection
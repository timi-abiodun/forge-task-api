@extends('layouts.app')

@section('title', 'New task')

@section('content')
<div class="max-w-lg mx-auto bg-white p-8 rounded-lg border border-gray-200">
    <h1 class="text-xl font-semibold mb-6 text-gray-900">New task in {{ $project->name }}</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('tasks.store', $project) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="project_id" value="{{ $project->id }}">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required autofocus>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status') === $status->value)>
                        {{ ucwords(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
            <select name="assigned_to" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="">Unassigned</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected(old('assigned_to') === $member->id)>
                        {{ $member->first_name }} {{ $member->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
            <input type="date" name="due_date" value="{{ old('due_date') }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium hover:bg-gray-800">
            Create task
        </button>
    </form>
</div>
@endsection
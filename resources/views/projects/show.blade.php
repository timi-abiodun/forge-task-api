@extends('layouts.app')

@section('title', $project->name)

@section('content')
@if (session('status'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
        {{ session('status') }}
    </div>
@endif

<div class="bg-white p-8 rounded-lg border border-gray-200 mb-6">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $project->name }}</h1>
            @if ($project->description)
                <p class="text-gray-600 mt-2">{{ $project->description }}</p>
            @endif
            <span class="inline-block mt-3 text-xs uppercase tracking-wide text-gray-500">
                {{ $project->status?->value ?? '—' }}
            </span>
        </div>

        <!-- <div class="flex gap-2">
            <a
                href="{{ route('projects.edit', $project) }}"
                class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5"
            >
                Edit
            </a>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 border border-red-200 rounded px-3 py-1.5">
                    Delete
                </button>
            </form>
        </div> -->
        <div class="flex gap-2">
            @can('update', $project)
                <a href="{{ route('projects.edit', $project) }}" class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5">
                    Edit
                </a>
            @endcan
            @can('delete', $project)
                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 border border-red-200 rounded px-3 py-1.5">
                        Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>

<div class="bg-white p-8 rounded-lg border border-gray-200">
    <div class="flex justify-between items-center mb-4">
        <!-- <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Tasks</h2>
        <a href="{{ route('tasks.create', $project) }}" class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5">
            New task
        </a> -->
        @can('create', \App\Models\Task::class)
            <a href="{{ route('tasks.create', $project) }}" class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5">
                New task
            </a>
        @endcan
    </div>

    @forelse ($tasks as $task)
        <a href="{{ route('tasks.show', $task) }}" class="block py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50">
            <p class="text-sm text-gray-900">{{ $task->name }}</p>
        </a>
    @empty
        <p class="text-sm text-gray-500">No tasks yet.</p>
    @endforelse
</div>
@endsection
@extends('layouts.app')

@section('title', $task->name)

@section('content')
@if (session('status'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
        {{ session('status') }}
    </div>
@endif

<div class="bg-white p-8 rounded-lg border border-gray-200">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $task->name }}</h1>
            @if ($task->description)
                <p class="text-gray-600 mt-2">{{ $task->description }}</p>
            @endif
            <div class="flex gap-4 mt-3 text-xs uppercase tracking-wide text-gray-500">
                <span>{{ ucwords(str_replace('_', ' ', $task->status?->value ?? '—')) }}</span>
                @if ($task->due_date)
                    <span>Due {{ $task->due_date->format('M j, Y') }}</span>
                @endif
                @if ($task->assignee)
                    <span>Assigned to {{ $task->assignee->first_name }}</span>
                @endif
            </div>
        </div>

        <div class="flex gap-2">
            @can('update', $task)
                <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1.5">
                    Edit
                </a>
            @endcan
            @can('delete', $task)
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 border border-red-200 rounded px-3 py-1.5">
                        Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <a href="{{ route('projects.show', $task->project_id) }}" class="inline-block mt-6 text-sm text-gray-500 hover:text-gray-900">
        &larr; Back to {{ $task->project->name }}
    </a>
</div>
<div class="bg-white p-8 rounded-lg border border-gray-200 mt-6">
    <h2 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">Attachments</h2>

    @foreach ($task->attachments as $attachment)
        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
            <span class="text-sm text-gray-900">{{ $attachment->file_name }}</span>
            <div class="flex gap-3">
                <a href="{{ route('attachments.download', [$task, $attachment]) }}" class="text-sm text-gray-600 hover:text-gray-900">
                    Download
                </a>
                @can('delete', $attachment)
                    <form method="POST" action="{{ route('attachments.destroy', [$task, $attachment]) }}" onsubmit="return confirm('Delete this file?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                @endcan
            </div>
        </div>
    @endforeach

    @can('create', [\App\Models\Attachment::class, $task])
        <form method="POST" action="{{ route('attachments.store', $task) }}" enctype="multipart/form-data" class="mt-4">
            @csrf
            <input type="file" name="attachment" required class="text-sm">
            <button type="submit" class="ml-2 bg-gray-900 text-white text-sm rounded px-3 py-1.5 hover:bg-gray-800">
                Upload
            </button>
        </form>
    @endcan
</div>
@endsection
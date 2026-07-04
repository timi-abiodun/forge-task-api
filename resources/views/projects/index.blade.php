@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-xl font-semibold text-gray-900">Projects</h1>
    <a href="{{ route('projects.create') }}" class="bg-gray-900 text-white text-sm rounded px-4 py-2 hover:bg-gray-800">
        New project
    </a>
</div>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
        {{ session('status') }}
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-100">
    @forelse ($projects as $project)
        <a href="{{ route('projects.show', $project) }}" class="block px-5 py-4 hover:bg-gray-50">
            <div class="flex justify-between items-center">
                <div>
                    <p class="font-medium text-gray-900">{{ $project->name }}</p>
                    @if ($project->description)
                        <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($project->description, 80) }}</p>
                    @endif
                </div>
                <span class="text-xs uppercase tracking-wide text-gray-500">
                    {{ $project->status?->value ?? '—' }}
                </span>
            </div>
        </a>
    @empty
        <p class="px-5 py-8 text-sm text-gray-500 text-center">No projects yet. Create your first one.</p>
    @endforelse
</div>

<div class="mt-4">
    {{ $projects->links() }}
</div>
@endsection
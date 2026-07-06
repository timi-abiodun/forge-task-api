<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Forge')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="font-semibold text-gray-900">
                    Forge
                </a>

                @auth
                    <a href="{{ route('projects.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        Projects
                    </a>
                    @can('viewAny', \App\Models\Invitation::class)
                        <a href="{{ route('invitations.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Invitations</a>
                    @endcan
<!-- 
                    <a href="{{ route('organisations.create') }}">Create organisation</a> -->
                    

                @endauth
            </div>

                @auth
                <div class="flex items-center gap-4">
                    @php $userOrganisations = auth()->user()->organisations()->get(); @endphp

                    @if ($userOrganisations->count() > 0)
                        <form method="POST" action="{{ route('organisations.switch') }}">
                            @csrf
                            <select
                                name="organisation_id"
                                onchange="this.form.submit()"
                                class="text-sm border border-gray-300 rounded px-2 py-1"
                            >
                                @foreach ($userOrganisations as $org)
                                    <option value="{{ $org->id }}" @selected(session('current_organisation_id') === $org->id)>
                                        {{ $org->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('organisations.create') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            + New org
                        </a>
                    @else
                        <a href="{{ route('organisations.create') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Create organisation
                        </a>
                    @endif

                    @auth
                        @php $unread = auth()->user()->unreadNotifications; @endphp
                        <details class="relative">
                            <summary class="list-none cursor-pointer text-sm text-gray-600 hover:text-gray-900">
                                Notifications
                                @if ($unread->count() > 0)
                                    <span class="ml-1 inline-flex items-center justify-center bg-red-600 text-white text-xs rounded-full h-5 w-5">
                                        {{ $unread->count() }}
                                    </span>
                                @endif
                            </summary>
                            <div class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                @forelse (auth()->user()->notifications()->latest()->take(10)->get() as $notification)
                                    <div class="px-4 py-3 border-b border-gray-100 last:border-0 {{ $notification->read_at ? '' : 'bg-gray-50' }}">
                                        <p class="text-sm text-gray-900">{{ $notification->data['task_name'] ?? 'Notification' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                        @if (!$notification->read_at)
                                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="mt-1">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-500 underline">Mark read</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="px-4 py-6 text-sm text-gray-500 text-center">No notifications.</p>
                                @endforelse
                            </div>
                        </details>
                    @endauth

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                            Log out
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-10">
        @yield('content')
    </main>
</body>
</html>
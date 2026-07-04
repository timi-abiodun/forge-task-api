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
                    @else
                        <a href="{{ route('organisations.create') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Create organisation
                        </a>
                    @endif

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
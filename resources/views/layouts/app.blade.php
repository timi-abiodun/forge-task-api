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
        <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="font-semibold text-gray-900">
                Forge
            </a>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                        Log out
                    </button>
                </form>
            @endauth
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-10">
        @yield('content')
    </main>
</body>
</html>
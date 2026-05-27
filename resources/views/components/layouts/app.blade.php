@props(['title' => 'Japanese Interview AI'])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell">
            <header class="border-b border-white/70 bg-white/75 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('start') }}" class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-teal-700 text-sm font-extrabold text-white">JP</span>
                        <span>
                            <span class="block text-sm font-bold text-zinc-950">Japanese Interview AI</span>
                            <span class="block text-xs font-medium text-zinc-500">AI interview platform</span>
                        </span>
                    </a>

                    <nav class="hidden items-center gap-2 text-sm font-semibold text-zinc-600 sm:flex">
                        @auth
                            <a href="{{ route('admin.dashboard') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Admin</a>
                            <a href="{{ route('start') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Kandidat</a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950" type="submit">Logout Admin</button>
                            </form>
                        @elseif (session('test_session_id'))
                            <a href="{{ route('interview') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Tes</a>
                            <a href="{{ route('results') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Hasil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950" type="submit">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('start') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Kandidat</a>
                            <a href="{{ route('login') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Admin</a>
                        @endif
                    </nav>
                </div>
            </header>

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>

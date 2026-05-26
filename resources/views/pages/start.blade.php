<x-layouts.app title="Mulai Tes - Japanese Interview AI">
    <section class="mx-auto grid min-h-[calc(100vh-73px)] max-w-6xl items-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_0.88fr] lg:px-8">
        <div class="max-w-2xl">
            <p class="mb-4 inline-flex rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-teal-800">
                Wawancara Bahasa Jepang
            </p>
            <h1 class="text-4xl font-extrabold leading-tight text-zinc-950 sm:text-5xl">
                Login untuk memulai tes lisan Bahasa Jepang.
            </h1>
            <p class="mt-5 max-w-xl text-base leading-8 text-zinc-600">
                Setelah masuk, kandidat menjawab 10 pertanyaan melalui rekaman suara. Backend akan menyimpan audio, menyalin suara, dan meminta evaluasi OpenAI.
            </p>

            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-2xl font-extrabold text-teal-700">10</p>
                    <p class="mt-1 text-sm text-zinc-600">Pertanyaan tetap</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-2xl font-extrabold text-amber-600">VN</p>
                    <p class="mt-1 text-sm text-zinc-600">Rekam di browser</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-2xl font-extrabold text-indigo-700">AI</p>
                    <p class="mt-1 text-sm text-zinc-600">Hasil evaluasi</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="panel p-6 sm:p-8">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-zinc-950">Login testing</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">Gunakan akun testing untuk membuat sesi interview baru.</p>
                </div>
                <span class="rounded-md bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-600">Backend ready</span>
            </div>

            <div class="mt-5 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">
                <p class="font-bold">Akun testing</p>
                <p class="mt-1">Username: <span class="font-mono font-bold">admin</span></p>
                <p>Password: <span class="font-mono font-bold">password</span></p>
            </div>

            <div class="mt-6 space-y-4">
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-800">Username</span>
                    <input name="username" value="{{ old('username', 'admin') }}" class="field mt-2" type="text" autocomplete="username" placeholder="admin">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-zinc-800">Password</span>
                    <input name="password" value="password" class="field mt-2" type="password" autocomplete="current-password" placeholder="password">
                </label>
            </div>

            @if ($errors->any())
                <p class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    {{ $errors->first() }}
                </p>
            @endif

            <button class="btn-primary mt-6 w-full" type="submit">
                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5.14v13.72a1 1 0 0 0 1.55.83l10.29-6.86a1 1 0 0 0 0-1.66L9.55 4.31A1 1 0 0 0 8 5.14Z" />
                </svg>
                Login dan mulai tes
            </button>
        </form>
    </section>
</x-layouts.app>

<x-layouts.app title="Admin Login - Japanese Interview AI">
    <section class="min-h-[calc(100vh-73px)] bg-zinc-950 px-4 py-12 text-white sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-5xl items-center gap-8 lg:grid-cols-[minmax(0,1fr)_400px]">
            <div>
                <p class="inline-flex rounded-md border border-indigo-400/30 bg-indigo-400/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-200">
                    Admin Area
                </p>
                <h1 class="mt-5 max-w-2xl text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                    Kelola kode akses kandidat dengan panel ringan.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-zinc-300">
                    Login admin dibangun memakai authentication bawaan Laravel tanpa package tambahan.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.login') }}" class="rounded-lg border border-white/10 bg-white/[0.08] p-6 shadow-2xl shadow-black/30 backdrop-blur sm:p-8">
                @csrf

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-white">Login admin</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-300">Masuk untuk generate dan memantau access code.</p>
                    </div>
                    <span class="rounded-md bg-indigo-400/10 px-3 py-1 text-xs font-bold text-indigo-200">Secure</span>
                </div>

                @if ($errors->any())
                    <p class="mt-5 rounded-md border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm font-semibold text-rose-100">
                        {{ $errors->first() }}
                    </p>
                @endif

                <div class="mt-6 space-y-4">
                    <label class="block">
                        <span class="text-sm font-semibold text-zinc-100">Email</span>
                        <input
                            name="email"
                            value="{{ old('email') }}"
                            class="mt-2 w-full rounded-md border border-white/10 bg-zinc-900 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-400/10"
                            type="email"
                            autocomplete="email"
                            placeholder="admin@ricksite.com"
                            required
                        >
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-zinc-100">Password</span>
                        <input
                            name="password"
                            class="mt-2 w-full rounded-md border border-white/10 bg-zinc-900 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-400/10"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Password admin"
                            required
                        >
                    </label>
                </div>

                <button class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md bg-indigo-400 px-5 py-3 text-sm font-bold text-zinc-950 transition hover:bg-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-400/20" type="submit">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z" />
                    </svg>
                    Masuk dashboard
                </button>
            </form>
        </div>
    </section>
</x-layouts.app>

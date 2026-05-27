<x-layouts.app title="Admin Dashboard - Japanese Interview AI">
    <section class="min-h-[calc(100vh-73px)] bg-zinc-950 px-4 py-8 text-white sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-indigo-200">Admin dashboard</p>
                    <h1 class="mt-2 text-3xl font-extrabold text-white">Access Code Manager</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-300">Generate kode sekali pakai dan pantau siapa yang sudah menggunakannya.</p>
                </div>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="inline-flex items-center justify-center rounded-md border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-bold text-zinc-100 transition hover:bg-white/10" type="submit">
                        Logout
                    </button>
                </form>
            </div>

            @if (session('success'))
                <p class="mb-5 rounded-md border border-teal-400/30 bg-teal-500/10 px-4 py-3 text-sm font-semibold text-teal-100">
                    {{ session('success') }}
                </p>
            @endif

            <div class="grid gap-5 lg:grid-cols-[360px_minmax(0,1fr)]">
                <aside class="space-y-5">
                    <form method="POST" action="{{ route('admin.generate-token') }}" class="rounded-lg border border-white/10 bg-white/[0.08] p-6 shadow-2xl shadow-black/20">
                        @csrf
                        <p class="text-sm font-bold uppercase tracking-wide text-indigo-200">Token baru</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-white">Generate New Access Code</h2>
                        <p class="mt-3 text-sm leading-6 text-zinc-300">Setiap token hanya bisa digunakan satu kali oleh satu kandidat.</p>
                        <button class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md bg-teal-400 px-5 py-4 text-sm font-extrabold text-zinc-950 transition hover:bg-teal-300 focus:outline-none focus:ring-4 focus:ring-teal-400/20" type="submit">
                            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11 5a1 1 0 1 1 2 0v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5Z" />
                            </svg>
                            Generate New Access Code
                        </button>
                    </form>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-white/10 bg-white/[0.06] p-4">
                            <p class="text-2xl font-extrabold text-white">{{ $accessCodes->count() }}</p>
                            <p class="mt-1 text-sm text-zinc-400">Total kode</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.06] p-4">
                            <p class="text-2xl font-extrabold text-teal-200">{{ $accessCodes->where('is_used', false)->count() }}</p>
                            <p class="mt-1 text-sm text-zinc-400">Belum dipakai</p>
                        </div>
                    </div>
                </aside>

                <div class="overflow-hidden rounded-lg border border-white/10 bg-white/[0.08] shadow-2xl shadow-black/20">
                    <div class="border-b border-white/10 px-5 py-4">
                        <h2 class="text-lg font-bold text-white">Riwayat kode akses</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                            <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-zinc-400">
                                <tr>
                                    <th class="px-5 py-3 font-bold">Kode</th>
                                    <th class="px-5 py-3 font-bold">Status</th>
                                    <th class="px-5 py-3 font-bold">Digunakan Oleh</th>
                                    <th class="px-5 py-3 font-bold">Waktu Digunakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10 text-zinc-200">
                                @forelse ($accessCodes as $accessCode)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <span class="font-mono text-base font-extrabold tracking-[0.18em] text-white">{{ $accessCode->code }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if ($accessCode->is_used)
                                                <span class="rounded-md bg-rose-500/10 px-3 py-1 text-xs font-bold text-rose-100">Terpakai</span>
                                            @else
                                                <span class="rounded-md bg-teal-500/10 px-3 py-1 text-xs font-bold text-teal-100">Aktif</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">{{ $accessCode->used_by_name ?? '-' }}</td>
                                        <td class="px-5 py-4">{{ $accessCode->used_at?->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-5 py-10 text-center text-zinc-400" colspan="4">Belum ada kode akses.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

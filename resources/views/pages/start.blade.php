<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mulai Tes — Japanese Interview AI</title>
    <meta name="description" content="Masukkan kode akses untuk memulai Japanese Speaking Assessment berbasis AI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen">
    <section class="min-h-screen px-4 py-10 sm:px-6 lg:px-8 flex items-center">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-5">
                <p class="inline-flex rounded-md border border-teal-400/30 bg-teal-400/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-teal-200">
                    Japanese Speaking Assessment
                </p>

                <div>
                    <h1 class="max-w-3xl text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                        Masukkan kode akses untuk memulai wawancara.
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-8 text-zinc-300">
                        Setiap kandidat membutuhkan satu kode akses dari admin. Kode hanya bisa dipakai sekali agar kuota OpenAI tetap terlindungi.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-white/10 bg-white/[0.06] p-4">
                        <p class="text-2xl font-extrabold text-teal-200">10</p>
                        <p class="mt-1 text-sm text-zinc-300">Pertanyaan tetap</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/[0.06] p-4">
                        <p class="text-2xl font-extrabold text-amber-200">1x</p>
                        <p class="mt-1 text-sm text-zinc-300">Kode sekali pakai</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/[0.06] p-4">
                        <p class="text-2xl font-extrabold text-indigo-200">AI</p>
                        <p class="mt-1 text-sm text-zinc-300">Evaluasi OpenAI</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <form method="POST" action="{{ route('candidate.login') }}" class="rounded-lg border border-white/10 bg-white/[0.08] p-6 shadow-2xl shadow-black/30 backdrop-blur sm:p-8" id="candidate-start-form">
                    @csrf

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-white">Login kandidat</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">Gunakan nama lengkap dan kode akses yang diberikan admin.</p>
                        </div>
                        <span class="rounded-md bg-teal-400/10 px-3 py-1 text-xs font-bold text-teal-200">Protected</span>
                    </div>

                    @if ($errors->any())
                        <p class="mt-5 rounded-md border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm font-semibold text-rose-100">
                            {{ $errors->first() }}
                        </p>
                    @endif

                    <div class="mt-6 space-y-4">
                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-100">Nama Lengkap</span>
                            <input
                                name="candidate_name"
                                value="{{ old('candidate_name') }}"
                                class="mt-2 w-full rounded-md border border-white/10 bg-zinc-900 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-teal-300 focus:ring-4 focus:ring-teal-400/10"
                                type="text"
                                autocomplete="name"
                                placeholder="Contoh: Sato Aulia"
                                required
                            >
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-100">Kode Akses</span>
                            <input
                                name="access_code"
                                value="{{ old('access_code') }}"
                                class="mt-2 w-full rounded-md border border-white/10 bg-zinc-900 px-4 py-3 font-mono text-sm uppercase tracking-[0.24em] text-white outline-none transition placeholder:tracking-normal placeholder:text-zinc-500 focus:border-teal-300 focus:ring-4 focus:ring-teal-400/10"
                                type="text"
                                inputmode="text"
                                maxlength="6"
                                placeholder="ABC123"
                                required
                            >
                        </label>
                    </div>

                    <button class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md bg-teal-500 px-5 py-3 text-sm font-bold text-zinc-950 transition hover:bg-teal-300 focus:outline-none focus:ring-4 focus:ring-teal-400/20" type="submit" id="start-btn">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5.14v13.72a1 1 0 0 0 1.55.83l10.29-6.86a1 1 0 0 0 0-1.66L9.55 4.31A1 1 0 0 0 8 5.14Z" />
                        </svg>
                        Mulai wawancara
                    </button>
                </form>

                {{-- Admin access link --}}
                <div class="flex items-center justify-center gap-2">
                    <svg class="h-3.5 w-3.5 text-zinc-600" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3Zm9 11v-1a7 7 0 0 0-14 0v1h2v-1a5 5 0 0 1 10 0v1Z"/>
                    </svg>
                    <a
                        href="{{ route('login') }}"
                        class="text-xs text-zinc-600 transition hover:text-zinc-400"
                        id="admin-login-link"
                    >
                        Login sebagai Admin
                    </a>
                </div>
            </div>
        </div>
    </section>
<script>
    const startForm = document.getElementById('candidate-start-form');
    const startBtn = document.getElementById('start-btn');

    startForm?.addEventListener('submit', () => {
        startBtn.disabled = true;
        startBtn.classList.add('cursor-not-allowed', 'opacity-70');
        startBtn.innerHTML = `
            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
            </svg>
            Memulai...
        `;
    });
</script>
</body>
</html>

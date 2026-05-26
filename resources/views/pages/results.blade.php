<x-layouts.app title="Hasil Tes - Japanese Interview AI">
    @php
        $averageScore = $session->total_score ? round($session->total_score) : 0;
        $summaryText = match (true) {
            $averageScore >= 88 => 'Kandidat menunjukkan kesiapan komunikasi Jepang yang kuat untuk sesi awal.',
            $averageScore >= 78 => 'Kandidat cukup siap, dengan beberapa area latihan pada pelafalan dan tata bahasa dasar.',
            default => 'Kandidat membutuhkan latihan tambahan sebelum masuk proses wawancara Jepang lanjutan.',
        };
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-teal-700">Dashboard hasil akhir</p>
                <h1 class="mt-2 text-3xl font-extrabold text-zinc-950">Ringkasan performa kandidat</h1>
                <p class="mt-2 text-sm leading-6 text-zinc-600">Skor dan umpan balik diambil dari hasil transkripsi dan evaluasi OpenAI yang tersimpan di database.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('interview') }}" class="btn-secondary">Kembali ke tes</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Logout</button>
                </form>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="space-y-4">
                <div class="panel p-6">
                    <p class="text-sm font-semibold text-zinc-500">Skor akhir</p>
                    <p class="mt-3 text-6xl font-extrabold text-teal-700">{{ $averageScore }}</p>
                    <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $summaryText }}</p>
                </div>

                <div class="panel p-6">
                    <h2 class="text-sm font-bold text-zinc-950">Kandidat</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-zinc-500">Nama</dt>
                            <dd class="mt-1 font-bold text-zinc-950">{{ $session->candidate->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Username</dt>
                            <dd class="mt-1 font-bold text-zinc-950">{{ $session->candidate->username }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Status</dt>
                            <dd class="mt-1 font-bold capitalize text-zinc-950">{{ str_replace('_', ' ', $session->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Progress</dt>
                            <dd class="mt-1 font-bold text-zinc-950">{{ $answers->where('status', 'completed')->count() }}/10 soal</dd>
                        </div>
                    </dl>
                </div>
            </aside>

            <div class="panel overflow-hidden">
                <div class="border-b border-zinc-200 bg-white p-5">
                    <h2 class="text-lg font-bold text-zinc-950">Feedback per pertanyaan</h2>
                </div>

                @if ($answers->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-lg font-bold text-zinc-950">Belum ada jawaban tersimpan.</p>
                        <p class="mt-2 text-sm text-zinc-600">Mulai tes untuk menghasilkan dashboard evaluasi.</p>
                        <a href="{{ route('interview') }}" class="btn-primary mt-5">Mulai tes</a>
                    </div>
                @else
                    <div class="divide-y divide-zinc-200">
                        @foreach ($answers as $answer)
                            <article class="p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-zinc-500">Pertanyaan {{ $answer['questionNumber'] }}</p>
                                        <p class="font-jp mt-2 text-lg font-bold leading-relaxed text-zinc-950">{{ $answer['question'] }}</p>
                                    </div>
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-xl font-extrabold text-teal-800">
                                        {{ $answer['score'] ?? '-' }}
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-3">
                                    <div class="rounded-lg bg-zinc-50 p-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Transkrip</p>
                                        <p class="font-jp mt-2 text-sm leading-6 text-zinc-800">{{ $answer['transcript'] ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-lg bg-amber-50 p-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Catatan perbaikan</p>
                                        <p class="mt-2 text-sm leading-6 text-zinc-800">{{ $answer['feedback'] ?? $answer['errorMessage'] ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-lg bg-teal-50 p-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-teal-700">Rincian skor</p>
                                        <dl class="mt-2 space-y-1 text-sm text-zinc-800">
                                            <div class="flex justify-between gap-3">
                                                <dt>Pronunciation</dt>
                                                <dd class="font-bold">{{ $answer['pronunciationScore'] ?? '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt>Fluency</dt>
                                                <dd class="font-bold">{{ $answer['fluencyScore'] ?? '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt>Grammar</dt>
                                                <dd class="font-bold">{{ $answer['grammarScore'] ?? '-' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>

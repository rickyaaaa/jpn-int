<x-layouts.app title="Tes Wawancara - Japanese Interview AI">
    <section
        x-data="interviewPrototype({
            questions: @js($questions),
            resultsUrl: '{{ route('results') }}',
            uploadUrl: '{{ route('answers.store') }}',
            csrfToken: '{{ csrf_token() }}',
            initialAnsweredCount: @js($answeredCount)
        })"
        x-init="init()"
        class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8"
    >
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-teal-700">Sesi kandidat</p>
                <h1 class="mt-2 text-3xl font-extrabold text-zinc-950">Rekam jawaban Bahasa Jepang</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">Jawab setiap pertanyaan secara lisan. Audio akan dikirim ke backend, disimpan, lalu diproses dengan OpenAI.</p>
            </div>
            <a href="{{ route('results') }}" class="btn-secondary">Lihat hasil</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="panel overflow-hidden">
                <div class="border-b border-zinc-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-bold text-zinc-700">
                            Pertanyaan <span x-text="currentIndex + 1"></span> dari <span x-text="questions.length"></span>
                        </p>
                        <p class="rounded-md bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800" x-text="progressPercent + '%'"></p>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full bg-teal-700 transition-all duration-500" :style="`width: ${progressPercent}%`"></div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="rounded-lg border border-zinc-200 bg-stone-50 p-6">
                        <p class="text-sm font-semibold text-zinc-500">Soal Jepang</p>
                        <p class="font-jp mt-4 text-3xl font-bold leading-relaxed text-zinc-950" x-text="currentQuestion.text"></p>
                    </div>

                    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-zinc-800">Perekam suara</p>
                                <p class="mt-1 text-sm text-zinc-500" x-text="permissionState === 'granted' ? 'Mikrofon siap digunakan.' : 'Minta izin mikrofon sebelum merekam.'"></p>
                            </div>
                            <div class="flex items-center gap-2 rounded-md bg-zinc-100 px-3 py-2 font-mono text-sm font-bold text-zinc-800">
                                <span class="h-2 w-2 rounded-full" :class="recorderState === 'recording' ? 'bg-rose-600 animate-pulse' : 'bg-zinc-400'"></span>
                                <span x-text="formattedTime"></span>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                            <button class="btn-secondary" type="button" x-on:click="requestMic" :disabled="permissionState === 'granted' || permissionState === 'unsupported'">
                                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3Z" />
                                    <path d="M5 11a1 1 0 1 1 2 0 5 5 0 0 0 10 0 1 1 0 1 1 2 0 7 7 0 0 1-6 6.93V21a1 1 0 1 1-2 0v-3.07A7 7 0 0 1 5 11Z" />
                                </svg>
                                Izin mikrofon
                            </button>

                            <button x-show="recorderState !== 'recording'" class="btn-danger" type="button" x-on:click="startRecording" :disabled="processing || permissionState === 'unsupported'">
                                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="12" cy="12" r="7" />
                                </svg>
                                Rekam suara
                            </button>

                            <button x-cloak x-show="recorderState === 'recording'" class="btn-secondary" type="button" x-on:click="stopRecording">
                                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7 7h10v10H7z" />
                                </svg>
                                Stop
                            </button>

                            <button class="btn-primary sm:ml-auto" type="button" x-on:click="submitAnswer" :disabled="!recordedBlob || processing">
                                <svg x-show="!processing" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.4 20.4 21 12 3.4 3.6 3 10l10 2-10 2 .4 6.4Z" />
                                </svg>
                                <svg x-cloak x-show="processing" aria-hidden="true" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                                </svg>
                                <span x-text="processing ? 'Memproses...' : 'Kirim jawaban'"></span>
                            </button>
                        </div>

                        <div x-cloak x-show="audioUrl" class="mt-5 rounded-lg border border-teal-200 bg-teal-50 p-4">
                            <p class="mb-3 text-sm font-bold text-teal-900">Preview rekaman</p>
                            <audio class="w-full" controls :src="audioUrl"></audio>
                            <button class="mt-3 text-sm font-bold text-teal-800 hover:text-teal-950" type="button" x-on:click="resetRecording">Ulangi rekaman</button>
                        </div>

                        <p x-cloak x-show="error" x-text="error" class="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"></p>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="panel p-5">
                    <h2 class="text-sm font-bold text-zinc-950">Status sesi</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500">Terjawab</dt>
                            <dd class="font-bold text-zinc-950"><span x-text="answers.length"></span>/10</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500">Status mikrofon</dt>
                            <dd class="font-bold capitalize text-zinc-950" x-text="permissionState"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500">Mode</dt>
                            <dd class="font-bold text-zinc-950">OpenAI</dd>
                        </div>
                    </dl>
                </div>

                <div class="panel p-5">
                    <h2 class="text-sm font-bold text-zinc-950">Daftar soal</h2>
                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <template x-for="question in questions" :key="question.number">
                            <div class="flex h-10 items-center justify-center rounded-md text-sm font-bold" :class="question.number <= answers.length ? 'bg-teal-700 text-white' : question.number === currentIndex + 1 ? 'bg-amber-100 text-amber-900 ring-2 ring-amber-300' : 'bg-zinc-100 text-zinc-500'" x-text="question.number"></div>
                        </template>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>

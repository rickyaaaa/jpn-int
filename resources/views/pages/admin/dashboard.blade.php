<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Japanese Interview AI</title>
    <meta name="description" content="Kelola dan pantau kode akses kandidat Japanese Interview AI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #09090b; color: #fff; min-height: 100vh; }

        /* Subtle grid bg */
        .grid-bg {
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Glow accents */
        .top-glow {
            position: fixed; top: -200px; left: 50%; transform: translateX(-50%);
            width: 800px; height: 400px; pointer-events: none;
            background: radial-gradient(ellipse, rgba(99,102,241,0.15) 0%, transparent 70%);
            z-index: 0;
        }

        /* Cards */
        .bento-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.06);
            transition: border-color 0.2s;
        }
        .bento-card:hover { border-color: rgba(255,255,255,0.14); }

        /* Stat number */
        .stat-num {
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        /* Generate button */
        .btn-generate {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 14px 20px;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            border: none; border-radius: 10px;
            color: #052e16; font-size: 0.875rem; font-weight: 800;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(74, 222, 128, 0.3);
        }
        .btn-generate:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 6px 28px rgba(74, 222, 128, 0.4); }
        .btn-generate:active { transform: translateY(0); }

        /* Logout button */
        .btn-logout {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #a1a1aa; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .btn-logout:hover { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.25); color: #fca5a5; }

        /* Badge pill */
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;
        }
        .pill-green {
            background: rgba(74,222,128,0.12);
            border: 1px solid rgba(74,222,128,0.25);
            color: #86efac;
        }
        .pill-red {
            background: rgba(248,113,113,0.12);
            border: 1px solid rgba(248,113,113,0.25);
            color: #fca5a5;
        }
        .pill-yellow {
            background: rgba(251,191,36,0.12);
            border: 1px solid rgba(251,191,36,0.25);
            color: #fde68a;
        }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .data-table thead th {
            padding: 10px 18px;
            text-align: left;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #71717a;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .data-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.15s;
        }
        .data-table tbody tr:last-child { border-bottom: none; }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.03); }
        .data-table tbody td { padding: 13px 18px; color: #d4d4d8; vertical-align: middle; }

        /* Code chip */
        .code-chip {
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #e4e4e7;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 4px 10px;
            border-radius: 6px;
        }

        /* Flash alert */
        .flash-success {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            background: rgba(74,222,128,0.1);
            border: 1px solid rgba(74,222,128,0.25);
            border-radius: 10px;
            color: #86efac;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .flash-success .flash-code {
            font-family: 'Courier New', monospace;
            font-weight: 800;
            letter-spacing: 0.12em;
            color: #4ade80;
        }

        /* Glow dot */
        .dot-green { width:7px; height:7px; border-radius:50%; background:#4ade80; box-shadow: 0 0 6px rgba(74,222,128,0.7); }
        .dot-red   { width:7px; height:7px; border-radius:50%; background:#f87171; box-shadow: 0 0 6px rgba(248,113,113,0.7); }

        /* Animations */
        @keyframes fadein { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        .ani { animation: fadein 0.5s cubic-bezier(0.16,1,0.3,1) both; }
        .ani-d1 { animation-delay: 0.05s; }
        .ani-d2 { animation-delay: 0.1s; }
        .ani-d3 { animation-delay: 0.15s; }
        .ani-d4 { animation-delay: 0.2s; }

        /* Divider */
        .hline { height:1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.07), transparent); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.18); }
    </style>
</head>
<body class="grid-bg">
    <div class="top-glow"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- ─── Top bar ─── --}}
        <header class="flex items-center justify-between mb-8 ani">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-indigo-500/20 border border-indigo-500/30">
                    <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3Zm9 11v-1a7 7 0 0 0-14 0v1h2v-1a5 5 0 0 1 10 0v1Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-zinc-500">Admin</p>
                    <h1 class="text-lg font-black text-white leading-tight tracking-tight">Access Code Manager</h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('start') }}"
                    class="btn-logout"
                    id="candidate-link"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
                    </svg>
                    Halaman Kandidat
                </a>

                <form method="POST" action="{{ route('admin.logout') }}" id="logout-form">
                    @csrf
                    <button type="submit" class="btn-logout" id="logout-btn">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </header>

        {{-- ─── Flash message ─── --}}
        @if (session('success'))
            <div class="flash-success mb-6 ani ani-d1">
                <svg class="h-5 w-5 text-green-400 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1 14.414-3.707-3.707 1.414-1.414L11 13.586l4.293-4.293 1.414 1.414Z"/>
                </svg>
                <span>
                    Kode baru berhasil dibuat:
                    {{-- Extract code from success message --}}
                    @php
                        preg_match('/([A-Z0-9]{6})/', session('success'), $m);
                    @endphp
                    @if (!empty($m[1]))
                        <span class="flash-code">{{ $m[1] }}</span>
                    @endif
                    — siap digunakan oleh kandidat.
                </span>
            </div>
        @endif

        {{-- ─── Bento Grid ─── --}}
        <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">

            {{-- ── Left column ── --}}
            <div class="flex flex-col gap-4">

                {{-- Generate card --}}
                <div class="bento-card p-6 ani ani-d1">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="dot-green"></div>
                        <p class="text-xs font-bold uppercase tracking-widest text-zinc-400">Generator</p>
                    </div>
                    <h2 class="text-xl font-black text-white mt-2 mb-1 tracking-tight">Generate Access Code</h2>
                    <p class="text-sm text-zinc-400 leading-relaxed">
                        Setiap kode 6 karakter hanya bisa digunakan <span class="text-zinc-300 font-semibold">satu kali</span> oleh satu kandidat.
                    </p>

                    <div class="hline my-5"></div>

                    <form method="POST" action="{{ route('admin.generate-token') }}" id="gen-form">
                        @csrf
                        <button type="submit" class="btn-generate" id="gen-btn">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z"/>
                            </svg>
                            Generate New Code
                        </button>
                    </form>
                </div>

                {{-- Stats row --}}
                <div class="grid grid-cols-2 gap-4 ani ani-d2">
                    <div class="bento-card p-5">
                        <p class="text-xs font-bold uppercase tracking-widest text-zinc-500 mb-2">Total</p>
                        <p class="stat-num text-white">{{ $accessCodes->count() }}</p>
                        <p class="mt-1 text-xs text-zinc-400">Kode dibuat</p>
                    </div>
                    <div class="bento-card p-5">
                        <p class="text-xs font-bold uppercase tracking-widest text-zinc-500 mb-2">Aktif</p>
                        <p class="stat-num text-emerald-400">{{ $accessCodes->where('is_used', false)->count() }}</p>
                        <p class="mt-1 text-xs text-zinc-400">Belum dipakai</p>
                    </div>
                </div>

                {{-- Used stat --}}
                <div class="bento-card p-5 ani ani-d3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-zinc-500 mb-2">Terpakai</p>
                            <p class="stat-num text-rose-400">{{ $accessCodes->where('is_used', true)->count() }}</p>
                            <p class="mt-1 text-xs text-zinc-400">Sudah digunakan</p>
                        </div>
                        <div class="h-14 w-14 rounded-full flex items-center justify-center"
                             style="background: conic-gradient(
                                #f87171 0% {{ $accessCodes->count() > 0 ? round(($accessCodes->where('is_used', true)->count() / $accessCodes->count()) * 100) : 0 }}%,
                                rgba(255,255,255,0.06) {{ $accessCodes->count() > 0 ? round(($accessCodes->where('is_used', true)->count() / $accessCodes->count()) * 100) : 0 }}% 100%
                             ); border-radius: 50%;">
                            <div class="h-9 w-9 rounded-full bg-zinc-950 flex items-center justify-center">
                                <span class="text-xs font-black text-rose-300">
                                    {{ $accessCodes->count() > 0 ? round(($accessCodes->where('is_used', true)->count() / $accessCodes->count()) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Right column: Table ── --}}
            <div class="bento-card overflow-hidden ani ani-d2">
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.07]">
                    <h2 class="font-bold text-white text-base">Riwayat Kode Akses</h2>
                    <span class="text-xs text-zinc-500">
                        {{ $accessCodes->count() }} kode
                    </span>
                </div>

                <div class="overflow-x-auto" style="max-height: calc(100vh - 220px); overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Status</th>
                                <th>Digunakan Oleh</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accessCodes as $code)
                                <tr>
                                    <td>
                                        <span class="code-chip">{{ $code->code }}</span>
                                    </td>
                                    <td>
                                        @if ($code->is_used)
                                            <div class="flex items-center gap-2">
                                                <div class="dot-red"></div>
                                                <span class="pill pill-red">Terpakai</span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <div class="dot-green"></div>
                                                <span class="pill pill-green">Aktif</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($code->used_by_name)
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 text-xs font-bold">
                                                    {{ strtoupper(substr($code->used_by_name, 0, 1)) }}
                                                </div>
                                                <span class="text-zinc-300">{{ $code->used_by_name }}</span>
                                            </div>
                                        @else
                                            <span class="text-zinc-600">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($code->used_at)
                                            <div>
                                                <p class="text-zinc-300">{{ $code->used_at->format('d M Y') }}</p>
                                                <p class="text-xs text-zinc-500">{{ $code->used_at->format('H:i') }} WIB</p>
                                            </div>
                                        @else
                                            <span class="text-zinc-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-16">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="h-12 w-12 rounded-2xl bg-white/[0.04] border border-white/[0.08] flex items-center justify-center">
                                                <svg class="h-6 w-6 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold text-zinc-500">Belum ada kode akses</p>
                                            <p class="text-xs text-zinc-600">Generate kode baru dari panel sebelah kiri.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- end grid --}}

        {{-- ─── Candidate Results ─── --}}
        <div class="mt-6 bento-card overflow-hidden ani ani-d4">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.07]">
                <div>
                    <h2 class="font-bold text-white text-base">Hasil Kandidat</h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Sesi tes yang sudah dijalankan</p>
                </div>
                <span class="text-xs text-zinc-500">{{ $sessions->count() }} sesi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kandidat</th>
                            <th>Skor</th>
                            <th>Status</th>
                            <th>Jawaban</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            @php
                                $score = $session->total_score;
                                $scoreColor = match(true) {
                                    $score === null              => 'text-zinc-500',
                                    $score >= 90                => 'text-emerald-400',
                                    $score >= 75                => 'text-teal-400',
                                    $score >= 60                => 'text-amber-400',
                                    default                     => 'text-rose-400',
                                };
                                $statusLabel = match($session->status) {
                                    'completed'   => ['label' => 'Selesai',     'class' => 'pill-green'],
                                    'in_progress' => ['label' => 'Berlangsung', 'class' => 'pill-yellow'],
                                    default       => ['label' => $session->status, 'class' => 'pill-red'],
                                };
                            @endphp
                            <tr>
                                {{-- Kandidat --}}
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="h-7 w-7 shrink-0 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 text-xs font-bold">
                                            {{ strtoupper(substr($session->candidate->name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="text-zinc-200 font-medium">{{ $session->candidate->name ?? '—' }}</span>
                                    </div>
                                </td>

                                {{-- Skor --}}
                                <td>
                                    @if ($score !== null)
                                        <span class="font-black text-lg {{ $scoreColor }}">{{ number_format($score, 1) }}</span>
                                        <span class="text-zinc-600 text-xs">/100</span>
                                    @else
                                        <span class="text-zinc-600 text-sm">Belum ada</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="pill {{ $statusLabel['class'] }}">
                                        {{ $statusLabel['label'] }}
                                    </span>
                                </td>

                                {{-- Jawaban --}}
                                <td>
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-1.5 w-16 rounded-full bg-white/[0.08] overflow-hidden">
                                            <div class="h-full rounded-full bg-indigo-500"
                                                 style="width: {{ min(100, ($session->answers_count / 10) * 100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-zinc-400">{{ $session->answers_count }}/10</span>
                                    </div>
                                </td>

                                {{-- Mulai --}}
                                <td>
                                    <p class="text-zinc-300 text-xs">{{ $session->start_time->format('d M Y') }}</p>
                                    <p class="text-zinc-500 text-xs">{{ $session->start_time->format('H:i') }}</p>
                                </td>

                                {{-- Selesai --}}
                                <td>
                                    @if ($session->end_time)
                                        <p class="text-zinc-300 text-xs">{{ $session->end_time->format('d M Y') }}</p>
                                        <p class="text-zinc-500 text-xs">{{ $session->end_time->format('H:i') }}</p>
                                    @else
                                        <span class="text-zinc-600 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-14">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="h-12 w-12 rounded-2xl bg-white/[0.04] border border-white/[0.08] flex items-center justify-center">
                                            <svg class="h-6 w-6 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-zinc-500">Belum ada kandidat yang tes</p>
                                        <p class="text-xs text-zinc-600">Hasil akan muncul di sini setelah kandidat menyelesaikan tes.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- end container --}}

    <script>
        // Loading state on generate
        const genForm = document.getElementById('gen-form');
        const genBtn  = document.getElementById('gen-btn');
        genForm?.addEventListener('submit', () => {
            genBtn.disabled = true;
            genBtn.innerHTML = `
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                    <path d="M12 2a10 10 0 0 1 10 10"/>
                </svg>
                Generating...
            `;
        });

        // Logout loading
        const logoutForm = document.getElementById('logout-form');
        const logoutBtn  = document.getElementById('logout-btn');
        logoutForm?.addEventListener('submit', () => {
            logoutBtn.disabled = true;
            logoutBtn.textContent = 'Logging out...';
        });

        // Auto-dismiss flash after 5s
        const flash = document.querySelector('.flash-success');
        if (flash) {
            setTimeout(() => {
                flash.style.transition = 'opacity 0.5s ease';
                flash.style.opacity = '0';
                setTimeout(() => flash.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>

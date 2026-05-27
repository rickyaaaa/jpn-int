<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Japanese Interview AI</title>
    <meta name="description" content="Panel admin untuk mengelola kode akses kandidat Japanese Interview AI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .noise-bg {
            background-color: #09090b;
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(99, 102, 241, 0.18) 0%, transparent 70%),
                url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        }
        @keyframes float-in {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: float-in 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .fade-up-d1 { animation-delay: 0.08s; }
        .fade-up-d2 { animation-delay: 0.16s; }
        .fade-up-d3 { animation-delay: 0.24s; }
        .fade-up-d4 { animation-delay: 0.32s; }
        .input-field {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 12px 16px;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .input-field::placeholder { color: rgba(255,255,255,0.25); }
        .input-field:focus {
            border-color: rgba(129, 140, 248, 0.7);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
            background: rgba(255,255,255,0.07);
        }
        .btn-primary {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 13px 20px;
            background: linear-gradient(135deg, #818cf8, #6366f1);
            border: none; border-radius: 10px;
            color: #fff; font-size: 0.9rem; font-weight: 700;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 24px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 6px 32px rgba(99, 102, 241, 0.4); }
        .btn-primary:active { transform: translateY(0); }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 18px;
            backdrop-filter: blur(24px);
            box-shadow: 0 32px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.07);
        }
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 999px;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
            margin: 24px 0;
        }
        .glow-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 6px 2px rgba(74, 222, 128, 0.5);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.6; transform: scale(1.3); }
        }
    </style>
</head>
<body class="noise-bg min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-[420px]">

        {{-- Brand --}}
        <div class="text-center mb-8 fade-up">
            <div class="inline-flex items-center gap-2 mb-4">
                <div class="glow-dot"></div>
                <span class="text-xs font-bold uppercase tracking-widest text-zinc-400">Admin Portal</span>
            </div>
            <div class="text-2xl font-black text-white tracking-tight">
                Japanese Interview <span class="text-indigo-400">AI</span>
            </div>
            <a
                href="{{ route('start') }}"
                class="inline-flex items-center gap-1.5 mt-3 text-xs text-zinc-600 hover:text-zinc-400 transition-colors"
            >
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
                </svg>
                Halaman Kandidat
            </a>
        </div>

        {{-- Card --}}
        <div class="card p-8 fade-up fade-up-d1">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-white">Selamat datang</h1>
                    <p class="mt-1 text-sm text-zinc-400">Masuk untuk kelola access code kandidat.</p>
                </div>
                <span class="badge" style="background:rgba(99,102,241,0.15); color:#a5b4fc; border:1px solid rgba(99,102,241,0.25);">
                    Secure
                </span>
            </div>

            <div class="divider"></div>

            {{-- Error alert --}}
            @if ($errors->any())
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 fade-up">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/>
                    </svg>
                    <p class="text-sm font-semibold text-rose-300">{{ $errors->first() }}</p>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4" id="admin-login-form">
                @csrf

                <div class="fade-up fade-up-d2">
                    <label class="block mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400" for="email">
                        Email Address
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        placeholder="admin@ricksite.com"
                        required
                        class="input-field"
                    >
                </div>

                <div class="fade-up fade-up-d3">
                    <label class="block mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            required
                            class="input-field pr-11"
                        >
                        <button
                            type="button"
                            id="toggle-password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300 transition-colors"
                            aria-label="Toggle password visibility"
                        >
                            <svg id="eye-icon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-2 fade-up fade-up-d4">
                    <button type="submit" class="btn-primary" id="submit-btn">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z"/>
                        </svg>
                        Masuk ke Dashboard
                    </button>
                </div>
            </form>

        </div>

        {{-- Footer --}}
        <p class="text-center mt-6 text-xs text-zinc-600 fade-up fade-up-d4">
            Akses terbatas — Japanese Interview AI &copy; {{ date('Y') }}
        </p>

    </div>

    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.innerHTML = isPassword
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`
                : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
        });

        // Loading state on submit
        const form = document.getElementById('admin-login-form');
        const submitBtn = document.getElementById('submit-btn');
        form.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                    <path d="M12 2a10 10 0 0 1 10 10" />
                </svg>
                Memverifikasi...
            `;
        });
    </script>
</body>
</html>

<?php

namespace App\Http\Controllers;

use App\Models\AccessCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['nullable', 'email', 'required_without:username'],
            'username' => ['nullable', 'string', 'required_without:email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'password' => $validated['password'],
        ];

        if (! empty($validated['email'])) {
            $credentials['email'] = $validated['email'];
        } else {
            $credentials['username'] = $validated['username'];
        }

        if (! Auth::attempt($credentials, remember: false)) {
            return back()
                ->withInput($request->only('email', 'username'))
                ->withErrors(['email' => 'Email atau password admin tidak sesuai.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function dashboard(): View
    {
        return view('pages.admin.dashboard', [
            'accessCodes' => AccessCode::query()
                ->latest()
                ->get(),
            'sessions' => \App\Models\TestSession::query()
                ->with('candidate')
                ->withCount('answers')
                ->withCount([
                    'answers as completed_answers_count' => fn ($query) => $query->where('status', 'completed'),
                    'answers as processing_answers_count' => fn ($query) => $query->where('status', 'processing'),
                    'answers as failed_answers_count' => fn ($query) => $query->where('status', 'failed'),
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function generateToken(Request $request): RedirectResponse
    {
        $allowedEmails = config('auth.token_generator_emails', []);
        $adminEmail = $request->user()?->email;

        if (! $adminEmail || ! in_array($adminEmail, $allowedEmails, true)) {
            return redirect()
                ->route('admin.dashboard')
                ->withErrors(['generate_token' => 'Akun admin ini tidak diizinkan membuat kode akses.']);
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (AccessCode::query()->where('code', $code)->exists());

        AccessCode::create([
            'code' => $code,
            'is_used' => false,
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Kode akses baru berhasil dibuat: {$code}");
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Password admin berhasil diperbarui.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('start');
    }
}

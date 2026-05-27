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
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: false)) {
            return back()
                ->withInput($request->only('email'))
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
                ->latest()
                ->get(),
        ]);
    }

    public function generateToken(): RedirectResponse
    {
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

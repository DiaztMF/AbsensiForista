<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Akun ber-2FA harus melewati tantangan kode sebelum sesi diaktifkan penuh
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);

            Auth::guard('web')->logout();

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();

        // F1 & F2: Admin diarahkan ke /admin, Member ke /dashboard dengan pop-up selamat datang
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.overview'));
        }

        Inertia::flash('show_welcome_popup', true);

        return redirect()->intended(route('member.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

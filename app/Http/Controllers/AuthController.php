<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('incoming-mails.index'));
        }

        return back()->withErrors([
            'email' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.',
        ])->onlyInput('email');
    }

    /**
     * Quick login for local demonstration & testing environment.
     */
    public function quickLogin(Request $request, string $email): RedirectResponse
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->with('error', 'Pengguna tidak ditemukan.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('incoming-mails.index'))
            ->with('success', 'Berhasil masuk sebagai ' . $user->name . ' (' . ($user->getRoleNames()->first() ?? 'User') . ').');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

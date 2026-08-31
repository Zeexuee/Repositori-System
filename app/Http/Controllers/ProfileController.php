<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display user profile and staff information.
     */
    public function show(): View
    {
        $user = auth()->user();

        return view('profile.show', compact('user'));
    }

    /**
     * Update staff name.
     */
    public function updateName(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Nama profil berhasil diperbarui.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini tidak cocok dengan data kami.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Kata sandi berhasil diperbarui.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users (Direksi only).
     */
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::with('roles')->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show form to create a new user.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(Roles::all())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan dengan role ".Roles::label($validated['role']).'.');
    }

    /**
     * Show form to edit user.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    /**
     * Update user details and role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(Roles::all())],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', "Data akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Remove user account.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        Gate::authorize('delete', $user);

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "Akun pengguna {$userName} berhasil dihapus.");
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users (Direksi only).
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Manajemen User terbatas hanya untuk Direksi.');
        }

        $search = $request->query('search');
        $role = $request->query('role');

        $usersQuery = User::with('roles');

        if (!empty($search)) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($role)) {
            $usersQuery->whereHas('roles', function ($q) use ($role) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($role)]);
            });
        }

        $users = $usersQuery->latest()->paginate(15);

        return view('users.index', compact('users', 'search', 'role'));
    }

    /**
     * Show form to create a new user.
     */
    public function create(): View
    {
        if (!auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Manajemen User terbatas hanya untuk Direksi.');
        }

        return view('users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Manajemen User terbatas hanya untuk Direksi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:Staf,Direksi'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan dengan role {$validated['role']}.");
    }

    /**
     * Show form to edit user.
     */
    public function edit(User $user): View
    {
        if (!auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Manajemen User terbatas hanya untuk Direksi.');
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Update user details and role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (!auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Manajemen User terbatas hanya untuk Direksi.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:Staf,Direksi'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
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
        if (!auth()->user()->hasRole('Direksi')) {
            abort(403, 'Akses Manajemen User terbatas hanya untuk Direksi.');
        }

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "Akun pengguna {$userName} berhasil dihapus.");
    }
}

@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200/80 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna (User Management)</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola data pengguna sistem, perbarui hak akses role, serta atur akun staff & direksi.</p>
        </div>
        <div>
            <a href="{{ route('users.create') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs inline-flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Pengguna Baru</span>
            </a>
        </div>
    </div>

    <!-- Search & Role Filter Bar -->
    <form method="GET" action="{{ route('users.index') }}" class="my-6 bg-white/90 p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center space-x-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span>Pencarian & Filter Pengguna</span>
            </h2>
            @if (request()->hasAny(['search', 'role']))
                <a href="{{ route('users.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-800 underline">
                    Reset Filter
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Search Name / Email -->
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Cari Nama / Email</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama pengguna atau email..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50/60 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Filter Role -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Peran (Role)</label>
                <select name="role" class="w-full px-3 py-2 bg-slate-50/60 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 shadow-2xs">
                    <option value="">-- Semua Role --</option>
                    <option value="Staf" {{ request('role') === 'Staf' ? 'selected' : '' }}>Staf</option>
                    <option value="Direksi" {{ request('role') === 'Direksi' ? 'selected' : '' }}>Direksi</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-2">
            <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs inline-flex items-center space-x-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Cari & Filter</span>
            </button>
        </div>
    </form>

    <!-- Users Table Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Email</th>
                        <th class="py-3.5 px-4">Peran (Role)</th>
                        <th class="py-3.5 px-4">Tanggal Bergabung</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 block text-xs">{{ $user->name }}</span>
                                        @if (auth()->id() === $user->id)
                                            <span class="text-[10px] text-blue-600 font-semibold">(Akun Anda)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $user->email }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $roleName = $user->getRoleNames()->first() ?? 'Staf';
                                @endphp
                                @if ($roleName === 'Direksi')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                        Direksi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        Staf
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-medium">
                                {{ $user->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('users.edit', $user) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-all border border-slate-200">
                                        Edit
                                    </a>
                                    @if (auth()->id() !== $user->id)
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg transition-all border border-rose-200 cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-500 italic">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection

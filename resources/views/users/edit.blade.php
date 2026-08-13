@extends('layouts.app')

@section('title', 'Edit Data Pengguna')

@section('content')
    <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Edit Data Pengguna</h1>
            <p class="text-xs text-slate-500 mt-1">Perbarui rincian pengguna {{ $user->name }} dan atur peran sistem.</p>
        </div>
        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl transition-all border border-slate-200 shadow-xs">
            ← Kembali ke Daftar Users
        </a>
    </div>

    <div class="max-w-2xl mx-auto bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs">
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $user->name) }}" 
                       required 
                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Alamat Email <span class="text-rose-500">*</span>
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       value="{{ old('email', $user->email) }}" 
                       required 
                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Reset Kata Sandi Baru <span class="text-slate-400 font-normal lowercase">(kosongkan jika tidak ingin diubah)</span>
                </label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       placeholder="Biarkan kosong jika tidak diubah"
                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('password')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Peran Sistem (Role) <span class="text-rose-500">*</span>
                </label>
                @php
                    $currentRole = $user->getRoleNames()->first() ?? 'Staf';
                @endphp
                <select name="role" id="role" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    <option value="Staf" {{ old('role', $currentRole) === 'Staf' ? 'selected' : '' }}>Staf</option>
                    <option value="Direksi" {{ old('role', $currentRole) === 'Direksi' ? 'selected' : '' }}>Direksi</option>
                </select>
                @error('role')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition-all">
                    Batal
                </a>
                <button type="submit" :disabled="loading" class="px-5 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
                    <span x-show="!loading">Simpan Perubahan</span>
                    <span x-show="loading" class="flex items-center space-x-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memproses...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
@endsection

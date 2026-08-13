@extends('layouts.app')

@section('title', 'Profil Pengguna & Pengaturan Akun')

@section('content')
    <div class="pb-4 border-b border-slate-200/80 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Profil Pengguna & Informasi Staff</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola data informasi akun, perbarui nama staff, dan ganti kata sandi
                keamanan.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 1. Detail Informasi Staff Card (Main Column) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-5">
                <div class="flex items-center space-x-4 pb-4 border-b border-slate-100">
                    <div
                        class="w-14 h-14 bg-slate-900 text-white rounded-2xl flex items-center justify-center font-bold text-xl shadow-md border border-slate-700 flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-slate-900 truncate">{{ $user->name }}</h2>
                        <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                        <span
                            class="inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-900/5 text-slate-800 border border-slate-300">
                            {{ $user->getRoleNames()->first() ?? 'Staff' }}
                        </span>
                    </div>
                </div>

                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                    <span>Detail Informasi Staff</span>
                </h3>

                <div class="space-y-3.5 text-xs">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Nama
                            Lengkap</span>
                        <span class="font-semibold text-slate-900 text-sm block mt-0.5">{{ $user->name }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Alamat
                            Email</span>
                        <span class="font-semibold text-slate-900 text-sm block mt-0.5">{{ $user->email }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Peran Sistem
                            (Role)</span>
                        <span
                            class="font-semibold text-slate-900 text-sm block mt-0.5">{{ $user->getRoleNames()->first() ?? 'Staff' }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Status Akun</span>
                        <span class="inline-flex items-center space-x-1.5 mt-1 text-emerald-700 font-bold text-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Aktif (Terverifikasi)</span>
                        </span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Tanggal
                            Dibuat</span>
                        <span
                            class="font-medium text-slate-800 block mt-0.5">{{ $user->created_at?->translatedFormat('d F Y, H:i') ?? '-' }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Alamat IP
                            Terkini</span>
                        <span class="font-mono text-slate-800 block mt-0.5">{{ request()->ip() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Form Ganti Nama & 3. Form Ganti Password (Side Column) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 2 - Ganti Nama Form -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-5">
                <h2
                    class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                    <span>1. Perbarui Nama Staff</span>
                </h2>

                <form action="{{ route('profile.update-name') }}" method="POST" class="space-y-4"
                    x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap Staff <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        @error('name')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="loading"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
                            <span x-show="!loading">Simpan Perubahan Nama</span>
                            <span x-show="loading" class="flex items-center space-x-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>Memproses...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- 3 - Ganti Password Form -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-5">
                <h2
                    class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                    <span>2. Ganti Kata Sandi (Password)</span>
                </h2>

                <form action="{{ route('profile.update-password') }}" method="POST" class="space-y-4"
                    x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password"
                            class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" name="current_password" id="current_password" required
                            class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        @error('current_password')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Kata Sandi Baru (Min. 8 Karakter) <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" name="password" id="password" required
                                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                            @error('password')
                                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation"
                                class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="loading"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
                            <span x-show="!loading">Perbarui Kata Sandi</span>
                            <span x-show="loading" class="flex items-center space-x-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>Memproses...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
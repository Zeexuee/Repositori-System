<!DOCTYPE html>
<html lang="id" class="h-full" style="zoom: 90%;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk System - Corporate Secretariat Repository</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Universal 90% System Zoom for All Browsers -->
    <style>
        html {
            zoom: 90%;
            zoom: 0.9;
        }

        @supports not (zoom: 0.9) {
            html {
                -moz-transform: scale(0.9);
                -moz-transform-origin: top center;
                transform: scale(0.9);
                transform-origin: top center;
                width: 111.1111%;
                min-height: 111.1111%;
            }
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans text-slate-900 antialiased ambient-bg flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
    
    <!-- Card Utama Login Clean & Profesional -->
    <div class="max-w-md w-full glass-card p-6 sm:p-8 rounded-3xl shadow-xl border border-white/80 space-y-6">
        
        <!-- Header Branding & Logo -->
        <div class="text-center">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                Sekretariat Mercubuana
            </h1>
            <p class="mt-1 text-xs text-slate-500 font-medium">
                Selamat Datang Kembali
            </p>
        </div>

        <!-- Alert Notifications -->
        @if (session('error'))
            <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('success'))
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Form Login -->
        <form x-data="{ loading: false, showPassword: false }" @submit="loading = true" action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            
            <!-- Input Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Email Pengguna
                </label>
                <div class="relative">
                    <input id="email" name="email" type="email" autocomplete="email" required
                        value="{{ old('email', 'staf@sekretariat.corp') }}"
                        placeholder="nama@sekretariat.corp"
                        class="w-full px-4 py-2.5 bg-white/90 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-all shadow-2xs">
                </div>
                @error('email')
                    <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- Input Password dengan Toggle Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Kata Sandi
                </label>
                <div class="relative">
                    <input id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required
                        value="password"
                        placeholder="••••••••"
                        class="w-full pl-4 pr-10 py-2.5 bg-white/90 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-all shadow-2xs">
                    
                    <!-- Toggle Icon -->
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                        <template x-if="!showPassword">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </template>
                        <template x-if="showPassword">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.387c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
                            </svg>
                        </template>
                    </button>
                </div>
                @error('password')
                    <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- Opsi Remember Me -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center text-slate-700 font-medium cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 bg-white text-slate-900 focus:ring-slate-900">
                    <span class="ml-2">Ingat Saya</span>
                </label>
            </div>

            <!-- Tombol Masuk -->
            <div class="pt-1">
                <button type="submit" :disabled="loading"
                    class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 shadow-md transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center">
                    <span x-show="!loading">Masuk Ke Sistem</span>
                    <span x-show="loading" class="flex items-center justify-center" x-cloak>
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </div>
        </form>

        <!-- Section Quick Login Peran Demo (Simulasi) -->
        <div class="pt-5 border-t border-slate-200/80">
            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider text-center mb-3">
                Login Cepat Peran Demo (Simulasi)
            </span>
            <div class="grid grid-cols-2 gap-2.5">
                <!-- 1. Staf Sekretariat -->
                <form action="{{ route('quick-login', ['email' => 'staf@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-left transition-all cursor-pointer flex flex-col group shadow-2xs hover:shadow-xs">
                        <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-600 transition-colors">1. Staf</span>
                        <span class="text-[10px] text-slate-500 truncate">staf@sekretariat.corp</span>
                    </button>
                </form>

                <!-- 2. Direksi -->
                <form action="{{ route('quick-login', ['email' => 'direksi@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-left transition-all cursor-pointer flex flex-col group shadow-2xs hover:shadow-xs">
                        <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-600 transition-colors">2. Direksi</span>
                        <span class="text-[10px] text-slate-500 truncate">direksi@sekretariat.corp</span>
                    </button>
                </form>

                <!-- 3. Kepala Divisi -->
                <form action="{{ route('quick-login', ['email' => 'kadiv@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-left transition-all cursor-pointer flex flex-col group shadow-2xs hover:shadow-xs">
                        <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-600 transition-colors">3. Kepala Divisi</span>
                        <span class="text-[10px] text-slate-500 truncate">kadiv@sekretariat.corp</span>
                    </button>
                </form>

                <!-- 4. Super Admin -->
                <form action="{{ route('quick-login', ['email' => 'admin@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-left transition-all cursor-pointer flex flex-col group shadow-2xs hover:shadow-xs">
                        <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-600 transition-colors">4. Super Admin</span>
                        <span class="text-[10px] text-slate-500 truncate">admin@sekretariat.corp</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

</body>

</html>
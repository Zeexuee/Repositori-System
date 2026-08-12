<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk System - Corporate Secretariat Repository</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-100 antialiased ambient-bg flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 glass-card p-8 sm:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
        <!-- Glossy Ambient Reflection -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div>
            <div class="w-14 h-14 bg-gradient-to-tr from-slate-800 to-indigo-900 text-indigo-300 rounded-2xl font-bold text-2xl flex items-center justify-center mx-auto mb-4 shadow-lg border border-slate-700">
                CS
            </div>
            <h2 class="text-center text-2xl font-bold text-slate-100 tracking-tight">
                Sekretariat Perusahaan
            </h2>
            <p class="mt-1 text-center text-xs text-slate-400 font-medium tracking-wide">
                Corporate Secretariat Repository System
            </p>
        </div>

        @if (session('error'))
            <div class="p-3.5 bg-rose-500/10 border border-rose-500/30 text-rose-300 rounded-xl text-xs font-semibold backdrop-blur-md">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 rounded-xl text-xs font-semibold backdrop-blur-md">
                {{ session('success') }}
            </div>
        @endif

        <form x-data="{ loading: false }" @submit="loading = true" action="{{ route('login') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Email Pengguna</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="{{ old('email', 'staf@sekretariat.corp') }}"
                       class="w-full px-4 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-xs">
                @error('email')
                    <span class="text-xs text-rose-400 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       value="password"
                       class="w-full px-4 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-xs">
                @error('password')
                    <span class="text-xs text-rose-400 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-slate-300 font-medium">
                    <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2">Ingat Saya</span>
                </label>
            </div>

            <div>
                <button type="submit" :disabled="loading" 
                        class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-50">
                    <span x-show="!loading">Masuk Ke Sistem</span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </div>
        </form>

        <div class="pt-5 border-t border-slate-700/60">
            <span class="block text-xs font-bold text-slate-400 uppercase text-center mb-3 tracking-wider">Login Cepat Peran Demo (Simulasi)</span>
            <div class="grid grid-cols-2 gap-3">
                <form action="{{ route('quick-login', ['email' => 'staf@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-3.5 text-xs font-medium text-slate-200 bg-slate-900/80 hover:bg-slate-800 border border-slate-700 rounded-xl text-left flex flex-col transition-all shadow-2xs hover:shadow-xs">
                        <span class="font-bold text-indigo-300">1. Staf</span>
                        <span class="text-[10px] text-slate-400">staf@sekretariat.corp</span>
                    </button>
                </form>

                <form action="{{ route('quick-login', ['email' => 'direksi@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-3.5 text-xs font-medium text-slate-200 bg-slate-900/80 hover:bg-slate-800 border border-slate-700 rounded-xl text-left flex flex-col transition-all shadow-2xs hover:shadow-xs">
                        <span class="font-bold text-indigo-300">2. Direksi</span>
                        <span class="text-[10px] text-slate-400">direksi@sekretariat.corp</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

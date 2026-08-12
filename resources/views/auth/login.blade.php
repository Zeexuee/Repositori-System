<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk System - Corporate Secretariat Repository</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-gray-900 bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 border border-gray-200 rounded-lg shadow-sm">
        <div>
            <div class="w-12 h-12 bg-blue-900 text-white rounded font-bold text-xl flex items-center justify-center mx-auto mb-3">
                CS
            </div>
            <h2 class="text-center text-2xl font-bold text-gray-900 tracking-tight">
                Sekretariat Perusahaan
            </h2>
            <p class="mt-1 text-center text-xs text-gray-600">
                Corporate Secretariat Repository System
            </p>
        </div>

        @if (session('error'))
            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-md text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif

        <form x-data="{ loading: false }" @submit="loading = true" action="{{ route('login') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Email Pengguna</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="{{ old('email', 'staf@sekretariat.corp') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-blue-800 focus:border-blue-800">
                @error('email')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Kata Sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       value="password"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-blue-800 focus:border-blue-800">
                @error('password')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-gray-700">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-900 focus:ring-blue-800">
                    <span class="ml-2">Ingat Saya</span>
                </label>
            </div>

            <div>
                <button type="submit" :disabled="loading" 
                        class="w-full py-2.5 px-4 border border-transparent rounded-md text-sm font-semibold text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-900 disabled:opacity-50">
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

        <div class="pt-4 border-t border-gray-200">
            <span class="block text-xs font-semibold text-gray-500 uppercase text-center mb-3">Login Cepat Peran Demo (Simulasi)</span>
            <div class="grid grid-cols-2 gap-3">
                <form action="{{ route('quick-login', ['email' => 'staf@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-3 text-xs font-medium text-gray-800 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded text-left flex flex-col">
                        <span class="font-bold text-blue-900">1. Staf</span>
                        <span class="text-[10px] text-gray-500">staf@sekretariat.corp</span>
                    </button>
                </form>

                <form action="{{ route('quick-login', ['email' => 'direksi@sekretariat.corp']) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-3 text-xs font-medium text-gray-800 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded text-left flex flex-col">
                        <span class="font-bold text-blue-900">2. Direksi</span>
                        <span class="text-[10px] text-gray-500">direksi@sekretariat.corp</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

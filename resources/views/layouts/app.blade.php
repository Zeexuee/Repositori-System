<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" style="zoom: 90%;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Corporate Secretariat Repository') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
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

        /* Pada tablet dan handphone (layar <= 1024px atau perangkat layar sentuh),
           matikan CSS zoom agar koordinat viewport, sentuhan jari/stylus, dan canvas tanda tangan presisi 1:1 tanpa offset */
        @media (max-width: 1024px), (pointer: coarse), (any-pointer: coarse), (hover: none) {
            html {
                zoom: 100% !important;
                zoom: 1 !important;
                -moz-transform: none !important;
                transform: none !important;
                width: 100% !important;
                min-height: 100% !important;
            }
        }
    </style>

    <script>
        /*
         * Hitung posisi pointer di dalam canvas tanda tangan (dalam piksel canvas).
         * Hanya memakai clientX/clientY dan getBoundingClientRect() yang berada di ruang koordinat
         * yang sama, lalu diskalakan ke ukuran bitmap canvas. Tidak memakai offsetX/offsetY karena
         * nilainya berbeda antar browser mobile (terutama di dalam modal/elemen yang di-transform)
         * sehingga goresan bergeser dari ujung pena/jari.
         */
        window.getSignaturePoint = function (event, canvas) {
            if (!canvas) return { x: 0, y: 0 };

            const rect = canvas.getBoundingClientRect();
            if (!rect.width || !rect.height) return { x: 0, y: 0 };

            const source = (event.touches && event.touches[0])
                || (event.changedTouches && event.changedTouches[0])
                || event;

            if (typeof source.clientX !== 'number' || typeof source.clientY !== 'number') {
                return { x: 0, y: 0 };
            }

            // Bitmap canvas digambar di area konten (di dalam border), bukan di border-box.
            const style = window.getComputedStyle(canvas);
            const unit = canvas.offsetWidth ? rect.width / canvas.offsetWidth : 1;
            const borderLeft = (parseFloat(style.borderLeftWidth) || 0) * unit;
            const borderTop = (parseFloat(style.borderTopWidth) || 0) * unit;
            const contentWidth = rect.width - borderLeft - ((parseFloat(style.borderRightWidth) || 0) * unit);
            const contentHeight = rect.height - borderTop - ((parseFloat(style.borderBottomWidth) || 0) * unit);

            if (contentWidth <= 0 || contentHeight <= 0) return { x: 0, y: 0 };

            return {
                x: (source.clientX - rect.left - borderLeft) * (canvas.width / contentWidth),
                y: (source.clientY - rect.top - borderTop) * (canvas.height / contentHeight),
            };
        };
    </script>

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans text-slate-900 antialiased ambient-bg selection:bg-slate-900 selection:text-white">
    <div class="min-h-screen flex flex-col pb-28 sm:pb-32">
        <!-- Liquid Glass Top Header -->
        @unless(View::hasSection('hide_header'))
            <header class="sticky top-0 z-40 px-2 sm:px-6 py-2.5 sm:py-4">
                <div
                    class="{{ View::hasSection('container_width') ? View::yieldContent('container_width') : 'max-w-[98vw] w-full' }} mx-auto glass-navbar rounded-xl sm:rounded-2xl px-4 sm:px-6 py-2.5 sm:py-3.5 flex items-center justify-between shadow-lg">
                    <!-- Sisi Kiri: Title, Indikasi Online/Offline, & Informasi IP -->
                    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                        <!-- Indikator Online/Offline -->
                        <div x-data="{ isOnline: navigator.onLine }" @online.window="isOnline = true"
                            @offline.window="isOnline = false" class="flex-shrink-0">
                            <template x-if="isOnline">
                                <span
                                    class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300/70 backdrop-blur-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                                    <span>Online</span>
                                </span>
                            </template>
                            <template x-if="!isOnline">
                                <span
                                    class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300/70 backdrop-blur-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Offline</span>
                                </span>
                            </template>
                        </div>

                        <!-- Informasi IP -->
                        <span
                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-slate-100/50 text-slate-700 border border-slate-200/50 font-mono backdrop-blur-sm flex-shrink-0"
                            title="Alamat IP">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span>IP: {{ request()->ip() }}</span>
                        </span>

                        <!-- Button Beta v2 Info (Iconless, Colorless) -->
                        <button @click="$dispatch('open-beta-v2-modal')" type="button"
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300/80 transition-all cursor-pointer backdrop-blur-sm shadow-2xs flex-shrink-0">
                            Beta v2
                        </button>
                    </div>

                    <!-- Sisi Kanan: User Dropdown & Hidden Logout -->
                    <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                        @auth
                            <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                <button @click="open = !open" type="button"
                                    class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-white/40 transition-all focus:outline-none cursor-pointer backdrop-blur-md">
                                    <div class="hidden md:flex flex-col items-end">
                                        <span class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                                        <span class="text-[10px] text-slate-500">{{ auth()->user()->email }}</span>
                                    </div>
                                    <span
                                        class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-bold bg-slate-900/10 text-slate-800 border border-slate-300/50 backdrop-blur-md shadow-2xs">
                                        {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                                    </span>
                                    <svg class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown Content -->
                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95" x-cloak
                                    class="absolute right-0 mt-2 w-48 bg-white/75 backdrop-blur-xl border border-white/60 rounded-xl shadow-xl py-1.5 z-50">
                                    <div class="px-3 py-2 border-b border-slate-100/50 md:hidden">
                                        <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                    </div>
                                    <a href="{{ route('profile.show') }}"
                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-700 hover:bg-white/50 border-b border-slate-100/50 flex items-center space-x-2 transition-all">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Profil Saya</span>
                                    </a>
                                    <button type="button" @click="open = false; $dispatch('open-beta-v2-modal')"
                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 border-b border-slate-100 flex items-center transition-all cursor-pointer">
                                        <span>Pembaruan Beta v2</span>
                                    </button>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-700 hover:bg-white/50 flex items-center space-x-2 transition-all cursor-pointer">
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span>Keluar / Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}"
                                class="text-xs font-semibold text-slate-900 hover:underline">Masuk</a>
                        @endauth
                    </div>
                </div>
            </header>
        @endunless

        <!-- Main Content Container (Responsive Mobile Bounds) -->
        <main class="flex-1 px-2 sm:px-4 lg:px-6 py-3 sm:py-6 {{ View::hasSection('container_width') ? View::yieldContent('container_width') : 'max-w-[98vw] w-full' }} mx-auto">
            <!-- Flash Message: Berhasil Disimpan (Success) -->
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="mb-4 sm:mb-6 p-4 sm:p-5 bg-emerald-50 border border-emerald-300 text-emerald-950 rounded-2xl flex items-start justify-between gap-3 shadow-xs">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="space-y-0.5">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-emerald-950">Berhasil Disimpan</h3>
                            <p class="text-xs sm:text-sm text-emerald-800 font-medium leading-relaxed">{{ session('success') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="show = false" class="text-emerald-700 hover:text-emerald-950 p-1.5 rounded-lg hover:bg-emerald-100/60 transition-colors cursor-pointer" title="Tutup pemberitahuan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            <!-- Flash Message: Gagal / Error -->
            @if (session('error'))
                <div x-data="{ show: true }" x-show="show"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="mb-4 sm:mb-6 p-4 sm:p-5 bg-rose-50 border border-rose-300 text-rose-950 rounded-2xl flex items-start justify-between gap-3 shadow-xs">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="space-y-0.5">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-rose-950">Gagal Memproses Data</h3>
                            <p class="text-xs sm:text-sm text-rose-800 font-medium leading-relaxed">{{ session('error') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="show = false" class="text-rose-700 hover:text-rose-950 p-1.5 rounded-lg hover:bg-rose-100/60 transition-colors cursor-pointer" title="Tutup pemberitahuan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            <!-- Global Fallback Validation Errors -->
            @if ($errors->any() && !View::hasSection('has_custom_error_box'))
                <div x-data="{ show: true }" x-show="show"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="mb-4 sm:mb-6 p-4 sm:p-5 bg-rose-50 border border-rose-300 text-rose-950 rounded-2xl flex items-start justify-between gap-3 shadow-xs">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-rose-950">Gagal Menyimpan Perubahan</h3>
                            <p class="text-xs sm:text-sm text-rose-800 font-medium">Silakan periksa dan perbaiki input berikut:</p>
                            <ul class="list-disc list-inside space-y-0.5 text-xs sm:text-sm text-rose-700 font-medium pt-1">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" @click="show = false" class="text-rose-700 hover:text-rose-950 p-1.5 rounded-lg hover:bg-rose-100/60 transition-colors cursor-pointer" title="Tutup pemberitahuan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            <!-- Glass Card Content Wrapper -->
            <div class="glass-card rounded-2xl sm:rounded-3xl {{ View::hasSection('card_padding') ? View::yieldContent('card_padding') : 'p-3 sm:p-5 lg:p-6' }} shadow-lg">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Apple-Style Floating Bottom Dock Navigation (Mobile Optimized) -->
    @auth
        <nav class="fixed bottom-3 sm:bottom-6 left-1/2 -translate-x-1/2 z-50 max-w-[96vw] sm:max-w-none">
            <div
                class="glass-dock rounded-full px-3 py-2 sm:px-4 sm:py-2.5 flex items-center space-x-2 sm:space-x-4 shadow-xl">

                <!-- 1. Surat Masuk Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('incoming-mails.index') }}"
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('incoming-mails.*') ? 'bg-slate-900/80 hover:bg-slate-900/90 text-white shadow-md border border-slate-700/50 backdrop-blur-md' : 'bg-white/30 hover:bg-white/60 text-slate-700 hover:text-slate-900 border border-white/50 backdrop-blur-md shadow-xs' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('incoming-mails.*'))
                        <span
                            class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div
                        class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Surat Masuk
                    </div>
                </div>

                <!-- 2. Surat Keluar Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('outgoing-mails.index') }}"
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('outgoing-mails.*') ? 'bg-slate-900/80 hover:bg-slate-900/90 text-white shadow-md border border-slate-700/50 backdrop-blur-md' : 'bg-white/30 hover:bg-white/60 text-slate-700 hover:text-slate-900 border border-white/50 backdrop-blur-md shadow-xs' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('outgoing-mails.*'))
                        <span
                            class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div
                        class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Surat Keluar
                    </div>
                </div>

                <!-- 3. Repositori (Card Per Bulan) Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('repository.index') }}"
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('repository.*') ? 'bg-slate-900/80 hover:bg-slate-900/90 text-white shadow-md border border-slate-700/50 backdrop-blur-md' : 'bg-white/30 hover:bg-white/60 text-slate-700 hover:text-slate-900 border border-white/50 backdrop-blur-md shadow-xs' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v0a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('repository.*'))
                        <span
                            class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div
                        class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Repositori Arsip
                    </div>
                </div>

                <!-- 4. Jejak Audit Dock Item (Khusus Direksi) -->
                @if (auth()->user()?->hasRole(\App\Support\Roles::MASTER))
                    <div class="relative group dock-item flex-shrink-0">
                        <a href="{{ route('audit-logs.index') }}"
                            class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('audit-logs.*') ? 'bg-slate-900/80 hover:bg-slate-900/90 text-white shadow-md border border-slate-700/50 backdrop-blur-md' : 'bg-white/30 hover:bg-white/60 text-slate-700 hover:text-slate-900 border border-white/50 backdrop-blur-md shadow-xs' }}">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                </path>
                            </svg>
                        </a>
                        <!-- Active Indicator Dot -->
                        @if (request()->routeIs('audit-logs.*'))
                            <span
                                class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                        @endif
                        <!-- Tooltip -->
                        <div
                            class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                            Event Log
                        </div>
                    </div>

                    <!-- 5. Manajemen User Dock Item (Khusus Direksi) -->
                    <div class="relative group dock-item flex-shrink-0">
                        <a href="{{ route('users.index') }}"
                            class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-slate-900/80 hover:bg-slate-900/90 text-white shadow-md border border-slate-700/50 backdrop-blur-md' : 'bg-white/30 hover:bg-white/60 text-slate-700 hover:text-slate-900 border border-white/50 backdrop-blur-md shadow-xs' }}">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </a>
                        <!-- Active Indicator Dot -->
                        @if (request()->routeIs('users.*'))
                            <span
                                class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                        @endif
                        <!-- Tooltip -->
                        <div
                            class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                            Manajemen User
                        </div>
                    </div>

                    <!-- 6. Broadcast Email Dock Item (Khusus Direksi) -->
                    <div class="relative group dock-item flex-shrink-0">
                        <a href="{{ route('broadcast-emails.index') }}"
                            class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('broadcast-emails.*') ? 'bg-slate-900/80 hover:bg-slate-900/90 text-white shadow-md border border-slate-700/50 backdrop-blur-md' : 'bg-white/30 hover:bg-white/60 text-slate-700 hover:text-slate-900 border border-white/50 backdrop-blur-md shadow-xs' }}">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                        </a>
                        <!-- Active Indicator Dot -->
                        @if (request()->routeIs('broadcast-emails.*'))
                            <span
                                class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                        @endif
                        <!-- Tooltip -->
                        <div
                            class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                            Broadcast Email
                        </div>
                    </div>
                @endif

            </div>
        </nav>
    @endauth

    @stack('scripts')

    <!-- Beta v2 Announcement Modal -->
    <x-beta-v2-announcement-modal />

    <!-- Loading Spinner Component -->
    <x-loading-spinner />
    <!-- Tombol Kembali ke Atas (Floating Scroll-to-Top Button) -->
    <div x-data="{ showScrollTop: false }"
        @scroll.window="showScrollTop = (window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop) > 280"
        class="fixed bottom-6 right-4 sm:bottom-8 sm:right-8 z-50">
        <button type="button"
            x-show="showScrollTop"
            x-cloak
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-90"
            @click="window.scrollTo({ top: 0, behavior: 'smooth' }); document.querySelectorAll('.overflow-x-auto').forEach(el => el.scrollTo({ left: 0, behavior: 'smooth' }));"
            aria-label="Kembali ke atas"
            title="Kembali ke atas"
            class="group inline-flex items-center gap-2 px-3.5 py-2.5 sm:px-4 sm:py-2.5 rounded-full bg-slate-900/90 hover:bg-slate-900 text-white shadow-xl hover:shadow-2xl border border-slate-700/60 backdrop-blur-md transition-all cursor-pointer">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
            <span class="text-xs font-semibold tracking-wide hidden sm:inline">Ke Atas</span>
        </button>
    </div>
</body>

</html>

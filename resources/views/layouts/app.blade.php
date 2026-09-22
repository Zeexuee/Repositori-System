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
    </style>

    <!-- Scripts and Styles -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
            @if (session('success'))
                <div
                    class="mb-4 sm:mb-6 p-3.5 sm:p-4 glass-card border-l-4 border-l-slate-900 text-slate-900 rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mb-4 sm:mb-6 p-3.5 sm:p-4 glass-card border-l-4 border-l-slate-500 text-slate-900 rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
                    <span>{{ session('error') }}</span>
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
                @if (auth()->user()?->hasRole('Direksi'))
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

    <!-- Loading Spinner Component -->
    <x-loading-spinner />
</body>

</html>
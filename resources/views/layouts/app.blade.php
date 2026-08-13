<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Corporate Secretariat Repository') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans text-slate-900 antialiased ambient-bg selection:bg-slate-900 selection:text-white">
    <div class="min-h-screen flex flex-col pb-28 sm:pb-32">
        <!-- Liquid Glass Top Header -->
        <header class="sticky top-0 z-40 px-3 sm:px-8 py-2.5 sm:py-4">
            <div class="max-w-7xl mx-auto glass-card rounded-xl sm:rounded-2xl px-4 sm:px-6 py-2.5 sm:py-3.5 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-slate-900 rounded-xl shadow-md flex items-center justify-center text-white font-extrabold text-sm sm:text-base border border-slate-700 flex-shrink-0">
                        CS
                    </div>
                    <div class="min-w-0">
                        <h1 class="font-bold text-sm sm:text-lg text-slate-900 tracking-tight leading-tight truncate">
                            Sekretariat Perusahaan
                        </h1>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium tracking-wide truncate">
                            Corporate Secretariat Repository System
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-3 flex-shrink-0">
                    @auth
                        <div class="flex items-center space-x-2 sm:space-x-3">
                            <div class="hidden md:flex flex-col items-end">
                                <span class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                                <span class="text-[10px] text-slate-500">{{ auth()->user()->email }}</span>
                            </div>
                            <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-bold bg-slate-900/5 text-slate-800 border border-slate-300 backdrop-blur-md shadow-2xs">
                                {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                            </span>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-900 hover:underline">Masuk</a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Main Content Container (Responsive Mobile Bounds) -->
        <main class="flex-1 px-3 sm:px-6 lg:px-8 py-3 sm:py-6 max-w-7xl w-full mx-auto">
            @if (session('success'))
                <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 glass-card border-l-4 border-l-emerald-600 text-emerald-950 rounded-xl text-xs sm:text-sm font-medium flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-2.5">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 glass-card border-l-4 border-l-rose-600 text-rose-950 rounded-xl text-xs sm:text-sm font-medium flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-2.5">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Glass Card Content Wrapper -->
            <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-lg">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Apple-Style Floating Bottom Dock Navigation (Mobile Optimized) -->
    @auth
        <nav class="fixed bottom-3 sm:bottom-6 left-1/2 -translate-x-1/2 z-50 max-w-[96vw] sm:max-w-none">
            <div class="glass-dock rounded-full px-3 py-2 sm:px-4 sm:py-2.5 flex items-center space-x-2 sm:space-x-4 shadow-xl">
                
                <!-- 1. Surat Masuk Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('incoming-mails.index') }}" 
                       class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('incoming-mails.*') ? 'bg-slate-900 text-white shadow-md' : 'bg-white/80 hover:bg-white text-slate-700 hover:text-slate-900 border border-slate-200/80' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('incoming-mails.*'))
                        <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Surat Masuk
                    </div>
                </div>

                <!-- 2. Surat Keluar Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('outgoing-mails.index') }}" 
                       class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('outgoing-mails.*') ? 'bg-slate-900 text-white shadow-md' : 'bg-white/80 hover:bg-white text-slate-700 hover:text-slate-900 border border-slate-200/80' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('outgoing-mails.*'))
                        <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Surat Keluar
                    </div>
                </div>

                <!-- 3. Repositori (Card Per Bulan) Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('repository.index') }}" 
                       class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('repository.*') ? 'bg-slate-900 text-white shadow-md' : 'bg-white/80 hover:bg-white text-slate-700 hover:text-slate-900 border border-slate-200/80' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M5 8h14M5 8a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v0a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('repository.*'))
                        <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Repositori Arsip
                    </div>
                </div>

                <!-- 4. Jejak Audit Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <a href="{{ route('audit-logs.index') }}" 
                       class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center transition-all duration-200 {{ request()->routeIs('audit-logs.*') ? 'bg-slate-900 text-white shadow-md' : 'bg-white/80 hover:bg-white text-slate-700 hover:text-slate-900 border border-slate-200/80' }}">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </a>
                    <!-- Active Indicator Dot -->
                    @if (request()->routeIs('audit-logs.*'))
                        <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full shadow-xs"></span>
                    @endif
                    <!-- Tooltip -->
                    <div class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Jejak Audit
                    </div>
                </div>

                <!-- Glass Divider -->
                <div class="h-6 sm:h-8 w-px bg-slate-300/80 my-auto flex-shrink-0"></div>

                <!-- 5. Quick Logout Dock Item -->
                <div class="relative group dock-item flex-shrink-0">
                    <form action="{{ route('logout') }}" method="POST" id="logout-form" class="inline">
                        @csrf
                        <button type="submit" 
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 transition-all duration-200">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </button>
                    </form>
                    <!-- Tooltip -->
                    <div class="hidden sm:block absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 glass-tooltip bg-rose-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap">
                        Keluar
                    </div>
                </div>

            </div>
        </nav>
    @endauth

    <!-- Loading Spinner Component -->
    <x-loading-spinner />
</body>

</html>
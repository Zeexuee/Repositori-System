<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Corporate Secretariat Repository') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans text-gray-900 antialiased bg-gray-50">
    <div class="min-h-full flex flex-col">
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-9 h-9 bg-blue-900 rounded flex items-center justify-center text-white font-bold text-lg">
                            CS
                        </div>
                        <span class="font-bold text-lg text-gray-900 tracking-tight">
                            Sekretariat Perusahaan
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    @auth
                        <div class="flex items-center space-x-3 text-sm">
                            <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>
                            <span
                                class="px-2.5 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                            </span>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-rose-700 hover:text-rose-900 border border-rose-200 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-900 hover:underline">Masuk</a>
                    @endauth
                </div>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar Navigation -->
            <aside class="w-64 bg-white border-r border-gray-200 flex-shrink-0 hidden md:block">
                <nav class="p-4 space-y-1">
                    <a href="{{ route('incoming-mails.index') }}"
                        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('incoming-mails.*') ? 'bg-blue-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                        Surat Masuk
                    </a>

                    <a href="{{ route('outgoing-mails.index') }}"
                        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('outgoing-mails.*') ? 'bg-blue-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Surat Keluar
                    </a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="max-w-7xl mx-auto space-y-6">
                    @if (session('success'))
                        <div
                            class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-md text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 rounded-md text-sm font-medium">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Content Card Wrapper -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Spinner Component for Queue Indicators -->
    <x-loading-spinner />
</body>

</html>
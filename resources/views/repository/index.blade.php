@extends('layouts.app')

@section('title', 'Repositori Arsip Dokumen')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Repositori Arsip Dokumen</h1>
        </div>
        @if ($selectedMonth)
            <div class="mt-2 sm:mt-0">
                <a href="{{ route('repository.index') }}"
                    class="w-full sm:w-auto text-center inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl transition-all border border-slate-200 shadow-xs">
                    Kembali ke Semua Bulan
                </a>
            </div>
        @endif
    </div>

    @if ($activeMonthData)
        <div class="mb-8 p-4 sm:p-6 glass-card border border-slate-200/80 rounded-2xl space-y-6"
            x-data="repositoryFilter">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between pb-4 border-b border-slate-200/80 gap-4">
                <div class="flex-shrink-0">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Arsip {{ $activeMonthData['label'] }}</h2>
                    <p class="text-xs text-slate-500">Total <span x-text="totalDocs"></span> Dokumen</p>
                </div>

                <form method="GET" action="{{ route('repository.index') }}" class="flex-1 max-w-2xl w-full"
                    role="search" @submit.prevent="performSearch()">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <div class="relative w-full">
                        <input type="text" name="search" x-model="searchQuery"
                            @input.debounce.500ms="performSearch()" @keydown.escape="clearSearch()"
                            maxlength="200" autocomplete="off" enterkeyhint="search"
                            aria-label="Cari dokumen dalam arsip bulan ini" :aria-busy="isSearching"
                            placeholder="Cari nomor surat, tanggal, status, perihal, pengirim, penerima..."
                            class="w-full px-5 py-3 bg-white border-2 border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 shadow-sm transition-all pr-12 [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden">
                        <div x-show="isSearching" x-cloak class="absolute right-4 top-1/2 -translate-y-1/2"
                            aria-hidden="true">
                            <svg class="animate-spin h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <div x-show="searchQuery.length > 0" x-cloak
                        class="mt-1.5 text-xs text-slate-500 text-center lg:text-left">
                        <span x-text="totalDocs"></span> dokumen ditemukan untuk
                        "<span class="font-bold text-slate-700" x-text="searchQuery"></span>"
                        <a href="{{ route('repository.index', ['month' => $selectedMonth]) }}"
                            @click.prevent="clearSearch()"
                            class="ml-2 text-slate-700 hover:text-slate-900 font-semibold underline">
                            Reset Pencarian
                        </a>
                    </div>
                    <p x-show="searchError" x-cloak x-text="searchError" role="alert" aria-live="polite"
                        class="mt-1.5 text-xs font-medium text-red-700 text-center lg:text-left"></p>
                </form>
            </div>

            <div x-data="{ activeTab: 'all' }" class="space-y-6 pt-2" id="repository-tables-container">
                @include('repository.partials.tables')
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4 gap-5 sm:gap-6">
            @foreach ($monthsData as $key => $month)
                <div
                    class="glass-card rounded-2xl p-5 sm:p-6 border border-white/90 hover:border-slate-300 transition-all duration-300 shadow-md hover:shadow-xl group relative">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 leading-snug">{{ $month['label'] }}</h3>
                            <span class="text-xs text-slate-500 font-medium">Tahun {{ $month['year'] }}</span>
                        </div>
                        <span
                            class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-900/5 text-slate-700 border border-slate-200 flex-shrink-0">
                            {{ $month['total_count'] }} Dokumen
                        </span>
                    </div>

                    <div class="mt-5 sm:mt-6 grid grid-cols-2 gap-2.5 text-xs">
                        <div class="p-3 bg-white/80 rounded-xl border border-slate-200/70">
                            <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Surat Masuk</span>
                            <span
                                class="font-bold text-slate-900 text-sm sm:text-base mt-0.5 block">{{ $month['incoming_count'] }}</span>
                        </div>
                        <div class="p-3 bg-white/80 rounded-xl border border-slate-200/70">
                            <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Surat Keluar</span>
                            <span
                                class="font-bold text-slate-900 text-sm sm:text-base mt-0.5 block">{{ $month['outgoing_count'] }}</span>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 pt-3.5 border-t border-slate-200/70">
                        <a href="{{ route('repository.index', ['month' => $key]) }}"
                            class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl text-center block transition-all shadow-xs">
                            Buka Arsip {{ $month['label'] }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('repositoryFilter', () => ({
                searchQuery: @js(trim((string) request()->query('search', ''))),
                isSearching: false,
                searchError: '',
                searchController: null,
                totalDocs: {{ $activeMonthData['total_count'] ?? 0 }},

                async performSearch() {
                    this.searchController?.abort();

                    const controller = new AbortController();
                    const url = new URL(window.location.href);
                    const normalizedSearch = this.searchQuery.trim();

                    if (normalizedSearch !== '') {
                        url.searchParams.set('search', normalizedSearch);
                    } else {
                        url.searchParams.delete('search');
                    }
                    url.searchParams.delete('page');

                    this.searchController = controller;
                    this.isSearching = true;
                    this.searchError = '';

                    try {
                        const response = await fetch(url.toString(), {
                            signal: controller.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Permintaan pencarian gagal.');
                        }

                        const data = await response.json();
                        const container = document.getElementById('repository-tables-container');

                        if (!container || typeof data.html !== 'string') {
                            throw new Error('Respons pencarian tidak valid.');
                        }

                        container.innerHTML = data.html;
                        this.totalDocs = Number(data.total) || 0;

                        this.$nextTick(() => {
                            if (window.Alpine) {
                                window.Alpine.initTree(container);
                            }
                        });

                        window.history.replaceState({}, '', url.toString());
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            console.error('Repository search error:', error);
                            this.searchError = 'Pencarian gagal dimuat. Silakan coba lagi.';
                        }
                    } finally {
                        if (this.searchController === controller) {
                            this.searchController = null;
                            this.isSearching = false;
                        }
                    }
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.performSearch();
                },
            }));
        });
    </script>
@endpush

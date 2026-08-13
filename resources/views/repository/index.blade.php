@extends('layouts.app')

@section('title', 'Repositori Arsip Dokumen')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Repositori Arsip Dokumen</h1>
            <!-- <p class="text-xs text-slate-500 mt-1"></p> -->
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
        <!-- Detail View & Filters For Selected Month ONLY -->
        <div class="mb-8 p-4 sm:p-6 glass-card border border-slate-200/80 rounded-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200/80 gap-3">
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Arsip {{ $activeMonthData['label'] }}</h2>
                    <p class="text-xs text-slate-500">Total {{ $activeMonthData['total_count'] }} Dokumen Terarsip
                        ({{ $activeMonthData['incoming_count'] }} Surat Masuk, {{ $activeMonthData['outgoing_count'] }} Surat
                        Keluar)</p>
                </div>
            </div>

            <!-- Filter Panel ONLY shown when a month is selected (e.g. ?month=2026-08) -->
            <form method="GET" action="{{ route('repository.index') }}"
                class="bg-white/90 p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center space-x-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Pencarian & Filter Arsip Bulan Ini</span>
                    </h3>
                    @if (request()->hasAny(['search', 'sender', 'recipient']))
                        <a href="{{ route('repository.index', ['month' => $selectedMonth]) }}"
                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 underline">
                            Reset Filter Bulan Ini
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Search Name / Subject / Number -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Cari Dokumen / Subjek /
                            No.</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari nama dokumen, subjek, no..."
                                class="w-full pl-9 pr-4 py-2 bg-slate-50/60 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 shadow-2xs">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Pengirim -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pengirim</label>
                        <input type="text" name="sender" value="{{ request('sender') }}" placeholder="Ketik pengirim"
                            list="senders_list"
                            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 shadow-2xs">
                        <datalist id="senders_list">
                            @foreach ($sendersList as $s)
                                <option value="{{ $s }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Filter Penerima -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Penerima</label>
                        <input type="text" name="recipient" value="{{ request('recipient') }}" placeholder="Ketik penerima"
                            list="recipients_list"
                            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 shadow-2xs">
                        <datalist id="recipients_list">
                            @foreach ($recipientsList as $r)
                                <option value="{{ $r }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="submit"
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs inline-flex items-center space-x-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Cari & Filter</span>
                    </button>
                </div>
            </form>

            <!-- List Filter Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 pt-2">
                <!-- Surat Masuk Card List -->
                <div class="bg-white/90 rounded-xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        Surat Masuk ({{ $activeMonthData['incoming_count'] }})
                    </h3>
                    <div class="space-y-2">
                        @forelse ($activeMonthData['incoming'] as $mail)
                            <div
                                class="p-3 bg-white hover:bg-blue-50/40 rounded-lg border border-slate-200/80 transition-all flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold text-slate-900 block truncate">{{ $mail->mail_number }}</span>
                                    <p class="text-xs text-slate-700 font-medium truncate">{{ $mail->subject }}</p>
                                    <span class="text-[10px] text-slate-500 block truncate">Pengirim: {{ $mail->sender }}</span>
                                </div>
                                <a href="{{ route('incoming-mails.show', $mail) }}"
                                    class="text-xs font-semibold text-blue-700 hover:text-blue-900 px-3 py-1.5 bg-blue-50 border border-blue-200/60 rounded-lg flex-shrink-0">
                                    Detail
                                </a>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada surat masuk yang cocok.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Surat Keluar Card List -->
                <div class="bg-white/90 rounded-xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        Surat Keluar ({{ $activeMonthData['outgoing_count'] }})
                    </h3>
                    <div class="space-y-2">
                        @forelse ($activeMonthData['outgoing'] as $mail)
                            <div
                                class="p-3 bg-white hover:bg-indigo-50/40 rounded-lg border border-slate-200/80 transition-all flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span
                                        class="text-xs font-bold text-slate-900 block truncate">{{ $mail->mail_number ?? '(Draf)' }}</span>
                                    <p class="text-xs text-slate-700 font-medium truncate">{{ $mail->subject }}</p>
                                    <span class="text-[10px] text-slate-500 block truncate">Penerima: {{ $mail->recipient }}</span>
                                </div>
                                <a href="{{ route('outgoing-mails.show', $mail) }}"
                                    class="text-xs font-semibold text-indigo-700 hover:text-indigo-900 px-3 py-1.5 bg-indigo-50 border border-indigo-200/60 rounded-lg flex-shrink-0">
                                    Detail
                                </a>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada surat keluar yang cocok.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Monthly Cards Grid (Tampilan Utama Semua Bulan) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach ($monthsData as $key => $month)
                <div
                    class="glass-card rounded-2xl p-5 sm:p-6 border border-white/90 hover:border-slate-300 transition-all duration-300 shadow-md hover:shadow-xl group relative">

                    <!-- Card Header -->
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

                    <!-- Minimal Stats Boxes -->
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

                    <!-- Action Button -->
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
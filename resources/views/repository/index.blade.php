@extends('layouts.app')

@section('title', 'Repositori Arsip Dokumen')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Repositori Arsip Dokumen</h1>
            <p class="text-xs text-slate-500 mt-1">Arsip digital terstruktur berdasarkan bulan dan tahun penerbitan & penerimaan.</p>
        </div>
        @if ($selectedMonth)
            <div class="mt-2 sm:mt-0">
                <a href="{{ route('repository.index') }}" class="w-full sm:w-auto text-center inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl transition-all border border-slate-200 shadow-xs">
                    Tampilkan Semua Bulan
                </a>
            </div>
        @endif
    </div>

    @if ($activeMonthData)
        <!-- Detail View For Selected Month -->
        <div class="mb-8 p-4 sm:p-6 glass-card border border-slate-200/80 rounded-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Arsip {{ $activeMonthData['label'] }}</h2>
                    <p class="text-xs text-slate-500">Total {{ $activeMonthData['total_count'] }} Dokumen Terarsip ({{ $activeMonthData['incoming_count'] }} Surat Masuk, {{ $activeMonthData['outgoing_count'] }} Surat Keluar)</p>
                </div>
            </div>

            <!-- List Filter Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mt-6">
                <!-- Surat Masuk Card List -->
                <div class="bg-white/90 rounded-xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        Surat Masuk ({{ $activeMonthData['incoming_count'] }})
                    </h3>
                    <div class="space-y-2">
                        @forelse ($activeMonthData['incoming'] as $mail)
                            <div class="p-3 bg-white hover:bg-blue-50/40 rounded-lg border border-slate-200/80 transition-all flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold text-slate-900 block truncate">{{ $mail->mail_number }}</span>
                                    <p class="text-xs text-slate-700 font-medium truncate">{{ $mail->subject }}</p>
                                    <span class="text-[10px] text-slate-500 block truncate">Pengirim: {{ $mail->sender }}</span>
                                </div>
                                <a href="{{ route('incoming-mails.show', $mail) }}" class="text-xs font-semibold text-blue-700 hover:text-blue-900 px-3 py-1.5 bg-blue-50 border border-blue-200/60 rounded-lg flex-shrink-0">
                                    Detail
                                </a>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada surat masuk di bulan ini.</p>
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
                            <div class="p-3 bg-white hover:bg-indigo-50/40 rounded-lg border border-slate-200/80 transition-all flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold text-slate-900 block truncate">{{ $mail->mail_number ?? '(Draf)' }}</span>
                                    <p class="text-xs text-slate-700 font-medium truncate">{{ $mail->subject }}</p>
                                    <span class="text-[10px] text-slate-500 block truncate">Penerima: {{ $mail->recipient }}</span>
                                </div>
                                <a href="{{ route('outgoing-mails.show', $mail) }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900 px-3 py-1.5 bg-indigo-50 border border-indigo-200/60 rounded-lg flex-shrink-0">
                                    Detail
                                </a>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada surat keluar di bulan ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Monthly Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @foreach ($monthsData as $key => $month)
            <div class="glass-card rounded-2xl p-5 sm:p-6 border border-white/90 hover:border-slate-300 transition-all duration-300 shadow-md hover:shadow-xl group relative {{ $selectedMonth === $key ? 'ring-2 ring-blue-600 bg-blue-50/40' : '' }}">
                
                <!-- Card Header (Clean Typography) -->
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-base sm:text-lg text-slate-900 leading-snug">{{ $month['label'] }}</h3>
                        <span class="text-xs text-slate-500 font-medium">Tahun {{ $month['year'] }}</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-900/5 text-slate-700 border border-slate-200 flex-shrink-0">
                        {{ $month['total_count'] }} Dokumen
                    </span>
                </div>

                <!-- Minimal Stats Boxes -->
                <div class="mt-5 sm:mt-6 grid grid-cols-2 gap-2.5 text-xs">
                    <div class="p-3 bg-white/80 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Surat Masuk</span>
                        <span class="font-bold text-slate-900 text-sm sm:text-base mt-0.5 block">{{ $month['incoming_count'] }}</span>
                    </div>
                    <div class="p-3 bg-white/80 rounded-xl border border-slate-200/70">
                        <span class="block text-slate-500 text-[10px] font-bold uppercase tracking-wider">Surat Keluar</span>
                        <span class="font-bold text-slate-900 text-sm sm:text-base mt-0.5 block">{{ $month['outgoing_count'] }}</span>
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
@endsection

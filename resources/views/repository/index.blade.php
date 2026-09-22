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
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                        <span>Pencarian & Filter Arsip Bulan Ini</span>
                    </h3>
                    @if (request()->hasAny(['search', 'sender', 'recipient']))
                        <a href="{{ route('repository.index', ['month' => $selectedMonth]) }}"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 underline">
                            Reset Filter Bulan Ini
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Search Name / Subject / Number -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Cari Dokumen / Subjek / No.</label>
                        <div>
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full px-4 py-2 bg-slate-50/60 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 shadow-2xs">
                        </div>
                    </div>

                    <!-- Filter Pengirim -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Pengirim</label>
                        <input type="text" name="sender" value="{{ request('sender') }}"
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
                        <input type="text" name="recipient" value="{{ request('recipient') }}"
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
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                        <span>Cari & Filter</span>
                    </button>
                </div>
            </form>

            <!-- Spacious Table Views with Segmented Tabs -->
            <div x-data="{ activeTab: 'all' }" class="space-y-6 pt-2">
                <!-- Segmented Tab Navigation -->
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
                    <button type="button" @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 cursor-pointer">
                        <span>Semua Dokumen</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">{{ $activeMonthData['total_count'] }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'incoming'"
                        :class="activeTab === 'incoming' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 cursor-pointer">
                        <span>Surat Masuk</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'incoming' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">{{ $activeMonthData['incoming_count'] }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'outgoing'"
                        :class="activeTab === 'outgoing' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 cursor-pointer">
                        <span>Surat Keluar</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'outgoing' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">{{ $activeMonthData['outgoing_count'] }}</span>
                    </button>
                </div>

                <!-- Surat Masuk Table -->
                <div x-show="activeTab === 'all' || activeTab === 'incoming'" class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800">
                                Surat Masuk ({{ $activeMonthData['incoming_count'] }})
                            </h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap w-14">No.</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nomor Surat</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Masuk</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Dari</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Kepada</th>
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                                    <th class="py-4 px-4 sm:px-5 min-w-[280px]">Perihal</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Keluar</th>
                                    <th class="py-4 px-4 sm:px-5 min-w-[200px]">Disposisi</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nama Penerima</th>
                                    <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-sm">
                                @forelse ($activeMonthData['incoming'] as $index => $mail)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
                                            {{ $mail->mail_number }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
                                            {{ $mail->received_date?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 font-medium text-slate-800 whitespace-nowrap">
                                            {{ $mail->sender }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 whitespace-nowrap">
                                            {{ $mail->recipient ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
                                            @php
                                                $badgeClasses = match ($mail->status) {
                                                    'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 font-bold',
                                                    'RETURN', 'RETURNED' => 'bg-white text-slate-800 border-slate-300 font-bold',
                                                    'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 font-bold',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                };
                                                $displayStatus = match ($mail->status) {
                                                    'RECEIVED' => 'RECEIVE',
                                                    'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                                    'RETURNED' => 'RETURN',
                                                    default => $mail->status,
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                                {{ $displayStatus }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-800 min-w-[280px] max-w-xl break-words leading-relaxed" title="{{ $mail->subject }}">
                                            {{ $mail->subject }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-600 font-mono whitespace-nowrap">
                                            {{ $mail->outgoing_date?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-600 min-w-[200px] max-w-md break-words leading-relaxed" title="{{ $mail->disposition_note }}">
                                            {{ $mail->disposition_note ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 font-medium whitespace-nowrap">
                                            {{ $mail->recipient_name ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">
                                            <a href="{{ route('incoming-mails.show', $mail) }}"
                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="p-8 text-center text-slate-500 italic text-sm">
                                            Tidak ada data surat masuk yang cocok.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Surat Keluar Table -->
                <div x-show="activeTab === 'all' || activeTab === 'outgoing'" class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800">
                                Surat Keluar ({{ $activeMonthData['outgoing_count'] }})
                            </h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap w-14">No.</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nomor Surat</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Dibuat</th>
                                    <th class="py-4 px-4 sm:px-5 min-w-[320px]">Subjek / Perihal</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Penerima</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Pembuat</th>
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                                    <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-sm">
                                @forelse ($activeMonthData['outgoing'] as $index => $mail)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
                                            {{ $mail->mail_number ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
                                            {{ $mail->created_at?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-800 font-medium min-w-[320px] max-w-2xl break-words leading-relaxed" title="{{ $mail->subject }}">
                                            {{ $mail->subject }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 font-medium whitespace-nowrap">{{ $mail->recipient }}</td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-600 font-medium whitespace-nowrap">{{ $mail->creator?->name ?? 'System' }}</td>
                                        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
                                            @php
                                                $badgeClasses = match ($mail->status) {
                                                    'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 font-bold',
                                                    'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 font-bold',
                                                    'RETURN', 'RETURNED' => 'bg-white text-slate-800 border-slate-300 font-bold',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                };
                                                $displayStatus = match ($mail->status) {
                                                    'RECEIVED' => 'RECEIVE',
                                                    'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                                    'RETURNED' => 'RETURN',
                                                    default => $mail->status,
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                                {{ $displayStatus }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">
                                            <a href="{{ route('outgoing-mails.show', $mail) }}"
                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-slate-500 italic text-sm">
                                            Tidak ada data surat keluar yang cocok.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Monthly Cards Grid (Tampilan Utama Semua Bulan) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4 gap-5 sm:gap-6">
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
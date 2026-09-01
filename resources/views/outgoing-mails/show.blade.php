@extends('layouts.app')

@section('title', 'Detail Surat Keluar')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Detail Surat Keluar</h1>
            <p class="text-xs text-slate-500 mt-0.5 font-mono">ID: {{ $outgoingMail->id }}</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <a href="{{ route('outgoing-mails.index') }}" class="w-full sm:w-auto text-center px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs transition-all">
                Kembali
            </a>

            @can('update', $outgoingMail)
                <a href="{{ route('outgoing-mails.edit', $outgoingMail) }}" class="w-full sm:w-auto text-center px-4 py-2 text-xs font-semibold text-slate-800 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 shadow-2xs transition-all">
                    Edit
                </a>
            @endcan
            @can('delete', $outgoingMail)
                <form action="{{ route('outgoing-mails.destroy', $outgoingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus draf/surat keluar ini?');" class="w-full sm:w-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-xs transition-all cursor-pointer">
                        Hapus
                    </button>
                </form>
            @endcan
        </div>
    </div>

    @php
        $recipientsList = array_filter(array_map('trim', explode(',', (string) $outgoingMail->recipient)));
    @endphp

    <!-- Structured Info Grid with Defined Card Boundaries -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        
        <!-- Field 1: Nomor Surat -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nomor Surat</span>
            <span class="font-bold text-slate-900 text-sm sm:text-base block break-words font-mono">{{ $outgoingMail->mail_number ?? '(Draf Belum Diberi Nomor)' }}</span>
        </div>

        <!-- Field 2: Pembuat Dokumen -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pembuat Dokumen</span>
            <span class="font-bold text-slate-900 text-sm sm:text-base block break-words">{{ $outgoingMail->creator?->name ?? 'System' }}</span>
        </div>

        <!-- Field 3: Penerima / Disposisi -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-2">
            <div class="flex items-center justify-between">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Penerima Surat / Disposisi</span>
                @if (count($recipientsList) > 1)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-mono">{{ count($recipientsList) }} Penerima</span>
                @endif
            </div>
            @if (count($recipientsList) > 1)
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach ($recipientsList as $r)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-800 shadow-2xs">
                            {{ $r }}
                        </span>
                    @endforeach
                </div>
            @else
                <span class="font-semibold text-slate-800 text-sm block break-words">{{ $outgoingMail->recipient ?? '-' }}</span>
            @endif
        </div>

        <!-- Field 4: Status -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1" x-data="{ open: false, selectedStatus: '{{ $outgoingMail->status }}' }">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status Dokumen</span>
            @php
                $statusBadgeClasses = match ($outgoingMail->status) {
                    'RETURN', 'RETURNED' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
                    'APPROVED', 'SIGNED' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
                    'RECEIVE', 'RECEIVED' => 'bg-sky-50 text-sky-700 border-sky-300 font-bold',
                    'REVISI', 'REVISION' => 'bg-amber-50 text-amber-900 border-amber-300 font-bold',
                    'WAITING' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
                    'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-amber-50 text-amber-700 border-amber-300 font-bold',
                    'REJECTED', 'SIGN_FAILED' => 'bg-rose-50 text-rose-700 border-rose-300 font-bold',
                    'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-300 font-medium',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $availableStatuses = [
                    'WAITING' => 'Menunggu Penerima',
                    'PROGRES' => 'Dalam Proses',
                    'RETURN' => 'Selesai / Dikembalikan',
                    'REVISI' => 'Revisi Dokumen',
                    'DRAFT' => 'Draf Dokumen',
                ];
            @endphp
            @can('update', $outgoingMail)
                <div class="relative inline-block text-left pt-0.5">
                    <form x-ref="statusForm" action="{{ route('outgoing-mails.update', $outgoingMail) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" :value="selectedStatus">
                    </form>

                    <button type="button" @click="open = !open"
                        title="Klik untuk memilih dan mengubah status"
                        class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold border transition-all cursor-pointer shadow-2xs hover:ring-2 hover:ring-slate-900/10 {{ $statusBadgeClasses }}">
                        <span>{{ $outgoingMail->status }}</span>
                        <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open"
                        x-cloak
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 mt-1.5 w-44 rounded-xl bg-white shadow-xl border border-slate-200 py-1 z-30 focus:outline-hidden">
                        <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            Pilih Status
                        </div>
                        @foreach ($availableStatuses as $statusCode => $statusDesc)
                            <button type="button"
                                @click="if('{{ $statusCode }}' !== '{{ $outgoingMail->status }}') { if(confirm('Ubah status dokumen surat menjadi {{ $statusCode }}?')) { selectedStatus = '{{ $statusCode }}'; $nextTick(() => $refs.statusForm.submit()); } } open = false;"
                                class="w-full text-left px-3 py-1.5 text-xs hover:bg-slate-50 flex items-center justify-between transition-colors {{ $outgoingMail->status === $statusCode ? 'bg-slate-50 font-bold' : '' }}">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-block w-2 h-2 rounded-full {{ str_starts_with($statusCode, 'RETURN') ? 'bg-emerald-500' : (in_array($statusCode, ['WAITING', 'PROGRES']) ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                                    <span class="text-slate-800">{{ $statusCode }}</span>
                                </div>
                                @if($outgoingMail->status === $statusCode)
                                    <span class="text-[10px] text-slate-400 font-normal">Aktif</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadgeClasses }}">
                    {{ $outgoingMail->status }}
                </span>
            @endcan
        </div>

        <!-- Field 5: Subjek (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Subjek / Perihal</span>
            <p class="font-medium text-slate-800 text-sm sm:text-base leading-relaxed break-words">{{ $outgoingMail->subject }}</p>
        </div>

        <!-- Field 6: Berkas Lampiran (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-2">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Berkas Lampiran Utama</span>
            @if ($outgoingMail->file_path)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-600 font-mono break-all">{{ $outgoingMail->file_path }}</span>
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <a href="{{ route('document.download', ['path' => $outgoingMail->file_path, 'inline' => 1]) }}" target="_blank"
                           class="w-full sm:w-auto text-center px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold rounded-xl transition-all shadow-2xs">
                            Pratinjau
                        </a>
                        <a href="{{ route('document.download', ['path' => $outgoingMail->file_path]) }}" 
                           class="w-full sm:w-auto text-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex-shrink-0">
                            Unduh Dokumen
                        </a>
                    </div>
                </div>
            @else
                <span class="text-slate-400 italic text-xs block">Tidak ada lampiran berkas.</span>
            @endif
        </div>

        <!-- Field 7: Daftar Disposisi & Tanda Tangan Penerima -->
        @php
            $dispositions = $outgoingMail->dispositions_data ?? [];
        @endphp
        @if (!empty($dispositions) && count($dispositions) > 0)
            <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        Status Tanda Tangan Unit Disposisi ({{ count($dispositions) }})
                    </span>
                    @if ($outgoingMail->status === 'WAITING')
                        <a href="{{ route('outgoing-mails.waiting') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900 underline">
                            Buka Ruang Waiting
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($dispositions as $dIndex => $disp)
                        <div class="p-3.5 bg-white rounded-xl border border-slate-200 flex flex-col justify-between space-y-2 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold bg-slate-900 text-white">
                                        {{ $dIndex + 1 }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-800">{{ $disp['name'] ?? 'Unit Disposisi' }}</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($disp['status'] ?? 'WAITING') === 'SIGNED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-300' : 'bg-amber-50 text-amber-800 border border-amber-300' }}">
                                    {{ ($disp['status'] ?? 'WAITING') === 'SIGNED' ? 'Ditandatangani' : 'Menunggu Penerima' }}
                                </span>
                            </div>

                            @if (!empty($disp['signature_path']))
                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <img src="{{ route('document.download', ['path' => $disp['signature_path'], 'inline' => 1]) }}" alt="Tanda Tangan" class="h-12 max-w-[140px] object-contain bg-slate-50 border border-slate-100 rounded-lg p-1">
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $disp['signed_at'] ?? '' }}</span>
                                </div>
                            @elseif (($disp['status'] ?? 'WAITING') === 'WAITING')
                                <div class="pt-1 text-[11px] text-slate-500">
                                    Menunggu tanda tangan penerima di ruang Waiting
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Field 8: Histori Perubahan Berkas -->
        @if ($outgoingMail->fileHistories->count() > 0)
            <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-3">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Histori Perubahan Berkas ({{ $outgoingMail->fileHistories->count() }})
                </span>
                <div class="space-y-2">
                    @foreach ($outgoingMail->fileHistories as $history)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 bg-white rounded-lg border border-slate-200 gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-bold text-slate-800 font-mono break-all">{{ $history->file_name ?? basename($history->file_path) }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium border border-slate-200">Versi Terganti</span>
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    Diganti pada <span class="font-semibold text-slate-700">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                                    @if ($history->uploader)
                                        oleh <span class="font-semibold text-slate-700">{{ $history->uploader->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center space-x-2 self-end sm:self-center">
                                <a href="{{ route('document.download', ['path' => $history->file_path, 'inline' => 1]) }}" target="_blank" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold rounded-lg shadow-2xs transition-all">
                                    Pratinjau
                                </a>
                                <a href="{{ route('document.download', ['path' => $history->file_path]) }}" class="px-3 py-1.5 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold rounded-lg shadow-xs transition-all">
                                    Unduh
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
@endsection

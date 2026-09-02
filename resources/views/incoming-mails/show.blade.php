@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')
<div x-data="showRevisionModalHandler()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-3">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Detail Surat Masuk</h1>
                @if ($incomingMail->revision_count > 0 || $incomingMail->status === 'REVISI')
                    <span class="px-2.5 py-1 text-xs font-bold bg-amber-50 text-amber-900 border border-amber-300 rounded-lg font-mono">
                        R [{{ $incomingMail->revision_count ?: 1 }}]
                    </span>
                @endif
                @if($incomingMail->status === 'DRAFT')
                    <span class="px-2.5 py-1 text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300 rounded-lg">
                        DRAFT
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-0.5 font-mono">
                No: {{ $incomingMail->mail_number }}
                @if($incomingMail->receipt_number)
                    <span class="ml-2 px-2 py-0.5 bg-slate-100 text-slate-700 font-semibold rounded border border-slate-200">
                        Ref Tanda Terima: {{ $incomingMail->receipt_number }}
                    </span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('incoming-mails.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs transition-all">
                Kembali
            </a>
            @can('update', $incomingMail)
                @if ($incomingMail->status === 'REVISI')
                    <button type="button" @click="openModal()" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 shadow-xs transition-all cursor-pointer">
                        Unggah Dokumen & TTD Revisi
                    </button>
                @endif
                <a href="{{ route('incoming-mails.edit', $incomingMail) }}" class="px-4 py-2 text-xs font-semibold text-slate-800 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 shadow-2xs transition-all">
                    Edit Dokumen
                </a>
            @endcan
            @can('delete', $incomingMail)
                <form action="{{ route('incoming-mails.destroy', $incomingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat masuk ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-xs transition-all cursor-pointer">
                        Hapus Dokumen
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Info Grid -->
    <div class="mt-6 space-y-6">
        
        <!-- Section 1: Data Utama -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
                Data Utama Surat Masuk
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nomor Surat</span>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-slate-900 text-sm block font-mono">{{ $incomingMail->mail_number }}</span>
                        @if ($incomingMail->revision_count > 0 || $incomingMail->status === 'REVISI')
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300 font-mono">
                                R [{{ $incomingMail->revision_count ?: 1 }}]
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Masuk</span>
                    <span class="font-bold text-slate-900 text-sm block font-mono">{{ $incomingMail->received_date?->format('d F Y') ?? '-' }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Surat</span>
                    <span class="font-semibold text-slate-800 text-sm block font-mono">{{ $incomingMail->outgoing_date?->format('d F Y') ?? '-' }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dari (Biro Pengirim)</span>
                    <span class="font-bold text-slate-900 text-sm block break-words">{{ $incomingMail->sender }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kepada</span>
                    <span class="font-semibold text-slate-800 text-sm block break-words">{{ $incomingMail->recipient ?? '-' }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1" x-data="{
                    open: false,
                    selectedStatus: '{{ $incomingMail->status }}',
                    dropdownPos: { top: '0px', left: '0px' },
                    toggleDropdown(el) {
                        if (this.open) {
                            this.open = false;
                            return;
                        }
                        const rect = el.getBoundingClientRect();
                        const dropdownHeight = 220;
                        const spaceBelow = window.innerHeight - rect.bottom;
                        
                        let top = rect.bottom + 6;
                        if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
                            top = rect.top - dropdownHeight;
                        }
                        let left = Math.max(10, Math.min(rect.left, window.innerWidth - 200));
                        this.dropdownPos = { top: top + 'px', left: left + 'px' };
                        this.open = true;
                    }
                }" @scroll.window.passive="open = false">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Surat</span>
                    @php
                        $statusBadgeClasses = match ($incomingMail->status) {
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
                            'RECEIVE' => 'Diterima',
                            'PROGRES' => 'Dalam Proses',
                            'RETURN' => 'Selesai / Dikembalikan',
                            'REVISI' => 'Revisi Dokumen',
                            'DRAFT' => 'Draft Dokumen',
                        ];
                    @endphp
                    @can('update', $incomingMail)
                        <div class="relative inline-block text-left pt-0.5">
                            <form x-ref="statusForm" action="{{ route('incoming-mails.update', $incomingMail) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" :value="selectedStatus">
                            </form>

                            <button type="button" @click="toggleDropdown($el)"
                                title="Klik untuk memilih dan mengubah status"
                                class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold border transition-all cursor-pointer shadow-2xs hover:ring-2 hover:ring-slate-900/10 {{ $statusBadgeClasses }}">
                                <span>{{ $incomingMail->status }}</span>
                                <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu Teleported to Body -->
                            <template x-teleport="body">
                                <div x-show="open"
                                    x-cloak
                                    @click.away="open = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    :style="{ top: dropdownPos.top, left: dropdownPos.left }"
                                    class="fixed w-48 rounded-xl bg-white shadow-2xl border border-slate-200 py-1 z-[99999] focus:outline-hidden">
                                    <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                        Pilih Status
                                    </div>
                                    @foreach ($availableStatuses as $statusCode => $statusDesc)
                                        <button type="button"
                                            @click="if('{{ $statusCode }}' !== '{{ $incomingMail->status }}') { if(confirm('Ubah status surat masuk menjadi {{ $statusCode }}?')) { selectedStatus = '{{ $statusCode }}'; $nextTick(() => $refs.statusForm.submit()); } } open = false;"
                                            class="w-full text-left px-3 py-1.5 text-xs hover:bg-slate-50 flex items-center justify-between transition-colors {{ $incomingMail->status === $statusCode ? 'bg-slate-50 font-bold' : '' }}">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-block w-2 h-2 rounded-full {{ str_starts_with($statusCode, 'RETURN') ? 'bg-emerald-500' : (str_starts_with($statusCode, 'RECEIVE') ? 'bg-sky-500' : ($statusCode === 'REVISI' ? 'bg-amber-600' : (in_array($statusCode, ['PROGRES', 'WAITING']) ? 'bg-amber-500' : 'bg-slate-400'))) }}"></span>
                                                <span class="text-slate-800">{{ $statusCode }}</span>
                                            </div>
                                            @if($incomingMail->status === $statusCode)
                                                <span class="text-[10px] text-slate-400 font-normal">Aktif</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </template>
                        </div>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadgeClasses }}">
                            {{ $incomingMail->status }}
                        </span>
                    @endcan
                </div>
            </div>

            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Perihal</span>
                <p class="font-semibold text-slate-900 text-sm leading-relaxed">{{ $incomingMail->subject }}</p>
            </div>
        </div>

        <!-- Section Riwayat Perubahan Revisi (Jika ada riwayat revisi atau status REVISI) -->
        @if ($incomingMail->revisions && $incomingMail->revisions->count() > 0)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-2">
                        <span>Riwayat Perubahan Dokumen (Revisi)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300 font-mono">
                            {{ $incomingMail->revisions->count() }} Kali Revisi
                        </span>
                    </h2>
                </div>

                <div class="space-y-3">
                    @foreach ($incomingMail->revisions as $rev)
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-xs font-bold font-mono">
                                        R [{{ $rev->revision_number }}]
                                    </span>
                                    <span class="text-xs font-bold text-slate-800">
                                        Perubahan Status: <span class="font-mono text-slate-500">{{ $rev->previous_status ?? '-' }}</span> → <span class="font-mono text-amber-800 font-bold">{{ $rev->new_status }}</span>
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    <span>{{ $rev->created_at->format('d/m/Y H:i') }}</span>
                                    @if ($rev->user)
                                        <span> oleh <strong class="text-slate-700">{{ $rev->user->name }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            @if ($rev->notes)
                                <div class="p-2.5 bg-white rounded-lg border border-slate-200/80 text-xs text-slate-700">
                                    <strong class="text-slate-900 block text-[11px] uppercase tracking-wider mb-0.5">Catatan Revisi:</strong>
                                    {{ $rev->notes }}
                                </div>
                            @endif

                            @php
                                $prevPhoto = $rev->previous_document_photo_path ?? ($rev->changed_fields['document_photo_path']['old'] ?? null);
                                $currPhoto = $rev->document_photo_path ?? ($rev->changed_fields['document_photo_path']['new'] ?? null);
                                $prevFile = $rev->previous_file_path ?? ($rev->changed_fields['file_path']['old'] ?? null);
                                $currFile = $rev->file_path ?? ($rev->changed_fields['file_path']['new'] ?? null);
                                $prevSig = $rev->previous_signature_path ?? ($rev->changed_fields['receipt_signature_path']['old'] ?? null);
                                $currSig = $rev->signature_path ?? ($rev->changed_fields['receipt_signature_path']['new'] ?? null);
                                $hasDocComparison = ($prevPhoto && $prevPhoto !== 'None') || ($currPhoto && $currPhoto !== 'None') || ($prevFile && $prevFile !== 'None') || ($currFile && $currFile !== 'None');
                                $hasSigComparison = ($prevSig && $prevSig !== 'None') || ($currSig && $currSig !== 'None');
                            @endphp

                            <!-- Komparasi Berkas / Dokumen Sebelum vs Sesudah Revisi -->
                            @if ($hasDocComparison)
                                <div class="pt-2">
                                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Berkas / Foto Dokumen Sebelum & Sesudah Revisi:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Dokumen Sebelum Revisi -->
                                        <div class="p-3 bg-white rounded-xl border border-slate-200/80 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Dokumen Sebelum Revisi</span>
                                                <span class="px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded text-[10px] font-semibold">Versi Lama</span>
                                            </div>
                                            @if ($prevPhoto && $prevPhoto !== 'None')
                                                <div class="bg-slate-50 p-1.5 border border-slate-200 rounded-lg flex items-center justify-center">
                                                    <img src="{{ route('document.download', ['path' => $prevPhoto, 'inline' => 1]) }}" alt="Foto Sebelum Revisi" class="max-h-36 object-contain rounded">
                                                </div>
                                                <a href="{{ route('document.download', ['path' => $prevPhoto]) }}" class="text-[11px] font-semibold text-slate-900 hover:underline block text-center">
                                                    Unduh Foto Lama
                                                </a>
                                            @elseif ($prevFile && $prevFile !== 'None')
                                                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between">
                                                    <span class="text-[11px] font-mono text-slate-600 truncate max-w-[160px]">{{ basename($prevFile) }}</span>
                                                    <a href="{{ route('document.download', ['path' => $prevFile]) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[10px] font-bold rounded border border-slate-200">
                                                        Unduh PDF Lama
                                                    </a>
                                                </div>
                                            @else
                                                <p class="text-[11px] text-slate-400 italic py-2">Tidak ada arsip berkas lama.</p>
                                            @endif
                                        </div>

                                        <!-- Dokumen Hasil Revisi -->
                                        <div class="p-3 bg-white rounded-xl border border-slate-200/80 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider">Dokumen Hasil Revisi</span>
                                                <span class="px-1.5 py-0.2 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded text-[10px] font-bold">Hasil Revisi</span>
                                            </div>
                                            @if ($currPhoto && $currPhoto !== 'None')
                                                <div class="bg-slate-50 p-1.5 border border-slate-200 rounded-lg flex items-center justify-center">
                                                    <img src="{{ route('document.download', ['path' => $currPhoto, 'inline' => 1]) }}" alt="Foto Hasil Revisi" class="max-h-36 object-contain rounded">
                                                </div>
                                                <a href="{{ route('document.download', ['path' => $currPhoto]) }}" class="text-[11px] font-semibold text-slate-900 hover:underline block text-center">
                                                    Unduh Foto Baru
                                                </a>
                                            @elseif ($currFile && $currFile !== 'None')
                                                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between">
                                                    <span class="text-[11px] font-mono text-slate-600 truncate max-w-[160px]">{{ basename($currFile) }}</span>
                                                    <a href="{{ route('document.download', ['path' => $currFile]) }}" class="px-2 py-1 bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold rounded">
                                                        Unduh PDF Baru
                                                    </a>
                                                </div>
                                            @else
                                                <p class="text-[11px] text-slate-400 italic py-2">Tidak ada berkas baru diunggah.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Komparasi Tanda Tangan Sebelum vs Sesudah Revisi -->
                            @if ($hasSigComparison)
                                <div class="pt-2">
                                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Tanda Tangan Sebelum & Sesudah Revisi:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Tanda Tangan Sebelum Revisi -->
                                        <div class="p-3 bg-white rounded-xl border border-slate-200/80 space-y-1.5">
                                            <span class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">Tanda Tangan Sebelumnya</span>
                                            @if ($prevSig && $prevSig !== 'None')
                                                <div class="bg-slate-50 p-2 border border-slate-200 rounded-lg flex items-center justify-center h-24">
                                                    <img src="{{ route('document.download', ['path' => $prevSig, 'inline' => 1]) }}" alt="TTD Sebelumnya" class="max-h-20 object-contain">
                                                </div>
                                            @else
                                                <p class="text-[11px] text-slate-400 italic py-2">Belum ada tanda tangan sebelumnya.</p>
                                            @endif
                                        </div>

                                        <!-- Tanda Tangan Setelah Revisi -->
                                        <div class="p-3 bg-white rounded-xl border border-slate-200/80 space-y-1.5">
                                            <span class="block text-[11px] font-bold text-slate-900 uppercase tracking-wider">Tanda Tangan Pengantar Revisi</span>
                                            @if ($currSig && $currSig !== 'None')
                                                <div class="bg-slate-50 p-2 border border-slate-200 rounded-lg flex items-center justify-center h-24">
                                                    <img src="{{ route('document.download', ['path' => $currSig, 'inline' => 1]) }}" alt="TTD Revisi" class="max-h-20 object-contain">
                                                </div>
                                            @else
                                                <p class="text-[11px] text-slate-400 italic py-2">Tidak ada tanda tangan baru.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if (!empty($rev->changed_fields) && is_array($rev->changed_fields))
                                <div class="pt-1">
                                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rincian Perubahan Field:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                        @foreach ($rev->changed_fields as $field => $change)
                                            @if (!in_array($field, ['file_path', 'document_photo_path', 'receipt_signature_path'], true))
                                                <div class="p-2 bg-white rounded-lg border border-slate-200/60 font-mono text-[11px]">
                                                    <span class="font-bold text-slate-800 block capitalize mb-0.5">{{ str_replace('_', ' ', $field) }}</span>
                                                    <div class="text-slate-500 truncate" title="Nilai Lama">Lama: {{ is_array($change['old'] ?? '') ? json_encode($change['old']) : ($change['old'] ?? '-') }}</div>
                                                    <div class="text-slate-900 font-semibold truncate" title="Nilai Baru">Baru: {{ is_array($change['new'] ?? '') ? json_encode($change['new']) : ($change['new'] ?? '-') }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Section Batch Items (Dokumen Lain dalam Tanda Terima Kolektif Ini) -->
        @if($incomingMail->batch_id && $incomingMail->batchItems && $incomingMail->batchItems->count() > 1)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Dokumen Lain dalam Tanda Terima Ini ({{ $incomingMail->batchItems->count() }} Berkas)
                    </h2>
                    <span class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                        {{ $incomingMail->receipt_number }}
                    </span>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($incomingMail->batchItems as $item)
                        <div class="py-3 flex items-center justify-between hover:bg-slate-50 px-2 rounded-lg transition-colors">
                            <div class="space-y-0.5">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs font-bold text-slate-900">{{ $item->mail_number }}</span>
                                    @if ($item->revision_count > 0 || $item->status === 'REVISI')
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300 font-mono">
                                            R [{{ $item->revision_count ?: 1 }}]
                                        </span>
                                    @endif
                                    @if($item->id === $incomingMail->id)
                                        <span class="text-[10px] bg-slate-900 text-white px-1.5 py-0.2 rounded font-semibold">(Dokumen Ini)</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-600 truncate max-w-lg">{{ $item->subject }}</p>
                            </div>
                            @if($item->id !== $incomingMail->id)
                                <a href="{{ route('incoming-mails.show', $item) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 text-xs font-semibold rounded-lg transition-all">
                                    Lihat Dokumen
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Section 2: Disposisi & Penerima -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
                Disposisi & Penerima Berkas
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Disposisi</span>
                    <p class="text-xs text-slate-800 whitespace-pre-line leading-relaxed">{{ $incomingMail->disposition_note ?? '-' }}</p>
                </div>

                <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keterangan</span>
                    <p class="text-xs text-slate-800 whitespace-pre-line leading-relaxed">{{ $incomingMail->notes ?? '-' }}</p>
                </div>
            </div>

            <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Penerima Berkas</span>
                <span class="font-bold text-slate-900 text-sm block">{{ $incomingMail->recipient_name ?? '-' }}</span>
            </div>
        </div>

        <!-- Section 3: Lampiran & Tanda Terima -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
                Foto Dokumen & Tanda Terima
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Foto Dokumen -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Foto Dokumen</span>
                        @if ($incomingMail->document_photo_path)
                            <a href="{{ route('document.download', ['path' => $incomingMail->document_photo_path]) }}" class="text-xs font-semibold text-slate-900 hover:underline">
                                Unduh
                            </a>
                        @endif
                    </div>

                    @if ($incomingMail->document_photo_path)
                        <div class="bg-white p-2 border border-slate-200 rounded-lg overflow-hidden flex items-center justify-center">
                            <img src="{{ route('document.download', ['path' => $incomingMail->document_photo_path, 'inline' => 1]) }}" alt="Foto Dokumen" class="max-h-56 object-contain rounded">
                        </div>
                    @elseif ($incomingMail->file_path)
                        <div class="bg-white p-3 border border-slate-200 rounded-lg flex items-center justify-between text-xs">
                            <span class="font-mono text-slate-600 truncate">{{ $incomingMail->file_path }}</span>
                            <a href="{{ route('document.download', ['path' => $incomingMail->file_path]) }}" class="px-3 py-1 bg-slate-900 text-white rounded-lg font-semibold text-[11px]">
                                Unduh PDF
                            </a>
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">Tidak ada foto/berkas terunggah.</p>
                    @endif
                </div>

                <!-- Tanda Terima -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Tanda Terima</span>
                        @if ($incomingMail->receipt_signature_path)
                            <a href="{{ route('document.download', ['path' => $incomingMail->receipt_signature_path]) }}" class="text-xs font-semibold text-slate-900 hover:underline">
                                Unduh
                            </a>
                        @endif
                    </div>

                    @if ($incomingMail->receipt_signature_path)
                        <div class="bg-white p-3 border border-slate-200 rounded-lg flex items-center justify-center min-h-[120px]">
                            <img src="{{ route('document.download', ['path' => $incomingMail->receipt_signature_path, 'inline' => 1]) }}" alt="Tanda Terima" class="max-h-40 object-contain">
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">Tidak ada tanda tangan terdaftar.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Form Dokumen & Tanda Tangan Revisi -->
    <template x-teleport="body">
        <div x-show="showModal"
            x-cloak
            class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 sm:p-6"
            style="position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
            
            <!-- Backdrop Overlay with Full Blur -->
            <div x-show="showModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeModal()"
                class="fixed inset-0 bg-slate-950/75 backdrop-blur-md transition-all"></div>

            <!-- Modal Container -->
            <div x-show="showModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                @click.away="closeModal()"
                class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5 z-10">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-slate-900">Form Revisi Dokumen</h3>
                            <span class="px-2 py-0.5 bg-amber-50 text-amber-900 border border-amber-300 text-xs font-bold font-mono rounded-md">
                                R [{{ ($incomingMail->revision_count ?? 0) + 1 }}]
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 font-mono">No. Surat: <strong class="text-slate-800">{{ $incomingMail->mail_number }}</strong></p>
                    </div>
                    <button type="button" @click="closeModal()" class="px-2.5 py-1 text-slate-500 hover:text-slate-800 text-xs font-bold rounded-lg hover:bg-slate-100 transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>

                <!-- Form Upload & Signature -->
                <form action="{{ route('incoming-mails.submit-revision', $incomingMail) }}" method="POST" enctype="multipart/form-data" @submit="submitRevisionForm($event)" class="space-y-4">
                    @csrf
                    <input type="hidden" name="signature_base64" :value="signatureBase64">

                    <!-- Upload Berkas Revisi Baru -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Upload Berkas Dokumen Revisi (PDF / Gambar)
                        </label>
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition-all shadow-2xs">
                        <p class="text-[11px] text-slate-400">Pilih berkas baru yang sudah diperbaiki untuk menggantikan berkas lama.</p>
                    </div>

                    <!-- Tanda Tangan Canvas -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Tanda Tangan Pengantar / Penerima Revisi
                            </label>
                            <span class="text-[11px] font-semibold"
                                :class="hasSignature ? 'text-emerald-700' : 'text-slate-400'"
                                x-text="hasSignature ? 'Tanda tangan terisi' : 'Goreskan di bawah'"></span>
                        </div>

                        <div class="border border-slate-300 rounded-2xl overflow-hidden bg-white shadow-2xs relative">
                            <canvas x-ref="showRevisiCanvas"
                                width="500"
                                height="150"
                                @mousedown="onStart($event)"
                                @mousemove="onMove($event)"
                                @mouseup="onEnd($event)"
                                @mouseleave="onEnd($event)"
                                @touchstart.prevent="onStart($event)"
                                @touchmove.prevent="onMove($event)"
                                @touchend.prevent="onEnd($event)"
                                class="w-full h-36 touch-none cursor-crosshair block bg-white"></canvas>

                            <div class="absolute bottom-2 right-2 flex items-center space-x-2">
                                <button type="button" @click="clearCanvas()" class="px-2.5 py-1 bg-white/90 hover:bg-white text-slate-600 text-[11px] font-semibold rounded-lg border border-slate-200 shadow-2xs transition-all cursor-pointer">
                                    Hapus Goresan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Revisi -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Catatan Revisi (Opsional)
                        </label>
                        <textarea name="revision_notes" rows="2" placeholder="Catatan perubahan yang telah dilakukan pada revisi ini..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-all shadow-2xs"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2.5">
                        <button type="button" @click="closeModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer flex items-center space-x-1.5">
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan & Selesaikan Revisi'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function showRevisionModalHandler() {
        return {
            showModal: false,
            signatureBase64: '',
            hasSignature: false,
            isDrawing: false,
            isSubmitting: false,
            canvasCtx: null,

            openModal() {
                this.showModal = true;
                this.signatureBase64 = '';
                this.hasSignature = false;
                this.isSubmitting = false;

                this.$nextTick(() => {
                    this.initCanvas();
                });
            },

            closeModal() {
                this.showModal = false;
            },

            initCanvas() {
                const canvas = this.$refs.showRevisiCanvas;
                if (!canvas) return;

                const rect = canvas.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;

                this.canvasCtx = canvas.getContext('2d');
                this.canvasCtx.scale(dpr, dpr);
                this.canvasCtx.strokeStyle = '#0f172a';
                this.canvasCtx.lineWidth = 2.5;
                this.canvasCtx.lineCap = 'round';
                this.canvasCtx.lineJoin = 'round';

                this.clearCanvas();
            },

            getCoordinates(e) {
                const canvas = this.$refs.showRevisiCanvas;
                const rect = canvas.getBoundingClientRect();
                if (e.touches && e.touches.length > 0) {
                    return {
                        x: e.touches[0].clientX - rect.left,
                        y: e.touches[0].clientY - rect.top
                    };
                }
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                };
            },

            onStart(e) {
                this.isDrawing = true;
                const pos = this.getCoordinates(e);
                this.canvasCtx.beginPath();
                this.canvasCtx.moveTo(pos.x, pos.y);
            },

            onMove(e) {
                if (!this.isDrawing) return;
                const pos = this.getCoordinates(e);
                this.canvasCtx.lineTo(pos.x, pos.y);
                this.canvasCtx.stroke();
                this.hasSignature = true;
            },

            onEnd(e) {
                if (!this.isDrawing) return;
                this.isDrawing = false;
                this.canvasCtx.closePath();
                this.captureSignature();
            },

            clearCanvas() {
                const canvas = this.$refs.showRevisiCanvas;
                if (!canvas || !this.canvasCtx) return;
                const rect = canvas.getBoundingClientRect();
                this.canvasCtx.clearRect(0, 0, rect.width, rect.height);
                this.signatureBase64 = '';
                this.hasSignature = false;
            },

            captureSignature() {
                const canvas = this.$refs.showRevisiCanvas;
                if (!canvas) return;
                this.signatureBase64 = canvas.toDataURL('image/png');
            },

            submitRevisionForm(e) {
                if (this.hasSignature) {
                    this.captureSignature();
                }
                this.isSubmitting = true;
            }
        };
    }
</script>
@endsection

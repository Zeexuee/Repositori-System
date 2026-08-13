@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Detail Surat Masuk</h1>
            <p class="text-xs text-slate-500 mt-0.5 font-mono">{{ $incomingMail->mail_number }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('incoming-mails.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs transition-all">
                Kembali
            </a>
            @can('update', $incomingMail)
                <a href="{{ route('incoming-mails.edit', $incomingMail) }}" class="px-4 py-2 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 shadow-2xs transition-all">
                    Edit Surat
                </a>
            @endcan
            @can('delete', $incomingMail)
                <form action="{{ route('incoming-mails.destroy', $incomingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat masuk ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-xs transition-all">
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
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                <span>Data Utama Surat Masuk</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nomor Surat</span>
                    <span class="font-bold text-slate-900 text-sm block font-mono">{{ $incomingMail->mail_number }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Masuk</span>
                    <span class="font-bold text-slate-900 text-sm block font-mono">{{ $incomingMail->received_date?->format('d F Y') ?? '-' }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Keluar</span>
                    <span class="font-semibold text-slate-800 text-sm block font-mono">{{ $incomingMail->outgoing_date?->format('d F Y') ?? '-' }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dari</span>
                    <span class="font-semibold text-slate-800 text-sm block break-words">{{ $incomingMail->sender }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kepada</span>
                    <span class="font-semibold text-slate-800 text-sm block break-words">{{ $incomingMail->recipient ?? '-' }}</span>
                </div>

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Surat</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-white shadow-2xs">
                        {{ $incomingMail->status }}
                    </span>
                </div>
            </div>

            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Perihal</span>
                <p class="font-semibold text-slate-900 text-sm leading-relaxed">{{ $incomingMail->subject }}</p>
            </div>
        </div>

        <!-- Section 2: Disposisi & Penerima -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                <span>Disposisi & Penerima Berkas</span>
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
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                <span>Foto Dokumen & Tanda Terima</span>
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
@endsection

@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Detail Surat Masuk</h1>
            <p class="text-xs text-slate-500 mt-0.5 font-mono">ID: {{ $incomingMail->id }}</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <a href="{{ route('incoming-mails.index') }}" class="w-full sm:w-auto text-center px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs transition-all">
                Kembali
            </a>
            @can('delete', $incomingMail)
                <form action="{{ route('incoming-mails.destroy', $incomingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat masuk ini?');" class="w-full sm:w-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-xs transition-all">
                        Hapus Dokumen
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Structured Info Grid with Defined Card Boundaries -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        
        <!-- Field 1: Nomor Surat -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nomor Surat</span>
            <span class="font-bold text-slate-900 text-sm sm:text-base block break-words">{{ $incomingMail->mail_number }}</span>
        </div>

        <!-- Field 2: Tanggal Diterima -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Diterima</span>
            <span class="font-bold text-slate-900 text-sm sm:text-base block">{{ $incomingMail->received_date?->format('d M Y') }}</span>
        </div>

        <!-- Field 3: Pengirim -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pengirim Surat</span>
            <span class="font-semibold text-slate-800 text-sm block break-words">{{ $incomingMail->sender }}</span>
        </div>

        <!-- Field 4: Status -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status Dokumen</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-800 border border-slate-300">
                {{ $incomingMail->status }}
            </span>
        </div>

        <!-- Field 5: Subjek (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Subjek / Perihal</span>
            <p class="font-medium text-slate-800 text-sm sm:text-base leading-relaxed break-words">{{ $incomingMail->subject }}</p>
        </div>

        <!-- Field 6: Berkas Lampiran (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-2">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Berkas Lampiran</span>
            @if ($incomingMail->file_path)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-600 font-mono break-all">{{ $incomingMail->file_path }}</span>
                    <a href="{{ route('document.download', ['path' => $incomingMail->file_path]) }}" 
                       class="w-full sm:w-auto text-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex-shrink-0">
                        Unduh Dokumen
                    </a>
                </div>
            @else
                <span class="text-slate-400 italic text-xs block">Tidak ada lampiran berkas.</span>
            @endif
        </div>

    </div>
@endsection

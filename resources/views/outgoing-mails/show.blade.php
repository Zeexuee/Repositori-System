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
            @can('delete', $outgoingMail)
                <form action="{{ route('outgoing-mails.destroy', $outgoingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus draf/surat keluar ini?');" class="w-full sm:w-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 shadow-xs transition-all cursor-pointer">
                        Hapus Dokumen
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Structured Info Grid with Defined Card Boundaries -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        
        <!-- Row 1: Nomor Surat, Waktu Dibuat, Status Dokumen (3 Columns) -->
        <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
            <!-- Field 1: Nomor Surat -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nomor Surat</span>
                <span class="font-bold text-slate-900 text-sm sm:text-base block break-words">{{ $outgoingMail->mail_number ?? '(Draf Belum Diberi Nomor)' }}</span>
            </div>

            <!-- Field 2: Tanggal Dibuat -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Dibuat</span>
                <span class="font-bold text-slate-900 text-sm sm:text-base block font-mono">{{ $outgoingMail->created_at?->format('d F Y, H:i') ?? '-' }}</span>
            </div>

            <!-- Field 3: Status -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status Dokumen</span>
                @php
                    $canonicalStatus = App\Support\OutgoingMailStatus::canonical($outgoingMail->status);
                    $hasReturnProgress = $outgoingMail->returnRecipients && $outgoingMail->returnRecipients->isNotEmpty();
                    if ($canonicalStatus === App\Support\OutgoingMailStatus::WAITING_RECEIVE && ! $hasReturnProgress) {
                        $canonicalStatus = App\Support\OutgoingMailStatus::READY_TO_DELIVERY;
                    }
                    $statusBadge = match ($canonicalStatus) {
                        App\Support\OutgoingMailStatus::REVISI => 'bg-amber-50 text-amber-800 border-amber-300',
                        App\Support\OutgoingMailStatus::READY_TO_DELIVERY => 'bg-white text-slate-700 border-slate-300',
                        App\Support\OutgoingMailStatus::WAITING_RECEIVE => 'bg-blue-50 text-blue-800 border-blue-300',
                        App\Support\OutgoingMailStatus::RECEIVE => 'bg-slate-900 text-white border-slate-900',
                        default => 'bg-slate-100 text-slate-800 border-slate-300',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                    {{ App\Support\OutgoingMailStatus::label($canonicalStatus) }}
                </span>
            </div>
        </div>

        <!-- Row 2: Pembuat Dokumen & Penerima Surat (2 Columns) -->
        <!-- Field 4: Pembuat Dokumen -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pembuat Dokumen</span>
            <span class="font-bold text-slate-900 text-sm sm:text-base block break-words">{{ $outgoingMail->creator?->name ?? 'System' }}</span>
        </div>

        <!-- Field 5: Penerima -->
        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Penerima Surat</span>
            <span class="font-semibold text-slate-800 text-sm block break-words">{{ $outgoingMail->recipient }}</span>
        </div>

        <!-- Field 5: Subjek (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Subjek / Perihal</span>
            <p class="font-medium text-slate-800 text-sm sm:text-base leading-relaxed break-words">{{ $outgoingMail->subject }}</p>
        </div>

        <!-- Field 6: Catatan Disposisi (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
            <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Catatan Disposisi</span>
            @if (!empty($outgoingMail->disposition_note))
                <p class="font-medium text-slate-800 text-sm sm:text-base leading-relaxed break-words whitespace-pre-line">{{ $outgoingMail->disposition_note }}</p>
            @else
                <span class="text-slate-400 italic text-xs block">Tidak ada catatan disposisi.</span>
            @endif
        </div>

        <!-- Section: Riwayat Revisi -->
        @if ($outgoingMail->revisionNotes->isNotEmpty())
            <section class="sm:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4" aria-labelledby="revision-notes-heading">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h2 id="revision-notes-heading" class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                            <span>Riwayat Revisi Dokumen</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $outgoingMail->revisionNotes->count() }} catatan tercatat, urutan terbaru di atas.
                        </p>
                    </div>
                    @if ($canonicalStatus === App\Support\OutgoingMailStatus::REVISI)
                        <span class="inline-flex self-start items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                            Sedang dalam revisi
                        </span>
                    @endif
                </div>

                <ul class="space-y-3">
                    @foreach ($outgoingMail->revisionNotes as $revisionNote)
                        <li class="p-4 rounded-xl border {{ $revisionNote->isRequest() ? 'bg-amber-50/60 border-amber-200' : 'bg-emerald-50/60 border-emerald-200' }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider {{ $revisionNote->isRequest() ? 'text-amber-800' : 'text-emerald-800' }}">
                                    {{ $revisionNote->isRequest() ? 'Permintaan Revisi' : 'Revisi Diselesaikan' }}
                                </span>
                                <span class="text-[11px] font-mono text-slate-500">
                                    {{ $revisionNote->created_at?->format('d/m/Y H:i') }}
                                    · {{ $revisionNote->creator?->name ?? 'Sistem' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-800 whitespace-pre-line leading-relaxed">
                                {{ $revisionNote->note ?? 'Tanpa catatan.' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <!-- Field 7: Berkas Lampiran (Full Width on Grid) -->
        <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Berkas Dokumen & Lampiran
                    @if ($outgoingMail->attachments->count() > 0)
                        <span class="text-slate-400 font-normal">({{ $outgoingMail->attachments->count() }} berkas)</span>
                    @endif
                </span>
            </div>

            @if ($outgoingMail->attachments->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($outgoingMail->attachments as $att)
                        <div class="bg-white p-3 border border-slate-200 rounded-xl space-y-2 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2 min-w-0 pr-2">
                                    @php
                                        $isImg = str_starts_with($att->mime_type ?? '', 'image/');
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isImg ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $isImg ? 'FOTO' : 'PDF' }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-800 truncate" title="{{ $att->file_name ?? basename($att->file_path) }}">
                                        {{ $att->file_name ?? basename($att->file_path) }}
                                    </span>
                                    @if ($att->file_size)
                                        <span class="text-[10px] text-slate-400 font-mono">
                                            ({{ number_format($att->file_size / 1024, 1) }} KB)
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-1.5 flex-shrink-0">
                                    <a href="{{ route('document.download', ['path' => $att->file_path, 'inline' => 1]) }}" target="_blank" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-[11px] font-semibold transition-all">
                                        Pratinjau
                                    </a>
                                    <a href="{{ route('document.download', ['path' => $att->file_path]) }}" class="px-2.5 py-1 bg-slate-900 text-white hover:bg-slate-800 rounded-lg text-[11px] font-bold transition-all">
                                        Unduh
                                    </a>
                                </div>
                            </div>

                            @if ($isImg)
                                <div class="mt-2 bg-slate-50 p-1.5 border border-slate-100 rounded-lg flex items-center justify-center">
                                    <img src="{{ route('document.download', ['path' => $att->file_path, 'inline' => 1]) }}" alt="{{ $att->file_name }}" class="max-h-40 object-contain rounded">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @elseif ($outgoingMail->file_path)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-600 font-mono break-all">{{ basename($outgoingMail->file_path) }}</span>
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <a href="{{ route('document.download', ['path' => $outgoingMail->file_path, 'inline' => 1]) }}" target="_blank"
                           class="w-full sm:w-auto text-center px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold rounded-xl transition-all shadow-2xs">
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

        <!-- Field 7: Histori Perubahan Berkas -->
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

        <!-- Field 9: Penerima Dokumen & Tanda Tangan (Jika Ada) -->
        @if ($outgoingMail->returnRecipients->isNotEmpty())
            @php
                $signedCount = $outgoingMail->returnRecipients->filter(fn ($r) => $r->signed_at !== null || $r->signature_path !== null)->count();
                $totalRecipients = $outgoingMail->returnRecipients->count();
            @endphp
            <div class="sm:col-span-2 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            Penerima Dokumen & Tanda Tangan
                        </span>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $signedCount }} dari {{ $totalRecipients }} penerima telah membubuhkan tanda tangan.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full min-w-[600px] text-left text-xs sm:text-sm">
                        <thead class="bg-slate-100 text-[11px] uppercase tracking-wider text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">No.</th>
                                <th scope="col" class="px-4 py-2.5">Penerima / Unit</th>
                                <th scope="col" class="px-4 py-2.5">Nama Penandatangan</th>
                                <th scope="col" class="px-4 py-2.5">Waktu Tanda Tangan</th>
                                <th scope="col" class="px-4 py-2.5 text-right">Bukti Tanda Tangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach ($outgoingMail->returnRecipients as $index => $recipient)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $recipient->recipient_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $recipient->signer_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                                        {{ $recipient->signed_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($recipient->signature_path)
                                            <a href="{{ route('document.download', ['path' => $recipient->signature_path, 'inline' => 1]) }}" target="_blank"
                                                class="inline-flex px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-800 border border-slate-200 shadow-2xs">
                                                Lihat Tanda Tangan
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Belum tanda tangan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
@endsection

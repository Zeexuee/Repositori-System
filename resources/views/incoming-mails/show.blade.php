@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-3">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Detail Surat Masuk</h1>
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
            @can('delete', $incomingMail)
                <form action="{{ route('incoming-mails.destroy', $incomingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat masuk ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 shadow-xs transition-all cursor-pointer">
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

                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Surat</span>
                    @php
                        $canonicalStatus = App\Support\IncomingMailStatus::canonical($incomingMail->status);
                        $statusBadge = match ($canonicalStatus) {
                            App\Support\IncomingMailStatus::REVISI => 'bg-amber-50 text-amber-800 border-amber-300',
                            App\Support\IncomingMailStatus::WAITING_RETURN => 'bg-blue-50 text-blue-800 border-blue-300',
                            App\Support\IncomingMailStatus::RETURN => 'bg-white text-slate-800 border-slate-300',
                            App\Support\IncomingMailStatus::DRAFT => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                            App\Support\IncomingMailStatus::DISPOSISI => 'bg-slate-200 text-slate-900 border-slate-400',
                            default => 'bg-slate-900 text-white border-slate-900',
                        };
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                        {{ App\Support\IncomingMailStatus::label($incomingMail->status) }}
                    </span>
                </div>
            </div>

            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-1">
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Perihal</span>
                <p class="font-semibold text-slate-900 text-sm leading-relaxed">{{ $incomingMail->subject }}</p>
            </div>
        </div>

        <!-- Section Batch Items (Dokumen Lain dalam Tanda Terima Kolektif Ini) -->
        @if($incomingMail->batch_id && $incomingMail->batchItems && $incomingMail->batchItems->count() > 1)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Dokumen Lain Dalam Tanda Terima Kolektif Ini (Biro: {{ $incomingMail->sender }})</span>
                    </h2>
                    <span class="text-xs font-bold px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md border border-slate-200">
                        Total: {{ $incomingMail->batchItems->count() }} Dokumen
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($incomingMail->batchItems as $item)
                        <div class="py-3 flex items-center justify-between hover:bg-slate-50 px-2 rounded-lg transition-colors">
                            <div class="space-y-0.5">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs font-bold text-slate-900">{{ $item->mail_number }}</span>
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

        @if ($incomingMail->revisionNotes->isNotEmpty())
            <section class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4" aria-labelledby="revision-notes-heading">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h2 id="revision-notes-heading" class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Riwayat Revisi
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $incomingMail->revisionNotes->count() }} catatan tercatat, terbaru di atas.
                        </p>
                    </div>
                    @if ($canonicalStatus === App\Support\IncomingMailStatus::REVISI)
                        <span class="inline-flex self-start items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                            Sedang dalam revisi
                        </span>
                    @endif
                </div>

                <ul class="space-y-3">
                    @foreach ($incomingMail->revisionNotes as $revisionNote)
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

        @if ($incomingMail->returnRecipients->isNotEmpty())
            @php
                $signedReturnRecipients = $incomingMail->returnRecipients->filter(fn ($recipient) => $recipient->signed_at !== null)->count();
                $totalReturnRecipients = $incomingMail->returnRecipients->count();
            @endphp
            <section class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4" aria-labelledby="return-recipients-heading">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h2 id="return-recipients-heading" class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Pengembalian Dokumen
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $signedReturnRecipients }} dari {{ $totalReturnRecipients }} penerima telah menandatangani.
                        </p>
                    </div>
                    <span class="inline-flex self-start items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                        {{ App\Support\IncomingMailStatus::label($incomingMail->status) }}
                    </span>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <caption class="sr-only">Daftar penerima dan penandatangan pengembalian dokumen</caption>
                        <thead class="bg-slate-100 text-[11px] uppercase tracking-wider text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3">No.</th>
                                <th scope="col" class="px-4 py-3">Penerima / Unit</th>
                                <th scope="col" class="px-4 py-3">Penandatangan</th>
                                <th scope="col" class="px-4 py-3">Waktu Tanda Tangan</th>
                                <th scope="col" class="px-4 py-3 text-right">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach ($incomingMail->returnRecipients as $index => $returnRecipient)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $returnRecipient->recipient_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $returnRecipient->signer_name ?? 'Belum diisi' }}</td>
                                    <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                                        {{ $returnRecipient->signed_at?->format('d/m/Y H:i') ?? 'Belum ditandatangani' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($returnRecipient->signature_path)
                                            <a href="{{ route('document.download', ['path' => $returnRecipient->signature_path, 'inline' => 1]) }}" target="_blank"
                                                class="inline-flex px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-800 border border-slate-200">
                                                Lihat Tanda Tangan
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Belum ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($canonicalStatus === App\Support\IncomingMailStatus::WAITING_RETURN && auth()->user()?->can('update', $incomingMail))
                    <div class="flex justify-end">
                        <a href="{{ route('incoming-mails.index') }}" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 rounded-xl text-xs font-bold">
                            Kembali dan Lanjutkan melalui Status
                        </a>
                    </div>
                @endif
            </section>
        @endif

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

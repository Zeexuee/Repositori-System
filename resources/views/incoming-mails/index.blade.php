@extends('layouts.app')

@section('title', 'Daftar Surat Masuk')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Masuk</h1>
        </div>
        @can('create', App\Models\IncomingMail::class)
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                <a href="{{ route('incoming-mails.index', ['status_filter' => request('status_filter') === 'draft' ? 'all' : 'draft']) }}"
                    class="inline-flex items-center space-x-2 px-4 py-2.5 {{ request('status_filter') === 'draft' ? 'bg-amber-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300' }} text-xs font-semibold rounded-xl transition-all shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ request('status_filter') === 'draft' ? 'Lihat Semua Surat' : 'Draft Surat Masuk' }}</span>
                    @if(!empty($draftCount) && $draftCount > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ request('status_filter') === 'draft' ? 'bg-white text-amber-900' : 'bg-amber-100 text-amber-800' }}">{{ $draftCount }}</span>
                    @endif
                </a>

                <a href="{{ route('incoming-mails.create') }}"
                    class="inline-flex items-center space-x-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Surat Masuk</span>
                </a>
            </div>
        @endcan
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr
                        class="border-b border-slate-200 bg-slate-100/90 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <th class="p-3.5 text-center">No.</th>
                        <th class="p-3.5">Nomor Surat</th>
                        <th class="p-3.5">Tanggal Masuk</th>
                        <th class="p-3.5">Dari</th>
                        <th class="p-3.5">Kepada</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Perihal</th>
                        <th class="p-3.5">Tanggal Keluar</th>
                        <th class="p-3.5">Disposisi</th>
                        <th class="p-3.5">Nama Penerima</th>
                        <th class="p-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @forelse ($incomingMails as $index => $mail)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $mail->status === 'DRAFT' ? 'bg-amber-50/40' : '' }}">
                            <td class="p-3.5 text-center font-bold text-slate-500">
                                {{ ($incomingMails->firstItem() ?? 1) + $index }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900 font-mono">
                                {{ $mail->mail_number }}
                            </td>
                            <td class="p-3.5 text-slate-700 font-mono">
                                {{ $mail->received_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="p-3.5 font-medium text-slate-800">
                                {{ $mail->sender }}
                            </td>
                            <td class="p-3.5 text-slate-700">
                                {{ $mail->recipient ?? '-' }}
                            </td>
                            <td class="p-3.5">
                                @php
                                    $isReceive = in_array($mail->status, ['RECEIVE', 'RECEIVED']);
                                    $badgeClasses = match ($mail->status) {
                                        'DRAFT' => 'bg-amber-100 text-amber-900 border-amber-300 font-bold',
                                        'RECEIVE', 'RECEIVED' => 'bg-sky-50 text-sky-700 border-sky-300 font-bold hover:bg-sky-100 cursor-pointer shadow-2xs transition-all',
                                        'RETURN', 'RETURNED' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                                        'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                    $displayStatus = match ($mail->status) {
                                        'RECEIVED' => 'RECEIVE',
                                        'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                        'RETURNED' => 'RETURN',
                                        default => $mail->status,
                                    };
                                @endphp

                                @if ($isReceive && auth()->user()?->can('update', $mail))
                                    <form action="{{ route('incoming-mails.update', $mail) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin merubah status dokumen {{ $mail->mail_number }} dari RECEIVE menjadi RETURN?\n\nCatatan: Dokumen akan otomatis dicatat pada Surat Keluar (RETURN).');">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="RETURN">
                                        <button type="submit" title="Klik untuk langsung merubah status menjadi RETURN" class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClasses }}">
                                            <span>{{ $displayStatus }}</span>
                                            <span class="text-[10px] text-sky-500 font-bold">↻</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClasses }}">
                                        {{ $displayStatus }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-800 max-w-xs truncate" title="{{ $mail->subject }}">
                                {{ $mail->subject }}
                            </td>
                            <td class="p-3.5 text-slate-600 font-mono">
                                {{ $mail->outgoing_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="p-3.5 text-slate-600 max-w-xs truncate" title="{{ $mail->disposition_note }}">
                                {{ $mail->disposition_note ? Str::limit($mail->disposition_note, 25) : '-' }}
                            </td>
                            <td class="p-3.5 text-slate-700 font-medium">
                                {{ $mail->recipient_name ?? '-' }}
                            </td>
                            <td class="p-3.5 text-right space-x-2">
                                <a href="{{ route('incoming-mails.show', $mail) }}"
                                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded font-semibold text-[11px]">
                                    Detail
                                </a>
                                @can('update', $mail)
                                    <a href="{{ route('incoming-mails.edit', $mail) }}"
                                        class="px-2 py-1 {{ $mail->status === 'DRAFT' ? 'bg-amber-500 hover:bg-amber-600 text-white font-bold' : 'bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold' }} rounded text-[11px]">
                                        {{ $mail->status === 'DRAFT' ? 'Finalisasi Draft' : 'Edit' }}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center text-slate-500 italic text-xs">
                                {{ request('status_filter') === 'draft' ? 'Tidak ada draft surat masuk yang belum difinalisasi.' : 'Belum ada data surat masuk.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $incomingMails->links() }}
    </div>
@endsection
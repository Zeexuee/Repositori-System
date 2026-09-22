@extends('layouts.app')

@section('title', 'Daftar Surat Keluar')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Keluar</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola pencatatan, verifikasi, dan persetujuan surat keluar.</p>
        </div>
        @can('create', App\Models\OutgoingMail::class)
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('outgoing-mails.create') }}"
                    class="inline-flex items-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                    <span>Tambah Surat Keluar</span>
                </a>
            </div>
        @endcan
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-full">
                <thead>
                    <tr
                        class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
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
                    @forelse ($outgoingMails as $index => $mail)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
                                {{ ($outgoingMails->firstItem() ?? 1) + $index }}
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
                                    $isProgres = in_array($mail->status, ['PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING']);
                                    $badgeClasses = match ($mail->status) {
                                        'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 font-bold',
                                        'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 font-bold hover:bg-slate-300 cursor-pointer shadow-2xs transition-all',
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

                                @if ($isProgres && auth()->user()?->can('update', $mail))
                                    <form action="{{ route('outgoing-mails.update', $mail) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin merubah status surat {{ $mail->mail_number ?? '' }} dari PROGRES menjadi RETURN?');">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="RETURN">
                                        <button type="submit" title="Klik untuk langsung merubah status menjadi RETURN" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                            <span>{{ $displayStatus }}</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                        {{ $displayStatus }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 sm:px-5 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('outgoing-mails.show', $mail) }}"
                                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                    Detail
                                </a>
                                @can('update', $mail)
                                    <a href="{{ route('outgoing-mails.edit', $mail) }}"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                        Edit
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-500 italic text-sm">Belum ada data surat keluar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $outgoingMails->links() }}
    </div>
@endsection
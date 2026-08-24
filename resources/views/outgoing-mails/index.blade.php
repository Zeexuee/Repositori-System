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
                    class="inline-flex items-center space-x-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Surat Keluar</span>
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
                        <th class="p-3.5">Nomor Surat</th>
                        <th class="p-3.5">Subjek / Perihal</th>
                        <th class="p-3.5">Penerima</th>
                        <th class="p-3.5">Pembuat</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @forelse ($outgoingMails as $mail)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5 font-bold text-slate-900 font-mono">
                                {{ $mail->mail_number ?? '-' }}
                            </td>
                            <td class="p-3.5 text-slate-800 font-medium max-w-xs truncate" title="{{ $mail->subject }}">
                                {{ $mail->subject }}
                            </td>
                            <td class="p-3.5 text-slate-700 font-medium">{{ $mail->recipient }}</td>
                            <td class="p-3.5 text-slate-600 text-xs font-medium">{{ $mail->creator?->name ?? 'System' }}</td>
                            <td class="p-3.5">
                                @php
                                    $badgeClasses = match ($mail->status) {
                                        'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                                        'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClasses }}">
                                    {{ $mail->status }}
                                </span>
                            </td>
                            <td class="p-3.5 text-right space-x-2">
                                <a href="{{ route('outgoing-mails.show', $mail) }}"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded font-semibold text-[11px]">
                                    Detail
                                </a>
                                @can('update', $mail)
                                    <a href="{{ route('outgoing-mails.edit', $mail) }}"
                                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded font-semibold text-[11px]">
                                        Edit
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 italic text-xs">Belum ada data surat keluar.
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
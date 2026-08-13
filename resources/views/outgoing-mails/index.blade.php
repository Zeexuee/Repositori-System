@extends('layouts.app')

@section('title', 'Daftar Surat Keluar')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Keluar</h1>
        </div>
        @can('create', App\Models\OutgoingMail::class)
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('outgoing-mails.create') }}"
                    class="inline-flex items-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                    Buat Draf Surat Keluar
                </a>
            </div>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr
                    class="border-b border-slate-200 bg-slate-100 text-xs font-bold text-slate-700 uppercase tracking-wider">
                    <th class="p-3.5">Nomor Surat</th>
                    <th class="p-3.5">Subjek</th>
                    <th class="p-3.5">Penerima</th>
                    <th class="p-3.5">Pembuat</th>
                    <th class="p-3.5">Status</th>
                    <th class="p-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm">
                @forelse ($outgoingMails as $mail)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-3.5 font-bold text-slate-900">{{ $mail->mail_number ?? '-' }}</td>
                        <td class="p-3.5 text-slate-800">{{ $mail->subject }}</td>
                        <td class="p-3.5 text-slate-700">{{ $mail->recipient }}</td>
                        <td class="p-3.5 text-slate-600 text-xs">{{ $mail->creator?->name ?? 'System' }}</td>
                        <td class="p-3.5">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-300">
                                {{ $mail->status }}
                            </span>
                        </td>
                        <td class="p-3.5 text-right space-x-2">
                            <a href="{{ route('outgoing-mails.show', $mail) }}"
                                class="text-xs font-semibold text-slate-900 hover:underline">Detail</a>
                            @can('update', $mail)
                                <a href="{{ route('outgoing-mails.edit', $mail) }}"
                                    class="text-xs font-semibold text-amber-700 hover:underline">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500 italic text-xs">Belum ada draf atau surat keluar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $outgoingMails->links() }}
    </div>
@endsection
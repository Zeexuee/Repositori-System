@extends('layouts.app')

@section('title', 'Daftar Surat Keluar')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Daftar Surat Keluar</h1>
            <p class="text-sm text-gray-600 mt-1">Kelola pembuatan draf, verifikasi, dan tanda tangan digital surat keluar.</p>
        </div>
        @can('create', App\Models\OutgoingMail::class)
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('outgoing-mails.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-900 text-white text-sm font-semibold rounded-md hover:bg-blue-800 transition-colors shadow-sm">
                    + Buat Draf Surat Keluar
                </a>
            </div>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    <th class="p-3">Nomor Surat</th>
                    <th class="p-3">Subjek</th>
                    <th class="p-3">Penerima</th>
                    <th class="p-3">Pembuat</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($outgoingMails as $mail)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-3 font-semibold text-gray-900">{{ $mail->mail_number ?? '(Draf Belum Diberi Nomor)' }}</td>
                        <td class="p-3 text-gray-800">{{ $mail->subject }}</td>
                        <td class="p-3 text-gray-700">{{ $mail->recipient }}</td>
                        <td class="p-3 text-gray-600">{{ $mail->creator?->name ?? 'System' }}</td>
                        <td class="p-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-300">
                                {{ $mail->status }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('outgoing-mails.show', $mail) }}" class="text-blue-700 hover:text-blue-900 font-medium">Detail</a>
                            @can('update', $mail)
                                <a href="{{ route('outgoing-mails.edit', $mail) }}" class="text-amber-700 hover:text-amber-900 font-medium">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500">Belum ada draf atau surat keluar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $outgoingMails->links() }}
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Daftar Surat Masuk')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Daftar Surat Masuk</h1>
            <p class="text-sm text-gray-600 mt-1">Kelola dan pantau seluruh alur dokumen surat masuk perusahaan.</p>
        </div>
        @can('create', App\Models\IncomingMail::class)
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('incoming-mails.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-900 text-white text-sm font-semibold rounded-md hover:bg-blue-800 transition-colors shadow-sm">
                    + Catat Surat Masuk
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
                    <th class="p-3">Pengirim</th>
                    <th class="p-3">Tanggal Diterima</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($incomingMails as $mail)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-3 font-semibold text-gray-900">{{ $mail->mail_number }}</td>
                        <td class="p-3 text-gray-800">{{ $mail->subject }}</td>
                        <td class="p-3 text-gray-700">{{ $mail->sender }}</td>
                        <td class="p-3 text-gray-600">{{ $mail->received_date?->format('d M Y') }}</td>
                        <td class="p-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-300">
                                {{ $mail->status }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('incoming-mails.show', $mail) }}" class="text-blue-700 hover:text-blue-900 font-medium">Detail</a>
                            @can('update', $mail)
                                <a href="{{ route('incoming-mails.edit', $mail) }}" class="text-amber-700 hover:text-amber-900 font-medium">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500">Belum ada data surat masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $incomingMails->links() }}
    </div>
@endsection

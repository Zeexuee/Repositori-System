@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Detail Surat Masuk</h1>
            <p class="text-sm text-gray-600 mt-1">ID Dokumen: {{ $incomingMail->id }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <a href="{{ route('incoming-mails.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Kembali
            </a>
            @can('delete', $incomingMail)
                <form action="{{ route('incoming-mails.destroy', $incomingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat masuk ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-rose-700 rounded-md hover:bg-rose-800">
                        Hapus Dokumen
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div class="space-y-4">
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Nomor Surat</span>
                <span class="font-bold text-gray-900 text-base">{{ $incomingMail->mail_number }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Subjek / Perihal</span>
                <span class="font-medium text-gray-800">{{ $incomingMail->subject }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Pengirim</span>
                <span class="font-medium text-gray-800">{{ $incomingMail->sender }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Tanggal Diterima</span>
                <span class="font-medium text-gray-800">{{ $incomingMail->received_date?->format('d M Y') }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-300 mt-1">
                    {{ $incomingMail->status }}
                </span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Berkas Lampiran</span>
                @if ($incomingMail->file_path)
                    <span class="text-blue-800 font-medium break-all">{{ $incomingMail->file_path }}</span>
                @else
                    <span class="text-gray-400">Tidak ada lampiran.</span>
                @endif
            </div>
        </div>
    </div>
@endsection

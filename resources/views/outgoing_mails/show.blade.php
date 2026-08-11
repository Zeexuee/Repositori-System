@extends('layouts.app')

@section('title', 'Detail Surat Keluar')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Detail Surat Keluar</h1>
            <p class="text-sm text-gray-600 mt-1">ID Dokumen: {{ $outgoingMail->id }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <a href="{{ route('outgoing-mails.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Kembali
            </a>
            @can('delete', $outgoingMail)
                <form action="{{ route('outgoing-mails.destroy', $outgoingMail) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus draf/surat keluar ini?');">
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
                <span class="font-bold text-gray-900 text-base">{{ $outgoingMail->mail_number ?? '(Draf Belum Diberi Nomor)' }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Subjek / Perihal</span>
                <span class="font-medium text-gray-800">{{ $outgoingMail->subject }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Penerima</span>
                <span class="font-medium text-gray-800">{{ $outgoingMail->recipient }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Pembuat Dokumen</span>
                <span class="font-medium text-gray-800">{{ $outgoingMail->creator?->name ?? 'System' }}</span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-300 mt-1">
                    {{ $outgoingMail->status }}
                </span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Berkas Lampiran</span>
                @if ($outgoingMail->file_path)
                    <span class="text-blue-800 font-medium break-all">{{ $outgoingMail->file_path }}</span>
                @else
                    <span class="text-gray-400">Tidak ada lampiran.</span>
                @endif
            </div>
        </div>
    </div>
@endsection

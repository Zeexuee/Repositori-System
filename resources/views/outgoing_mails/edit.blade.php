@extends('layouts.app')

@section('title', 'Edit Surat Keluar')

@section('content')
    <div class="pb-4 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-900">Edit Surat Keluar</h1>
        <p class="text-sm text-gray-600 mt-1">Perbarui informasi surat keluar.</p>
    </div>

    <form action="{{ route('outgoing-mails.update', $outgoingMail) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="subject" class="block text-sm font-semibold text-gray-900 mb-1">Subjek / Perihal</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject', $outgoingMail->subject) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm">
                @error('subject')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recipient" class="block text-sm font-semibold text-gray-900 mb-1">Penerima Surat</label>
                <input type="text" name="recipient" id="recipient" value="{{ old('recipient', $outgoingMail->recipient) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm">
                @error('recipient')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="file" class="block text-sm font-semibold text-gray-900 mb-1">Ganti Berkas PDF (Opsional)</label>
            <input type="file" name="file" id="file" accept=".pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100">
            @error('file')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
            <a href="{{ route('outgoing-mails.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Batal
            </a>
            <button type="submit" :disabled="loading" class="px-5 py-2 text-sm font-semibold text-white bg-blue-900 rounded-md hover:bg-blue-800 disabled:opacity-50 inline-flex items-center space-x-2">
                <span x-show="!loading">Perbarui Dokumen</span>
                <span x-show="loading" class="flex items-center space-x-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memproses...</span>
                </span>
            </button>
        </div>
    </form>
@endsection

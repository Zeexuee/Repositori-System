@extends('layouts.app')

@section('title', 'Buat Draf Surat Keluar')

@section('content')
    <div class="pb-4 border-b border-slate-200/80">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Buat Draf Surat Keluar</h1>
        <p class="text-xs text-slate-500 mt-1">Lengkapi informasi dasar draf surat keluar (Nomor surat akan digenerate otomatis oleh sistem).</p>
    </div>

    <form action="{{ route('outgoing-mails.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subjek / Perihal</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required class="w-full px-4 py-2.5 bg-white/80 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('subject')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recipient" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Penerima Surat</label>
                <input type="text" name="recipient" id="recipient" value="{{ old('recipient') }}" required class="w-full px-4 py-2.5 bg-white/80 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('recipient')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="file" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Upload Berkas Draf PDF (Opsional pada saat Draf)</label>
            <input type="file" name="file" id="file" accept=".pdf" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
            @error('file')
                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <a href="{{ route('outgoing-mails.index') }}" class="px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                Batal
            </a>
            <button type="submit" :disabled="loading" class="px-5 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs">
                <span x-show="!loading">Simpan Draf</span>
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

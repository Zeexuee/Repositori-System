@extends('layouts.app')

@section('title', 'Form Surat Keluar (Batch)')

@section('content')
    <div class="pb-4 border-b border-slate-200/80">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Form Surat Keluar</h1>
        <p class="text-xs text-slate-500 mt-1">Catat dan terbitkan surat keluar baru ke dalam sistem secara kolektif.</p>
    </div>

    @if ($errors->any())
        <div class="mt-6 p-4 bg-rose-50 border border-rose-200 rounded-xl">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-xs font-bold text-rose-800">Terdapat kesalahan pada input Anda:</h3>
                    <div class="mt-2 text-xs text-rose-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('outgoing-mails.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="outgoingMailBatchForm" @submit="onSubmit">
        @csrf

        <div class="space-y-6">
            <template x-for="(doc, index) in documents" :key="doc.id">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6 relative transition-all duration-300"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-4">
                    
                    <!-- Header Baris Dokumen -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-3">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-900 text-white text-[10px] font-bold" x-text="index + 1"></span>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Form Surat Keluar</h3>
                        </div>
                        <button type="button" @click="removeDocument(index)" x-show="documents.length > 1" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors cursor-pointer">
                            Hapus
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Nomor Surat (Opsional) -->
                        <div>
                            <label :for="'mail_number_' + index" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Surat <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                            <input type="text" :name="'documents[' + index + '][mail_number]'" :id="'mail_number_' + index" x-model="doc.mail_number" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                        </div>

                        <!-- Subjek / Perihal -->
                        <div>
                            <label :for="'subject_' + index" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subjek / Perihal <span class="text-slate-900 font-bold">*</span></label>
                            <input type="text" :name="'documents[' + index + '][subject]'" :id="'subject_' + index" x-model="doc.subject" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        </div>

                        <!-- Penerima Surat -->
                        <div>
                            <label :for="'recipient_' + index" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Penerima Surat <span class="text-slate-900 font-bold">*</span></label>
                            <input type="text" :name="'documents[' + index + '][recipient]'" :id="'recipient_' + index" x-model="doc.recipient" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Upload Berkas PDF / Gambar -->
                        <div>
                            <label :for="'file_' + index" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Upload Berkas Dokumen (PDF / Gambar)</label>
                            <input type="file" :name="'documents[' + index + '][file]'" :id="'file_' + index" accept="application/pdf,image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                        </div>

                        <!-- Catatan Disposisi -->
                        <div>
                            <label :for="'disposition_note_' + index" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                            <textarea :name="'documents[' + index + '][disposition_note]'" :id="'disposition_note_' + index" x-model="doc.disposition_note" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs"></textarea>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Tombol Tambah Dokumen -->
        <div class="flex justify-center pt-4 pb-2">
            <button type="button" @click="addDocument()" class="px-5 py-2.5 bg-white border-2 border-dashed border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 font-bold text-xs rounded-xl transition-all flex items-center space-x-2 cursor-pointer shadow-sm hover:shadow">
                <span class="text-lg leading-none">+</span>
                <span>Tambah Baris Surat Keluar</span>
            </button>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200/80">
            <a href="{{ route('outgoing-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                Batal
            </a>

            <button type="submit" :disabled="loading" class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
                <span x-show="!loading">Simpan Semua Surat Keluar</span>
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

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('outgoingMailBatchForm', () => ({
                loading: false,
                documents: [
                    { id: Date.now(), mail_number: '', subject: '', recipient: '', disposition_note: '' }
                ],
                addDocument() {
                    this.documents.push({
                        id: Date.now() + Math.random(),
                        mail_number: '',
                        subject: '',
                        recipient: '',
                        disposition_note: ''
                    });
                },
                removeDocument(index) {
                    if (this.documents.length > 1) {
                        this.documents.splice(index, 1);
                    }
                },
                onSubmit(e) {
                    if (this.documents.length === 0) {
                        e.preventDefault();
                        alert('Minimal harus ada 1 surat keluar.');
                        return;
                    }
                    this.loading = true;
                }
            }));
        });
    </script>
@endsection

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

                    <!-- Upload Dokumen & Berkas Lampiran (Bisa Lebih Dari 1 Dokumen) -->
                    <div x-data="{
                        selectedFiles: [],
                        handleFiles(event) {
                            const newFiles = Array.from(event.target.files);
                            const dt = new DataTransfer();
                            this.selectedFiles.forEach(f => dt.items.add(f));
                            newFiles.forEach(f => {
                                if (!this.selectedFiles.some(existing => existing.name === f.name && existing.size === f.size)) {
                                    dt.items.add(f);
                                }
                            });
                            this.$refs.fileInput.files = dt.files;
                            this.selectedFiles = Array.from(dt.files);
                        },
                        removeFile(fileIndex) {
                            const dt = new DataTransfer();
                            const files = Array.from(this.$refs.fileInput.files);
                            files.splice(fileIndex, 1);
                            files.forEach(f => dt.items.add(f));
                            this.$refs.fileInput.files = dt.files;
                            this.selectedFiles = Array.from(dt.files);
                        },
                        formatSize(bytes) {
                            if (bytes < 1024) return bytes + ' B';
                            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                            return (bytes / 1048576).toFixed(1) + ' MB';
                        }
                    }" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Upload Dokumen & Lampiran (Bisa Lebih Dari 1 File)
                            </label>
                            <span class="text-[11px] text-slate-500 font-medium" x-show="selectedFiles.length > 0">
                                <span class="font-bold text-slate-900" x-text="selectedFiles.length"></span> berkas terpilih
                            </span>
                        </div>

                        <div class="relative border-2 border-dashed border-slate-300 hover:border-slate-800 rounded-2xl p-4 bg-slate-50/70 hover:bg-slate-50 transition-all text-center group cursor-pointer"
                             @click="$refs.fileInput.click()">
                            <input type="file" 
                                   x-ref="fileInput"
                                   :name="'documents[' + index + '][files][]'" 
                                   multiple 
                                   accept="application/pdf,image/*" 
                                   capture="environment" 
                                   @change="handleFiles($event)"
                                   class="hidden">
                            
                            <div class="flex flex-col items-center justify-center space-y-1.5 py-1">
                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-700 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5 text-slate-600 group-hover:text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <p class="text-xs font-bold text-slate-800">
                                    <span class="underline decoration-slate-400 group-hover:decoration-slate-900">+ Pilih / Tambah Dokumen</span>
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Pilih 1 atau beberapa dokumen (PDF / Gambar, maks. 10MB per file)
                                </p>
                            </div>
                        </div>

                        <!-- List File Terpilih -->
                        <div x-show="selectedFiles.length > 0" class="space-y-1.5 pt-1">
                            <template x-for="(file, fIdx) in selectedFiles" :key="fIdx">
                                <div class="flex items-center justify-between p-2.5 bg-white border border-slate-200 rounded-xl text-xs shadow-2xs">
                                    <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold"
                                              :class="file.type.includes('pdf') ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                                              x-text="file.type.includes('pdf') ? 'PDF' : 'FOTO'">
                                        </span>
                                        <span class="font-medium text-slate-800 truncate" x-text="file.name"></span>
                                        <span class="text-slate-400 text-[10px] flex-shrink-0 font-mono" x-text="'(' + formatSize(file.size) + ')'"></span>
                                    </div>
                                    <button type="button" 
                                            @click.stop="removeFile(fIdx)" 
                                            class="flex-shrink-0 p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus berkas ini">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Catatan Disposisi -->
                    <div>
                        <label :for="'disposition_note_' + index" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                        <textarea :name="'documents[' + index + '][disposition_note]'" :id="'disposition_note_' + index" x-model="doc.disposition_note" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs"></textarea>
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

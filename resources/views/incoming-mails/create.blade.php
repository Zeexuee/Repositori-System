@extends('layouts.app')

@section('title', 'Pencatatan Surat Masuk')

@section('content')
    <div x-data="incomingMailBatchForm()" class="space-y-6">
        <div class="pb-4 border-b border-slate-200/80 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Form Pencatatan Surat Masuk</h1>
                <p class="text-xs text-slate-500 mt-1">Catat satu atau beberapa dokumen dari pengirim/biro yang sama dalam satu tanda terima.</p>
            </div>
            <div class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-bold shadow-xs space-x-1.5">
                <span class="text-slate-400">Total Dokumen:</span>
                <span class="font-mono text-white" x-text="documents.length">1</span>
            </div>
        </div>

        <div x-show="errorMessageList.length > 0" class="p-4 bg-slate-100 border border-slate-300 rounded-2xl text-xs text-slate-800 space-y-2">
            <p class="font-bold text-slate-900">Gagal menyimpan surat masuk. Silakan periksa kembali input berikut:</p>
            <ul class="list-disc list-inside space-y-1 font-medium">
                <template x-for="(err, idx) in errorMessageList" :key="idx">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>

        <form action="{{ route('incoming-mails.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-ref="form" @submit="onSubmit($event)">
            @csrf
            <input type="hidden" name="is_draft" :value="isDraft ? '1' : '0'">

            <!-- Section 1: Informasi Tanda Terima & Pengirim (Shared / Terkunci untuk Seluruh Dokumen) -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
                <div class="pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Informasi Utama & Pengirim</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dari (Pengirim) -->
                    <div x-data="searchableSelect({
                        initialValue: {{ json_encode(old('sender', '')) }},
                        listType: 'senders'
                    })" class="relative" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Dari / Biro Pengirim <span class="text-slate-900 font-bold">*</span>
                        </label>
                        <input type="hidden" name="sender" :value="value">

                        <!-- Trigger Button -->
                        <button type="button" 
                                @click="toggle()"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all cursor-pointer">
                            <span x-text="value ? value : 'Pilih / Cari Biro Pengirim...'" :class="{ 'text-slate-400': !value, 'text-slate-900 font-bold': value }"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown List -->
                        <div x-show="open" x-cloak 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute z-30 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                            
                            <div class="relative">
                                <input x-ref="searchInput" 
                                       type="text" 
                                       x-model="search"
                                       @keydown.enter.prevent.stop="commitSearch()"
                                       @keydown.escape.prevent.stop="open = false"
                                       placeholder="Cari atau tulis nama baru..."
                                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">&#215;</span>
                            </div>

                            <div class="max-h-44 overflow-y-auto space-y-1">
                                <!-- Registrasi on-the-fly: WAJIB berada di urutan paling atas -->
                                <div class="pb-1 border-b border-slate-100">
                                    <button type="button"
                                            @click="search.trim() !== '' ? addNewOption(search.trim()) : $refs.searchInput.focus()"
                                            :disabled="search.trim() !== '' && hasExactMatch"
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                        <span x-show="search.trim() === ''">+ Tambah Baru</span>
                                        <span x-show="search.trim() !== '' && !hasExactMatch">+ Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                        <span x-show="search.trim() !== '' && hasExactMatch">Opsi "<span x-text="search.trim()"></span>" sudah terdaftar</span>
                                    </button>
                                </div>

                                <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar list yang ditambahkan</p>
                                <template x-for="item in filteredOptions" :key="item">
                                    <div class="flex items-center space-x-1">
                                        <button type="button" 
                                                @click="selectOption(item)" 
                                                class="flex-1 text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                                :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': value === item }">
                                            <span x-text="item"></span>
                                            <span x-show="value === item" class="text-xs">&#10003;</span>
                                        </button>
                                        <button type="button" 
                                                @click.stop="removeOption(item)" 
                                                class="p-2 text-slate-400 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                                                title="Hapus dari daftar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Masuk -->
                    <div>
                        <label for="received_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Masuk <span class="text-slate-900 font-bold">*</span></label>
                        <div class="relative w-full">
                            <input type="datetime-local" name="received_date" id="received_date" value="{{ old('received_date', date('Y-m-d\TH:i')) }}" required readonly class="peer w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl focus:outline-none text-sm text-slate-500 shadow-2xs cursor-not-allowed">
                            <div class="absolute bottom-full left-4 mb-2 hidden peer-hover:block peer-focus:block px-2.5 py-1.5 bg-slate-800 text-white text-[10px] font-bold rounded shadow-xl whitespace-nowrap z-50 pointer-events-none transition-all">
                                Waktu dan tanggal telah di set otomatis
                                <div class="absolute -bottom-1 left-4 w-2 h-2 bg-slate-800 rotate-45"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section 2: Daftar Dokumen-Dokumen Dalam Tanda Terima -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Daftar Dokumen Surat Masuk</span>
                    </h2>

                    <button type="button" @click="addDocument()" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-100 border border-slate-200 text-slate-900 hover:bg-slate-200 rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer">
                        <span>+ Tambah Dokumen Lain dari Biro Ini</span>
                    </button>
                </div>

                <template x-for="(doc, index) in documents" :key="doc.id">
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-5 relative">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-lg bg-slate-900 text-white text-xs font-bold flex items-center justify-center font-mono" x-text="index + 1"></span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="'Dokumen Surat Masuk #' + (index + 1)"></h3>
                            </div>

                            <button type="button" 
                                    x-show="documents.length > 1" 
                                    @click="removeDocument(index)" 
                                    class="text-xs text-slate-600 hover:text-slate-900 font-semibold px-2 py-1 rounded-lg hover:bg-slate-100 transition-all flex items-center space-x-1 cursor-pointer">
                                <span>Hapus Dokumen Ini</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nomor Surat -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor Surat <span class="text-slate-400 font-normal normal-case">(Opsional)</span>
                                </label>
                                <input type="text" 
                                       :name="'documents[' + index + '][mail_number]'" 
                                       x-model="doc.mail_number" 
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                            </div>

                            <!-- Kepada (Penerima) Dokumen Ini -->
                            <div x-data="searchableSelect({
                                initialValue: doc.recipient,
                                listType: 'recipients'
                            })" class="relative" @click.away="open = false" x-effect="doc.recipient = value">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Kepada / Penerima Dokumen <span class="text-slate-900 font-bold" x-show="!isDraft">*</span>
                                </label>
                                <input type="hidden" :name="'documents[' + index + '][recipient]'" :value="value">

                                <!-- Trigger Button -->
                                <button type="button" 
                                        @click="toggle()"
                                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all cursor-pointer">
                                    <span x-text="value ? value : 'Pilih / Cari Penerima...'" :class="{ 'text-slate-400': !value, 'text-slate-900 font-bold': value }"></span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- Dropdown List -->
                                <div x-show="open" x-cloak 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-30 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                                    
                                    <div class="relative">
                                        <input x-ref="searchInput" 
                                       type="text" 
                                       x-model="search"
                                       @keydown.enter.prevent.stop="commitSearch()"
                                       @keydown.escape.prevent.stop="open = false"
                                       placeholder="Cari atau tulis nama baru..."
                                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                        <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">&#215;</span>
                                    </div>

                                    <div class="max-h-44 overflow-y-auto space-y-1">
                                        <!-- Registrasi on-the-fly: WAJIB berada di urutan paling atas -->
                                        <div class="pb-1 border-b border-slate-100">
                                            <button type="button"
                                                    @click="search.trim() !== '' ? addNewOption(search.trim()) : $refs.searchInput.focus()"
                                                    :disabled="search.trim() !== '' && hasExactMatch"
                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                                <span x-show="search.trim() === ''">+ Tambah Baru</span>
                                                <span x-show="search.trim() !== '' && !hasExactMatch">+ Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                                <span x-show="search.trim() !== '' && hasExactMatch">Opsi "<span x-text="search.trim()"></span>" sudah terdaftar</span>
                                            </button>
                                        </div>

                                        <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar list yang ditambahkan</p>
                                        <template x-for="item in filteredOptions" :key="item">
                                            <div class="flex items-center space-x-1">
                                                <button type="button" 
                                                        @click="selectOption(item)" 
                                                        class="flex-1 text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                                        :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': value === item }">
                                                    <span x-text="item"></span>
                                                    <span x-show="value === item" class="text-xs">&#10003;</span>
                                                </button>
                                                <button type="button" 
                                                        @click.stop="removeOption(item)" 
                                                        class="p-2 text-slate-400 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Hapus dari daftar">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Perihal -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Perihal <span class="text-slate-900 font-bold" x-show="!isDraft">*</span>
                            </label>
                            <input type="text" 
                                   :name="'documents[' + index + '][subject]'" 
                                   x-model="doc.subject" 
                                   class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Disposisi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                                <textarea :name="'documents[' + index + '][disposition_note]'" x-model="doc.disposition_note" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 shadow-2xs focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
                            </div>

                            <!-- Keterangan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan / Catatan Tambahan</label>
                                <textarea :name="'documents[' + index + '][notes]'" x-model="doc.notes" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 shadow-2xs focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
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
                        }" class="pt-2 space-y-2">
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
                                        Pilih 1 atau beberapa dokumen (PDF / Foto Kamera, maks. 10MB per file)
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
                    </div>
                </template>
            </div>

            <!-- Action Buttons: Simpan Sebagai Draft vs Finalisasi -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200/80">
                <a href="{{ route('incoming-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                    Batal
                </a>

                <div class="flex items-center space-x-3">
                    <!-- FITUR DRAFT DI-HOLD SEMENTARA
                    <button type="submit" 
                            @click="isDraft = true" 
                            :disabled="loading" 
                            class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-all shadow-2xs cursor-pointer">
                        <span>Simpan Sebagai Draft</span>
                    </button>
                    -->

                    <!-- Tombol Finalisasi -->
                    <button type="submit" 
                            @click="isDraft = false" 
                            :disabled="loading" 
                            class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
                        <span x-show="!loading">Simpan Dokumen</span>
                        <span x-show="loading" class="flex items-center space-x-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memproses...</span>
                        </span>
                    </button>
                </div>
            </div>
        </form>

    </div>

    <script>
        function incomingMailBatchForm() {
            const rawOldDocs = {{ json_encode(array_values(old('documents', []))) }};
            const oldDocs = Array.isArray(rawOldDocs) ? rawOldDocs : Object.values(rawOldDocs || {});
            const defaultDoc = [
                { id: Date.now(), mail_number: '', recipient: '', subject: '', disposition_note: '', notes: '' }
            ];
            const initialDocs = (oldDocs && oldDocs.length > 0) 
                ? oldDocs.map((d, i) => ({ 
                    id: Date.now() + i, 
                    mail_number: (d && d.mail_number !== undefined && d.mail_number !== null) ? String(d.mail_number) : '', 
                    recipient: (d && d.recipient !== undefined && d.recipient !== null) ? String(d.recipient) : '', 
                    subject: (d && d.subject !== undefined && d.subject !== null) ? String(d.subject) : '', 
                    disposition_note: (d && d.disposition_note !== undefined && d.disposition_note !== null) ? String(d.disposition_note) : '', 
                    notes: (d && d.notes !== undefined && d.notes !== null) ? String(d.notes) : '' 
                }))
                : defaultDoc;

            return {
                loading: false,
                isDraft: {{ old('is_draft') ? 'true' : 'false' }},
                documents: initialDocs,
                errorMessageList: {{ json_encode($errors->all()) }},

                addDocument() {
                    this.documents.push({
                        id: Date.now() + Math.random(),
                        mail_number: '',
                        recipient: '',
                        subject: '',
                        disposition_note: '',
                        notes: ''
                    });
                },
                removeDocument(index) {
                    if (this.documents.length > 1) {
                        this.documents.splice(index, 1);
                    }
                },
                async onSubmit(e) {
                    this.errorMessageList = [];
                    const errors = [];

                    // Validasi input wajib: Biro Pengirim
                    const senderInput = this.$refs.form.querySelector('input[name="sender"]');
                    const senderVal = senderInput ? senderInput.value.trim() : '';
                    if (!senderVal) {
                        errors.push('Biro / Pengirim wajib dipilih atau diisi.');
                    }

                    // Validasi input wajib: Tanggal Masuk
                    const dateInput = this.$refs.form.querySelector('input[name="received_date"]');
                    if (!dateInput || !dateInput.value.trim()) {
                        errors.push('Tanggal Masuk wajib diisi.');
                    }


                    // Jika bukan draft: Kepada dan Perihal di setiap dokumen wajib diisi
                    if (!this.isDraft) {
                        this.documents.forEach((doc, idx) => {
                            const num = idx + 1;
                            if (!doc.recipient || !String(doc.recipient).trim()) {
                                errors.push(`Kepada / Penerima Dokumen pada Dokumen #${num} wajib diisi.`);
                            }
                            if (!doc.subject || !String(doc.subject).trim()) {
                                errors.push(`Perihal pada Dokumen #${num} wajib diisi.`);
                            }
                        });
                    }

                    // Jika terdapat data wajib yang belum lengkap:
                    if (errors.length > 0) {
                        e.preventDefault();
                        this.errorMessageList = errors;
                        this.loading = false;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return false;
                    }

                    e.preventDefault();
                    this.loading = true;

                    try {
                        const formData = new FormData(this.$refs.form);
                        // Tambahkan nilai is_draft sesuai pilihan user
                        formData.set('is_draft', this.isDraft ? '1' : '0');

                        const response = await fetch(this.$refs.form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = '{{ route('incoming-mails.index') }}';
                            }
                        } else {
                            const data = await response.json();
                            if (response.status === 422) {
                                // Validation error dari server
                                this.errorMessageList = Object.values(data.errors).flat();
                            } else {
                                this.errorMessageList = [data.message || 'Terjadi kesalahan saat menyimpan data.'];
                            }
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    } catch (error) {
                        this.errorMessageList = ['Terjadi kesalahan koneksi atau server. Silakan coba lagi.'];
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }

        // Store global: daftar opsi dibagikan ke seluruh komponen combobox pada halaman ini,
        // sehingga registrasi / penghapusan on-the-fly langsung tercermin di semua dropdown.
        document.addEventListener('alpine:init', () => {
            Alpine.store('mailOptions', {
                senders: @json(collect($senders ?? [])->filter()->unique()->values()),
                recipients: @json(collect($recipients ?? [])->filter()->unique()->values()),

                list(type) {
                    return type === 'senders' ? this.senders : this.recipients;
                },

                add(type, option) {
                    const value = String(option ?? '').trim();
                    if (value === '') {
                        return '';
                    }

                    const target = this.list(type);
                    const existing = target.find(item => String(item).toLowerCase() === value.toLowerCase());
                    if (existing !== undefined) {
                        return existing;
                    }

                    target.unshift(value);

                    return value;
                },

                remove(type, option) {
                    if (type === 'senders') {
                        this.senders = this.senders.filter(item => item !== option);
                    } else {
                        this.recipients = this.recipients.filter(item => item !== option);
                    }
                },
            });
        });

        function searchableSelect(config) {
            const settings = config || {};

            return {
                open: false,
                search: '',
                value: settings.initialValue ? String(settings.initialValue) : '',
                listType: settings.listType === 'recipients' ? 'recipients' : 'senders',

                init() {
                    // Pastikan nilai awal (misal hasil old() setelah validasi gagal) tetap tersedia di daftar.
                    if (this.value) {
                        this.$store.mailOptions.add(this.listType, this.value);
                    }
                },

                get options() {
                    return this.$store.mailOptions.list(this.listType);
                },

                get filteredOptions() {
                    const keyword = this.search.trim().toLowerCase();
                    if (keyword === '') {
                        return this.options;
                    }

                    return this.options.filter(option => String(option).toLowerCase().includes(keyword));
                },

                get hasExactMatch() {
                    const keyword = this.search.trim().toLowerCase();

                    return keyword !== '' && this.options.some(option => String(option).toLowerCase() === keyword);
                },

                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    }
                },

                selectOption(option) {
                    this.value = option;
                    this.search = '';
                    this.open = false;
                },

                addNewOption(newOption) {
                    const registered = this.$store.mailOptions.add(this.listType, newOption);
                    if (registered === '') {
                        return;
                    }

                    this.selectOption(registered);
                },

                removeOption(option) {
                    this.$store.mailOptions.remove(this.listType, option);
                    if (this.value === option) {
                        this.value = '';
                    }
                },

                // Enter di kolom pencarian: pilih kecocokan persis, atau daftarkan opsi baru.
                commitSearch() {
                    const keyword = this.search.trim();

                    if (keyword === '') {
                        this.open = false;
                        return;
                    }

                    const match = this.options.find(option => String(option).toLowerCase() === keyword.toLowerCase());

                    if (match !== undefined) {
                        this.selectOption(match);
                        return;
                    }

                    this.addNewOption(keyword);
                },
            };
        }
    </script>
@endsection











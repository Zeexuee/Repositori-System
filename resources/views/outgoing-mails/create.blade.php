@extends('layouts.app')

@section('title', 'Form Surat Keluar (Batch)')

@section('content')
    <div class="pb-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2 font-medium" aria-label="Breadcrumb">
                <a href="{{ route('outgoing-mails.index') }}" class="hover:text-slate-900 hover:underline transition-colors">
                    Surat Keluar
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Catat Surat Keluar</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Form Surat Keluar</h1>
            <p class="text-xs text-slate-500 mt-1">Catat dan terbitkan surat keluar baru ke dalam sistem secara kolektif.</p>
        </div>
        <div class="flex items-center space-x-2.5">
            <a href="{{ route('outgoing-mails.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                ← Kembali ke Daftar
            </a>
        </div>
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

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Nomor Surat (Opsional) -->
                        <div>
                            <label :for="'mail_number_' + doc.formKey" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Surat <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                            <input type="text" :name="'documents[' + doc.formKey + '][mail_number]'" :id="'mail_number_' + doc.formKey" x-model="doc.mail_number" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                        </div>

                        <!-- Subjek / Perihal -->
                        <div>
                            <label :for="'subject_' + doc.formKey" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subjek / Perihal <span class="text-slate-900 font-bold">*</span></label>
                            <input type="text" :name="'documents[' + doc.formKey + '][subject]'" :id="'subject_' + doc.formKey" x-model="doc.subject" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        </div>

                        <!-- Penerima Surat (Multi-Recipient Chip Combobox) -->
                        <div x-data="multiRecipientSelect({ listType: 'recipients', initialValue: Alpine.raw(doc).recipient })" x-effect="doc.recipient = value" class="relative" @click.away="closeDropdown()">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Penerima Surat <span class="text-slate-900 font-bold">*</span>
                            </label>
                            <input type="hidden" :name="'documents[' + doc.formKey + '][recipient]'" :value="value">

                            <!-- Trigger Box with Chips -->
                            <div @click="toggle()"
                                 class="w-full min-h-[42px] px-3 py-1.5 bg-slate-50/50 border border-slate-200 rounded-xl text-left text-sm text-slate-900 focus-within:ring-2 focus-within:ring-slate-900 flex items-center justify-between gap-2 shadow-2xs transition-all cursor-pointer">
                                <div class="flex flex-wrap items-center gap-1.5 flex-1">
                                    <template x-for="(tag, tIdx) in tags" :key="tag + '_' + tIdx">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-900 text-white shadow-2xs">
                                            <span x-text="tag"></span>
                                            <button type="button" @click.stop="removeTag(tIdx)" class="text-slate-300 hover:text-white leading-none cursor-pointer" title="Hapus penerima ini">&times;</button>
                                        </span>
                                    </template>
                                    <span x-show="tags.length === 0" class="text-slate-400 text-sm py-0.5 px-1">Pilih / Tambah Penerima...</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            <!-- Dropdown List -->
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                                <div class="relative">
                                    <input x-ref="searchInput"
                                           type="text"
                                           x-model="search"
                                           @keydown.enter.prevent.stop="commitSearch()"
                                           @keydown.comma.prevent.stop="commitSearch()"
                                           @keydown.escape.prevent.stop="closeDropdown()"
                                           placeholder="Ketik penerima lalu tekan Enter..."
                                           class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                    <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">&times;</span>
                                </div>

                                <div class="max-h-44 overflow-y-auto space-y-1">
                                    <div class="pb-1 border-b border-slate-100">
                                        <button type="button"
                                                @click="search.trim() !== '' ? addNewOption(search.trim()) : $refs.searchInput.focus()"
                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer">
                                            <span x-show="search.trim() === ''">+ Ketik untuk Tambah Penerima Baru</span>
                                            <span x-show="search.trim() !== ''">+ Tambahkan "<span x-text="search.trim()"></span>"</span>
                                        </button>
                                    </div>

                                    <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar penerima</p>
                                    <template x-for="item in filteredOptions" :key="item">
                                        <div class="flex items-center space-x-1">
                                            <button type="button"
                                                    @click="toggleOption(item)"
                                                    class="flex-1 text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                                    :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': isSelected(item) }">
                                                <span x-text="item" class="truncate pr-2"></span>
                                                <span x-show="isSelected(item)" class="text-xs">&#10003;</span>
                                            </button>
                                            <button type="button"
                                                    @click.stop="removeOption(item)"
                                                    class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                                                    title="Hapus dari daftar saran">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Status (Opsional) -->
                        <div x-data="searchableSelect({ listType: 'statuses', initialValue: Alpine.raw(doc).status })" x-effect="doc.status = value" class="relative" @click.away="open = false">
                            <label :for="'status_' + doc.formKey" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                            <div class="relative">
                                <input type="text" :name="'documents[' + doc.formKey + '][status]'" :id="'status_' + doc.formKey" x-model="value" @focus="open = true" @input="open = true" autocomplete="off" placeholder="Ketik atau pilih status..." class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs pr-8">
                                <button type="button" @click="open = !open" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                            <div x-show="open && filteredOptions.length > 0" x-transition class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto py-1">
                                <template x-for="opt in filteredOptions" :key="opt">
                                    <div @click="select(opt)" class="flex items-center justify-between px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 cursor-pointer group">
                                        <span x-text="opt" class="truncate pr-2"></span>
                                        <button type="button" @click.stop="remove(opt)" title="Hapus dari daftar saran" class="text-slate-300 hover:text-rose-600 opacity-0 group-hover:opacity-100 transition-opacity p-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
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
                                   :name="'documents[' + doc.formKey + '][files][]'" 
                                   multiple 
                                   accept="application/pdf,image/*" 
                                   capture="environment" 
                                   @change="handleFiles($event)"
                                   class="hidden">
                            
                            <div class="flex flex-col items-center justify-center space-y-1 py-2">
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
                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-300"
                                              x-text="file.type.includes('pdf') ? 'PDF' : 'FOTO'">
                                        </span>
                                        <span class="font-medium text-slate-800 truncate" x-text="file.name"></span>
                                        <span class="text-slate-400 text-[10px] flex-shrink-0 font-mono" x-text="'(' + formatSize(file.size) + ')'"></span>
                                    </div>
                                    <button type="button" 
                                            @click.stop="removeFile(fIdx)" 
                                            class="flex-shrink-0 p-1 text-slate-400 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
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
                        <label :for="'disposition_note_' + doc.formKey" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                        <textarea :name="'documents[' + doc.formKey + '][disposition_note]'" :id="'disposition_note_' + doc.formKey" x-model="doc.disposition_note" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs"></textarea>
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
            <a href="{{ route('outgoing-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                ← Batal & Kembali
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
            Alpine.store('mailOptions', {
                statuses: @json($statuses ?? \App\Support\MailCustomStatus::DEFAULTS),
                recipients: @json($recipients ?? []),
                getOptions(type) {
                    return this[type] || [];
                },
                addOption(type, val, persist = true) {
                    const clean = String(val || '').trim();
                    if (!clean) return '';
                    const list = this[type] || [];
                    const existing = list.find(item => String(item).toLowerCase() === clean.toLowerCase());
                    const finalVal = existing || clean;
                    if (!existing) {
                        this[type].unshift(finalVal);
                    }
                    if (persist) {
                        window.mailOptionSync?.restore(type, finalVal);
                    }
                    return finalVal;
                },
                removeOption(type, val) {
                    const key = String(val ?? '').trim().toLowerCase();
                    if (this[type]) {
                        this[type] = this[type].filter(item => String(item).trim().toLowerCase() !== key);
                    }
                    window.mailOptionSync?.remove(type, val);
                }
            });

            Alpine.data('multiRecipientSelect', ({ listType, initialValue }) => {
                const parseTags = (raw) => {
                    if (!raw) return [];
                    if (Array.isArray(raw)) return raw.map(s => String(s).trim()).filter(Boolean);
                    return String(raw).split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
                };

                return {
                    open: false,
                    search: '',
                    tags: parseTags(initialValue),
                    init() {
                        // Tidak memasukkan ulang tag awal ke store agar opsi yang pernah dihapus tidak muncul kembali
                    },
                    get value() {
                        return this.tags.join(', ');
                    },
                    get options() {
                        return Alpine.store('mailOptions').getOptions(listType);
                    },
                    get filteredOptions() {
                        const kw = this.search.trim().toLowerCase();
                        if (!kw) return this.options;
                        return this.options.filter(opt => String(opt).toLowerCase().includes(kw));
                    },
                    isSelected(opt) {
                        const target = String(opt).toLowerCase();
                        return this.tags.some(t => t.toLowerCase() === target);
                    },
                    toggle() {
                        this.open = !this.open;
                        if (this.open) {
                            this.$nextTick(() => this.$refs.searchInput?.focus());
                        }
                    },
                    closeDropdown() {
                        if (this.search.trim() !== '') {
                            this.commitSearch();
                        }
                        this.open = false;
                    },
                    addTag(opt) {
                        const clean = String(opt || '').trim();
                        if (!clean) return;
                        if (!this.isSelected(clean)) {
                            this.tags.push(clean);
                        }
                    },
                    removeTag(index) {
                        this.tags.splice(index, 1);
                    },
                    toggleOption(opt) {
                        const target = String(opt).toLowerCase();
                        const idx = this.tags.findIndex(t => t.toLowerCase() === target);
                        if (idx !== -1) {
                            this.tags.splice(idx, 1);
                        } else {
                            this.tags.push(opt);
                        }
                        this.search = '';
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    },
                    addNewOption(newOpt) {
                        const parts = String(newOpt || '').split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
                        parts.forEach(part => {
                            const registered = Alpine.store('mailOptions').addOption(listType, part);
                            if (registered) {
                                this.addTag(registered);
                            }
                        });
                        this.search = '';
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    },
                    removeOption(opt) {
                        Alpine.store('mailOptions').removeOption(listType, opt);
                        const target = String(opt).toLowerCase();
                        this.tags = this.tags.filter(t => t.toLowerCase() !== target);
                    },
                    commitSearch() {
                        const kw = this.search.trim();
                        if (!kw) return;
                        this.addNewOption(kw);
                    }
                };
            });

            Alpine.data('searchableSelect', ({ listType, initialValue }) => ({
                open: false,
                value: initialValue || '',
                get options() {
                    return Alpine.store('mailOptions').getOptions(listType);
                },
                get filteredOptions() {
                    if (!this.value) return this.options;
                    return this.options.filter(opt => opt.toLowerCase().includes(this.value.toLowerCase()));
                },
                select(opt) {
                    this.value = opt;
                    this.open = false;
                },
                remove(opt) {
                    Alpine.store('mailOptions').removeOption(listType, opt);
                    if (this.value === opt) {
                        this.value = '';
                    }
                }
            }));

            Alpine.data('outgoingMailBatchForm', () => {
                const rawOldDocs = @json(array_values(old('documents', [])));
                const oldDocs = Array.isArray(rawOldDocs) ? rawOldDocs : Object.values(rawOldDocs || {});
                let nextFormKey = 0;
                const initialDocs = (oldDocs && oldDocs.length > 0)
                    ? oldDocs.map((d, i) => ({
                        id: Date.now() + i,
                        formKey: nextFormKey++,
                        mail_number: (d && d.mail_number) ? String(d.mail_number) : '',
                        subject: (d && d.subject) ? String(d.subject) : '',
                        recipient: (d && d.recipient) ? String(d.recipient) : '',
                        status: (d && d.status) ? String(d.status) : '',
                        disposition_note: (d && d.disposition_note) ? String(d.disposition_note) : ''
                    }))
                    : [
                        { id: Date.now(), formKey: nextFormKey++, mail_number: '', subject: '', recipient: '', status: '', disposition_note: '' }
                    ];

                return {
                    loading: false,
                    documents: initialDocs,
                    addDocument() {
                        this.documents.push({
                            id: Date.now() + Math.random(),
                            formKey: nextFormKey++,
                            mail_number: '',
                            subject: '',
                            recipient: '',
                            status: '',
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
                };
            });
        });
    </script>
@endsection

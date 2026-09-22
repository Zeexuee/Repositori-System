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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Dari (Pengirim) -->
                    <div x-data="searchableSelect({
                        initialValue: {{ json_encode(old('sender', '')) }},
                        defaultOptions: {{ json_encode($senders ?? []) }}
                    })" class="relative" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Dari / Biro Pengirim <span class="text-slate-900 font-bold">*</span>
                        </label>
                        <input type="hidden" name="sender" :value="value">

                        <!-- Trigger Button -->
                        <button type="button" 
                                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
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
                                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">×</span>
                            </div>

                            <div class="max-h-44 overflow-y-auto space-y-1">
                                <template x-for="item in filteredOptions" :key="item">
                                    <button type="button" 
                                            @click="selectOption(item)" 
                                            class="w-full text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                            :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': value === item }">
                                        <span x-text="item"></span>
                                        <span x-show="value === item" class="text-xs">✓</span>
                                    </button>
                                </template>

                                <div x-show="search.trim() !== '' && !filteredOptions.map(o => o.toLowerCase()).includes(search.trim().toLowerCase())" class="pt-1 border-t border-slate-100">
                                    <button type="button" 
                                            @click="addNewOption(search.trim())" 
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg flex items-center space-x-1.5 transition-colors cursor-pointer">
                                        <span>Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Masuk -->
                    <div>
                        <label for="received_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Masuk <span class="text-slate-900 font-bold">*</span></label>
                        <input type="date" name="received_date" id="received_date" value="{{ old('received_date', date('Y-m-d')) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    </div>

                    <!-- Nama Penerima -->
                    <div>
                        <label for="recipient_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Penerima <span class="text-slate-900 font-bold">*</span>
                        </label>
                        <input type="text" name="recipient_name" id="recipient_name" value="{{ old('recipient_name') }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
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
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Kepada / Penerima Dokumen <span class="text-slate-900 font-bold" x-show="!isDraft">*</span>
                                </label>
                                <input type="text" 
                                       list="recipient-presets"
                                       :name="'documents[' + index + '][recipient]'" 
                                       x-model="doc.recipient" 
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
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

                        <!-- Upload Berkas / Foto Dokumen (Satu Tombol untuk PDF atau Foto Kamera) -->
                        <div class="pt-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Upload Dokumen (PDF / Foto Kamera)
                            </label>
                            <input type="file" 
                                   :name="'documents[' + index + '][file]'" 
                                   accept="application/pdf,image/*" 
                                   capture="environment" 
                                   class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
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
                    <!-- Tombol Simpan Draft -->
                    <button type="submit" 
                            @click="isDraft = true" 
                            :disabled="loading" 
                            class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-all shadow-2xs cursor-pointer">
                        <span>Simpan Sebagai Draft</span>
                    </button>

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

        <!-- Datalist Options for Recipients -->
        <datalist id="recipient-presets">
            @foreach ($recipients ?? [] as $recipientOption)
                <option value="{{ $recipientOption }}"></option>
            @endforeach
        </datalist>
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
                onSubmit(e) {
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

                    // Validasi input wajib: Nama Penerima
                    const recipientNameInput = this.$refs.form.querySelector('input[name="recipient_name"]');
                    const recipientNameVal = recipientNameInput ? recipientNameInput.value.trim() : '';
                    if (!recipientNameVal) {
                        errors.push('Nama Penerima wajib diisi.');
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
                    // Cegah submit agar form TIDAK reload, TIDAK menghapus file/data yang sudah diketik, dan posisi layar TIDAK terkunci
                    if (errors.length > 0) {
                        e.preventDefault();
                        this.errorMessageList = errors;
                        this.loading = false;
                        return false;
                    }

                    this.loading = true;
                }
            };
        }

        function searchableSelect(config) {
            const defaultOpts = Array.isArray(config.defaultOptions) ? [...config.defaultOptions] : [];
            const initVal = config.initialValue || '';
            if (initVal && !defaultOpts.includes(initVal)) {
                defaultOpts.unshift(initVal);
            }

            return {
                open: false,
                search: '',
                value: initVal,
                options: defaultOpts,

                get filteredOptions() {
                    if (!this.search.trim()) {
                        return this.options;
                    }
                    return this.options.filter(opt => 
                        opt.toLowerCase().includes(this.search.trim().toLowerCase())
                    );
                },

                selectOption(opt) {
                    this.value = opt;
                    this.search = '';
                    this.open = false;
                },

                addNewOption(newOpt) {
                    if (!newOpt) return;
                    if (!this.options.includes(newOpt)) {
                        this.options.push(newOpt);
                    }
                    this.value = newOpt;
                    this.search = '';
                    this.open = false;
                }
            };
        }
    </script>
@endsection

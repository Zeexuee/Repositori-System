@extends('layouts.app')

@section('title', 'Pencatatan Surat Masuk')

@section('content')
    <div x-data="incomingMailBatchForm()" class="space-y-6">
        <div class="pb-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2 font-medium" aria-label="Breadcrumb">
                    <a href="{{ route('incoming-mails.index') }}" class="hover:text-slate-900 hover:underline transition-colors">
                        Surat Masuk
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-800 font-semibold">Tambah Dokumen Baru</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Form Pencatatan Surat Masuk</h1>
                <p class="text-xs text-slate-500 mt-1">Catat satu atau beberapa dokumen dari pengirim/biro yang sama dalam satu tanda terima.</p>
            </div>
            <div class="flex items-center space-x-2.5">
                <a href="{{ route('incoming-mails.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                    ← Kembali ke Daftar
                </a>
                <div class="inline-flex items-center px-3 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold shadow-xs space-x-1.5">
                    <span class="text-slate-400">Total:</span>
                    <span class="font-mono text-white" x-text="documents.length">1</span>
                </div>
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
                        <input type="datetime-local" name="received_date" id="received_date" 
                            value="{{ old('received_date', now()->format('Y-m-d\TH:i')) }}" 
                            required 
                            class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        @error('received_date')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
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

                <div x-ref="docList" class="space-y-4">
                    <template x-for="(doc, index) in documents" :key="doc.id">
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-5 relative transition-all duration-300"
                             :data-doc-id="doc.id"
                             :class="{ 'animate-stack-in ring-2 ring-slate-900/10 border-slate-300': doc.isNew }"
                             :style="'z-index: ' + doc.docNumber">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 h-6 rounded-lg bg-slate-900 text-white text-xs font-bold flex items-center justify-center font-mono" x-text="doc.docNumber"></span>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="'Dokumen Surat Masuk #' + doc.docNumber"></h3>
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
                                       :name="'documents[' + doc.formKey + '][mail_number]'" 
                                       x-model="doc.mail_number" 
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                            </div>

                            <!-- Kepada (Penerima) Dokumen Ini (Multi-Recipient Chip Combobox) -->
                            <div x-data="multiRecipientSelect({
                                initialValue: Alpine.raw(doc).recipient,
                                listType: 'recipients'
                            })" class="relative" @click.away="closeDropdown()" x-effect="doc.recipient = value">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Kepada / Penerima Dokumen <span class="text-slate-900 font-bold" x-show="!isDraft">*</span>
                                </label>
                                <input type="hidden" :name="'documents[' + doc.formKey + '][recipient]'" :value="value">

                                <!-- Trigger Box with Chips -->
                                <div @click="toggle()"
                                     class="w-full min-h-[42px] px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-left text-sm text-slate-900 focus-within:ring-2 focus-within:ring-slate-900 flex items-center justify-between gap-2 shadow-2xs transition-all cursor-pointer">
                                    <div class="flex flex-wrap items-center gap-1.5 flex-1">
                                        <template x-for="(tag, tIdx) in tags" :key="tag + '_' + tIdx">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-900 text-white shadow-2xs">
                                                <span x-text="tag"></span>
                                                <button type="button" @click.stop="removeTag(tIdx)" class="text-slate-300 hover:text-white leading-none cursor-pointer" title="Hapus penerima ini">&#215;</button>
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
                                     class="absolute z-30 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                                    
                                    <div class="relative">
                                        <input x-ref="searchInput" 
                                               type="text" 
                                               x-model="search"
                                               @keydown.enter.prevent.stop="commitSearch()"
                                               @keydown.comma.prevent.stop="commitSearch()"
                                               @keydown.escape.prevent.stop="closeDropdown()"
                                               placeholder="Ketik nama penerima lalu tekan Enter..."
                                               class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                        <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">&#215;</span>
                                    </div>

                                    <div class="max-h-44 overflow-y-auto space-y-1">
                                        <!-- Registrasi on-the-fly: WAJIB berada di urutan paling atas -->
                                        <div class="pb-1 border-b border-slate-100">
                                            <button type="button"
                                                    @click="search.trim() !== '' ? addNewOption(search.trim()) : $refs.searchInput.focus()"
                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer">
                                                <span x-show="search.trim() === ''">+ Ketik untuk Tambah Penerima Baru</span>
                                                <span x-show="search.trim() !== ''">+ Tambahkan "<span x-text="search.trim()"></span>"</span>
                                            </button>
                                        </div>

                                        <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar penerima yang ditambahkan</p>
                                        <template x-for="item in filteredOptions" :key="item">
                                            <div class="flex items-center space-x-1">
                                                <button type="button" 
                                                        @click="toggleOption(item)" 
                                                        class="flex-1 text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                                        :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': isSelected(item) }">
                                                    <span x-text="item"></span>
                                                    <span x-show="isSelected(item)" class="text-xs">&#10003;</span>
                                                </button>
                                                <button type="button" 
                                                        @click.stop="removeOption(item)" 
                                                        class="p-2 text-slate-400 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                                                        title="Hapus dari daftar saran">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Perihal -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Perihal <span class="text-slate-900 font-bold" x-show="!isDraft">*</span>
                                </label>
                                <input type="text" 
                                       :name="'documents[' + doc.formKey + '][subject]'" 
                                       x-model="doc.subject" 
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                            </div>

                            <!-- Status (Inputan Admin - Opsional) -->
                            <div x-data="searchableSelect({
                                initialValue: Alpine.raw(doc).status,
                                listType: 'statuses'
                            })" class="relative" @click.away="open = false" x-effect="doc.status = value">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Status <span class="text-slate-400 font-normal normal-case">(Opsional)</span>
                                </label>
                                <input type="hidden" :name="'documents[' + doc.formKey + '][status]'" :value="value">

                                <!-- Trigger Button -->
                                <button type="button" 
                                        @click="toggle()"
                                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all cursor-pointer">
                                    <span x-text="value ? value : 'Pilih / Ketik Status...'" :class="{ 'text-slate-400': !value, 'text-slate-900 font-bold': value }"></span>
                                    <div class="flex items-center space-x-1.5">
                                        <span x-show="value" @click.stop="value = ''" class="text-slate-400 hover:text-slate-700 text-xs font-bold px-1" title="Kosongkan status">&#215;</span>
                                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
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
                                               placeholder="Cari atau tulis status baru..."
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
                                                <span x-show="search.trim() !== '' && !hasExactMatch">+ Tambah "<span x-text="search.trim()"></span>" sebagai status baru</span>
                                                <span x-show="search.trim() !== '' && hasExactMatch">Status "<span x-text="search.trim()"></span>" sudah terdaftar</span>
                                            </button>
                                        </div>

                                        <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar status yang ditambahkan</p>
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Disposisi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                                <textarea :name="'documents[' + doc.formKey + '][disposition_note]'" x-model="doc.disposition_note" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 shadow-2xs focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
                            </div>

                            <!-- Keterangan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan / Catatan Tambahan</label>
                                <textarea :name="'documents[' + doc.formKey + '][notes]'" x-model="doc.notes" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 shadow-2xs focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
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
                                        Pilih 1 atau beberapa dokumen (PDF / Foto Kamera, maks. 10MB per file)
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
                    </div>
                </template>
                </div>

                <div x-show="documents.length > 1" class="flex justify-center pt-1" x-cloak>
                    <button type="button" @click="addDocument()" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl text-xs font-semibold transition-all shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Dokumen Lain dari Biro Ini</span>
                    </button>
                </div>
            </div>

            <!-- Action Buttons: Simpan Sebagai Draft vs Finalisasi -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200/80">
                <a href="{{ route('incoming-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                    ← Batal & Kembali
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
            let nextFormKey = 0;
            const defaultDoc = [
                { id: Date.now(), formKey: nextFormKey++, docNumber: 1, mail_number: '', recipient: '', subject: '', status: '', disposition_note: '', notes: '' }
            ];
            const totalOld = (oldDocs && oldDocs.length > 0) ? oldDocs.length : 0;
            const initialDocs = totalOld > 0
                ? oldDocs.map((d, i) => ({ 
                    id: Date.now() + i, 
                    formKey: nextFormKey++,
                    docNumber: totalOld - i,
                    mail_number: (d && d.mail_number !== undefined && d.mail_number !== null) ? String(d.mail_number) : '', 
                    recipient: (d && d.recipient !== undefined && d.recipient !== null) ? String(d.recipient) : '', 
                    subject: (d && d.subject !== undefined && d.subject !== null) ? String(d.subject) : '', 
                    status: (d && d.status !== undefined && d.status !== null) ? String(d.status) : '', 
                    disposition_note: (d && d.disposition_note !== undefined && d.disposition_note !== null) ? String(d.disposition_note) : '', 
                    notes: (d && d.notes !== undefined && d.notes !== null) ? String(d.notes) : '' 
                }))
                : defaultDoc;

            return {
                loading: false,
                isDraft: {{ old('is_draft') ? 'true' : 'false' }},
                documents: initialDocs,
                errorMessageList: {{ json_encode($errors->all()) }},

                syncDocNumbers() {
                    const total = this.documents.length;
                    this.documents.forEach((doc, idx) => {
                        doc.docNumber = total - idx;
                    });
                },
                getDocNumber(index) {
                    return this.documents.length - index;
                },
                getFormIndex(index) {
                    const doc = this.documents[index];
                    return doc && doc.formKey !== undefined ? doc.formKey : (this.documents.length - 1 - index);
                },
                addDocument() {
                    const newDoc = {
                        id: Date.now() + Math.random(),
                        formKey: nextFormKey++,
                        docNumber: this.documents.length + 1,
                        mail_number: '',
                        recipient: '',
                        subject: '',
                        status: '',
                        disposition_note: '',
                        notes: '',
                        isNew: true
                    };
                    this.documents.unshift(newDoc);
                    this.syncDocNumbers();

                    this.$nextTick(() => {
                        const firstDoc = this.$refs.docList?.querySelector('.animate-stack-in') || this.$refs.docList?.querySelector('.bg-white');
                        if (firstDoc) {
                            firstDoc.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            const firstInput = firstDoc.querySelector('input[type="text"]');
                            if (firstInput) {
                                setTimeout(() => firstInput.focus(), 250);
                            }
                        }
                    });
                },
                removeDocument(index) {
                    if (this.documents.length > 1) {
                        this.documents.splice(index, 1);
                        this.syncDocNumbers();
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
                            const num = doc.docNumber || this.getDocNumber(idx);
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
                        const formData = new FormData();
                        const csrfToken = this.$refs.form.querySelector('input[name="_token"]')?.value;
                        if (csrfToken) {
                            formData.append('_token', csrfToken);
                        }
                        formData.append('is_draft', this.isDraft ? '1' : '0');
                        formData.append('sender', senderVal);
                        formData.append('received_date', dateInput.value.trim());

                        // Susun seluruh dokumen secara berurutan (0, 1, 2, ...) dari Dokumen #1 hingga Dokumen terakhir
                        const orderedDocs = [...this.documents].reverse();
                        orderedDocs.forEach((doc, idx) => {
                            formData.append(`documents[${idx}][mail_number]`, doc.mail_number ? String(doc.mail_number).trim() : '');
                            formData.append(`documents[${idx}][recipient]`, doc.recipient ? String(doc.recipient).trim() : '');
                            formData.append(`documents[${idx}][subject]`, doc.subject ? String(doc.subject).trim() : '');
                            formData.append(`documents[${idx}][status]`, doc.status ? String(doc.status).trim() : '');
                            formData.append(`documents[${idx}][disposition_note]`, doc.disposition_note ? String(doc.disposition_note) : '');
                            formData.append(`documents[${idx}][notes]`, doc.notes ? String(doc.notes) : '');

                            const cardEl = this.$refs.docList?.querySelector(`[data-doc-id="${doc.id}"]`);
                            const fileInput = cardEl?.querySelector('input[type="file"]');
                            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                                Array.from(fileInput.files).forEach(file => {
                                    formData.append(`documents[${idx}][files][]`, file);
                                });
                            }
                        });

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
                statuses: @json(collect($statuses ?? \App\Support\MailCustomStatus::DEFAULTS)->filter()->unique()->values()),

                list(type) {
                    if (type === 'recipients') return this.recipients;
                    if (type === 'statuses') return this.statuses;
                    return this.senders;
                },

                add(type, option, persist = true) {
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

                    // Registrasi ulang opsi yang pernah dihapus: hapus dari daftar tersembunyi di server.
                    if (persist) {
                        window.mailOptionSync?.restore(type, value);
                    }

                    return value;
                },

                remove(type, option) {
                    const key = String(option ?? '').trim().toLowerCase();
                    const keep = item => String(item).trim().toLowerCase() !== key;
                    if (type === 'senders') {
                        this.senders = this.senders.filter(keep);
                    } else if (type === 'recipients') {
                        this.recipients = this.recipients.filter(keep);
                    } else if (type === 'statuses') {
                        this.statuses = this.statuses.filter(keep);
                    }

                    // Simpan penghapusan secara permanen agar tidak muncul lagi saat halaman dimuat ulang.
                    window.mailOptionSync?.remove(type, option);
                },
            });
        });

        function searchableSelect(config) {
            const settings = config || {};

            return {
                open: false,
                search: '',
                value: settings.initialValue ? String(settings.initialValue) : '',
                listType: ['recipients', 'statuses'].includes(settings.listType) ? settings.listType : 'senders',

                init() {
                    // Nilai awal tetap tampil pada tombol pemilih tanpa dimasukkan kembali
                    // ke daftar riwayat, sehingga opsi yang sudah dihapus tidak muncul lagi.
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

        function multiRecipientSelect(config) {
            const settings = config || {};
            const parseTags = (raw) => {
                if (!raw) return [];
                if (Array.isArray(raw)) return raw.map(s => String(s).trim()).filter(Boolean);
                return String(raw).split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
            };

            return {
                open: false,
                search: '',
                tags: parseTags(settings.initialValue),
                listType: settings.listType || 'recipients',

                init() {
                    // Chip penerima awal tetap tampil tanpa dimasukkan kembali ke daftar riwayat.
                },

                get value() {
                    return this.tags.join(', ');
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

                isSelected(option) {
                    const target = String(option).toLowerCase();
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

                addTag(option) {
                    const clean = String(option || '').trim();
                    if (!clean) return;
                    if (!this.isSelected(clean)) {
                        this.tags.push(clean);
                    }
                },

                removeTag(index) {
                    this.tags.splice(index, 1);
                },

                toggleOption(option) {
                    const target = String(option).toLowerCase();
                    const existingIdx = this.tags.findIndex(t => t.toLowerCase() === target);
                    if (existingIdx !== -1) {
                        this.tags.splice(existingIdx, 1);
                    } else {
                        this.tags.push(option);
                    }
                    this.search = '';
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                },

                addNewOption(newOption) {
                    const parts = String(newOption || '').split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
                    parts.forEach(part => {
                        const registered = this.$store.mailOptions.add(this.listType, part);
                        if (registered !== '') {
                            this.addTag(registered);
                        }
                    });
                    this.search = '';
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                },

                removeOption(option) {
                    this.$store.mailOptions.remove(this.listType, option);
                    const target = String(option).toLowerCase();
                    this.tags = this.tags.filter(t => t.toLowerCase() !== target);
                },

                commitSearch() {
                    const keyword = this.search.trim();
                    if (keyword === '') return;

                    const parts = keyword.split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
                    parts.forEach(part => {
                        const match = this.options.find(option => String(option).toLowerCase() === part.toLowerCase());
                        if (match !== undefined) {
                            this.addTag(match);
                        } else {
                            const registered = this.$store.mailOptions.add(this.listType, part);
                            if (registered !== '') {
                                this.addTag(registered);
                            }
                        }
                    });
                    this.search = '';
                }
            };
        }
    </script>
@endsection











@extends('layouts.app')

@section('title', $incomingMail->status === 'DRAFT' ? 'Finalisasi Draf Surat Masuk' : 'Edit Surat Masuk')
@section('hide_header', true)

@section('content')
    <div x-data="incomingMailEditBatchForm()" class="space-y-6">
        <div class="pb-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ $incomingMail->status === 'DRAFT' ? 'Finalisasi Draf Surat Masuk' : 'Edit Surat Masuk' }}
                    </h1>
                    @if ($incomingMail->status === 'DRAFT')
                        <span class="px-2.5 py-0.5 text-xs font-bold bg-slate-900 text-white rounded-lg">
                            DRAFT
                        </span>
                    @elseif ($incomingMail->revision_count > 0 || $incomingMail->status === 'REVISI')
                        <span class="px-2.5 py-0.5 text-xs font-bold bg-amber-50 text-amber-900 border border-amber-300 rounded-lg font-mono">
                            R [{{ $incomingMail->revision_count ?: 1 }}]
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $incomingMail->status === 'DRAFT' 
                        ? 'Lengkapi data surat masuk, tambah dokumen lain bila ada, dan selesaikan finalisasi dokumen.' 
                        : 'Perbarui data surat masuk atau tambahkan berkas tambahan dalam tanda terima ini.' }}
                </p>
            </div>
            <div class="flex items-center space-x-2.5">
                <a href="{{ route('incoming-mails.index', $incomingMail->status === 'DRAFT' ? ['status_filter' => 'draft'] : []) }}"
                    class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 shadow-2xs transition-all">
                    Batal
                </a>
                <div class="inline-flex items-center px-3.5 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold shadow-xs space-x-1.5">
                    <span class="text-slate-400">Total Dokumen:</span>
                    <span class="font-mono text-white" x-text="documents.length">1</span>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-2">
                <p class="font-bold text-rose-900">Gagal menyimpan pembaruan. Silakan periksa kembali input berikut:</p>
                <ul class="list-disc list-inside space-y-1 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('incoming-mails.update', $incomingMail) }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-ref="form" @submit="onSubmit($event)">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_draft" x-model="isDraft">

            <!-- Section 1: Informasi Tanda Terima & Pengirim -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Informasi Utama & Pengirim</span>
                    </h2>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg">
                        Pengirim Terkunci Untuk Seluruh Dokumen
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Dari (Pengirim) -->
                    <div x-data="searchableSelect({
                        initialValue: '{{ old('sender', $incomingMail->sender) }}',
                        defaultOptions: {{ json_encode($presetSenders ?? []) }}
                    })" class="relative md:col-span-1" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Dari / Biro Pengirim <span class="text-rose-500">*</span>
                        </label>
                        <input type="hidden" name="sender" :value="value" required>

                        <!-- Trigger Button -->
                        <button type="button" 
                                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all">
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
                                       placeholder="Ketik nama biro / divisi..." 
                                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">×</span>
                            </div>

                            <div class="max-h-44 overflow-y-auto space-y-1">
                                <template x-for="item in filteredOptions" :key="item">
                                    <button type="button" 
                                            @click="selectOption(item)" 
                                            class="w-full text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors"
                                            :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': value === item }">
                                        <span x-text="item"></span>
                                        <span x-show="value === item" class="text-xs">✓</span>
                                    </button>
                                </template>

                                <div x-show="search.trim() !== '' && !filteredOptions.map(o => o.toLowerCase()).includes(search.trim().toLowerCase())" class="pt-1 border-t border-slate-100">
                                    <button type="button" 
                                            @click="addNewOption(search.trim())" 
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg flex items-center space-x-1.5 transition-colors">
                                        <span>Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('sender')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tanggal Masuk -->
                    <div>
                        <label for="received_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Masuk <span class="text-rose-500">*</span></label>
                        <input type="date" name="received_date" id="received_date" value="{{ old('received_date', $incomingMail->received_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        @error('received_date')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kepada (Penerima) -->
                    <div x-data="searchableSelect({
                        initialValue: '{{ old('recipient', $incomingMail->recipient) }}',
                        defaultOptions: {{ json_encode($presetRecipients ?? []) }}
                    })" class="relative" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kepada / Penerima
                        </label>
                        <input type="hidden" name="recipient" :value="value">

                        <button type="button" 
                                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all">
                            <span x-text="value ? value : 'Pilih / Cari Penerima...'" :class="{ 'text-slate-400': !value, 'text-slate-900 font-semibold': value }"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute z-30 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                            <div class="relative">
                                <input x-ref="searchInput" type="text" x-model="search" placeholder="Ketik untuk mencari..." class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">×</span>
                            </div>

                            <div class="max-h-44 overflow-y-auto space-y-1">
                                <template x-for="item in filteredOptions" :key="item">
                                    <button type="button" @click="selectOption(item)" class="w-full text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors" :class="{ 'bg-slate-900 text-white font-bold': value === item }">
                                        <span x-text="item"></span>
                                        <span x-show="value === item" class="text-xs">✓</span>
                                    </button>
                                </template>

                                <div x-show="search.trim() !== '' && !filteredOptions.map(o => o.toLowerCase()).includes(search.trim().toLowerCase())" class="pt-1 border-t border-slate-100">
                                    <button type="button" 
                                            @click="addNewOption(search.trim())" 
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg flex items-center space-x-1.5 transition-colors">
                                        <span>Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                    <!-- Nama Penerima Petugas -->
                    <div>
                        <label for="recipient_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Petugas Penerima</label>
                        <input type="text" name="recipient_name" id="recipient_name" value="{{ old('recipient_name', $incomingMail->recipient_name ?? auth()->user()?->name) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    </div>

                    <!-- Signature Pad -->
                    <div x-data="signaturePad('{{ $incomingMail->receipt_signature_path ? route('document.download', ['path' => $incomingMail->receipt_signature_path, 'inline' => 1]) : '' }}')" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Tanda Tangan Pengantar</span>
                        </label>
                        
                        <div class="bg-slate-50 p-3 border border-slate-200 rounded-xl space-y-3">
                            <div class="relative bg-white rounded-xl border border-slate-300 overflow-hidden shadow-2xs">
                                <canvas x-ref="canvas" 
                                        width="600" 
                                        height="160" 
                                        @mousedown="onStart($event)"
                                        @mousemove="onMove($event)"
                                        @mouseup="onEnd($event)"
                                        @mouseleave="onEnd($event)"
                                        @touchstart.prevent="onStart($event)"
                                        @touchmove.prevent="onMove($event)"
                                        @touchend.prevent="onEnd($event)"
                                        class="w-full h-32 touch-none cursor-crosshair block bg-white"></canvas>
                            </div>
                            
                            <input type="hidden" name="receipt_signature" :value="signatureBase64">

                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-600" x-text="hasSignature ? 'Tanda tangan terisi' : 'Belum ditandatangani'"></span>
                                <button type="button" 
                                        @click="clearCanvas()" 
                                        class="px-3 py-1 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-all cursor-pointer">
                                    Bersihkan / Gambar Ulang
                                </button>
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

                    <!-- Tombol Tambah Dokumen Lain -->
                    <button type="button" @click="addDocument()" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-100 border border-slate-200 text-slate-900 hover:bg-slate-200 rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer">
                        <span>+ Tambah Dokumen Lain dari Biro Ini</span>
                    </button>
                </div>

                <template x-for="(doc, index) in documents" :key="doc.temp_id || doc.id">
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-5 relative">
                        <input type="hidden" :name="'documents[' + index + '][id]'" :value="doc.id">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-lg bg-slate-900 text-white text-xs font-bold flex items-center justify-center font-mono" x-text="index + 1"></span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="'Dokumen Surat Masuk #' + (index + 1)"></h3>
                                <span x-show="doc.id" class="text-[10px] bg-slate-100 text-slate-600 font-mono px-2 py-0.5 rounded border border-slate-200">Tersimpan</span>
                            </div>

                            <button type="button" 
                                    x-show="documents.length > 1" 
                                    @click="removeDocument(index)" 
                                    class="text-xs text-rose-600 hover:text-rose-800 font-semibold px-2 py-1 rounded-lg hover:bg-rose-50 transition-all flex items-center space-x-1 cursor-pointer">
                                <span>Hapus Dokumen Ini</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nomor Surat -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor Surat <span class="text-rose-500" x-show="!isDraft">*</span>
                                </label>
                                <input type="text" 
                                       :name="'documents[' + index + '][mail_number]'" 
                                       x-model="doc.mail_number" 
                                       placeholder="Misal: 045/DIR-KEU/VIII/2026"
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                            </div>

                            <!-- Tanggal Surat -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Surat</label>
                                <input type="date" 
                                       :name="'documents[' + index + '][outgoing_date]'" 
                                       x-model="doc.outgoing_date" 
                                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                            </div>
                        </div>

                        <!-- Perihal -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Perihal <span class="text-rose-500" x-show="!isDraft">*</span>
                            </label>
                            <input type="text" 
                                   :name="'documents[' + index + '][subject]'" 
                                   x-model="doc.subject" 
                                   placeholder="Uraian ringkas pokok perihal surat..."
                                   class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-medium">
                        </div>

                        <!-- Disposisi & Keterangan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Isi Ringkas Disposisi</label>
                                <textarea :name="'documents[' + index + '][disposition_note]'" 
                                          x-model="doc.disposition_note"
                                          rows="2" 
                                          placeholder="Petunjuk atau disposisi pejabat..." 
                                          class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan Tambahan</label>
                                <textarea :name="'documents[' + index + '][notes]'" 
                                          x-model="doc.notes"
                                          rows="2" 
                                          placeholder="Catatan tambahan bila ada..." 
                                          class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs"></textarea>
                            </div>
                        </div>

                        <!-- Upload Berkas / Foto Dokumen -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                            <!-- Berkas PDF -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Upload Dokumen (PDF / Scan)</label>
                                <input type="file" 
                                       :name="'documents[' + index + '][file]'" 
                                       accept=".pdf,.jpg,.jpeg,.png,.webp"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition-all shadow-2xs">
                                <template x-if="doc.file_path">
                                    <p class="text-[11px] text-slate-500 mt-1 truncate">Berkas saat ini: <span class="font-mono" x-text="doc.file_path"></span></p>
                                </template>
                            </div>

                            <!-- Foto Fisik Dokumen -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Foto Dokumen Fisik (Gambar)</label>
                                <input type="file" 
                                       :name="'documents[' + index + '][document_photo]'" 
                                       accept="image/*"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition-all shadow-2xs">
                                <template x-if="doc.document_photo_path">
                                    <p class="text-[11px] text-slate-500 mt-1 truncate">Foto saat ini: <span class="font-mono" x-text="doc.document_photo_path"></span></p>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Action Buttons Footer -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200">
                <a href="{{ route('incoming-mails.index', $incomingMail->status === 'DRAFT' ? ['status_filter' => 'draft'] : []) }}" 
                   class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition-all text-center">
                    Batal
                </a>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <!-- Tombol Simpan Draf -->
                    <button type="submit" 
                            @click="isDraft = true"
                            class="w-full sm:w-auto px-5 py-2.5 bg-white border border-slate-300 text-slate-800 hover:bg-slate-50 rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer">
                        Simpan Sebagai Draf
                    </button>

                    <!-- Tombol Finalisasi / Simpan Dokumen -->
                    <button type="submit" 
                            @click="isDraft = false"
                            class="w-full sm:w-auto px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition-all cursor-pointer">
                        {{ $incomingMail->status === 'DRAFT' ? 'Finalisasi & Simpan Surat Masuk' : 'Simpan Perubahan Dokumen' }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function incomingMailEditBatchForm() {
            const initialDocs = {{ Js::from($formattedDocuments ?? []) }};

            return {
                isDraft: {{ $incomingMail->status === 'DRAFT' ? 'true' : 'false' }},
                documents: initialDocs && initialDocs.length > 0 ? initialDocs : [
                    {
                        id: '{{ $incomingMail->id }}',
                        mail_number: '{{ $incomingMail->mail_number }}',
                        outgoing_date: '{{ $incomingMail->outgoing_date?->format('Y-m-d') ?? '' }}',
                        subject: '{{ $incomingMail->subject }}',
                        disposition_note: '{{ $incomingMail->disposition_note ?? '' }}',
                        notes: '{{ $incomingMail->notes ?? '' }}',
                        status: '{{ $incomingMail->status }}',
                        file_path: '{{ $incomingMail->file_path }}',
                        document_photo_path: '{{ $incomingMail->document_photo_path }}'
                    }
                ],

                addDocument() {
                    this.documents.push({
                        id: null,
                        temp_id: Date.now() + Math.random(),
                        mail_number: '',
                        outgoing_date: '',
                        subject: '',
                        disposition_note: '',
                        notes: '',
                        status: this.isDraft ? 'DRAFT' : 'RECEIVE',
                        file_path: null,
                        document_photo_path: null
                    });
                },

                removeDocument(index) {
                    if (this.documents.length > 1) {
                        this.documents.splice(index, 1);
                    }
                },

                onSubmit(e) {
                    // Allowed submit
                }
            };
        }

        function searchableSelect(config) {
            return {
                open: false,
                search: '',
                value: config.initialValue || '',
                options: config.defaultOptions || [],

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

        function signaturePad(initialSignatureUrl) {
            return {
                isDrawing: false,
                hasSignature: false,
                signatureBase64: '',
                ctx: null,

                init() {
                    this.$nextTick(() => {
                        this.setupCanvas();
                        if (initialSignatureUrl) {
                            this.loadInitialSignature(initialSignatureUrl);
                        }
                    });
                },

                setupCanvas() {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;

                    this.ctx = canvas.getContext('2d');
                    this.ctx.lineWidth = 3;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                    this.ctx.strokeStyle = '#0f172a';
                },

                loadInitialSignature(url) {
                    const img = new Image();
                    img.crossOrigin = "Anonymous";
                    img.onload = () => {
                        const canvas = this.$refs.canvas;
                        if (!canvas || !this.ctx) return;
                        this.ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        this.hasSignature = true;
                        this.signatureBase64 = canvas.toDataURL('image/png');
                    };
                    img.src = url;
                },

                getPos(e) {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return { x: 0, y: 0 };
                    
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;

                    let clientX = e.clientX;
                    let clientY = e.clientY;

                    if (e.touches && e.touches.length > 0) {
                        clientX = e.touches[0].clientX;
                        clientY = e.touches[0].clientY;
                    }

                    return {
                        x: (clientX - rect.left) * scaleX,
                        y: (clientY - rect.top) * scaleY
                    };
                },

                onStart(e) {
                    this.isDrawing = true;
                    if (!this.ctx) this.setupCanvas();
                    const pos = this.getPos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                },

                onMove(e) {
                    if (!this.isDrawing) return;
                    const pos = this.getPos(e);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    this.hasSignature = true;
                },

                onEnd(e) {
                    if (this.isDrawing) {
                        this.isDrawing = false;
                        if (this.$refs.canvas) {
                            this.signatureBase64 = this.$refs.canvas.toDataURL('image/png');
                        }
                    }
                },

                clearCanvas() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || !this.ctx) return;
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasSignature = false;
                    this.signatureBase64 = '';
                }
            };
        }
    </script>
@endsection

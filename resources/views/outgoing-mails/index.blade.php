@extends('layouts.app')

@section('title', 'Daftar Surat Keluar')

@section('content')
    <div x-data="outgoingMailStatusHandler()" x-init="rebindPagination()" class="space-y-4">
        <!-- Success Toast Notification -->
        <div x-show="showSuccessToast" 
            x-cloak
            :role="toastIsError ? 'alert' : 'status'"
            aria-live="polite"
            class="fixed top-20 right-4 z-[9999] max-w-md"
            style="margin: 0 !important;"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4">
            <div class="bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 p-4 flex items-start gap-3">
                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold" x-text="toastIsError ? 'Tidak dapat diproses' : 'Berhasil!'"></p>
                    <p class="text-xs text-slate-300 mt-0.5" x-text="successMessage"></p>
                </div>
                <button @click="showSuccessToast = false" class="flex-shrink-0 text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

<!-- Success Toast Notification -->
        <div x-show="showSuccessToast" 
            x-cloak
            :role="toastIsError ? 'alert' : 'status'"
            aria-live="polite"
            class="fixed top-20 right-4 z-[9999] max-w-md"
            style="margin: 0 !important;"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4">
            <div class="bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 p-4 flex items-start gap-3">
                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold" x-text="toastIsError ? 'Tidak dapat diproses' : 'Berhasil!'"></p>
                    <p class="text-xs text-slate-300 mt-0.5" x-text="successMessage"></p>
                </div>
                <button @click="showSuccessToast = false" class="flex-shrink-0 text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

<!-- Custom Confirmation Modal -->
        <div x-show="showConfirmModal" 
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="margin: 0 !important;"
            @keydown.escape.window="showConfirmModal = false">
            
            <!-- Backdrop - Transparent untuk click outside -->
            <div class="fixed inset-0" 
                @click="showConfirmModal = false"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full border-2 border-slate-900 overflow-hidden"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">
                
                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Konfirmasi Perubahan Status</h3>
                </div>
                
                <!-- Body -->
                <div class="px-6 py-5">
                    <p class="text-sm text-slate-700 leading-relaxed" x-html="confirmMessage"></p>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" 
                        @click="showConfirmModal = false"
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl transition-all disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" 
                        @click="confirmBulkUpdate()"
                        :disabled="isSubmitting"
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span x-show="!isSubmitting">Ya, Ubah Status</span>
                        <span x-show="isSubmitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>

<!-- Teleported Single Status Dropdown (Topmost, outside all clipping containers) -->
        <template x-teleport="body">
            <div x-show="showStatusDropdown" 
                x-cloak
                @click.outside="showStatusDropdown = false"
                class="fixed z-[99999] w-44 bg-white border border-slate-200 rounded-xl shadow-2xl py-1.5 text-left"
                :style="`top: ${statusDropdownPosition.top}; left: ${statusDropdownPosition.left};`"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">
                <div class="px-3.5 py-1 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Status Berikutnya
                </div>
                <div class="py-1" role="menu">
                    <template x-for="option in availableStatusOptions" :key="option.value">
                        <button type="button" role="menuitem" @click="selectStatusOption(option.value)"
                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full flex-shrink-0" :class="statusDotClass(option.value)"></span>
                            <span x-text="option.label"></span>
                        </button>
                    </template>

<!-- Revision Note Modal -->
        <template x-teleport="body">
            <div x-show="showRevisionModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                role="dialog" aria-modal="true" aria-labelledby="revision-note-title"
                @keydown.escape.window="closeRevisionModal()">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-lg overflow-hidden"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <h2 id="revision-note-title" class="text-lg font-bold text-slate-900" x-text="revisionModalTitle"></h2>
                        <p class="text-xs text-slate-500 mt-1" x-text="revisionModalSubtitle"></p>
                    </div>

                    <div class="px-6 py-5 space-y-3">
                        <label for="revision-note-input" class="block text-xs font-bold text-slate-700">
                            <span x-text="revisionIsRequired ? 'Catatan Kebutuhan Revisi' : 'Catatan Penyelesaian Revisi'"></span>
                            <span x-show="revisionIsRequired" class="text-rose-600">*</span>
                            <span x-show="!revisionIsRequired" class="font-normal text-slate-400">(opsional)</span>
                        </label>
                        <textarea id="revision-note-input" x-model="revisionNote" rows="5" maxlength="2000"
                            :required="revisionIsRequired"
                            :aria-required="revisionIsRequired"
                            :placeholder="revisionIsRequired
                                ? 'Jelaskan bagian mana yang perlu diperbaiki.'
                                : 'Jelaskan bagaimana revisi diselesaikan (boleh dikosongkan).'"
                            class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
                        <p class="text-[11px] text-slate-400 text-right">
                            <span x-text="revisionNote.length"></span>/2000
                        </p>
                        <p x-show="revisionError" x-text="revisionError" role="alert" aria-live="assertive"
                            class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800"></p>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" @click="closeRevisionModal()" :disabled="isSubmitting"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl disabled:opacity-50">
                            Batal
                        </button>
                        <button type="button" @click="submitRevisionNote()" :disabled="isSubmitting"
                            class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl disabled:opacity-50 inline-flex items-center gap-2">
                            <svg x-show="isSubmitting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Simpan dan Ubah Status
                        </button>
                    </div>
                </div>
            </div>
        </template>

<!-- Receive Signature Workflow Modal -->
        <template x-teleport="body">
            <div x-show="showReceiveModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-3 sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="receive-workflow-title"
                @keydown.escape.window="closeReceiveModal()">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-5xl max-h-[92vh] overflow-hidden flex flex-col"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    <div class="px-5 sm:px-7 py-5 border-b border-slate-200 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="receive-workflow-title" class="text-lg font-bold text-slate-900">Tanda Tangan Penerimaan Dokumen</h2>
                            <p class="text-xs text-slate-500 mt-1">
                                <span x-text="receiveMailLabel"></span> · <span x-text="receiveMailIds.length"></span> dokumen
                            </p>
                        </div>
                        <button type="button" @click="closeReceiveModal()" :disabled="isSavingReceive"
                            aria-label="Tutup proses penerimaan"
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-5 sm:px-7 py-5 overflow-y-auto bg-slate-50/70 space-y-4">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 leading-relaxed">
                            Tambahkan semua penerima dokumen. Isi nama orang yang menandatangani dan bubuhkan tanda tangan untuk penerima yang sudah mengambil dokumen. Progres yang belum lengkap dapat disimpan melalui <strong>Lanjut Nanti</strong>.
                        </div>

                        <div x-show="isLoadingReceive" class="py-12 flex items-center justify-center gap-3 text-sm font-semibold text-slate-600">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Memuat progres penerimaan...
                        </div>

                        <div x-show="!isLoadingReceive" class="space-y-4">
                            <template x-for="(recipient, index) in receiveRecipients" :key="recipient.client_key">
                                <fieldset class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-4 shadow-2xs">
                                    <legend class="px-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Penerima <span x-text="index + 1"></span>
                                    </legend>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label :for="`receive-recipient-${recipient.client_key}`" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penerima / Unit</label>
                                            <input type="text" :id="`receive-recipient-${recipient.client_key}`"
                                                x-model="recipient.recipient_name" @input="receiveDirty = true" maxlength="255"
                                                placeholder="Contoh: Divisi Keuangan"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900" required>
                                        </div>
                                        <div>
                                            <label :for="`receive-signer-${recipient.client_key}`" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penandatangan</label>
                                            <input type="text" :id="`receive-signer-${recipient.client_key}`"
                                                x-model="recipient.signer_name" @input="receiveDirty = true" maxlength="255"
                                                placeholder="Boleh dikosongkan jika belum hadir"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between gap-3 mb-1.5">
                                            <span class="text-xs font-bold text-slate-700">Tanda Tangan Penerima</span>
                                            <span x-show="recipient.has_signature" class="text-[11px] font-semibold text-slate-700">Tanda tangan tersimpan</span>
                                        </div>
                                        <canvas width="800" height="220"
                                            x-init="$nextTick(() => setupReceiveCanvas(recipient, $el))"
                                            @pointerdown.prevent="startReceiveSignature($event, recipient)"
                                            @pointermove.prevent="drawReceiveSignature($event, recipient)"
                                            @pointerup.prevent="finishReceiveSignature($event, recipient)"
                                            @pointercancel.prevent="finishReceiveSignature($event, recipient)"
                                            aria-label="Area tanda tangan penerima"
                                            class="w-full h-40 bg-white border border-slate-300 rounded-xl touch-none cursor-crosshair"></canvas>
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] text-slate-500">Gunakan mouse, pena digital, atau sentuhan.</p>
                                            <button type="button" @click="clearReceiveSignature(recipient)"
                                                class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100">
                                                Bersihkan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex justify-end" x-show="receiveRecipients.length > 1">
                                        <button type="button" @click="removeReceiveRecipient(index)"
                                            class="text-xs font-semibold text-slate-700 hover:underline">Hapus penerima ini</button>
                                    </div>
                                </fieldset>
                            </template>

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between pb-4 border-b border-slate-200/80 gap-4">
            <div class="flex-shrink-0">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight whitespace-nowrap">Daftar Surat Keluar</h1>
                <p class="text-xs text-slate-500 mt-1">Kelola pencatatan, verifikasi, dan persetujuan surat keluar.</p>
            </div>
            
            <!-- Search Bar -->
            <div class="flex-1 max-w-2xl mx-auto w-full">
                <div class="relative">
                    <input type="text" 
                        x-model="searchQuery"
                        @input.debounce.500ms="performSearch()"
                        placeholder="Cari nomor surat, perihal, penerima..."
                        class="w-full px-5 py-3 bg-white border-2 border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 shadow-sm transition-all">
                    <div x-show="isSearching" class="absolute right-4 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <div x-show="searchQuery.length > 0" class="mt-1 text-xs text-slate-500 text-center">
                    <span x-text="totalResults"></span> dokumen ditemukan
                    <button @click="clearSearch()" class="ml-2 text-slate-700 hover:text-slate-900 font-semibold underline">Reset</button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2.5 flex-shrink-0 mt-4 lg:mt-0">
                <!-- Bulk Action Dropdown -->
                <div x-show="isSelectionMode" x-cloak class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        :disabled="selectedIds.length === 0"
                        :class="selectedIds.length === 0 ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs border border-slate-900">
                        <span>Aksi (<span x-text="selectedIds.length"></span>)</span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="open" 
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="py-1">
                            <p class="px-4 pt-1.5 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                Hanya dokumen yang alurnya sesuai akan diubah
                            </p>
                            <template x-for="option in bulkStatusOptions" :key="option.value">
                                <button type="button" @click="bulkUpdateStatus(option.value); open = false"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                    <span>Ubah ke <span x-text="option.label"></span> (<span x-text="option.eligible"></span>)</span>
                                    <span class="w-2 h-2 rounded-full" :class="statusDotClass(option.value)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <button type="button" @click="toggleSelectionMode()"
                    :class="isSelectionMode ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300 shadow-2xs'"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-xl transition-all border cursor-pointer">
                    <span x-text="isSelectionMode ? 'Batal Select' : 'Select'">Select</span>
                </button>

                @can('create', App\Models\OutgoingMail::class)
                    <a href="{{ route('outgoing-mails.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                        <span>Tambah Surat Keluar</span>
                    </a>
                @endcan
            </div>
        </div>

        <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                            <th x-show="isSelectionMode" x-cloak class="py-4 px-3 text-center whitespace-nowrap w-10">
                                <input type="checkbox"
                                    @change="toggleSelectAll()"
                                    :checked="isAllSelected"
                                    :indeterminate.prop="isIndeterminate"
                                    title="Pilih Semua Dokumen di Halaman Ini"
                                    class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
                            </th>
                            <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap w-14">No.</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nomor Surat</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Dibuat</th>
                            <th class="py-4 px-4 sm:px-5 min-w-[320px]">Subjek / Perihal</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Penerima</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Pembuat</th>
                            <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                            <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm" x-ref="tableBody">
                        @include('outgoing-mails.partials.table-rows')
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6" x-ref="paginationContainer">
            @include('outgoing-mails.partials.pagination')
        </div>
    </div>

    

<script>
        function outgoingMailStatusHandler() {
            return {
                isSelectionMode: false,
                searchQuery: '{{ request('search') }}',
                isSearching: false,
                totalResults: {{ $incomingMails->total() }},
                selectedIds: [],
                allIds: @json($incomingMails->pluck('id')->map(fn($id) => (string) $id)->values()),
                statusById: @js($incomingMails->mapWithKeys(fn ($mail) => [
                    (string) $mail->id => App\Support\OutgoingMailStatus::canonical($mail->status) ?? (string) $mail->status,
                ])),
                statusChangeError: '',
                
                // Modal state
                showConfirmModal: false,
                confirmMessage: '',
                confirmTargetStatus: '',
                confirmMailIds: [],
                isSubmitting: false,
                toastIsError: false,

                // Status transition rules (standard operating procedure)
                statusTransitions: @js(App\Support\OutgoingMailStatus::transitionMap()),

                // Revision note state
                showRevisionModal: false,
                revisionNote: '',
                revisionError: '',
                revisionIsRequired: true,
                revisionTargetStatus: '',
                revisionMailIds: [],
                revisionMailLabel: '',

                // Disposition note state
                showDispositionModal: false,
                dispositionNote: '',
                dispositionError: '',
                dispositionMailIds: [],
                dispositionMailLabel: '',
                dispositionBlockedCount: 0,

                // Receive signature workflow state
                showReceiveModal: false,
                isLoadingReceive: false,
                isSavingReceive: false,
                receiveDirty: false,
                receiveError: '',
                receiveMailIds: [],
                receiveMailLabel: '',
                receiveRecipients: [],
                receiveCanvases: {},
                receiveDrawingKey: null,
                receivePreviousFocus: null,
                
                // Draft Modal state
                showDraftModal: false,
                isLoadingDrafts: false,
                
                // Toast state
                showSuccessToast: false,
                successMessage: '',
                
                // Status dropdown state (teleported to body)
                showStatusDropdown: false,
                statusDropdownPosition: { top: '0px', left: '0px' },
                statusDropdownMailId: null,
                statusDropdownMailNumber: '',
                statusDropdownCurrentStatus: '',
                
                // Watch modal state untuk lock body scroll
                init() {
                    // Setup global functions untuk onclick handler
                    window.toggleMailSelection = (id) => {
                        this.toggleSelection(id);
                    };
                    
                    window.dispatchSingleStatusChange = (id, mailNumber, currentStatus, targetStatus) => {
                        this.handleSingleStatusChange({ id, mailNumber, currentStatus, targetStatus });
                    };
                    
                    window.openStatusDropdown = (event, id, mailNumber, currentStatus) => {
                        this.openStatusDropdown(event, id, mailNumber, currentStatus);
                    };

                    window.resumeReceiveWorkflow = (id, mailNumber) => {
                        this.openReceiveWorkflow([id], mailNumber, true);
                    };

                    window.addEventListener('scroll', () => {
                        if (this.showStatusDropdown) this.showStatusDropdown = false;
                    }, true);

                    window.addEventListener('resize', () => {
                        if (this.showStatusDropdown) this.showStatusDropdown = false;
                    });
                    
                    // Update checkbox states on initial load
                    this.$nextTick(() => {
                        this.updateCheckboxStates();
                    });
                    
                    // Watch modal state untuk prevent body scroll
                    this.$watch('showConfirmModal', (value) => {
                        if (value || this.showDraftModal || this.showReceiveModal) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
                    this.$watch('showDraftModal', (value) => {
                        if (value || this.showConfirmModal || this.showReceiveModal) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
                    this.$watch('showReceiveModal', (value) => {
                        if (value || this.showConfirmModal || this.showDraftModal) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
                },

                get availableStatusOptions() {
                    const current = this.normalizeStatus(this.statusDropdownCurrentStatus);

                    return this.statusTransitions[current] ?? [];
                },

                /**
                 * Statuses reachable by at least one selected document,
                 * annotated with how many documents qualify.
                 */
                get bulkStatusOptions() {
                    const counts = new Map();

                    this.selectedIds.forEach(id => {
                        const options = this.statusTransitions[this.normalizeStatus(this.statusById[id])] ?? [];

                        options.forEach(option => {
                            const existing = counts.get(option.value);
                            counts.set(option.value, {
                                value: option.value,
                                label: option.label,
                                eligible: (existing?.eligible ?? 0) + 1,
                            });
                        });
                    });

                    return [...counts.values()];
                },

                get revisionModalTitle() {
                    return this.revisionIsRequired ? 'Ajukan Revisi Dokumen' : 'Selesaikan Revisi Dokumen';
                },

                get revisionModalSubtitle() {
                    const target = this.statusLabel(this.revisionTargetStatus);

                    return `${this.revisionMailLabel} · status akan menjadi ${target}`;
                },

                normalizeStatus(status) {
                    return String(status || '').trim().toUpperCase().replace(/\s+/g, '_');
                },

                statusLabel(status) {
                    return this.normalizeStatus(status).replace(/_/g, ' ');
                },

                statusDotClass(status) {
                    return {
                        RECEIVE: 'bg-slate-900',
                        DRAFT: 'bg-indigo-500',
                        REVISI: 'bg-amber-400',
                        RETURN: 'border border-slate-400 bg-white',
                        WAITING_RETURN: 'bg-blue-500',
                        DISPOSISI: 'bg-slate-600',
                    }[this.normalizeStatus(status)] ?? 'bg-slate-400';
                },

                canTransition(currentStatus, targetStatus) {
                    const allowed = this.statusTransitions[this.normalizeStatus(currentStatus)] ?? [];

                    return allowed.some(option => option.value === this.normalizeStatus(targetStatus));
                },

                openRevisionModal({ mailIds, label, targetStatus, isRequired }) {
                    this.showStatusDropdown = false;
                    this.revisionMailIds = [...mailIds];
                    this.revisionMailLabel = label;
                    this.revisionTargetStatus = targetStatus;
                    this.revisionIsRequired = isRequired;
                    this.revisionNote = '';
                    this.revisionError = '';
                    this.showRevisionModal = true;

                    this.$nextTick(() => document.getElementById('revision-note-input')?.focus());
                },

                closeRevisionModal() {
                    if (this.isSubmitting) return;
                    this.showRevisionModal = false;
                    this.revisionError = '';
                },

                async submitRevisionNote() {
                    if (this.revisionIsRequired && !this.revisionNote.trim()) {
                        this.revisionError = 'Catatan kebutuhan revisi wajib diisi.';
                        return;
                    }

                    const applied = await this.applyStatusChange(
                        this.revisionMailIds,
                        this.revisionTargetStatus,
                        this.revisionNote.trim() || null
                    );

                    if (applied) {
                        this.showRevisionModal = false;
                    } else {
                        this.revisionError = this.statusChangeError;
                    }
                },

                openDispositionModal({ mailIds, label, blockedCount = 0 }) {
                    this.showStatusDropdown = false;
                    this.dispositionMailIds = [...mailIds];
                    this.dispositionMailLabel = label;
                    this.dispositionBlockedCount = blockedCount;
                    this.dispositionNote = '';
                    this.dispositionError = '';
                    this.showDispositionModal = true;

                    this.$nextTick(() => document.getElementById('disposition-note-input')?.focus());
                },

                closeDispositionModal() {
                    if (this.isSubmitting) return;
                    this.showDispositionModal = false;
                    this.dispositionError = '';
                },

                async submitDispositionNote() {
                    const note = this.dispositionNote.trim();

                    if (!note) {
                        this.dispositionError = 'Catatan disposisi wajib diisi.';
                        return;
                    }

                    const applied = await this.applyStatusChange(
                        this.dispositionMailIds,
                        'DISPOSISI',
                        null,
                        note
                    );

                    if (applied) {
                        this.showDispositionModal = false;
                    } else {
                        this.dispositionError = this.statusChangeError;
                    }
                },

                newReceiveRecipient(data = {}) {
                    return {
                        id: data.id || null,
                        client_key: data.client_key || (window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`),
                        recipient_name: data.recipient_name || '',
                        signer_name: data.signer_name || '',
                        signature: null,
                        signature_url: data.signature_url || null,
                        has_signature: Boolean(data.has_signature),
                        remove_signature: false,
                    };
                },

                async openReceiveWorkflow(mailIds, label, resume = false) {
                    this.showStatusDropdown = false;
                    this.receivePreviousFocus = document.activeElement;
                    this.receiveMailIds = [...mailIds];
                    this.receiveMailLabel = label || 'Dokumen terpilih';
                    this.receiveRecipients = [this.newReceiveRecipient()];
                    this.receiveCanvases = {};
                    this.receiveError = '';
                    this.receiveDirty = false;
                    this.showReceiveModal = true;

                    if (!resume) return;

                    this.isLoadingReceive = true;
                    try {
                        const baseUrl = @js(url('/outgoing-mails'));
                        const response = await fetch(`${baseUrl}/${encodeURIComponent(mailIds[0])}/receive-recipients`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Progres pengembalian gagal dimuat.');
                        }

                        this.receiveMailLabel = data.mail?.mail_number || this.receiveMailLabel;
                        this.receiveRecipients = data.recipients?.length
                            ? data.recipients.map(recipient => this.newReceiveRecipient(recipient))
                            : [this.newReceiveRecipient()];
                    } catch (error) {
                        console.error('Receive workflow load error:', error);
                        this.receiveError = error.message || 'Progres pengembalian gagal dimuat.';
                    } finally {
                        this.isLoadingReceive = false;
                    }
                },

                closeReceiveModal() {
                    if (this.isSavingReceive) return;
                    if (this.receiveDirty && !window.confirm('Perubahan yang belum disimpan akan hilang. Tutup proses ini?')) return;

                    this.receiveDirty = false;
                    this.showReceiveModal = false;
                    this.receiveError = '';
                    this.$nextTick(() => this.receivePreviousFocus?.focus?.());
                },

                addReceiveRecipient() {
                    this.receiveRecipients.push(this.newReceiveRecipient());
                    this.receiveDirty = true;
                },

                removeReceiveRecipient(index) {
                    if (this.receiveRecipients.length === 1) return;
                    const [removed] = this.receiveRecipients.splice(index, 1);
                    delete this.receiveCanvases[removed.client_key];
                    this.receiveDirty = true;
                },

                setupReceiveCanvas(recipient, canvas) {
                    const context = canvas.getContext('2d');
                    context.lineWidth = 3;
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.strokeStyle = '#0f172a';
                    this.receiveCanvases[recipient.client_key] = canvas;

                    if (recipient.signature_url) {
                        const image = new Image();
                        image.onload = () => context.drawImage(image, 0, 0, canvas.width, canvas.height);
                        image.src = recipient.signature_url;
                    }
                },

                receiveCanvasPosition(event, canvas) {
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: (event.clientX - rect.left) * (canvas.width / rect.width),
                        y: (event.clientY - rect.top) * (canvas.height / rect.height),
                    };
                },

                startReceiveSignature(event, recipient) {
                    const canvas = this.receiveCanvases[recipient.client_key];
                    if (!canvas) return;
                    canvas.setPointerCapture?.(event.pointerId);
                    const point = this.receiveCanvasPosition(event, canvas);
                    const context = canvas.getContext('2d');
                    context.beginPath();
                    context.moveTo(point.x, point.y);
                    this.receiveDrawingKey = recipient.client_key;
                },

                drawReceiveSignature(event, recipient) {
                    if (this.receiveDrawingKey !== recipient.client_key) return;
                    const canvas = this.receiveCanvases[recipient.client_key];
                    if (!canvas) return;
                    const point = this.receiveCanvasPosition(event, canvas);
                    const context = canvas.getContext('2d');
                    context.lineTo(point.x, point.y);
                    context.stroke();
                },

                finishReceiveSignature(event, recipient) {
                    if (this.receiveDrawingKey !== recipient.client_key) return;
                    const canvas = this.receiveCanvases[recipient.client_key];
                    this.receiveDrawingKey = null;
                    if (!canvas) return;
                    canvas.releasePointerCapture?.(event.pointerId);
                    recipient.signature = canvas.toDataURL('image/png');
                    recipient.has_signature = true;
                    recipient.remove_signature = false;
                    this.receiveDirty = true;
                },

                clearReceiveSignature(recipient) {
                    const canvas = this.receiveCanvases[recipient.client_key];
                    canvas?.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height);
                    recipient.signature = null;
                    recipient.signature_url = null;
                    recipient.has_signature = false;
                    recipient.remove_signature = true;
                    this.receiveDirty = true;
                },

                get unsignedRecipientCount() {
                    return this.receiveRecipients.filter(
                        recipient => !recipient.signer_name.trim() || (!recipient.signature && !recipient.has_signature)
                    ).length;
                },

                skipRemainingSignatures() {
                    const pending = this.unsignedRecipientCount;

                    if (pending === 0) {
                        this.saveReceiveWorkflow('complete');
                        return;
                    }

                    const confirmed = window.confirm(
                        `${pending} penerima belum menandatangani. Pengembalian akan ditutup dan status menjadi RETURN, ` +
                        'sementara penerima tersebut tetap tercatat belum menandatangani. Lanjutkan?'
                    );

                    if (confirmed) {
                        this.saveReceiveWorkflow('skip_remaining');
                    }
                },

                async saveReceiveWorkflow(action) {
                    if (this.isSavingReceive) return;

                    const missingRecipient = this.receiveRecipients.find(recipient => !recipient.recipient_name.trim());
                    if (missingRecipient) {
                        this.receiveError = 'Nama setiap penerima wajib diisi.';
                        return;
                    }

                    if (action === 'complete') {
                        const incomplete = this.receiveRecipients.find(recipient =>
                            !recipient.signer_name.trim() || (!recipient.signature && !recipient.has_signature)
                        );
                        if (incomplete) {
                            this.receiveError = 'Lengkapi nama penandatangan dan tanda tangan seluruh penerima sebelum menyelesaikan pengembalian.';
                            return;
                        }
                    }

                    this.isSavingReceive = true;
                    this.receiveError = '';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                        const response = await fetch(@js(route('outgoing-mails.receive-workflow.store')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                mail_ids: this.receiveMailIds,
                                action,
                                recipients: this.receiveRecipients.map(recipient => ({
                                    id: recipient.id,
                                    recipient_name: recipient.recipient_name.trim(),
                                    signer_name: recipient.signer_name.trim() || null,
                                    signature: recipient.signature,
                                    remove_signature: recipient.remove_signature,
                                })),
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            const validationMessage = Object.values(result.errors || {}).flat()[0];
                            throw new Error(validationMessage || result.message || 'Progres pengembalian gagal disimpan.');
                        }

                        this.receiveDirty = false;
                        this.showReceiveModal = false;
                        this.showToast(result.message);
                        this.selectedIds = [];
                        await this.refreshTableData();
                    } catch (error) {
                        console.error('Receive workflow save error:', error);
                        this.receiveError = error.message || 'Progres pengembalian gagal disimpan.';
                    } finally {
                        this.isSavingReceive = false;
                    }
                },

                async openDraftModal(url = null) {
                    this.showDraftModal = true;
                    this.isLoadingDrafts = true;
                    
                    try {
                        const targetUrl = url || new URL('{{ route('outgoing-mails.index') }}?status=DRAFT', window.location.origin).toString();
                        
                        const response = await fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });
                        
                        if (!response.ok) throw new Error('Network response was not ok');
                        
                        const data = await response.json();
                        
                        this.$refs.draftTableBody.innerHTML = data.html;
                        this.$refs.draftPaginationContainer.innerHTML = data.pagination;
                        
                        this.$nextTick(() => {
                            const links = this.$refs.draftPaginationContainer.querySelectorAll('a');
                            links.forEach(link => {
                                link.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    this.openDraftModal(link.href);
                                });
                            });
                        });
                        
                    } catch (error) {
                        console.error('Draft load error:', error);
                        this.$refs.draftTableBody.innerHTML = '<tr><td colspan="12" class="p-8 text-center text-slate-500 italic text-sm">Terjadi kesalahan saat memuat draft.</td></tr>';
                    } finally {
                        this.isLoadingDrafts = false;
                    }
                },

                toggleSelectionMode() {
                    this.isSelectionMode = !this.isSelectionMode;
                    if (!this.isSelectionMode) {
                        this.selectedIds = [];
                    }
                },

                async performSearch() {
                    this.isSearching = true;
                    
                    try {
                        const url = new URL(window.location.href);
                        if (this.searchQuery.trim()) {
                            url.searchParams.set('search', this.searchQuery.trim());
                        } else {
                            url.searchParams.delete('search');
                        }
                        url.searchParams.delete('page'); // Reset ke halaman 1
                        
                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });
                        
                        if (!response.ok) throw new Error('Network response was not ok');
                        
                        const data = await response.json();
                        
                        // PENTING: Backup selectedIds SEBELUM update DOM
                        const preservedSelections = [...this.selectedIds];
                        
                        // Update allIds dan peta status untuk current page
                        this.allIds = data.allIds;
                        this.statusById = { ...this.statusById, ...(data.statuses || {}) };
                        
                        // Update table content
                        this.$refs.tableBody.innerHTML = data.html;
                        
                        // Update pagination
                        this.$refs.paginationContainer.innerHTML = data.pagination;
                        
                        // Update total results
                        this.totalResults = data.total;
                        
                        // RESTORE selectedIds setelah DOM update
                        this.selectedIds = preservedSelections;
                        
                        // Re-check checkboxes and update row styling
                        this.$nextTick(() => {
                            if (window.Alpine) {
                                window.Alpine.initTree(this.$refs.tableBody);
                            }
                            this.updateCheckboxStates();
                        });
                        
                        // Update URL tanpa reload
                        window.history.pushState({}, '', url.toString());
                        
                    } catch (error) {
                        console.error('Search error:', error);
                    } finally {
                        this.isSearching = false;
                    }
                },

                updateCheckboxStates() {
                    const checkboxes = this.$refs.tableBody.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        const id = checkbox.value;
                        const row = checkbox.closest('tr');
                        if (this.selectedIds.includes(id)) {
                            checkbox.checked = true;
                            if (row) row.classList.add('bg-slate-100/90');
                        } else {
                            checkbox.checked = false;
                            if (row) row.classList.remove('bg-slate-100/90');
                        }
                    });
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.performSearch();
                },

                get isAllSelected() {
                    return this.allIds.length > 0 && this.allIds.every(id => this.selectedIds.includes(id));
                },

                get isIndeterminate() {
                    const selectedInPage = this.allIds.filter(id => this.selectedIds.includes(id)).length;
                    return selectedInPage > 0 && selectedInPage < this.allIds.length;
                },

                toggleSelectAll() {
                    const allPageIdsSelected = this.allIds.every(id => this.selectedIds.includes(id));
                    
                    if (allPageIdsSelected) {
                        // Deselect all in current page
                        this.selectedIds = this.selectedIds.filter(id => !this.allIds.includes(id));
                    } else {
                        // Select all in current page
                        this.allIds.forEach(id => {
                            if (!this.selectedIds.includes(id)) {
                                this.selectedIds.push(id);
                            }
                        });
                    }
                },

                toggleSelection(id) {
                    if (this.selectedIds.includes(id)) {
                        this.selectedIds = this.selectedIds.filter(selectedId => selectedId !== id);
                    } else {
                        this.selectedIds.push(id);
                    }
                    
                    // Update checkbox and row styling
                    const checkbox = this.$refs.tableBody.querySelector(`input[value="${id}"]`);
                    if (checkbox) {
                        checkbox.checked = this.selectedIds.includes(id);
                        const row = checkbox.closest('tr');
                        if (row) {
                            if (this.selectedIds.includes(id)) {
                                row.classList.add('bg-slate-100/90');
                            } else {
                                row.classList.remove('bg-slate-100/90');
                            }
                        }
                    }
                },

                clearSelection() {
                    this.selectedIds = [];
                },

                getSelectedInCurrentPage() {
                    return this.selectedIds.filter(id => this.allIds.includes(id)).length;
                },

                openStatusDropdown(event, id, mailNumber, currentStatus) {
                    event.stopPropagation();
                    const btn = event.currentTarget;
                    const rect = btn.getBoundingClientRect();

                    let zoom = 1;
                    const rawZoom = document.documentElement.style.zoom || window.getComputedStyle(document.documentElement).zoom;
                    if (rawZoom) {
                        zoom = typeof rawZoom === 'string' && rawZoom.includes('%') ? (parseFloat(rawZoom) / 100) : parseFloat(rawZoom);
                    }
                    if (isNaN(zoom) || zoom <= 0) zoom = 0.9;

                    const top = (rect.bottom / zoom) + 6;
                    const left = (rect.left / zoom) + ((rect.width / zoom) / 2) - 88;
                    const clampedLeft = Math.max(12, Math.min(left, (window.innerWidth / zoom) - 188));

                    this.statusDropdownPosition = {
                        top: `${Math.round(top)}px`,
                        left: `${Math.round(clampedLeft)}px`
                    };
                    this.statusDropdownMailId = id;
                    this.statusDropdownMailNumber = mailNumber;
                    this.statusDropdownCurrentStatus = currentStatus;
                    this.showStatusDropdown = true;
                },

                selectStatusOption(targetStatus) {
                    this.showStatusDropdown = false;
                    this.handleSingleStatusChange({
                        id: this.statusDropdownMailId,
                        mailNumber: this.statusDropdownMailNumber,
                        currentStatus: this.statusDropdownCurrentStatus,
                        targetStatus: targetStatus
                    });
                },

                bulkUpdateStatus(targetStatus) {
                    if (this.selectedIds.length === 0) return;

                    const count = this.selectedIds.length;
                    const label = count === 1 ? '1 dokumen terpilih' : `${count} dokumen terpilih`;

                    // Documents whose current status does not allow this transition.
                    const blocked = this.selectedIds.filter(
                        id => !this.canTransition(this.statusById[id], targetStatus)
                    );

                    if (blocked.length === count) {
                        this.showErrorToast(
                            `Tidak ada dokumen terpilih yang dapat diubah menjadi ${this.statusLabel(targetStatus)} sesuai alur status.`
                        );
                        return;
                    }

                    const eligibleIds = this.selectedIds.filter(id => !blocked.includes(id));

                    if (this.normalizeStatus(targetStatus) === 'RETURN') {
                        this.openReceiveWorkflow(eligibleIds, label);
                        return;
                    }

                    this.requestStatusChange(eligibleIds, label, targetStatus, blocked.length);
                },

                /**
                 * Route a status change through the revision note modal when needed,
                 * otherwise through the plain confirmation modal.
                 */
                requestStatusChange(mailIds, label, targetStatus, blockedCount = 0) {
                    const target = this.normalizeStatus(targetStatus);
                    const leavingRevisi = mailIds.some(id => this.normalizeStatus(this.statusById[id]) === 'REVISI');

                    // Disposisi selalu wajib disertai catatan disposisi.
                    if (target === 'DISPOSISI') {
                        this.openDispositionModal({ mailIds, label, blockedCount });
                        return;
                    }

                    if (target === 'REVISI') {
                        this.openRevisionModal({ mailIds, label, targetStatus: target, isRequired: true });
                        return;
                    }

                    if (leavingRevisi) {
                        this.openRevisionModal({ mailIds, label, targetStatus: target, isRequired: false });
                        return;
                    }

                    let message = `Apakah Anda yakin ingin mengubah status <strong>${label}</strong> menjadi <strong>${this.statusLabel(target)}</strong>?`;

                    if (blockedCount > 0) {
                        message += `<br/><br/><span class="text-xs text-amber-700">${blockedCount} dokumen dilewati karena alur statusnya tidak mengizinkan perubahan ini.</span>`;
                    }

                    this.confirmMessage = message;
                    this.confirmTargetStatus = target;
                    this.confirmMailIds = [...mailIds];
                    this.showConfirmModal = true;
                },

                async confirmBulkUpdate() {
                    const applied = await this.applyStatusChange(this.confirmMailIds, this.confirmTargetStatus);

                    if (applied) {
                        this.showConfirmModal = false;
                    }
                },

                /**
                 * Persist a status change for the given documents.
                 * Receives true when at least one document was updated.
                 */
                async applyStatusChange(mailIds, targetStatus, revisionNote = null, dispositionNote = null) {
                    if (this.isSubmitting || mailIds.length === 0) return false;

                    this.isSubmitting = true;
                    this.statusChangeError = '';

                    try {
                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');
                        formData.append('status', targetStatus);
                        if (revisionNote) {
                            formData.append('revision_note', revisionNote);
                        }
                        if (dispositionNote) {
                            formData.append('disposition_note', dispositionNote);
                        }
                        mailIds.forEach(id => formData.append('ids[]', id));

                        const response = await fetch(@js(route('outgoing-mails.bulk-update-status')), {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            const validationMessage = Object.values(result.errors || {}).flat()[0];
                            throw new Error(validationMessage || result.message || 'Status gagal diperbarui.');
                        }

                        this.showToast(result.message);

                        await this.refreshTableData();
                        this.selectedIds = [];
                        this.confirmMailIds = [];

                        return true;
                    } catch (error) {
                        console.error('Status change error:', error);
                        this.statusChangeError = error.message || 'Status gagal diperbarui.';
                        this.showErrorToast(this.statusChangeError);

                        return false;
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                showToast(message, isError = false) {
                    this.toastIsError = isError;
                    this.successMessage = message;
                    this.showSuccessToast = true;
                    setTimeout(() => { this.showSuccessToast = false; }, isError ? 6000 : 5000);
                },

                showErrorToast(message) {
                    this.showToast(message, true);
                },

                async refreshTableData() {
                    try {
                        const url = new URL(window.location.href);
                        
                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });
                        
                        if (!response.ok) throw new Error('Network response was not ok');
                        
                        const data = await response.json();
                        
                        // Update table content
                        this.$refs.tableBody.innerHTML = data.html;
                        
                        // Update pagination
                        this.$refs.paginationContainer.innerHTML = data.pagination;
                        
                        // Update total results
                        this.totalResults = data.total;
                        
                        // Update allIds dan peta status untuk validasi alur
                        this.allIds = data.allIds;
                        this.statusById = { ...this.statusById, ...(data.statuses || {}) };
                        
                        // Re-check checkboxes and update row styling
                        this.$nextTick(() => {
                            if (window.Alpine) {
                                window.Alpine.initTree(this.$refs.tableBody);
                            }
                            this.updateCheckboxStates();
                        });
                        
                    } catch (error) {
                        console.error('Refresh error:', error);
                    }
                },

                handleSingleStatusChange(detail) {
                    const targetStatus = this.normalizeStatus(detail.targetStatus);
                    const currentStatus = this.statusById[detail.id] ?? detail.currentStatus;

                    if (!this.canTransition(currentStatus, targetStatus)) {
                        this.showErrorToast(
                            `Dokumen ${detail.mailNumber} berstatus ${this.statusLabel(currentStatus)} sehingga tidak dapat diubah menjadi ${this.statusLabel(targetStatus)}.`
                        );
                        return;
                    }

                    if (targetStatus === 'RETURN') {
                        this.openReceiveWorkflow([detail.id], detail.mailNumber);
                        return;
                    }

                    this.requestStatusChange([detail.id], `dokumen ${detail.mailNumber}`, targetStatus);
                }
            };
        }
    </script>
@endsection

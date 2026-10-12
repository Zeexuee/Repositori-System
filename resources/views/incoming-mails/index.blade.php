@extends('layouts.app')

@section('title', 'Daftar Surat Masuk')

@section('content')
    <div x-data="incomingMailSelector()" 
         @single-status-change.window="handleSingleStatusChange($event.detail)"
         class="space-y-4">
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

        <!-- Disposition Note Modal (wajib sebelum status menjadi DISPOSISI) -->
        <template x-teleport="body">
            <div x-show="showDispositionModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                role="dialog" aria-modal="true" aria-labelledby="disposition-note-title"
                @keydown.escape.window="closeDispositionModal()">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closeDispositionModal()"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-lg overflow-hidden"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <h2 id="disposition-note-title" class="text-lg font-bold text-slate-900">Disposisikan Dokumen</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            <span x-text="dispositionMailLabel"></span> · status akan menjadi DISPOSISI
                        </p>
                    </div>

                    <div class="px-6 py-5 space-y-3">
                        <label for="disposition-note-input" class="block text-xs font-bold text-slate-700">
                            Catatan Disposisi <span class="text-rose-600">*</span>
                        </label>
                        <textarea id="disposition-note-input" x-model="dispositionNote" rows="5" maxlength="2000"
                            required aria-required="true"
                            placeholder="Contoh: Mohon ditindaklanjuti oleh Divisi Keuangan paling lambat Jumat."
                            class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
                        <p class="text-[11px] text-slate-400 text-right">
                            <span x-text="dispositionNote.length"></span>/2000
                        </p>
                        <p x-show="dispositionBlockedCount > 0" class="text-xs text-amber-700">
                            <span x-text="dispositionBlockedCount"></span> dokumen dilewati karena alur statusnya tidak mengizinkan perubahan ini.
                        </p>
                        <p class="text-xs text-slate-600">Catatan: DISPOSISI adalah status akhir dan tidak dapat diubah lagi.</p>
                        <p x-show="dispositionError" x-text="dispositionError" role="alert" aria-live="assertive"
                            class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800"></p>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" @click="closeDispositionModal()" :disabled="isSubmitting"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl disabled:opacity-50">
                            Batal
                        </button>
                        <button type="button" @click="submitDispositionNote()" :disabled="isSubmitting || !dispositionNote.trim()"
                            class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <svg x-show="isSubmitting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Simpan dan Disposisikan
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Return Signature Workflow Modal -->
        <template x-teleport="body">
            <div x-show="showReturnModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-3 sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="return-workflow-title"
                @keydown.escape.window="closeReturnModal()">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-5xl max-h-[92vh] overflow-hidden flex flex-col"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    <div class="px-5 sm:px-7 py-5 border-b border-slate-200 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="return-workflow-title" class="text-lg font-bold text-slate-900">Tanda Tangan Pengembalian Dokumen</h2>
                            <p class="text-xs text-slate-500 mt-1">
                                <span x-text="returnMailLabel"></span> · <span x-text="returnMailIds.length"></span> dokumen
                            </p>
                        </div>
                        <button type="button" @click="closeReturnModal()" :disabled="isSavingReturn"
                            aria-label="Tutup proses pengembalian"
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-5 sm:px-7 py-5 overflow-y-auto bg-slate-50/70 space-y-4">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 leading-relaxed">
                            Tambahkan semua penerima dokumen. Isi nama orang yang menandatangani dan bubuhkan tanda tangan untuk penerima yang sudah mengambil dokumen. Progres yang belum lengkap dapat disimpan melalui <strong>Lanjut Nanti</strong>.
                        </div>

                        <div x-show="isLoadingReturn" class="py-12 flex items-center justify-center gap-3 text-sm font-semibold text-slate-600">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Memuat progres pengembalian...
                        </div>

                        <div x-show="!isLoadingReturn" class="space-y-4">
                            <template x-for="(recipient, index) in returnRecipients" :key="recipient.client_key">
                                <fieldset class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-4 shadow-2xs">
                                    <legend class="px-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Penerima <span x-text="index + 1"></span>
                                    </legend>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label :for="`return-recipient-${recipient.client_key}`" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penerima / Unit</label>
                                            <input type="text" :id="`return-recipient-${recipient.client_key}`"
                                                x-model="recipient.recipient_name" @input="returnDirty = true" maxlength="255"
                                                placeholder="Contoh: Divisi Keuangan"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900" required>
                                        </div>
                                        <div>
                                            <label :for="`return-signer-${recipient.client_key}`" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penandatangan</label>
                                            <input type="text" :id="`return-signer-${recipient.client_key}`"
                                                x-model="recipient.signer_name" @input="returnDirty = true" maxlength="255"
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
                                            x-init="$nextTick(() => setupReturnCanvas(recipient, $el))"
                                            @pointerdown.prevent="startReturnSignature($event, recipient)"
                                            @pointermove.prevent="drawReturnSignature($event, recipient)"
                                            @pointerup.prevent="finishReturnSignature($event, recipient)"
                                            @pointercancel.prevent="finishReturnSignature($event, recipient)"
                                            aria-label="Area tanda tangan penerima"
                                            class="w-full h-40 bg-white border border-slate-300 rounded-xl touch-none cursor-crosshair"></canvas>
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] text-slate-500">Gunakan mouse, pena digital, atau sentuhan.</p>
                                            <button type="button" @click="clearReturnSignature(recipient)"
                                                class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100">
                                                Bersihkan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex justify-end" x-show="returnRecipients.length > 1">
                                        <button type="button" @click="removeReturnRecipient(index)"
                                            class="text-xs font-semibold text-slate-700 hover:underline">Hapus penerima ini</button>
                                    </div>
                                </fieldset>
                            </template>

                            <button type="button" @click="addReturnRecipient()"
                                class="w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-white hover:border-slate-500 transition-colors">
                                + Tambah Penerima
                            </button>
                        </div>

                        <p x-show="returnError" x-text="returnError" role="alert" aria-live="assertive"
                            class="p-3 rounded-xl bg-slate-100 border border-slate-300 text-xs font-semibold text-slate-800"></p>
                    </div>

                    <div class="px-5 sm:px-7 py-4 border-t border-slate-200 bg-white flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="button" @click="closeReturnModal()" :disabled="isSavingReturn"
                            class="px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 disabled:opacity-50">
                            Batal
                        </button>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="button" @click="saveReturnWorkflow('save_later')"
                                :disabled="isSavingReturn || isLoadingReturn"
                                class="px-5 py-2.5 text-xs font-bold text-slate-800 bg-slate-100 border border-slate-300 rounded-xl hover:bg-slate-200 disabled:opacity-50">
                                Lanjut Nanti
                            </button>
                            <button type="button" @click="skipRemainingSignatures()"
                                :disabled="isSavingReturn || isLoadingReturn"
                                title="Tutup pengembalian tanpa menunggu tanda tangan penerima yang tersisa"
                                class="px-5 py-2.5 text-xs font-bold text-slate-800 bg-slate-50 border border-slate-300 rounded-xl hover:bg-slate-100 disabled:opacity-50">
                                Lewati Proses Tanda Tangan yang Tersisa
                            </button>
                            <button type="button" @click="saveReturnWorkflow('complete')"
                                :disabled="isSavingReturn || isLoadingReturn"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center justify-center gap-2">
                                <svg x-show="isSavingReturn" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Selesaikan Pengembalian
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Draft Modal -->
        <div x-show="showDraftModal" 
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 pointer-events-none"
            style="margin: 0 !important;"
            @keydown.escape.window="showDraftModal = false">
            
            <div class="fixed inset-0 pointer-events-auto" @click="showDraftModal = false"></div>
            
            <div class="relative bg-white rounded-xl shadow-xl max-w-7xl w-full border border-slate-200 overflow-hidden flex flex-col max-h-[85vh] pointer-events-auto"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">
                
                <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between bg-white">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Daftar Dokumen Draft</h3>
                        <p class="text-xs text-slate-500 mt-1">Lanjutkan pengerjaan dokumen yang belum selesai disimpan.</p>
                    </div>
                    <button type="button" @click="showDraftModal = false" class="text-slate-400 hover:text-slate-900 transition-colors p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1 bg-slate-50 relative">
                    <div x-show="isLoadingDrafts" class="absolute inset-0 bg-white/75 flex items-center justify-center z-10">
                        <div class="flex items-center space-x-3 bg-white px-5 py-3 rounded-xl shadow-xl border border-slate-200">
                            <svg class="animate-spin h-5 w-5 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-bold text-slate-900">Memuat Draft...</span>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-full">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                                        <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap w-14">No.</th>
                                        <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nomor Surat</th>
                                        <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Masuk</th>
                                        <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Dari</th>
                                        <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Kepada</th>
                                        <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                                        <th class="py-4 px-4 sm:px-5 min-w-[280px]">Perihal</th>
                                        <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Keluar</th>
                                        <th class="py-4 px-4 sm:px-5 min-w-[200px]">Disposisi</th>
                                        <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nama Penerima</th>
                                        <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-sm" x-ref="draftTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4" x-ref="draftPaginationContainer"></div>
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
                    <p x-show="availableStatusOptions.length === 0"
                        class="px-3.5 py-2.5 text-[11px] text-slate-500 leading-relaxed">
                        Status <span class="font-bold" x-text="statusDropdownCurrentStatus"></span> adalah status akhir dan tidak dapat diubah lagi.
                    </p>
                </div>
            </div>
        </template>

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
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between pb-4 border-b border-slate-200/80 gap-4">
            <!-- Title -->
            <div class="flex-shrink-0">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight whitespace-nowrap">Daftar Surat Masuk</h1>
            </div>
            
            <!-- Search Bar (Lebih Besar & Centered) -->
            <div class="flex-1 max-w-2xl mx-auto">
                <div class="relative">
                    <input type="text" 
                        x-model="searchQuery"
                        @input.debounce.500ms="performSearch()"
                        placeholder="Cari nomor surat, perihal, pengirim, penerima..."
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

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 flex-shrink-0">
                <!-- Bulk Action Dropdown (Only show when selection mode is active) -->
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
                    
                    <!-- Info: Selection across pages -->
                    <div x-show="selectedIds.length > 0 && getSelectedInCurrentPage() < selectedIds.length" 
                        class="absolute top-full left-0 mt-1 text-[10px] text-slate-600 whitespace-nowrap">
                        <span x-text="getSelectedInCurrentPage()"></span> di halaman ini, 
                        <span x-text="selectedIds.length - getSelectedInCurrentPage()"></span> di halaman lain
                    </div>
                    
                    <!-- Dropdown Menu -->
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

                <!-- Tombol Select / Batal Select -->
                <button type="button" @click="toggleSelectionMode()"
                    :class="isSelectionMode ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300 shadow-2xs'"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-xl transition-all border cursor-pointer">
                    <span x-text="isSelectionMode ? 'Batal Select' : 'Select'">Select</span>
                </button>

                <!-- FITUR DRAFT DI-HOLD SEMENTARA
                <button type="button" @click="openDraftModal()"
                    class="inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 shadow-2xs border border-indigo-200 text-xs font-bold rounded-xl transition-all cursor-pointer">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Draft</span>
                </button>
                -->

                @can('create', App\Models\IncomingMail::class)
                    <a href="{{ route('incoming-mails.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                        <span>Tambah Surat Masuk</span>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Hidden Bulk Form for Submission -->
        <form x-ref="bulkForm" action="{{ route('incoming-mails.bulk-update-status') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="status" x-ref="bulkStatusInput">
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>

        <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-full">
                    <thead>
                        <tr
                            class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                            <!-- Select Box Column Header -->
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
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Masuk</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Dari</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Kepada</th>
                            <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                            <th class="py-4 px-4 sm:px-5 min-w-[280px]">Perihal</th>
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Keluar</th>
                            <th class="py-4 px-4 sm:px-5 min-w-[200px]">Disposisi</th>
                            <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm" x-ref="tableBody">
                        @include('incoming-mails.partials.table-rows')
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6" x-ref="paginationContainer">
            @include('incoming-mails.partials.pagination')
        </div>
    </div>

    <script>
        function incomingMailSelector() {
            return {
                isSelectionMode: false,
                searchQuery: '{{ request('search') }}',
                isSearching: false,
                totalResults: {{ $incomingMails->total() }},
                selectedIds: [],
                allIds: @json($incomingMails->pluck('id')->map(fn($id) => (string) $id)->values()),
                statusById: @js($incomingMails->mapWithKeys(fn ($mail) => [
                    (string) $mail->id => App\Support\IncomingMailStatus::canonical($mail->status) ?? (string) $mail->status,
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
                statusTransitions: @js(App\Support\IncomingMailStatus::transitionMap()),

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

                // Return signature workflow state
                showReturnModal: false,
                isLoadingReturn: false,
                isSavingReturn: false,
                returnDirty: false,
                returnError: '',
                returnMailIds: [],
                returnMailLabel: '',
                returnRecipients: [],
                returnCanvases: {},
                returnDrawingKey: null,
                returnPreviousFocus: null,
                
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

                    window.resumeReturnWorkflow = (id, mailNumber) => {
                        this.openReturnWorkflow([id], mailNumber, true);
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
                        if (value || this.showReturnModal) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
                    this.$watch('showReturnModal', (value) => {
                        if (value || this.showConfirmModal) {
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

                newReturnRecipient(data = {}) {
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

                async openReturnWorkflow(mailIds, label, resume = false) {
                    this.showStatusDropdown = false;
                    this.returnPreviousFocus = document.activeElement;
                    this.returnMailIds = [...mailIds];
                    this.returnMailLabel = label || 'Dokumen terpilih';
                    this.returnRecipients = [this.newReturnRecipient()];
                    this.returnCanvases = {};
                    this.returnError = '';
                    this.returnDirty = false;
                    this.showReturnModal = true;

                    if (!resume) return;

                    this.isLoadingReturn = true;
                    try {
                        const baseUrl = @js(url('/incoming-mails'));
                        const response = await fetch(`${baseUrl}/${encodeURIComponent(mailIds[0])}/return-recipients`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Progres pengembalian gagal dimuat.');
                        }

                        this.returnMailLabel = data.mail?.mail_number || this.returnMailLabel;
                        this.returnRecipients = data.recipients?.length
                            ? data.recipients.map(recipient => this.newReturnRecipient(recipient))
                            : [this.newReturnRecipient()];
                    } catch (error) {
                        console.error('Return workflow load error:', error);
                        this.returnError = error.message || 'Progres pengembalian gagal dimuat.';
                    } finally {
                        this.isLoadingReturn = false;
                    }
                },

                closeReturnModal() {
                    if (this.isSavingReturn) return;
                    if (this.returnDirty && !window.confirm('Perubahan yang belum disimpan akan hilang. Tutup proses ini?')) return;

                    this.returnDirty = false;
                    this.showReturnModal = false;
                    this.returnError = '';
                    this.$nextTick(() => this.returnPreviousFocus?.focus?.());
                },

                addReturnRecipient() {
                    this.returnRecipients.push(this.newReturnRecipient());
                    this.returnDirty = true;
                },

                removeReturnRecipient(index) {
                    if (this.returnRecipients.length === 1) return;
                    const [removed] = this.returnRecipients.splice(index, 1);
                    delete this.returnCanvases[removed.client_key];
                    this.returnDirty = true;
                },

                setupReturnCanvas(recipient, canvas) {
                    const context = canvas.getContext('2d');
                    context.lineWidth = 3;
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.strokeStyle = '#0f172a';
                    this.returnCanvases[recipient.client_key] = canvas;

                    if (recipient.signature_url) {
                        const image = new Image();
                        image.onload = () => context.drawImage(image, 0, 0, canvas.width, canvas.height);
                        image.src = recipient.signature_url;
                    }
                },

                returnCanvasPosition(event, canvas) {
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: (event.clientX - rect.left) * (canvas.width / rect.width),
                        y: (event.clientY - rect.top) * (canvas.height / rect.height),
                    };
                },

                startReturnSignature(event, recipient) {
                    const canvas = this.returnCanvases[recipient.client_key];
                    if (!canvas) return;
                    canvas.setPointerCapture?.(event.pointerId);
                    const point = this.returnCanvasPosition(event, canvas);
                    const context = canvas.getContext('2d');
                    context.beginPath();
                    context.moveTo(point.x, point.y);
                    this.returnDrawingKey = recipient.client_key;
                },

                drawReturnSignature(event, recipient) {
                    if (this.returnDrawingKey !== recipient.client_key) return;
                    const canvas = this.returnCanvases[recipient.client_key];
                    if (!canvas) return;
                    const point = this.returnCanvasPosition(event, canvas);
                    const context = canvas.getContext('2d');
                    context.lineTo(point.x, point.y);
                    context.stroke();
                },

                finishReturnSignature(event, recipient) {
                    if (this.returnDrawingKey !== recipient.client_key) return;
                    const canvas = this.returnCanvases[recipient.client_key];
                    this.returnDrawingKey = null;
                    if (!canvas) return;
                    canvas.releasePointerCapture?.(event.pointerId);
                    recipient.signature = canvas.toDataURL('image/png');
                    recipient.has_signature = true;
                    recipient.remove_signature = false;
                    this.returnDirty = true;
                },

                clearReturnSignature(recipient) {
                    const canvas = this.returnCanvases[recipient.client_key];
                    canvas?.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height);
                    recipient.signature = null;
                    recipient.signature_url = null;
                    recipient.has_signature = false;
                    recipient.remove_signature = true;
                    this.returnDirty = true;
                },

                get unsignedRecipientCount() {
                    return this.returnRecipients.filter(
                        recipient => !recipient.signer_name.trim() || (!recipient.signature && !recipient.has_signature)
                    ).length;
                },

                skipRemainingSignatures() {
                    const pending = this.unsignedRecipientCount;

                    if (pending === 0) {
                        this.saveReturnWorkflow('complete');
                        return;
                    }

                    const confirmed = window.confirm(
                        `${pending} penerima belum menandatangani. Pengembalian akan ditutup dan status menjadi RETURN, ` +
                        'sementara penerima tersebut tetap tercatat belum menandatangani. Lanjutkan?'
                    );

                    if (confirmed) {
                        this.saveReturnWorkflow('skip_remaining');
                    }
                },

                async saveReturnWorkflow(action) {
                    if (this.isSavingReturn) return;

                    const missingRecipient = this.returnRecipients.find(recipient => !recipient.recipient_name.trim());
                    if (missingRecipient) {
                        this.returnError = 'Nama setiap penerima wajib diisi.';
                        return;
                    }

                    if (action === 'complete') {
                        const incomplete = this.returnRecipients.find(recipient =>
                            !recipient.signer_name.trim() || (!recipient.signature && !recipient.has_signature)
                        );
                        if (incomplete) {
                            this.returnError = 'Lengkapi nama penandatangan dan tanda tangan seluruh penerima sebelum menyelesaikan pengembalian.';
                            return;
                        }
                    }

                    this.isSavingReturn = true;
                    this.returnError = '';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                        const response = await fetch(@js(route('incoming-mails.return-workflow.store')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                mail_ids: this.returnMailIds,
                                action,
                                recipients: this.returnRecipients.map(recipient => ({
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

                        this.returnDirty = false;
                        this.showReturnModal = false;
                        this.showToast(result.message);
                        this.selectedIds = [];
                        await this.refreshTableData();
                    } catch (error) {
                        console.error('Return workflow save error:', error);
                        this.returnError = error.message || 'Progres pengembalian gagal disimpan.';
                    } finally {
                        this.isSavingReturn = false;
                    }
                },

                async openDraftModal(url = null) {
                    this.showDraftModal = true;
                    this.isLoadingDrafts = true;
                    
                    try {
                        const targetUrl = url || new URL('{{ route('incoming-mails.index') }}?status=DRAFT', window.location.origin).toString();
                        
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
                        this.openReturnWorkflow(eligibleIds, label);
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
                 * Returns true when at least one document was updated.
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

                        const response = await fetch(@js(route('incoming-mails.bulk-update-status')), {
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
                        this.openReturnWorkflow([detail.id], detail.mailNumber);
                        return;
                    }

                    this.requestStatusChange([detail.id], `dokumen ${detail.mailNumber}`, targetStatus);
                }
            };
        }
    </script>
@endsection
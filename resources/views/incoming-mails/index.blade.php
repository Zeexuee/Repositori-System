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
                                ? ''
                                : 'Opsional'"
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

        <!-- Disposition Workflow Modal -->
        <template x-teleport="body">
            <div x-show="showDispositionModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-3 sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="disposition-workflow-title"
                @keydown.escape.window="closeDispositionModal()">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-5xl max-h-[92vh] overflow-hidden flex flex-col"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    <div class="px-5 sm:px-7 py-5 border-b border-slate-200 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="disposition-workflow-title" class="text-lg font-bold text-slate-900">Alur Disposisi Dokumen</h2>
                            <p class="text-xs text-slate-500 mt-1">
                                <span x-text="dispositionMailLabel"></span> · <span x-text="dispositionMailIds.length"></span> dokumen
                            </p>
                        </div>
                        <button type="button" @click="closeDispositionModal()" :disabled="isSavingDisposition"
                            aria-label="Tutup proses disposisi"
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-5 sm:px-7 py-5 overflow-y-auto bg-slate-50/70 space-y-4">
                        <!-- Petunjuk SOP Disposisi -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 leading-relaxed">
                            Masukkan <strong>Catatan Disposisi</strong> terlebih dahulu, lalu tambahkan penerima disposisi yang dituju. Anda dapat menyimpan progres sementara melalui <strong>Lanjut Nanti</strong> atau menyelesaikan alur ketika tanda tangan telah lengkap.
                        </div>

                        <!-- 1. Form Input Catatan Disposisi (Wajib diisi SEBELUM form penerima) -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-3 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <label for="disposition-workflow-note" class="block text-xs font-bold text-slate-800">
                                    Catatan Disposisi <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[11px] font-mono text-slate-400">
                                    <span x-text="dispositionNote.length"></span>/2000
                                </span>
                            </div>
                            <textarea id="disposition-workflow-note" x-model="dispositionNote" @input="dispositionDirty = true" rows="4" maxlength="2000"
                                required aria-required="true"
                                placeholder=""
                                class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900"></textarea>
                        </div>

                        <!-- Loading Indicator jika membuka kembali progres -->
                        <div x-show="isLoadingDisposition" class="py-12 flex items-center justify-center gap-3 text-sm font-semibold text-slate-600">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Memuat data disposisi...
                        </div>

                        <!-- 2. Form Input Penerima dan Lain-lain (Setelah Catatan Disposisi) -->
                        <div x-show="!isLoadingDisposition" class="space-y-4">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider px-1">
                                Penerima Disposisi
                            </h3>

                            <template x-for="(recipient, index) in dispositionRecipients" :key="recipient.client_key">
                                <fieldset class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-4 shadow-2xs">
                                    <legend class="px-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Penerima <span x-text="index + 1"></span>
                                    </legend>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label :for="`disposition-recipient-${recipient.client_key}`" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penerima / Unit Tujuan <span class="text-rose-600">*</span></label>
                                            <input type="text" :id="`disposition-recipient-${recipient.client_key}`"
                                                x-model="recipient.recipient_name" @input="dispositionDirty = true" maxlength="255"
                                                placeholder="Contoh: Kepala Biro Umum / Bagian Keuangan"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900" required>
                                        </div>
                                        <div>
                                            <label :for="`disposition-signer-${recipient.client_key}`" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penandatangan</label>
                                            <input type="text" :id="`disposition-signer-${recipient.client_key}`"
                                                x-model="recipient.signer_name" @input="dispositionDirty = true" maxlength="255"
                                                placeholder="Boleh dikosongkan jika belum hadir / lanjut nanti"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between gap-3 mb-1.5">
                                            <span class="text-xs font-bold text-slate-700">Tanda Tangan Penerima Disposisi</span>
                                            <span x-show="recipient.has_signature" class="text-[11px] font-semibold text-slate-700">Tanda tangan tersimpan</span>
                                        </div>
                                        <canvas width="800" height="220"
                                            x-init="$nextTick(() => setupDispositionCanvas(recipient, $el))"
                                            @pointerdown.prevent="startDispositionSignature($event, recipient)"
                                            @pointermove.prevent="drawDispositionSignature($event, recipient)"
                                            @pointerup.prevent="finishDispositionSignature($event, recipient)"
                                            @pointercancel.prevent="finishDispositionSignature($event, recipient)"
                                            aria-label="Area tanda tangan penerima disposisi"
                                            class="w-full h-40 bg-white border border-slate-300 rounded-xl touch-none cursor-crosshair"></canvas>
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] text-slate-500">Gunakan mouse, pena digital, atau layar sentuh.</p>
                                            <button type="button" @click="clearDispositionSignature(recipient)"
                                                class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100">
                                                Bersihkan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex justify-end" x-show="dispositionRecipients.length > 1">
                                        <button type="button" @click="removeDispositionRecipient(index)"
                                            class="text-xs font-semibold text-slate-700 hover:underline">Hapus penerima ini</button>
                                    </div>
                                </fieldset>
                            </template>

                            <button type="button" @click="addDispositionRecipient()"
                                class="w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-white hover:border-slate-500 transition-colors">
                                + Tambah Penerima Disposisi
                            </button>
                        </div>

                        <p x-show="dispositionError" x-text="dispositionError" role="alert" aria-live="assertive"
                            class="p-3 rounded-xl bg-slate-100 border border-slate-300 text-xs font-semibold text-slate-800"></p>
                    </div>

                    <div class="px-5 sm:px-7 py-4 border-t border-slate-200 bg-white flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="button" @click="closeDispositionModal()" :disabled="isSavingDisposition"
                            class="px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 disabled:opacity-50">
                            Batal
                        </button>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="button" @click="saveDispositionWorkflow('save_later')"
                                :disabled="isSavingDisposition || isLoadingDisposition"
                                class="px-5 py-2.5 text-xs font-bold text-slate-800 bg-slate-100 border border-slate-300 rounded-xl hover:bg-slate-200 disabled:opacity-50">
                                Lanjut Nanti
                            </button>
                            <button type="button" @click="skipRemainingDispositionSignatures()"
                                :disabled="isSavingDisposition || isLoadingDisposition"
                                title="Tutup disposisi tanpa menunggu tanda tangan penerima yang tersisa"
                                class="px-5 py-2.5 text-xs font-bold text-slate-800 bg-slate-50 border border-slate-300 rounded-xl hover:bg-slate-100 disabled:opacity-50">
                                Lewati Proses Tanda Tangan yang Tersisa
                            </button>
                            <button type="button" @click="saveDispositionWorkflow('complete')"
                                :disabled="isSavingDisposition || isLoadingDisposition"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center justify-center gap-2">
                                <svg x-show="isSavingDisposition" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Selesaikan Disposisi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Fitur Intip Catatan Status Disposisi Modal -->
        <template x-teleport="body">
            <div x-show="showDispositionPreviewModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-3 sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="disposition-preview-title"
                @keydown.escape.window="showDispositionPreviewModal = false">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showDispositionPreviewModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">
                    
                    <div class="px-6 py-5 border-b border-slate-200 flex items-start justify-between gap-4 bg-white">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-900 text-white uppercase tracking-wider">
                                    Disposisi
                                </span>
                                <h2 id="disposition-preview-title" class="text-base font-bold text-slate-900">
                                    Catatan & Penerima Disposisi
                                </h2>
                            </div>
                            <p class="text-xs text-slate-500 font-mono mt-1" x-text="dispositionPreviewMail ? 'No: ' + dispositionPreviewMail.mail_number : ''"></p>
                        </div>
                        <button type="button" @click="showDispositionPreviewModal = false"
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto space-y-5 bg-slate-50/60">
                        <div x-show="isLoadingDispositionPreview" class="py-12 flex items-center justify-center gap-3 text-sm font-semibold text-slate-600">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Memuat data disposisi...
                        </div>

                        <div x-show="!isLoadingDispositionPreview" class="space-y-5">
                            <!-- Catatan Disposisi Card -->
                            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-2">
                                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Isi Catatan Disposisi
                                </span>
                                <p class="text-sm text-slate-800 whitespace-pre-line leading-relaxed font-medium"
                                    x-text="dispositionPreviewMail?.disposition_note || 'Tidak ada catatan khusus.'"></p>
                            </div>

                            <!-- Daftar Penerima Disposisi -->
                            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                        Penerima & Tanda Tangan (<span x-text="dispositionPreviewRecipients.length"></span>)
                                    </h3>
                                </div>

                                <template x-if="dispositionPreviewRecipients.length === 0">
                                    <p class="text-xs text-slate-400 italic py-2">
                                        Belum ada daftar tanda tangan penerima tercatat untuk disposisi ini.
                                    </p>
                                </template>

                                <div class="space-y-3" x-show="dispositionPreviewRecipients.length > 0">
                                    <template x-for="(recipient, index) in dispositionPreviewRecipients" :key="recipient.id || index">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-slate-900" x-text="recipient.recipient_name"></span>
                                                    <span x-show="recipient.has_signature" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                        Sudah Ditandatangani
                                                    </span>
                                                    <span x-show="!recipient.has_signature" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-700">
                                                        Belum Ditandatangani
                                                    </span>
                                                </div>
                                                <p class="text-xs text-slate-600" x-text="'Penandatangan: ' + (recipient.signer_name || '-')"></p>
                                                <p class="text-[11px] text-slate-400 font-mono" x-show="recipient.signed_at"
                                                    x-text="'Waktu: ' + (recipient.signed_at ? new Date(recipient.signed_at).toLocaleString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute:'2-digit'}) : '')"></p>
                                            </div>

                                            <div x-show="recipient.signature_url" class="shrink-0">
                                                <a :href="recipient.signature_url" target="_blank"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-800 transition-colors">
                                                    <span>Lihat Tanda Tangan</span>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between">
                        <template x-if="dispositionPreviewMail">
                            <a :href="`{{ url('/incoming-mails') }}/${dispositionPreviewMail.id}`"
                                class="text-xs font-bold text-slate-700 hover:text-slate-900 hover:underline">
                                Lihat Halaman Detail Surat →
                            </a>
                        </template>
                        <button type="button" @click="showDispositionPreviewModal = false"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all">
                            Tutup
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

        <!-- Teleported Upload Attachment Modal (Foto / Dokumen) -->
        <template x-teleport="body">
            <div x-show="showUploadAttachmentModal" x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-3 sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="upload-attachment-title"
                @keydown.escape.window="closeUploadAttachmentModal()">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closeUploadAttachmentModal()"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-xl max-h-[92vh] overflow-hidden flex flex-col"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95">

                    <!-- Header -->
                    <div class="px-5 sm:px-6 py-4 border-b border-slate-200 flex items-start justify-between gap-4">
                        <div class="space-y-0.5">
                            <h2 id="upload-attachment-title" class="text-base font-bold text-slate-900">
                                Berkas Dokumen
                            </h2>
                            <p class="text-xs text-slate-500 font-mono">
                                <span class="font-bold text-slate-800" x-text="uploadAttachmentMail.mail_number"></span>
                                <span class="mx-1">·</span>
                                <span class="truncate max-w-md inline-block align-bottom font-sans text-slate-600" x-text="uploadAttachmentMail.subject"></span>
                            </p>
                        </div>
                        <button type="button" @click="closeUploadAttachmentModal()" :disabled="isUploadingAttachments"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors disabled:opacity-50 cursor-pointer">
                            Tutup
                        </button>
                    </div>

                    <!-- Body (Scrollable) -->
                    <div class="px-5 sm:px-6 py-5 overflow-y-auto space-y-5">

                        <!-- Error Banner (if any) -->
                        <div x-show="uploadAttachmentError" x-cloak class="p-3 bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-800 font-medium">
                            <span x-text="uploadAttachmentError"></span>
                        </div>

                        <!-- Section A: Existing Attachments -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Berkas Terunggah <span class="text-slate-400 font-normal font-mono" x-show="!isLoadingAttachments" x-text="'(' + existingAttachments.length + ')'"></span>
                                </span>
                            </div>

                            <!-- Skeleton loader while fetching -->
                            <div x-show="isLoadingAttachments" class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs text-slate-500 font-medium">
                                Memuat berkas...
                            </div>

                            <!-- Empty State -->
                            <div x-show="!isLoadingAttachments && existingAttachments.length === 0" class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center text-xs text-slate-400 italic">
                                Belum ada berkas terunggah.
                            </div>

                            <!-- Attachments List -->
                            <div x-show="!isLoadingAttachments && existingAttachments.length > 0" class="space-y-2 max-h-52 overflow-y-auto">
                                <template x-for="att in existingAttachments" :key="att.id">
                                    <div class="p-3 bg-white border border-slate-200 rounded-xl flex items-center justify-between text-xs gap-3">
                                        <div class="flex items-center space-x-2.5 min-w-0 pr-1">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200 flex-shrink-0"
                                                x-text="att.is_image ? 'FOTO' : 'PDF'">
                                            </span>
                                            <div class="truncate">
                                                <span class="font-bold text-slate-900 truncate block" x-text="att.file_name" :title="att.file_name"></span>
                                                <span class="text-[10px] text-slate-400 font-mono" x-show="att.file_size" x-text="formatBytes(att.file_size)"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-1.5 flex-shrink-0">
                                            <a :href="att.preview_url" target="_blank"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold rounded-lg text-xs transition-all border border-slate-200">
                                                Lihat
                                            </a>
                                            <template x-if="!att.is_legacy">
                                                <button type="button" @click="deleteAttachment(att.id)" :disabled="isDeletingAttachmentId === att.id"
                                                    class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-600 hover:text-slate-900 font-semibold rounded-lg text-xs transition-all border border-slate-200 cursor-pointer disabled:opacity-50">
                                                    Hapus
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Section B: Upload Zone -->
                        <div class="space-y-3 pt-3 border-t border-slate-100">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                                Tambah Berkas Baru
                            </span>

                            <!-- Drag & Drop Zone -->
                            <div class="border border-dashed border-slate-300 hover:border-slate-800 bg-slate-50/70 hover:bg-slate-100/70 rounded-xl p-5 text-center transition-all cursor-pointer"
                                @click="$refs.attachmentFileInput.click()"
                                @dragover.prevent="$el.classList.add('border-slate-900', 'bg-slate-100')"
                                @dragleave.prevent="$el.classList.remove('border-slate-900', 'bg-slate-100')"
                                @drop.prevent="$el.classList.remove('border-slate-900', 'bg-slate-100'); handleAttachmentDrop($event)">
                                
                                <input type="file" x-ref="attachmentFileInput" multiple accept="image/*,application/pdf"
                                    class="hidden" @change="handleAttachmentFileInput($event)">

                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-slate-900">
                                        Pilih Berkas atau Tarik ke Sini
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-mono">
                                        PDF, JPG, PNG, WEBP (Maks. 10MB)
                                    </p>
                                </div>
                            </div>

                            <!-- Staged Files Preview -->
                            <div x-show="selectedAttachmentFiles.length > 0" x-cloak class="space-y-2 pt-2">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                                    <span>Berkas Terpilih (<span x-text="selectedAttachmentFiles.length"></span>)</span>
                                    <button type="button" @click="clearSelectedAttachments()" class="text-slate-500 hover:text-slate-800 font-semibold cursor-pointer">
                                        Bersihkan
                                    </button>
                                </div>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <template x-for="(item, index) in selectedAttachmentFiles" :key="index">
                                        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs gap-3">
                                            <div class="flex items-center space-x-2.5 min-w-0 pr-1">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-800 border border-slate-300 flex-shrink-0"
                                                    x-text="item.is_image ? 'FOTO' : 'PDF'">
                                                </span>
                                                <div class="truncate">
                                                    <span class="font-bold text-slate-900 truncate block" x-text="item.name"></span>
                                                    <span class="text-[10px] text-slate-500 font-mono" x-text="formatBytes(item.size)"></span>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeSelectedAttachmentFile(index)"
                                                class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-600 hover:text-slate-900 font-semibold rounded-lg text-xs border border-slate-200 cursor-pointer">
                                                Batal
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
                        <button type="button" @click="closeUploadAttachmentModal()" :disabled="isUploadingAttachments"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition-all disabled:opacity-50 cursor-pointer">
                            Tutup
                        </button>
                        <button type="button" @click="submitUploadAttachments()"
                            :disabled="selectedAttachmentFiles.length === 0 || isUploadingAttachments"
                            class="px-5 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <span x-text="isUploadingAttachments ? 'Mengunggah...' : 'Unggah'"></span>
                        </button>
                    </div>

                </div>
            </div>
        </template>

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

                // Disposition workflow state
                showDispositionModal: false,
                isLoadingDisposition: false,
                isSavingDisposition: false,
                dispositionDirty: false,
                dispositionNote: '',
                dispositionError: '',
                dispositionMailIds: [],
                dispositionMailLabel: '',
                dispositionRecipients: [],
                dispositionCanvases: {},
                dispositionDrawingKey: null,
                dispositionPreviousFocus: null,

                // Disposition preview state (Fitur Intip Catatan Status Disposisi)
                showDispositionPreviewModal: false,
                isLoadingDispositionPreview: false,
                dispositionPreviewMail: null,
                dispositionPreviewRecipients: [],

                // Upload Attachment Modal state (Foto / Dokumen)
                showUploadAttachmentModal: false,
                isLoadingAttachments: false,
                isUploadingAttachments: false,
                isDeletingAttachmentId: null,
                uploadAttachmentMail: {
                    id: '',
                    mail_number: '',
                    subject: '',
                },
                existingAttachments: [],
                selectedAttachmentFiles: [],
                uploadAttachmentError: '',

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

                    window.resumeDispositionWorkflow = (id, mailNumber) => {
                        this.openDispositionWorkflow([id], mailNumber, true);
                    };

                    window.openDispositionPreviewModal = (id, mailNumber) => {
                        this.openDispositionPreview(id, mailNumber);
                    };

                    window.openUploadAttachmentModal = (id, mailNumber, subject) => {
                        this.openUploadAttachmentModal(id, mailNumber, subject);
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
                    const checkOverflow = () => {
                        if (this.showConfirmModal || this.showReturnModal || this.showDispositionModal || this.showDispositionPreviewModal || this.showUploadAttachmentModal) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    };

                    this.$watch('showConfirmModal', checkOverflow);
                    this.$watch('showReturnModal', checkOverflow);
                    this.$watch('showDispositionModal', checkOverflow);
                    this.$watch('showDispositionPreviewModal', checkOverflow);
                    this.$watch('showUploadAttachmentModal', checkOverflow);
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
                        WAITING_DISPOSISI: 'bg-cyan-500',
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

                newDispositionRecipient(data = {}) {
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

                async openDispositionWorkflow(mailIds, label, resume = false) {
                    this.showStatusDropdown = false;
                    this.dispositionPreviousFocus = document.activeElement;
                    this.dispositionMailIds = [...mailIds];
                    this.dispositionMailLabel = label || 'Dokumen terpilih';
                    this.dispositionNote = '';
                    this.dispositionRecipients = [this.newDispositionRecipient()];
                    this.dispositionCanvases = {};
                    this.dispositionError = '';
                    this.dispositionDirty = false;
                    this.showDispositionModal = true;

                    if (!resume) return;

                    this.isLoadingDisposition = true;
                    try {
                        const baseUrl = @js(url('/incoming-mails'));
                        const response = await fetch(`${baseUrl}/${encodeURIComponent(mailIds[0])}/disposition-recipients`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Progres disposisi gagal dimuat.');
                        }

                        this.dispositionMailLabel = data.mail?.mail_number || this.dispositionMailLabel;
                        this.dispositionNote = data.mail?.disposition_note || '';
                        this.dispositionRecipients = data.recipients?.length
                            ? data.recipients.map(recipient => this.newDispositionRecipient(recipient))
                            : [this.newDispositionRecipient()];
                    } catch (error) {
                        console.error('Disposition workflow load error:', error);
                        this.dispositionError = error.message || 'Progres disposisi gagal dimuat.';
                    } finally {
                        this.isLoadingDisposition = false;
                    }
                },

                closeDispositionModal() {
                    if (this.isSavingDisposition) return;
                    if (this.dispositionDirty && !window.confirm('Perubahan yang belum disimpan akan hilang. Tutup proses ini?')) return;

                    this.dispositionDirty = false;
                    this.showDispositionModal = false;
                    this.dispositionError = '';
                    this.$nextTick(() => this.dispositionPreviousFocus?.focus?.());
                },

                addDispositionRecipient() {
                    this.dispositionRecipients.push(this.newDispositionRecipient());
                    this.dispositionDirty = true;
                },

                removeDispositionRecipient(index) {
                    if (this.dispositionRecipients.length === 1) return;
                    const [removed] = this.dispositionRecipients.splice(index, 1);
                    delete this.dispositionCanvases[removed.client_key];
                    this.dispositionDirty = true;
                },

                setupDispositionCanvas(recipient, canvas) {
                    const context = canvas.getContext('2d');
                    context.lineWidth = 3;
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.strokeStyle = '#0f172a';
                    this.dispositionCanvases[recipient.client_key] = canvas;

                    if (recipient.signature_url) {
                        const image = new Image();
                        image.onload = () => context.drawImage(image, 0, 0, canvas.width, canvas.height);
                        image.src = recipient.signature_url;
                    }
                },

                dispositionCanvasPosition(event, canvas) {
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: (event.clientX - rect.left) * (canvas.width / rect.width),
                        y: (event.clientY - rect.top) * (canvas.height / rect.height),
                    };
                },

                startDispositionSignature(event, recipient) {
                    const canvas = this.dispositionCanvases[recipient.client_key];
                    if (!canvas) return;
                    canvas.setPointerCapture?.(event.pointerId);
                    const point = this.dispositionCanvasPosition(event, canvas);
                    const context = canvas.getContext('2d');
                    context.beginPath();
                    context.moveTo(point.x, point.y);
                    this.dispositionDrawingKey = recipient.client_key;
                },

                drawDispositionSignature(event, recipient) {
                    if (this.dispositionDrawingKey !== recipient.client_key) return;
                    const canvas = this.dispositionCanvases[recipient.client_key];
                    if (!canvas) return;
                    const point = this.dispositionCanvasPosition(event, canvas);
                    const context = canvas.getContext('2d');
                    context.lineTo(point.x, point.y);
                    context.stroke();
                },

                finishDispositionSignature(event, recipient) {
                    if (this.dispositionDrawingKey !== recipient.client_key) return;
                    const canvas = this.dispositionCanvases[recipient.client_key];
                    this.dispositionDrawingKey = null;
                    if (!canvas) return;
                    canvas.releasePointerCapture?.(event.pointerId);
                    recipient.signature = canvas.toDataURL('image/png');
                    recipient.has_signature = true;
                    recipient.remove_signature = false;
                    this.dispositionDirty = true;
                },

                clearDispositionSignature(recipient) {
                    const canvas = this.dispositionCanvases[recipient.client_key];
                    canvas?.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height);
                    recipient.signature = null;
                    recipient.signature_url = null;
                    recipient.has_signature = false;
                    recipient.remove_signature = true;
                    this.dispositionDirty = true;
                },

                get unsignedDispositionRecipientCount() {
                    return this.dispositionRecipients.filter(
                        recipient => !recipient.signer_name.trim() || (!recipient.signature && !recipient.has_signature)
                    ).length;
                },

                skipRemainingDispositionSignatures() {
                    const pending = this.unsignedDispositionRecipientCount;

                    if (pending === 0) {
                        this.saveDispositionWorkflow('complete');
                        return;
                    }

                    const confirmed = window.confirm(
                        `${pending} penerima belum menandatangani. Disposisi akan ditutup dan status menjadi DISPOSISI, ` +
                        'sementara penerima tersebut tetap tercatat belum menandatangani. Lanjutkan?'
                    );

                    if (confirmed) {
                        this.saveDispositionWorkflow('skip_remaining');
                    }
                },

                async saveDispositionWorkflow(action) {
                    if (this.isSavingDisposition) return;

                    const note = this.dispositionNote.trim();
                    if (!note) {
                        this.dispositionError = 'Catatan disposisi wajib diisi.';
                        return;
                    }

                    const missingRecipient = this.dispositionRecipients.find(recipient => !recipient.recipient_name.trim());
                    if (missingRecipient) {
                        this.dispositionError = 'Nama setiap penerima / unit tujuan disposisi wajib diisi.';
                        return;
                    }

                    if (action === 'complete') {
                        const incomplete = this.dispositionRecipients.find(recipient =>
                            !recipient.signer_name.trim() || (!recipient.signature && !recipient.has_signature)
                        );
                        if (incomplete) {
                            this.dispositionError = 'Lengkapi nama penandatangan dan tanda tangan seluruh penerima sebelum menyelesaikan disposisi.';
                            return;
                        }
                    }

                    this.isSavingDisposition = true;
                    this.dispositionError = '';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                        const response = await fetch(@js(route('incoming-mails.disposition-workflow.store')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                mail_ids: this.dispositionMailIds,
                                disposition_note: note,
                                action,
                                recipients: this.dispositionRecipients.map(recipient => ({
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
                            throw new Error(validationMessage || result.message || 'Progres disposisi gagal disimpan.');
                        }

                        this.dispositionDirty = false;
                        this.showDispositionModal = false;
                        this.showToast(result.message);
                        this.selectedIds = [];
                        await this.refreshTableData();
                    } catch (error) {
                        console.error('Disposition workflow save error:', error);
                        this.dispositionError = error.message || 'Progres disposisi gagal disimpan.';
                    } finally {
                        this.isSavingDisposition = false;
                    }
                },

                async openDispositionPreview(mailId, mailNumber) {
                    this.showDispositionPreviewModal = true;
                    this.isLoadingDispositionPreview = true;
                    this.dispositionPreviewMail = {
                        id: mailId,
                        mail_number: mailNumber,
                        disposition_note: '',
                    };
                    this.dispositionPreviewRecipients = [];

                    try {
                        const baseUrl = @js(url('/incoming-mails'));
                        const response = await fetch(`${baseUrl}/${encodeURIComponent(mailId)}/disposition-recipients`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Catatan disposisi gagal dimuat.');
                        }

                        this.dispositionPreviewMail = data.mail;
                        this.dispositionPreviewRecipients = data.recipients || [];
                    } catch (error) {
                        console.error('Disposition preview load error:', error);
                    } finally {
                        this.isLoadingDispositionPreview = false;
                    }
                },

                openUploadAttachmentModal(mailId, mailNumber, subject) {
                    this.showUploadAttachmentModal = true;
                    this.uploadAttachmentError = '';
                    this.selectedAttachmentFiles = [];
                    this.existingAttachments = [];
                    this.uploadAttachmentMail = {
                        id: mailId,
                        mail_number: mailNumber || '-',
                        subject: subject || '-',
                    };
                    this.fetchAttachments(mailId);
                },

                closeUploadAttachmentModal() {
                    if (this.isUploadingAttachments) return;
                    this.showUploadAttachmentModal = false;
                    this.clearSelectedAttachments();
                    this.uploadAttachmentError = '';
                },

                clearSelectedAttachments() {
                    this.selectedAttachmentFiles.forEach(item => {
                        if (item.preview_url) URL.revokeObjectURL(item.preview_url);
                    });
                    this.selectedAttachmentFiles = [];
                },

                async fetchAttachments(mailId) {
                    this.isLoadingAttachments = true;
                    try {
                        const baseUrl = @js(url('/incoming-mails'));
                        const response = await fetch(`${baseUrl}/${encodeURIComponent(mailId)}/attachments`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();
                        if (response.ok && data.success) {
                            this.existingAttachments = data.attachments || [];
                            if (data.mail) {
                                this.uploadAttachmentMail.mail_number = data.mail.mail_number;
                                this.uploadAttachmentMail.subject = data.mail.subject;
                            }
                        } else {
                            this.uploadAttachmentError = data.message || 'Gagal memuat berkas surat.';
                        }
                    } catch (err) {
                        console.error('Fetch attachments error:', err);
                        this.uploadAttachmentError = 'Terjadi kesalahan saat memuat berkas.';
                    } finally {
                        this.isLoadingAttachments = false;
                    }
                },

                handleAttachmentFileInput(event) {
                    const files = Array.from(event.target.files || []);
                    if (!files.length) return;

                    files.forEach(file => {
                        const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                        const isImg = file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name);
                        if (!isPdf && !isImg) return;

                        const exists = this.selectedAttachmentFiles.some(f => f.name === file.name && f.size === file.size);
                        if (!exists) {
                            const previewUrl = isImg ? URL.createObjectURL(file) : null;
                            this.selectedAttachmentFiles.push({
                                file: file,
                                name: file.name,
                                size: file.size,
                                is_image: isImg,
                                preview_url: previewUrl,
                            });
                        }
                    });
                    event.target.value = '';
                    this.uploadAttachmentError = '';
                },

                handleAttachmentDrop(event) {
                    const files = Array.from(event.dataTransfer?.files || []);
                    if (!files.length) return;

                    files.forEach(file => {
                        const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                        const isImg = file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name);
                        if (!isPdf && !isImg) return;

                        const exists = this.selectedAttachmentFiles.some(f => f.name === file.name && f.size === file.size);
                        if (!exists) {
                            const previewUrl = isImg ? URL.createObjectURL(file) : null;
                            this.selectedAttachmentFiles.push({
                                file: file,
                                name: file.name,
                                size: file.size,
                                is_image: isImg,
                                preview_url: previewUrl,
                            });
                        }
                    });
                    this.uploadAttachmentError = '';
                },

                removeSelectedAttachmentFile(index) {
                    const item = this.selectedAttachmentFiles[index];
                    if (item && item.preview_url) {
                        URL.revokeObjectURL(item.preview_url);
                    }
                    this.selectedAttachmentFiles.splice(index, 1);
                },

                async submitUploadAttachments() {
                    if (this.selectedAttachmentFiles.length === 0 || this.isUploadingAttachments) {
                        return;
                    }

                    this.isUploadingAttachments = true;
                    this.uploadAttachmentError = '';

                    const formData = new FormData();
                    this.selectedAttachmentFiles.forEach(item => {
                        formData.append('files[]', item.file);
                    });

                    try {
                        const baseUrl = @js(url('/incoming-mails'));
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                        const response = await fetch(`${baseUrl}/${encodeURIComponent(this.uploadAttachmentMail.id)}/attachments`, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            let msg = data.message || 'Gagal mengunggah foto atau dokumen.';
                            if (data.errors) {
                                const errorMessages = Object.values(data.errors).flat();
                                if (errorMessages.length > 0) msg = errorMessages.join(' ');
                            }
                            throw new Error(msg);
                        }

                        this.clearSelectedAttachments();
                        this.showToast(data.message || 'Foto / dokumen berhasil ditambahkan.');
                        await this.fetchAttachments(this.uploadAttachmentMail.id);
                        await this.refreshTableData();
                    } catch (err) {
                        console.error('Upload attachments error:', err);
                        this.uploadAttachmentError = err.message || 'Terjadi kesalahan saat mengunggah berkas.';
                    } finally {
                        this.isUploadingAttachments = false;
                    }
                },

                async deleteAttachment(attachmentId) {
                    if (!confirm('Apakah Anda yakin ingin menghapus berkas ini?')) return;

                    this.isDeletingAttachmentId = attachmentId;
                    this.uploadAttachmentError = '';

                    try {
                        const baseUrl = @js(url('/incoming-mails'));
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                        const response = await fetch(`${baseUrl}/${encodeURIComponent(this.uploadAttachmentMail.id)}/attachments/${encodeURIComponent(attachmentId)}`, {
                            method: 'DELETE',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Gagal menghapus berkas.');
                        }

                        this.showToast('Berkas berhasil dihapus.');
                        await this.fetchAttachments(this.uploadAttachmentMail.id);
                        await this.refreshTableData();
                    } catch (err) {
                        console.error('Delete attachment error:', err);
                        this.uploadAttachmentError = err.message || 'Terjadi kesalahan saat menghapus berkas.';
                    } finally {
                        this.isDeletingAttachmentId = null;
                    }
                },

                formatBytes(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
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
                    this.$nextTick(() => {
                        this.updateCheckboxStates();
                    });
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
                        
                        // Update table content.
                        // Jangan panggil Alpine.destroyTree() pada element x-ref: itu juga
                        // menghapus registrasi $ref (dan listener @click) milik tableBody
                        // sehingga baris lama tetap tampil tapi tidak bisa diklik.
                        // Alpine mendeteksi node baru lewat MutationObserver.
                        this.$refs.tableBody.innerHTML = data.html;
                        
                        // Update pagination
                        this.$refs.paginationContainer.innerHTML = data.pagination;
                        
                        // Update total results
                        this.totalResults = data.total;
                        
                        // RESTORE selectedIds setelah DOM update
                        this.selectedIds = preservedSelections;
                        
                        // Re-check checkboxes and update row styling
                        this.$nextTick(() => {
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

                    if (this.normalizeStatus(targetStatus) === 'DISPOSISI') {
                        this.openDispositionWorkflow(eligibleIds, label);
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

                    // Disposisi selalu wajib disertai alur catatan disposisi & penerima.
                    if (target === 'DISPOSISI') {
                        this.openDispositionWorkflow(mailIds, label);
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
                        
                        // Update table content.
                        // Jangan panggil Alpine.destroyTree() pada element x-ref: itu juga
                        // menghapus registrasi $ref (dan listener @click) milik tableBody
                        // sehingga baris lama tetap tampil tapi tidak bisa diklik.
                        // Alpine mendeteksi node baru lewat MutationObserver.
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

                    if (targetStatus === 'DISPOSISI') {
                        this.openDispositionWorkflow([detail.id], detail.mailNumber);
                        return;
                    }

                    this.requestStatusChange([detail.id], `dokumen ${detail.mailNumber}`, targetStatus);
                }
            };
        }

        window.incomingMailSelector = incomingMailSelector;

        if (window.Alpine) {
            window.Alpine.data('incomingMailSelector', incomingMailSelector);
        } else {
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('incomingMailSelector', incomingMailSelector);
            });
        }
    </script>
@endsection
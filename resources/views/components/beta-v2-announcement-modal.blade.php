@auth
    <div x-data="{
        isOpen: false,
        modalMode: 'welcome',
        init() {
            const userId = '{{ auth()->id() }}';
            const storageKey = 'seen_beta_v2_modal_user_' + userId;
            const hasSeen = localStorage.getItem(storageKey);
            const justLoggedIn = {{ session('first_login_beta_v2') ? 'true' : 'false' }};

            if (justLoggedIn || !hasSeen) {
                setTimeout(() => {
                    this.modalMode = 'welcome';
                    this.isOpen = true;
                }, 300);
            }
        },
        openDetail() {
            this.modalMode = 'detail';
            this.isOpen = true;
        },
        dismissWelcome() {
            const userId = '{{ auth()->id() }}';
            localStorage.setItem('seen_beta_v2_modal_user_' + userId, 'true');
            this.isOpen = false;
        },
        closeModal() {
            this.isOpen = false;
        }
    }"
    @open-beta-v2-modal.window="openDetail()"
    @keydown.escape.window="isOpen = false"
    x-cloak>

        <template x-teleport="body">
            <div x-show="isOpen" x-cloak
                class="fixed inset-0 z-[100001] flex items-center justify-center p-3 sm:p-5"
                role="dialog" aria-modal="true">

                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="isOpen = false"></div>

                <!-- 1. Modal Pertama: Hanya Informasi Terima Kasih (Compact) -->
                <div x-show="modalMode === 'welcome'"
                    class="relative bg-white rounded-2xl shadow-xl border border-slate-300 w-full max-w-md overflow-hidden flex flex-col z-10"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95">

                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block">Beta v2.0</span>
                            <h2 class="text-base font-bold text-slate-900">
                                Terima Kasih
                            </h2>
                        </div>
                        <button type="button" @click="dismissWelcome()"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-900 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                            Tutup
                        </button>
                    </div>

                    <div class="p-6 space-y-3 text-xs text-slate-700 leading-relaxed">
                        <p class="font-medium text-slate-900">
                            Terima kasih telah berpartisipasi dalam testing kedua ini ya :).
                        </p>
                        <p class="text-slate-600">
                            Informasi mengenai pembaruan dan perubahan sistem dapat dilihat pada halaman <strong>Beta v2</strong> di menu navigasi atas.
                        </p>
                    </div>

                    <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button type="button" @click="dismissWelcome()"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-bold rounded-xl transition-all cursor-pointer">
                            Mengerti
                        </button>
                        <button type="button" @click="modalMode = 'detail'"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all cursor-pointer">
                            Lihat Detail
                        </button>
                    </div>
                </div>

                <!-- 2. Modal Kedua: Detail Pembaruan (Dari Navigasi) -->
                <div x-show="modalMode === 'detail'"
                    class="relative bg-white rounded-2xl shadow-xl border border-slate-300 w-full max-w-xl max-h-[90vh] overflow-hidden flex flex-col z-10"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95">

                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block">Beta v2.0</span>
                            <h2 class="text-base font-bold text-slate-900">
                                Detail Pembaruan
                            </h2>
                        </div>
                        <button type="button" @click="closeModal()"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-900 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                            Tutup
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto space-y-2.5 text-xs text-slate-700 leading-normal">
                        <div class="p-3 border border-slate-200 rounded-xl">
                            <h3 class="font-bold text-slate-900">1. Alur Status Dokumen & SOP</h3>
                            <p class="text-slate-600 mt-0.5">Penambahan status Revisi dan Disposisi dengan kewajiban input catatan.</p>
                        </div>

                        <div class="p-3 border border-slate-200 rounded-xl">
                            <h3 class="font-bold text-slate-900">2. Riwayat Catatan Revisi</h3>
                            <p class="text-slate-600 mt-0.5">Riwayat revisi, waktu, dan nama pembuat ditampilkan pada detail surat.</p>
                        </div>

                        <div class="p-3 border border-slate-200 rounded-xl">
                            <h3 class="font-bold text-slate-900">3. Tanda Tangan Penerimaan Berkas</h3>
                            <p class="text-slate-600 mt-0.5">Tanda tangan digital penerimaan fisik dokumen dengan dukungan simpan bertahap.</p>
                        </div>

                        <div class="p-3 border border-slate-200 rounded-xl">
                            <h3 class="font-bold text-slate-900">4. Alur Surat Keluar</h3>
                            <p class="text-slate-600 mt-0.5">Siklus status lengkap dan perbaikan pembaruan status massal.</p>
                        </div>

                        <div class="p-3 border border-slate-200 rounded-xl">
                            <h3 class="font-bold text-slate-900">5. Repositori & Edit Batch</h3>
                            <p class="text-slate-600 mt-0.5">Tampilan arsip dokumen per bulan dan fitur edit massal surat masuk.</p>
                        </div>

                        <div class="p-3 border border-slate-200 rounded-xl">
                            <h3 class="font-bold text-slate-900">6. Antarmuka & Form Input</h3>
                            <p class="text-slate-600 mt-0.5">Penyesuaian tata letak, tombol checklist tabel, dan pembersihan placeholder form.</p>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all cursor-pointer">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </template>
    </div>
@endauth

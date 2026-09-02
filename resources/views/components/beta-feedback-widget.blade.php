@auth
<div x-data="betaFeedbackWidget()" x-cloak class="fixed bottom-5 right-5 z-[999]">
    
    <!-- Ultra Liquid Glass Floating Trigger Orb (Icon Only for Navigation) -->
    <button type="button"
        @click="toggleWidget()"
        title="Beta Feedback & Error"
        class="group relative flex items-center justify-center w-12 h-12 bg-white/35 hover:bg-white/60 text-slate-800 rounded-full shadow-lg backdrop-blur-2xl transition-all duration-300 transform hover:scale-105 active:scale-95 border border-white/60 cursor-pointer">
        
        <!-- Navigation Button Icon -->
        <template x-if="!isOpen">
            <div class="relative flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-800 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z" />
                </svg>
                <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-slate-900 border border-white"></span>
                </span>
            </div>
        </template>

        <!-- Close Navigation Icon when Opened -->
        <template x-if="isOpen">
            <svg class="w-5 h-5 text-slate-800 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </template>
    </button>

    <!-- Ultra Liquid Glass Popover Drawer Window -->
    <div x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-3"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-3"
        @click.away="isOpen = false"
        class="absolute bottom-15 right-0 w-[92vw] sm:w-[380px] bg-white/35 backdrop-blur-3xl rounded-3xl border border-white/60 shadow-[0_20px_50px_rgba(15,23,42,0.12),inset_0_1.5px_2px_rgba(255,255,255,0.8)] overflow-hidden flex flex-col z-[1000] max-h-[82vh] sm:max-h-[580px]">
        
        <!-- Minimal Glass Header -->
        <div class="px-5 py-3.5 border-b border-white/40 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-slate-900 tracking-tight">Beta Feedback</span>
                <span class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white/40 border border-white/60 rounded-full font-mono">v1.0</span>
            </div>
            <button type="button" @click="isOpen = false" class="text-slate-400 hover:text-slate-800 p-1 rounded-full hover:bg-white/40 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Segment Switcher (Minimal Glass Pill) -->
        <div class="p-1 mx-4 mt-3 bg-white/20 border border-white/40 rounded-2xl flex backdrop-blur-md text-[11px] font-semibold">
            <button type="button"
                @click="activeTab = 'create'"
                :class="activeTab === 'create' ? 'bg-white/75 text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                class="flex-1 py-1.5 text-center rounded-xl transition-all cursor-pointer">
                Lapor Error
            </button>
            <button type="button"
                @click="activeTab = 'history'; fetchHistory()"
                :class="activeTab === 'history' ? 'bg-white/75 text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                class="flex-1 py-1.5 text-center rounded-xl transition-all cursor-pointer flex items-center justify-center space-x-1">
                <span>Log Laporan</span>
                <template x-if="historyList.length > 0">
                    <span class="px-1.5 py-0.2 text-[9px] font-mono bg-white/60 rounded-full text-slate-800" x-text="historyList.length"></span>
                </template>
            </button>
        </div>

        <!-- Body Content Area -->
        <div class="p-4 sm:p-5 overflow-y-auto flex-1 space-y-3">
            
            <!-- Tab 1: Form Lapor Error -->
            <div x-show="activeTab === 'create'" class="space-y-3">
                
                <div x-show="successMessage" x-cloak class="p-2.5 bg-white/50 border border-white/60 text-slate-800 rounded-2xl text-[11px] font-medium backdrop-blur-md flex items-center justify-between shadow-2xs">
                    <span x-text="successMessage"></span>
                    <button type="button" @click="successMessage = ''" class="text-slate-400 hover:text-slate-800 font-bold ml-2">×</button>
                </div>

                <form @submit.prevent="submitFeedback()" class="space-y-2.5">
                    <!-- Custom Liquid Glass Category Select Dropdown -->
                    <div class="relative" x-data="{ openCat: false }">
                        <button type="button" @click="openCat = !openCat"
                            class="w-full px-3 py-2 bg-white/30 hover:bg-white/50 border border-white/60 rounded-2xl text-[11px] text-slate-900 flex items-center justify-between transition-all backdrop-blur-md cursor-pointer font-medium shadow-2xs">
                            <span x-text="categoryLabels[form.category]"></span>
                            <svg class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': openCat }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openCat" x-cloak @click.away="openCat = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 right-0 mt-1 bg-white/85 backdrop-blur-2xl border border-white/90 rounded-2xl shadow-xl p-1 z-50 space-y-0.5">
                            <template x-for="(label, key) in categoryLabels" :key="key">
                                <button type="button" @click="form.category = key; openCat = false"
                                    :class="form.category === key ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-700 hover:bg-white/60'"
                                    class="w-full text-left px-3 py-1.5 text-[11px] rounded-xl transition-all flex items-center justify-between cursor-pointer">
                                    <span x-text="label"></span>
                                    <template x-if="form.category === key">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <input type="text" x-model="form.title" required placeholder="Judul ringkas kendala..." class="w-full px-3 py-2 bg-white/30 border border-white/50 rounded-2xl text-[11px] text-slate-900 placeholder:text-slate-400 focus:bg-white/70 focus:outline-none transition-all backdrop-blur-md">
                    </div>

                    <div>
                        <textarea x-model="form.description" required rows="3" placeholder="Rincian kendala..." class="w-full px-3 py-2 bg-white/30 border border-white/50 rounded-2xl text-[11px] text-slate-900 placeholder:text-slate-400 focus:bg-white/70 focus:outline-none transition-all backdrop-blur-md"></textarea>
                    </div>

                    <div>
                        <input type="file" @change="handleFileChange($event)" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full px-3 py-1.5 bg-white/30 border border-white/50 rounded-2xl text-[10px] text-slate-700 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-[10px] file:font-semibold file:bg-slate-900 file:text-white backdrop-blur-md">
                    </div>

                    <div class="p-2.5 bg-white/20 border border-white/40 rounded-2xl text-[10px] font-mono text-slate-500 backdrop-blur-md space-y-0.5">
                        <div class="truncate"><span class="text-slate-700">URL:</span> <span x-text="currentUrl"></span></div>
                        <div class="truncate"><span class="text-slate-700">User:</span> {{ auth()->user()->name }}</div>
                    </div>

                    <button type="submit"
                        :disabled="isSubmitting"
                        class="w-full py-2.5 bg-slate-900/90 hover:bg-slate-900 text-white text-xs font-semibold rounded-2xl backdrop-blur-md transition-all shadow-sm cursor-pointer flex items-center justify-center">
                        <span x-text="isSubmitting ? 'Mengirim...' : 'Kirim Laporan'"></span>
                    </button>
                </form>
            </div>

            <!-- Tab 2: Riwayat List -->
            <div x-show="activeTab === 'history'" class="space-y-2.5">
                <template x-if="isLoadingHistory">
                    <div class="py-8 text-center text-[11px] text-slate-400 italic">Memuat riwayat...</div>
                </template>

                <template x-if="!isLoadingHistory && historyList.length === 0">
                    <div class="py-8 text-center text-[11px] text-slate-400 italic">Belum ada riwayat.</div>
                </template>

                <template x-if="!isLoadingHistory && historyList.length > 0">
                    <div class="space-y-2.5">
                        <template x-for="item in historyList" :key="item.id">
                            <div class="p-3 bg-white/30 border border-white/50 rounded-2xl backdrop-blur-md space-y-1.5">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="px-2 py-0.5 text-[9px] font-mono font-semibold rounded-full bg-white/60 text-slate-800 border border-white/60" x-text="item.status_label"></span>
                                    <span class="text-[9px] font-mono text-slate-400" x-text="item.created_at_formatted"></span>
                                </div>

                                <div>
                                    <h4 class="text-[11px] font-bold text-slate-900" x-text="item.title"></h4>
                                    <p class="text-[10px] text-slate-600 mt-0.5 whitespace-pre-line" x-text="item.description"></p>
                                </div>

                                <template x-if="item.attachment_url">
                                    <a :href="item.attachment_url" target="_blank" class="text-[10px] text-slate-900 font-semibold underline block">
                                        Lampiran Berkas
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

        </div>
    </div>
</div>

<script>
    function betaFeedbackWidget() {
        return {
            isOpen: false,
            activeTab: 'create',
            isSubmitting: false,
            isLoadingHistory: false,
            successMessage: '',
            currentUrl: window.location.href,
            historyList: [],
            file: null,
            categoryLabels: {
                'BUG': 'Error / Bug Aplikasi',
                'UI_UX': 'Tampilan / UX',
                'PERFORMANCE': 'Kinerja / Lambat',
                'OTHER': 'Lainnya / Saran'
            },
            form: {
                category: 'BUG',
                title: '',
                description: '',
            },

            toggleWidget() {
                this.isOpen = !this.isOpen;
                if (this.isOpen && this.activeTab === 'history') {
                    this.fetchHistory();
                }
            },

            handleFileChange(e) {
                if (e.target.files && e.target.files[0]) {
                    this.file = e.target.files[0];
                } else {
                    this.file = null;
                }
            },

            async submitFeedback() {
                this.isSubmitting = true;
                this.successMessage = '';

                try {
                    const formData = new FormData();
                    formData.append('category', this.form.category);
                    formData.append('title', this.form.title);
                    formData.append('description', this.form.description);
                    formData.append('page_url', window.location.href);
                    if (this.file) {
                        formData.append('attachment', this.file);
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const response = await fetch("{{ route('beta-feedback.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.successMessage = result.message;
                        this.form.title = '';
                        this.form.description = '';
                        this.file = null;
                        this.activeTab = 'history';
                        this.fetchHistory();
                    } else {
                        alert(result.message || 'Gagal mengirim laporan. Silakan periksa kembali inputan Anda.');
                    }
                } catch (err) {
                    alert('Terjadi kesalahan jaringan saat mengirim laporan.');
                } finally {
                    this.isSubmitting = false;
                }
            },

            async fetchHistory() {
                this.isLoadingHistory = true;
                try {
                    const response = await fetch("{{ route('beta-feedback.history') }}", {
                        headers: {
                            'Accept': 'application/json',
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.historyList = result.feedbacks;
                    }
                } catch (err) {
                    console.error('Error fetching feedback history:', err);
                } finally {
                    this.isLoadingHistory = false;
                }
            }
        };
    }
</script>
@endauth

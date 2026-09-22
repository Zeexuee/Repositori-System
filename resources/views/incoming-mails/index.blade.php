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
                    Pilih Status
                </div>
                <div class="py-1">
                    <button type="button" @click="selectStatusOption('RECEIVE')"
                        class="w-full text-left px-3.5 py-2 text-xs font-semibold hover:bg-slate-50 transition-colors flex items-center justify-between"
                        :class="statusDropdownCurrentStatus === 'RECEIVE' ? 'text-slate-900 bg-slate-50 font-bold' : 'text-slate-700'">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                            <span>Receive</span>
                        </div>
                        <svg x-show="statusDropdownCurrentStatus === 'RECEIVE'" class="w-3.5 h-3.5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                    <button type="button" @click="selectStatusOption('PROGRES')"
                        class="w-full text-left px-3.5 py-2 text-xs font-semibold hover:bg-slate-50 transition-colors flex items-center justify-between"
                        :class="statusDropdownCurrentStatus === 'PROGRES' ? 'text-slate-900 bg-slate-50 font-bold' : 'text-slate-700'">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span>Progres</span>
                        </div>
                        <svg x-show="statusDropdownCurrentStatus === 'PROGRES'" class="w-3.5 h-3.5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                    <button type="button" @click="selectStatusOption('REVISI')"
                        class="w-full text-left px-3.5 py-2 text-xs font-semibold hover:bg-slate-50 transition-colors flex items-center justify-between"
                        :class="statusDropdownCurrentStatus === 'REVISI' ? 'text-slate-900 bg-slate-50 font-bold' : 'text-slate-700'">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span>Revisi</span>
                        </div>
                        <svg x-show="statusDropdownCurrentStatus === 'REVISI'" class="w-3.5 h-3.5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                    <button type="button" @click="selectStatusOption('RETURN')"
                        class="w-full text-left px-3.5 py-2 text-xs font-semibold hover:bg-slate-50 transition-colors flex items-center justify-between"
                        :class="statusDropdownCurrentStatus === 'RETURN' ? 'text-slate-900 bg-slate-50 font-bold' : 'text-slate-700'">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full border border-slate-400 bg-white"></span>
                            <span>Return</span>
                        </div>
                        <svg x-show="statusDropdownCurrentStatus === 'RETURN'" class="w-3.5 h-3.5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- Success Toast Notification -->
        <div x-show="showSuccessToast" 
            x-cloak
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
                    <p class="text-sm font-semibold">Berhasil!</p>
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
                            <button type="button" @click="bulkUpdateStatus('RECEIVE'); open = false"
                                class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                <span>Ubah ke Receive</span>
                                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                            </button>
                            <button type="button" @click="bulkUpdateStatus('PROGRES'); open = false"
                                class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                <span>Ubah ke Progres</span>
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            </button>
                            <button type="button" @click="bulkUpdateStatus('REVISI'); open = false"
                                class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                <span>Ubah ke Revisi</span>
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            </button>
                            <button type="button" @click="bulkUpdateStatus('RETURN'); open = false"
                                class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                <span>Ubah ke Return</span>
                                <span class="w-2 h-2 rounded-full border border-slate-400 bg-white"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Select / Batal Select -->
                <button type="button" @click="toggleSelectionMode()"
                    :class="isSelectionMode ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300 shadow-2xs'"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-xl transition-all border cursor-pointer">
                    <span x-text="isSelectionMode ? 'Batal Select' : 'Select'">Select</span>
                </button>

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
                            <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nama Penerima</th>
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
                
                // Modal state
                showConfirmModal: false,
                confirmMessage: '',
                confirmTargetStatus: '',
                isSubmitting: false,
                singleUpdateId: null,
                
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
                        if (value) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
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
                        
                        // Update allIds untuk current page
                        this.allIds = data.allIds;
                        
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
                    const statusLabel = targetStatus === 'RETURN' ? 'RETURN' : (targetStatus === 'PROGRES' ? 'PROGRES' : (targetStatus === 'REVISI' ? 'REVISI' : 'RECEIVE'));

                    let message = `Apakah Anda yakin ingin mengubah status <strong>${count} dokumen terpilih</strong> menjadi <strong>${statusLabel}</strong>?`;
                    if (targetStatus === 'RETURN') {
                        message += `<br/><br/><span class="text-xs text-slate-600">Catatan: Dokumen akan otomatis dicatat/disinkronkan pada Surat Keluar (RETURN).</span>`;
                    } else if (targetStatus === 'PROGRES') {
                        message += `<br/><br/><span class="text-xs text-slate-600">Catatan: Dokumen akan otomatis dicatat/disinkronkan pada Surat Keluar (PROGRES).</span>`;
                    } else if (targetStatus === 'REVISI') {
                        message += `<br/><br/><span class="text-xs text-slate-600">Catatan: Dokumen akan otomatis dicatat/disinkronkan pada Surat Keluar (REVISI).</span>`;
                    }

                    // Show confirmation modal
                    this.confirmMessage = message;
                    this.confirmTargetStatus = targetStatus;
                    this.singleUpdateId = null;
                    this.showConfirmModal = true;
                },

                async confirmBulkUpdate() {
                    if (this.isSubmitting) return;
                    
                    this.isSubmitting = true;
                    
                    try {
                        // Check if single update or bulk update
                        const isSingleUpdate = this.singleUpdateId !== null;
                        const idsToUpdate = isSingleUpdate ? [this.singleUpdateId] : this.selectedIds;
                        
                        const formData = new FormData();
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('status', this.confirmTargetStatus);
                        idsToUpdate.forEach(id => {
                            formData.append('ids[]', id);
                        });
                        
                        const response = await fetch('{{ route("incoming-mails.bulk-update-status") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        
                        const result = await response.json();
                        
                        // Close modal
                        this.showConfirmModal = false;
                        
                        // Show success toast
                        this.successMessage = result.message || `Status berhasil diperbarui.`;
                        this.showSuccessToast = true;
                        setTimeout(() => {
                            this.showSuccessToast = false;
                        }, 4000);
                        
                        // Update UI: refresh table dengan AJAX (preserve current page & search)
                        await this.refreshTableData();
                        
                        // Clear selections only for bulk update
                        if (!isSingleUpdate) {
                            this.selectedIds = [];
                        }
                        
                        // Reset single update id
                        this.singleUpdateId = null;
                        
                    } catch (error) {
                        console.error('Update error:', error);
                        alert('Terjadi kesalahan saat memperbarui status. Silakan coba lagi.');
                    } finally {
                        this.isSubmitting = false;
                    }
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
                        
                        // Update allIds
                        this.allIds = data.allIds;
                        
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
                    const targetStatus = detail.targetStatus || 'RETURN';
                    const statusLabel = targetStatus === 'RETURN' ? 'RETURN' : (targetStatus === 'PROGRES' ? 'PROGRES' : (targetStatus === 'REVISI' ? 'REVISI' : 'RECEIVE'));

                    let message = `Apakah Anda yakin ingin mengubah status dokumen <strong>${detail.mailNumber}</strong> dari <strong>${detail.currentStatus}</strong> menjadi <strong>${statusLabel}</strong>?`;
                    
                    if (targetStatus === 'RETURN') {
                        message += `<br/><br/><span class="text-xs text-slate-600">Catatan: Dokumen akan otomatis dicatat/disinkronkan pada Surat Keluar (RETURN).</span>`;
                    } else if (targetStatus === 'PROGRES') {
                        message += `<br/><br/><span class="text-xs text-slate-600">Catatan: Dokumen akan otomatis dicatat/disinkronkan pada Surat Keluar (PROGRES).</span>`;
                    } else if (targetStatus === 'REVISI') {
                        message += `<br/><br/><span class="text-xs text-slate-600">Catatan: Dokumen akan otomatis dicatat/disinkronkan pada Surat Keluar (REVISI).</span>`;
                    }

                    this.confirmMessage = message;
                    this.confirmTargetStatus = targetStatus;
                    this.singleUpdateId = detail.id;
                    this.showConfirmModal = true;
                }
            };
        }
    </script>
@endsection
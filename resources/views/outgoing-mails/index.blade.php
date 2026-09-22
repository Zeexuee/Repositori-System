@extends('layouts.app')

@section('title', 'Daftar Surat Keluar')

@section('content')
    <div x-data="outgoingMailStatusHandler()" class="space-y-4">
        <!-- Confirmation Modal -->
        <div x-show="showConfirmModal" 
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            style="margin: 0 !important;"
            @keydown.escape.window="showConfirmModal = false">
            
            <div class="fixed inset-0" @click="showConfirmModal = false"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full border-2 border-slate-900 overflow-hidden"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">
                
                <div class="px-6 py-5 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Konfirmasi Perubahan Status</h3>
                </div>
                
                <div class="px-6 py-5">
                    <p class="text-sm text-slate-700 leading-relaxed" x-html="confirmMessage"></p>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 flex items-center justify-end gap-3">
                    <button type="button" 
                        @click="showConfirmModal = false"
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl transition-all disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" 
                        @click="confirmStatusUpdate()"
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

        <!-- Hidden Form for Submission -->
        <form x-ref="statusForm" method="POST" class="hidden">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" x-ref="statusInput">
        </form>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Keluar</h1>
                <p class="text-xs text-slate-500 mt-1">Kelola pencatatan, verifikasi, dan persetujuan surat keluar.</p>
            </div>
            @can('create', App\Models\OutgoingMail::class)
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('outgoing-mails.create') }}"
                        class="inline-flex items-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                        <span>Tambah Surat Keluar</span>
                    </a>
                </div>
            @endcan
        </div>

        <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-full">
                    <thead>
                        <tr
                            class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
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
                    <tbody class="divide-y divide-slate-200 text-sm">
                        @forelse ($outgoingMails as $index => $mail)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
                                    {{ ($outgoingMails->firstItem() ?? 1) + $index }}
                                </td>
                                <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
                                    {{ $mail->mail_number ?? '-' }}
                                </td>
                                <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
                                    {{ $mail->created_at?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="py-4 px-4 sm:px-5 text-slate-800 font-medium min-w-[320px] max-w-2xl break-words leading-relaxed" title="{{ $mail->subject }}">
                                    {{ $mail->subject }}
                                </td>
                                <td class="py-4 px-4 sm:px-5 text-slate-700 font-medium whitespace-nowrap">{{ $mail->recipient }}</td>
                                <td class="py-4 px-4 sm:px-5 text-slate-600 font-medium whitespace-nowrap">{{ $mail->creator?->name ?? 'System' }}</td>
                                <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
                                    @php
                                        $badgeClasses = match ($mail->status) {
                                            'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 hover:bg-slate-800',
                                            'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 hover:bg-slate-300 font-bold',
                                            'REVISI' => 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 font-bold',
                                            'RETURN', 'RETURNED' => 'bg-white text-slate-800 border-slate-300 hover:bg-slate-50 font-bold',
                                            default => 'bg-slate-100 text-slate-700 border-slate-300',
                                        };
                                        $displayStatus = match ($mail->status) {
                                            'RECEIVED' => 'RECEIVE',
                                            'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                            'RETURNED' => 'RETURN',
                                            default => $mail->status,
                                        };
                                    @endphp

                                    @if (auth()->user()?->can('update', $mail))
                                        <button type="button"
                                            onclick="window.openOutgoingStatusDropdown(event, '{{ route('outgoing-mails.update', $mail) }}', '{{ addslashes($mail->mail_number ?? '-') }}', '{{ $displayStatus }}')"
                                            title="Klik untuk mengubah status surat"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                                            <span>{{ $displayStatus }}</span>
                                            <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                            {{ $displayStatus }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 sm:px-5 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('outgoing-mails.show', $mail) }}"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                        Detail
                                    </a>
                                    @can('update', $mail)
                                        <a href="{{ route('outgoing-mails.edit', $mail) }}"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                            Edit
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-500 italic text-sm">Belum ada data surat keluar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $outgoingMails->links() }}
        </div>
    </div>

    <script>
        function outgoingMailStatusHandler() {
            return {
                showConfirmModal: false,
                confirmMessage: '',
                targetUrl: '',
                targetStatus: '',
                isSubmitting: false,

                // Status dropdown state (teleported to body)
                showStatusDropdown: false,
                statusDropdownPosition: { top: '0px', left: '0px' },
                statusDropdownUrl: '',
                statusDropdownMailNumber: '',
                statusDropdownCurrentStatus: '',

                init() {
                    window.openOutgoingStatusDropdown = (event, url, mailNumber, currentStatus) => {
                        this.openStatusDropdown(event, url, mailNumber, currentStatus);
                    };

                    window.addEventListener('scroll', () => {
                        if (this.showStatusDropdown) this.showStatusDropdown = false;
                    }, true);

                    window.addEventListener('resize', () => {
                        if (this.showStatusDropdown) this.showStatusDropdown = false;
                    });

                    this.$watch('showConfirmModal', (value) => {
                        if (value) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    });
                },

                openStatusDropdown(event, url, mailNumber, currentStatus) {
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
                    this.statusDropdownUrl = url;
                    this.statusDropdownMailNumber = mailNumber;
                    this.statusDropdownCurrentStatus = currentStatus;
                    this.showStatusDropdown = true;
                },

                selectStatusOption(newStatus) {
                    this.showStatusDropdown = false;
                    this.promptStatusChange(
                        this.statusDropdownUrl,
                        this.statusDropdownMailNumber,
                        this.statusDropdownCurrentStatus,
                        newStatus
                    );
                },

                promptStatusChange(url, mailNumber, currentStatus, newStatus) {
                    const statusLabel = newStatus === 'RETURN' ? 'RETURN' : (newStatus === 'PROGRES' ? 'PROGRES' : (newStatus === 'REVISI' ? 'REVISI' : 'RECEIVE'));
                    this.confirmMessage = `Apakah Anda yakin ingin mengubah status surat <strong>${mailNumber}</strong> dari <strong>${currentStatus}</strong> menjadi <strong>${statusLabel}</strong>?`;
                    this.targetUrl = url;
                    this.targetStatus = newStatus;
                    this.showConfirmModal = true;
                },

                confirmStatusUpdate() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;

                    this.$refs.statusForm.action = this.targetUrl;
                    this.$refs.statusInput.value = this.targetStatus;
                    this.$refs.statusForm.submit();
                }
            };
        }
    </script>
@endsection
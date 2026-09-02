@extends('layouts.app')

@section('title', 'Daftar Surat Masuk')

@section('content')
<div x-data="revisionModalHandler()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Masuk</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola pencatatan, disposisi, dan riwayat revisi surat masuk.</p>
        </div>
        @can('create', App\Models\IncomingMail::class)
            <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2.5">
                <!-- Tombol Draft Surat Masuk -->
                <a href="{{ route('incoming-mails.index', ['status_filter' => request('status_filter') === 'draft' ? 'all' : 'draft']) }}"
                    class="px-4 py-2.5 {{ request('status_filter') === 'draft' ? 'bg-slate-900 text-white' : 'bg-white hover:bg-slate-50 text-slate-800 border border-slate-200' }} text-xs font-semibold rounded-xl transition-all shadow-2xs">
                    <span>{{ request('status_filter') === 'draft' ? 'Lihat Semua Surat' : 'Draft Surat Masuk' }}</span>
                    @if(!empty($draftCount) && $draftCount > 0)
                        <span class="ml-1.5 px-2 py-0.5 text-[10px] font-bold rounded-full {{ request('status_filter') === 'draft' ? 'bg-white text-slate-900' : 'bg-slate-100 text-slate-700' }}">{{ $draftCount }}</span>
                    @endif
                </a>

                <!-- Tombol Daftar Revisi (Tepat di samping tombol draft) -->
                <a href="{{ route('incoming-mails.index', ['status_filter' => request('status_filter') === 'revisi' ? 'all' : 'revisi']) }}"
                    class="px-4 py-2.5 {{ request('status_filter') === 'revisi' ? 'bg-slate-900 text-white' : 'bg-white hover:bg-slate-50 text-slate-800 border border-slate-200' }} text-xs font-semibold rounded-xl transition-all shadow-2xs">
                    <span>{{ request('status_filter') === 'revisi' ? 'Lihat Semua Surat' : 'Daftar Revisi' }}</span>
                    @if(!empty($revisiCount) && $revisiCount > 0)
                        <span class="ml-1.5 px-2 py-0.5 text-[10px] font-bold rounded-full {{ request('status_filter') === 'revisi' ? 'bg-white text-slate-900' : 'bg-amber-100 text-amber-900 border border-amber-300' }}">{{ $revisiCount }}</span>
                    @endif
                </a>

                <a href="{{ route('incoming-mails.create') }}"
                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                    Tambah Surat Masuk
                </a>
            </div>
        @endcan
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr
                        class="border-b border-slate-200 bg-slate-100/90 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <th class="p-3.5 text-center">No.</th>
                        <th class="p-3.5">Nomor Surat</th>
                        <th class="p-3.5">Tanggal Masuk</th>
                        <th class="p-3.5">Dari</th>
                        <th class="p-3.5">Kepada</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Perihal</th>
                        <th class="p-3.5">Tanggal Keluar</th>
                        <th class="p-3.5">Disposisi</th>
                        <th class="p-3.5">Nama Penerima</th>
                        <th class="p-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @forelse ($incomingMails as $index => $mail)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $mail->status === 'REVISI' ? 'bg-amber-50/30' : '' }}">
                            <td class="p-3.5 text-center font-bold text-slate-500">
                                {{ ($incomingMails->firstItem() ?? 1) + $index }}
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-bold text-slate-900 font-mono">{{ $mail->mail_number }}</span>
                                    @if ($mail->revision_count > 0 || $mail->status === 'REVISI')
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300 font-mono" title="Dokumen telah direvisi {{ $mail->revision_count ?: 1 }} kali">
                                            R [{{ $mail->revision_count ?: 1 }}]
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-700 font-mono">
                                {{ $mail->received_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="p-3.5 font-medium text-slate-800">
                                {{ $mail->sender }}
                            </td>
                            <td class="p-3.5 text-slate-700">
                                {{ $mail->recipient ?? '-' }}
                            </td>
                            <td class="p-3.5" x-data="{
                                open: false,
                                selectedStatus: '{{ $mail->status }}',
                                dropdownPos: { top: '0px', left: '0px' },
                                toggleDropdown(el) {
                                    if (this.open) {
                                        this.open = false;
                                        return;
                                    }
                                    const rect = el.getBoundingClientRect();
                                    const dropdownHeight = 220;
                                    const spaceBelow = window.innerHeight - rect.bottom;
                                    
                                    let top = rect.bottom + 6;
                                    if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
                                        top = rect.top - dropdownHeight;
                                    }
                                    let left = Math.max(10, Math.min(rect.left, window.innerWidth - 200));
                                    this.dropdownPos = { top: top + 'px', left: left + 'px' };
                                    this.open = true;
                                }
                            }" @scroll.window.passive="open = false">
                                @php
                                    $badgeClasses = match ($mail->status) {
                                        'RETURN', 'RETURNED' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
                                        'APPROVED', 'SIGNED' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
                                        'RECEIVE', 'RECEIVED' => 'bg-sky-50 text-sky-700 border-sky-300 font-bold',
                                        'REVISI', 'REVISION' => 'bg-amber-50 text-amber-900 border-amber-300 font-bold',
                                        'WAITING' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
                                        'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-amber-50 text-amber-700 border-amber-300 font-bold',
                                        'REJECTED', 'SIGN_FAILED' => 'bg-rose-50 text-rose-700 border-rose-300 font-bold',
                                        'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-300 font-medium',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                    $displayStatus = match ($mail->status) {
                                        'RECEIVED' => 'RECEIVE',
                                        'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                        'RETURNED' => 'RETURN',
                                        'REVISION' => 'REVISI',
                                        default => $mail->status,
                                    };
                                    $availableStatuses = [
                                        'RECEIVE' => 'Diterima',
                                        'PROGRES' => 'Dalam Proses',
                                        'RETURN' => 'Selesai / Dikembalikan',
                                        'REVISI' => 'Revisi Dokumen',
                                        'DRAFT' => 'Draft Dokumen',
                                    ];
                                @endphp

                                @can('update', $mail)
                                    <div class="relative inline-block text-left">
                                        <form x-ref="statusForm" action="{{ route('incoming-mails.update', $mail) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" :value="selectedStatus">
                                        </form>

                                        <button type="button" @click="toggleDropdown($el)"
                                            title="Klik untuk memilih dan mengubah status"
                                            class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border transition-all cursor-pointer shadow-2xs hover:ring-2 hover:ring-slate-900/10 {{ $badgeClasses }}">
                                            <span>{{ $displayStatus }}</span>
                                            <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Menu Teleported to Body -->
                                        <template x-teleport="body">
                                            <div x-show="open"
                                                x-cloak
                                                @click.away="open = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="transform opacity-100 scale-100"
                                                x-transition:leave-end="transform opacity-0 scale-95"
                                                :style="{ top: dropdownPos.top, left: dropdownPos.left }"
                                                class="fixed w-48 rounded-xl bg-white shadow-2xl border border-slate-200 py-1 z-[99999] focus:outline-hidden">
                                                <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                                    Pilih Status
                                                </div>
                                                @foreach ($availableStatuses as $statusCode => $statusDesc)
                                                    <button type="button"
                                                        @click="if('{{ $statusCode }}' !== '{{ $mail->status }}') { if(confirm('Ubah status surat masuk {{ $mail->mail_number }} menjadi {{ $statusCode }}?')) { selectedStatus = '{{ $statusCode }}'; $nextTick(() => $refs.statusForm.submit()); } } open = false;"
                                                        class="w-full text-left px-3 py-1.5 text-xs hover:bg-slate-50 flex items-center justify-between transition-colors {{ $mail->status === $statusCode ? 'bg-slate-50 font-bold' : '' }}">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="inline-block w-2 h-2 rounded-full {{ str_starts_with($statusCode, 'RETURN') ? 'bg-emerald-500' : (str_starts_with($statusCode, 'RECEIVE') ? 'bg-sky-500' : ($statusCode === 'REVISI' ? 'bg-amber-600' : (in_array($statusCode, ['PROGRES', 'WAITING']) ? 'bg-amber-500' : 'bg-slate-400'))) }}"></span>
                                                            <span class="text-slate-800">{{ $statusCode }}</span>
                                                        </div>
                                                        @if($mail->status === $statusCode)
                                                            <span class="text-[10px] text-slate-400 font-normal">Aktif</span>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        </template>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClasses }}">
                                        {{ $displayStatus }}
                                    </span>
                                @endcan
                            </td>
                            <td class="p-3.5 text-slate-800 max-w-xs truncate" title="{{ $mail->subject }}">
                                {{ $mail->subject }}
                            </td>
                            <td class="p-3.5 text-slate-600 font-mono">
                                {{ $mail->outgoing_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="p-3.5 text-slate-600 max-w-xs truncate" title="{{ $mail->disposition_note }}">
                                {{ $mail->disposition_note ? Str::limit($mail->disposition_note, 25) : '-' }}
                            </td>
                            <td class="p-3.5 text-slate-700 font-medium">
                                {{ $mail->recipient_name ?? '-' }}
                            </td>
                            <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('incoming-mails.show', $mail) }}"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-[11px]">
                                    Detail
                                </a>
                                @can('update', $mail)
                                    @if ($mail->status === 'REVISI')
                                        <!-- Tombol Revisi Langsung Buka Modal Revisi (Upload & Tanda Tangan) -->
                                        <button type="button"
                                            @click="openRevisiModal('{{ $mail->id }}', '{{ $mail->mail_number }}', '{{ addslashes($mail->sender) }}', '{{ addslashes($mail->subject) }}', {{ $mail->revision_count ?: 0 }})"
                                            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-[11px] transition-all shadow-xs cursor-pointer">
                                            Revisi
                                        </button>
                                    @elseif ($mail->status === 'DRAFT')
                                        <a href="{{ route('incoming-mails.edit', $mail) }}"
                                            class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold text-[11px]">
                                            Finalisasi
                                        </a>
                                    @else
                                        <a href="{{ route('incoming-mails.edit', $mail) }}"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-[11px]">
                                            Edit
                                        </a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center text-slate-500 italic text-xs">
                                @if(request('status_filter') === 'revisi')
                                    Tidak ada surat masuk yang sedang dalam status revisi.
                                @elseif(request('status_filter') === 'draft')
                                    Tidak ada draft surat masuk yang belum difinalisasi.
                                @else
                                    Belum ada data surat masuk.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $incomingMails->links() }}
    </div>

    <!-- Modal Form Dokumen & Tanda Tangan Revisi (Teleported to Body for seamless backdrop blur) -->
    <template x-teleport="body">
        <div x-show="showModal"
            x-cloak
            class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 sm:p-6"
            style="position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
            
            <!-- Backdrop Overlay with Full Blur -->
            <div x-show="showModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeModal()"
                class="fixed inset-0 bg-slate-950/75 backdrop-blur-md transition-all"></div>

            <!-- Modal Container -->
            <div x-show="showModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                @click.away="closeModal()"
                class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5 z-10">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-slate-900">Form Revisi Dokumen</h3>
                            <span class="px-2 py-0.5 bg-amber-50 text-amber-900 border border-amber-300 text-xs font-bold font-mono rounded-md" x-text="'R [' + (activeMail.revCount + 1) + ']'"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 font-mono">No. Surat: <strong class="text-slate-800" x-text="activeMail.number"></strong></p>
                    </div>
                    <button type="button" @click="closeModal()" class="px-2.5 py-1 text-slate-500 hover:text-slate-800 text-xs font-bold rounded-lg hover:bg-slate-100 transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>

                <!-- Info Box -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-1">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Perihal Dokumen</span>
                    <p class="text-xs font-semibold text-slate-800 line-clamp-2" x-text="activeMail.subject"></p>
                </div>

                <!-- Form Upload & Signature -->
                <form :action="'/incoming-mails/' + activeMail.id + '/revisi'" method="POST" enctype="multipart/form-data" @submit="submitRevisionForm($event)" class="space-y-4">
                    @csrf
                    <input type="hidden" name="signature_base64" :value="signatureBase64">

                    <!-- Upload Berkas Revisi Baru -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Upload Berkas Dokumen Revisi (PDF / Gambar)
                        </label>
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition-all shadow-2xs">
                        <p class="text-[11px] text-slate-400">Pilih berkas baru yang sudah diperbaiki untuk menggantikan berkas lama.</p>
                    </div>

                    <!-- Tanda Tangan Canvas -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Tanda Tangan Pengantar / Penerima Revisi
                            </label>
                            <span class="text-[11px] font-semibold"
                                :class="hasSignature ? 'text-emerald-700' : 'text-slate-400'"
                                x-text="hasSignature ? 'Tanda tangan terisi' : 'Goreskan di bawah'"></span>
                        </div>

                        <div class="border border-slate-300 rounded-2xl overflow-hidden bg-white shadow-2xs relative">
                            <canvas x-ref="revisiCanvas"
                                width="500"
                                height="150"
                                @mousedown="onStart($event)"
                                @mousemove="onMove($event)"
                                @mouseup="onEnd($event)"
                                @mouseleave="onEnd($event)"
                                @touchstart.prevent="onStart($event)"
                                @touchmove.prevent="onMove($event)"
                                @touchend.prevent="onEnd($event)"
                                class="w-full h-36 touch-none cursor-crosshair block bg-white"></canvas>

                            <div class="absolute bottom-2 right-2 flex items-center space-x-2">
                                <button type="button" @click="clearCanvas()" class="px-2.5 py-1 bg-white/90 hover:bg-white text-slate-600 text-[11px] font-semibold rounded-lg border border-slate-200 shadow-2xs transition-all cursor-pointer">
                                    Hapus Goresan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Revisi -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Catatan Revisi (Opsional)
                        </label>
                        <textarea name="revision_notes" rows="2" placeholder="Catatan perubahan yang telah dilakukan pada revisi ini..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-all shadow-2xs"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2.5">
                        <button type="button" @click="closeModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer flex items-center space-x-1.5">
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan & Selesaikan Revisi'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function revisionModalHandler() {
        return {
            showModal: false,
            activeMail: { id: '', number: '', sender: '', subject: '', revCount: 0 },
            signatureBase64: '',
            hasSignature: false,
            isDrawing: false,
            isSubmitting: false,
            canvasCtx: null,

            openRevisiModal(id, number, sender, subject, revCount) {
                this.activeMail = { id, number, sender, subject, revCount };
                this.showModal = true;
                this.signatureBase64 = '';
                this.hasSignature = false;
                this.isSubmitting = false;

                this.$nextTick(() => {
                    this.initCanvas();
                });
            },

            closeModal() {
                this.showModal = false;
            },

            initCanvas() {
                const canvas = this.$refs.revisiCanvas;
                if (!canvas) return;

                const rect = canvas.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;

                this.canvasCtx = canvas.getContext('2d');
                this.canvasCtx.scale(dpr, dpr);
                this.canvasCtx.strokeStyle = '#0f172a';
                this.canvasCtx.lineWidth = 2.5;
                this.canvasCtx.lineCap = 'round';
                this.canvasCtx.lineJoin = 'round';

                this.clearCanvas();
            },

            getCoordinates(e) {
                const canvas = this.$refs.revisiCanvas;
                const rect = canvas.getBoundingClientRect();
                if (e.touches && e.touches.length > 0) {
                    return {
                        x: e.touches[0].clientX - rect.left,
                        y: e.touches[0].clientY - rect.top
                    };
                }
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                };
            },

            onStart(e) {
                this.isDrawing = true;
                const pos = this.getCoordinates(e);
                this.canvasCtx.beginPath();
                this.canvasCtx.moveTo(pos.x, pos.y);
            },

            onMove(e) {
                if (!this.isDrawing) return;
                const pos = this.getCoordinates(e);
                this.canvasCtx.lineTo(pos.x, pos.y);
                this.canvasCtx.stroke();
                this.hasSignature = true;
            },

            onEnd(e) {
                if (!this.isDrawing) return;
                this.isDrawing = false;
                this.canvasCtx.closePath();
                this.captureSignature();
            },

            clearCanvas() {
                const canvas = this.$refs.revisiCanvas;
                if (!canvas || !this.canvasCtx) return;
                const rect = canvas.getBoundingClientRect();
                this.canvasCtx.clearRect(0, 0, rect.width, rect.height);
                this.signatureBase64 = '';
                this.hasSignature = false;
            },

            captureSignature() {
                const canvas = this.$refs.revisiCanvas;
                if (!canvas) return;
                this.signatureBase64 = canvas.toDataURL('image/png');
            },

            submitRevisionForm(e) {
                if (this.hasSignature) {
                    this.captureSignature();
                }
                this.isSubmitting = true;
            }
        };
    }
</script>
@endsection
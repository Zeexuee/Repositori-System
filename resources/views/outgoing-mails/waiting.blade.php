@extends('layouts.app')

@section('title', 'Ruang Dokumen Menunggu (Waiting)')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Keluar (Menunggu)</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                    {{ $totalWaiting ?? $waitingMails->total() }} Menunggu
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Daftar surat keluar yang sedang menunggu tanda tangan dari seluruh unit penerima / disposisi.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('outgoing-mails.index') }}"
                class="inline-flex items-center space-x-2 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 shadow-2xs transition-all">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Semua Surat Keluar</span>
            </a>

            @can('create', App\Models\OutgoingMail::class)
                <a href="{{ route('outgoing-mails.create') }}"
                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all shadow-xs">
                    Tambah Surat Keluar
                </a>
            @endcan
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mt-6 flex items-center justify-between">
        <form method="GET" action="{{ route('outgoing-mails.waiting') }}" class="flex items-center space-x-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor surat, subjek, atau penerima..."
                    class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-xs text-slate-900 transition-all shadow-2xs">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('outgoing-mails.waiting') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition-all" title="Reset pencarian">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Table of Waiting Documents & Modal Root Container -->
    <div class="mt-4 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden"
        x-data="signatureModalManager()">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100/90 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <th class="p-3.5 text-center">No.</th>
                        <th class="p-3.5">Nomor Surat</th>
                        <th class="p-3.5">Subjek / Perihal</th>
                        <th class="p-3.5">Unit Disposisi & Status Tanda Tangan</th>
                        <th class="p-3.5">Pembuat</th>
                        <th class="p-3.5">Berkas</th>
                        <th class="p-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @forelse ($waitingMails as $index => $mail)
                        @php
                            $dispositions = $mail->dispositions_data ?? [];
                            if (empty($dispositions) && !empty($mail->recipient)) {
                                $names = array_filter(array_map('trim', explode(',', (string) $mail->recipient)));
                                foreach ($names as $name) {
                                    $dispositions[] = ['name' => $name, 'status' => 'WAITING', 'signature_path' => null];
                                }
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5 text-center font-bold text-slate-500">
                                {{ ($waitingMails->firstItem() ?? 1) + $index }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900 font-mono">
                                {{ $mail->mail_number ?? '-' }}
                            </td>
                            <td class="p-3.5 text-slate-800 font-medium max-w-xs truncate" title="{{ $mail->subject }}">
                                {{ $mail->subject }}
                            </td>
                            <!-- Unit Disposisi & Status TTD per Unit -->
                            <td class="p-3.5 max-w-md">
                                <div class="space-y-2">
                                    @forelse ($dispositions as $dIndex => $disp)
                                        @php
                                            $isSigned = ($disp['status'] ?? 'WAITING') === 'SIGNED' || !empty($disp['signature_path']);
                                        @endphp
                                        <div class="flex items-center justify-between p-2.5 rounded-xl border {{ $isSigned ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-slate-50/80' }} gap-2">
                                            <div class="flex items-center space-x-2 min-w-0 pr-2">
                                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $isSigned ? 'bg-emerald-700 text-white' : 'bg-slate-900 text-white' }}"
                                                    x-text="{{ $dIndex + 1 }}"></span>
                                                <span class="font-bold text-xs text-slate-800 truncate">{{ $disp['name'] ?? 'Unit Disposisi' }}</span>
                                            </div>

                                            <div class="flex items-center space-x-2 flex-shrink-0">
                                                @if ($isSigned)
                                                    @if(!empty($disp['signature_path']))
                                                        <img src="{{ route('document.download', ['path' => $disp['signature_path'], 'inline' => 1]) }}" alt="TTD" class="h-6 max-w-[70px] object-contain bg-white border border-emerald-200 rounded px-1 shadow-2xs">
                                                    @endif
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                        Ditandatangani
                                                    </span>
                                                    <button type="button"
                                                        @click="openModal('{{ route('outgoing-mails.sign-disposition', $mail) }}', {{ $dIndex }}, '{{ addslashes($disp['name'] ?? 'Unit Disposisi') }}', '{{ addslashes($mail->mail_number ?? '') }}', true)"
                                                        class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg font-semibold text-[10px] shadow-2xs transition-all cursor-pointer">
                                                        Edit TTD
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        @click="openModal('{{ route('outgoing-mails.sign-disposition', $mail) }}', {{ $dIndex }}, '{{ addslashes($disp['name'] ?? 'Unit Disposisi') }}', '{{ addslashes($mail->mail_number ?? '') }}', false)"
                                                        class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold text-[11px] transition-all shadow-2xs cursor-pointer">
                                                        TTD Sekarang
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-slate-400 italic text-[11px]">Belum ada data disposisi</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-600 text-xs font-medium">
                                {{ $mail->creator?->name ?? 'System' }}
                                <span class="block text-[10px] text-slate-400">{{ $mail->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="p-3.5">
                                @if ($mail->file_path)
                                    <div class="flex items-center space-x-1.5">
                                        <a href="{{ route('document.download', ['path' => $mail->file_path, 'inline' => 1]) }}" target="_blank"
                                            class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-[11px] font-semibold rounded-lg shadow-2xs transition-all" title="Pratinjau Berkas">
                                            Lihat PDF
                                        </a>
                                        <a href="{{ route('document.download', ['path' => $mail->file_path]) }}"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold rounded-lg transition-colors" title="Unduh Berkas">
                                            Unduh
                                        </a>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px] italic">Tanpa berkas</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('outgoing-mails.edit', $mail) }}"
                                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-semibold text-[11px] transition-all">
                                    Edit
                                </a>

                                <a href="{{ route('outgoing-mails.show', $mail) }}"
                                    class="px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-semibold text-[11px] transition-all shadow-xs">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500 italic text-xs">
                                @if (request('search'))
                                    Tidak ditemukan dokumen menunggu yang sesuai dengan kata kunci "{{ request('search') }}".
                                @else
                                    Saat ini tidak ada dokumen yang sedang menunggu tanda tangan penerima.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Signature Canvas Modal (Teleported to Body for perfect full-screen backdrop-blur and z-index isolation) -->
        <template x-teleport="body">
            <div x-show="showModal"
                x-cloak
                class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 sm:p-6"
                style="position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
                
                <!-- Backdrop Overlay with Deep Backdrop Blur -->
                <div x-show="showModal"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="closeModal()"
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-md transition-all"></div>

                <!-- Modal Window Container -->
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
                            <h3 class="text-base font-bold text-slate-900" x-text="isEditing ? 'Edit Tanda Tangan Penerima' : 'Form Gambar Tanda Tangan Penerima'"></h3>
                            <p class="text-xs text-slate-500 mt-1 font-mono">Surat: <strong class="text-slate-800" x-text="mailNumber || '(Draf Tanpa Nomor)'"></strong></p>
                        </div>
                        <button type="button" @click="closeModal()" class="px-2.5 py-1 text-slate-500 hover:text-slate-800 text-xs font-bold rounded-lg hover:bg-slate-100 transition-all cursor-pointer">
                            Tutup
                        </button>
                    </div>

                    <!-- Unit Information Card -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Unit Disposisi Penerima</span>
                        <p class="text-xs font-bold text-slate-900 mt-0.5" x-text="unitName"></p>
                    </div>

                    <!-- Signature Form -->
                    <form :action="actionUrl" method="POST" @submit="submitSignature($event)">
                        @csrf
                        <input type="hidden" name="disposition_index" :value="dispositionIndex">
                        <input type="hidden" name="signature_base64" :value="signatureBase64">

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Goreskan Tanda Tangan Baru (Canvas) <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] font-semibold"
                                    :class="hasSignature ? 'text-emerald-700' : 'text-slate-400'"
                                    x-text="hasSignature ? 'Tanda tangan terisi' : 'Goreskan di bawah'"></span>
                            </div>

                            <div class="border border-slate-300 rounded-2xl overflow-hidden bg-white shadow-2xs relative">
                                <canvas x-ref="modalCanvas"
                                    width="500"
                                    height="160"
                                    @mousedown="onStart($event)"
                                    @mousemove="onMove($event)"
                                    @mouseup="onEnd($event)"
                                    @mouseleave="onEnd($event)"
                                    @touchstart.prevent="onStart($event)"
                                    @touchmove.prevent="onMove($event)"
                                    @touchend.prevent="onEnd($event)"
                                    class="w-full h-40 touch-none cursor-crosshair block bg-white"></canvas>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-[11px] text-slate-500">Gunakan mouse atau layar sentuh.</span>
                                <button type="button" @click="clearCanvas()"
                                    class="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-all cursor-pointer">
                                    Bersihkan Pad
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-2.5 pt-5 border-t border-slate-100 mt-5">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="!hasSignature || loading"
                                class="px-5 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-50 rounded-xl transition-all shadow-xs cursor-pointer">
                                <span x-show="!loading" x-text="isEditing ? 'Simpan Perubahan TTD' : 'Simpan Tanda Tangan'"></span>
                                <span x-show="loading">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-6">
        {{ $waitingMails->links() }}
    </div>

    <script>
        function signatureModalManager() {
            return {
                showModal: false,
                isEditing: false,
                loading: false,
                actionUrl: '',
                dispositionIndex: 0,
                unitName: '',
                mailNumber: '',
                signatureBase64: '',
                hasSignature: false,
                isDrawing: false,
                ctx: null,

                openModal(url, index, unit, mailNum, editing = false) {
                    this.actionUrl = url;
                    this.dispositionIndex = index;
                    this.unitName = unit;
                    this.mailNumber = mailNum;
                    this.isEditing = editing;
                    this.signatureBase64 = '';
                    this.hasSignature = false;
                    this.showModal = true;

                    setTimeout(() => {
                        this.setupCanvas();
                    }, 50);
                },

                closeModal() {
                    this.showModal = false;
                    this.clearCanvas();
                },

                setupCanvas() {
                    const canvas = this.$refs.modalCanvas;
                    if (!canvas) return;
                    this.ctx = canvas.getContext('2d');
                    this.ctx.lineWidth = 2.5;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                    this.ctx.strokeStyle = '#0f172a';
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                },

                getPos(e) {
                    const canvas = this.$refs.modalCanvas;
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
                        if (this.$refs.modalCanvas) {
                            this.signatureBase64 = this.$refs.modalCanvas.toDataURL('image/png');
                        }
                    }
                },

                clearCanvas() {
                    const canvas = this.$refs.modalCanvas;
                    if (canvas && this.ctx) {
                        this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }
                    this.hasSignature = false;
                    this.signatureBase64 = '';
                },

                submitSignature(e) {
                    if (!this.hasSignature || !this.signatureBase64) {
                        e.preventDefault();
                        alert('Silakan goreskan tanda tangan terlebih dahulu pada canvas.');
                        return;
                    }
                    this.loading = true;
                }
            };
        }
    </script>
@endsection

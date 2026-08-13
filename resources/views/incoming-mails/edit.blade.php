@extends('layouts.app')

@section('title', 'Edit Surat Masuk')

@section('content')
    <div class="pb-4 border-b border-slate-200/80 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Surat Masuk</h1>
        </div>
        <div class="inline-flex items-center space-x-2 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
            <span>No. Surat: <strong class="text-slate-900 font-mono">{{ $incomingMail->mail_number }}</strong></span>
        </div>
    </div>

    <form action="{{ route('incoming-mails.update', $incomingMail) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        @method('PUT')

        <!-- Section 1: Data Utama -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                <span>Data Utama Surat</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Nomer surat -->
                <div>
                    <label for="mail_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Surat <span class="text-rose-500">*</span></label>
                    <input type="text" name="mail_number" id="mail_number" value="{{ old('mail_number', $incomingMail->mail_number) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('mail_number')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Masuk -->
                <div>
                    <label for="received_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Masuk <span class="text-rose-500">*</span></label>
                    <input type="date" name="received_date" id="received_date" value="{{ old('received_date', $incomingMail->received_date?->format('Y-m-d')) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('received_date')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Keluar -->
                <div>
                    <label for="outgoing_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Keluar</label>
                    <input type="date" name="outgoing_date" id="outgoing_date" value="{{ old('outgoing_date', $incomingMail->outgoing_date?->format('Y-m-d')) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('outgoing_date')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Dari -->
                <div>
                    <label for="sender" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dari <span class="text-rose-500">*</span></label>
                    <input type="text" name="sender" id="sender" value="{{ old('sender', $incomingMail->sender) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('sender')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kepada -->
                <div>
                    <label for="recipient" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kepada</label>
                    <input type="text" name="recipient" id="recipient" value="{{ old('recipient', $incomingMail->recipient) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('recipient')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="status" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        <option value="RECEIVED" {{ old('status', $incomingMail->status) == 'RECEIVED' ? 'selected' : '' }}>RECEIVED</option>
                        <option value="REGISTERED" {{ old('status', $incomingMail->status) == 'REGISTERED' ? 'selected' : '' }}>REGISTERED</option>
                        <option value="PENDING_DISPOSITION" {{ old('status', $incomingMail->status) == 'PENDING_DISPOSITION' ? 'selected' : '' }}>PENDING_DISPOSITION</option>
                        <option value="IN_PROGRESS" {{ old('status', $incomingMail->status) == 'IN_PROGRESS' ? 'selected' : '' }}>IN_PROGRESS</option>
                        <option value="COMPLETED" {{ old('status', $incomingMail->status) == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                        <option value="OVERDUE" {{ old('status', $incomingMail->status) == 'OVERDUE' ? 'selected' : '' }}>OVERDUE</option>
                    </select>
                    @error('status')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Perihal -->
            <div>
                <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Perihal <span class="text-rose-500">*</span></label>
                <input type="text" name="subject" id="subject" value="{{ old('subject', $incomingMail->subject) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('subject')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Section 2: Disposisi, Keterangan & Penerima -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                <span>Disposisi & Penerima</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Disposisi -->
                <div>
                    <label for="disposition_note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Disposisi</label>
                    <textarea name="disposition_note" id="disposition_note" rows="3" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">{{ old('disposition_note', $incomingMail->disposition_note) }}</textarea>
                    @error('disposition_note')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">{{ old('notes', $incomingMail->notes) }}</textarea>
                    @error('notes')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Nama Penerima -->
            <div>
                <label for="recipient_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Penerima</label>
                <input type="text" name="recipient_name" id="recipient_name" value="{{ old('recipient_name', $incomingMail->recipient_name) }}" class="w-full md:w-1/2 px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                @error('recipient_name')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Section 3: Lampiran Foto Dokumen & Tanda Terima -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                <span>Foto Dokumen & Tanda Terima</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Foto Dokumen -->
                <div x-data="{ hasFile: false, fileName: '' }">
                    <label for="document_photo" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Foto Dokumen
                    </label>

                    @if ($incomingMail->document_photo_path)
                        <div class="mb-3 p-3 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-700">File Foto Ada</span>
                            <a href="{{ route('document.download', ['path' => $incomingMail->document_photo_path, 'inline' => 1]) }}" target="_blank" class="text-xs text-slate-900 hover:underline font-semibold">
                                Pratinjau
                            </a>
                        </div>
                    @endif

                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl hover:border-slate-300 transition-all space-y-3">
                        <input type="file" 
                               name="document_photo" 
                               id="document_photo" 
                               x-ref="photoInput"
                               accept="image/*,application/pdf" 
                               capture="environment" 
                               @change="hasFile = $event.target.files.length > 0; fileName = hasFile ? $event.target.files[0].name : ''"
                               class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">

                        <div x-show="hasFile" x-cloak class="flex items-center justify-between p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg">
                            <span class="text-xs font-medium text-emerald-800 truncate max-w-[220px]" x-text="'File baru: ' + fileName"></span>
                            <button type="button" 
                                    @click="$refs.photoInput.value = ''; hasFile = false; fileName = ''"
                                    class="px-2.5 py-1 text-xs font-bold text-rose-700 bg-white border border-rose-200 rounded-md hover:bg-rose-50 shadow-2xs transition-all flex-shrink-0">
                                Batal Upload
                            </button>
                        </div>
                    </div>
                    @error('document_photo')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanda Terima Signature Canvas Pad ONLY (Upload Removed) -->
                <div x-data="signaturePad()" class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanda Terima
                    </label>

                    @if ($incomingMail->receipt_signature_path)
                        <div class="mb-3 p-3 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-700">File Tanda Tangan Ada</span>
                            <a href="{{ route('document.download', ['path' => $incomingMail->receipt_signature_path, 'inline' => 1]) }}" target="_blank" class="text-xs text-slate-900 hover:underline font-semibold">
                                Pratinjau
                            </a>
                        </div>
                    @endif
                    
                    <div class="bg-slate-50 p-3 border border-slate-200 rounded-xl space-y-3">
                        <div class="relative bg-white rounded-xl border border-slate-300 overflow-hidden shadow-2xs">
                            <canvas x-ref="canvas" 
                                    width="600" 
                                    height="200" 
                                    @mousedown="onStart($event)"
                                    @mousemove="onMove($event)"
                                    @mouseup="onEnd($event)"
                                    @mouseleave="onEnd($event)"
                                    @touchstart.prevent="onStart($event)"
                                    @touchmove.prevent="onMove($event)"
                                    @touchend.prevent="onEnd($event)"
                                    class="w-full h-40 touch-none cursor-crosshair block bg-white"></canvas>
                        </div>
                        
                        <input type="hidden" name="receipt_signature" :value="signatureBase64">

                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-emerald-600" x-text="hasSignature ? '✓ Tanda tangan baru digoreskan' : ''"></span>
                            <button type="button" @click="clearCanvas()" class="px-3 py-1 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-all">
                                Bersihkan Pad
                            </button>
                        </div>
                    </div>
                    @error('receipt_signature')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <a href="{{ route('incoming-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                Batal
            </a>
            <button type="submit" :disabled="loading" class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs">
                <span x-show="!loading">Simpan Perubahan</span>
                <span x-show="loading" class="flex items-center space-x-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memproses...</span>
                </span>
            </button>
        </div>
    </form>

    <script>
        function signaturePad() {
            return {
                isDrawing: false,
                hasSignature: false,
                signatureBase64: '',
                ctx: null,

                init() {
                    this.$nextTick(() => {
                        this.setupCanvas();
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

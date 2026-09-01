@extends('layouts.app')

@section('title', 'Form Surat Keluar')

@section('content')
    <div class="pb-4 border-b border-slate-200/80">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Form Surat Keluar</h1>
        <p class="text-xs text-slate-500 mt-1">Catat surat keluar dan tanda tangani langsung per unit disposisi atau simpan sebagai Menunggu Penerima.</p>
    </div>

    <form action="{{ route('outgoing-mails.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6"
        x-data="outgoingMailForm({
            initialDispositions: {{ json_encode(old('dispositions', [['name' => old('recipient', ''), 'signature_base64' => '']])) }}
        })"
        @submit="loading = true">
        @csrf

        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nomor Surat (Opsional) -->
                <div>
                    <label for="mail_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Surat (Opsional)</label>
                    <input type="text" name="mail_number" id="mail_number" value="{{ old('mail_number') }}" placeholder="Otomatis dibuat jika dikosongkan" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                    @error('mail_number')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subjek / Perihal -->
                <div>
                    <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subjek / Perihal <span class="text-rose-500">*</span></label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required placeholder="Contoh: Undangan Koordinasi Rencana Strategis 2026" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('subject')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Upload Berkas PDF / Gambar -->
            <div>
                <label for="file" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Upload Berkas Dokumen (PDF / Gambar)</label>
                <input type="file" name="file" id="file" accept="application/pdf,image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                @error('file')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Section Disposisi & Form Tanda Tangan Langsung Per Disposisi -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-1">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Unit Disposisi & Tanda Tangan Penerima
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Isi unit penerima dan goreskan tanda tangan langsung. Jika tanda tangan belum diisi, tombol akan otomatis menjadi <strong>Menunggu Penerima</strong>.</p>
                </div>

                <button type="button" @click="addDisposition()"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-all self-start sm:self-auto cursor-pointer">
                    Tambah Disposisi
                </button>
            </div>

            <!-- List of Dispositions -->
            <div class="space-y-4">
                <template x-for="(disp, index) in dispositions" :key="disp.id">
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-4"
                        x-data="dispositionItem(disp, index)">
                        
                        <!-- Hidden form inputs -->
                        <input type="hidden" :name="'dispositions[' + index + '][signature_base64]'" :value="disp.signature_base64">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-lg bg-slate-900 text-white text-xs font-bold flex items-center justify-center font-mono" x-text="index + 1"></span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="'Unit Disposisi #' + (index + 1)"></h3>
                            </div>

                            <button type="button" x-show="dispositions.length > 1" @click="removeDisposition(index)"
                                class="text-xs text-rose-600 hover:text-rose-800 font-semibold px-2.5 py-1 rounded-lg hover:bg-rose-50 transition-all cursor-pointer">
                                Hapus Disposisi
                            </button>
                        </div>

                        <!-- Input Nama Unit Disposisi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Instansi / Unit Disposisi <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" :name="'dispositions[' + index + '][name]'" x-model="disp.name" required
                                placeholder="Contoh: Divisi Keuangan / Kepala Biro Umum / Bapak Direktur"
                                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        </div>

                        <!-- Form Gambar Tanda Tangan (Canvas Langsung Ditampilkan) -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Tanda Tangan Penerima (Canvas)
                                </label>
                                <span class="text-xs font-bold"
                                    :class="disp.signature_base64 ? 'text-emerald-700' : 'text-slate-400'"
                                    x-text="disp.signature_base64 ? 'Tanda tangan terisi' : 'Kosongkan jika belum tanda tangan'"></span>
                            </div>

                            <div class="border border-slate-300 rounded-2xl overflow-hidden bg-white shadow-2xs relative">
                                <canvas x-ref="canvas"
                                    width="500"
                                    height="140"
                                    @mousedown="onStart($event)"
                                    @mousemove="onMove($event)"
                                    @mouseup="onEnd($event)"
                                    @mouseleave="onEnd($event)"
                                    @touchstart.prevent="onStart($event)"
                                    @touchmove.prevent="onMove($event)"
                                    @touchend.prevent="onEnd($event)"
                                    class="w-full h-32 touch-none cursor-crosshair block bg-white"></canvas>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-[11px] text-slate-500">Goreskan tanda tangan langsung dengan mouse atau layar sentuh.</span>
                                <button type="button" @click="clearCanvas()"
                                    class="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-all cursor-pointer">
                                    Bersihkan Pad
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            @error('dispositions')
                <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Bar: Dinamis berubah antara "Simpan Surat Keluar" dan "Menunggu Penerima" -->
        <div class="flex items-center justify-between pt-5 border-t border-slate-200/80">
            <a href="{{ route('outgoing-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                Batal
            </a>

            <!-- Tombol jika SEMUA tanda tangan SUDAH terisi -->
            <template x-if="allSigned()">
                <button type="submit" :disabled="loading" class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 transition-all shadow-xs cursor-pointer">
                    <span x-show="!loading">Simpan Surat Keluar</span>
                    <span x-show="loading">Memproses...</span>
                </button>
            </template>

            <!-- Tombol jika ADA tanda tangan yang BELUM terisi (otomatis menjadi "Menunggu Penerima") -->
            <template x-if="!allSigned()">
                <button type="submit" :disabled="loading" class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl disabled:opacity-50 transition-all shadow-xs cursor-pointer">
                    <span x-show="!loading">Menunggu Penerima</span>
                    <span x-show="loading">Menyimpan ke Waiting...</span>
                </button>
            </template>
        </div>
    </form>

    <script>
        function outgoingMailForm(config) {
            return {
                loading: false,
                dispositions: (config.initialDispositions && config.initialDispositions.length > 0) 
                    ? config.initialDispositions.map((d, i) => ({
                        id: Date.now() + i,
                        name: d.name || '',
                        signature_base64: d.signature_base64 || ''
                    }))
                    : [{ id: Date.now(), name: '', signature_base64: '' }],

                addDisposition() {
                    this.dispositions.push({
                        id: Date.now() + Math.random(),
                        name: '',
                        signature_base64: ''
                    });
                },

                removeDisposition(index) {
                    if (this.dispositions.length > 1) {
                        this.dispositions.splice(index, 1);
                    }
                },

                allSigned() {
                    if (this.dispositions.length === 0) return false;
                    return this.dispositions.every(d => d.signature_base64 && d.signature_base64.trim() !== '');
                }
            };
        }

        function dispositionItem(disp, index) {
            return {
                disp: disp,
                isDrawing: false,
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
                    this.ctx.lineWidth = 2.5;
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
                },

                onEnd(e) {
                    if (this.isDrawing) {
                        this.isDrawing = false;
                        if (this.$refs.canvas) {
                            this.disp.signature_base64 = this.$refs.canvas.toDataURL('image/png');
                        }
                    }
                },

                clearCanvas() {
                    const canvas = this.$refs.canvas;
                    if (canvas && this.ctx) {
                        this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }
                    this.disp.signature_base64 = '';
                }
            };
        }
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Broadcast Email Notifikasi')

@section('content')
    <!-- Quill Rich Text Editor CDN Assets -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <style>
        .ql-toolbar.ql-snow {
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            border-color: #e2e8f0 !important;
            background-color: #f8fafc;
            padding: 8px 12px;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            border-color: #e2e8f0 !important;
            font-family: inherit;
            font-size: 0.875rem;
            min-height: 200px;
            background-color: rgba(248, 250, 252, 0.5);
        }
        .ql-editor {
            min-height: 200px;
        }
        .ql-editor.ql-blank::before {
            color: #94a3b8;
            font-style: normal;
            font-size: 0.875rem;
        }
    </style>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Broadcast Email Notifikasi</h1>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-2">
            <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Total Pengguna: <strong>{{ $totalUsers }}</strong></span>
            </span>
        </div>
    </div>

    <!-- 1. Form Kirim Broadcast Email (Full Width untuk tempat compose lebih lega) -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-7 shadow-2xs space-y-5"
         x-data="{ 
             detailModalOpen: false,
             selectedBroadcast: { subject: '', body: '', date: '', sender: '', target: '', count: 0, attachments: [] },
             formatSize(bytes) {
                 if (!bytes || bytes === 0) return '0 B';
                 const k = 1024;
                 const sizes = ['B', 'KB', 'MB', 'GB'];
                 const i = Math.floor(Math.log(bytes) / Math.log(k));
                 return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
             }
         }">
        <form action="{{ route('broadcast-emails.send') }}" 
              method="POST" 
              enctype="multipart/form-data"
              class="space-y-4" 
              x-data="{ 
                  recipients: '{{ old('recipients') }}', 
                  bodyHtml: `{!! old('body') !!}`,
                  targetAudienceLabel: '{{ old('target_audience', 'Custom Recipient') }}', 
                  loading: false,
                  allEmails: '{{ $allUserEmails }}',
                  stafEmails: '{{ $stafUserEmails }}',
                  direksiEmails: '{{ $direksiUserEmails }}',
                  attachedFiles: [],
                  setPreset(type, emails) {
                      this.recipients = emails;
                      this.targetAudienceLabel = type;
                  },
                  handleFileSelect(event) {
                      const inputFiles = Array.from(event.target.files);
                      this.attachedFiles = inputFiles.map(f => ({
                          file: f,
                          name: f.name,
                          sizeFormatted: this.formatSize(f.size)
                      }));
                  },
                  removeFile(index) {
                      this.attachedFiles.splice(index, 1);
                      const dt = new DataTransfer();
                      this.attachedFiles.forEach(item => dt.items.add(item.file));
                      document.getElementById('attachment-input').files = dt.files;
                  },
                  initQuill() {
                      const quill = new Quill('#editor-container', {
                          theme: 'snow',
                          placeholder: 'Tuliskan isi pesan pengumuman atau detail undangan di sini... (Gunakan ikon gambar di toolbar untuk menyisipkan foto)',
                          modules: {
                              toolbar: [
                                  [{ 'header': [1, 2, 3, false] }],
                                  ['bold', 'italic', 'underline', 'strike'],
                                  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                  [{ 'align': [] }],
                                  [{ 'color': [] }, { 'background': [] }],
                                  ['link', 'image', 'clean']
                              ]
                          }
                      });
                      
                      if (this.bodyHtml) {
                          quill.root.innerHTML = this.bodyHtml;
                      }

                      quill.on('text-change', () => {
                          this.bodyHtml = quill.root.innerHTML;
                      });
                  }
              }" 
              x-init="initQuill()"
              @submit="loading = true">
            @csrf

            <input type="hidden" name="target_audience" :value="targetAudienceLabel">

            <!-- Fitur Utama: Kolom Kepada (To) Standard Email Client -->
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-1.5 gap-1.5">
                    <label for="recipients" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Kepada (To) <span class="text-rose-500">*</span>
                    </label>
                    <!-- Fitur Khusus: Preset Group Shortcut Buttons -->
                    <div class="flex items-center space-x-1.5 flex-wrap">
                        <span class="text-[11px] text-slate-400 font-medium mr-1">Aksi Cepat : </span>
                        <button type="button" 
                                @click="setPreset('Semua Pengguna', allEmails)" 
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-lg border border-slate-200 transition-all cursor-pointer">
                            + Semua ({{ $totalUsers }})
                        </button>
                        <button type="button" 
                                @click="setPreset('Khusus Staf', stafEmails)" 
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-lg border border-slate-200 transition-all cursor-pointer">
                            + Staf ({{ $stafCount }})
                        </button>
                        <button type="button" 
                                @click="setPreset('Khusus Direksi', direksiEmails)" 
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-lg border border-slate-200 transition-all cursor-pointer">
                            + Direksi ({{ $direksiCount }})
                        </button>
                    </div>
                </div>
                <textarea name="recipients" 
                          id="recipients" 
                          x-model="recipients"
                          rows="3" 
                          required 
                          placeholder="Gunakan Koma atau baris baru untuk memisahkan email"
                          class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-xs sm:text-sm font-mono text-slate-900 transition-all shadow-2xs">{{ old('recipients') }}</textarea>
                @error('recipients')
                    <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Subjek Email / Judul Pesan <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="subject" 
                       id="subject" 
                       value="{{ old('subject') }}" 
                       required 
                       placeholder="Contoh: Undangan Rapat Koordinasi / Pengumuman Sekretariat..."
                       class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-xs sm:text-sm text-slate-900 transition-all shadow-2xs">
                @error('subject')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Rich Text Editor (Quill.js with Inline Image Support) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Isi Pesan Notifikasi / Undangan <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-slate-400">Gunakan ikon <strong class="text-slate-600">Gambar</strong> pada toolbar untuk menyisipkan foto inline</span>
                </div>
                
                <div id="editor-container"></div>
                <input type="hidden" name="body" :value="bodyHtml" required>
                
                @error('body')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bagian Lampiran Dokumen / File Attachments (Gmail Style) -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Lampiran Dokumen / File (PDF, Word, Excel, Gambar, Zip, dll)
                </label>

                <div class="flex items-center space-x-3">
                    <label for="attachment-input" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition-all inline-flex items-center space-x-2 cursor-pointer shadow-2xs">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span>Pilih Lampiran File</span>
                    </label>
                    <input type="file" 
                           name="attachments[]" 
                           id="attachment-input" 
                           multiple 
                           @change="handleFileSelect($event)" 
                           class="hidden">
                    <span class="text-[11px] text-slate-400">Maks. 15MB per file</span>
                </div>

                <!-- Preview Daftar File Yang Dilampirkan -->
                <div x-show="attachedFiles.length > 0" class="mt-3 space-y-2">
                    <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">File Terpilih (<span x-text="attachedFiles.length"></span>):</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="(item, index) in attachedFiles" :key="index">
                            <div class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                                <div class="flex items-center space-x-2 truncate mr-2">
                                    <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-medium text-slate-800 truncate" x-text="item.name"></span>
                                    <span class="text-[10px] text-slate-400 shrink-0 font-mono" x-text="'(' + item.sizeFormatted + ')'"></span>
                                </div>
                                <button type="button" @click="removeFile(index)" title="Hapus Lampiran" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-50 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                @error('attachments')
                    <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                    <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-3 flex items-center justify-end border-t border-slate-100">
                <button type="submit" :disabled="loading" class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span x-show="!loading">Kirim Pesan Email</span>
                    <span x-show="loading" class="flex items-center space-x-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Mengirim Email & Lampiran...</span>
                    </span>
                </button>
            </div>
        </form>

        <!-- 2. Riwayat Broadcast Email (Pindah ke Bawah, Full Width Card) -->
        <div class="mt-8 pt-6 border-t border-slate-100 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Riwayat Broadcast Email</span>
                </h2>
                <span class="text-xs text-slate-500 font-medium">Total Terkirim: <strong>{{ $broadcasts->total() }}</strong></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Waktu</th>
                            <th class="py-3.5 px-4">Subjek Email</th>
                            <th class="py-3.5 px-4">Penerima</th>
                            <th class="py-3.5 px-4">Jumlah Penerima</th>
                            <th class="py-3.5 px-4">Pengirim</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($broadcasts as $broadcast)
                            @php
                                $attachmentsList = [];
                                if (!empty($broadcast->attachments) && is_array($broadcast->attachments)) {
                                    foreach ($broadcast->attachments as $idx => $att) {
                                        $attachmentsList[] = [
                                            'name' => $att['name'] ?? 'File Lampiran',
                                            'size' => $att['size'] ?? 0,
                                            'download_url' => route('broadcast-emails.download-attachment', ['broadcast' => $broadcast->id, 'index' => $idx]),
                                        ];
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                                    {{ $broadcast->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-xs">{{ $broadcast->subject }}</div>
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ Str::limit(strip_tags($broadcast->body), 100) }}</div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                    {{ $broadcast->target_audience }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $broadcast->recipient_count }} Email
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap font-medium">
                                    {{ $broadcast->sender?->name ?? 'System' }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button type="button"
                                            data-broadcast="{{ json_encode([
                                                'id' => $broadcast->id,
                                                'subject' => $broadcast->subject,
                                                'body' => $broadcast->body,
                                                'date' => $broadcast->created_at?->translatedFormat('d F Y, H:i'),
                                                'sender' => $broadcast->sender?->name ?? 'System',
                                                'target' => $broadcast->target_audience,
                                                'count' => $broadcast->recipient_count,
                                                'attachments' => $attachmentsList
                                            ]) }}"
                                            @click="
                                                selectedBroadcast = JSON.parse($el.dataset.broadcast);
                                                detailModalOpen = true;
                                            "
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg border border-slate-200 transition-all text-xs cursor-pointer inline-flex items-center space-x-1">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500 italic text-xs">
                                    Belum ada riwayat email broadcast.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($broadcasts->hasPages())
                <div class="pt-2">
                    {{ $broadcasts->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Detail Broadcast Email -->
        <div x-show="detailModalOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
             style="display: none;">
            
            <div @click.away="detailModalOpen = false" 
                 class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-3xl w-full overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Detail Broadcast Email</h3>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-200/60 transition-all cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content (Scrollable) -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <!-- Metadata Box -->
                    <div class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-xl grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="sm:col-span-2">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Subjek Email</span>
                            <span class="font-bold text-slate-900 block text-xs mt-0.5" x-text="selectedBroadcast.subject"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Waktu Kirim</span>
                            <span class="font-semibold text-slate-700 block text-xs mt-0.5 font-mono" x-text="selectedBroadcast.date"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Pengirim</span>
                            <span class="font-semibold text-slate-700 block text-xs mt-0.5" x-text="selectedBroadcast.sender"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Target Penerima</span>
                            <span class="font-semibold text-slate-800 block text-xs mt-0.5" x-text="selectedBroadcast.target"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Total Penerima</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 mt-0.5" x-text="selectedBroadcast.count + ' Email'"></span>
                        </div>
                    </div>

                    <!-- Lampiran File Box -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                            Lampiran File Terkirim (<span x-text="selectedBroadcast.attachments ? selectedBroadcast.attachments.length : 0"></span>):
                        </label>
                        <template x-if="selectedBroadcast.attachments && selectedBroadcast.attachments.length > 0">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template x-for="(att, idx) in selectedBroadcast.attachments" :key="idx">
                                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs gap-2">
                                        <div class="flex items-center space-x-2 truncate min-w-0">
                                            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            <span class="font-bold text-slate-800 truncate" x-text="att.name"></span>
                                            <span class="text-[10px] text-slate-400 font-mono shrink-0" x-text="formatSize(att.size || 0)"></span>
                                        </div>
                                        <template x-if="att.download_url">
                                            <a :href="att.download_url"
                                               target="_blank"
                                               class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] rounded-lg transition-all shadow-2xs inline-flex items-center space-x-1 shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                <span>Unduh</span>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!selectedBroadcast.attachments || selectedBroadcast.attachments.length === 0">
                            <div class="p-3 bg-slate-50/70 border border-slate-200/80 rounded-xl text-xs text-slate-400 italic">
                                Tidak ada dokumen/file yang dilampirkan pada pengiriman email ini.
                            </div>
                        </template>
                    </div>

                    <!-- Body Content Box -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Isi Pesan Email:</label>
                        <div class="p-5 bg-white border border-slate-200 rounded-xl text-slate-800 text-xs sm:text-sm leading-relaxed shadow-2xs overflow-x-auto" 
                             x-html="selectedBroadcast.body">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

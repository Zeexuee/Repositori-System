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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Kirim Broadcast Email (Mobile: Atas, Desktop: Kiri Utama lg:col-span-2) -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 sm:p-7 shadow-2xs space-y-5">
            <form action="{{ route('broadcast-emails.send') }}" 
                  method="POST" 
                  class="space-y-4" 
                  x-data="{ 
                      recipients: '{{ old('recipients') }}', 
                      bodyHtml: `{!! old('body') !!}`,
                      targetAudienceLabel: '{{ old('target_audience', 'Custom Recipient') }}', 
                      loading: false,
                      allEmails: '{{ $allUserEmails }}',
                      stafEmails: '{{ $stafUserEmails }}',
                      direksiEmails: '{{ $direksiUserEmails }}',
                      setPreset(type, emails) {
                          this.recipients = emails;
                          this.targetAudienceLabel = type;
                      },
                      initQuill() {
                          const quill = new Quill('#editor-container', {
                              theme: 'snow',
                              placeholder: 'Tuliskan isi pesan pengumuman atau detail undangan di sini...',
                              modules: {
                                  toolbar: [
                                      [{ 'header': [1, 2, 3, false] }],
                                      ['bold', 'italic', 'underline', 'strike'],
                                      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                      [{ 'align': [] }],
                                      [{ 'color': [] }, { 'background': [] }],
                                      ['link', 'clean']
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
                              placeholder="Gunakan Koma atau baris baru untuk memisahkan"
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

                <!-- Rich Text Editor (Quill.js - Rich Text Formatting Like Gmail) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Isi Pesan Notifikasi / Undangan <span class="text-rose-500">*</span>
                    </label>
                    
                    <div id="editor-container"></div>
                    <input type="hidden" name="body" :value="bodyHtml" required>
                    
                    @error('body')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2 flex items-center justify-end">
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
                            <span>Mengirim Email...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar Riwayat Broadcast Email (Mobile: Bawah, Desktop: Samping Saja lg:col-span-1) -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Riwayat Terbaru</span>
                    </div>
                    <span class="text-xs text-slate-500 font-medium normal-case">Total: {{ $broadcasts->total() }}</span>
                </h2>

                <div class="space-y-3">
                    @forelse ($broadcasts as $broadcast)
                        <div class="p-3.5 bg-slate-50/70 border border-slate-100 rounded-xl space-y-1.5 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <span class="font-mono">{{ $broadcast->created_at?->format('d/m/Y H:i') }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    {{ $broadcast->recipient_count }} Email
                                </span>
                            </div>
                            <h3 class="font-bold text-slate-900 text-xs leading-snug">{{ $broadcast->subject }}</h3>
                            <p class="text-[11px] text-slate-500 line-clamp-2">{!! Str::limit(strip_tags($broadcast->body), 80) !!}</p>
                            <div class="pt-1 flex items-center justify-between text-[10px] text-slate-400">
                                <span>Oleh: {{ $broadcast->sender?->name ?? 'System' }}</span>
                                <span class="font-semibold text-slate-600">{{ $broadcast->target_audience }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-500 italic text-xs">Belum ada riwayat email broadcast.</div>
                    @endforelse
                </div>

                <div class="pt-2">
                    {{ $broadcasts->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

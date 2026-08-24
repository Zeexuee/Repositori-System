@extends('layouts.app')

@section('title', 'Riwayat Broadcast Email')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Riwayat Broadcast Email</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar pengiriman pesan notifikasi dan undangan email massal yang telah dikirim.</p>
        </div>
        <div class="mt-3 sm:mt-0 flex items-center space-x-3">
            <a href="{{ route('broadcast-emails.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs inline-flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tulis Broadcast Baru</span>
            </a>
        </div>
    </div>

    <!-- Tabel Riwayat Broadcast Email -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-4"
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
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Waktu Kirim</th>
                        <th class="py-3 px-4">Pengirim</th>
                        <th class="py-3 px-4">Subjek & Isi Pesan</th>
                        <th class="py-3 px-4">Target Penerima</th>
                        <th class="py-3 px-4 text-center">Penerima</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
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
                            <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $broadcast->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                {{ $broadcast->sender?->name ?? 'System' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block text-xs">{{ $broadcast->subject }}</span>
                                <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ Str::limit(strip_tags($broadcast->body), 120) }}</p>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $broadcast->target_audience }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-900 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    {{ $broadcast->recipient_count }} Email
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
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
                            <td colspan="6" class="py-12 text-center text-slate-500 italic">Belum ada riwayat broadcast email yang dikirim.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $broadcasts->links() }}
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

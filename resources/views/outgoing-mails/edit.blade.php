@extends('layouts.app')

@section('title', 'Edit Surat Keluar')

@section('content')
    <div x-data="editOutgoingMailForm()" class="space-y-6">
        <div class="pb-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Surat Keluar</h1>
                <p class="text-xs text-slate-500 mt-1">Perbarui rincian, berkas lampiran, atau status surat keluar.</p>
            </div>
            <div class="flex items-center space-x-2">
                <div class="inline-flex items-center space-x-2 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                    <span>Status: <strong class="text-slate-900 font-mono">{{ App\Support\OutgoingMailStatus::label($outgoingMail->status) }}</strong></span>
                </div>
                @can('delete', $outgoingMail)
                    <button type="button" 
                            @click="openDeleteModal()" 
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus Dokumen</span>
                    </button>
                @endcan
            </div>
        </div>

        <form action="{{ route('outgoing-mails.update', $outgoingMail) }}" method="POST" enctype="multipart/form-data" class="space-y-6" @submit="loading = true">
        @csrf
        @method('PUT')

        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Nomor Surat -->
                <div>
                    <label for="mail_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Surat</label>
                    <input type="text" name="mail_number" id="mail_number" value="{{ old('mail_number', $outgoingMail->mail_number) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                    @error('mail_number')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subjek / Perihal -->
                <div>
                    <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subjek / Perihal <span class="text-slate-900 font-bold">*</span></label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject', $outgoingMail->subject) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('subject')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Penerima Surat -->
                <div>
                    <label for="recipient" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Penerima Surat <span class="text-slate-900 font-bold">*</span></label>
                    <input type="text" name="recipient" id="recipient" value="{{ old('recipient', $outgoingMail->recipient) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('recipient')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Surat Keluar -->
                <div>
                    <label for="created_at" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Surat Keluar <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                    <input type="datetime-local" name="created_at" id="created_at" value="{{ old('created_at', $outgoingMail->created_at?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                    @error('created_at')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Upload Berkas PDF / Gambar (Multi-file) -->
            <div x-data="{ hasFile: false, fileName: '' }">
                <label for="file" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Tambah / Ganti Berkas Dokumen (PDF / Gambar)
                </label>
                @if ($outgoingMail->attachments->isNotEmpty())
                    <div class="mb-3 space-y-2">
                        <span class="text-xs font-bold text-slate-700 block">Berkas Terunggah Saat Ini ({{ $outgoingMail->attachments->count() }} berkas):</span>
                        @foreach ($outgoingMail->attachments as $att)
                            <div class="p-2.5 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                                <div class="flex items-center space-x-2 min-w-0 pr-2">
                                    @php
                                        $isImg = str_starts_with($att->mime_type ?? '', 'image/');
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isImg ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $isImg ? 'FOTO' : 'PDF' }}
                                    </span>
                                    <span class="truncate font-medium text-slate-800">{{ $att->file_name ?? basename($att->file_path) }}</span>
                                    @if ($att->file_size)
                                        <span class="text-[10px] text-slate-400 font-mono">({{ number_format($att->file_size / 1024, 1) }} KB)</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 flex-shrink-0">
                                    <a href="{{ route('document.download', ['path' => $att->file_path, 'inline' => 1]) }}" target="_blank" class="text-xs text-slate-900 hover:underline font-semibold">
                                        Pratinjau
                                    </a>
                                    <a href="{{ route('document.download', ['path' => $att->file_path]) }}" class="text-xs text-slate-900 hover:underline font-semibold">
                                        Unduh
                                    </a>
                                    @can('update', $outgoingMail)
                                        <button type="button" 
                                                onclick="deleteOutgoingAttachment('{{ $att->id }}')" 
                                                class="text-xs text-rose-600 hover:text-rose-800 font-semibold cursor-pointer">
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($outgoingMail->file_path)
                    <div class="mb-3 p-3 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                        <span class="font-medium text-slate-700 truncate pr-2">Berkas Utama: {{ basename($outgoingMail->file_path) }}</span>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <a href="{{ route('document.download', ['path' => $outgoingMail->file_path, 'inline' => 1]) }}" target="_blank" class="text-slate-900 underline font-semibold">
                                Pratinjau
                            </a>
                            <a href="{{ route('document.download', ['path' => $outgoingMail->file_path]) }}" class="text-slate-900 underline font-semibold">
                                Unduh
                            </a>
                        </div>
                    </div>
                @endif

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl hover:border-slate-300 transition-all space-y-3">
                    <input type="file" 
                           name="files[]" 
                           id="file" 
                           multiple
                           x-ref="fileInput"
                           accept="application/pdf,image/*" 
                           @change="hasFile = $event.target.files.length > 0; fileName = hasFile ? ($event.target.files.length + ' file dipilih') : ''"
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">

                    <div x-show="hasFile" x-cloak class="flex items-center justify-between p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg">
                        <span class="text-xs font-medium text-emerald-800 truncate max-w-[220px]" x-text="'File baru: ' + fileName"></span>
                        <button type="button" 
                                @click="$refs.fileInput.value = ''; hasFile = false; fileName = ''"
                                class="px-2.5 py-1 text-xs font-bold text-rose-700 bg-white border border-rose-200 rounded-md hover:bg-rose-50 shadow-2xs transition-all flex-shrink-0 cursor-pointer">
                            Batal Upload
                        </button>
                    </div>
                </div>
                @error('files')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
                @error('file')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Catatan Disposisi -->
            <div>
                <label for="disposition_note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                <textarea name="disposition_note" id="disposition_note" rows="3" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">{{ old('disposition_note', $outgoingMail->disposition_note) }}</textarea>
                @error('disposition_note')
                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Histori Perubahan Berkas -->
            @if ($outgoingMail->fileHistories->count() > 0)
                <div class="pt-6 border-t border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            <span>Histori Perubahan Berkas ({{ $outgoingMail->fileHistories->count() }})</span>
                        </h3>
                    </div>
                    <div class="space-y-2">
                        @foreach ($outgoingMail->fileHistories as $history)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-xl gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-slate-800 font-mono break-all">{{ $history->file_name ?? basename($history->file_path) }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-medium">Versi Lama</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Diganti pada <span class="font-medium text-slate-700">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                                        @if ($history->uploader)
                                            oleh <span class="font-medium text-slate-700">{{ $history->uploader->name }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2 self-end sm:self-center">
                                    <a href="{{ route('document.download', ['path' => $history->file_path, 'inline' => 1]) }}" target="_blank" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold rounded-lg shadow-2xs transition-all">
                                        Pratinjau
                                    </a>
                                    <a href="{{ route('document.download', ['path' => $history->file_path]) }}" class="px-3 py-1.5 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold rounded-lg shadow-xs transition-all">
                                        Unduh
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200/80">
            <a href="{{ route('outgoing-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
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

    @can('delete', $outgoingMail)
        <!-- Modal Popup Konfirmasi Hapus Dokumen -->
        <div x-show="showDeleteModal" 
             x-cloak
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
             style="margin: 0 !important;"
             @keydown.escape.window="showDeleteModal = false">
            
            <!-- Backdrop Blur -->
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showDeleteModal = false"></div>
            
            <!-- Modal Card Content -->
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-900">Konfirmasi Hapus Dokumen</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Apakah Anda yakin ingin menghapus surat keluar ini? Dokumen dengan No: <strong class="text-slate-800 font-mono">{{ $outgoingMail->mail_number ?: '-' }}</strong> akan dipindahkan ke tempat sampah (soft delete).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" 
                            @click="showDeleteModal = false"
                            :disabled="isDeleting"
                            class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl transition-all disabled:opacity-50 shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="button" 
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-xs disabled:opacity-50 cursor-pointer flex items-center space-x-2">
                        <span x-show="!isDeleting">Ya, Hapus Dokumen</span>
                        <span x-show="isDeleting" class="flex items-center space-x-1.5" x-cloak>
                            <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Menghapus...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <form id="delete-outgoing-mail-form" action="{{ route('outgoing-mails.destroy', $outgoingMail) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endcan
    </div>

    <script>
        function editOutgoingMailForm() {
            return {
                loading: false,
                isDeleting: false,
                showDeleteModal: false,

                openDeleteModal() {
                    this.showDeleteModal = true;
                },

                executeDelete() {
                    this.isDeleting = true;
                    const deleteForm = document.getElementById('delete-outgoing-mail-form');
                    if (deleteForm) {
                        deleteForm.submit();
                    }
                }
            };
        }

        async function deleteOutgoingAttachment(attachmentId) {
            if (!confirm('Apakah Anda yakin ingin menghapus berkas dokumen ini?')) {
                return;
            }
            try {
                const response = await fetch(`{{ route('outgoing-mails.index') }}/{{ $outgoingMail->id }}/attachments/${attachmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menghapus berkas dokumen.');
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi saat menghapus berkas.');
            }
        }
    </script>
@endsection

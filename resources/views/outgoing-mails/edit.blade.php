@extends('layouts.app')

@section('title', 'Edit Surat Keluar')
@section('custom_edit_alert', true)

@section('content')
    <div x-data="editOutgoingMailForm()" class="space-y-6">
        <div class="pb-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2 font-medium" aria-label="Breadcrumb">
                    <a href="{{ route('outgoing-mails.index') }}" class="hover:text-slate-900 hover:underline transition-colors">
                        Surat Keluar
                    </a>
                    <span class="text-slate-300">/</span>
                    <a href="{{ route('outgoing-mails.show', $outgoingMail) }}" class="hover:text-slate-900 hover:underline transition-colors font-mono">
                        {{ $outgoingMail->mail_number ?: 'Detail' }}
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-800 font-semibold">Edit Dokumen</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Surat Keluar</h1>
                <p class="text-xs text-slate-500 mt-1">Perbarui rincian, berkas lampiran, status, atau progres surat keluar.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('outgoing-mails.show', $outgoingMail) }}" 
                   class="inline-flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                    ← Kembali
                </a>
                <div class="inline-flex items-center space-x-2 px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                    <span>Progres: <strong class="text-slate-900 font-mono">{{ App\Support\OutgoingMailStatus::label($outgoingMail->progress) }}</strong></span>
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

        <!-- Tanda Berhasil Disimpan (Iconless, Colorless, Minimalis) -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="p-3.5 sm:p-4 bg-slate-100 border border-slate-300 text-slate-800 rounded-xl text-xs sm:text-sm flex items-center justify-between shadow-2xs">
                <span class="font-medium">{{ session('success') }}</span>
                <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-700 text-lg leading-none px-1.5 cursor-pointer" title="Tutup">&times;</button>
            </div>
        @endif

        <!-- Tanda Gagal Disimpan - Error Sesi (Iconless, Colorless, Minimalis) -->
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="p-3.5 sm:p-4 bg-slate-100 border border-slate-300 text-slate-800 rounded-xl text-xs sm:text-sm flex items-center justify-between shadow-2xs">
                <span class="font-medium">{{ session('error') }}</span>
                <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-700 text-lg leading-none px-1.5 cursor-pointer" title="Tutup">&times;</button>
            </div>
        @endif

        <!-- Tanda Gagal Disimpan - Kesalahan Input / Validasi (Iconless, Colorless, Minimalis) -->
        <div x-show="errorMessageList.length > 0" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="p-3.5 sm:p-4 bg-slate-100 border border-slate-300 text-slate-800 rounded-xl text-xs sm:text-sm space-y-2 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="font-bold text-slate-900">Gagal menyimpan perubahan. Silakan periksa kembali input berikut:</p>
                <button type="button" @click="errorMessageList = []" class="text-slate-400 hover:text-slate-700 text-lg leading-none px-1.5 cursor-pointer" title="Tutup">&times;</button>
            </div>
            <ul class="list-disc list-inside space-y-1 font-medium text-slate-700">
                <template x-for="(err, idx) in errorMessageList" :key="idx">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>

        <form action="{{ route('outgoing-mails.update', $outgoingMail) }}" method="POST" enctype="multipart/form-data" class="space-y-6" @submit="onSubmit($event)">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $outgoingMail->id }}">

        <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
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

                <!-- Penerima Surat (Multi-Recipient Chip Combobox) -->
                <div x-data="multiRecipientSelect({ listType: 'recipients', initialValue: @js(old('recipient', $outgoingMail->recipient)), defaultOptions: @js($recipients ?? []) })" class="relative" @click.away="closeDropdown()">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Penerima Surat <span class="text-slate-900 font-bold">*</span>
                    </label>
                    <input type="hidden" name="recipient" :value="value">

                    <!-- Trigger Box with Chips -->
                    <div @click="toggle()"
                         class="w-full min-h-[42px] px-3 py-1.5 bg-slate-50/50 border border-slate-200 rounded-xl text-left text-sm text-slate-900 focus-within:ring-2 focus-within:ring-slate-900 flex items-center justify-between gap-2 shadow-2xs transition-all cursor-pointer">
                        <div class="flex flex-wrap items-center gap-1.5 flex-1">
                            <template x-for="(tag, tIdx) in tags" :key="tag + '_' + tIdx">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-900 text-white shadow-2xs">
                                    <span x-text="tag"></span>
                                    <button type="button" @click.stop="removeTag(tIdx)" class="text-slate-300 hover:text-white leading-none cursor-pointer" title="Hapus penerima ini">&times;</button>
                                </span>
                            </template>
                            <span x-show="tags.length === 0" class="text-slate-400 text-sm py-0.5 px-1">Pilih / Tambah Penerima...</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <!-- Dropdown List -->
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                        <div class="relative">
                            <input x-ref="searchInput"
                                   type="text"
                                   x-model="search"
                                   @keydown.enter.prevent.stop="commitSearch()"
                                   @keydown.comma.prevent.stop="commitSearch()"
                                   @keydown.escape.prevent.stop="closeDropdown()"
                                   placeholder="Ketik penerima lalu tekan Enter..."
                                   class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                            <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">&times;</span>
                        </div>

                        <div class="max-h-44 overflow-y-auto space-y-1">
                            <div class="pb-1 border-b border-slate-100">
                                <button type="button"
                                        @click="search.trim() !== '' ? addNewOption(search.trim()) : $refs.searchInput.focus()"
                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer">
                                    <span x-show="search.trim() === ''">+ Ketik untuk Tambah Penerima Baru</span>
                                    <span x-show="search.trim() !== ''">+ Tambahkan "<span x-text="search.trim()"></span>"</span>
                                </button>
                            </div>

                            <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar penerima</p>
                            <template x-for="item in filteredOptions" :key="item">
                                <div class="flex items-center space-x-1">
                                    <button type="button"
                                            @click="toggleOption(item)"
                                            class="flex-1 text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                            :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': isSelected(item) }">
                                        <span x-text="item" class="truncate pr-2"></span>
                                        <span x-show="isSelected(item)" class="text-xs">&#10003;</span>
                                    </button>
                                    <button type="button"
                                            @click.stop="removeOption(item)"
                                            class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                                            title="Hapus dari daftar saran">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    @error('recipient')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status (Opsional) -->
                <div x-data="searchableSelect({ listType: 'statuses', initialValue: @js(old('status', $outgoingMail->status)), defaultOptions: @js($statuses ?? \App\Support\MailCustomStatus::DEFAULTS) })" class="relative" @click.away="open = false">
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                    <div class="relative">
                        <input type="text" name="status" id="status" x-model="value" @focus="open = true" @input="open = true" autocomplete="off" placeholder="Ketik atau pilih status..." class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs pr-8">
                        <button type="button" @click="open = !open" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                    <div x-show="open && filteredOptions.length > 0" x-transition class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto py-1">
                        <template x-for="opt in filteredOptions" :key="opt">
                            <div @click="select(opt)" class="flex items-center justify-between px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 cursor-pointer group">
                                <span x-text="opt" class="truncate pr-2"></span>
                                <button type="button" @click.stop="remove(opt)" title="Hapus dari daftar saran" class="text-slate-300 hover:text-rose-600 opacity-0 group-hover:opacity-100 transition-opacity p-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    @error('status')
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
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-300">
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
                                                class="text-xs text-slate-500 hover:text-slate-900 font-semibold cursor-pointer">
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

                    <div x-show="hasFile" x-cloak class="flex items-center justify-between p-2.5 bg-slate-100 border border-slate-300 rounded-lg">
                        <span class="text-xs font-medium text-slate-800 truncate max-w-[220px]" x-text="'File baru: ' + fileName"></span>
                        <button type="button" 
                                @click="$refs.fileInput.value = ''; hasFile = false; fileName = ''"
                                class="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 shadow-2xs transition-all flex-shrink-0 cursor-pointer">
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
                ← Batal & Kembali
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
        document.addEventListener('alpine:init', () => {
            Alpine.data('searchableSelect', ({ listType, initialValue, defaultOptions }) => ({
                open: false,
                value: initialValue || '',
                rawOptions: defaultOptions || [],
                deleted: JSON.parse(localStorage.getItem('deleted_outgoing_' + listType) || '[]'),
                get options() {
                    return this.rawOptions.filter(item => !this.deleted.includes(item));
                },
                get filteredOptions() {
                    if (!this.value) return this.options;
                    return this.options.filter(opt => opt.toLowerCase().includes(this.value.toLowerCase()));
                },
                select(opt) {
                    this.value = opt;
                    this.open = false;
                },
                remove(opt) {
                    if (!this.deleted.includes(opt)) {
                        this.deleted.push(opt);
                        localStorage.setItem('deleted_outgoing_' + listType, JSON.stringify(this.deleted));
                    }
                }
            }));

            Alpine.data('multiRecipientSelect', ({ listType, initialValue, defaultOptions }) => {
                const parseTags = (raw) => {
                    if (!raw) return [];
                    if (Array.isArray(raw)) return raw.map(s => String(s).trim()).filter(Boolean);
                    return String(raw).split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
                };
                const initialTags = parseTags(initialValue);
                const rawOpts = Array.isArray(defaultOptions) ? [...defaultOptions] : [];
                initialTags.forEach(tag => {
                    if (!rawOpts.some(o => String(o).toLowerCase() === tag.toLowerCase())) {
                        rawOpts.unshift(tag);
                    }
                });

                return {
                    open: false,
                    search: '',
                    tags: initialTags,
                    rawOptions: rawOpts,
                    deleted: JSON.parse(localStorage.getItem('deleted_outgoing_' + (listType || 'recipients')) || '[]'),
                    get value() {
                        return this.tags.join(', ');
                    },
                    get options() {
                        return this.rawOptions.filter(item => !this.deleted.includes(item));
                    },
                    get filteredOptions() {
                        const kw = this.search.trim().toLowerCase();
                        if (!kw) return this.options;
                        return this.options.filter(opt => String(opt).toLowerCase().includes(kw));
                    },
                    isSelected(opt) {
                        const target = String(opt).toLowerCase();
                        return this.tags.some(t => t.toLowerCase() === target);
                    },
                    toggle() {
                        this.open = !this.open;
                        if (this.open) {
                            this.$nextTick(() => this.$refs.searchInput?.focus());
                        }
                    },
                    closeDropdown() {
                        if (this.search.trim() !== '') {
                            this.commitSearch();
                        }
                        this.open = false;
                    },
                    addTag(opt) {
                        const clean = String(opt || '').trim();
                        if (!clean) return;
                        if (!this.isSelected(clean)) {
                            this.tags.push(clean);
                        }
                    },
                    removeTag(index) {
                        this.tags.splice(index, 1);
                    },
                    toggleOption(opt) {
                        const target = String(opt).toLowerCase();
                        const idx = this.tags.findIndex(t => t.toLowerCase() === target);
                        if (idx !== -1) {
                            this.tags.splice(idx, 1);
                        } else {
                            this.tags.push(opt);
                        }
                        this.search = '';
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    },
                    addNewOption(newOpt) {
                        const parts = String(newOpt || '').split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
                        parts.forEach(part => {
                            if (!this.rawOptions.some(o => String(o).toLowerCase() === part.toLowerCase())) {
                                this.rawOptions.unshift(part);
                            }
                            if (this.deleted.includes(part)) {
                                this.deleted = this.deleted.filter(d => d !== part);
                                localStorage.setItem('deleted_outgoing_' + (listType || 'recipients'), JSON.stringify(this.deleted));
                            }
                            this.addTag(part);
                        });
                        this.search = '';
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    },
                    removeOption(opt) {
                        if (!this.deleted.includes(opt)) {
                            this.deleted.push(opt);
                            localStorage.setItem('deleted_outgoing_' + (listType || 'recipients'), JSON.stringify(this.deleted));
                        }
                        const target = String(opt).toLowerCase();
                        this.tags = this.tags.filter(t => t.toLowerCase() !== target);
                    },
                    commitSearch() {
                        const kw = this.search.trim();
                        if (!kw) return;
                        this.addNewOption(kw);
                    }
                };
            }));
        });

        function editOutgoingMailForm() {
            return {
                loading: false,
                isDeleting: false,
                showDeleteModal: false,
                errorMessageList: {{ json_encode($errors->all()) }},

                openDeleteModal() {
                    this.showDeleteModal = true;
                },

                executeDelete() {
                    this.isDeleting = true;
                    const deleteForm = document.getElementById('delete-outgoing-mail-form');
                    if (deleteForm) {
                        deleteForm.submit();
                    }
                },

                onSubmit(e) {
                    this.errorMessageList = [];
                    const errors = [];

                    const subjectInput = document.getElementById('subject');
                    if (!subjectInput || !subjectInput.value.trim()) {
                        errors.push('Subjek / Perihal surat wajib diisi.');
                    }

                    const recipientInput = document.querySelector('input[name="recipient"]');
                    if (!recipientInput || !recipientInput.value.trim()) {
                        errors.push('Penerima Surat wajib diisi.');
                    }

                    if (errors.length > 0) {
                        e.preventDefault();
                        this.errorMessageList = errors;
                        this.loading = false;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return false;
                    }

                    this.loading = true;
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

@extends('layouts.app')

@section('title', 'Edit Surat Masuk')

@section('content')
    @php
        $firstImgAtt = $incomingMail->attachments->first(fn($a) => str_starts_with($a->mime_type ?? '', 'image/'));
        $initialPreviewUrl = '';
        $initialFileType = 'none';
        $initialFileName = '';

        if ($incomingMail->document_photo_path) {
            $initialPreviewUrl = route('document.download', ['path' => $incomingMail->document_photo_path, 'inline' => 1]);
            $initialFileType = 'image';
            $initialFileName = basename($incomingMail->document_photo_path);
        } elseif ($firstImgAtt) {
            $initialPreviewUrl = route('document.download', ['path' => $firstImgAtt->file_path, 'inline' => 1]);
            $initialFileType = 'image';
            $initialFileName = $firstImgAtt->file_name ?? basename($firstImgAtt->file_path);
        } elseif ($incomingMail->file_path) {
            $initialPreviewUrl = route('document.download', ['path' => $incomingMail->file_path, 'inline' => 1]);
            $initialFileType = 'pdf';
            $initialFileName = basename($incomingMail->file_path);
        } elseif ($incomingMail->attachments->isNotEmpty()) {
            $firstAtt = $incomingMail->attachments->first();
            $isImg = str_starts_with($firstAtt->mime_type ?? '', 'image/');
            $initialPreviewUrl = route('document.download', ['path' => $firstAtt->file_path, 'inline' => 1]);
            $initialFileType = $isImg ? 'image' : 'pdf';
            $initialFileName = $firstAtt->file_name ?? basename($firstAtt->file_path);
        }
    @endphp

    <div x-data="editIncomingMailForm()" class="space-y-6">
        <div class="pb-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Surat Masuk</h1>
                <p class="text-xs text-slate-500 mt-1">Perbarui rincian surat masuk, unggah berkas, dan periksa pratinjau dokumen.</p>
            </div>
            <div class="flex items-center space-x-2.5">
                <div class="inline-flex items-center space-x-2 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                    <span>No. Surat: <strong class="text-slate-900 font-mono">{{ $incomingMail->mail_number ?: '-' }}</strong></span>
                </div>
                @can('delete', $incomingMail)
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

        <div x-show="errorMessageList.length > 0" x-cloak class="p-4 bg-slate-100 border border-slate-300 rounded-2xl text-xs text-slate-800 space-y-2">
            <p class="font-bold text-slate-900">Gagal memperbarui dokumen. Silakan periksa kembali input berikut:</p>
            <ul class="list-disc list-inside space-y-1 font-medium">
                <template x-for="(err, idx) in errorMessageList" :key="idx">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>

        <form x-ref="form" action="{{ route('incoming-mails.update', $incomingMail) }}" method="POST" enctype="multipart/form-data" class="space-y-6" @submit.prevent="onSubmit">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Utama & Pengirim -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
                <div class="pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Informasi Utama & Pengirim</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dari (Pengirim) -->
                    <div x-data="searchableSelect({
                        initialValue: {{ json_encode(old('sender', $incomingMail->sender)) }},
                        defaultOptions: {{ json_encode($senders ?? []) }}
                    })" class="relative" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Dari / Biro Pengirim <span class="text-slate-900 font-bold">*</span>
                        </label>
                        <input type="hidden" name="sender" :value="value">

                        <!-- Trigger Button -->
                        <button type="button" 
                                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all cursor-pointer">
                            <span x-text="value ? value : 'Pilih / Cari Biro Pengirim...'" :class="{ 'text-slate-400': !value, 'text-slate-900 font-bold': value }"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown List -->
                        <div x-show="open" x-cloak 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute z-30 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                            
                            <div class="relative">
                                <input x-ref="searchInput" 
                                       type="text" 
                                       x-model="search" 
                                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">×</span>
                            </div>

                            <div class="max-h-44 overflow-y-auto space-y-1">
                                <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar list yang ditambahkan</p>
                                <template x-for="item in filteredOptions" :key="item">
                                    <button type="button" 
                                            @click="selectOption(item)" 
                                            class="w-full text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                            :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': value === item }">
                                        <span x-text="item"></span>
                                        <span x-show="value === item" class="text-xs">✓</span>
                                    </button>
                                </template>

                                <div x-show="search.trim() !== '' && !filteredOptions.map(o => o.toLowerCase()).includes(search.trim().toLowerCase())" class="pt-1 border-t border-slate-100">
                                    <button type="button" 
                                            @click="addNewOption(search.trim())" 
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg flex items-center space-x-1.5 transition-colors cursor-pointer">
                                        <span>Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('sender')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tanggal Masuk -->
                    <div>
                        <label for="received_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Masuk <span class="text-slate-900 font-bold">*</span></label>
                        <input type="datetime-local" name="received_date" id="received_date" value="{{ old('received_date', $incomingMail->received_date?->format('Y-m-d\TH:i')) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                        @error('received_date')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            <!-- Section 2: Detail Dokumen -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6">
                <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Detail Dokumen Surat</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nomor Surat -->
                    <div>
                        <label for="mail_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Surat <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                        <input type="text" name="mail_number" id="mail_number" value="{{ old('mail_number', $incomingMail->mail_number) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                        @error('mail_number')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kepada (Penerima) Dokumen Ini -->
                    <div x-data="searchableSelect({
                        initialValue: {{ json_encode(old('recipient', $incomingMail->recipient)) }},
                        defaultOptions: []
                    })" class="relative" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kepada / Penerima Dokumen <span class="text-slate-900 font-bold">*</span>
                        </label>
                        <input type="hidden" name="recipient" :value="value">

                        <!-- Trigger Button -->
                        <button type="button" 
                                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-left text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 flex items-center justify-between shadow-2xs transition-all cursor-pointer">
                            <span x-text="value ? value : 'Pilih / Cari Penerima...'" :class="{ 'text-slate-400': !value, 'text-slate-900 font-bold': value }"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown List -->
                        <div x-show="open" x-cloak 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute z-30 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                            
                            <div class="relative">
                                <input x-ref="searchInput" 
                                       type="text" 
                                       x-model="search" 
                                       class="w-full px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                <span x-show="search" @click="search = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 cursor-pointer text-xs font-bold">×</span>
                            </div>

                            <div class="max-h-44 overflow-y-auto space-y-1">
                                <p x-show="options.length === 0" class="text-xs text-slate-500 text-center py-2">Belum ada daftar list yang ditambahkan</p>
                                <template x-for="item in filteredOptions" :key="item">
                                    <button type="button" 
                                            @click="selectOption(item)" 
                                            class="w-full text-left px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 rounded-lg flex items-center justify-between transition-colors cursor-pointer"
                                            :class="{ 'bg-slate-900 text-white hover:bg-slate-800 font-bold': value === item }">
                                        <span x-text="item"></span>
                                        <span x-show="value === item" class="text-xs">✓</span>
                                    </button>
                                </template>

                                <div x-show="search.trim() !== '' && !filteredOptions.map(o => o.toLowerCase()).includes(search.trim().toLowerCase())" class="pt-1 border-t border-slate-100">
                                    <button type="button" 
                                            @click="addNewOption(search.trim())" 
                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 rounded-lg flex items-center space-x-1.5 transition-colors cursor-pointer">
                                        <span>Tambah "<span x-text="search.trim()"></span>" sebagai opsi baru</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('recipient')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Perihal -->
                <div>
                    <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Perihal <span class="text-slate-900 font-bold">*</span>
                    </label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject', $incomingMail->subject) }}" required class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">
                    @error('subject')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tanggal Keluar/Surat -->
                    <div>
                        <label for="outgoing_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Surat Keluar <span class="text-slate-400 font-normal normal-case">(Opsional)</span></label>
                        <input type="datetime-local" name="outgoing_date" id="outgoing_date" value="{{ old('outgoing_date', $incomingMail->outgoing_date?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs font-mono">
                        @error('outgoing_date')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Surat (mengikuti alur standar) -->
                    @php
                        $currentStatus = App\Support\IncomingMailStatus::canonical($incomingMail->status);
                        $currentStatusLabel = App\Support\IncomingMailStatus::label($incomingMail->status);
                        $allowedStatuses = App\Support\IncomingMailStatus::allowedTransitions($currentStatus);
                        $selectedStatus = App\Support\IncomingMailStatus::canonical(old('status', $incomingMail->status));
                    @endphp
                    <div x-data="{ status: @js($selectedStatus ?? $currentStatus) }">
                        <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Dokumen</label>
                        <select name="status" id="status" x-model="status"
                            @disabled($allowedStatuses === [])
                            class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs disabled:opacity-60">
                            <option value="{{ $currentStatus }}">
                                {{ $currentStatusLabel }} (status saat ini)
                            </option>
                            @foreach ($allowedStatuses as $allowedStatus)
                                @continue($allowedStatus === App\Support\IncomingMailStatus::RETURN || $allowedStatus === $currentStatus)
                                <option value="{{ $allowedStatus }}" {{ $selectedStatus === $allowedStatus ? 'selected' : '' }}>
                                    {{ App\Support\IncomingMailStatus::label($allowedStatus) }}
                                </option>
                            @endforeach
                        </select>

                        @if ($allowedStatuses === [])
                            <p class="text-xs text-slate-500 mt-1.5">
                                {{ $currentStatusLabel }} adalah status akhir dan tidak dapat diubah lagi.
                            </p>
                        @elseif (in_array(App\Support\IncomingMailStatus::RETURN, $allowedStatuses, true))
                            <p class="text-xs text-slate-500 mt-1.5">
                                Status RETURN hanya dapat dipilih melalui proses tanda tangan pengembalian di halaman daftar surat masuk.
                            </p>
                        @endif

                        <!-- Catatan revisi: wajib saat mengajukan revisi, opsional saat menyelesaikannya -->
                        <div x-show="status === 'REVISI' || @js($currentStatus === App\Support\IncomingMailStatus::REVISI)"
                            x-cloak class="mt-3 space-y-1.5">
                            <label for="revision_note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <span x-text="status === 'REVISI' ? 'Catatan Kebutuhan Revisi' : 'Catatan Penyelesaian Revisi'"></span>
                                <span x-show="status === 'REVISI'" class="text-rose-600">*</span>
                                <span x-show="status !== 'REVISI'" class="text-slate-400 font-normal normal-case">(opsional)</span>
                            </label>
                            <textarea name="revision_note" id="revision_note" rows="3" maxlength="2000"
                                :required="status === 'REVISI'"
                                :placeholder="status === 'REVISI'
                                    ? 'Jelaskan bagian mana yang perlu diperbaiki.'
                                    : 'Jelaskan bagaimana revisi diselesaikan (boleh dikosongkan).'"
                                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">{{ old('revision_note') }}</textarea>
                            @error('revision_note')
                                <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('status')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Disposisi -->
                    <div>
                        <label for="disposition_note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Disposisi (Opsional)</label>
                        <textarea name="disposition_note" id="disposition_note" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">{{ old('disposition_note', $incomingMail->disposition_note) }}</textarea>
                        @error('disposition_note')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan Tambahan (Opsional)</label>
                        <textarea name="notes" id="notes" rows="2" class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm text-slate-900 transition-all shadow-2xs">{{ old('notes', $incomingMail->notes) }}</textarea>
                        @error('notes')
                            <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Unggah Berkas & Pratinjau Dokumen / Gambar -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 shadow-2xs space-y-6"
                 x-data="documentMediaManager({
                    initialPreviewUrl: {{ json_encode($initialPreviewUrl) }},
                    initialFileType: {{ json_encode($initialFileType) }},
                    initialFileName: {{ json_encode($initialFileName) }}
                 })">
                <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span>
                        <span>Berkas Dokumen & Pratinjau Gambar</span>
                    </h2>
                    <span class="text-[11px] font-medium text-slate-500">Mendukung berkas PDF, JPG, JPEG, PNG, WEBP (Maksimal 10MB)</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <!-- Kolom 1: Unggah Berkas & Daftar Berkas Terdaftar -->
                    <div class="space-y-5">
                        <!-- Dropzone File Upload -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Unggah Dokumen / Foto Baru
                            </label>

                            <div class="p-4 bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl hover:border-slate-400 transition-all space-y-3">
                                <div class="flex flex-col items-center justify-center py-2 text-center cursor-pointer" @click="$refs.photoInput.click()">
                                    <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-xs font-semibold text-slate-800">Klik untuk memilih berkas atau foto dokumen</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Bisa pilih beberapa berkas sekaligus (PDF / Gambar)</p>
                                </div>

                                <input type="file" 
                                       name="files[]" 
                                       id="file" 
                                       multiple
                                       x-ref="photoInput"
                                       accept="application/pdf,image/*" 
                                       capture="environment" 
                                       @change="handleFileChange($event)"
                                       class="hidden">

                                <div x-show="selectedFiles.length > 0" x-cloak class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-emerald-900" x-text="selectedFiles.length + ' Berkas Baru Dipilih:'"></span>
                                        <button type="button" 
                                                @click="clearSelectedFiles()"
                                                class="px-2.5 py-1 text-[11px] font-bold text-rose-700 bg-white border border-rose-200 rounded-lg hover:bg-rose-50 shadow-2xs transition-all cursor-pointer">
                                            Batal Pilihan
                                        </button>
                                    </div>
                                    <ul class="text-[11px] text-emerald-800 space-y-1 divide-y divide-emerald-100 max-h-36 overflow-y-auto">
                                        <template x-for="(file, idx) in selectedFiles" :key="idx">
                                            <li class="pt-1 flex items-center justify-between">
                                                <div class="flex items-center space-x-1.5 truncate pr-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    <span class="truncate font-medium font-mono" x-text="file.name"></span>
                                                </div>
                                                <button type="button" 
                                                        @click="previewSelectedFile(idx)" 
                                                        class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 underline flex-shrink-0 cursor-pointer">
                                                    Pratinjau
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            @error('files')
                                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                            @error('file')
                                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Daftar Berkas Terunggah Saat Ini -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Berkas Terdaftar di Dokumen
                                </label>
                                @if ($incomingMail->attachments->isNotEmpty())
                                    <span class="text-[10px] text-slate-500 font-medium">({{ $incomingMail->attachments->count() }} berkas tersimpan)</span>
                                @endif
                            </div>

                            @if ($incomingMail->attachments->isNotEmpty())
                                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                                    @foreach ($incomingMail->attachments as $att)
                                        @php
                                            $isImg = str_starts_with($att->mime_type ?? '', 'image/');
                                            $downloadUrl = route('document.download', ['path' => $att->file_path]);
                                            $previewUrl = route('document.download', ['path' => $att->file_path, 'inline' => 1]);
                                            $fileName = $att->file_name ?? basename($att->file_path);
                                        @endphp
                                        <div id="incoming-att-{{ $att->id }}" class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs hover:bg-slate-100/60 transition-colors">
                                            <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isImg ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                                    {{ $isImg ? 'FOTO' : 'PDF' }}
                                                </span>
                                                <span class="truncate font-medium text-slate-800" title="{{ $fileName }}">{{ $fileName }}</span>
                                                @if ($att->file_size)
                                                    <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">({{ number_format($att->file_size / 1024, 1) }} KB)</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-2 flex-shrink-0">
                                                <button type="button" 
                                                        @click="setPreview('{{ $previewUrl }}', '{{ $isImg ? 'image' : 'pdf' }}', '{{ $fileName }}')" 
                                                        class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-200 text-slate-900 rounded-lg text-xs font-semibold shadow-2xs transition-all cursor-pointer">
                                                    Pratinjau
                                                </button>
                                                <a href="{{ $downloadUrl }}" class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-200 text-slate-900 rounded-lg text-xs font-semibold shadow-2xs transition-all">
                                                    Unduh
                                                </a>
                                                @can('update', $incomingMail)
                                                    <button type="button" 
                                                            @click="deleteAttachment('{{ $att->id }}')" 
                                                            class="px-2 py-1 text-xs text-rose-600 hover:text-rose-800 font-semibold cursor-pointer">
                                                        Hapus
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($incomingMail->file_path || $incomingMail->document_photo_path)
                                @php
                                    $legacyPath = $incomingMail->document_photo_path ?: $incomingMail->file_path;
                                    $isImg = (bool) $incomingMail->document_photo_path;
                                    $previewUrl = route('document.download', ['path' => $legacyPath, 'inline' => 1]);
                                    $fileName = basename($legacyPath);
                                @endphp
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isImg ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                            {{ $isImg ? 'FOTO' : 'PDF' }}
                                        </span>
                                        <span class="truncate font-medium text-slate-800">{{ $fileName }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2 flex-shrink-0">
                                        <button type="button" 
                                                @click="setPreview('{{ $previewUrl }}', '{{ $isImg ? 'image' : 'pdf' }}', '{{ $fileName }}')" 
                                                class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-200 text-slate-900 rounded-lg text-xs font-semibold shadow-2xs transition-all cursor-pointer">
                                            Pratinjau
                                        </button>
                                        <a href="{{ route('document.download', ['path' => $legacyPath]) }}" class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-200 text-slate-900 rounded-lg text-xs font-semibold shadow-2xs transition-all">
                                            Unduh
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs text-slate-500 italic">
                                    Belum ada berkas lampiran yang diunggah untuk surat masuk ini.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Kolom 2: Tempat Pratinjau Dokumen / Gambar (Pengganti Canvas Tanda Tangan) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Pratinjau Dokumen / Gambar
                            </label>
                            <template x-if="previewUrl">
                                <a :href="previewUrl" target="_blank" class="text-xs text-slate-900 hover:underline font-semibold flex items-center space-x-1">
                                    <span>Buka Ukuran Penuh</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </template>
                        </div>

                        <!-- Card Pratinjau Interaktif -->
                        <div class="bg-slate-100/90 border border-slate-300 rounded-2xl p-4 min-h-[340px] flex flex-col items-center justify-center relative overflow-hidden shadow-inner">
                            <!-- State 1: Pratinjau Gambar -->
                            <template x-if="previewType === 'image' && previewUrl">
                                <div class="w-full flex flex-col items-center space-y-2">
                                    <div class="max-h-80 w-full overflow-hidden rounded-xl border border-slate-200 bg-white flex items-center justify-center p-2 shadow-xs">
                                        <img :src="previewUrl" :alt="previewName" class="max-h-76 w-auto object-contain rounded-lg">
                                    </div>
                                    <p class="text-[11px] text-slate-600 font-mono truncate max-w-full font-medium" x-text="previewName"></p>
                                </div>
                            </template>

                            <!-- State 2: Pratinjau PDF -->
                            <template x-if="previewType === 'pdf' && previewUrl">
                                <div class="w-full flex flex-col items-center space-y-3 py-8 text-center">
                                    <div class="w-16 h-16 rounded-2xl bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-700 shadow-xs">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="space-y-1">
                                        <span class="text-xs font-bold text-slate-900 block font-mono" x-text="previewName"></span>
                                        <span class="text-[11px] text-slate-500 block">Dokumen Berkas PDF</span>
                                    </div>
                                    <a :href="previewUrl" target="_blank" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-all inline-flex items-center space-x-1.5">
                                        <span>Buka & Baca Dokumen PDF</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                </div>
                            </template>

                            <!-- State 3: Kosong / Belum ada preview -->
                            <template x-if="!previewUrl">
                                <div class="text-center py-12 px-4 space-y-3">
                                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-200 flex items-center justify-center text-slate-400">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-slate-700">Area Pratinjau Dokumen / Gambar</p>
                                        <p class="text-[11px] text-slate-500 max-w-xs">Pilih atau unggah berkas di kolom sebelah kiri, atau klik tombol "Pratinjau" pada berkas terdaftar untuk menampilkannya di sini.</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200/80">
                <a href="{{ route('incoming-mails.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all shadow-2xs">
                    Batal
                </a>

                <button type="submit" :disabled="loading" class="px-6 py-2.5 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 disabled:opacity-50 inline-flex items-center space-x-2 transition-all shadow-xs cursor-pointer">
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

        @can('delete', $incomingMail)
            <!-- Modal Pop-up Konfirmasi Hapus Dokumen -->
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
                                    Apakah Anda yakin ingin menghapus surat masuk ini? Dokumen dengan No: <strong class="text-slate-800 font-mono">{{ $incomingMail->mail_number ?: '-' }}</strong> akan dipindahkan ke tempat sampah (soft delete).
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

            <form id="delete-incoming-mail-form" action="{{ route('incoming-mails.destroy', $incomingMail) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endcan
    </div>

    <script>
        function editIncomingMailForm() {
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
                    const deleteForm = document.getElementById('delete-incoming-mail-form');
                    if (deleteForm) {
                        deleteForm.submit();
                    }
                },

                async deleteAttachment(attachmentId) {
                    if (!confirm('Apakah Anda yakin ingin menghapus berkas dokumen ini?')) {
                        return;
                    }
                    try {
                        const response = await fetch(`{{ route('incoming-mails.index') }}/{{ $incomingMail->id }}/attachments/${attachmentId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        if (response.ok && data.success) {
                            const el = document.getElementById(`incoming-att-${attachmentId}`);
                            if (el) el.remove();
                        } else {
                            alert(data.message || 'Gagal menghapus berkas dokumen.');
                        }
                    } catch (err) {
                        alert('Terjadi kesalahan koneksi saat menghapus berkas.');
                    }
                },
                
                async onSubmit(e) {
                    this.errorMessageList = [];
                    const errors = [];
                    
                    // Validasi input wajib: Biro Pengirim
                    const senderInput = this.$refs.form.querySelector('input[name="sender"]');
                    const senderVal = senderInput ? senderInput.value.trim() : '';
                    if (!senderVal) {
                        errors.push('Biro / Pengirim wajib dipilih atau diisi.');
                    }
                    
                    // Validasi input wajib: Kepada
                    const recipientInput = this.$refs.form.querySelector('input[name="recipient"]');
                    const recipientVal = recipientInput ? recipientInput.value.trim() : '';
                    if (!recipientVal) {
                        errors.push('Kepada / Penerima Dokumen wajib dipilih atau diisi.');
                    }

                    // Validasi input wajib: Tanggal Masuk
                    const dateInput = this.$refs.form.querySelector('input[name="received_date"]');
                    const dateVal = dateInput ? dateInput.value.trim() : '';
                    if (!dateVal) {
                        errors.push('Tanggal Masuk wajib diisi.');
                    }
                    
                    if (errors.length > 0) {
                        this.errorMessageList = errors;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return false;
                    }
                    
                    this.loading = true;
                    
                    try {
                        const formData = new FormData(this.$refs.form);
                        
                        const response = await fetch(this.$refs.form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = '{{ route('incoming-mails.index') }}';
                            }
                        } else {
                            const data = await response.json();
                            if (response.status === 422) {
                                this.errorMessageList = Object.values(data.errors).flat();
                            } else {
                                this.errorMessageList = [data.message || 'Terjadi kesalahan saat menyimpan data.'];
                            }
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    } catch (error) {
                        this.errorMessageList = ['Terjadi kesalahan koneksi atau server. Silakan coba lagi.'];
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        function searchableSelect(config) {
            const defaultOpts = Array.isArray(config.defaultOptions) ? [...config.defaultOptions] : [];
            const initVal = config.initialValue || '';
            if (initVal && !defaultOpts.includes(initVal)) {
                defaultOpts.unshift(initVal);
            }

            return {
                open: false,
                search: '',
                value: initVal,
                options: defaultOpts,

                get filteredOptions() {
                    if (!this.search.trim()) {
                        return this.options;
                    }
                    return this.options.filter(opt => 
                        opt.toLowerCase().includes(this.search.trim().toLowerCase())
                    );
                },

                selectOption(opt) {
                    this.value = opt;
                    this.search = '';
                    this.open = false;
                },

                addNewOption(newOpt) {
                    if (!newOpt) return;
                    if (!this.options.includes(newOpt)) {
                        this.options.push(newOpt);
                    }
                    this.value = newOpt;
                    this.search = '';
                    this.open = false;
                }
            };
        }

        function documentMediaManager(config) {
            return {
                previewUrl: config.initialPreviewUrl || '',
                previewType: config.initialFileType || 'none',
                previewName: config.initialFileName || '',
                selectedFiles: [],

                handleFileChange(e) {
                    const files = Array.from(e.target.files || []);
                    this.selectedFiles = files;
                    if (files.length > 0) {
                        this.previewSelectedFile(0);
                    }
                },

                previewSelectedFile(index) {
                    const file = this.selectedFiles[index];
                    if (!file) return;

                    this.previewName = file.name;
                    if (file.type.startsWith('image/')) {
                        this.previewType = 'image';
                        this.previewUrl = URL.createObjectURL(file);
                    } else if (file.type === 'application/pdf') {
                        this.previewType = 'pdf';
                        this.previewUrl = URL.createObjectURL(file);
                    } else {
                        this.previewType = 'file';
                        this.previewUrl = '';
                    }
                },

                clearSelectedFiles() {
                    this.selectedFiles = [];
                    if (this.$refs.photoInput) {
                        this.$refs.photoInput.value = '';
                    }
                    this.previewUrl = config.initialPreviewUrl || '';
                    this.previewType = config.initialFileType || 'none';
                    this.previewName = config.initialFileName || '';
                },

                setPreview(url, type, name) {
                    this.previewUrl = url;
                    this.previewType = type;
                    this.previewName = name;
                }
            };
        }
    </script>
@endsection

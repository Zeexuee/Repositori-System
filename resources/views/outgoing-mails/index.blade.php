@extends('layouts.app')

@section('title', 'Daftar Surat Keluar')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Surat Keluar</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola pencatatan, verifikasi, dan persetujuan surat keluar.</p>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Tombol Waiting -->
            <a href="{{ route('outgoing-mails.waiting') }}"
                class="inline-flex items-center space-x-2 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 text-xs font-semibold rounded-xl transition-all shadow-2xs">
                <span>Waiting</span>
                @if(!empty($waitingCount) && $waitingCount > 0)
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-900 text-white">{{ $waitingCount }}</span>
                @endif
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
        <form method="GET" action="{{ route('outgoing-mails.index') }}" class="flex items-center space-x-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor/subjek/penerima..."
                    class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-xs text-slate-900 transition-all shadow-2xs">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('outgoing-mails.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-xl transition-all" title="Reset pencarian">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="mt-4 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100/90 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <th class="p-3.5">Nomor Surat</th>
                        <th class="p-3.5">Subjek / Perihal</th>
                        <th class="p-3.5">Penerima / Disposisi</th>
                        <th class="p-3.5">Pembuat</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @forelse ($outgoingMails as $mail)
                        @php
                            $recipientsList = array_filter(array_map('trim', explode(',', (string) $mail->recipient)));
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5 font-bold text-slate-900 font-mono">
                                {{ $mail->mail_number ?? '-' }}
                            </td>
                            <td class="p-3.5 text-slate-800 font-medium max-w-xs truncate" title="{{ $mail->subject }}">
                                {{ $mail->subject }}
                            </td>
                            <td class="p-3.5 max-w-xs">
                                @if (count($recipientsList) > 1)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($recipientsList as $r)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-[10px] font-semibold text-slate-700">
                                                {{ $r }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-700 font-medium">{{ $mail->recipient ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-600 text-xs font-medium">{{ $mail->creator?->name ?? 'System' }}</td>
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
                                        'WAITING' => 'Menunggu Penerima',
                                        'PROGRES' => 'Dalam Proses',
                                        'RETURN' => 'Selesai / Dikembalikan',
                                        'REVISI' => 'Revisi Dokumen',
                                        'DRAFT' => 'Draf Dokumen',
                                    ];
                                @endphp

                                @can('update', $mail)
                                    <div class="relative inline-block text-left">
                                        <form x-ref="statusForm" action="{{ route('outgoing-mails.update', $mail) }}" method="POST">
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

                                        <!-- Dropdown Menu Teleported to Body (Liquid Glass Style) -->
                                        <template x-teleport="body">
                                            <div x-show="open"
                                                x-cloak
                                                @click.away="open = false"
                                                x-transition:enter="transition ease-out duration-120"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-100"
                                                x-transition:leave-start="transform opacity-100 scale-100"
                                                x-transition:leave-end="transform opacity-0 scale-95"
                                                :style="{ top: dropdownPos.top, left: dropdownPos.left }"
                                                class="fixed w-52 rounded-2xl bg-white/80 backdrop-blur-2xl border border-white/90 shadow-[0_20px_40px_rgba(15,23,42,0.15),inset_0_1px_1px_rgba(255,255,255,0.9)] p-1.5 z-[99999] space-y-0.5 focus:outline-hidden">
                                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-200/50 mb-1">
                                                    Pilih Status Surat
                                                </div>
                                                @foreach ($availableStatuses as $statusCode => $statusDesc)
                                                    <button type="button"
                                                        @click="if('{{ $statusCode }}' !== '{{ $mail->status }}') { if(confirm('Ubah status surat {{ $mail->mail_number ?? '' }} menjadi {{ $statusCode }}?')) { selectedStatus = '{{ $statusCode }}'; $nextTick(() => $refs.statusForm.submit()); } } open = false;"
                                                        class="w-full text-left px-2.5 py-1.5 text-xs rounded-xl hover:bg-slate-100/80 flex items-center justify-between transition-all font-medium text-slate-800 cursor-pointer {{ $mail->status === $statusCode ? 'bg-slate-100/90 font-bold text-slate-900 shadow-2xs' : '' }}">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="inline-block w-2 h-2 rounded-full {{ str_starts_with($statusCode, 'RETURN') ? 'bg-emerald-500' : (in_array($statusCode, ['WAITING', 'PROGRES']) ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                                                            <span>{{ $statusCode }}</span>
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
                            <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('outgoing-mails.show', $mail) }}"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-[11px] transition-all">
                                    Detail
                                </a>
                                @can('update', $mail)
                                    <a href="{{ route('outgoing-mails.edit', $mail) }}"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-[11px] transition-all">
                                        Edit
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 italic text-xs">
                                @if (request('search'))
                                    Tidak ditemukan surat keluar dengan kata kunci "{{ request('search') }}".
                                @else
                                    Belum ada data surat keluar.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $outgoingMails->links() }}
    </div>
@endsection
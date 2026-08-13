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
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-2xs space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Waktu Kirim</th>
                        <th class="py-3 px-4">Pengirim</th>
                        <th class="py-3 px-4">Subjek & Isi Pesan</th>
                        <th class="py-3 px-4">Target Penerima</th>
                        <th class="py-3 px-4 text-center">Penerima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($broadcasts as $broadcast)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $broadcast->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                {{ $broadcast->sender?->name ?? 'System' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block text-xs">{{ $broadcast->subject }}</span>
                                <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ Str::limit($broadcast->body, 120) }}</p>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500 italic">Belum ada riwayat broadcast email yang dikirim.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $broadcasts->links() }}
        </div>
    </div>
@endsection

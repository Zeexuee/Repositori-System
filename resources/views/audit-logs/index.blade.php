@extends('layouts.app')

@section('title', 'Jejak Audit System')

@section('content')
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200/80 gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                Event Log
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Riwayat aktivitas sistem per {{ $periodDate }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Download Log Aktif -->
            <a href="{{ route('audit-logs.export') }}"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs inline-flex items-center space-x-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Download Log</span>
            </a>

            <!-- Download Log Trash -->
            <a href="{{ route('audit-logs.export-trash') }}"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs inline-flex items-center space-x-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>See Trash</span>
            </a>
        </div>
    </div>

    <!-- Alert Flash Message -->
    @if (session('success'))
        <div
            class="mt-4 p-3 sm:p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Audit Logs Table Card -->
    <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Waktu</th>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Aksi</th>
                        <th class="py-3.5 px-4">Model Target</th>
                        <th class="py-3.5 px-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    @forelse ($auditLogs as $log)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $log->created_at?->translatedFormat('d M Y, H:i:s') ?? '-' }}
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $log->user?->name ?? 'System / Anonymous' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $log->user?->email ?? '-' }}</div>
                            </td>

                            <td class="py-3 px-4">
                                @php
                                    $actionColor = match (strtoupper($log->action)) {
                                        'CREATE', 'CREATED', 'STORE' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                        'UPDATE', 'UPDATED' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'DELETE', 'DELETED', 'DESTROY' => 'bg-rose-50 text-rose-800 border-rose-200',
                                        default => 'bg-slate-100 text-slate-800 border-slate-200',
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $actionColor }}">
                                    {{ strtoupper($log->action) }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-slate-600 font-mono">
                                {{ class_basename($log->model_type) }}
                                <span class="text-slate-400 text-[10px]">#{{ substr((string) $log->model_id, 0, 8) }}</span>
                            </td>

                            <td class="py-3 px-4 font-mono text-slate-500">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 italic text-xs">
                                Belum ada riwayat jejak audit yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($auditLogs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
@endsection
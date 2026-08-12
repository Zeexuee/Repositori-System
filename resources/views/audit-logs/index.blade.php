@extends('layouts.app')

@section('title', 'Jejak Audit System')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Jejak Audit (Audit Log Trail)</h1>
            <p class="text-xs text-slate-600 mt-1">Catatan riwayat aktivitas pengguna yang bersifat immutable (*append-only*).</p>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider">
                    <th class="py-3.5 px-4">Waktu</th>
                    <th class="py-3.5 px-4">Pengguna</th>
                    <th class="py-3.5 px-4">Aksi</th>
                    <th class="py-3.5 px-4">Model Target</th>
                    <th class="py-3.5 px-4">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-slate-800">
                @forelse ($auditLogs as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-600">
                            {{ $log->created_at?->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            {{ $log->user?->name ?? 'System / Anonymous' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-800 border border-slate-300">
                                {{ strtoupper($log->action) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 text-xs font-mono">
                            {{ class_basename($log->model_type) }} #{{ substr((string)$log->model_id, 0, 8) }}...
                        </td>
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-600">
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

    <div class="mt-4">
        {{ $auditLogs->links() }}
    </div>
@endsection

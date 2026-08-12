@extends('layouts.app')

@section('title', 'Jejak Audit System')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Jejak Audit (Audit Log Trail)</h1>
            <p class="text-sm text-gray-600 mt-1">Catatan riwayat aktivitas pengguna yang bersifat immutable (*append-only*).</p>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200 text-gray-700 font-semibold">
                    <th class="py-3 px-4">Waktu</th>
                    <th class="py-3 px-4">Pengguna</th>
                    <th class="py-3 px-4">Aksi</th>
                    <th class="py-3 px-4">Model Target</th>
                    <th class="py-3 px-4">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-800">
                @forelse ($auditLogs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 text-xs font-mono text-gray-600">
                            {{ $log->created_at?->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="py-3 px-4 font-medium text-gray-900">
                            {{ $log->user?->name ?? 'System / Anonymous' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                {{ strtoupper($log->action) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-700 text-xs font-mono">
                            {{ class_basename($log->model_type) }} #{{ substr((string)$log->model_id, 0, 8) }}...
                        </td>
                        <td class="py-3 px-4 text-xs font-mono text-gray-600">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-500">
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

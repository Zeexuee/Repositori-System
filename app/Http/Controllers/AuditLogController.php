<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs (Direksi only).
     */
    public function index(): View|RedirectResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $auditLogs = AuditLog::with('user')->latest()->paginate(25);
        $periodDate = Carbon::now()->translatedFormat('d F Y');

        return view('audit-logs.index', compact('auditLogs', 'periodDate'));
    }

    /**
     * Export active audit logs to CSV.
     */
    public function export(): StreamedResponse
    {
        Gate::authorize('export', AuditLog::class);

        $filename = 'event_logs_aktif_'.date('Y-m-d_H-i-s').'.csv';
        $logs = AuditLog::with('user')->latest()->get();

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['ID', 'Waktu Dibuat', 'Pengguna', 'Email Pengguna', 'Aksi', 'Model Target', 'Model ID', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s') ?? '-',
                    $log->user?->name ?? 'System',
                    $log->user?->email ?? '-',
                    strtoupper($log->action),
                    class_basename($log->model_type),
                    (string) $log->model_id,
                    $log->ip_address ?? '127.0.0.1',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export trashed audit logs to CSV.
     */
    public function exportTrash(): StreamedResponse
    {
        Gate::authorize('export', AuditLog::class);

        $filename = 'event_logs_sampah_'.date('Y-m-d_H-i-s').'.csv';
        $logs = AuditLog::onlyTrashed()->with('user')->latest('deleted_at')->get();

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['ID', 'Waktu Dibuat', 'Waktu Masuk Trash', 'Pengguna', 'Email Pengguna', 'Aksi', 'Model Target', 'Model ID', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s') ?? '-',
                    $log->deleted_at?->format('Y-m-d H:i:s') ?? '-',
                    $log->user?->name ?? 'System',
                    $log->user?->email ?? '-',
                    strtoupper($log->action),
                    class_basename($log->model_type),
                    (string) $log->model_id,
                    $log->ip_address ?? '127.0.0.1',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

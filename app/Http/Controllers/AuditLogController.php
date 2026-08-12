<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs (Super Admin / Authorized roles only).
     */
    public function index(): View|RedirectResponse
    {
        if (! auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Akses khusus Super Admin.');
        }

        $auditLogs = AuditLog::with('user')->latest()->paginate(25);

        return view('audit-logs.index', compact('auditLogs'));
    }
}

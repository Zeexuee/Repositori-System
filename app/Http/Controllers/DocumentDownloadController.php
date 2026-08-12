<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentDownloadController extends Controller
{
    /**
     * Download or stream a secure document from local disk storage.
     */
    public function download(Request $request): StreamedResponse|Response
    {
        $path = (string) $request->query('path');

        if (empty($path)) {
            abort(404, 'Path dokumen tidak valid.');
        }

        // Cari dokumen di tabel IncomingMail atau OutgoingMail untuk otorisasi Policy
        $incomingMail = IncomingMail::where('file_path', $path)->first();
        $outgoingMail = OutgoingMail::where('file_path', $path)->first();

        if ($incomingMail) {
            Gate::authorize('view', $incomingMail);
        } elseif ($outgoingMail) {
            Gate::authorize('view', $outgoingMail);
        } else {
            // Jika tidak ditemukan di kedua entitas, hanya Super Admin yang boleh akses
            if (! auth()->user()?->hasRole('Super Admin')) {
                abort(403, 'Anda tidak memiliki hak akses untuk dokumen ini.');
            }
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas dokumen tidak ditemukan pada penyimpanan lokal.');
        }

        return Storage::disk('local')->download($path);
    }
}

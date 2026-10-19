<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $incomingMail = IncomingMail::where('file_path', $path)
            ->orWhere('document_photo_path', $path)
            ->orWhere('receipt_signature_path', $path)
            ->first();

        if (! $incomingMail) {
            $attachment = \App\Models\IncomingMailAttachment::where('file_path', $path)->first();
            if ($attachment) {
                $incomingMail = $attachment->incomingMail;
            } else {
                $incomingReturn = \App\Models\IncomingMailReturnRecipient::where('signature_path', $path)->first();
                if ($incomingReturn) {
                    $incomingMail = $incomingReturn->incomingMail;
                } else {
                    $incomingDisposition = \App\Models\IncomingMailDispositionRecipient::where('signature_path', $path)->first();
                    if ($incomingDisposition) {
                        $incomingMail = $incomingDisposition->incomingMail;
                    }
                }
            }
        }

        $outgoingMail = OutgoingMail::where('file_path', $path)->first();

        if (! $outgoingMail) {
            $outgoingAttachment = \App\Models\OutgoingMailAttachment::where('file_path', $path)->first();
            if ($outgoingAttachment) {
                $outgoingMail = $outgoingAttachment->outgoingMail;
            } else {
                $outgoingReturn = \App\Models\OutgoingMailReturnRecipient::where('signature_path', $path)->first();
                if ($outgoingReturn) {
                    $outgoingMail = $outgoingReturn->outgoingMail;
                } else {
                    $history = \App\Models\OutgoingMailFileHistory::where('file_path', $path)->first();
                    if ($history) {
                        $outgoingMail = $history->outgoingMail;
                    }
                }
            }
        }

        if ($incomingMail) {
            Gate::authorize('view', $incomingMail);
        } elseif ($outgoingMail) {
            Gate::authorize('view', $outgoingMail);
        } else {
            // Jika tidak ditemukan di kedua entitas, pastikan pengguna memiliki peran yang valid
            if (! auth()->user()?->hasAnyRole(['staff', 'master'])) {
                abort(403, 'Anda tidak memiliki hak akses untuk dokumen ini.');
            }
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas dokumen tidak ditemukan pada penyimpanan lokal.');
        }

        // Jika rute dipanggil untuk inline preview (gambar), kembalikan response file inline
        if ($request->boolean('inline')) {
            $mimeType = Storage::disk('local')->mimeType($path) ?? 'application/octet-stream';

            return response()->file(Storage::disk('local')->path($path), [
                'Content-Type' => $mimeType,
            ]);
        }

        return Storage::disk('local')->download($path);
    }
}

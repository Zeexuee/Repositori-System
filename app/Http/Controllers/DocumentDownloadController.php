<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMail;
use App\Models\IncomingMailAttachment;
use App\Models\IncomingMailDispositionRecipient;
use App\Models\IncomingMailReturnRecipient;
use App\Models\OutgoingMail;
use App\Models\OutgoingMailAttachment;
use App\Models\OutgoingMailFileHistory;
use App\Models\OutgoingMailReturnRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Download or stream a secure document from local disk storage.
     *
     * A path is only served when it belongs to a document the user may view.
     * Unknown paths are rejected even for privileged roles, so this endpoint
     * cannot be used as a generic file reader.
     */
    public function download(Request $request): StreamedResponse|Response
    {
        $path = (string) $request->query('path');

        if ($path === '') {
            abort(404, 'Path dokumen tidak valid.');
        }

        $incomingMail = $this->findIncomingMail($path);
        $outgoingMail = $incomingMail === null ? $this->findOutgoingMail($path) : null;

        if ($incomingMail !== null) {
            Gate::authorize('view', $incomingMail);
        } elseif ($outgoingMail !== null) {
            Gate::authorize('view', $outgoingMail);
        } else {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas dokumen tidak ditemukan pada penyimpanan lokal.');
        }

        if ($request->boolean('inline')) {
            return response()->file(Storage::disk('local')->path($path), [
                'Content-Type' => Storage::disk('local')->mimeType($path) ?? 'application/octet-stream',
            ]);
        }

        return Storage::disk('local')->download($path);
    }

    private function findIncomingMail(string $path): ?IncomingMail
    {
        $mail = IncomingMail::query()
            ->where('file_path', $path)
            ->orWhere('document_photo_path', $path)
            ->orWhere('receipt_signature_path', $path)
            ->first();

        if ($mail !== null) {
            return $mail;
        }

        $attachment = IncomingMailAttachment::where('file_path', $path)->first();

        if ($attachment !== null) {
            return $attachment->incomingMail;
        }

        $returnRecipient = IncomingMailReturnRecipient::where('signature_path', $path)->first();

        if ($returnRecipient !== null) {
            return $returnRecipient->incomingMail;
        }

        return IncomingMailDispositionRecipient::where('signature_path', $path)
            ->first()
            ?->incomingMail;
    }

    private function findOutgoingMail(string $path): ?OutgoingMail
    {
        $mail = OutgoingMail::where('file_path', $path)->first();

        if ($mail !== null) {
            return $mail;
        }

        $attachment = OutgoingMailAttachment::where('file_path', $path)->first();

        if ($attachment !== null) {
            return $attachment->outgoingMail;
        }

        $returnRecipient = OutgoingMailReturnRecipient::where('signature_path', $path)->first();

        if ($returnRecipient !== null) {
            return $returnRecipient->outgoingMail;
        }

        return OutgoingMailFileHistory::where('file_path', $path)->first()?->outgoingMail;
    }
}

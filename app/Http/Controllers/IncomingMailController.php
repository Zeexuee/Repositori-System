<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingMailRequest;
use App\Http\Requests\UpdateIncomingMailRequest;
use App\Jobs\ProcessOcrJob;
use App\Models\IncomingMail;
use App\Models\User;
use App\Services\IncomingMailStatusSyncService;
use App\Services\IncomingMailStatusTransitionService;
use App\Support\IncomingMailStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IncomingMailController extends Controller
{
    public function __construct(
        private readonly IncomingMailStatusSyncService $statusSyncService,
        private readonly IncomingMailStatusTransitionService $statusTransitionService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', IncomingMail::class);

        $query = IncomingMail::query();

        // Pencarian Cepat Global (mencakup semua 9 kolom)
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mail_number', 'like', "%{$search}%")
                    ->orWhere('received_date', 'like', "%{$search}%")
                    ->orWhere('sender', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('outgoing_date', 'like', "%{$search}%")
                    ->orWhere('disposition_note', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        // Pencarian / Filter Spesifik:
        // 1. Nomor Surat
        if ($request->filled('mail_number')) {
            $query->where('mail_number', 'like', '%'.trim((string) $request->input('mail_number')).'%');
        }

        // 2. Tanggal (Tanggal Masuk)
        if ($request->filled('received_date')) {
            $query->whereDate('received_date', $request->input('received_date'));
        }

        // 3. Dari (Biro Pengirim)
        if ($request->filled('sender')) {
            $query->where('sender', 'like', '%'.trim((string) $request->input('sender')).'%');
        }

        // 4. Kepada
        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%'.trim((string) $request->input('recipient')).'%');
        }

        // 5. Status
        if ($request->filled('status')) {
            $status = IncomingMailStatus::canonical($request->input('status'))
                ?? strtoupper(trim((string) $request->input('status')));
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', IncomingMailStatus::DRAFT);
        }

        // 6. Perihal
        if ($request->filled('subject')) {
            $query->where('subject', 'like', '%'.trim((string) $request->input('subject')).'%');
        }

        // 7. Tanggal Keluar
        if ($request->filled('outgoing_date')) {
            $query->whereDate('outgoing_date', $request->input('outgoing_date'));
        }

        // 8. Disposisi
        if ($request->filled('disposition_note')) {
            $query->where('disposition_note', 'like', '%'.trim((string) $request->input('disposition_note')).'%');
        }

        // 9. Nama Penerima
        if ($request->filled('recipient_name')) {
            $query->where('recipient_name', 'like', '%'.trim((string) $request->input('recipient_name')).'%');
        }

        $incomingMails = $query->latest()->paginate(15)->withQueryString();

        // Return JSON untuk AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('incoming-mails.partials.table-rows', compact('incomingMails'))->render(),
                'pagination' => view('incoming-mails.partials.pagination', compact('incomingMails'))->render(),
                'total' => $incomingMails->total(),
                'allIds' => $incomingMails->pluck('id')->map(fn ($id) => (string) $id)->values(),
                'statuses' => $incomingMails->mapWithKeys(fn (IncomingMail $mail) => [
                    (string) $mail->id => IncomingMailStatus::canonical($mail->status) ?? (string) $mail->status,
                ]),
            ]);
        }

        return view('incoming-mails.index', compact('incomingMails'));
    }

    /**
     * Bulk update status for selected incoming mails.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'exists:incoming_mails,id'],
            'status' => ['required', 'string', 'in:RECEIVE,RECEIVED,REVISI,DISPOSISI,DRAFT'],
            'revision_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'ids.required' => 'Pilih minimal satu dokumen.',
            'status.in' => 'Status tujuan tidak tersedia. Status RETURN hanya dapat dicapai melalui proses tanda tangan pengembalian.',
            'revision_note.max' => 'Catatan revisi maksimal 2000 karakter.',
        ]);

        $targetStatus = IncomingMailStatus::canonical($validated['status']);
        $targetLabel = IncomingMailStatus::label($targetStatus);
        $revisionNote = $validated['revision_note'] ?? null;
        $actorId = auth()->id();

        $mails = IncomingMail::whereIn('id', $validated['ids'])->get();
        $updatedCount = 0;
        $rejected = [];

        foreach ($mails as $mail) {
            if (! Gate::allows('update', $mail)) {
                $rejected[] = [
                    'mail_number' => $mail->mail_number,
                    'reason' => 'Anda tidak memiliki izin mengubah dokumen ini.',
                ];

                continue;
            }

            $reason = $this->statusTransitionService->rejectionReason($mail, $targetStatus);

            if ($reason !== null) {
                $rejected[] = [
                    'mail_number' => $mail->mail_number,
                    'reason' => $reason,
                ];

                continue;
            }

            try {
                $this->statusTransitionService->apply($mail, $targetStatus, $actorId, $revisionNote);
                $updatedCount++;
            } catch (ValidationException $exception) {
                $rejected[] = [
                    'mail_number' => $mail->mail_number,
                    'reason' => collect($exception->errors())->flatten()->first(),
                ];
            }
        }

        $message = $updatedCount > 0
            ? "Status berhasil diperbarui menjadi {$targetLabel} untuk {$updatedCount} dokumen."
            : 'Tidak ada dokumen yang dapat diubah statusnya.';

        if ($rejected !== []) {
            $message .= ' '.count($rejected).' dokumen dilewati karena alur status tidak sesuai.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $updatedCount > 0,
                'message' => $message,
                'updated_count' => $updatedCount,
                'rejected' => $rejected,
            ], $updatedCount > 0 ? 200 : 422);
        }

        return redirect()
            ->route('incoming-mails.index')
            ->with($updatedCount > 0 ? 'success' : 'error', $message);
    }

    /**
     * Show the form for creating new incoming mail(s).
     */
    public function create(): View
    {
        Gate::authorize('create', IncomingMail::class);

        $nextSequenceNumber = IncomingMail::count() + 1;

        $defaultCategories = [
            'Kementerian BUMN',
            'Direksi / Board of Directors',
            'Divisi Hukum & Legal',
            'Divisi Keuangan & Akuntansi',
            'Divisi SDM & General Affairs',
            'Divisi IT & Digital Transformation',
            'Satuan Pengawasan Intern (SPI)',
            'Sekretariat Perusahaan',
            'Pihak Ketiga / Vendor',
        ];

        $dbSenders = IncomingMail::whereNotNull('sender')
            ->select('sender')
            ->distinct()
            ->pluck('sender')
            ->toArray();

        $dbRecipients = IncomingMail::whereNotNull('recipient')
            ->select('recipient')
            ->distinct()
            ->pluck('recipient')
            ->toArray();

        $senders = array_values(array_unique(array_merge($defaultCategories, $dbSenders)));
        $recipients = array_values(array_unique(array_merge($defaultCategories, $dbRecipients)));

        return view('incoming-mails.create', compact('nextSequenceNumber', 'senders', 'recipients'));
    }

    /**
     * Store newly created incoming mail(s) in storage (Support single or batch multi-document with locked sender and draft mode).
     */
    public function store(StoreIncomingMailRequest $request): RedirectResponse
    {
        Gate::authorize('create', IncomingMail::class);

        $validated = $request->validated();
        $globalStatus = $request->input('status') ?: 'RECEIVE';
        if ($request->input('is_draft') == '1') {
            $globalStatus = 'DRAFT';
        }

        // Shared Batch metadata & signature
        $batchId = (string) Str::uuid();
        $dateStr = now()->format('Ymd');
        $seq = IncomingMail::whereDate('created_at', now()->toDateString())->whereNotNull('batch_id')->distinct('batch_id')->count('batch_id') + 1;
        $receiptNumber = sprintf('TR-%s-%04d', $dateStr, $seq);

        // Storage Tanda Terima Signature (Shared for the batch)
        $receiptSignaturePath = null;
        if ($request->hasFile('receipt_signature_file')) {
            $receiptSignaturePath = $request->file('receipt_signature_file')->store('incoming-mails/signatures', 'local');
        } elseif ($request->filled('receipt_signature') && str_contains((string) $request->receipt_signature, 'data:image')) {
            $sigData = explode(',', (string) $request->receipt_signature)[1] ?? '';
            if (! empty($sigData)) {
                $decoded = base64_decode($sigData);
                $sigFileName = 'incoming-mails/signatures/'.Str::uuid().'.png';
                Storage::disk('local')->put($sigFileName, $decoded);
                $receiptSignaturePath = $sigFileName;
            }
        }

        // Common header attributes with LOCKED sender
        $commonAttributes = [
            'batch_id' => $batchId,
            'receipt_number' => $receiptNumber,
            'sender' => $validated['sender'],
            'received_date' => $validated['received_date'],
            'recipient' => $validated['recipient'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'receipt_signature_path' => $receiptSignaturePath,
            'status' => $globalStatus,
        ];

        $documents = $request->input('documents');

        if (is_array($documents) && count($documents) > 0) {
            foreach ($documents as $index => $doc) {
                $fileKey = "documents.{$index}.file";
                $photoKey = "documents.{$index}.document_photo";

                $filePath = null;
                $documentPhotoPath = null;

                if ($request->hasFile($fileKey)) {
                    $fileObj = $request->file($fileKey);
                    $mime = (string) $fileObj->getMimeType();
                    if (str_starts_with($mime, 'image/')) {
                        $documentPhotoPath = $fileObj->store('incoming-mails/photos', 'local');
                    } else {
                        $filePath = $fileObj->store('incoming-mails', 'local');
                    }
                }

                if ($request->hasFile($photoKey)) {
                    $documentPhotoPath = $request->file($photoKey)->store('incoming-mails/photos', 'local');
                }

                $mailNumber = ! empty($doc['mail_number']) ? $doc['mail_number'] : $receiptNumber;
                $subject = ! empty($doc['subject']) ? $doc['subject'] : '(Tanpa Perihal)';
                $docStatus = ! empty($doc['status']) ? $doc['status'] : $globalStatus;
                if ($request->input('is_draft') == '1') {
                    $docStatus = 'DRAFT';
                }
                $docRecipient = ! empty($doc['recipient']) ? $doc['recipient'] : ($validated['recipient'] ?? null);

                $incomingMail = IncomingMail::create(array_merge($commonAttributes, [
                    'mail_number' => $mailNumber,
                    'subject' => $subject,
                    'recipient' => $docRecipient,
                    'status' => $docStatus,
                    'outgoing_date' => ! empty($doc['outgoing_date']) ? $doc['outgoing_date'] : $validated['received_date'],
                    'disposition_note' => ! empty($doc['disposition_note']) ? $doc['disposition_note'] : null,
                    'notes' => ! empty($doc['notes']) ? $doc['notes'] : null,
                    'file_path' => $filePath,
                    'document_photo_path' => $documentPhotoPath,
                ]));

                if ($filePath) {
                    ProcessOcrJob::dispatch($incomingMail);
                }

                $this->statusSyncService->sync($incomingMail, auth()->id());
            }
        } else {
            // Single document mode
            $filePath = null;
            $documentPhotoPath = null;

            if ($request->hasFile('file')) {
                $fileObj = $request->file('file');
                $mime = (string) $fileObj->getMimeType();
                if (str_starts_with($mime, 'image/')) {
                    $documentPhotoPath = $fileObj->store('incoming-mails/photos', 'local');
                } else {
                    $filePath = $fileObj->store('incoming-mails', 'local');
                }
            }

            if ($request->hasFile('document_photo')) {
                $documentPhotoPath = $request->file('document_photo')->store('incoming-mails/photos', 'local');
            }

            $mailNumber = ! empty($validated['mail_number']) ? $validated['mail_number'] : $receiptNumber;
            $subject = ! empty($validated['subject']) ? $validated['subject'] : '(Tanpa Perihal)';

            $incomingMail = IncomingMail::create(array_merge($commonAttributes, [
                'mail_number' => $mailNumber,
                'subject' => $subject,
                'outgoing_date' => ! empty($validated['outgoing_date']) ? $validated['outgoing_date'] : $validated['received_date'],
                'disposition_note' => $validated['disposition_note'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'file_path' => $filePath,
                'document_photo_path' => $documentPhotoPath,
            ]));

            if ($filePath) {
                ProcessOcrJob::dispatch($incomingMail);
            }

            $this->statusSyncService->sync($incomingMail, auth()->id());
        }

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', 'Tanda Terima kolektif & Surat Masuk berhasil dicatat.');
    }

    /**
     * Display the specified incoming mail with its batch items.
     */
    public function show(IncomingMail $incomingMail): View
    {
        Gate::authorize('view', $incomingMail);

        $incomingMail->load([
            'dispositions.sender',
            'dispositions.receiver',
            'batchItems',
            'returnRecipients.creator',
            'revisionNotes.creator',
        ]);
        $users = User::orderBy('name')->get();

        return view('incoming-mails.show', compact('incomingMail', 'users'));
    }

    /**
     * Show the form for editing the specified incoming mail.
     */
    public function edit(IncomingMail $incomingMail): View
    {
        Gate::authorize('update', $incomingMail);

        $incomingMail->load('batchItems');

        $defaultCategories = [
            'Kementerian BUMN',
            'Direksi / Board of Directors',
            'Divisi Hukum & Legal',
            'Divisi Keuangan & Akuntansi',
            'Divisi SDM & General Affairs',
            'Divisi IT & Digital Transformation',
            'Satuan Pengawasan Intern (SPI)',
            'Sekretariat Perusahaan',
            'Pihak Ketiga / Vendor',
        ];

        $dbSenders = IncomingMail::whereNotNull('sender')
            ->select('sender')
            ->distinct()
            ->pluck('sender')
            ->toArray();

        $dbRecipients = IncomingMail::whereNotNull('recipient')
            ->select('recipient')
            ->distinct()
            ->pluck('recipient')
            ->toArray();

        $senders = array_values(array_unique(array_merge($defaultCategories, $dbSenders)));
        $recipients = array_values(array_unique(array_merge($defaultCategories, $dbRecipients)));

        return view('incoming-mails.edit', compact('incomingMail', 'senders', 'recipients'));
    }

    /**
     * Update the specified incoming mail in storage.
     */
    public function update(UpdateIncomingMailRequest $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('update', $incomingMail);

        $data = $request->validated();

        // Status changes follow the standard operating procedure, never a plain field update.
        $requestedStatus = IncomingMailStatus::canonical($data['status'] ?? null);
        $currentStatus = IncomingMailStatus::canonical($incomingMail->status);
        unset($data['status']);

        $statusChange = $requestedStatus !== null && $requestedStatus !== $currentStatus
            ? $requestedStatus
            : null;

        if ($statusChange !== null) {
            $reason = $this->statusTransitionService->rejectionReason($incomingMail, $statusChange);

            if ($reason !== null) {
                throw ValidationException::withMessages(['status' => $reason]);
            }
        }

        if ($request->hasFile('file')) {
            $fileObj = $request->file('file');
            $mime = (string) $fileObj->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $data['document_photo_path'] = $fileObj->store('incoming-mails/photos', 'local');
            } else {
                $data['file_path'] = $fileObj->store('incoming-mails', 'local');
            }
        }

        if ($request->hasFile('document_photo')) {
            $data['document_photo_path'] = $request->file('document_photo')->store('incoming-mails/photos', 'local');
        }

        if ($request->hasFile('receipt_signature_file')) {
            $data['receipt_signature_path'] = $request->file('receipt_signature_file')->store('incoming-mails/signatures', 'local');
        } elseif ($request->filled('receipt_signature') && str_contains((string) $request->receipt_signature, 'data:image')) {
            $sigData = explode(',', (string) $request->receipt_signature)[1] ?? '';
            if (! empty($sigData)) {
                $decoded = base64_decode($sigData);
                $sigFileName = 'incoming-mails/signatures/'.Str::uuid().'.png';
                Storage::disk('local')->put($sigFileName, $decoded);
                $data['receipt_signature_path'] = $sigFileName;
            }
        }

        $incomingMail->update($data);

        if ($statusChange !== null) {
            $this->statusTransitionService->apply(
                $incomingMail,
                $statusChange,
                auth()->id(),
                $request->input('revision_note')
            );
        } else {
            $this->statusSyncService->sync($incomingMail, auth()->id());
        }

        // If batch_id exists, sync common fields to other batch items if needed
        if ($incomingMail->batch_id) {
            $sharedUpdates = array_filter([
                'sender' => $data['sender'] ?? null,
                'received_date' => $data['received_date'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'receipt_signature_path' => $data['receipt_signature_path'] ?? null,
            ]);

            if (! empty($sharedUpdates)) {
                IncomingMail::where('batch_id', $incomingMail->batch_id)
                    ->where('id', '!=', $incomingMail->id)
                    ->update($sharedUpdates);
            }
        }

        // Trigger OCR if new file uploaded
        if ($request->hasFile('file') && $incomingMail->file_path) {
            ProcessOcrJob::dispatch($incomingMail);
        }

        return redirect()
            ->back()
            ->with('success', 'Surat Masuk berhasil diperbarui.');
    }

    /**
     * Remove the specified incoming mail from storage (Soft Delete).
     */
    public function destroy(IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('delete', $incomingMail);

        $incomingMail->delete();

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', 'Surat Masuk berhasil dihapus.');
    }
}

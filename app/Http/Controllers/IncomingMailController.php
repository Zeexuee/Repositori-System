<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingMailRequest;
use App\Http\Requests\UpdateIncomingMailRequest;
use App\Jobs\ProcessOcrJob;
use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IncomingMailController extends Controller
{
    /**
     * Display a listing of incoming mails with server-side pagination and draft filters.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', IncomingMail::class);

        $statusFilter = request('status_filter', 'all');

        $query = IncomingMail::latest();

        if ($statusFilter === 'draft') {
            $query->draft();
        } elseif ($statusFilter === 'submitted') {
            $query->submitted();
        }

        $incomingMails = $query->paginate(15)->withQueryString();

        $draftCount = IncomingMail::draft()->count();
        $submittedCount = IncomingMail::submitted()->count();

        return view('incoming-mails.index', compact('incomingMails', 'statusFilter', 'draftCount', 'submittedCount'));
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
        $isDraft = filter_var($request->input('is_draft'), FILTER_VALIDATE_BOOLEAN) || $request->input('action') === 'draft';
        $globalStatus = $isDraft ? 'DRAFT' : ($request->input('status') ?: 'RECEIVE');

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
                $sigFileName = 'incoming-mails/signatures/' . Str::uuid() . '.png';
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

                $mailNumber = ! empty($doc['mail_number']) ? $doc['mail_number'] : 'SM-DRAFT-' . Str::upper(Str::random(6));
                $subject = ! empty($doc['subject']) ? $doc['subject'] : '(Tanpa Perihal)';
                $docStatus = ! empty($doc['status']) ? $doc['status'] : $globalStatus;

                $incomingMail = IncomingMail::create(array_merge($commonAttributes, [
                    'mail_number' => $mailNumber,
                    'subject' => $subject,
                    'status' => $docStatus,
                    'outgoing_date' => ! empty($doc['outgoing_date']) ? $doc['outgoing_date'] : null,
                    'disposition_note' => ! empty($doc['disposition_note']) ? $doc['disposition_note'] : null,
                    'notes' => ! empty($doc['notes']) ? $doc['notes'] : null,
                    'file_path' => $filePath,
                    'document_photo_path' => $documentPhotoPath,
                ]));

                if (! $isDraft && $filePath) {
                    ProcessOcrJob::dispatch($incomingMail);
                }

                $this->syncToOutgoingMail($incomingMail);
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

            $mailNumber = ! empty($validated['mail_number']) ? $validated['mail_number'] : 'SM-DRAFT-' . Str::upper(Str::random(6));
            $subject = ! empty($validated['subject']) ? $validated['subject'] : '(Tanpa Perihal)';

            $incomingMail = IncomingMail::create(array_merge($commonAttributes, [
                'mail_number' => $mailNumber,
                'subject' => $subject,
                'outgoing_date' => $validated['outgoing_date'] ?? null,
                'disposition_note' => $validated['disposition_note'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'file_path' => $filePath,
                'document_photo_path' => $documentPhotoPath,
            ]));

            if (! $isDraft && $filePath) {
                ProcessOcrJob::dispatch($incomingMail);
            }

            $this->syncToOutgoingMail($incomingMail);
        }

        $msg = $isDraft
            ? 'Draft Tanda Terima kolektif berhasil disimpan.'
            : 'Tanda Terima kolektif & Surat Masuk berhasil dicatat.';

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', $msg);
    }

    /**
     * Display the specified incoming mail with its batch items.
     */
    public function show(IncomingMail $incomingMail): View
    {
        Gate::authorize('view', $incomingMail);

        $incomingMail->load(['dispositions.sender', 'dispositions.receiver', 'batchItems']);
        $users = \App\Models\User::orderBy('name')->get();

        return view('incoming-mails.show', compact('incomingMail', 'users'));
    }

    /**
     * Show the form for editing the specified incoming mail.
     */
    public function edit(IncomingMail $incomingMail): View
    {
        Gate::authorize('update', $incomingMail);

        $incomingMail->load('batchItems');

        return view('incoming-mails.edit', compact('incomingMail'));
    }

    /**
     * Update the specified incoming mail in storage.
     */
    public function update(UpdateIncomingMailRequest $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('update', $incomingMail);

        $data = $request->validated();
        $wasDraft = $incomingMail->status === 'DRAFT';
        $isDraft = filter_var($request->input('is_draft'), FILTER_VALIDATE_BOOLEAN) || $request->input('action') === 'draft';

        if ($isDraft) {
            $data['status'] = 'DRAFT';
        } elseif ($wasDraft && (! isset($data['status']) || $data['status'] === 'DRAFT')) {
            $data['status'] = 'RECEIVE';
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
                $sigFileName = 'incoming-mails/signatures/' . Str::uuid() . '.png';
                Storage::disk('local')->put($sigFileName, $decoded);
                $data['receipt_signature_path'] = $sigFileName;
            }
        }

        $incomingMail->update($data);

        // Sync OutgoingMail if status is RETURN or PROGRES
        $this->syncToOutgoingMail($incomingMail);

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

        // Trigger OCR if changing from draft to active and has file
        if ($wasDraft && $incomingMail->status !== 'DRAFT' && $incomingMail->file_path) {
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

    /**
     * Automatically create OutgoingMail record if status is RETURN or PROGRES.
     */
    protected function syncToOutgoingMail(IncomingMail $incomingMail): void
    {
        $status = Str::upper((string) $incomingMail->status);

        if (in_array($status, ['RETURN', 'RETURNED'], true)) {
            OutgoingMail::create([
                'mail_number' => 'SK-' . now()->format('Ymd') . '-' . sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1),
                'subject' => '[RETURN] ' . $incomingMail->subject,
                'recipient' => $incomingMail->sender,
                'file_path' => $incomingMail->file_path ?? $incomingMail->document_photo_path,
                'created_by' => auth()->id() ?? 1,
                'status' => 'APPROVED',
            ]);
        } elseif (in_array($status, ['PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING'], true)) {
            OutgoingMail::create([
                'mail_number' => 'SK-' . now()->format('Ymd') . '-' . sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1),
                'subject' => '[PROGRES] ' . $incomingMail->subject,
                'recipient' => $incomingMail->sender,
                'file_path' => $incomingMail->file_path ?? $incomingMail->document_photo_path,
                'created_by' => auth()->id() ?? 1,
                'status' => 'PENDING',
            ]);
        }
    }
}

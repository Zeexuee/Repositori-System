<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingMailRequest;
use App\Http\Requests\UpdateIncomingMailRequest;
use App\Jobs\ProcessOcrJob;
use App\Models\IncomingMail;
use App\Models\IncomingMailRevision;
use App\Models\OutgoingMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IncomingMailController extends Controller
{
    /**
     * Display a listing of incoming mails.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', IncomingMail::class);

        $statusFilter = request('status_filter', 'all');

        $query = IncomingMail::latest();

        if ($statusFilter === 'draft') {
            $query->draft();
        } elseif ($statusFilter === 'revisi') {
            $query->revisi();
        } elseif ($statusFilter === 'submitted') {
            $query->submitted();
        }

        $incomingMails = $query->paginate(15)->withQueryString();

        $draftCount = IncomingMail::draft()->count();
        $revisiCount = IncomingMail::revisi()->count();
        $submittedCount = IncomingMail::submitted()->count();

        return view('incoming-mails.index', compact('incomingMails', 'statusFilter', 'draftCount', 'revisiCount', 'submittedCount'));
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

        $mergedSenders = array_values(array_unique(array_filter(array_merge($defaultCategories, $dbSenders))));
        $mergedRecipients = array_values(array_unique(array_filter(array_merge($defaultCategories, $dbRecipients))));

        return view('incoming-mails.create', [
            'nextSequenceNumber' => $nextSequenceNumber,
            'presetSenders' => $mergedSenders,
            'presetRecipients' => $mergedRecipients,
        ]);
    }

    /**
     * Store newly created incoming mail(s) in storage.
     */
    public function store(StoreIncomingMailRequest $request): RedirectResponse
    {
        Gate::authorize('create', IncomingMail::class);

        $validated = $request->validated();
        $batchId = (string) Str::uuid();
        $receiptNumber = 'RCV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));

        $isDraft = filter_var($request->input('is_draft'), FILTER_VALIDATE_BOOLEAN) || $request->input('action') === 'draft';
        $globalStatus = $isDraft ? 'DRAFT' : 'RECEIVE';

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

        if ($isDraft) {
            return redirect()
                ->route('incoming-mails.index', ['status_filter' => 'draft'])
                ->with('success', 'Draf Surat Masuk berhasil disimpan.');
        }

        return redirect()
            ->route('incoming-mails.index')
            ->with('success', 'Surat Masuk berhasil dicatat dalam buku agenda.');
    }

    /**
     * Display the specified incoming mail with its batch items and revisions.
     */
    public function show(IncomingMail $incomingMail): View
    {
        Gate::authorize('view', $incomingMail);

        $incomingMail->load(['dispositions.sender', 'dispositions.receiver', 'batchItems', 'revisions.user']);
        $users = \App\Models\User::orderBy('name')->get();

        return view('incoming-mails.show', compact('incomingMail', 'users'));
    }

    /**
     * Show the form for editing the specified incoming mail (or finalizing draft batch).
     */
    public function edit(IncomingMail $incomingMail): View
    {
        Gate::authorize('update', $incomingMail);

        $incomingMail->load(['batchItems', 'revisions.user']);

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

        $mergedSenders = array_values(array_unique(array_filter(array_merge($defaultCategories, $dbSenders))));
        $mergedRecipients = array_values(array_unique(array_filter(array_merge($defaultCategories, $dbRecipients))));

        $batchCollection = ($incomingMail->batch_id && $incomingMail->batchItems && $incomingMail->batchItems->count() > 0)
            ? $incomingMail->batchItems
            : collect([$incomingMail]);

        $formattedDocuments = $batchCollection->map(function ($doc) {
            return [
                'id' => $doc->id,
                'mail_number' => $doc->mail_number,
                'outgoing_date' => $doc->outgoing_date ? $doc->outgoing_date->format('Y-m-d') : '',
                'subject' => $doc->subject,
                'disposition_note' => $doc->disposition_note ?? '',
                'notes' => $doc->notes ?? '',
                'status' => $doc->status,
                'file_path' => $doc->file_path,
                'document_photo_path' => $doc->document_photo_path,
            ];
        })->values()->toArray();

        return view('incoming-mails.edit', [
            'incomingMail' => $incomingMail,
            'presetSenders' => $mergedSenders,
            'presetRecipients' => $mergedRecipients,
            'batchDocuments' => $batchCollection,
            'formattedDocuments' => $formattedDocuments,
        ]);
    }

    /**
     * Update the specified incoming mail in storage (supports batch documents finalization).
     */
    public function update(UpdateIncomingMailRequest $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('update', $incomingMail);

        $data = $request->validated();
        $wasDraft = $incomingMail->status === 'DRAFT';
        $isDraft = filter_var($request->input('is_draft'), FILTER_VALIDATE_BOOLEAN) || $request->input('action') === 'draft';
        $globalStatus = $isDraft ? 'DRAFT' : 'RECEIVE';

        $documents = $request->input('documents');

        // Storage Tanda Terima Signature if provided
        $receiptSignaturePath = $incomingMail->receipt_signature_path;
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

        if (is_array($documents) && count($documents) > 0) {
            $batchId = $incomingMail->batch_id ?: (string) Str::uuid();
            $receiptNumber = $incomingMail->receipt_number ?: ('RCV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)));

            $commonAttributes = [
                'batch_id' => $batchId,
                'receipt_number' => $receiptNumber,
                'sender' => $request->input('sender', $incomingMail->sender),
                'received_date' => $request->input('received_date', $incomingMail->received_date),
                'recipient' => $request->input('recipient', $incomingMail->recipient),
                'recipient_name' => $request->input('recipient_name', $incomingMail->recipient_name),
                'receipt_signature_path' => $receiptSignaturePath,
            ];

            foreach ($documents as $index => $doc) {
                $docId = $doc['id'] ?? null;
                $fileKey = "documents.{$index}.file";
                $photoKey = "documents.{$index}.document_photo";

                $mailNumber = ! empty($doc['mail_number']) ? $doc['mail_number'] : ($isDraft ? 'SM-DRAFT-' . Str::upper(Str::random(6)) : $incomingMail->mail_number);
                $subject = ! empty($doc['subject']) ? $doc['subject'] : '(Tanpa Perihal)';
                $docStatus = $isDraft ? 'DRAFT' : (! empty($doc['status']) && $doc['status'] !== 'DRAFT' ? $doc['status'] : $globalStatus);

                $docAttributes = array_merge($commonAttributes, [
                    'mail_number' => $mailNumber,
                    'subject' => $subject,
                    'status' => $docStatus,
                    'outgoing_date' => ! empty($doc['outgoing_date']) ? $doc['outgoing_date'] : null,
                    'disposition_note' => ! empty($doc['disposition_note']) ? $doc['disposition_note'] : null,
                    'notes' => ! empty($doc['notes']) ? $doc['notes'] : null,
                ]);

                if ($request->hasFile($fileKey)) {
                    $fileObj = $request->file($fileKey);
                    $mime = (string) $fileObj->getMimeType();
                    if (str_starts_with($mime, 'image/')) {
                        $docAttributes['document_photo_path'] = $fileObj->store('incoming-mails/photos', 'local');
                    } else {
                        $docAttributes['file_path'] = $fileObj->store('incoming-mails', 'local');
                    }
                }

                if ($request->hasFile($photoKey)) {
                    $docAttributes['document_photo_path'] = $request->file($photoKey)->store('incoming-mails/photos', 'local');
                }

                if ($docId && $existing = IncomingMail::find($docId)) {
                    $existing->update($docAttributes);
                    if ($wasDraft && ! $isDraft && $existing->file_path) {
                        ProcessOcrJob::dispatch($existing);
                    }
                    $this->syncToOutgoingMail($existing);
                } else {
                    $newDoc = IncomingMail::create($docAttributes);
                    if (! $isDraft && $newDoc->file_path) {
                        ProcessOcrJob::dispatch($newDoc);
                    }
                    $this->syncToOutgoingMail($newDoc);
                }
            }

            $successMsg = $isDraft ? 'Draf Surat Masuk berhasil diperbarui.' : 'Surat Masuk berhasil difinalisasi dan dicatat dalam buku agenda.';
            return redirect()
                ->route('incoming-mails.index', $isDraft ? ['status_filter' => 'draft'] : [])
                ->with('success', $successMsg);
        }

        // Single document mode fallback
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

        if ($receiptSignaturePath) {
            $data['receipt_signature_path'] = $receiptSignaturePath;
        }

        // Track revisions when status is changed to REVISI or when editing a revised document
        $newStatus = $data['status'] ?? $incomingMail->status;
        $isRevisionChange = ($newStatus === 'REVISI' || $incomingMail->status === 'REVISI');

        if ($isRevisionChange) {
            $currentCount = $incomingMail->revision_count ?? 0;
            $newRevisionCount = ($incomingMail->status !== 'REVISI' && $newStatus === 'REVISI')
                ? ($currentCount + 1)
                : max(1, $currentCount);

            $data['revision_count'] = $newRevisionCount;

            $changedFields = [];
            foreach ($data as $k => $v) {
                if (in_array($k, ['mail_number', 'subject', 'sender', 'recipient', 'disposition_note', 'notes', 'status', 'file_path', 'document_photo_path', 'outgoing_date'], true)) {
                    $oldVal = $incomingMail->{$k};
                    if ($oldVal != $v) {
                        $changedFields[$k] = [
                            'old' => (string) $oldVal,
                            'new' => (string) $v,
                        ];
                    }
                }
            }

            if (empty($changedFields)) {
                $changedFields['status'] = [
                    'old' => (string) $incomingMail->status,
                    'new' => (string) $newStatus,
                ];
            }

            $previousFilePath = $incomingMail->file_path;
            $currentFilePath = $data['file_path'] ?? $incomingMail->file_path;
            $previousPhotoPath = $incomingMail->document_photo_path;
            $currentPhotoPath = $data['document_photo_path'] ?? $incomingMail->document_photo_path;
            $previousSigPath = $incomingMail->receipt_signature_path;
            $currentSigPath = $data['receipt_signature_path'] ?? $incomingMail->receipt_signature_path;

            IncomingMailRevision::create([
                'incoming_mail_id' => $incomingMail->id,
                'user_id' => auth()->id(),
                'revision_number' => $newRevisionCount,
                'previous_status' => $incomingMail->status,
                'new_status' => $newStatus,
                'notes' => $request->input('revision_notes') ?? $request->input('notes') ?? 'Dokumen masuk ke status revisi R [' . $newRevisionCount . ']',
                'changed_fields' => $changedFields,
                'previous_file_path' => $previousFilePath,
                'file_path' => $currentFilePath,
                'previous_document_photo_path' => $previousPhotoPath,
                'document_photo_path' => $currentPhotoPath,
                'previous_signature_path' => $previousSigPath,
                'signature_path' => $currentSigPath,
            ]);
        }

        $incomingMail->update($data);

        // Sync OutgoingMail if status is RETURN, PROGRES, or REVISI
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

        $successMsg = 'Surat Masuk berhasil diperbarui.';
        if ($newStatus === 'REVISI') {
            $successMsg = 'Dokumen berhasil diubah ke status REVISI R [' . ($data['revision_count'] ?? 1) . '] dan dicatat dalam riwayat revisi.';
        } elseif ($wasDraft && ! $isDraft) {
            $successMsg = 'Draf Surat Masuk berhasil difinalisasi dan dicatat dalam buku agenda.';
        }

        return redirect()
            ->route('incoming-mails.index', $isDraft ? ['status_filter' => 'draft'] : [])
            ->with('success', $successMsg);
    }

    /**
     * Submit revised document and signature for incoming mail in REVISI status.
     * Automatically transitions the mail status back to RECEIVE.
     */
    public function submitRevision(\Illuminate\Http\Request $request, IncomingMail $incomingMail): RedirectResponse
    {
        Gate::authorize('update', $incomingMail);

        $request->validate([
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'signature_base64' => ['nullable', 'string'],
            'receipt_signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'revision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $currentCount = (int) ($incomingMail->revision_count ?? 0);
        $newRevisionCount = $currentCount + 1;

        $updates = [
            'status' => 'RECEIVE',
            'revision_count' => $newRevisionCount,
        ];

        $changedFields = [
            'status' => [
                'old' => $incomingMail->status,
                'new' => 'RECEIVE',
            ],
        ];

        // Process revised file upload
        if ($request->hasFile('file')) {
            $fileObj = $request->file('file');
            $mime = (string) $fileObj->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $updates['document_photo_path'] = $fileObj->store('incoming-mails/photos', 'local');
            } else {
                $updates['file_path'] = $fileObj->store('incoming-mails', 'local');
            }
            $changedFields['file_path'] = [
                'old' => (string) ($incomingMail->file_path ?? $incomingMail->document_photo_path ?? 'None'),
                'new' => (string) ($updates['file_path'] ?? $updates['document_photo_path']),
            ];
        }

        if ($request->hasFile('document_photo')) {
            $updates['document_photo_path'] = $request->file('document_photo')->store('incoming-mails/photos', 'local');
            $changedFields['document_photo_path'] = [
                'old' => (string) ($incomingMail->document_photo_path ?? 'None'),
                'new' => (string) $updates['document_photo_path'],
            ];
        }

        // Process signature
        if ($request->filled('signature_base64') && str_contains((string) $request->input('signature_base64'), 'data:image')) {
            $sigData = explode(',', (string) $request->input('signature_base64'))[1] ?? '';
            if (! empty($sigData)) {
                $decoded = base64_decode($sigData);
                $sigFileName = 'incoming-mails/signatures/' . Str::uuid() . '.png';
                Storage::disk('local')->put($sigFileName, $decoded);
                $updates['receipt_signature_path'] = $sigFileName;
                $changedFields['receipt_signature_path'] = [
                    'old' => (string) ($incomingMail->receipt_signature_path ?? 'None'),
                    'new' => (string) $sigFileName,
                ];
            }
        } elseif ($request->hasFile('receipt_signature_file')) {
            $updates['receipt_signature_path'] = $request->file('receipt_signature_file')->store('incoming-mails/signatures', 'local');
            $changedFields['receipt_signature_path'] = [
                'old' => (string) ($incomingMail->receipt_signature_path ?? 'None'),
                'new' => (string) $updates['receipt_signature_path'],
            ];
        }

        $previousFilePath = $incomingMail->file_path;
        $currentFilePath = $updates['file_path'] ?? $incomingMail->file_path;
        $previousPhotoPath = $incomingMail->document_photo_path;
        $currentPhotoPath = $updates['document_photo_path'] ?? $incomingMail->document_photo_path;
        $previousSigPath = $incomingMail->receipt_signature_path;
        $currentSigPath = $updates['receipt_signature_path'] ?? $incomingMail->receipt_signature_path;

        $incomingMail->update($updates);

        // Record revision history
        IncomingMailRevision::create([
            'incoming_mail_id' => $incomingMail->id,
            'user_id' => auth()->id(),
            'revision_number' => $newRevisionCount,
            'previous_status' => $incomingMail->getOriginal('status') ?? 'REVISI',
            'new_status' => 'RECEIVE',
            'notes' => $request->input('revision_notes') ?: ('Revisi R [' . $newRevisionCount . '] telah diserahkan dan ditandatangani.'),
            'changed_fields' => $changedFields,
            'previous_file_path' => $previousFilePath,
            'file_path' => $currentFilePath,
            'previous_document_photo_path' => $previousPhotoPath,
            'document_photo_path' => $currentPhotoPath,
            'previous_signature_path' => $previousSigPath,
            'signature_path' => $currentSigPath,
        ]);

        $this->syncToOutgoingMail($incomingMail);

        return redirect()
            ->back()
            ->with('success', 'Dokumen ' . $incomingMail->mail_number . ' berhasil direvisi R [' . $newRevisionCount . '] dan status otomatis berubah menjadi RECEIVE.');
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
            ->with('success', 'Surat Masuk berhasil dihapus (arsip soft delete).');
    }

    /**
     * Synchronize status with corresponding OutgoingMail if needed.
     */
    protected function syncToOutgoingMail(IncomingMail $incomingMail): void
    {
        $status = $incomingMail->status;

        if (in_array($status, ['RETURN', 'RETURNED'], true)) {
            $outgoingMail = OutgoingMail::where('subject', 'like', '%' . $incomingMail->subject)
                ->latest()
                ->first();

            if ($outgoingMail) {
                $outgoingMail->update([
                    'status' => 'RETURN',
                    'subject' => '[RETURN] ' . preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|REVISI)\]\s*/i', '', $incomingMail->subject),
                ]);
            } else {
                OutgoingMail::create([
                    'mail_number' => 'SK-' . now()->format('Ymd') . '-' . sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1),
                    'subject' => '[RETURN] ' . preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|REVISI)\]\s*/i', '', $incomingMail->subject),
                    'recipient' => $incomingMail->sender,
                    'file_path' => $incomingMail->file_path ?? $incomingMail->document_photo_path,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'RETURN',
                ]);
            }
        } elseif (in_array($status, ['PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING'], true)) {
            $outgoingMail = OutgoingMail::where('subject', 'like', '%' . $incomingMail->subject)
                ->latest()
                ->first();

            if ($outgoingMail) {
                $outgoingMail->update([
                    'status' => 'PROGRES',
                    'subject' => '[PROGRES] ' . preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|REVISI)\]\s*/i', '', $incomingMail->subject),
                ]);
            } else {
                OutgoingMail::create([
                    'mail_number' => 'SK-' . now()->format('Ymd') . '-' . sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1),
                    'subject' => '[PROGRES] ' . preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|REVISI)\]\s*/i', '', $incomingMail->subject),
                    'recipient' => $incomingMail->sender,
                    'file_path' => $incomingMail->file_path ?? $incomingMail->document_photo_path,
                    'created_by' => auth()->id() ?? 1,
                    'status' => 'PROGRES',
                ]);
            }
        } elseif (in_array($status, ['REVISI', 'REVISION'], true)) {
            $outgoingMail = OutgoingMail::where('subject', 'like', '%' . $incomingMail->subject)
                ->latest()
                ->first();

            if ($outgoingMail) {
                $outgoingMail->update([
                    'status' => 'REVISI',
                    'subject' => '[REVISI] ' . preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|REVISI)\]\s*/i', '', $incomingMail->subject),
                ]);
            }
        }
    }
}

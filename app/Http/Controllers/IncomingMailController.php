<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingMailRequest;
use App\Http\Requests\UpdateIncomingMailRequest;
use App\Jobs\ProcessOcrJob;
use App\Models\IncomingMail;
use App\Models\IncomingMailAttachment;
use App\Models\User;
use App\Services\IncomingMailStatusTransitionService;
use App\Support\IncomingMailStatus;
use App\Support\MailRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IncomingMailController extends Controller
{
    /** Jumlah baris yang dimuat per blok lazy load pada daftar surat masuk. */
    private const LAZY_LOAD_CHUNK = 20;

    /** Batas maksimal dokumen yang dimuat pada halaman lazy load. */
    public const LAZY_LOAD_MAX_ROWS = 200;

    public function __construct(
        private readonly IncomingMailStatusTransitionService $statusTransitionService
    ) {}

    public function updateBatch(Request $request, string $batchId)
    {
        $existingMails = IncomingMail::where('batch_id', $batchId)->get();
        if ($existingMails->isEmpty()) {
            abort(404);
        }

        Gate::authorize('update', $existingMails->first());

        if (is_array($request->input('documents'))) {
            $docsInput = $request->input('documents');
            foreach ($docsInput as $idx => $docItem) {
                if (is_array($docItem) && array_key_exists('recipient', $docItem)) {
                    $docsInput[$idx]['recipient'] = MailRecipient::format($docItem['recipient']);
                }
            }
            $request->merge(['documents' => $docsInput]);
        }

        $validated = $request->validate([
            'sender' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.id' => ['required', 'string'],
            'documents.*.mail_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($request, $batchId, $existingMails) {
                  if (blank($value)) {
                      return;
                  }
                  $batchReceiptNumber = $existingMails->first()?->receipt_number;
                  if ($value === $batchReceiptNumber) {
                      return;
                  }
                  preg_match('/documents\.(\d+)\.mail_number/', $attribute, $matches);
                  $index = $matches[1] ?? null;
                  $id = $index !== null ? $request->input("documents.{$index}.id") : null;
                  $query = \App\Models\IncomingMail::where('mail_number', $value)
                      ->whereNull('deleted_at')
                      ->where('batch_id', '!=', $batchId);
                  if ($id) { $query->where('id', '!=', $id); }
                  if ($query->exists()) { $fail('Nomor Surat pada dokumen ini sudah ada di sistem.'); }
              }],
            'documents.*.subject' => ['required', 'string', 'max:255'],
            'documents.*.recipient' => ['nullable', 'string', 'max:2000'],
            'documents.*.status' => ['nullable', 'string', 'max:255'],
            'documents.*.outgoing_date' => ['nullable', 'date'],
            'documents.*.disposition_note' => ['nullable', 'string'],
            'documents.*.notes' => ['nullable', 'string'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documents.*.document_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'documents.*.files' => ['nullable', 'array'],
            'documents.*.files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $globalProgress = $request->input('is_draft') == '1' ? 'DRAFT' : 'RECEIVE';
        $documents = $request->input('documents');
        
        $submittedIds = array_column($documents, 'id');
        $existingIds = $existingMails->pluck('id')->toArray();
        
        // Delete mails that are removed from batch
        foreach ($existingMails as $mail) {
            if (!in_array($mail->id, $submittedIds)) {
                $mail->delete();
            }
        }
        
        $receiptSignaturePath = $existingMails->first()->receipt_signature_path;
        $receiptNumber = $existingMails->first()->receipt_number;

        foreach ($documents as $index => $doc) {
            $mailData = [
                'batch_id' => $batchId,
                'receipt_number' => $receiptNumber,
                'sender' => $validated['sender'],
                'received_date' => $validated['received_date'],
                'recipient' => $doc['recipient'] ?? null,
                'progress' => $globalProgress,
                'status' => ! empty($doc['status']) ? trim((string) $doc['status']) : null,
                'mail_number' => ! empty($doc['mail_number']) ? $doc['mail_number'] : $receiptNumber,
                'subject' => $doc['subject'],
                'outgoing_date' => ! empty($doc['outgoing_date']) ? $doc['outgoing_date'] : null,
                'disposition_note' => $doc['disposition_note'] ?? null,
                'notes' => $doc['notes'] ?? null,
            ];

            $docFiles = $this->extractFiles($request, "documents.{$index}");

            if (in_array($doc['id'], $existingIds)) {
                $mailObj = IncomingMail::find($doc['id']);
                if ($mailObj) {
                    $mailObj->update($mailData);
                    $this->saveAttachments($mailObj, $docFiles);
                }
            } else {
                $mailData['receipt_signature_path'] = $receiptSignaturePath;
                $newMail = IncomingMail::create($mailData);
                $this->saveAttachments($newMail, $docFiles);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('incoming-mails.index')
            ]);
        }

        return redirect()->route('incoming-mails.index')->with('success', 'Batch surat masuk berhasil diperbarui.');
    }


    public function index(Request $request)
    {
        Gate::authorize('viewAny', IncomingMail::class);

        $query = IncomingMail::query();

        // Pencarian Cepat Global (mencakup semua kolom)
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mail_number', 'like', "%{$search}%")
                    ->orWhere('received_date', 'like', "%{$search}%")
                    ->orWhere('sender', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('progress', 'like', "%{$search}%")
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

        // 5. Progres (SOP Workflow)
        if ($request->filled('progress')) {
            $progress = IncomingMailStatus::canonical($request->input('progress'))
                ?? strtoupper(trim((string) $request->input('progress')));
            $query->where('progress', $progress);

            if ($progress === IncomingMailStatus::DRAFT) {
                $query->whereIn('id', function ($q) {
                    $q->selectRaw('MIN(id)')
                        ->from('incoming_mails')
                        ->where('progress', IncomingMailStatus::DRAFT)
                        ->groupBy('batch_id');
                });
                $query->with('batchItems');
            }
        } else {
            $query->where('progress', '!=', IncomingMailStatus::DRAFT);
        }

        // 5b. Status (Inputan Admin)
        if ($request->filled('status')) {
            $query->where('status', 'like', '%'.trim((string) $request->input('status')).'%');
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

        // Lazy load (infinite scroll): halaman penuh selalu dimulai dari blok pertama,
        // blok berikutnya diambil lewat AJAX dengan parameter `page`. `per_page` dipakai
        // saat memuat ulang tabel agar seluruh baris yang sudah tampil tetap dipertahankan.
        $isAjax = $request->ajax() || $request->wantsJson();
        $perPage = min(max($request->integer('per_page', self::LAZY_LOAD_CHUNK), 1), self::LAZY_LOAD_MAX_ROWS);

        $incomingMails = $query->latest()
            ->orderByDesc('id') // urutan stabil antar blok lazy load
            ->paginate($perPage, ['*'], 'page', $isAjax ? null : 1)
            ->withQueryString();

        // Return JSON untuk AJAX request
        if ($isAjax) {
            $progressMap = $incomingMails->mapWithKeys(fn (IncomingMail $mail) => [
                (string) $mail->id => IncomingMailStatus::canonical($mail->progress) ?? (string) $mail->progress,
            ]);

            $hasMore = $incomingMails->hasMorePages() && ($incomingMails->currentPage() * $perPage < self::LAZY_LOAD_MAX_ROWS);

            return response()->json([
                'html' => view('incoming-mails.partials.table-rows', compact('incomingMails'))->render(),
                'pagination' => view('incoming-mails.partials.pagination', compact('incomingMails'))->render(),
                'current_page' => $incomingMails->currentPage(),
                'has_more' => $hasMore,
                'total' => $incomingMails->total(),
                'max_limit' => self::LAZY_LOAD_MAX_ROWS,
                'allIds' => $incomingMails->pluck('id')->map(fn ($id) => (string) $id)->values(),
                'progresses' => $progressMap,
                'statuses' => $progressMap,
            ]);
        }

        return view('incoming-mails.index', compact('incomingMails'));
    }

    /**
     * Bulk update progress for selected incoming mails.
     */
    public function bulkUpdateStatus(Request $request)
    {
        if (! $request->filled('progress') && $request->filled('status')) {
            $request->merge(['progress' => $request->input('status')]);
        }

        // RETURN dan WAITING_RETURN hanya dapat dicapai melalui alur tanda tangan
        // pengembalian, yang sekaligus menampung catatan disposisi.
        $assignableStatuses = implode(',', IncomingMailStatus::manuallyAssignable());

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'exists:incoming_mails,id'],
            'progress' => ['required', 'string', 'in:'.$assignableStatuses.',RECEIVED'],
            'revision_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'ids.required' => 'Pilih minimal satu dokumen.',
            'progress.in' => 'Progres tujuan tidak tersedia. Progres RETURN hanya dapat dicapai melalui proses tanda tangan pengembalian.',
            'revision_note.max' => 'Catatan revisi maksimal 2000 karakter.',
        ]);

        $targetStatus = IncomingMailStatus::canonical($validated['progress']);
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
            ? "Progres berhasil diperbarui menjadi {$targetLabel} untuk {$updatedCount} dokumen."
            : 'Tidak ada dokumen yang dapat diubah progresnya.';

        if ($rejected !== []) {
            $message .= ' '.count($rejected).' dokumen dilewati karena alur progres tidak sesuai.';
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

        $senders = IncomingMail::whereNotNull('sender')->where('sender', '!=', '')->select('sender')->distinct()->pluck('sender');
        $recipients = $this->getDistinctRecipients();
        $statuses = $this->getDistinctStatuses();

        return view('incoming-mails.create', compact('nextSequenceNumber', 'senders', 'recipients', 'statuses'));
    }

    /**
     * Store newly created incoming mail(s) in storage (Support single or batch multi-document with locked sender and draft mode).
     */
    public function store(StoreIncomingMailRequest $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('create', IncomingMail::class);

        $validated = $request->validated();
        $globalProgress = $request->input('progress') ?: 'RECEIVE';
        if ($request->input('is_draft') == '1') {
            $globalProgress = 'DRAFT';
        }
        $globalStatus = ! empty($validated['status']) ? trim((string) $validated['status']) : null;

        // Shared Batch metadata & signature
        $batchId = (string) Str::uuid();
        $dateStr = now()->format('Ymd');
        $prefix = sprintf('TR-%s-', $dateStr);
        $highestSeq = IncomingMail::withTrashed()
            ->where(function ($q) use ($prefix) {
                $q->where('receipt_number', 'like', $prefix.'%')
                  ->orWhere('mail_number', 'like', $prefix.'%');
            })
            ->get(['receipt_number', 'mail_number'])
            ->reduce(function (int $max, $item) use ($prefix): int {
                foreach ([$item->receipt_number, $item->mail_number] as $val) {
                    if ($val !== null && preg_match('/^'.preg_quote($prefix, '/').'(\d+)/', (string) $val, $matches) === 1) {
                        $max = max($max, (int) $matches[1]);
                    }
                }
                return $max;
            }, 0);
        $seq = max($highestSeq + 1, 1);
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

            'receipt_signature_path' => $receiptSignaturePath,
            'progress' => $globalProgress,
            'status' => $globalStatus,
        ];

        $documents = $request->input('documents');

        if (is_array($documents) && count($documents) > 0) {
            foreach ($documents as $index => $doc) {
                $mailNumber = ! empty($doc['mail_number']) ? $doc['mail_number'] : $receiptNumber;
                $subject = ! empty($doc['subject']) ? $doc['subject'] : '(Tanpa Perihal)';
                $docProgress = ! empty($doc['progress']) ? $doc['progress'] : $globalProgress;
                if ($request->input('is_draft') == '1') {
                    $docProgress = 'DRAFT';
                }
                $docStatus = ! empty($doc['status']) ? trim((string) $doc['status']) : $globalStatus;
                $docRecipient = ! empty($doc['recipient']) ? $doc['recipient'] : ($validated['recipient'] ?? null);

                $incomingMail = IncomingMail::create(array_merge($commonAttributes, [
                    'mail_number' => $mailNumber,
                    'subject' => $subject,
                    'recipient' => $docRecipient,
                    'progress' => $docProgress,
                    'status' => $docStatus,
                    'outgoing_date' => ! empty($doc['outgoing_date']) ? $doc['outgoing_date'] : null,
                    'disposition_note' => ! empty($doc['disposition_note']) ? $doc['disposition_note'] : null,
                    'notes' => ! empty($doc['notes']) ? $doc['notes'] : null,
                ]));

                $docFiles = $this->extractFiles($request, "documents.{$index}");
                $this->saveAttachments($incomingMail, $docFiles);

                if ($incomingMail->file_path) {
                    ProcessOcrJob::dispatch($incomingMail);
                }
            }
        } else {
            // Single document mode
            $mailNumber = ! empty($validated['mail_number']) ? $validated['mail_number'] : $receiptNumber;
            $subject = ! empty($validated['subject']) ? $validated['subject'] : '(Tanpa Perihal)';

            $incomingMail = IncomingMail::create(array_merge($commonAttributes, [
                'mail_number' => $mailNumber,
                'subject' => $subject,
                'outgoing_date' => ! empty($validated['outgoing_date']) ? $validated['outgoing_date'] : null,
                'disposition_note' => $validated['disposition_note'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]));

            $singleFiles = [];
            if ($request->hasFile('files')) {
                $f = $request->file('files');
                if (is_array($f)) {
                    $singleFiles = array_merge($singleFiles, $f);
                } elseif ($f instanceof \Illuminate\Http\UploadedFile) {
                    $singleFiles[] = $f;
                }
            }
            if ($request->hasFile('file')) {
                $singleFiles[] = $request->file('file');
            }
            if ($request->hasFile('document_photo')) {
                $singleFiles[] = $request->file('document_photo');
            }

            $this->saveAttachments($incomingMail, $singleFiles);

            if ($incomingMail->file_path) {
                ProcessOcrJob::dispatch($incomingMail);
            }
        }

        if ($request->expectsJson()) {
            session()->flash('success', 'Tanda Terima kolektif & Surat Masuk berhasil dicatat.');
            return response()->json(['redirect' => route('incoming-mails.index')]);
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
            'dispositionRecipients.creator',
            'revisionNotes.creator',
            'attachments',
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

        $incomingMail->load(['batchItems', 'attachments']);

        $senders = IncomingMail::whereNotNull('sender')->where('sender', '!=', '')->select('sender')->distinct()->pluck('sender');
        $recipients = $this->getDistinctRecipients();
        $statuses = $this->getDistinctStatuses();

        if (IncomingMailStatus::canonical($incomingMail->progress) === IncomingMailStatus::DRAFT) {
            return view('incoming-mails.edit-batch', compact('incomingMail', 'senders', 'recipients', 'statuses'));
        }

        return view('incoming-mails.edit', compact('incomingMail', 'senders', 'recipients', 'statuses'));
    }

    /**
     * Update the specified incoming mail in storage.
     */
    public function update(UpdateIncomingMailRequest $request, IncomingMail $incomingMail): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('update', $incomingMail);

        $data = $request->validated();

        // Progress changes follow the standard operating procedure, never a plain field update.
        $requestedProgress = IncomingMailStatus::canonical($data['progress'] ?? null);
        $currentProgress = IncomingMailStatus::canonical($incomingMail->progress);
        unset($data['progress']);

        if (array_key_exists('status', $data)) {
            $data['status'] = ! empty($data['status']) ? trim((string) $data['status']) : null;
        }

        if (array_key_exists('mail_number', $data)) {
            $data['mail_number'] = ! empty($data['mail_number'])
                ? trim((string) $data['mail_number'])
                : ($incomingMail->receipt_number ?: $incomingMail->mail_number);
        }

        $isContinuingRevisi = $requestedProgress === IncomingMailStatus::REVISI
            && $currentProgress === IncomingMailStatus::REVISI
            && filled($request->input('revision_note'));

        $progressChange = ($requestedProgress !== null && ($requestedProgress !== $currentProgress || $isContinuingRevisi))
            ? $requestedProgress
            : null;

        if ($progressChange !== null) {
            $reason = $this->statusTransitionService->rejectionReason($incomingMail, $progressChange);

            if ($reason !== null) {
                throw ValidationException::withMessages(['progress' => $reason]);
            }
        }

        $updateFiles = [];
        if ($request->hasFile('files')) {
            $f = $request->file('files');
            if (is_array($f)) {
                $updateFiles = array_merge($updateFiles, $f);
            } elseif ($f instanceof \Illuminate\Http\UploadedFile) {
                $updateFiles[] = $f;
            }
        }
        if ($request->hasFile('file')) {
            $updateFiles[] = $request->file('file');
        }
        if ($request->hasFile('document_photo')) {
            $updateFiles[] = $request->file('document_photo');
        }

        if (! empty($updateFiles)) {
            $this->saveAttachments($incomingMail, $updateFiles);
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

        if ($progressChange !== null) {
            $this->statusTransitionService->apply(
                $incomingMail,
                $progressChange,
                auth()->id(),
                $request->input('revision_note')
            );
        }

        // If batch_id exists, sync common fields to other batch items if needed
        if ($incomingMail->batch_id) {
            $sharedUpdates = array_filter([
                'sender' => $data['sender'] ?? null,
                'received_date' => $data['received_date'] ?? null,
                'recipient' => $data['recipient'] ?? null,

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

        if ($request->expectsJson()) {
            session()->flash('success', 'Surat Masuk berhasil diperbarui.');
            return response()->json(['redirect' => back()->getTargetUrl()]);
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
     * Get distinct individual recipients across IncomingMail and OutgoingMail.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function getDistinctRecipients(): \Illuminate\Support\Collection
    {
        $raw = IncomingMail::whereNotNull('recipient')->where('recipient', '!=', '')->distinct()->pluck('recipient')
            ->merge(\App\Models\OutgoingMail::whereNotNull('recipient')->where('recipient', '!=', '')->distinct()->pluck('recipient'));

        return collect(MailRecipient::extractDistinct($raw));
    }

    /**
     * Get distinct admin-inputted statuses across IncomingMail and OutgoingMail.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function getDistinctStatuses(): \Illuminate\Support\Collection
    {
        $raw = IncomingMail::whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status')
            ->merge(\App\Models\OutgoingMail::whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status'));

        return collect(\App\Support\MailCustomStatus::extractDistinct($raw));
    }

    /**
     * Extract all uploaded files for a given form key prefix (supporting multiple files array, single file, and photo).
     *
     * @return array<\Illuminate\Http\UploadedFile>
     */
    private function extractFiles(Request $request, string $prefix): array
    {
        $files = [];

        // Array of files (documents.0.files)
        if ($request->hasFile("{$prefix}.files")) {
            $uploaded = $request->file("{$prefix}.files");
            if (is_array($uploaded)) {
                $files = array_merge($files, $uploaded);
            } elseif ($uploaded instanceof \Illuminate\Http\UploadedFile) {
                $files[] = $uploaded;
            }
        }

        // Single file (documents.0.file)
        if ($request->hasFile("{$prefix}.file")) {
            $uploaded = $request->file("{$prefix}.file");
            if (is_array($uploaded)) {
                $files = array_merge($files, $uploaded);
            } elseif ($uploaded instanceof \Illuminate\Http\UploadedFile) {
                $files[] = $uploaded;
            }
        }

        // Single document photo (documents.0.document_photo)
        if ($request->hasFile("{$prefix}.document_photo")) {
            $uploaded = $request->file("{$prefix}.document_photo");
            if (is_array($uploaded)) {
                $files = array_merge($files, $uploaded);
            } elseif ($uploaded instanceof \Illuminate\Http\UploadedFile) {
                $files[] = $uploaded;
            }
        }

        return array_values(array_filter($files, fn ($f) => $f instanceof \Illuminate\Http\UploadedFile && $f->isValid()));
    }

    /**
     * Store attachments for an incoming mail and set primary paths for backward compatibility.
     *
     * @param  array<\Illuminate\Http\UploadedFile>  $uploadedFiles
     */
    private function saveAttachments(IncomingMail $mail, array $uploadedFiles): void
    {
        if (empty($uploadedFiles)) {
            return;
        }

        $primaryFilePath = $mail->file_path;
        $primaryPhotoPath = $mail->document_photo_path;

        foreach ($uploadedFiles as $fileObj) {
            $mime = (string) $fileObj->getMimeType();
            $isImage = str_starts_with($mime, 'image/');
            $folder = $isImage ? 'incoming-mails/photos' : 'incoming-mails';
            $storedPath = $fileObj->store($folder, 'local');

            if ($isImage && ! $primaryPhotoPath) {
                $primaryPhotoPath = $storedPath;
            }
            if (! $isImage && ! $primaryFilePath) {
                $primaryFilePath = $storedPath;
            }

            $mail->attachments()->create([
                'file_path' => $storedPath,
                'file_name' => $fileObj->getClientOriginalName(),
                'file_size' => $fileObj->getSize(),
                'mime_type' => $mime,
            ]);
        }

        if (! $primaryFilePath && $primaryPhotoPath) {
            $primaryFilePath = $primaryPhotoPath;
        }

        if ($primaryFilePath !== $mail->file_path || $primaryPhotoPath !== $mail->document_photo_path) {
            $mail->update([
                'file_path' => $primaryFilePath,
                'document_photo_path' => $primaryPhotoPath,
            ]);
        }
    }

    /**
     * Get attachments for an incoming mail.
     */
    public function getAttachments(IncomingMail $incomingMail): JsonResponse
    {
        Gate::authorize('view', $incomingMail);

        $incomingMail->load('attachments');

        $existingFilePaths = $incomingMail->attachments->pluck('file_path')->all();
        $legacyAttachments = collect();

        if ($incomingMail->document_photo_path && ! in_array($incomingMail->document_photo_path, $existingFilePaths, true)) {
            $legacyAttachments->push([
                'id' => 'legacy-photo',
                'file_name' => basename($incomingMail->document_photo_path),
                'file_size' => Storage::disk('local')->exists($incomingMail->document_photo_path) ? Storage::disk('local')->size($incomingMail->document_photo_path) : null,
                'mime_type' => 'image/jpeg',
                'is_image' => true,
                'is_legacy' => true,
                'preview_url' => route('document.download', ['path' => $incomingMail->document_photo_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $incomingMail->document_photo_path]),
                'created_at' => $incomingMail->created_at?->format('d/m/Y H:i'),
            ]);
        }

        if ($incomingMail->file_path && ! in_array($incomingMail->file_path, $existingFilePaths, true) && $incomingMail->file_path !== $incomingMail->document_photo_path) {
            $legacyAttachments->push([
                'id' => 'legacy-file',
                'file_name' => basename($incomingMail->file_path),
                'file_size' => Storage::disk('local')->exists($incomingMail->file_path) ? Storage::disk('local')->size($incomingMail->file_path) : null,
                'mime_type' => 'application/pdf',
                'is_image' => false,
                'is_legacy' => true,
                'preview_url' => route('document.download', ['path' => $incomingMail->file_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $incomingMail->file_path]),
                'created_at' => $incomingMail->created_at?->format('d/m/Y H:i'),
            ]);
        }

        $attachments = $incomingMail->attachments->map(function (IncomingMailAttachment $att) {
            $isImg = str_starts_with((string) $att->mime_type, 'image/');

            return [
                'id' => (string) $att->id,
                'file_name' => $att->file_name ?? basename($att->file_path),
                'file_size' => $att->file_size,
                'mime_type' => $att->mime_type,
                'is_image' => $isImg,
                'is_legacy' => false,
                'preview_url' => route('document.download', ['path' => $att->file_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $att->file_path]),
                'created_at' => $att->created_at?->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'mail' => [
                'id' => (string) $incomingMail->id,
                'mail_number' => $incomingMail->mail_number,
                'subject' => $incomingMail->subject,
                'sender' => $incomingMail->sender,
            ],
            'attachments' => $legacyAttachments->merge($attachments)->values(),
        ]);
    }

    /**
     * Store new attachments (photos or documents) for an incoming mail.
     */
    public function storeAttachments(Request $request, IncomingMail $incomingMail): JsonResponse
    {
        Gate::authorize('update', $incomingMail);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'files.required' => 'Pilih minimal satu foto atau dokumen.',
            'files.array' => 'Format berkas tidak valid.',
            'files.*.file' => 'Berkas harus berupa file yang valid.',
            'files.*.mimes' => 'Format file harus berupa PDF, JPG, JPEG, PNG, atau WEBP.',
            'files.*.max' => 'Ukuran file tidak boleh melebihi 10MB.',
        ]);

        $this->saveAttachments($incomingMail, $validated['files']);

        return response()->json([
            'success' => true,
            'message' => count($validated['files']) . ' foto/dokumen berhasil ditambahkan.',
            'total_attachments' => $incomingMail->attachments()->count(),
        ]);
    }

    /**
     * Delete an attachment from an incoming mail.
     */
    public function destroyAttachment(IncomingMail $incomingMail, IncomingMailAttachment $attachment): JsonResponse
    {
        Gate::authorize('update', $incomingMail);

        if ($attachment->incoming_mail_id !== $incomingMail->id) {
            abort(404, 'Lampiran tidak ditemukan untuk surat masuk ini.');
        }

        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $deletedFilePath = $attachment->file_path;
        $attachment->delete();

        $updates = [];
        if ($incomingMail->file_path === $deletedFilePath) {
            $next = $incomingMail->attachments()->where('file_path', '!=', $deletedFilePath)->first();
            $updates['file_path'] = $next?->file_path;
        }
        if ($incomingMail->document_photo_path === $deletedFilePath) {
            $nextPhoto = $incomingMail->attachments()->where('file_path', '!=', $deletedFilePath)->where('mime_type', 'like', 'image/%')->first();
            $updates['document_photo_path'] = $nextPhoto?->file_path;
        }
        if (! empty($updates)) {
            $incomingMail->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berkas berhasil dihapus.',
            'total_attachments' => $incomingMail->attachments()->count(),
        ]);
    }
}





<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutgoingMailRequest;
use App\Http\Requests\UpdateOutgoingMailRequest;
use App\Jobs\ProcessDigitalSignatureJob;
use App\Models\OutgoingMail;
use App\Models\OutgoingMailFileHistory;
use App\Services\OutgoingMailStatusTransitionService;
use App\Support\OutgoingMailStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OutgoingMailController extends Controller
{
    public function __construct(
        private readonly OutgoingMailStatusTransitionService $statusTransitionService
    ) {}

    /**
     * Display a listing of outgoing mails with server-side pagination.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', OutgoingMail::class);

        $query = OutgoingMail::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mail_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $outgoingMails = $query->with('creator')->latest()->paginate(15)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('outgoing-mails.partials.table-rows', compact('outgoingMails'))->render(),
                'pagination' => view('outgoing-mails.partials.pagination', compact('outgoingMails'))->render(),
                'total' => $outgoingMails->total(),
                'allIds' => $outgoingMails->pluck('id')->map(fn ($id) => (string) $id)->values(),
                'statuses' => $outgoingMails->mapWithKeys(fn (OutgoingMail $mail) => [
                    (string) $mail->id => match ($mail->status) {
                        'RECEIVED' => 'RECEIVE',
                        'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                        'RETURNED' => 'RETURN',
                        'WAITING_RETURN' => 'WAITING RETURN',
                        default => $mail->status,
                    },
                ]),
            ]);
        }

        return view('outgoing-mails.index', compact('outgoingMails'));
    }

    /**
     * Bulk update status for selected outgoing mails.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'exists:outgoing_mails,id'],
            'status' => ['required', 'string'],
            'revision_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'ids.required' => 'Pilih minimal satu dokumen.',
            'revision_note.max' => 'Catatan revisi maksimal 2000 karakter.',
        ]);

        $targetStatus = match ($validated['status']) {
            'RECEIVE' => 'RECEIVED',
            'PROGRES' => 'PROGRESS',
            'RETURN' => 'RETURNED',
            'WAITING RETURN' => 'WAITING_RETURN',
            default => $validated['status'],
        };
        
        $mails = OutgoingMail::whereIn('id', $validated['ids'])->get();
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
                $this->statusTransitionService->apply(
                    $mail, 
                    $targetStatus, 
                    auth()->id(),
                    $validated['revision_note'] ?? null
                );
                $updatedCount++;
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $rejected[] = [
                    'mail_number' => $mail->mail_number,
                    'reason' => collect($exception->errors())->flatten()->first(),
                ];
            }
        }

        $message = $updatedCount > 0
            ? "Status berhasil diperbarui menjadi {$targetStatus} untuk {$updatedCount} dokumen."
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
            ->route('outgoing-mails.index')
            ->with($updatedCount > 0 ? 'success' : 'error', $message);
    }

    /**
     * Show the form for creating a new outgoing mail.
     */
    public function create(): View
    {
        Gate::authorize('create', OutgoingMail::class);

        return view('outgoing-mails.create');
    }

    /**
     * Store a newly created outgoing mail in storage.
     */
    public function store(StoreOutgoingMailRequest $request): RedirectResponse
    {
        Gate::authorize('create', OutgoingMail::class);

        $validated = $request->validated();
        $documents = $validated['documents'] ?? [];
        $createdCount = 0;

        foreach ($documents as $index => $doc) {
            $status = 'WAITING_RETURN';

            $fileKey = "documents.{$index}.file";
            $filePath = $request->hasFile($fileKey)
                ? $request->file($fileKey)->store('outgoing-mails', 'local')
                : null;

            $mailNumber = ! empty($doc['mail_number'])
                ? $doc['mail_number']
                : 'SK-'.now()->format('Ymd').'-'.sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1);

            OutgoingMail::create([
                'mail_number' => $mailNumber,
                'subject' => $doc['subject'],
                'recipient' => $doc['recipient'],
                'file_path' => $filePath,
                'created_by' => auth()->id(),
                'status' => $status,
                'disposition_note' => $doc['disposition_note'] ?? null,
            ]);

            $createdCount++;
        }

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', "{$createdCount} Surat Keluar berhasil dicatat (Status: WAITING RETURN).");
    }

    /**
     * Display the specified outgoing mail.
     */
    public function show(OutgoingMail $outgoingMail): View
    {
        Gate::authorize('view', $outgoingMail);

        $outgoingMail->load(['creator', 'fileHistories.uploader']);

        return view('outgoing-mails.show', compact('outgoingMail'));
    }

    /**
     * Issue PSrE Digital Signature for the outgoing mail.
     */
    public function sign(OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('sign', $outgoingMail);

        $this->statusTransitionService->apply($outgoingMail, 'APPROVED', auth()->id());

        ProcessDigitalSignatureJob::dispatch($outgoingMail, auth()->user());

        return redirect()
            ->route('outgoing-mails.show', $outgoingMail)
            ->with('success', 'Tanda tangan digital (PSrE) berhasil dipicu.');
    }

    /**
     * Show the form for editing the specified outgoing mail.
     */
    public function edit(OutgoingMail $outgoingMail): View
    {
        Gate::authorize('update', $outgoingMail);

        $outgoingMail->load('fileHistories.uploader');

        return view('outgoing-mails.edit', compact('outgoingMail'));
    }

    /**
     * Update the specified outgoing mail in storage.
     */
    public function update(UpdateOutgoingMailRequest $request, OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('update', $outgoingMail);

        $data = $request->validated();

        $requestedStatus = OutgoingMailStatus::canonical($data['status'] ?? null);
        $currentStatus = OutgoingMailStatus::canonical($outgoingMail->status);
        unset($data['status']);

        $statusChange = $requestedStatus !== null && $requestedStatus !== $currentStatus
            ? $requestedStatus
            : null;

        if ($statusChange !== null) {
            $reason = $this->statusTransitionService->rejectionReason($outgoingMail, $statusChange);

            if ($reason !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status' => $reason]);
            }
        }

        if ($request->hasFile('file')) {
            if (! empty($outgoingMail->file_path)) {
                OutgoingMailFileHistory::create([
                    'outgoing_mail_id' => $outgoingMail->id,
                    'file_path' => $outgoingMail->file_path,
                    'file_name' => basename($outgoingMail->file_path),
                    'uploaded_by' => auth()->id(),
                ]);
            }

            $data['file_path'] = $request->file('file')->store('outgoing-mails', 'local');
        }

        if (empty($data['mail_number']) && empty($outgoingMail->mail_number)) {
            $data['mail_number'] = 'SK-'.now()->format('Ymd').'-'.sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1);
        }

        $outgoingMail->update($data);

        if ($statusChange !== null) {
            $this->statusTransitionService->apply(
                $outgoingMail,
                $statusChange,
                auth()->id(),
                $data['revision_note'] ?? null
            );
        }

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', 'Surat Keluar berhasil diperbarui.');
    }

    /**
     * Remove the specified outgoing mail from storage (Soft Delete).
     */
    public function destroy(OutgoingMail $outgoingMail): RedirectResponse
    {
        Gate::authorize('delete', $outgoingMail);

        $outgoingMail->delete();

        return redirect()
            ->route('outgoing-mails.index')
            ->with('success', 'Surat Keluar berhasil dihapus.');
    }
}

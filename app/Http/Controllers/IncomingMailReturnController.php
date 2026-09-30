<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveIncomingMailReturnRequest;
use App\Models\IncomingMail;
use App\Models\IncomingMailReturnRecipient;
use App\Services\IncomingMailStatusSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class IncomingMailReturnController extends Controller
{
    public function __construct(
        private readonly IncomingMailStatusSyncService $statusSyncService
    ) {}

    public function show(IncomingMail $incomingMail): JsonResponse
    {
        Gate::authorize('update', $incomingMail);

        $incomingMail->load(['returnRecipients' => fn ($query) => $query->orderBy('sort_order')]);

        return response()->json([
            'mail' => [
                'id' => $incomingMail->id,
                'mail_number' => $incomingMail->mail_number,
                'status' => $incomingMail->status,
            ],
            'recipients' => $incomingMail->returnRecipients->map(fn (IncomingMailReturnRecipient $recipient) => [
                'id' => $recipient->id,
                'recipient_name' => $recipient->recipient_name,
                'signer_name' => $recipient->signer_name,
                'has_signature' => filled($recipient->signature_path),
                'signature_url' => $recipient->signature_path
                    ? route('document.download', ['path' => $recipient->signature_path, 'inline' => 1])
                    : null,
                'signed_at' => $recipient->signed_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(SaveIncomingMailReturnRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $mailIds = $validated['mail_ids'];
        $action = $validated['action'];
        $recipientInputs = $validated['recipients'];
        $actorId = $request->user()?->id;

        $mails = IncomingMail::whereIn('id', $mailIds)->get();
        if ($mails->count() !== count($mailIds)) {
            throw ValidationException::withMessages([
                'mail_ids' => 'Satu atau lebih dokumen tidak ditemukan.',
            ]);
        }

        foreach ($mails as $mail) {
            Gate::authorize('update', $mail);

            if (in_array($mail->status, ['DRAFT', 'COMPLETED', 'RETURN', 'RETURNED'], true)) {
                throw ValidationException::withMessages([
                    'mail_ids' => "Dokumen {$mail->mail_number} tidak dapat diproses untuk pengembalian.",
                ]);
            }

            if (count($mailIds) > 1 && $mail->status === 'WAITING_RETURN') {
                throw ValidationException::withMessages([
                    'mail_ids' => "Dokumen {$mail->mail_number} sedang menunggu pengembalian dan harus dilanjutkan secara terpisah.",
                ]);
            }
        }

        $newSignaturePaths = [];
        $obsoleteSignaturePaths = [];

        try {
            DB::transaction(function () use (
                $mailIds,
                $recipientInputs,
                $action,
                $actorId,
                &$newSignaturePaths,
                &$obsoleteSignaturePaths
            ): void {
                $lockedMails = IncomingMail::whereIn('id', $mailIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($lockedMails as $mail) {
                    $existingRecipients = $mail->returnRecipients()->get()->keyBy('id');
                    $keptRecipientIds = [];

                    foreach ($recipientInputs as $index => $input) {
                        $existing = isset($input['id'])
                            ? $existingRecipients->get($input['id'])
                            : null;
                        $signaturePath = $existing?->signature_path;
                        $signerName = trim((string) ($input['signer_name'] ?? '')) ?: null;
                        $hasNewSignature = filled($input['signature'] ?? null);
                        $removeSignature = (bool) ($input['remove_signature'] ?? false);

                        if ($existing && $existing->signer_name !== $signerName && ! $hasNewSignature) {
                            $removeSignature = true;
                        }

                        if ($removeSignature && $signaturePath) {
                            $obsoleteSignaturePaths[] = $signaturePath;
                            $signaturePath = null;
                        }

                        if ($hasNewSignature) {
                            if ($signaturePath) {
                                $obsoleteSignaturePaths[] = $signaturePath;
                            }

                            $signaturePath = $this->storeSignature((string) $input['signature'], $index);
                            $newSignaturePaths[] = $signaturePath;
                        }

                        if ($action === 'complete' && ($signerName === null || $signaturePath === null)) {
                            throw ValidationException::withMessages([
                                "recipients.{$index}.signer_name" => 'Nama penandatangan dan tanda tangan wajib dilengkapi sebelum menyelesaikan pengembalian.',
                            ]);
                        }

                        $recipient = $existing ?? new IncomingMailReturnRecipient([
                            'incoming_mail_id' => $mail->id,
                            'created_by' => $actorId,
                        ]);

                        $recipient->fill([
                            'recipient_name' => trim((string) $input['recipient_name']),
                            'signer_name' => $signerName,
                            'signature_path' => $signaturePath,
                            'signed_at' => $signerName !== null && $signaturePath !== null
                                ? ($existing?->signed_at ?? now())
                                : null,
                            'sort_order' => $index,
                        ]);
                        $recipient->save();
                        $keptRecipientIds[] = $recipient->id;
                    }

                    $recipientsToDelete = $existingRecipients->except($keptRecipientIds);
                    foreach ($recipientsToDelete as $recipientToDelete) {
                        if ($recipientToDelete->signature_path) {
                            $obsoleteSignaturePaths[] = $recipientToDelete->signature_path;
                        }
                        $recipientToDelete->delete();
                    }

                    $mail->update([
                        'status' => $action === 'complete' ? 'RETURN' : 'WAITING_RETURN',
                    ]);

                    if ($action === 'complete') {
                        $this->statusSyncService->sync($mail, $actorId);
                    }
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newSignaturePaths);
            throw $exception;
        }

        Storage::disk('local')->delete(array_values(array_unique($obsoleteSignaturePaths)));

        $isCompleted = $action === 'complete';

        return response()->json([
            'success' => true,
            'status' => $isCompleted ? 'RETURN' : 'WAITING_RETURN',
            'message' => $isCompleted
                ? 'Pengembalian selesai. Semua tanda tangan telah disimpan.'
                : 'Progres disimpan. Pengembalian dapat dilanjutkan melalui status WAITING RETURN.',
            'updated_ids' => $mailIds,
        ]);
    }

    private function storeSignature(string $dataUrl, int $index): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                "recipients.{$index}.signature" => 'Format tanda tangan tidak valid.',
            ]);
        }

        $decoded = base64_decode($matches[1], true);
        $imageInfo = $decoded !== false ? @getimagesizefromstring($decoded) : false;

        if ($decoded === false || strlen($decoded) > 2 * 1024 * 1024 || ($imageInfo['mime'] ?? null) !== 'image/png') {
            throw ValidationException::withMessages([
                "recipients.{$index}.signature" => 'Tanda tangan harus berupa gambar PNG dengan ukuran maksimal 2 MB.',
            ]);
        }

        $path = 'incoming-mails/return-signatures/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $decoded);

        return $path;
    }
}

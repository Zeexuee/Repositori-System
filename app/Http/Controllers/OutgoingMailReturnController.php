<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveOutgoingMailReturnRequest;
use App\Models\OutgoingMail;
use App\Models\OutgoingMailReturnRecipient;
use App\Support\OutgoingMailStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OutgoingMailReturnController extends Controller
{
    public function show(OutgoingMail $outgoingMail): JsonResponse
    {
        Gate::authorize('view', $outgoingMail);

        $outgoingMail->load(['returnRecipients' => fn ($query) => $query->orderBy('sort_order')]);

        return response()->json([
            'mail' => [
                'id' => $outgoingMail->id,
                'mail_number' => $outgoingMail->mail_number,
                'status' => $outgoingMail->status,
                'disposition_note' => $outgoingMail->disposition_note,
            ],
            'recipients' => $outgoingMail->returnRecipients->map(fn (OutgoingMailReturnRecipient $recipient) => [
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

    public function store(SaveOutgoingMailReturnRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $mailIds = $validated['mail_ids'];
        $action = $validated['action'];
        $recipientInputs = $validated['recipients'];
        $actorId = $request->user()?->id;

        // Catatan disposisi opsional. Kunci yang dikirim dengan nilai kosong berarti
        // operator sengaja mengosongkan catatan, sedangkan kunci yang tidak dikirim
        // sama sekali membiarkan catatan lama apa adanya.
        $hasDispositionNote = array_key_exists('disposition_note', $validated);
        $dispositionNote = filled($validated['disposition_note'] ?? null)
            ? trim((string) $validated['disposition_note'])
            : null;

        $mails = OutgoingMail::whereIn('id', $mailIds)->get();
        if ($mails->count() !== count($mailIds)) {
            throw ValidationException::withMessages([
                'mail_ids' => 'Satu atau lebih dokumen tidak ditemukan.',
            ]);
        }

        foreach ($mails as $mail) {
            Gate::authorize('update', $mail);

            if (OutgoingMailStatus::canonical($mail->status) === OutgoingMailStatus::RECEIVE) {
                abort(403, 'Dokumen sudah berstatus RECEIVE dan tidak dapat diproses penerimaan ulang.');
            }

            if (! OutgoingMailStatus::canProcessReceive($mail->status)) {
                $label = OutgoingMailStatus::label($mail->status);

                throw ValidationException::withMessages([
                    'mail_ids' => "Dokumen {$mail->mail_number} berstatus {$label} sehingga tidak dapat diproses untuk penerimaan.",
                ]);
            }

            if (count($mailIds) > 1 && OutgoingMailStatus::canonical($mail->status) === OutgoingMailStatus::WAITING_RECEIVE) {
                throw ValidationException::withMessages([
                    'mail_ids' => "Dokumen {$mail->mail_number} sedang menunggu penerimaan dan harus dilanjutkan secara terpisah.",
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
                $hasDispositionNote,
                $dispositionNote,
                &$newSignaturePaths,
                &$obsoleteSignaturePaths
            ): void {
                $lockedMails = OutgoingMail::whereIn('id', $mailIds)
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
                                "recipients.{$index}.signer_name" => 'Nama penandatangan dan tanda tangan wajib dilengkapi sebelum menyelesaikan penerimaan. Gunakan "Lewati proses tanda tangan yang tersisa" bila sisanya tidak akan ditandatangani.',
                            ]);
                        }

                        $recipient = $existing ?? new OutgoingMailReturnRecipient([
                            'outgoing_mail_id' => $mail->id,
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

                    $isFinalizing = in_array($action, ['complete', 'skip_remaining'], true);

                    $attributes = [
                        'status' => $isFinalizing
                            ? OutgoingMailStatus::RECEIVE
                            : OutgoingMailStatus::WAITING_RECEIVE,
                    ];

                    if ($hasDispositionNote) {
                        $attributes['disposition_note'] = $dispositionNote;
                    }

                    $mail->update($attributes);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newSignaturePaths);
            throw $exception;
        }

        Storage::disk('local')->delete(array_values(array_unique($obsoleteSignaturePaths)));

        $isFinalized = in_array($action, ['complete', 'skip_remaining'], true);

        return response()->json([
            'success' => true,
            'status' => $isFinalized ? OutgoingMailStatus::RECEIVE : OutgoingMailStatus::WAITING_RECEIVE,
            'message' => match ($action) {
                'complete' => 'Penerimaan selesai. Semua tanda tangan telah disimpan.',
                'skip_remaining' => 'Penerimaan ditutup. Tanda tangan yang tersisa dilewati dan tercatat sebagai belum ditandatangani.',
                default => 'Progres disimpan. Penerimaan dapat dilanjutkan melalui status WAITING RECEIVE.',
            },
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

        $path = 'outgoing-mails/return-signatures/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $decoded);

        return $path;
    }
}

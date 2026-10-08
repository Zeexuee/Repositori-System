<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutgoingMail;
use App\Models\OutgoingMailRevisionNote;
use App\Support\OutgoingMailStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutgoingMailStatusTransitionService
{
    public function __construct() {}

    /**
     * Apply a status transition, enforcing the standard operating procedure.
     *
     * @param  string|null  $revisionNote  Required when entering REVISI, optional when leaving it.
     */
    public function apply(
        OutgoingMail $outgoingMail,
        string $targetStatus,
        ?int $actorId,
        ?string $revisionNote = null
    ): void {
        $target = OutgoingMailStatus::canonical($targetStatus);

        if ($target === null) {
            throw ValidationException::withMessages([
                'status' => 'Status tujuan tidak dikenali.',
            ]);
        }

        if ($target === OutgoingMailStatus::RECEIVE) {
            throw ValidationException::withMessages([
                'status' => 'Status penerimaan hanya dapat diubah melalui proses tanda tangan penerimaan.',
            ]);
        }

        DB::transaction(function () use ($outgoingMail, $target, $actorId, $revisionNote): void {
            $mail = OutgoingMail::whereKey($outgoingMail->getKey())->lockForUpdate()->firstOrFail();
            $current = OutgoingMailStatus::canonical($mail->status);

            $this->guardTransition($mail, $current, $target);

            if ($target === OutgoingMailStatus::REVISI && blank($revisionNote)) {
                throw ValidationException::withMessages([
                    'revision_note' => 'Catatan kebutuhan revisi wajib diisi.',
                ]);
            }

            $attributes = ['status' => $target];

            $mail->update($attributes);

            if ($target === OutgoingMailStatus::REVISI) {
                $this->recordNote($mail, OutgoingMailRevisionNote::TYPE_REQUESTED, $revisionNote, $actorId);
            } elseif ($current === OutgoingMailStatus::REVISI && filled($revisionNote)) {
                $this->recordNote($mail, OutgoingMailRevisionNote::TYPE_RESOLVED, $revisionNote, $actorId);
            }

            $outgoingMail->setAttribute('status', $target);
        });
    }

    /**
     * Human readable reason why a transition is rejected, or null when allowed.
     */
    public function rejectionReason(OutgoingMail $outgoingMail, string $targetStatus): ?string
    {
        $current = OutgoingMailStatus::canonical($outgoingMail->status);
        $target = OutgoingMailStatus::canonical($targetStatus);

        if ($target === null) {
            return 'Status tujuan tidak dikenali.';
        }

        if ($current === $target) {
            return 'Dokumen sudah berada pada status tersebut.';
        }

        if (OutgoingMailStatus::canTransition($current, $target)) {
            return null;
        }

        $allowed = OutgoingMailStatus::allowedTransitions($current);
        $currentLabel = OutgoingMailStatus::label($outgoingMail->status);
        $targetLabel = OutgoingMailStatus::label($targetStatus);

        if ($allowed === []) {
            return "Status {$currentLabel} adalah status akhir dan tidak dapat diubah lagi.";
        }

        $allowedLabels = implode(', ', array_map(
            static fn (string $status): string => OutgoingMailStatus::label($status),
            $allowed
        ));

        return "Status {$currentLabel} tidak dapat diubah menjadi {$targetLabel}. Pilihan yang tersedia: {$allowedLabels}.";
    }

    private function guardTransition(OutgoingMail $mail, ?string $current, string $target): void
    {
        if (! OutgoingMailStatus::canTransition($current, $target)) {
            throw ValidationException::withMessages([
                'status' => $this->rejectionReason($mail, $target) ?? 'Perubahan status tidak diizinkan.',
            ]);
        }
    }

    private function recordNote(OutgoingMail $mail, string $type, ?string $note, ?int $actorId): void
    {
        $mail->revisionNotes()->create([
            'type' => $type,
            'note' => filled($note) ? trim($note) : null,
            'created_by' => $actorId,
        ]);
    }
}

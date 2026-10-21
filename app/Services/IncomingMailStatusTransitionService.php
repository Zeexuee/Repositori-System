<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncomingMail;
use App\Models\IncomingMailRevisionNote;
use App\Support\IncomingMailStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomingMailStatusTransitionService
{
    /**
     * Apply a status transition, enforcing the standard operating procedure.
     *
     * @param  string|null  $revisionNote  Required when entering REVISI, optional when leaving it.
     */
    public function apply(
        IncomingMail $incomingMail,
        string $targetStatus,
        ?int $actorId,
        ?string $revisionNote = null
    ): void {
        $target = IncomingMailStatus::canonical($targetStatus);

        if ($target === null) {
            throw ValidationException::withMessages([
                'status' => 'Status tujuan tidak dikenali.',
            ]);
        }

        if ($target === IncomingMailStatus::WAITING_RETURN || $target === IncomingMailStatus::RETURN) {
            throw ValidationException::withMessages([
                'status' => 'Status pengembalian hanya dapat diubah melalui proses tanda tangan pengembalian.',
            ]);
        }

        DB::transaction(function () use ($incomingMail, $target, $actorId, $revisionNote): void {
            $mail = IncomingMail::whereKey($incomingMail->getKey())->lockForUpdate()->firstOrFail();
            $current = IncomingMailStatus::canonical($mail->status);

            $this->guardTransition($mail, $current, $target);

            if ($target === IncomingMailStatus::REVISI && blank($revisionNote)) {
                throw ValidationException::withMessages([
                    'revision_note' => 'Catatan kebutuhan revisi wajib diisi.',
                ]);
            }

            $mail->update(['status' => $target]);

            if ($target === IncomingMailStatus::REVISI) {
                $this->recordNote($mail, IncomingMailRevisionNote::TYPE_REQUESTED, $revisionNote, $actorId);
            } elseif ($current === IncomingMailStatus::REVISI && filled($revisionNote)) {
                $this->recordNote($mail, IncomingMailRevisionNote::TYPE_RESOLVED, $revisionNote, $actorId);
            }

            $incomingMail->setAttribute('status', $target);
        });
    }

    /**
     * Human readable reason why a transition is rejected, or null when allowed.
     */
    public function rejectionReason(IncomingMail $incomingMail, string $targetStatus): ?string
    {
        $current = IncomingMailStatus::canonical($incomingMail->status);
        $target = IncomingMailStatus::canonical($targetStatus);

        if ($target === null) {
            return 'Status tujuan tidak dikenali.';
        }

        if ($current === $target) {
            return 'Dokumen sudah berada pada status tersebut.';
        }

        if (IncomingMailStatus::canTransition($current, $target)) {
            return null;
        }

        $allowed = IncomingMailStatus::allowedTransitions($current);
        $currentLabel = IncomingMailStatus::label($incomingMail->status);
        $targetLabel = IncomingMailStatus::label($targetStatus);

        if ($allowed === []) {
            return "Status {$currentLabel} adalah status akhir dan tidak dapat diubah lagi.";
        }

        $allowedLabels = implode(', ', array_map(
            static fn (string $status): string => IncomingMailStatus::label($status),
            $allowed
        ));

        return "Status {$currentLabel} tidak dapat diubah menjadi {$targetLabel}. Pilihan yang tersedia: {$allowedLabels}.";
    }

    private function guardTransition(IncomingMail $mail, ?string $current, string $target): void
    {
        if (! IncomingMailStatus::canTransition($current, $target)) {
            throw ValidationException::withMessages([
                'status' => $this->rejectionReason($mail, $target) ?? 'Perubahan status tidak diizinkan.',
            ]);
        }
    }

    private function recordNote(IncomingMail $mail, string $type, ?string $note, ?int $actorId): void
    {
        $mail->revisionNotes()->create([
            'type' => $type,
            'note' => filled($note) ? trim($note) : null,
            'created_by' => $actorId,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Support\IncomingMailStatus;
use LogicException;

class IncomingMailStatusSyncService
{
    /**
     * Statuses that are mirrored onto an outgoing mail record.
     *
     * @var array<int, string>
     */
    private const MIRRORED_STATUSES = [
        IncomingMailStatus::RETURN,
        IncomingMailStatus::REVISI,
    ];

    public function sync(IncomingMail $incomingMail, ?int $actorId): void
    {
        $status = IncomingMailStatus::canonical($incomingMail->status);

        if ($status !== null && in_array($status, self::MIRRORED_STATUSES, true)) {
            $this->syncOutgoingMail($incomingMail, $status, $actorId);
        }
    }

    private function syncOutgoingMail(IncomingMail $incomingMail, string $status, ?int $actorId): void
    {
        $cleanSubject = preg_replace(
            '/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|REVISI)\]\s*/i',
            '',
            $incomingMail->subject
        );
        $subject = "[{$status}] {$cleanSubject}";

        $outgoingMail = OutgoingMail::where('subject', 'like', '%'.$incomingMail->subject)
            ->latest()
            ->first();

        if ($outgoingMail) {
            $outgoingMail->update([
                'status' => $status,
                'subject' => $subject,
            ]);

            return;
        }

        if ($actorId === null) {
            throw new LogicException('A user is required to synchronize an outgoing mail.');
        }

        OutgoingMail::create([
            'mail_number' => 'SK-'.now()->format('Ymd').'-'.sprintf('%04d', OutgoingMail::whereNotNull('mail_number')->count() + 1),
            'subject' => $subject,
            'recipient' => $incomingMail->sender,
            'file_path' => $incomingMail->file_path ?? $incomingMail->document_photo_path,
            'created_by' => $actorId,
            'status' => $status,
        ]);
    }
}

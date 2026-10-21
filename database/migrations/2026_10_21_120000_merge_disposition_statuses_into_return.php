<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges the retired disposition statuses into the return statuses.
 *
 * The disposition note itself is retained on both mail tables; only the status
 * family collapses. Disposition recipients are copied into the return
 * recipients table so the return interface keeps showing them. The original
 * incoming_mail_disposition_recipients rows are deliberately left untouched as
 * an audit trail.
 */
return new class extends Migration
{
    /**
     * Retired statuses mapped onto their replacement.
     *
     * @var array<string, array<int, string>>
     */
    private const STATUS_MAP = [
        'RETURN' => ['DISPOSISI', 'DISPOSITION', 'DISPATCHED'],
        'WAITING_RETURN' => [
            'WAITING_DISPOSISI',
            'WAITING DISPOSISI',
            'WAITING_DISPOSITION',
            'WAITING DISPOSITION',
            'PENDING_DISPOSITION',
        ],
    ];

    public function up(): void
    {
        foreach (self::STATUS_MAP as $replacement => $retired) {
            DB::table('incoming_mails')
                ->whereIn('status', $retired)
                ->update(['status' => $replacement]);
        }

        $this->copyDispositionRecipientsToReturnRecipients();
    }

    public function down(): void
    {
        // Rows copied by up() reuse the source primary key, so they can be
        // removed precisely. The status merge itself is not reversible: a
        // merged RETURN is indistinguishable from an original RETURN.
        $copiedIds = DB::table('incoming_mail_disposition_recipients')->pluck('id');

        if ($copiedIds->isNotEmpty()) {
            DB::table('incoming_mail_return_recipients')
                ->whereIn('id', $copiedIds)
                ->delete();
        }
    }

    /**
     * Copy disposition recipients across, skipping documents that already have
     * return recipients so an existing return workflow is never overwritten.
     */
    private function copyDispositionRecipientsToReturnRecipients(): void
    {
        $mailIdsWithReturnRecipients = DB::table('incoming_mail_return_recipients')
            ->distinct()
            ->pluck('incoming_mail_id')
            ->all();

        DB::table('incoming_mail_disposition_recipients')
            ->orderBy('incoming_mail_id')
            ->orderBy('sort_order')
            ->chunk(200, function ($recipients) use ($mailIdsWithReturnRecipients): void {
                $rows = [];

                foreach ($recipients as $recipient) {
                    if (in_array($recipient->incoming_mail_id, $mailIdsWithReturnRecipients, true)) {
                        continue;
                    }

                    $rows[] = [
                        // Reusing the source id keeps down() exact and makes the
                        // migration idempotent via insertOrIgnore.
                        'id' => $recipient->id,
                        'incoming_mail_id' => $recipient->incoming_mail_id,
                        'recipient_name' => $recipient->recipient_name,
                        'signer_name' => $recipient->signer_name,
                        'signature_path' => $recipient->signature_path,
                        'signed_at' => $recipient->signed_at,
                        'sort_order' => $recipient->sort_order,
                        'created_by' => $recipient->created_by,
                        'created_at' => $recipient->created_at,
                        'updated_at' => $recipient->updated_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('incoming_mail_return_recipients')->insertOrIgnore($rows);
                }
            });
    }
};

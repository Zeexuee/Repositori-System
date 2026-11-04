<?php

declare(strict_types=1);

use App\Models\IncomingMail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dokumen yang tidak memiliki riwayat tanda tangan pengembalian:
        // Reset outgoing_date ke NULL (karena sebelumnya terisi otomatis sama dengan received_date).
        DB::table('incoming_mails')
            ->whereNotIn('id', function ($query) {
                $query->select('incoming_mail_id')->from('incoming_mail_return_recipients');
            })
            ->update(['outgoing_date' => null]);

        // 2. Dokumen yang memiliki riwayat pengembalian:
        // Pastikan outgoing_date mencatat waktu tanda tangan/aktivitas pengembalian terakhir.
        $mailsWithReturn = IncomingMail::with('returnRecipients')->has('returnRecipients')->get();
        foreach ($mailsWithReturn as $mail) {
            $latestSignedAt = null;
            foreach ($mail->returnRecipients as $r) {
                if ($r->signed_at && ($latestSignedAt === null || $r->signed_at->gt($latestSignedAt))) {
                    $latestSignedAt = $r->signed_at;
                }
            }
            $mail->outgoing_date = $latestSignedAt ?? $mail->returnRecipients->max('created_at') ?? $mail->updated_at;
            $mail->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for data cleanup
    }
};

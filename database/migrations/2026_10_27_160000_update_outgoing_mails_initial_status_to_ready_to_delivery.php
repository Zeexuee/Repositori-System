<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update outgoing mails with WAITING_RECEIVE and no return recipient progress to READY_TO_DELIVERY
        DB::table('outgoing_mails')
            ->where('status', 'WAITING_RECEIVE')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('outgoing_mail_return_recipients')
                    ->whereColumn('outgoing_mail_return_recipients.outgoing_mail_id', 'outgoing_mails.id');
            })
            ->update(['status' => 'READY_TO_DELIVERY']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('outgoing_mails')
            ->where('status', 'READY_TO_DELIVERY')
            ->update(['status' => 'WAITING_RECEIVE']);
    }
};

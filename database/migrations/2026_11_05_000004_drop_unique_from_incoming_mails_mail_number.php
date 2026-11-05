<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check and drop the unique index if it exists, to allow batch/draft documents to share auto-generated doc receipt numbers
        try {
            Schema::table('incoming_mails', function (Blueprint $table) {
                $table->dropUnique('incoming_mails_mail_number_unique');
            });
        } catch (\Throwable $e) {
            // Index might not exist or might already have been dropped
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('incoming_mails', function (Blueprint $table) {
                $table->unique('mail_number', 'incoming_mails_mail_number_unique');
            });
        } catch (\Throwable $e) {
            //
        }
    }
};


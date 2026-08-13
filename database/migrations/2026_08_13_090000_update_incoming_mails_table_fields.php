<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->string('recipient')->nullable()->after('sender');
            $table->date('outgoing_date')->nullable()->after('received_date');
            $table->text('disposition_note')->nullable()->after('status');
            $table->text('notes')->nullable()->after('disposition_note');
            $table->string('recipient_name')->nullable()->after('notes');
            $table->string('document_photo_path')->nullable()->after('file_path');
            $table->string('receipt_signature_path')->nullable()->after('document_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->dropColumn([
                'recipient',
                'outgoing_date',
                'disposition_note',
                'notes',
                'recipient_name',
                'document_photo_path',
                'receipt_signature_path',
            ]);
        });
    }
};

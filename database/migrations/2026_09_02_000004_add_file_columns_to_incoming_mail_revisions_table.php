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
        Schema::table('incoming_mail_revisions', function (Blueprint $table) {
            if (! Schema::hasColumn('incoming_mail_revisions', 'previous_file_path')) {
                $table->string('previous_file_path')->nullable()->after('changed_fields');
            }
            if (! Schema::hasColumn('incoming_mail_revisions', 'file_path')) {
                $table->string('file_path')->nullable()->after('previous_file_path');
            }
            if (! Schema::hasColumn('incoming_mail_revisions', 'previous_document_photo_path')) {
                $table->string('previous_document_photo_path')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('incoming_mail_revisions', 'document_photo_path')) {
                $table->string('document_photo_path')->nullable()->after('previous_document_photo_path');
            }
            if (! Schema::hasColumn('incoming_mail_revisions', 'previous_signature_path')) {
                $table->string('previous_signature_path')->nullable()->after('document_photo_path');
            }
            if (! Schema::hasColumn('incoming_mail_revisions', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('previous_signature_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_mail_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'previous_file_path',
                'file_path',
                'previous_document_photo_path',
                'document_photo_path',
                'previous_signature_path',
                'signature_path',
            ]);
        });
    }
};

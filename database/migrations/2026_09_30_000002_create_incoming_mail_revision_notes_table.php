<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statuses that are no longer part of the standard operating procedure.
     *
     * @var array<int, string>
     */
    private const RETIRED_STATUSES = ['PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING'];

    public function up(): void
    {
        Schema::create('incoming_mail_revision_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incoming_mail_id')->constrained('incoming_mails')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Millisecond precision keeps the history order deterministic.
            $table->timestamps(3);

            $table->index(['incoming_mail_id', 'created_at'], 'revision_notes_mail_created_index');
        });

        // The PROGRES status is retired; those documents become DRAFT again.
        DB::table('incoming_mails')
            ->whereIn('status', self::RETIRED_STATUSES)
            ->update(['status' => 'DRAFT']);

        DB::table('outgoing_mails')
            ->whereIn('status', self::RETIRED_STATUSES)
            ->update(['status' => 'DRAFT']);
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_mail_revision_notes');
    }
};

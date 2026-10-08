<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_mail_revision_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_mail_id')->constrained('outgoing_mails')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Millisecond precision keeps the history order deterministic.
            $table->timestamps(3);

            $table->index(['outgoing_mail_id', 'created_at'], 'outgoing_revision_notes_mail_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_mail_revision_notes');
    }
};

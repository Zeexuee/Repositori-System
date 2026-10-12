<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix the outgoing_mail_revision_notes table with proper columns
        Schema::dropIfExists('outgoing_mail_revision_notes');

        Schema::create('outgoing_mail_revision_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_mail_id')->constrained('outgoing_mails')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(3);

            $table->index(['outgoing_mail_id', 'created_at'], 'outgoing_revision_notes_mail_created_index');
        });

        // Create outgoing_mail_return_recipients for signature workflow
        Schema::create('outgoing_mail_return_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_mail_id')->constrained('outgoing_mails')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('signer_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['outgoing_mail_id', 'sort_order'], 'outgoing_return_recipients_mail_order_index');
        });

        // Migrate legacy statuses to new WAITING_RECEIVE
        DB::table('outgoing_mails')
            ->whereIn('status', [
                'WAITING_RETURN', 'RETURN', 'RETURNED',
                'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING',
                'APPROVED', 'SIGNED', 'DRAFT',
            ])
            ->update(['status' => 'WAITING_RECEIVE']);
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_mail_return_recipients');
        Schema::dropIfExists('outgoing_mail_revision_notes');
    }
};

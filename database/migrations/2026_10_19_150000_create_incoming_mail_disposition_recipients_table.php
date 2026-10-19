<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_mail_disposition_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incoming_mail_id')->constrained('incoming_mails')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('signer_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['incoming_mail_id', 'sort_order'], 'disposition_recipients_mail_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_mail_disposition_recipients');
    }
};

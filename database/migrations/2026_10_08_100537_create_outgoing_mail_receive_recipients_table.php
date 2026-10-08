<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_mail_receive_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_mail_id')->constrained('outgoing_mails')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('signer_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['outgoing_mail_id', 'sort_order'], 'receive_recipients_mail_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_mail_receive_recipients');
    }
};

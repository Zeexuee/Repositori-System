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
        Schema::create('outgoing_mail_file_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_mail_id')->constrained('outgoing_mails')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('outgoing_mail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_mail_file_histories');
    }
};

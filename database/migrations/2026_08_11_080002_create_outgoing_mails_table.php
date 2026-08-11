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
        Schema::create('outgoing_mails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('mail_number')->nullable()->unique();
            $table->string('subject');
            $table->string('recipient');
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', [
                'DRAFT',
                'IN_REVIEW',
                'REVISED',
                'APPROVED',
                'SIGN_FAILED',
                'SIGNED',
            ])->default('DRAFT');
            $table->timestamps();

            $table->index('status');
            $table->index('mail_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_mails');
    }
};

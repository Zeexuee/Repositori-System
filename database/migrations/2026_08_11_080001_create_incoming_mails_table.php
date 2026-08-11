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
        Schema::create('incoming_mails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('mail_number')->unique();
            $table->string('subject');
            $table->string('sender');
            $table->date('received_date');
            $table->string('file_path')->nullable();
            $table->enum('status', [
                'RECEIVED',
                'REGISTERED',
                'PENDING_DISPOSITION',
                'IN_PROGRESS',
                'COMPLETED',
                'OVERDUE',
            ])->default('RECEIVED');
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
        Schema::dropIfExists('incoming_mails');
    }
};

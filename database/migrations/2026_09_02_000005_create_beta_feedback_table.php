<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beta_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('category', ['BUG', 'UI_UX', 'PERFORMANCE', 'OTHER'])->default('BUG');
            $table->string('title');
            $table->text('description');
            $table->string('page_url')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])->default('PENDING');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beta_feedback');
    }
};

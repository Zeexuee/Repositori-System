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
        Schema::table('incoming_mails', function (Blueprint $table) {
            if (! Schema::hasColumn('incoming_mails', 'revision_count')) {
                $table->unsignedInteger('revision_count')->default(0)->after('status');
            }
        });

        if (! Schema::hasTable('incoming_mail_revisions')) {
            Schema::create('incoming_mail_revisions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('incoming_mail_id')->constrained('incoming_mails')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('revision_number')->default(1);
                $table->string('previous_status')->nullable();
                $table->string('new_status')->default('REVISI');
                $table->text('notes')->nullable();
                $table->json('changed_fields')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_mail_revisions');

        Schema::table('incoming_mails', function (Blueprint $table) {
            if (Schema::hasColumn('incoming_mails', 'revision_count')) {
                $table->dropColumn('revision_count');
            }
        });
    }
};

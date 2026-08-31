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
            $table->uuid('batch_id')->nullable()->after('id')->index();
            $table->string('receipt_number')->nullable()->after('batch_id')->index();
        });

        // Update status enum if database driver supports altering enum, or use string/change
        // DB::statement / Schema change
        try {
            Schema::table('incoming_mails', function (Blueprint $table) {
                $table->string('status')->default('RECEIVED')->change();
            });
        } catch (\Throwable $e) {
            // fallback if change() requires doctrine/dbal or driver specific handling
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'receipt_number']);
        });
    }
};

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
        try {
            Schema::table('outgoing_mails', function (Blueprint $table) {
                $table->text('recipient')->change();
            });
        } catch (\Throwable $e) {
            // Fallback for drivers that don't support change() without dbal
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('outgoing_mails', function (Blueprint $table) {
                $table->string('recipient', 255)->change();
            });
        } catch (\Throwable $e) {
            //
        }
    }
};

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
        // Cari index unique pada kolom mail_number jika ada
        $uniqueIndex = collect(Schema::getIndexes('incoming_mails'))
            ->first(function ($idx) {
                return ($idx['unique'] ?? false) && in_array('mail_number', $idx['columns'] ?? [], true);
            });

        if ($uniqueIndex) {
            Schema::table('incoming_mails', function (Blueprint $table) use ($uniqueIndex) {
                $table->dropUnique($uniqueIndex['name']);
            });
        }

        // Pastikan kolom mail_number tetap memiliki index biasa untuk performa query pencarian
        $hasIndex = collect(Schema::getIndexes('incoming_mails'))
            ->contains(function ($idx) {
                return in_array('mail_number', $idx['columns'] ?? [], true);
            });

        if (! $hasIndex) {
            Schema::table('incoming_mails', function (Blueprint $table) {
                $table->index('mail_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->unique('mail_number');
        });
    }
};

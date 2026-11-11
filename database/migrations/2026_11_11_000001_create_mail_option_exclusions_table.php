<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar opsi riwayat (pengirim / penerima / status) yang dihapus pengguna
     * dari saran combobox registrasi on-the-fly.
     */
    public function up(): void
    {
        Schema::create('mail_option_exclusions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('value', 255);
            $table->string('value_key', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['type', 'value_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_option_exclusions');
    }
};


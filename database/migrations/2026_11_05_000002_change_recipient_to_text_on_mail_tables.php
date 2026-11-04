<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->text('recipient')->nullable()->change();
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->text('recipient')->change();
        });
    }

    public function down(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->string('recipient')->nullable()->change();
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->string('recipient')->change();
        });
    }
};


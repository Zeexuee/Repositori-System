<?php

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
            $table->dateTime('received_date')->change();
            $table->dateTime('outgoing_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->date('received_date')->change();
            $table->date('outgoing_date')->nullable()->change();
        });
    }
};

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
            $table->dropIndex(['status']);
        });

        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->renameColumn('status', 'progress');
        });

        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->string('status')->nullable()->after('progress');
            $table->index('progress');
            $table->index('status');
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->renameColumn('status', 'progress');
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->string('status')->nullable()->after('progress');
            $table->index('progress');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['progress']);
            $table->dropColumn('status');
        });

        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->renameColumn('progress', 'status');
        });

        Schema::table('incoming_mails', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['progress']);
            $table->dropColumn('status');
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->renameColumn('progress', 'status');
        });

        Schema::table('outgoing_mails', function (Blueprint $table) {
            $table->index('status');
        });
    }
};


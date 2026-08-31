<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit-logs:recycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindahkan log yang lebih dari 5 hari ke Trash dan hapus permanen log di Trash yang lebih dari 3 hari.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Pindahkan log aktif > 5 hari ke trash (Soft Delete)
        $trashedCount = AuditLog::where('created_at', '<', Carbon::now()->subDays(5))->delete();

        // 2. Hapus permanen log di trash > 3 hari (Force Delete)
        $permanentlyDeletedCount = AuditLog::onlyTrashed()
            ->where('deleted_at', '<', Carbon::now()->subDays(3))
            ->forceDelete();

        $this->info("Recycle audit logs selesai: {$trashedCount} log dipindahkan ke trash, {$permanentlyDeletedCount} log dihapus permanen.");

        return Command::SUCCESS;
    }
}

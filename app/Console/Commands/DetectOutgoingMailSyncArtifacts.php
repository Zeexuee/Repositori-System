<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Support\IncomingMailStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DetectOutgoingMailSyncArtifacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outgoing-mails:detect-sync-artifacts
        {--delete : Hapus permanen artefak setelah konfirmasi}
        {--force : Lewati konfirmasi (hanya dipakai bersama --delete)}
        {--backup= : Path backup JSON relatif terhadap disk local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deteksi surat keluar artefak sinkronisasi surat masuk lama (default: laporan saja).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $candidates = $this->findCandidates();

        if ($candidates->isEmpty()) {
            $this->info('Tidak ada artefak sinkronisasi yang terdeteksi.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Nomor', 'Subjek', 'Penerima', 'Surat Masuk', 'Status Masuk'],
            $candidates->map(fn (array $candidate): array => [
                $candidate['outgoing']->mail_number,
                $candidate['outgoing']->subject,
                $candidate['outgoing']->recipient,
                $candidate['incoming']->mail_number,
                IncomingMailStatus::label($candidate['incoming']->status),
            ])->all()
        );

        if (! $this->option('delete')) {
            $this->warn('Mode laporan: tidak ada data yang dihapus. Tinjau daftar di atas, lalu jalankan --delete bila sudah yakin.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Hapus permanen '.$candidates->count().' artefak di atas?')) {
            $this->info('Dibatalkan.');

            return Command::SUCCESS;
        }

        $backupPath = (string) ($this->option('backup')
            ?: 'backups/outgoing-mail-sync-artifacts-'.now()->format('Ymd-His').'.json');

        Storage::disk('local')->put(
            $backupPath,
            json_encode($this->backupPayload($candidates), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        foreach ($candidates as $candidate) {
            $candidate['outgoing']->forceDelete();
        }

        $this->info($candidates->count().' artefak dihapus permanen. Backup: '.$backupPath);

        return Command::SUCCESS;
    }

    /**
     * Find outgoing mails whose subject and recipient match a returned or
     * revised incoming mail. Manual review is still required before deletion.
     *
     * @return Collection<int, array{outgoing: OutgoingMail, incoming: IncomingMail}>
     */
    private function findCandidates(): Collection
    {
        return OutgoingMail::query()
            ->where(function ($query): void {
                $query->where('subject', 'like', '[REVISI]%')
                    ->orWhere('subject', 'like', '[RETURN]%');
            })
            ->orderBy('mail_number')
            ->get()
            ->map(function (OutgoingMail $outgoing): ?array {
                $statusTag = Str::startsWith($outgoing->subject, '[RETURN]') ? '[RETURN]' : '[REVISI]';
                $cleanSubject = trim(Str::after($outgoing->subject, $statusTag));

                if ($cleanSubject === '') {
                    return null;
                }

                $incoming = IncomingMail::query()
                    ->where('subject', $cleanSubject)
                    ->where('sender', $outgoing->recipient)
                    ->whereIn('status', [IncomingMailStatus::RETURN, IncomingMailStatus::REVISI])
                    ->orderByDesc('created_at')
                    ->first();

                return $incoming === null
                    ? null
                    : ['outgoing' => $outgoing, 'incoming' => $incoming];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array{outgoing: OutgoingMail, incoming: IncomingMail}>  $candidates
     * @return array<string, mixed>
     */
    private function backupPayload(Collection $candidates): array
    {
        return [
            'created_at' => now()->toIso8601String(),
            'command' => 'outgoing-mails:detect-sync-artifacts',
            'artifacts' => $candidates->map(function (array $candidate): array {
                $outgoing = $candidate['outgoing'];
                $incoming = $candidate['incoming'];

                return [
                    'outgoing_mail' => $outgoing->getAttributes(),
                    'source_incoming_mail' => $incoming->only(['id', 'mail_number', 'subject', 'sender', 'status']),
                    'audit_logs' => AuditLog::query()
                        ->where('model_type', OutgoingMail::class)
                        ->where('model_id', $outgoing->id)
                        ->get()
                        ->map(fn (AuditLog $log): array => $log->getAttributes())
                        ->all(),
                ];
            })->all(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DetectOutgoingMailSyncArtifactsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => Roles::STAFF]));

        return $user;
    }

    private function revisedIncomingMail(string $subject = 'Undangan Rapat', string $sender = 'Rektor 2'): IncomingMail
    {
        return IncomingMail::create([
            'mail_number' => 'SM-UJI-001',
            'received_date' => '2026-10-21 09:00:00',
            'sender' => $sender,
            'recipient' => 'Rektor 3',
            'subject' => $subject,
            'status' => 'REVISI',
        ]);
    }

    private function outgoingMail(User $creator, string $subject, string $recipient): OutgoingMail
    {
        return OutgoingMail::create([
            'mail_number' => 'SK-UJI-'.fake()->unique()->numerify('####'),
            'subject' => $subject,
            'recipient' => $recipient,
            'created_by' => $creator->id,
            'status' => 'REVISI',
        ]);
    }

    public function test_dry_run_reports_artifacts_without_deleting_them(): void
    {
        Storage::fake('local');

        $creator = $this->staff();
        $this->revisedIncomingMail();
        $artifact = $this->outgoingMail($creator, '[REVISI] Undangan Rapat', 'Rektor 2');

        $this->artisan('outgoing-mails:detect-sync-artifacts')
            ->expectsOutputToContain($artifact->mail_number)
            ->expectsOutputToContain('Mode laporan')
            ->assertSuccessful();

        $this->assertSame(1, OutgoingMail::whereKey($artifact->id)->count());
    }

    public function test_non_matching_outgoing_mail_is_ignored(): void
    {
        Storage::fake('local');

        $creator = $this->staff();
        $this->revisedIncomingMail();
        $legitimate = $this->outgoingMail($creator, '[REVISI] Undangan Rapat', 'PT Lain');

        $this->artisan('outgoing-mails:detect-sync-artifacts')
            ->expectsOutputToContain('Tidak ada artefak')
            ->assertSuccessful();

        $this->assertSame(1, OutgoingMail::whereKey($legitimate->id)->count());
    }

    public function test_force_delete_removes_artifacts_and_writes_backup(): void
    {
        Storage::fake('local');

        $creator = $this->staff();
        $this->revisedIncomingMail();
        $artifact = $this->outgoingMail($creator, '[REVISI] Undangan Rapat', 'Rektor 2');
        $legitimate = $this->outgoingMail($creator, '[REVISI] Surat Lain', 'PT Lain');

        $this->artisan('outgoing-mails:detect-sync-artifacts', [
            '--delete' => true,
            '--force' => true,
            '--backup' => 'backups/test-sync-artifacts.json',
        ])->assertSuccessful();

        $this->assertSame(0, OutgoingMail::withTrashed()->whereKey($artifact->id)->count());
        $this->assertSame(1, OutgoingMail::whereKey($legitimate->id)->count());
        Storage::disk('local')->assertExists('backups/test-sync-artifacts.json');
    }
}

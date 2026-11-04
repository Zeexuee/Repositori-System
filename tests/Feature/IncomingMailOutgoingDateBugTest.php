<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\User;
use App\Support\IncomingMailStatus;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncomingMailOutgoingDateBugTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_mail_created_without_return_activity_has_null_outgoing_date(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->post(route('incoming-mails.store'), [
            'sender' => 'Kementerian Pendidikan',
            'received_date' => '2026-11-05T09:00',
            'subject' => 'Surat Baru Masuk',
            'recipient' => 'Rektor',
        ]);

        $response->assertRedirect(route('incoming-mails.index'));

        $mail = IncomingMail::where('subject', 'Surat Baru Masuk')->firstOrFail();
        $this->assertNull($mail->outgoing_date, 'Tanggal Keluar harus null saat dokumen baru masuk dan belum ada aktivitas pengembalian');
    }

    public function test_incoming_mail_return_activity_sets_outgoing_date_to_return_timestamp(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'SM-20261105-001',
            'subject' => 'Surat Untuk Dikembalikan',
            'sender' => 'Biro Keuangan',
            'recipient' => 'Dekan',
            'received_date' => '2026-11-01 10:00:00',
            'outgoing_date' => null,
            'progress' => IncomingMailStatus::RECEIVE,
        ]);

        $this->assertNull($mail->outgoing_date);

        // Dummy base64 signature
        $sigData = 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('sig.png')->getContent());

        // Perform return workflow
        $response = $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'complete',
            'disposition_note' => 'Sudah diparaf dan disetujui.',
            'recipients' => [
                [
                    'recipient_name' => 'Biro Keuangan',
                    'signer_name' => 'Budi Santoso',
                    'signature' => $sigData,
                ],
            ],
        ]);

        $response->assertOk();

        $mail->refresh();
        $this->assertSame(IncomingMailStatus::RETURN, $mail->progress);
        $this->assertNotNull($mail->outgoing_date, 'Tanggal Keluar harus terisi saat ada aktivitas pengembalian');
        $this->assertNotEquals('2026-11-01 10:00:00', $mail->outgoing_date->format('Y-m-d H:i:s'));
    }
}

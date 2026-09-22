<?php

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncomingMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_incoming_mails_by_all_criteria(): void
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail1 = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/001',
            'received_date' => '2026-09-10',
            'sender' => 'Biro Humas',
            'recipient' => 'Direktur Utama',
            'status' => 'RECEIVE',
            'subject' => 'Undangan Rapat Koordinasi',
            'outgoing_date' => '2026-09-12',
            'disposition_note' => 'Mohon ditindaklanjuti',
            'recipient_name' => 'Budi Santoso',
        ]);

        $mail2 = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/002',
            'received_date' => '2026-09-15',
            'sender' => 'Biro Keuangan',
            'recipient' => 'Kepala Divisi',
            'status' => 'PROGRES',
            'subject' => 'Laporan Anggaran Triwulan',
            'outgoing_date' => '2026-09-16',
            'disposition_note' => 'Arsipkan setelah review',
            'recipient_name' => 'Siti Aminah',
        ]);

        // Global search
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['search' => 'Humas']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/001');
        $response->assertDontSee('TEST/SURAT/002');

        // Specific filter: mail_number
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['mail_number' => '002']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/002');
        $response->assertDontSee('TEST/SURAT/001');

        // Specific filter: status
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['status' => 'PROGRES']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/002');
        $response->assertDontSee('TEST/SURAT/001');

        // Specific filter: disposition_note
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['disposition_note' => 'ditindaklanjuti']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/001');
        $response->assertDontSee('TEST/SURAT/002');

        // Specific filter: recipient_name
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['recipient_name' => 'Siti']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/002');
        $response->assertDontSee('TEST/SURAT/001');
    }

    public function test_user_can_update_status_to_revisi_and_sync_to_outgoing_mail(): void
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/003',
            'received_date' => '2026-09-18',
            'sender' => 'Kementerian Keuangan',
            'recipient' => 'Direktur Utama',
            'status' => 'RECEIVE',
            'subject' => 'Permintaan Data Operasional',
        ]);

        $response = $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->status);

        $outgoingMail = \App\Models\OutgoingMail::where('recipient', 'Kementerian Keuangan')->latest()->first();
        $this->assertNotNull($outgoingMail);
        $this->assertSame('REVISI', $outgoingMail->status);
        $this->assertStringStartsWith('[REVISI]', $outgoingMail->subject);
    }
}

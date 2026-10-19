<?php

namespace Tests\Feature;

use App\Models\OutgoingMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutgoingMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_outgoing_mail_status_to_revisi(): void
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20260922-0001',
            'subject' => 'Surat Tanggapan Kerjasama',
            'recipient' => 'PT Mitra Sejahtera',
            'created_by' => $user->id,
            'status' => 'WAITING_RECEIVE',
        ]);

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
            'revision_note' => 'Halo tes',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->status);
    }

    public function test_outgoing_mail_show_displays_revision_history(): void
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20260922-0002',
            'subject' => 'Surat Undangan Evaluasi',
            'recipient' => 'PT Mitra Sejahtera',
            'created_by' => $user->id,
            'status' => 'WAITING_RECEIVE',
        ]);

        $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
            'revision_note' => 'Halo tes catatan revisi',
        ]);

        $response = $this->actingAs($user)->get(route('outgoing-mails.show', $mail));

        $response->assertOk();
        $response->assertSee('Riwayat Revisi Dokumen');
        $response->assertSee('Halo tes catatan revisi');
        $response->assertSee('Permintaan Revisi');
    }

    public function test_outgoing_mail_show_displays_only_initial_creation_date(): void
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261019-0003',
            'subject' => 'Surat Undangan Rapat Koordinasi',
            'recipient' => 'Dinas Pendidikan',
            'created_by' => $user->id,
            'status' => 'WAITING_RECEIVE',
        ]);

        $response = $this->actingAs($user)->get(route('outgoing-mails.show', $mail));

        $response->assertOk();
        $response->assertSee('Tanggal Dibuat');
        $response->assertDontSee('Terakhir Diubah');
    }
}


<?php

namespace Tests\Feature;

use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutgoingMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_outgoing_mail_status_to_revisi(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
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
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
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
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
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

    public function test_outgoing_mail_index_displays_upload_gambar_action(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261021-0001',
            'subject' => 'Surat Pengantar Lampiran',
            'recipient' => 'PT Mitra Sejahtera',
            'created_by' => $user->id,
            'status' => 'WAITING_RECEIVE',
        ]);

        $response = $this->actingAs($user)->get(route('outgoing-mails.index'));

        $response->assertOk();
        $response->assertSee('Upload Gambar');
        $response->assertSee('window.openUploadAttachmentModal');
    }

    public function test_user_can_upload_and_manage_outgoing_mail_attachments(): void
    {
        Storage::fake('local');

        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261021-0002',
            'subject' => 'Surat Pengiriman Dokumen',
            'recipient' => 'Dinas Pendidikan',
            'created_by' => $user->id,
            'status' => 'WAITING_RECEIVE',
        ]);

        $pdf = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');
        $photo = UploadedFile::fake()->image('foto.jpg', 600, 600);

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.attachments.store', $mail), [
            'files' => [$pdf, $photo],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total_attachments' => 2,
        ]);

        $this->assertCount(2, $mail->fresh()->attachments);

        // Fetch attachments via API
        $getResponse = $this->actingAs($user)->getJson(route('outgoing-mails.attachments.index', $mail));
        $getResponse->assertOk();
        $getResponse->assertJsonStructure([
            'success',
            'mail' => ['id', 'mail_number', 'subject', 'recipient'],
            'attachments' => [
                '*' => ['id', 'file_name', 'file_size', 'mime_type', 'is_image', 'preview_url', 'download_url'],
            ],
        ]);

        // Delete attachment
        $attachment = $mail->fresh()->attachments->first();
        $delResponse = $this->actingAs($user)->deleteJson(route('outgoing-mails.attachments.destroy', [$mail, $attachment]));
        $delResponse->assertOk();
        $this->assertCount(1, $mail->fresh()->attachments);
    }

    public function test_storing_outgoing_mail_sets_status_to_ready_to_delivery(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->post(route('outgoing-mails.store'), [
            'documents' => [
                [
                    'subject' => 'Surat Pengantar Kerjasama Baru',
                    'recipient' => 'PT Mitra Baru',
                ],
            ],
        ]);

        $response->assertRedirect(route('outgoing-mails.index'));
        $response->assertSessionHas('success', '1 Surat Keluar berhasil dicatat (Status: READY TO DELIVERY).');

        $mail = OutgoingMail::where('subject', 'Surat Pengantar Kerjasama Baru')->first();
        $this->assertNotNull($mail);
        $this->assertSame('READY_TO_DELIVERY', $mail->status);
    }

    public function test_outgoing_mail_index_displays_ready_to_delivery(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261027-0099',
            'subject' => 'Surat Siap Dikirim',
            'recipient' => 'Dinas Perhubungan',
            'created_by' => $user->id,
            'status' => 'READY_TO_DELIVERY',
        ]);

        $response = $this->actingAs($user)->get(route('outgoing-mails.index'));

        $response->assertOk();
        $response->assertSee('READY TO DELIVERY');
    }

    public function test_outgoing_mail_can_transition_from_revisi_back_to_ready_to_delivery(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261027-0100',
            'subject' => 'Surat Perbaikan',
            'recipient' => 'Dinas Lingkungan',
            'created_by' => $user->id,
            'status' => 'REVISI',
        ]);

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'READY_TO_DELIVERY',
            'revision_note' => 'Revisi telah diperbaiki dan siap dikirim ulang',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $this->assertSame('READY_TO_DELIVERY', $mail->fresh()->status);
    }
}

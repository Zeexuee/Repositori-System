<?php

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncomingMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_incoming_mails_by_all_criteria(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
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
            'status' => 'REVISI',
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
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['status' => 'REVISI']));
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
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
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
            'revision_note' => 'Data operasional belum lengkap.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->status);

        $outgoingMail = OutgoingMail::where('recipient', 'Kementerian Keuangan')->latest()->first();
        $this->assertNotNull($outgoingMail);
        $this->assertSame('REVISI', $outgoingMail->status);
        $this->assertStringStartsWith('[REVISI]', $outgoingMail->subject);
    }

    public function test_beta_v2_modal_is_rendered_for_authenticated_users(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertStatus(200);
        $response->assertSee('Terima Kasih');
        $response->assertSee('Beta v2');
        $response->assertSee('Detail Pembaruan');
    }

    public function test_incoming_mail_create_page_has_editable_received_date_with_default_now(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('incoming-mails.create'));

        $response->assertStatus(200);
        $response->assertSee('id="received_date"', false);
        $response->assertSee('value="' . now()->format('Y-m-d\TH:i') . '"', false);
        $response->assertDontSee('id="received_date" value="' . now()->format('Y-m-d\TH:i') . '" required readonly', false);
    }

    public function test_incoming_mail_stores_custom_received_date_and_time(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $customDateTime = '2026-05-20T14:30';

        $response = $this->actingAs($user)->post(route('incoming-mails.store'), [
            'sender' => 'Kementerian ESDM',
            'received_date' => $customDateTime,
            'documents' => [
                [
                    'mail_number' => 'ESDM/2026/001',
                    'recipient' => 'Direktur Utama',
                    'subject' => 'Surat Undangan Audiensi Khusus',
                    'disposition_note' => '',
                    'notes' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('incoming-mails.index'));

        $this->assertDatabaseHas('incoming_mails', [
            'mail_number' => 'ESDM/2026/001',
            'sender' => 'Kementerian ESDM',
            'recipient' => 'Direktur Utama',
            'received_date' => '2026-05-20 14:30:00',
        ]);
    }

    public function test_incoming_mail_index_displays_action_button_to_add_photo_or_document(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/AKSI-01',
            'received_date' => '2026-10-19 10:00:00',
            'sender' => 'Biro Umum',
            'recipient' => 'Direksi',
            'status' => 'RECEIVE',
            'subject' => 'Surat Pengantar Dokumen Penting',
        ]);

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertOk();
        $response->assertSee('Add Doc');
        $response->assertSee('window.openUploadAttachmentModal');
    }

    public function test_user_can_upload_photo_and_document_attachments(): void
    {
        Storage::fake('local');

        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/UPLOAD-01',
            'received_date' => '2026-10-19 10:00:00',
            'sender' => 'Biro Arsip',
            'recipient' => 'Sekretaris',
            'status' => 'RECEIVE',
            'subject' => 'Surat Lampiran Berkas',
        ]);

        $photoFile = UploadedFile::fake()->image('foto_dokumen.jpg', 600, 400);
        $pdfFile = UploadedFile::fake()->create('dokumen_resmi.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.attachments.store', $mail), [
            'files' => [$photoFile, $pdfFile],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total_attachments' => 2,
        ]);

        $mail->refresh();
        $this->assertCount(2, $mail->attachments);
        $this->assertNotNull($mail->document_photo_path);
        $this->assertNotNull($mail->file_path);

        // Fetch attachments via API
        $getResponse = $this->actingAs($user)->getJson(route('incoming-mails.attachments.index', $mail));
        $getResponse->assertOk();
        $getResponse->assertJsonStructure([
            'success',
            'mail' => ['id', 'mail_number', 'subject'],
            'attachments' => [
                '*' => ['id', 'file_name', 'mime_type', 'is_image', 'preview_url', 'download_url'],
            ],
        ]);
    }

    public function test_user_can_delete_attachment(): void
    {
        Storage::fake('local');

        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/DEL-01',
            'received_date' => '2026-10-19 10:00:00',
            'sender' => 'Biro Arsip',
            'status' => 'RECEIVE',
            'subject' => 'Surat Tes Hapus Berkas',
        ]);

        $pdfFile = UploadedFile::fake()->create('dokumen_hapus.pdf', 300, 'application/pdf');
        $this->actingAs($user)->postJson(route('incoming-mails.attachments.store', $mail), [
            'files' => [$pdfFile],
        ]);

        $mail->refresh();
        $attachment = $mail->attachments->first();
        $this->assertNotNull($attachment);

        $delResponse = $this->actingAs($user)->deleteJson(route('incoming-mails.attachments.destroy', [$mail, $attachment]));
        $delResponse->assertOk();
        $delResponse->assertJson(['success' => true]);

        $mail->refresh();
        $this->assertCount(0, $mail->attachments);
    }
}

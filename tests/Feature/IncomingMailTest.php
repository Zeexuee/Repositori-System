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
            'status' => 'Penting',
            'progress' => 'RECEIVE',
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
            'status' => 'Rahasia',
            'progress' => 'REVISI',
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

        // Specific filter: progress
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['progress' => 'REVISI']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/002');
        $response->assertDontSee('TEST/SURAT/001');

        // Specific filter: status (input admin)
        $response = $this->actingAs($user)->get(route('incoming-mails.index', ['status' => 'Penting']));
        $response->assertStatus(200);
        $response->assertSee('TEST/SURAT/001');
        $response->assertDontSee('TEST/SURAT/002');

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

    public function test_user_can_update_status_to_revisi_without_touching_outgoing_mails(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/003',
            'received_date' => '2026-09-18',
            'sender' => 'Kementerian Keuangan',
            'recipient' => 'Direktur Utama',
            'progress' => 'RECEIVE',
            'subject' => 'Permintaan Data Operasional',
        ]);

        $response = $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'progress' => 'REVISI',
            'revision_note' => 'Data operasional belum lengkap.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->progress);

        // Surat masuk dan surat keluar adalah dua register terpisah.
        $this->assertSame(0, OutgoingMail::count());
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

    public function test_incoming_mail_index_uses_lazy_load_chunks_instead_of_pages(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        foreach (range(1, 25) as $i) {
            IncomingMail::forceCreate([
                'mail_number' => sprintf('LAZY/IN/%03d', $i),
                'received_date' => '2026-11-05',
                'sender' => 'Biro Umum',
                'recipient' => 'Rektor',
                'progress' => 'RECEIVE',
                'subject' => "Surat Lazy Load {$i}",
                'created_at' => now()->subMinutes($i),
            ]);
        }

        // Halaman penuh: blok pertama + penanda lazy load, tanpa link paginasi, meski ada ?page lama
        $this->actingAs($user)->get(route('incoming-mails.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('loadMoreSentinel', false)
            ->assertSee('LAZY/IN/001')
            ->assertDontSee('LAZY/IN/025');

        // Blok berikutnya lewat AJAX
        $this->actingAs($user)->getJson(route('incoming-mails.index', ['page' => 2]))
            ->assertOk()
            ->assertJson(['current_page' => 2, 'has_more' => false, 'total' => 25])
            ->assertJsonCount(5, 'allIds');

        // Muat ulang seluruh baris yang sudah tampil sekaligus (dibatasi maksimal 200)
        $this->actingAs($user)->getJson(route('incoming-mails.index', ['per_page' => 40]))
            ->assertOk()
            ->assertJson(['current_page' => 1, 'has_more' => false, 'max_limit' => 200])
            ->assertJsonCount(25, 'allIds');

        // Pastikan markup lazy load memuat teks maksimal 200 dokumen dan teks seluruh dokumen
        $this->actingAs($user)->get(route('incoming-mails.index'))
            ->assertOk()
            ->assertSee('Maksimal load 200 dokumen, gunakan')
            ->assertSee('halaman repositori')
            ->assertSee('Menampilkan seluruh');
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
        $response->assertSee('Upload Gambar');
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

    public function test_user_can_edit_all_dates_on_incoming_mail(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = IncomingMail::create([
            'mail_number' => 'TEST/SURAT/DATE-01',
            'received_date' => '2026-10-19 10:00:00',
            'sender' => 'Biro Administrasi',
            'recipient' => 'Direktur',
            'status' => 'RECEIVE',
            'subject' => 'Surat Uji Tanggal',
            'outgoing_date' => '2026-10-19 12:00:00',
        ]);

        $response = $this->actingAs($user)->put(route('incoming-mails.update', $mail), [
            'sender' => 'Biro Administrasi Updated',
            'recipient' => 'Direktur',
            'subject' => 'Surat Uji Tanggal',
            'received_date' => '2026-11-01T08:30',
            'outgoing_date' => '2026-11-02T14:45',
        ]);

        $response->assertSessionHasNoErrors();
        $mail->refresh();
        $this->assertEquals('2026-11-01 08:30:00', $mail->received_date->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-02 14:45:00', $mail->outgoing_date->format('Y-m-d H:i:s'));
    }

    public function test_user_can_edit_all_dates_on_incoming_mail_batch_update(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $batchId = 'batch-test-dates-123';
        $mail = IncomingMail::create([
            'batch_id' => $batchId,
            'receipt_number' => 'TT-2026-999',
            'mail_number' => 'TEST/SURAT/BATCH-DATE-01',
            'received_date' => '2026-10-10 10:00:00',
            'sender' => 'Biro Umum',
            'recipient' => 'Wakil Rektor',
            'status' => 'RECEIVE',
            'subject' => 'Surat Batch Awal',
            'outgoing_date' => '2026-10-10 11:00:00',
        ]);

        $response = $this->actingAs($user)->put(route('incoming-mails.batch-update', $batchId), [
            'sender' => 'Biro Umum',
            'received_date' => '2026-11-03T09:15',
            'is_draft' => '0',
            'documents' => [
                [
                    'id' => $mail->id,
                    'mail_number' => 'TEST/SURAT/BATCH-DATE-01',
                    'recipient' => 'Wakil Rektor',
                    'subject' => 'Surat Batch Tanggal Baru',
                    'outgoing_date' => '2026-11-04T16:20',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $mail->refresh();
        $this->assertEquals('2026-11-03 09:15:00', $mail->received_date->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-04 16:20:00', $mail->outgoing_date->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_create_and_edit_custom_status_on_incoming_mail(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Verify default statuses "Tembusan" and "Undangan" are included in create form
        $this->actingAs($user)->get(route('incoming-mails.create'))
            ->assertOk()
            ->assertSee('Tembusan')
            ->assertSee('Undangan');

        $response = $this->actingAs($user)->post(route('incoming-mails.store'), [
            'sender' => 'Biro Akademik',
            'received_date' => '2026-11-05T09:00',
            'documents' => [
                [
                    'mail_number' => 'AKD/2026/001',
                    'recipient' => 'Rektor',
                    'subject' => 'Permohonan Persetujuan Kurikulum',
                    'status' => 'Segera Ditindaklanjuti',
                ],
            ],
        ]);

        $response->assertRedirect(route('incoming-mails.index'));

        $mail = IncomingMail::where('mail_number', 'AKD/2026/001')->firstOrFail();
        $this->assertSame('Segera Ditindaklanjuti', $mail->status);
        $this->assertSame('RECEIVE', $mail->progress);

        $this->actingAs($user)->put(route('incoming-mails.update', $mail), [
            'sender' => 'Biro Akademik',
            'recipient' => 'Rektor',
            'subject' => 'Permohonan Persetujuan Kurikulum',
            'status' => 'Sangat Rahasia',
        ])->assertSessionHasNoErrors();

        $mail->refresh();
        $this->assertSame('Sangat Rahasia', $mail->status);
        $this->assertSame('RECEIVE', $mail->progress);
    }

    public function test_incoming_mail_supports_multiple_recipients_per_document(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->post(route('incoming-mails.store'), [
            'sender' => 'Biro SDM',
            'received_date' => '2026-11-05T10:00',
            'documents' => [
                [
                    'mail_number' => 'SDM/2026/MULTI-01',
                    'recipient' => 'Rektor, Wakil Rektor I, Biro Keuangan',
                    'subject' => 'Undangan Rapat Koordinasi Pimpinan',
                ],
            ],
        ]);

        $response->assertRedirect(route('incoming-mails.index'));

        $mail = IncomingMail::where('mail_number', 'SDM/2026/MULTI-01')->firstOrFail();
        $this->assertSame('Rektor, Wakil Rektor I, Biro Keuangan', $mail->recipient);
        $this->assertSame(['Rektor', 'Wakil Rektor I', 'Biro Keuangan'], $mail->recipients_list);

        // Update using array input & verify deduplication + normalization
        $this->actingAs($user)->put(route('incoming-mails.update', $mail), [
            'sender' => 'Biro SDM',
            'recipient' => ['Rektor', 'Dekan Fakultas Teknik', 'Biro Keuangan', 'rektor'],
            'subject' => 'Undangan Rapat Koordinasi Pimpinan',
        ])->assertSessionHasNoErrors();

        $mail->refresh();
        $this->assertSame('Rektor, Dekan Fakultas Teknik, Biro Keuangan', $mail->recipient);
        $this->assertSame(['Rektor', 'Dekan Fakultas Teknik', 'Biro Keuangan'], $mail->recipients_list);

        // Verify detail page renders all recipients
        $this->actingAs($user)->get(route('incoming-mails.show', $mail))
            ->assertOk()
            ->assertSee('Rektor')
            ->assertSee('Dekan Fakultas Teknik')
            ->assertSee('Biro Keuangan');
    }
}

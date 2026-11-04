<?php

namespace Tests\Feature;

use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\OutgoingMailStatus;
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
            'progress' => 'WAITING_RECEIVE',
        ]);

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'progress' => 'REVISI',
            'revision_note' => 'Halo tes',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->progress);
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
            'progress' => 'WAITING_RECEIVE',
        ]);

        $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'progress' => 'REVISI',
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

    public function test_outgoing_mail_index_uses_lazy_load_chunks_instead_of_pages(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        foreach (range(1, 25) as $i) {
            OutgoingMail::forceCreate([
                'mail_number' => sprintf('LAZY-OUT-%03d', $i),
                'subject' => "Surat Keluar Lazy Load {$i}",
                'recipient' => 'PT Mitra Sejahtera',
                'created_by' => $user->id,
                'progress' => 'READY_TO_DELIVERY',
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($user)->get(route('outgoing-mails.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('loadMoreSentinel', false)
            ->assertSee('LAZY-OUT-001')
            ->assertDontSee('LAZY-OUT-025');

        $this->actingAs($user)->getJson(route('outgoing-mails.index', ['page' => 2]))
            ->assertOk()
            ->assertJson(['current_page' => 2, 'has_more' => false, 'total' => 25])
            ->assertJsonCount(5, 'allIds');

        // Muat ulang seluruh baris yang sudah tampil sekaligus (dibatasi maksimal 200)
        $this->actingAs($user)->getJson(route('outgoing-mails.index', ['per_page' => 40]))
            ->assertOk()
            ->assertJson(['current_page' => 1, 'has_more' => false, 'max_limit' => 200])
            ->assertJsonCount(25, 'allIds');

        // Pastikan markup lazy load memuat teks maksimal 200 dokumen dan teks seluruh dokumen
        $this->actingAs($user)->get(route('outgoing-mails.index'))
            ->assertOk()
            ->assertSee('Maksimal load 200 dokumen, gunakan')
            ->assertSee('halaman repositori')
            ->assertSee('Menampilkan seluruh');
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
        $response->assertSessionHas('success', '1 Surat Keluar berhasil dicatat (Progres: READY TO DELIVERY).');

        $mail = OutgoingMail::where('subject', 'Surat Pengantar Kerjasama Baru')->first();
        $this->assertNotNull($mail);
        $this->assertSame('READY_TO_DELIVERY', $mail->progress);
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
            'progress' => 'READY_TO_DELIVERY',
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
            'progress' => 'REVISI',
        ]);

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'progress' => 'READY_TO_DELIVERY',
            'revision_note' => 'Revisi telah diperbaiki dan siap dikirim ulang',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $this->assertSame('READY_TO_DELIVERY', $mail->fresh()->progress);
    }

    public function test_outgoing_mail_with_receive_status_can_transition_to_revisi(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261027-0101',
            'subject' => 'Surat Selesai Terima',
            'recipient' => 'PT Sumber Makmur',
            'created_by' => $user->id,
            'progress' => 'RECEIVE',
        ]);

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'progress' => 'REVISI',
            'revision_note' => 'Ada lampiran yang perlu diperbaiki ulang',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $this->assertSame('REVISI', $mail->fresh()->progress);
        $this->assertDatabaseHas('outgoing_mail_revision_notes', [
            'outgoing_mail_id' => $mail->id,
            'note' => 'Ada lampiran yang perlu diperbaiki ulang',
        ]);
    }

    public function test_outgoing_mail_index_displays_unlocked_receive_status_and_eye_preview_icon(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261027-0102',
            'subject' => 'Surat Sudah Diterima',
            'recipient' => 'Dinas Pendidikan',
            'created_by' => $user->id,
            'progress' => 'RECEIVE',
        ]);

        $response = $this->actingAs($user)->get(route('outgoing-mails.index'));

        $response->assertOk();
        // Should have status dropdown button for RECEIVE (not locked span)
        $response->assertSee('window.openStatusDropdown(event, \''.$mail->id.'\'', false);
        // Should have eye icon button calling openReceiptPreviewModal
        $response->assertSee('window.openReceiptPreviewModal(\''.$mail->id.'\'', false);
        // Modal for receipt preview should be present
        $response->assertSee('showReceiptPreviewModal');
    }

    public function test_outgoing_mail_can_transition_from_revisi_directly_to_revisi_again(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261028-0201',
            'subject' => 'Surat Sedang Revisi',
            'recipient' => 'PT Semen Padang',
            'created_by' => $user->id,
            'progress' => 'REVISI',
        ]);

        $this->assertTrue(OutgoingMailStatus::canTransition('REVISI', 'REVISI'));

        $response = $this->actingAs($user)->postJson(route('outgoing-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'progress' => 'REVISI',
            'revision_note' => 'Perbaiki perihal dan lampiran kedua.',
        ]);

        $response->assertOk();
        $this->assertSame('REVISI', $mail->fresh()->progress);
        $this->assertDatabaseHas('outgoing_mail_revision_notes', [
            'outgoing_mail_id' => $mail->id,
            'note' => 'Perbaiki perihal dan lampiran kedua.',
        ]);
    }

    public function test_user_can_edit_creation_date_on_outgoing_mail(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20261028-DATE-01',
            'subject' => 'Surat Uji Tanggal Keluar',
            'recipient' => 'PT Mitra Sejahtera',
            'created_by' => $user->id,
            'progress' => 'READY_TO_DELIVERY',
        ]);

        $response = $this->actingAs($user)->put(route('outgoing-mails.update', $mail), [
            'mail_number' => 'SK-20261028-DATE-01',
            'subject' => 'Surat Uji Tanggal Keluar Updated',
            'recipient' => 'PT Mitra Sejahtera',
            'created_at' => '2026-11-02T13:30',
        ]);

        $response->assertSessionHasNoErrors();
        $mail->refresh();
        $this->assertEquals('2026-11-02 13:30:00', $mail->created_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_create_and_edit_custom_status_on_outgoing_mail(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Verify default statuses "Tembusan" and "Undangan" are included in create form
        $this->actingAs($user)->get(route('outgoing-mails.create'))
            ->assertOk()
            ->assertSee('Tembusan')
            ->assertSee('Undangan');

        $response = $this->actingAs($user)->post(route('outgoing-mails.store'), [
            'documents' => [
                [
                    'mail_number' => 'SK-20261105-STAT-01',
                    'subject' => 'Surat Keputusan Direksi',
                    'recipient' => 'Divisi Operasional',
                    'status' => 'Prioritas Utama',
                ],
            ],
        ]);

        $response->assertRedirect(route('outgoing-mails.index'));

        $mail = OutgoingMail::where('mail_number', 'SK-20261105-STAT-01')->firstOrFail();
        $this->assertSame('Prioritas Utama', $mail->status);
        $this->assertSame('READY_TO_DELIVERY', $mail->progress);

        $this->actingAs($user)->put(route('outgoing-mails.update', $mail), [
            'mail_number' => 'SK-20261105-STAT-01',
            'subject' => 'Surat Keputusan Direksi',
            'recipient' => 'Divisi Operasional',
            'status' => 'Konfidensial',
        ])->assertRedirect(route('outgoing-mails.index'));

        $mail->refresh();
        $this->assertSame('Konfidensial', $mail->status);
        $this->assertSame('READY_TO_DELIVERY', $mail->progress);
    }

    public function test_outgoing_mail_supports_multiple_recipients_per_document(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->post(route('outgoing-mails.store'), [
            'documents' => [
                [
                    'mail_number' => 'SK-20261105-MULTI-01',
                    'subject' => 'Edaran Bersama Antar Divisi',
                    'recipient' => 'Divisi Keuangan, Divisi SDM, Divisi Operasional',
                ],
            ],
        ]);

        $response->assertRedirect(route('outgoing-mails.index'));

        $mail = OutgoingMail::where('mail_number', 'SK-20261105-MULTI-01')->firstOrFail();
        $this->assertSame('Divisi Keuangan, Divisi SDM, Divisi Operasional', $mail->recipient);
        $this->assertSame(['Divisi Keuangan', 'Divisi SDM', 'Divisi Operasional'], $mail->recipients_list);

        // Update with array of recipients
        $this->actingAs($user)->put(route('outgoing-mails.update', $mail), [
            'mail_number' => 'SK-20261105-MULTI-01',
            'subject' => 'Edaran Bersama Antar Divisi',
            'recipient' => ['Divisi Keuangan', 'Divisi Hukum', 'Divisi Operasional'],
        ])->assertRedirect(route('outgoing-mails.index'));

        $mail->refresh();
        $this->assertSame('Divisi Keuangan, Divisi Hukum, Divisi Operasional', $mail->recipient);
        $this->assertSame(['Divisi Keuangan', 'Divisi Hukum', 'Divisi Operasional'], $mail->recipients_list);

        $this->actingAs($user)->get(route('outgoing-mails.show', $mail))
            ->assertOk()
            ->assertSee('Divisi Keuangan')
            ->assertSee('Divisi Hukum')
            ->assertSee('Divisi Operasional');
    }
}

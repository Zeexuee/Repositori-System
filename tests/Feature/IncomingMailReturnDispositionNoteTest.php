<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\User;
use App\Support\IncomingMailStatus;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The disposition workflow was merged into the return workflow. The return form
 * now collects an optional disposition note alongside the recipient signatures.
 */
class IncomingMailReturnDispositionNoteTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createMail(string $status = 'RECEIVE'): IncomingMail
    {
        return IncomingMail::create([
            'mail_number' => 'DISP/TEST/'.fake()->unique()->numerify('####'),
            'received_date' => '2026-10-19',
            'sender' => 'Kementerian Sekretariat Negara',
            'recipient' => 'Direktur Utama',
            'progress' => $status,
            'subject' => 'Disposisi Dokumen Uji Coba',
        ]);
    }

    private function samplePngBase64(): string
    {
        $image = UploadedFile::fake()->image('signature.png', 100, 50);

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($image->getPathname()));
    }

    public function test_return_progress_stores_the_disposition_note_as_waiting_return(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'disposition_note' => 'Mohon dipelajari dan disiapkan draft tanggapan.',
            'action' => 'save_later',
            'recipients' => [
                [
                    'recipient_name' => 'Divisi Hukum',
                    'signer_name' => 'Budi Santoso',
                    'signature' => $this->samplePngBase64(),
                ],
                [
                    'recipient_name' => 'Divisi Keuangan',
                    'signer_name' => null,
                    'signature' => null,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => IncomingMailStatus::WAITING_RETURN,
        ]);

        $mail->refresh();
        $this->assertSame(IncomingMailStatus::WAITING_RETURN, $mail->progress);
        $this->assertSame('Mohon dipelajari dan disiapkan draft tanggapan.', $mail->disposition_note);
        $this->assertCount(2, $mail->returnRecipients);

        $first = $mail->returnRecipients->first();
        $this->assertSame('Divisi Hukum', $first->recipient_name);
        $this->assertSame('Budi Santoso', $first->signer_name);
        $this->assertNotNull($first->signature_path);
        $this->assertNotNull($first->signed_at);
        Storage::disk('local')->assertExists($first->signature_path);
    }

    public function test_completing_a_return_keeps_the_disposition_note(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $mail = $this->createMail('WAITING_RETURN');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'disposition_note' => 'Disposisi telah selesai diparaf seluruh unit.',
            'action' => 'complete',
            'recipients' => [
                [
                    'recipient_name' => 'Divisi Umum',
                    'signer_name' => 'Ahmad Fauzi',
                    'signature' => $this->samplePngBase64(),
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => IncomingMailStatus::RETURN,
        ]);

        $mail->refresh();
        $this->assertSame(IncomingMailStatus::RETURN, $mail->progress);
        $this->assertSame('Disposisi telah selesai diparaf seluruh unit.', $mail->disposition_note);
        $this->assertCount(1, $mail->returnRecipients);
        $this->assertNotNull($mail->returnRecipients->first()->signature_path);
    }

    public function test_disposition_note_is_optional_when_returning(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertStatus(200);

        $mail->refresh();
        $this->assertSame(IncomingMailStatus::RETURN, $mail->progress);
        $this->assertNull($mail->disposition_note);
    }

    public function test_an_empty_submitted_note_clears_the_stored_note(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');
        $mail->update(['disposition_note' => 'Catatan lama']);

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'disposition_note' => '',
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertStatus(200);

        $this->assertNull($mail->fresh()->disposition_note);
    }

    public function test_omitting_the_note_preserves_the_stored_note(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');
        $mail->update(['disposition_note' => 'Catatan lama yang harus bertahan']);

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertStatus(200);

        $this->assertSame('Catatan lama yang harus bertahan', $mail->fresh()->disposition_note);
    }

    public function test_return_recipients_endpoint_exposes_the_disposition_note(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RETURN');
        $mail->update(['disposition_note' => 'Catatan penting disposisi']);
        $mail->returnRecipients()->create([
            'recipient_name' => 'Biro Keuangan',
            'signer_name' => 'Siti Rahma',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->getJson(route('incoming-mails.return-recipients.show', $mail));

        $response->assertStatus(200);
        $response->assertJsonPath('mail.mail_number', $mail->mail_number);
        $response->assertJsonPath('mail.disposition_note', 'Catatan penting disposisi');
        $response->assertJsonPath('recipients.0.recipient_name', 'Biro Keuangan');
        $response->assertJsonPath('recipients.0.signer_name', 'Siti Rahma');
    }

    public function test_disposition_note_and_recipients_are_rendered_in_show_page(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RETURN');
        $mail->update(['disposition_note' => 'Catatan Khusus Disposisi Direksi']);
        $mail->returnRecipients()->create([
            'recipient_name' => 'Biro Perencanaan',
            'signer_name' => 'Ir. Hendra',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('incoming-mails.show', $mail));

        $response->assertStatus(200);
        $response->assertSee('Informasi Catatan Disposisi');
        $response->assertSee('Catatan Khusus Disposisi Direksi');
        $response->assertSee('Biro Perencanaan');
        $response->assertSee('Ir. Hendra');
    }

    public function test_legacy_disposition_recipients_are_still_rendered_in_show_page(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RETURN');
        $mail->dispositionRecipients()->create([
            'recipient_name' => 'Biro Warisan Lama',
            'signer_name' => 'Pak Lurah',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('incoming-mails.show', $mail));

        $response->assertStatus(200);
        $response->assertSee('Biro Warisan Lama');
        $response->assertSee('Pak Lurah');
    }

    public function test_table_renders_the_peek_button_for_returned_documents(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RETURN');
        $mail->update(['disposition_note' => 'Disposisi Terlaksana']);

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertStatus(200);
        $response->assertSee('openDispositionPreviewModal');
        $response->assertSee('Intip catatan disposisi & penerima dokumen', false);
    }

    public function test_return_modal_exposes_the_optional_disposition_note_field(): void
    {
        $user = $this->actor();
        $this->createMail('RECEIVE');

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertStatus(200);
        $response->assertSee('return-disposition-note');
        $response->assertSee('returnDispositionNote');
    }

    public function test_return_recipients_show_endpoint_includes_sender_information(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('WAITING_RETURN');
        $mail->update(['sender' => 'Fakultas Ilmu Komputer']);

        $response = $this->actingAs($user)->getJson(route('incoming-mails.return-recipients.show', $mail));

        $response->assertOk();
        $response->assertJsonPath('mail.sender', 'Fakultas Ilmu Komputer');
    }

    public function test_return_recipients_show_endpoint_supports_batch_mail_ids(): void
    {
        $user = $this->actor();
        $mail1 = $this->createMail('WAITING_RETURN');
        $mail1->update(['sender' => 'Biro Perencanaan']);

        $mail2 = $this->createMail('WAITING_RETURN');
        $mail2->update(['sender' => 'Biro Perencanaan']);

        $response = $this->actingAs($user)->getJson(
            route('incoming-mails.return-recipients.show', $mail1)."?mail_ids={$mail1->id},{$mail2->id}"
        );

        $response->assertOk();
        $response->assertJsonPath('mail.sender', 'Biro Perencanaan');
    }

    public function test_table_renders_data_sender_and_auto_fill_hint(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');
        $mail->update(['sender' => 'Biro Kemahasiswaan']);

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertOk();
        $response->assertSee('data-sender="Biro Kemahasiswaan"', false);
        $response->assertSee('(Otomatis dari pengirim)');
    }

    public function test_show_endpoint_includes_attachments_for_incoming_mail(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');
        $mail->attachments()->create([
            'file_path' => 'incoming-mails/attachments/test-doc.pdf',
            'file_name' => 'test-doc.pdf',
            'file_size' => 10240,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($user)->getJson(route('incoming-mails.return-recipients.show', $mail));

        $response->assertOk();
        $response->assertJsonStructure([
            'mail',
            'attachments',
            'recipients',
        ]);
        $response->assertJsonCount(1, 'attachments');
        $response->assertJsonPath('attachments.0.file_name', 'test-doc.pdf');
        $response->assertJsonPath('attachments.0.mime_type', 'application/pdf');
    }

    public function test_table_renders_peek_button_when_mail_has_attachments(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE'); // Not RETURN and no disposition_note
        $mail->attachments()->create([
            'file_path' => 'incoming-mails/attachments/foto-lampiran.jpg',
            'file_name' => 'foto-lampiran.jpg',
            'file_size' => 20480,
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertOk();
        $response->assertSee('openDispositionPreviewModal');
        $response->assertSee('Berkas Dokumen Terunggah');
    }
}

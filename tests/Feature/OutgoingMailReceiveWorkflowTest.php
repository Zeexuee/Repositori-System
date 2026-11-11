<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutgoingMailReceiveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1x1 PNG used as a stand-in for the canvas signature.
     */
    private const SIGNATURE_DATA_URL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => Roles::STAFF]));

        return $user;
    }

    private function outgoingMail(User $creator, string $status = 'WAITING_RECEIVE'): OutgoingMail
    {
        return OutgoingMail::create([
            'mail_number' => 'SK-UJI-'.fake()->unique()->numerify('####'),
            'subject' => 'Surat Uji Penerimaan',
            'recipient' => 'PT Mitra',
            'created_by' => $creator->id,
            'progress' => $status,
        ]);
    }

    public function test_receive_workflow_stores_the_optional_disposition_note(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);

        $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'disposition_note' => 'Arsipkan setelah diterima penerima.',
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'PT Mitra']],
        ])->assertOk();

        $this->assertSame('Arsipkan setelah diterima penerima.', $mail->fresh()->disposition_note);
    }

    public function test_omitting_the_note_preserves_the_stored_note(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);
        $mail->update(['disposition_note' => 'Catatan lama yang harus bertahan']);

        $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'PT Mitra']],
        ])->assertOk();

        $this->assertSame('Catatan lama yang harus bertahan', $mail->fresh()->disposition_note);
    }

    public function test_receive_recipients_endpoint_exposes_the_disposition_note(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);
        $mail->update(['disposition_note' => 'Catatan disposisi surat keluar']);

        $this->actingAs($staff)
            ->getJson(route('outgoing-mails.return-recipients.show', $mail))
            ->assertOk()
            ->assertJsonPath('mail.disposition_note', 'Catatan disposisi surat keluar');
    }

    public function test_completing_receive_workflow_finalizes_mail_to_receive(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);

        $response = $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'complete',
            'recipients' => [
                [
                    'recipient_name' => 'Budi Santoso',
                    'signer_name' => 'Budi Santoso',
                    'signature' => self::SIGNATURE_DATA_URL,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'RECEIVE',
        ]);

        $mail->refresh();

        $this->assertSame('RECEIVE', $mail->progress);
        $this->assertCount(1, $mail->returnRecipients);
        $this->assertNotNull($mail->returnRecipients->first()->signed_at);
        Storage::disk('local')->assertExists($mail->returnRecipients->first()->signature_path);
    }

    public function test_skip_remaining_finalizes_mail_without_complete_signatures(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);

        $response = $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [
                ['recipient_name' => 'Siti Aminah'],
            ],
        ]);

        $response->assertOk();

        $mail->refresh();

        $this->assertSame('RECEIVE', $mail->progress);
        $this->assertNull($mail->returnRecipients->first()->signed_at);
    }

    public function test_complete_action_requires_signature_for_every_recipient(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);

        $response = $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'complete',
            'recipients' => [
                ['recipient_name' => 'Siti Aminah'],
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertSame('WAITING_RECEIVE', $mail->refresh()->progress);
        $this->assertCount(0, $mail->returnRecipients);
    }

    public function test_finalized_mail_cannot_reenter_receive_workflow(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);

        $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [
                ['recipient_name' => 'Siti Aminah'],
            ],
        ])->assertOk();

        $this->actingAs($staff)->postJson(route('outgoing-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'complete',
            'recipients' => [
                [
                    'recipient_name' => 'Siti Aminah',
                    'signer_name' => 'Siti Aminah',
                    'signature' => self::SIGNATURE_DATA_URL,
                ],
            ],
        ])->assertForbidden();

        $this->assertSame('RECEIVE', $mail->refresh()->progress);
    }

    public function test_show_endpoint_includes_attachments_for_outgoing_mail(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff);
        $mail->attachments()->create([
            'file_path' => 'outgoing-mails/attachments/draft-sk.pdf',
            'file_name' => 'draft-sk.pdf',
            'file_size' => 15360,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($staff)->getJson(route('outgoing-mails.return-recipients.show', $mail));

        $response->assertOk();
        $response->assertJsonStructure([
            'mail',
            'attachments',
            'recipients',
        ]);
        $response->assertJsonCount(1, 'attachments');
        $response->assertJsonPath('attachments.0.file_name', 'draft-sk.pdf');
    }

    public function test_outgoing_table_renders_peek_button_when_mail_has_attachments(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail($staff, 'READY_TO_DELIVERY'); // Not RECEIVE and no returnRecipients and no disposition_note
        $mail->attachments()->create([
            'file_path' => 'outgoing-mails/attachments/lampiran-surat.pdf',
            'file_name' => 'lampiran-surat.pdf',
            'file_size' => 12000,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($staff)->get(route('outgoing-mails.index'));

        $response->assertOk();
        $response->assertSee('openReceiptPreviewModal');
        $response->assertSee('Berkas Dokumen Terunggah');
    }
}

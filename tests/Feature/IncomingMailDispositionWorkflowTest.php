<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\User;
use App\Support\IncomingMailStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncomingMailDispositionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
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
            'status' => $status,
            'subject' => 'Disposisi Dokumen Uji Coba',
        ]);
    }

    private function samplePngBase64(): string
    {
        $image = UploadedFile::fake()->image('signature.png', 100, 50);

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($image->getPathname()));
    }

    public function test_user_can_save_disposition_progress_as_waiting_disposisi(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $mail = $this->createMail('RECEIVE');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.disposition-workflow.store'), [
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
            'status' => IncomingMailStatus::WAITING_DISPOSISI,
        ]);

        $mail->refresh();
        $this->assertSame(IncomingMailStatus::WAITING_DISPOSISI, $mail->status);
        $this->assertSame('Mohon dipelajari dan disiapkan draft tanggapan.', $mail->disposition_note);
        $this->assertCount(2, $mail->dispositionRecipients);

        $first = $mail->dispositionRecipients->first();
        $this->assertSame('Divisi Hukum', $first->recipient_name);
        $this->assertSame('Budi Santoso', $first->signer_name);
        $this->assertNotNull($first->signature_path);
        $this->assertNotNull($first->signed_at);
        Storage::disk('local')->assertExists($first->signature_path);
    }

    public function test_user_can_complete_disposition_workflow(): void
    {
        Storage::fake('local');
        $user = $this->actor();
        $mail = $this->createMail('WAITING_DISPOSISI');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.disposition-workflow.store'), [
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
            'status' => IncomingMailStatus::DISPOSISI,
        ]);

        $mail->refresh();
        $this->assertSame(IncomingMailStatus::DISPOSISI, $mail->status);
        $this->assertSame('Disposisi telah selesai diparaf seluruh unit.', $mail->disposition_note);
        $this->assertCount(1, $mail->dispositionRecipients);
        $this->assertNotNull($mail->dispositionRecipients->first()->signature_path);
    }

    public function test_disposition_recipients_can_be_retrieved_via_show(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('DISPOSISI');
        $mail->update(['disposition_note' => 'Catatan penting disposisi']);
        $mail->dispositionRecipients()->create([
            'recipient_name' => 'Biro Keuangan',
            'signer_name' => 'Siti Rahma',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->getJson(route('incoming-mails.disposition-recipients.show', $mail));

        $response->assertStatus(200);
        $response->assertJsonPath('mail.mail_number', $mail->mail_number);
        $response->assertJsonPath('mail.disposition_note', 'Catatan penting disposisi');
        $response->assertJsonPath('recipients.0.recipient_name', 'Biro Keuangan');
        $response->assertJsonPath('recipients.0.signer_name', 'Siti Rahma');
    }

    public function test_disposition_note_and_recipients_are_rendered_in_show_page(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('DISPOSISI');
        $mail->update(['disposition_note' => 'Catatan Khusus Disposisi Direksi']);
        $mail->dispositionRecipients()->create([
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

    public function test_table_renders_intip_button_for_disposisi_status(): void
    {
        $user = $this->actor();
        $mail = $this->createMail('DISPOSISI');
        $mail->update(['disposition_note' => 'Disposisi Terlaksana']);

        $response = $this->actingAs($user)->get(route('incoming-mails.index'));

        $response->assertStatus(200);
        $response->assertSee('openDispositionPreviewModal');
        $response->assertSee('Klik untuk intip catatan status disposisi');
    }
}

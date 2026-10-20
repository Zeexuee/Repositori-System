<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function userWithRole(?string $role = Roles::STAFF): User
    {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->assignRole(Role::firstOrCreate(['name' => $role]));
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function incomingMail(array $attributes = []): IncomingMail
    {
        return IncomingMail::create(array_merge([
            'mail_number' => 'SM-UJI-'.fake()->unique()->numerify('####'),
            'received_date' => '2026-10-21 09:00:00',
            'sender' => 'Biro Umum',
            'recipient' => 'Direktur Utama',
            'subject' => 'Dokumen Uji Unduh',
            'status' => 'RECEIVE',
        ], $attributes));
    }

    public function test_staff_can_download_document_owned_by_incoming_mail(): void
    {
        Storage::disk('local')->put('incoming-mails/dokumen.pdf', 'isi dokumen');
        $mail = $this->incomingMail(['file_path' => 'incoming-mails/dokumen.pdf']);

        $this->actingAs($this->userWithRole())
            ->get(route('document.download', ['path' => $mail->file_path]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_staff_can_preview_document_inline(): void
    {
        Storage::disk('local')->put('incoming-mails/dokumen.pdf', 'isi dokumen');
        $mail = $this->incomingMail(['file_path' => 'incoming-mails/dokumen.pdf']);

        $this->actingAs($this->userWithRole())
            ->get(route('document.download', ['path' => $mail->file_path, 'inline' => 1]))
            ->assertOk();
    }

    public function test_path_without_owning_document_is_not_downloadable(): void
    {
        Storage::disk('local')->put('incoming-mails/orphan.pdf', 'isi file tanpa pemilik');

        $this->actingAs($this->userWithRole())
            ->get(route('document.download', ['path' => 'incoming-mails/orphan.pdf']))
            ->assertNotFound();
    }

    public function test_signature_path_resolves_through_return_recipient(): void
    {
        Storage::disk('local')->put('incoming-mails/signatures/ttd.png', 'isi tanda tangan');
        $user = $this->userWithRole();
        $mail = $this->incomingMail();

        $mail->returnRecipients()->create([
            'recipient_name' => 'Divisi Keuangan',
            'signer_name' => 'Budi Santoso',
            'signature_path' => 'incoming-mails/signatures/ttd.png',
            'signed_at' => now(),
            'sort_order' => 0,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('document.download', [
                'path' => 'incoming-mails/signatures/ttd.png',
                'inline' => 1,
            ]))
            ->assertOk();
    }

    public function test_user_without_document_role_is_forbidden(): void
    {
        Storage::disk('local')->put('incoming-mails/dokumen.pdf', 'isi dokumen');
        $mail = $this->incomingMail(['file_path' => 'incoming-mails/dokumen.pdf']);

        $this->actingAs($this->userWithRole(null))
            ->get(route('document.download', ['path' => $mail->file_path]))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        Storage::disk('local')->put('incoming-mails/dokumen.pdf', 'isi dokumen');
        $mail = $this->incomingMail(['file_path' => 'incoming-mails/dokumen.pdf']);

        $this->get(route('document.download', ['path' => $mail->file_path]))
            ->assertRedirect(route('login'));
    }
}

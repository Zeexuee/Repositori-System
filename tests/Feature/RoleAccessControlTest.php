<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessDigitalSignatureJob;
use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $role]));

        return $user;
    }

    private function staff(): User
    {
        return $this->userWithRole(Roles::STAFF);
    }

    private function master(): User
    {
        return $this->userWithRole(Roles::MASTER);
    }

    private function incomingMail(string $status = 'RECEIVE'): IncomingMail
    {
        return IncomingMail::create([
            'mail_number' => 'RBAC/IN/'.fake()->unique()->numerify('####'),
            'received_date' => '2026-10-19 10:00:00',
            'sender' => 'Biro Umum',
            'recipient' => 'Direktur Utama',
            'status' => $status,
            'subject' => 'Dokumen Uji RBAC '.fake()->unique()->numerify('####'),
        ]);
    }

    private function outgoingMail(string $status = 'WAITING_RECEIVE'): OutgoingMail
    {
        $creator = $this->staff();

        return OutgoingMail::create([
            'mail_number' => 'RBAC/OUT/'.fake()->unique()->numerify('####'),
            'subject' => 'Dokumen Keluar Uji RBAC',
            'recipient' => 'PT Mitra',
            'created_by' => $creator->id,
            'status' => $status,
        ]);
    }

    public function test_staff_cannot_delete_incoming_mail(): void
    {
        $staff = $this->staff();
        $mail = $this->incomingMail();

        $this->actingAs($staff)
            ->delete(route('incoming-mails.destroy', $mail))
            ->assertForbidden();

        $this->assertNotSoftDeleted('incoming_mails', ['id' => $mail->id]);
    }

    public function test_master_can_delete_incoming_mail(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail();

        $this->actingAs($master)
            ->delete(route('incoming-mails.destroy', $mail))
            ->assertRedirect(route('incoming-mails.index'));

        $this->assertSoftDeleted('incoming_mails', ['id' => $mail->id]);
    }

    public function test_staff_cannot_delete_outgoing_mail(): void
    {
        $staff = $this->staff();
        $mail = $this->outgoingMail();

        $this->actingAs($staff)
            ->delete(route('outgoing-mails.destroy', $mail))
            ->assertForbidden();

        $this->assertNotSoftDeleted('outgoing_mails', ['id' => $mail->id]);
    }

    public function test_master_can_delete_outgoing_mail(): void
    {
        $master = $this->master();
        $mail = $this->outgoingMail();

        $this->actingAs($master)
            ->delete(route('outgoing-mails.destroy', $mail))
            ->assertRedirect(route('outgoing-mails.index'));

        $this->assertSoftDeleted('outgoing_mails', ['id' => $mail->id]);
    }

    public function test_staff_cannot_manually_finalize_disposisi(): void
    {
        $staff = $this->staff();
        $mail = $this->incomingMail('RECEIVE');

        $response = $this->actingAs($staff)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'DISPOSISI',
            'disposition_note' => 'Mencoba menutup disposisi secara manual.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'updated_count' => 0,
        ]);

        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }

    public function test_master_can_manually_finalize_disposisi(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail('RECEIVE');

        $response = $this->actingAs($master)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'DISPOSISI',
            'disposition_note' => 'Disposisi ditutup oleh Direksi.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated_count' => 1,
        ]);

        $this->assertSame('DISPOSISI', $mail->fresh()->status);
    }

    public function test_final_documents_are_locked_even_for_master(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail('DISPOSISI');

        $this->actingAs($master)->get(route('incoming-mails.edit', $mail))->assertForbidden();

        $this->actingAs($master)->postJson(route('incoming-mails.attachments.store', $mail), [
            'files' => [],
        ])->assertForbidden();
    }

    public function test_staff_cannot_access_direksi_only_modules(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('broadcast-emails.index'))->assertForbidden();
    }

    public function test_master_can_access_direksi_only_modules(): void
    {
        $master = $this->master();

        $this->actingAs($master)->get(route('audit-logs.index'))->assertOk();
        $this->actingAs($master)->get(route('users.index'))->assertOk();
        $this->actingAs($master)->get(route('broadcast-emails.index'))->assertOk();
    }

    public function test_staff_cannot_sign_outgoing_mail(): void
    {
        Queue::fake();

        $staff = $this->staff();
        $mail = $this->outgoingMail('APPROVED');

        $this->actingAs($staff)
            ->post(route('outgoing-mails.sign', $mail))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_master_can_sign_outgoing_mail(): void
    {
        Queue::fake();

        $master = $this->master();
        $mail = $this->outgoingMail('APPROVED');

        $this->actingAs($master)
            ->post(route('outgoing-mails.sign', $mail))
            ->assertRedirect(route('outgoing-mails.show', $mail));

        Queue::assertPushed(ProcessDigitalSignatureJob::class);
    }

    public function test_master_can_create_staff_user_with_canonical_role(): void
    {
        $master = $this->master();
        Role::firstOrCreate(['name' => Roles::STAFF]);

        $this->actingAs($master)->post(route('users.store'), [
            'name' => 'Staf Baru',
            'email' => 'staf.baru@example.com',
            'password' => 'password123',
            'role' => Roles::STAFF,
        ])->assertRedirect(route('users.index'));

        $created = User::where('email', 'staf.baru@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole(Roles::STAFF));
    }

    public function test_staff_cannot_create_incoming_mail_directly_as_disposisi(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('incoming-mails.store'), [
            'sender' => 'Biro Umum',
            'received_date' => '2026-10-19T10:00',
            'mail_number' => 'RBAC/CREATE/001',
            'subject' => 'Surat Langsung Final',
            'status' => 'DISPOSISI',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('incoming_mails', ['mail_number' => 'RBAC/CREATE/001']);
    }

    public function test_staff_cannot_transition_to_disposisi_through_edit_form(): void
    {
        $staff = $this->staff();
        $mail = $this->incomingMail('RECEIVE');

        $this->actingAs($staff)->put(route('incoming-mails.update', $mail), [
            'status' => 'DISPOSISI',
            'disposition_note' => 'Mencoba memaksa status final.',
        ])->assertSessionHasErrors('status');

        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }

    public function test_master_can_create_incoming_mail_directly_as_disposisi(): void
    {
        $master = $this->master();

        $this->actingAs($master)->post(route('incoming-mails.store'), [
            'sender' => 'Biro Umum',
            'received_date' => '2026-10-19T10:00',
            'mail_number' => 'RBAC/CREATE/002',
            'subject' => 'Surat Final oleh Direksi',
            'status' => 'DISPOSISI',
            'disposition_note' => 'Langsung didisposisikan.',
        ])->assertRedirect(route('incoming-mails.index'));

        $this->assertDatabaseHas('incoming_mails', [
            'mail_number' => 'RBAC/CREATE/002',
            'status' => 'DISPOSISI',
        ]);
    }

    public function test_legacy_role_names_are_rejected_by_user_management(): void
    {
        $master = $this->master();

        $this->actingAs($master)->post(route('users.store'), [
            'name' => 'Staf Lama',
            'email' => 'staf.lama@example.com',
            'password' => 'password123',
            'role' => 'Staf',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'staf.lama@example.com']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_staff_cannot_set_return_manually(): void
    {
        $staff = $this->staff();
        $mail = $this->incomingMail('RECEIVE');

        $response = $this->actingAs($staff)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RETURN',
        ]);

        $response->assertStatus(422);
        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }

    public function test_master_cannot_set_return_manually_either(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail('RECEIVE');

        // RETURN is only reachable through the return signature workflow, which
        // is also where the optional disposition note is collected.
        $this->actingAs($master)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RETURN',
        ])->assertStatus(422);

        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }

    public function test_master_can_finalize_a_return_through_the_workflow(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail('RECEIVE');

        $this->actingAs($master)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'disposition_note' => 'Disposisi ditutup oleh Direksi.',
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertOk()->assertJson(['success' => true, 'status' => 'RETURN']);

        $mail->refresh();
        $this->assertSame('RETURN', $mail->status);
        $this->assertSame('Disposisi ditutup oleh Direksi.', $mail->disposition_note);
    }

    public function test_legacy_completed_documents_stay_locked_even_for_master(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail('COMPLETED');

        $this->actingAs($master)->get(route('incoming-mails.edit', $mail))->assertForbidden();

        $this->actingAs($master)->postJson(route('incoming-mails.attachments.store', $mail), [
            'files' => [],
        ])->assertForbidden();
    }

    public function test_returned_documents_remain_editable(): void
    {
        $master = $this->master();
        $mail = $this->incomingMail('RETURN');

        $this->actingAs($master)->get(route('incoming-mails.edit', $mail))->assertOk();
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

    public function test_nobody_can_create_incoming_mail_directly_as_return(): void
    {
        foreach ([$this->staff(), $this->master()] as $index => $user) {
            $mailNumber = 'RBAC/CREATE/00'.($index + 1);

            $this->actingAs($user)->post(route('incoming-mails.store'), [
                'sender' => 'Biro Umum',
                'received_date' => '2026-10-19T10:00',
                'mail_number' => $mailNumber,
                'subject' => 'Surat Langsung Dikembalikan',
                'status' => 'RETURN',
            ])->assertSessionHasErrors('status');

            $this->assertDatabaseMissing('incoming_mails', ['mail_number' => $mailNumber]);
        }
    }

    public function test_staff_cannot_transition_to_return_through_edit_form(): void
    {
        $staff = $this->staff();
        $mail = $this->incomingMail('RECEIVE');

        $this->actingAs($staff)->put(route('incoming-mails.update', $mail), [
            'status' => 'RETURN',
            'disposition_note' => 'Mencoba memaksa status pengembalian.',
        ])->assertSessionHasErrors('status');

        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }

    public function test_incoming_mail_can_still_be_created_with_a_disposition_note(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('incoming-mails.store'), [
            'sender' => 'Biro Umum',
            'received_date' => '2026-10-19T10:00',
            'mail_number' => 'RBAC/CREATE/003',
            'subject' => 'Surat dengan Catatan Disposisi',
            'status' => 'RECEIVE',
            'disposition_note' => 'Mohon ditindaklanjuti Divisi Keuangan.',
        ])->assertRedirect(route('incoming-mails.index'));

        $this->assertDatabaseHas('incoming_mails', [
            'mail_number' => 'RBAC/CREATE/003',
            'status' => 'RECEIVE',
            'disposition_note' => 'Mohon ditindaklanjuti Divisi Keuangan.',
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

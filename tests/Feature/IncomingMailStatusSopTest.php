<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\IncomingMailRevisionNote;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\IncomingMailStatus;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncomingMailStatusSopTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => Roles::STAFF]));

        return $user;
    }

    private function master(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => Roles::MASTER]));

        return $user;
    }

    private function mailWithStatus(string $status): IncomingMail
    {
        return IncomingMail::create([
            'mail_number' => 'SOP/'.fake()->unique()->numerify('####'),
            'received_date' => '2026-09-20',
            'sender' => 'Biro Umum',
            'recipient' => 'Direktur Utama',
            'status' => $status,
            'subject' => 'Perihal '.fake()->unique()->numerify('####'),
        ]);
    }

    public function test_transition_matrix_matches_the_standard_operating_procedure(): void
    {
        $this->assertSame(
            ['RECEIVE', 'REVISI', 'DISPOSISI'],
            IncomingMailStatus::allowedTransitions('DRAFT')
        );
        $this->assertSame(
            ['REVISI', 'RETURN', 'DISPOSISI'],
            IncomingMailStatus::allowedTransitions('RECEIVE')
        );
        $this->assertSame(
            ['RECEIVE', 'DISPOSISI'],
            IncomingMailStatus::allowedTransitions('REVISI')
        );
        $this->assertSame(
            ['RETURN'],
            IncomingMailStatus::allowedTransitions('WAITING_RETURN')
        );
        $this->assertSame(
            ['REVISI', 'DISPOSISI'],
            IncomingMailStatus::allowedTransitions('RETURN')
        );
        $this->assertSame([], IncomingMailStatus::allowedTransitions('DISPOSISI'));
    }

    public function test_retired_progres_status_is_interpreted_as_draft(): void
    {
        foreach (['PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING'] as $legacy) {
            $this->assertSame('DRAFT', IncomingMailStatus::canonical($legacy));
        }
    }

    public function test_return_cannot_be_changed_back_to_draft(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RETURN');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'DRAFT',
        ]);

        $response->assertStatus(422);
        $this->assertSame('RETURN', $mail->fresh()->status);
    }

    public function test_disposisi_is_a_final_status(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('DISPOSISI');

        $response = $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RECEIVE',
        ]);

        $response->assertStatus(422);
        $this->assertSame('DISPOSISI', $mail->fresh()->status);
    }

    public function test_entering_disposisi_requires_a_disposition_note_and_stores_it(): void
    {
        $user = $this->master();
        $mail = $this->mailWithStatus('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'DISPOSISI',
        ])->assertStatus(422)->assertJsonValidationErrors('disposition_note');

        $this->assertSame('RECEIVE', $mail->fresh()->status);

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'DISPOSISI',
            'disposition_note' => 'Mohon ditindaklanjuti Divisi Keuangan.',
        ])->assertStatus(200);

        $mail->refresh();
        $this->assertSame('DISPOSISI', $mail->status);
        $this->assertSame('Mohon ditindaklanjuti Divisi Keuangan.', $mail->disposition_note);
    }

    public function test_entering_revisi_requires_a_note_and_records_it(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
        ])->assertStatus(422);

        $this->assertSame('RECEIVE', $mail->fresh()->status);
        $this->assertSame(0, $mail->revisionNotes()->count());

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
            'revision_note' => 'Tanda tangan pengirim belum ada.',
        ])->assertStatus(200);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->status);

        $note = $mail->revisionNotes()->first();
        $this->assertSame(IncomingMailRevisionNote::TYPE_REQUESTED, $note->type);
        $this->assertSame('Tanda tangan pengirim belum ada.', $note->note);
    }

    public function test_leaving_revisi_stores_an_optional_resolution_note(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('REVISI');

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RECEIVE',
            'revision_note' => 'Tanda tangan sudah dilengkapi.',
        ])->assertStatus(200);

        $mail->refresh();
        $this->assertSame('RECEIVE', $mail->status);

        $note = $mail->revisionNotes()->first();
        $this->assertSame(IncomingMailRevisionNote::TYPE_RESOLVED, $note->type);
        $this->assertSame('Tanda tangan sudah dilengkapi.', $note->note);
    }

    public function test_leaving_revisi_without_a_note_is_allowed(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('REVISI');

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RECEIVE',
        ])->assertStatus(200);

        $this->assertSame('RECEIVE', $mail->fresh()->status);
        $this->assertSame(0, $mail->revisionNotes()->count());
    }

    public function test_return_status_cannot_be_set_without_the_signature_workflow(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RETURN',
        ])->assertStatus(422);

        $this->actingAs($user)->put(route('incoming-mails.update', $mail), [
            'status' => 'RETURN',
        ])->assertSessionHasErrors('status');

        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }

    public function test_skipping_remaining_signatures_finalizes_the_return(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [
                ['recipient_name' => 'Divisi Keuangan'],
                ['recipient_name' => 'Divisi Hukum'],
            ],
        ])->assertStatus(200)->assertJson(['status' => 'RETURN']);

        $mail->refresh();
        $this->assertSame('RETURN', $mail->status);
        $this->assertSame(2, $mail->returnRecipients()->count());
        $this->assertSame(2, $mail->returnRecipients()->whereNull('signed_at')->count());
        $this->assertSame(0, OutgoingMail::count());
    }

    public function test_waiting_return_is_reached_by_saving_progress_for_later(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'save_later',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertStatus(200)->assertJson(['status' => 'WAITING_RETURN']);

        $this->assertSame('WAITING_RETURN', $mail->fresh()->status);
    }

    public function test_completing_a_return_requires_every_signature(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RECEIVE');

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'complete',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertStatus(422);

        $this->assertSame('RECEIVE', $mail->fresh()->status);
    }
}

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
            ['RECEIVE', 'REVISI', 'RETURN'],
            IncomingMailStatus::allowedTransitions('DRAFT')
        );
        $this->assertSame(
            ['REVISI', 'RETURN'],
            IncomingMailStatus::allowedTransitions('RECEIVE')
        );
        $this->assertSame(
            ['RECEIVE'],
            IncomingMailStatus::allowedTransitions('REVISI')
        );
        $this->assertSame(
            ['RETURN'],
            IncomingMailStatus::allowedTransitions('WAITING_RETURN')
        );
        $this->assertSame(
            ['REVISI'],
            IncomingMailStatus::allowedTransitions('RETURN')
        );
    }

    public function test_retired_disposition_statuses_are_merged_into_the_return_family(): void
    {
        foreach (['DISPOSISI', 'DISPOSITION', 'DISPATCHED'] as $legacy) {
            $this->assertSame('RETURN', IncomingMailStatus::canonical($legacy));
        }

        foreach ([
            'WAITING_DISPOSISI',
            'WAITING DISPOSISI',
            'WAITING_DISPOSITION',
            'WAITING DISPOSITION',
            'PENDING_DISPOSITION',
        ] as $legacy) {
            $this->assertSame('WAITING_RETURN', IncomingMailStatus::canonical($legacy));
        }

        $this->assertSame(
            ['DRAFT', 'RECEIVE', 'REVISI', 'WAITING_RETURN', 'RETURN'],
            IncomingMailStatus::all()
        );
    }

    public function test_return_and_waiting_return_are_not_manually_assignable(): void
    {
        $this->assertSame(
            ['DRAFT', 'RECEIVE', 'REVISI'],
            IncomingMailStatus::manuallyAssignable()
        );
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

    public function test_a_returned_document_can_still_be_sent_back_for_revision(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('RETURN');

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
            'revision_note' => 'Berkas yang dikembalikan kurang satu lampiran.',
        ])->assertStatus(200);

        $this->assertSame('REVISI', $mail->fresh()->status);
    }

    public function test_legacy_disposition_status_is_treated_as_return(): void
    {
        $user = $this->actor();
        $mail = $this->mailWithStatus('DISPOSISI');

        // A stored legacy value normalizes to RETURN, so only RETURN's
        // transitions are available.
        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'RECEIVE',
        ])->assertStatus(422);

        $this->actingAs($user)->postJson(route('incoming-mails.bulk-update-status'), [
            'ids' => [$mail->id],
            'status' => 'REVISI',
            'revision_note' => 'Dokumen lama perlu diperbaiki.',
        ])->assertStatus(200);

        $this->assertSame('REVISI', $mail->fresh()->status);
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

    public function test_revisi_cannot_be_returned_before_the_revision_is_resolved(): void
    {
        $user = $this->master();
        $mail = $this->mailWithStatus('REVISI');

        $this->assertFalse(IncomingMailStatus::canProcessReturn('REVISI'));

        $this->actingAs($user)->postJson(route('incoming-mails.return-workflow.store'), [
            'mail_ids' => [$mail->id],
            'action' => 'skip_remaining',
            'recipients' => [['recipient_name' => 'Divisi Keuangan']],
        ])->assertStatus(422);

        $mail->refresh();
        $this->assertSame('REVISI', $mail->status);
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

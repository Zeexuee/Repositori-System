<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\OutgoingMailNumber;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutgoingMailNumberTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => Roles::STAFF]));

        return $user;
    }

    private function mailWithNumber(User $creator, string $mailNumber): OutgoingMail
    {
        return OutgoingMail::create([
            'mail_number' => $mailNumber,
            'subject' => 'Surat Uji Nomor',
            'recipient' => 'PT Mitra',
            'created_by' => $creator->id,
            'status' => 'WAITING_RECEIVE',
        ]);
    }

    public function test_first_number_of_the_day_starts_at_one(): void
    {
        $this->assertSame('SK-'.now()->format('Ymd').'-0001', OutgoingMailNumber::next());
    }

    public function test_next_number_does_not_reuse_deleted_sequences(): void
    {
        $creator = $this->staff();
        $prefix = 'SK-'.now()->format('Ymd').'-';

        $this->mailWithNumber($creator, $prefix.'0001');
        $this->mailWithNumber($creator, $prefix.'0002')->delete();
        $this->mailWithNumber($creator, $prefix.'0003');

        $this->assertSame($prefix.'0004', OutgoingMailNumber::next());
    }

    public function test_store_assigns_generated_number_without_unique_violation(): void
    {
        $creator = $this->staff();
        $prefix = 'SK-'.now()->format('Ymd').'-';

        $this->mailWithNumber($creator, $prefix.'0001');
        $this->mailWithNumber($creator, $prefix.'0002')->delete();
        $this->mailWithNumber($creator, $prefix.'0003');

        $this->actingAs($creator)->post(route('outgoing-mails.store'), [
            'documents' => [
                ['subject' => 'Surat Baru', 'recipient' => 'PT Baru'],
            ],
        ])->assertRedirect(route('outgoing-mails.index'));

        $created = OutgoingMail::where('subject', 'Surat Baru')->firstOrFail();

        $this->assertSame($prefix.'0004', $created->mail_number);
    }
}

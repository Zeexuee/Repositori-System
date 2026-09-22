<?php

namespace Tests\Feature;

use App\Models\OutgoingMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutgoingMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_outgoing_mail_status_to_revisi(): void
    {
        $role = Role::firstOrCreate(['name' => 'Staf']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $mail = OutgoingMail::create([
            'mail_number' => 'SK-20260922-0001',
            'subject' => '[PROGRES] Surat Tanggapan Kerjasama',
            'recipient' => 'PT Mitra Sejahtera',
            'created_by' => $user->id,
            'status' => 'PROGRES',
        ]);

        $response = $this->actingAs($user)->put(route('outgoing-mails.update', $mail), [
            'status' => 'REVISI',
        ]);

        $response->assertRedirect(route('outgoing-mails.index'));
        $response->assertSessionHas('success');

        $mail->refresh();
        $this->assertSame('REVISI', $mail->status);
        $this->assertStringStartsWith('[REVISI]', $mail->subject);
    }
}

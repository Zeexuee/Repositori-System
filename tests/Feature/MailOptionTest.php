<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\MailOptionList;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_exclude_option_and_it_disappears_from_suggestions(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Seed some mails
        IncomingMail::create([
            'mail_number' => 'TR-20261111-0001',
            'sender' => 'Biro Hubungan Masyarakat',
            'recipient' => 'Direktur Utama',
            'status' => 'Segera Diproses',
            'subject' => 'Surat Penting',
            'received_date' => now(),
            'progress' => 'RECEIVE',
        ]);

        OutgoingMail::create([
            'mail_number' => 'SK-20261111-0001',
            'subject' => 'Surat Keluar 1',
            'recipient' => 'Kementerian Pendidikan',
            'status' => 'Konfidensial',
            'created_by' => $user->id,
            'progress' => 'READY_TO_DELIVERY',
        ]);

        // Prior to exclusion, options are present
        $this->assertContains('Biro Hubungan Masyarakat', MailOptionList::senders()->all());
        $this->assertContains('Direktur Utama', MailOptionList::recipients()->all());
        $this->assertContains('Kementerian Pendidikan', MailOptionList::recipients()->all());
        $this->assertContains('Segera Diproses', MailOptionList::statuses()->all());
        $this->assertContains('Konfidensial', MailOptionList::statuses()->all());

        // Remove sender
        $res = $this->actingAs($user)->postJson(route('mail-options.destroy'), [
            'type' => 'senders',
            'value' => 'Biro Hubungan Masyarakat',
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Remove recipient
        $res = $this->actingAs($user)->postJson(route('mail-options.destroy'), [
            'type' => 'recipients',
            'value' => 'Direktur Utama',
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Remove status
        $res = $this->actingAs($user)->postJson(route('mail-options.destroy'), [
            'type' => 'statuses',
            'value' => 'Konfidensial',
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Check that excluded options no longer appear in suggestions
        $this->assertNotContains('Biro Hubungan Masyarakat', MailOptionList::senders()->all());
        $this->assertNotContains('Direktur Utama', MailOptionList::recipients()->all());
        $this->assertNotContains('Konfidensial', MailOptionList::statuses()->all());

        // But other options are still there
        $this->assertContains('Kementerian Pendidikan', MailOptionList::recipients()->all());
        $this->assertContains('Segera Diproses', MailOptionList::statuses()->all());
    }

    public function test_user_can_restore_previously_excluded_option(): void
    {
        $role = Role::firstOrCreate(['name' => Roles::STAFF]);
        $user = User::factory()->create();
        $user->assignRole($role);

        IncomingMail::create([
            'mail_number' => 'TR-20261111-0002',
            'sender' => 'Biro Umum',
            'recipient' => 'Wakil Rektor II',
            'subject' => 'Surat Evaluasi',
            'received_date' => now(),
            'progress' => 'RECEIVE',
        ]);

        // Exclude
        MailOptionList::exclude('senders', 'Biro Umum', $user->id);
        $this->assertNotContains('Biro Umum', MailOptionList::senders()->all());

        // Restore via API
        $res = $this->actingAs($user)->postJson(route('mail-options.restore'), [
            'type' => 'senders',
            'value' => 'Biro Umum',
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Check restored
        $this->assertContains('Biro Umum', MailOptionList::senders()->all());
    }

    public function test_guest_cannot_modify_mail_options(): void
    {
        $res = $this->postJson(route('mail-options.destroy'), [
            'type' => 'statuses',
            'value' => 'Tembusan',
        ]);
        $res->assertUnauthorized();
    }
}


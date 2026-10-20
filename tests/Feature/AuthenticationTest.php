<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_login_endpoint_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->post('/quick-login/'.$user->email)->assertNotFound();

        $this->assertGuest();
    }

    public function test_login_requires_the_correct_password(): void
    {
        $user = User::factory()->create(['email' => 'staf@example.com']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'staf@example.com']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('incoming-mails.index'));

        $this->assertAuthenticatedAs($user);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_failed_logins_are_throttled(): void
    {
        $user = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'user',
        ]);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'WrongPassword123!',
            ]);
        }

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'WrongPassword123!',
            ])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_session_id_changes_after_successful_login(): void
    {
        $user = User::factory()->create([
            'email' => 'session@example.com',
            'role' => 'user',
        ]);

        $initialSessionId = session()->getId();

        $this->withSession(['_token' => 'preexisting'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($initialSessionId, session()->getId());
    }

    public function test_old_password_no_longer_authenticates_after_password_change(): void
    {
        $user = User::factory()->create([
            'email' => 'password-change@example.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->post('/profile/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->post('/logout')->assertRedirect('/');

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'OldPassword123!',
            ])
            ->assertSessionHasErrors('email');

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'NewPassword123!',
            ])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AlternativeLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_normalizes_an_indonesian_phone_number(): void
    {
        $this->post('/register', [
            'name' => 'Phone Reader',
            'email' => 'phone-reader@example.com',
            'phone' => '0812 3456-7890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'phone-reader@example.com',
            'phone' => '+6281234567890',
        ]);
    }

    public function test_phone_number_is_optional_for_existing_email_registration(): void
    {
        $this->post('/register', [
            'name' => 'Email Reader',
            'email' => 'email-reader@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'email-reader@example.com',
            'phone' => null,
        ]);
    }

    public function test_user_can_login_with_phone_number_and_password(): void
    {
        $user = User::factory()->create([
            'phone' => '+6281234567890',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post('/login', [
            'login' => '0812-3456-7890',
            'password' => 'Password123!',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_phone_number_is_rejected_after_normalization(): void
    {
        User::factory()->create(['phone' => '+6281234567890']);

        $this->from('/register')->post('/register', [
            'name' => 'Duplicate Phone',
            'email' => 'duplicate-phone@example.com',
            'phone' => '0812 3456 7890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('phone');
    }

    public function test_existing_user_can_add_a_phone_number_from_profile(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0813 5555 7777',
        ])->assertRedirect(route('profile'));

        $this->assertSame('+6281355557777', $user->fresh()->phone);
    }

    public function test_invalid_phone_number_is_rejected(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Invalid Phone',
            'email' => 'invalid-phone@example.com',
            'phone' => 'not-a-phone',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('phone');
    }

    public function test_google_login_requires_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_google_login_creates_a_verified_user(): void
    {
        $this->configureGoogleLogin();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Google Reader',
            'email' => 'google-reader@example.com',
            'verified_email' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect('/');

        $user = User::where('email', 'google-reader@example.com')->firstOrFail();

        $this->assertSame('google-user-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_links_an_existing_account_by_email(): void
    {
        $this->configureGoogleLogin();

        $user = User::factory()->unverified()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-existing-456',
            'name' => 'Existing Reader',
            'email' => 'existing@example.com',
            'verified_email' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect('/');

        $user->refresh();

        $this->assertSame('google-existing-456', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
    }

    private function configureGoogleLogin(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }
}

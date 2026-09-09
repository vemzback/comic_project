<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountRecoveryAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_available_to_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertSee('Send Reset Link');
    }

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset-reader@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_request_does_not_reveal_unknown_accounts(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'unknown-reader@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'status',
                'If an account matches that email, a password reset link has been sent.'
            );

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'valid-reset@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_invalid_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'invalid-reset@example.com']);

        $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
            ->post(route('password.store'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_registration_requires_email_verification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'New Unverified Reader',
            'email' => 'new-unverified@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'new-unverified@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_survives_a_verification_delivery_failure(): void
    {
        config([
            'mail.default' => 'brevo',
            'mail.mailers.brevo.api_key' => '',
        ]);
        Mail::purge();

        $this->post(route('register'), [
            'name' => 'Delivery Failure Reader',
            'email' => 'delivery-failure@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-failed');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'delivery-failure@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_unverified_user_can_view_verification_notice_and_resend_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verify your email')
            ->assertSee($user->email);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_displays_a_clear_message_when_delivery_fails(): void
    {
        config([
            'mail.default' => 'brevo',
            'mail.mailers.brevo.api_key' => '',
        ]);
        Mail::purge();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-failed');
    }

    public function test_user_can_verify_email_with_a_valid_signed_link(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_is_redirected_from_protected_library_features(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('bookmarks.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_changing_email_requires_verification_again(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'old-reader@example.com']);

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'name' => $user->name,
                'email' => 'new-reader@example.com',
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        $user->refresh();

        $this->assertSame('new-reader@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}

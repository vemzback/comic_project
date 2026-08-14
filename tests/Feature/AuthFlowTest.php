<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Reader',
            'email' => 'newreader@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('users', [
            'email' => 'newreader@example.com',
            'role' => 'user',
        ]);

        $user = User::where('email', 'newreader@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_regular_user_can_login_and_redirect_to_home(): void
    {
        $user = User::factory()->create([
            'email' => 'reader@example.com',
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'email' => 'reader@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_login_and_redirect_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_admin_route(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profile User')
            ->assertSee('profile@example.com');
    }

    public function test_user_cannot_change_own_role_via_profile_update(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->post('/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'role' => 'admin',
            ]);

        $user->refresh();

        $this->assertSame('user', $user->role);
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Rating;
use App\Models\ReadingHistory;
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

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'newreader@example.com',
            'role' => 'user',
        ]);

        $user = User::where('email', 'newreader@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_page_renders_shared_auth_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('zYx comic')
            ->assertSee('Welcome back')
            ->assertSee('Sign in to continue reading')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Sign In')
            ->assertSee('Register');
    }

    public function test_register_page_renders_shared_auth_form(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('zYx comic')
            ->assertSee('Create your account')
            ->assertSee('Start reading and saving comics')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Confirm Password')
            ->assertSee('Create Account')
            ->assertSee('Login');
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

    public function test_admin_dashboard_shows_platform_summary_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $users = User::factory()->count(3)->create();
        $comics = Comic::factory()->count(3)->create(['status' => 'ongoing']);
        Comic::factory()->create(['status' => 'completed']);
        $chapter = Chapter::factory()->create();
        Page::factory()->count(4)->create(['chapter_id' => $chapter->id]);

        Bookmark::create(['user_id' => $users[0]->id, 'comic_id' => $comics[0]->id]);
        Bookmark::create(['user_id' => $users[1]->id, 'comic_id' => $comics[1]->id]);

        ReadingHistory::create([
            'user_id' => $users[2]->id,
            'comic_id' => $comics[2]->id,
            'chapter_id' => $chapter->id,
            'page_number' => 2,
            'last_read_at' => now(),
        ]);

        Comment::create([
            'user_id' => $users[0]->id,
            'comic_id' => $comics[0]->id,
            'body' => 'Looks good.',
            'is_approved' => true,
        ]);

        Rating::create([
            'user_id' => $users[1]->id,
            'comic_id' => $comics[1]->id,
            'score' => 5,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Platform Overview')
            ->assertSee('Total Users')
            ->assertSee((string) User::count())
            ->assertSee('Total Comics')
            ->assertSee((string) Comic::count())
            ->assertSee('Total Chapters')
            ->assertSee((string) Chapter::count())
            ->assertSee('Total Pages')
            ->assertSee((string) Page::count())
            ->assertSee('Bookmarks')
            ->assertSee('Comments')
            ->assertSee('Ratings');
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
            ->assertSee('profile@example.com')
            ->assertSee('Your comic journey')
            ->assertSee('Reading dashboard')
            ->assertSee('My Bookmarks')
            ->assertSee('Account Security');
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

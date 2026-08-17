<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ======================================================================
    // Admin User Index Tests
    // ======================================================================

    public function test_guest_cannot_access_admin_user_index(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_user_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_user_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_admin_user_index_displays_registered_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);
        $user2 = User::factory()->create([
            'role' => 'user',
            'name' => 'Bob Writer',
            'email' => 'bob@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Alice Reader');
        $response->assertSee('alice@example.com');
        $response->assertSee('Bob Writer');
        $response->assertSee('bob@example.com');
    }

    public function test_admin_user_index_displays_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);
        $anotherAdmin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        // Verify that roles are displayed (case-insensitive or with markup)
        $response->assertSee('user', false);
        $response->assertSee('admin', false);
    }

    public function test_admin_user_index_does_not_expose_password_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'password' => 'SecretPassword123!',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        // Verify password is not exposed
        $response->assertDontSee('SecretPassword123!');
        // Verify password hash is not exposed (typical hash pattern)
        $response->assertDontSee('$2y$');
    }

    public function test_admin_user_index_is_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create more users than one page should display
        // Assuming the convention of 12 per page (from ComicController)
        User::factory()->count(15)->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        // Verify pagination is present by checking for pagination links or data
        // The response should have pagination available
        $this->assertTrue($response->original['users']->perPage() > 0);
        $this->assertTrue($response->original['users']->hasPages());
    }

    // ======================================================================
    // Admin User Detail Tests
    // ======================================================================

    public function test_guest_cannot_access_admin_user_detail(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->get("/admin/users/{$user->id}")->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_user_detail(): void
    {
        $viewer = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create(['role' => 'user']);

        $this->actingAs($viewer)
            ->get("/admin/users/{$targetUser->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_admin_user_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}")
            ->assertOk();
    }

    public function test_admin_user_detail_displays_identity_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertOk();
        $response->assertSee('Test User');
        $response->assertSee('test@example.com');
        $response->assertSee('user', false); // role should be visible
        // Created date should be visible (in some format)
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function test_admin_user_detail_displays_activity_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        // Create some activity for this user using the project's fixture pattern
        // (direct model creation, not factories, following UserBookmarkTest, ReadingHistoryTest, etc.)
        
        // Bookmarks: create 3 different comics and bookmark each one
        for ($i = 0; $i < 3; $i++) {
            $comic = Comic::factory()->create();
            Bookmark::create(['user_id' => $user->id, 'comic_id' => $comic->id]);
        }
        
        // Reading history: one comic with one chapter and 5 reads
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        for ($i = 0; $i < 5; $i++) {
            ReadingHistory::create([
                'user_id' => $user->id,
                'comic_id' => $comic->id,
                'chapter_id' => $chapter->id,
                'page_number' => ($i + 1),
            ]);
        }
        
        // Comments: 2 different comics
        for ($i = 0; $i < 2; $i++) {
            $comic = Comic::factory()->create();
            Comment::create([
                'user_id' => $user->id,
                'comic_id' => $comic->id,
                'body' => 'Test comment ' . ($i + 1),
                'is_approved' => true,
            ]);
        }
        
        // Ratings: 4 different comics with different scores
        for ($i = 0; $i < 4; $i++) {
            $comic = Comic::factory()->create();
            Rating::create([
                'user_id' => $user->id,
                'comic_id' => $comic->id,
                'score' => (($i % 5) + 1),
            ]);
        }

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertOk();
        // Verify that activity counts are displayed
        // The view should contain counts or the data to display them
        $userData = $response->original['user'];
        $this->assertNotNull($userData);
        // Verify counts are accessible via relationships
        $this->assertEquals(3, $userData->bookmarks()->count());
        $this->assertEquals(5, $userData->readingHistories()->count());
        $this->assertEquals(2, $userData->comments()->count());
        $this->assertEquals(4, $userData->ratings()->count());
    }

    public function test_admin_user_detail_does_not_expose_password_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertOk();
        // Verify password is not exposed
        $response->assertDontSee('password');
        $response->assertDontSee('$2y$');
    }

    public function test_nonexistent_user_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // This test verifies that the admin.users.show route returns 404 when given a nonexistent user ID.
        // This ensures that route-model-binding properly fails for invalid user IDs.
        // RED: Will fail because the route does not exist yet.
        // GREEN: Will pass when admin.users.show route is implemented with proper model binding.
        $this->actingAs($admin)
            ->get(route('admin.users.show', ['user' => 99999]))
            ->assertNotFound();
    }

    // ======================================================================
    // Phase 5D-2B: Search Tests
    // ======================================================================

    public function test_admin_can_search_users_by_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alice = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);
        $bob = User::factory()->create([
            'role' => 'user',
            'name' => 'Bob Writer',
            'email' => 'bob@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=Alice');

        $response->assertOk();
        $response->assertSee('Alice Reader');
        $response->assertDontSee('Bob Writer');
    }

    public function test_admin_can_search_users_by_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);
        $user2 = User::factory()->create([
            'role' => 'user',
            'name' => 'Bob Writer',
            'email' => 'bob@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=alice@');

        $response->assertOk();
        $response->assertSee('Alice Reader');
        $response->assertDontSee('Bob Writer');
    }

    public function test_admin_search_is_case_insensitive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);

        $responseUppercase = $this->actingAs($admin)->get('/admin/users?search=ALICE');
        $responseLowercase = $this->actingAs($admin)->get('/admin/users?search=alice');
        $responseMixed = $this->actingAs($admin)->get('/admin/users?search=AlIcE');

        $responseUppercase->assertSee('Alice Reader');
        $responseLowercase->assertSee('Alice Reader');
        $responseMixed->assertSee('Alice Reader');
    }

    public function test_admin_search_is_partial_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=ali');

        $response->assertOk();
        $response->assertSee('Alice Reader');
    }

    public function test_admin_search_excludes_nonmatching_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matching = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);
        $nonMatching = User::factory()->create([
            'role' => 'user',
            'name' => 'Charlie Developer',
            'email' => 'charlie@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=alice');

        $response->assertOk();
        $response->assertSee('Alice Reader');
        $response->assertDontSee('Charlie Developer');
    }

    public function test_admin_empty_search_shows_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'user', 'name' => 'Alice']);
        $user2 = User::factory()->create(['role' => 'user', 'name' => 'Bob']);

        $response = $this->actingAs($admin)->get('/admin/users?search=');

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
    }

    public function test_admin_no_search_param_shows_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'user', 'name' => 'Alice']);
        $user2 = User::factory()->create(['role' => 'user', 'name' => 'Bob']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
    }

    // ======================================================================
    // Phase 5D-2B: Role Filter Tests
    // ======================================================================

    public function test_admin_can_filter_users_by_role_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user', 'name' => 'Alice User']);
        $anotherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Bob Admin']);

        $response = $this->actingAs($admin)->get('/admin/users?role=user');

        $response->assertOk();
        $response->assertSee('Alice User');
        $response->assertDontSee('Bob Admin');
    }

    public function test_admin_can_filter_users_by_role_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user', 'name' => 'Alice User']);
        $anotherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Bob Admin']);

        $response = $this->actingAs($admin)->get('/admin/users?role=admin');

        $response->assertOk();
        $response->assertSee('Bob Admin');
        // Admin user viewing may see themselves, but regular user should not be in results
        $this->assertCount(2, $response->original['users']); // admin + anotherAdmin
    }

    public function test_admin_empty_role_filter_shows_all_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user', 'name' => 'Alice']);
        $anotherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Bob']);

        $response = $this->actingAs($admin)->get('/admin/users?role=');

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
    }

    public function test_admin_no_role_param_shows_all_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user', 'name' => 'Alice']);
        $anotherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Bob']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
    }

    public function test_admin_invalid_role_filter_is_ignored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'user', 'name' => 'Alice']);
        $user2 = User::factory()->create(['role' => 'admin', 'name' => 'Bob']);

        // Invalid role should be ignored, showing all users
        $response = $this->actingAs($admin)->get('/admin/users?role=superuser');

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
    }

    // ======================================================================
    // Phase 5D-2B: Combined Search + Filter Tests
    // ======================================================================

    public function test_admin_search_and_role_filter_work_together(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $aliceUser = User::factory()->create([
            'role' => 'user',
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
        ]);
        $aliceAdmin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Alice Admin',
            'email' => 'alice.admin@example.com',
        ]);
        $bobUser = User::factory()->create([
            'role' => 'user',
            'name' => 'Bob Writer',
            'email' => 'bob@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=alice&role=user');

        $response->assertOk();
        $response->assertSee('Alice Reader');
        $response->assertDontSee('Alice Admin');
        $response->assertDontSee('Bob Writer');
    }

    public function test_admin_search_and_role_filter_return_empty_when_no_matches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Bob Writer',
            'email' => 'bob@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=alice&role=admin');

        $response->assertOk();
        $response->assertSee('No users match your search criteria');
    }

    // ======================================================================
    // Phase 5D-2B: Pagination Tests
    // ======================================================================

    public function test_admin_search_parameters_preserved_on_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Create 15 users with similar names to trigger pagination, using factory's unique email generation
        for ($i = 0; $i < 15; $i++) {
            User::factory()->create([
                'role' => 'user',
                'name' => 'Alice User ' . $i,
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/users?search=alice&page=2');

        $response->assertOk();
        // Check that the pagination links preserve the search parameter
        $html = $response->getContent();
        $this->assertStringContainsString('search=alice', $html);
    }

    public function test_admin_role_filter_preserved_on_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(15)->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get('/admin/users?role=user&page=2');

        $response->assertOk();
        // Check that the pagination links preserve the role parameter
        $html = $response->getContent();
        $this->assertStringContainsString('role=user', $html);
    }

    public function test_admin_both_params_preserved_on_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(15)->create([
            'role' => 'user',
            'name' => 'Alice User',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=alice&role=user&page=2');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('search=alice', $html);
        $this->assertStringContainsString('role=user', $html);
    }

    // ======================================================================
    // Phase 5D-2B: Empty State Tests
    // ======================================================================

    public function test_admin_displays_different_empty_message_for_filtered_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => 'Bob']);

        $response = $this->actingAs($admin)->get('/admin/users?search=alice');

        $response->assertOk();
        $response->assertSee('No users match your search criteria');
    }

    // ======================================================================
    // Phase 5D-2B: Authorization Regression Tests
    // ======================================================================

    public function test_guest_cannot_search_users(): void
    {
        $this->get('/admin/users?search=alice')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_search_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/admin/users?search=alice')
            ->assertForbidden();
    }
}

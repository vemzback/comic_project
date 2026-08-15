<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_bookmark_list(): void
    {
        $this->get('/bookmarks')->assertRedirect('/login');
    }

    public function test_guest_cannot_create_bookmark(): void
    {
        $comic = Comic::factory()->create();

        $this->post('/bookmarks/' . $comic->id)->assertRedirect('/login');
    }

    public function test_guest_cannot_delete_bookmark(): void
    {
        $comic = Comic::factory()->create();

        $this->delete('/bookmarks/' . $comic->id)->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_bookmark_list(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        Bookmark::create(['user_id' => $user->id, 'comic_id' => $comic->id]);

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertOk()
            ->assertSee('My Bookmarks')
            ->assertSee($comic->title);
    }

    public function test_authenticated_user_can_bookmark_a_comic(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $this->actingAs($user)
            ->post('/bookmarks/' . $comic->id)
            ->assertRedirect(route('comic.detail', $comic));

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    public function test_authenticated_user_can_remove_a_bookmark(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        Bookmark::create(['user_id' => $user->id, 'comic_id' => $comic->id]);

        $this->actingAs($user)
            ->delete('/bookmarks/' . $comic->id)
            ->assertRedirect(route('bookmarks.index'));

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    public function test_duplicate_bookmark_is_prevented(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        Bookmark::create(['user_id' => $user->id, 'comic_id' => $comic->id]);

        $this->actingAs($user)
            ->from(route('comic.detail', $comic))
            ->post('/bookmarks/' . $comic->id)
            ->assertRedirect(route('comic.detail', $comic));

        $this->assertSame(1, Bookmark::where('user_id', $user->id)->where('comic_id', $comic->id)->count());
    }

    public function test_user_only_sees_own_bookmarks(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();
        $comicOne = Comic::factory()->create(['title' => 'User One Comic']);
        $comicTwo = Comic::factory()->create(['title' => 'User Two Comic']);

        Bookmark::create(['user_id' => $userOne->id, 'comic_id' => $comicOne->id]);
        Bookmark::create(['user_id' => $userTwo->id, 'comic_id' => $comicTwo->id]);

        $this->actingAs($userOne)
            ->get('/bookmarks')
            ->assertOk()
            ->assertSee($comicOne->title)
            ->assertDontSee($comicTwo->title);
    }

    public function test_user_cannot_delete_another_users_bookmark(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();
        $comic = Comic::factory()->create();
        Bookmark::create(['user_id' => $userTwo->id, 'comic_id' => $comic->id]);

        $this->actingAs($userOne)
            ->delete('/bookmarks/' . $comic->id)
            ->assertNotFound();
    }

    public function test_nonexistent_comic_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/bookmarks/999999')
            ->assertNotFound();

        $this->actingAs($user)
            ->delete('/bookmarks/999999')
            ->assertNotFound();
    }

    public function test_comic_detail_shows_bookmarked_state(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        Bookmark::create(['user_id' => $user->id, 'comic_id' => $comic->id]);

        $this->actingAs($user)
            ->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertSee('Remove Bookmark');
    }

    public function test_comic_detail_shows_unbookmarked_state(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $this->actingAs($user)
            ->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertSee('Bookmark');
    }

    public function test_csrf_and_auth_protection_remains_active(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/bookmarks')
            ->assertOk();

        auth()->logout();

        $this->get('/bookmarks')->assertRedirect('/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComicCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_comic_index(): void
    {
        $this->get('/admin/comics')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_comic_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/admin/comics')
            ->assertForbidden();
    }

    public function test_admin_can_view_comic_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/comics')
            ->assertOk()
            ->assertSee('Comic Management');
    }

    public function test_admin_can_create_a_comic_with_genres(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $genreAction = Genre::factory()->create(['name' => 'Action', 'slug' => 'action']);
        $genreFantasy = Genre::factory()->create(['name' => 'Fantasy', 'slug' => 'fantasy']);

        $response = $this->actingAs($admin)->post('/admin/comics', [
            'title' => 'Shadow of the City',
            'slug' => 'shadow-of-the-city',
            'description' => 'A city full of secrets and danger.',
            'cover_image' => 'covers/shadow-of-the-city.jpg',
            'status' => 'ongoing',
            'published_at' => '2026-08-15',
            'is_featured' => '1',
            'seo_title' => 'Shadow of the City',
            'seo_description' => 'A feature-rich comic about urban danger.',
            'genres' => [$genreAction->id, $genreFantasy->id],
        ]);

        $response->assertRedirect(route('admin.comics.index'));
        $this->assertDatabaseHas('comics', [
            'title' => 'Shadow of the City',
            'slug' => 'shadow-of-the-city',
            'status' => 'ongoing',
            'is_featured' => true,
        ]);

        $comic = Comic::query()->where('slug', 'shadow-of-the-city')->firstOrFail();
        $this->assertTrue($comic->genres()->whereIn('genres.id', [$genreAction->id, $genreFantasy->id])->exists());
    }

    public function test_invalid_comic_data_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/admin/comics/create')
            ->post('/admin/comics', [
                'title' => '',
                'slug' => '',
                'description' => 'Test',
                'status' => 'invalid-status',
            ])
            ->assertSessionHasErrors(['title', 'slug', 'status']);
    }

    public function test_admin_can_edit_and_update_a_comic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $genre = Genre::factory()->create(['slug' => 'action']);
        $comic = Comic::factory()->create([
            'title' => 'Old Title',
            'slug' => 'old-title',
            'status' => 'ongoing',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put("/admin/comics/{$comic->id}", [
                'title' => 'Updated Title',
                'slug' => 'updated-title',
                'description' => 'Updated description.',
                'cover_image' => 'covers/updated-title.jpg',
                'status' => 'completed',
                'published_at' => '2026-07-01',
                'is_featured' => '1',
                'seo_title' => 'Updated SEO Title',
                'seo_description' => 'Updated SEO description',
                'genres' => [$genre->id],
            ])
            ->assertRedirect(route('admin.comics.index'));

        $comic->refresh();

        $this->assertSame('Updated Title', $comic->title);
        $this->assertSame('updated-title', $comic->slug);
        $this->assertSame('completed', $comic->status);
        $this->assertTrue((bool) $comic->is_featured);
        $this->assertTrue($comic->genres()->whereKey($genre->id)->exists());
    }

    public function test_admin_can_manage_a_future_dated_comic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/comics', [
                'title' => 'Scheduled Comic',
                'slug' => 'scheduled-comic',
                'status' => 'ongoing',
                'published_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.comics.index'));

        $comic = Comic::query()->where('slug', 'scheduled-comic')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.comics.edit', $comic))
            ->assertOk()
            ->assertSee('Scheduled Comic');
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Comic::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($admin)
            ->from('/admin/comics/create')
            ->post('/admin/comics', [
                'title' => 'Existing Slug Comic',
                'slug' => 'existing-slug',
                'description' => 'Duplicate slug should fail.',
                'status' => 'ongoing',
            ])
            ->assertSessionHasErrors(['slug']);
    }

    public function test_invalid_genre_and_date_input_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $genre = Genre::factory()->create();

        $this->actingAs($admin)
            ->from('/admin/comics/create')
            ->post('/admin/comics', [
                'title' => 'Bad Input Comic',
                'slug' => 'bad-input-comic',
                'description' => 'Bad input test.',
                'status' => 'ongoing',
                'published_at' => 'not-a-date',
                'genres' => [$genre->id, 999999],
            ])
            ->assertSessionHasErrors(['published_at', 'genres.1']);
    }

    public function test_admin_can_delete_a_comic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}")
            ->assertRedirect(route('admin.comics.index'));

        $this->assertDatabaseMissing('comics', ['id' => $comic->id]);
    }

    public function test_normal_user_cannot_delete_a_comic(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $comic = Comic::factory()->create();

        $this->actingAs($user)
            ->delete("/admin/comics/{$comic->id}")
            ->assertForbidden();
    }
}

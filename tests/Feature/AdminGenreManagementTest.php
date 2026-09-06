<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGenreManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_genre_management(): void
    {
        $this->get(route('admin.genres.index'))->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_genre_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.genres.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_genre_index_and_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.genres.index'))
            ->assertOk()->assertSee('Genre Management');
        $this->actingAs($admin)->get(route('admin.genres.create'))
            ->assertOk()->assertSee('Create Genre')->assertSee('name="_token"', false);
    }

    public function test_admin_can_create_genre_with_generated_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.genres.store'), [
            'name' => 'Science Fiction',
            'description' => 'Stories beyond the known world.',
        ])->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'Science Fiction',
            'slug' => 'science-fiction',
        ]);
    }

    public function test_invalid_and_duplicate_genre_names_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Genre::factory()->create(['name' => 'Action', 'slug' => 'action']);

        $this->actingAs($admin)->from(route('admin.genres.create'))
            ->post(route('admin.genres.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
        $this->actingAs($admin)->from(route('admin.genres.create'))
            ->post(route('admin.genres.store'), ['name' => 'Action'])
            ->assertSessionHasErrors('name');
    }

    public function test_slug_collision_is_suffixed_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Genre::factory()->create(['name' => 'Sci-Fi', 'slug' => 'sci-fi']);

        $this->actingAs($admin)->post(route('admin.genres.store'), ['name' => 'Sci Fi'])
            ->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseHas('genres', ['name' => 'Sci Fi', 'slug' => 'sci-fi-2']);
    }

    public function test_admin_can_edit_genre_and_preserve_comic_relationship(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $genre = Genre::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
        $comic = Comic::factory()->create();
        $comic->genres()->attach($genre);

        $this->actingAs($admin)->put(route('admin.genres.update', $genre), [
            'name' => 'New Name',
            'description' => 'Updated.',
        ])->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => 'New Name', 'slug' => 'new-name']);
        $this->assertDatabaseHas('comic_genres', ['comic_id' => $comic->id, 'genre_id' => $genre->id]);

        $this->actingAs($admin)->get(route('admin.genres.edit', $genre->fresh()))
            ->assertOk()->assertSee('name="_token"', false)->assertSee('name="_method"', false);
    }

    public function test_admin_can_delete_genre_without_deleting_comics_or_unrelated_genres(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $genre = Genre::factory()->create();
        $unrelatedGenre = Genre::factory()->create();
        $comic = Comic::factory()->create();
        $comic->genres()->attach($genre);

        $this->actingAs($admin)->delete(route('admin.genres.destroy', $genre))
            ->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
        $this->assertDatabaseMissing('comic_genres', ['comic_id' => $comic->id, 'genre_id' => $genre->id]);
        $this->assertDatabaseHas('comics', ['id' => $comic->id]);
        $this->assertDatabaseHas('genres', ['id' => $unrelatedGenre->id]);
    }

    public function test_public_genre_browsing_remains_available(): void
    {
        $genre = Genre::factory()->create(['name' => 'Action', 'slug' => 'action']);

        $this->get(route('genres'))->assertOk()->assertSee('Action');
        $this->get(route('genres.show', $genre))->assertOk()->assertSee('Action');
    }
}

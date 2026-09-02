<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishingSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_publication_readiness_on_comic_pages(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create([
            'description' => null,
            'author' => null,
            'cover_image' => null,
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.comics.index'))
            ->assertOk()
            ->assertSee('Readiness')
            ->assertSee('issues');

        $this->actingAs($admin)
            ->get(route('admin.comics.show', $comic))
            ->assertOk()
            ->assertSee('Publication Readiness')
            ->assertSee('Complete the items below before publishing this comic.');
    }

    public function test_new_comic_cannot_be_published_before_content_is_added(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.comics.create'))
            ->post(route('admin.comics.store'), [
                'title' => 'Unsafe Launch',
                'slug' => 'unsafe-launch',
                'status' => 'ongoing',
                'published_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.comics.create'))
            ->assertSessionHasErrors('published_at');

        $this->assertDatabaseMissing('comics', ['slug' => 'unsafe-launch']);
    }

    public function test_complete_draft_comic_can_be_published(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        [$comic, $genre] = $this->makeCompleteDraftComic();

        $this->actingAs($admin)
            ->put(route('admin.comics.update', $comic), $this->comicPayload($comic, $genre, [
                'published_at' => now()->toDateString(),
            ]))
            ->assertRedirect(route('admin.comics.index'))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($comic->fresh()->published_at);
    }

    public function test_incomplete_comic_remains_draft_when_publish_is_attempted(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $genre = Genre::factory()->create();
        $comic = Comic::factory()->create([
            'description' => 'A description.',
            'author' => 'A creator',
            'cover_image' => null,
            'published_at' => null,
        ]);
        $comic->genres()->attach($genre);

        $this->actingAs($admin)
            ->from(route('admin.comics.edit', $comic))
            ->put(route('admin.comics.update', $comic), $this->comicPayload($comic, $genre, [
                'published_at' => now()->toDateString(),
            ]))
            ->assertRedirect(route('admin.comics.edit', $comic))
            ->assertSessionHasErrors('published_at');

        $this->assertNull($comic->fresh()->published_at);
    }

    public function test_chapter_must_have_valid_pages_before_it_can_be_published(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-one',
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.comics.chapters.edit', [$comic, $chapter]))
            ->put(route('admin.comics.chapters.update', [$comic, $chapter]), [
                'chapter_number' => 1,
                'title' => 'Chapter One',
                'slug' => 'chapter-one',
                'sort_order' => 1,
                'published_at' => now()->toDateString(),
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.comics.chapters.edit', [$comic, $chapter]))
            ->assertSessionHasErrors('is_published');

        $this->assertFalse((bool) $chapter->fresh()->is_published);
    }

    public function test_chapter_with_sequential_stored_pages_can_be_published(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-one',
            'is_published' => false,
            'published_at' => null,
        ]);
        Storage::disk('public')->put('chapters/one/001.jpg', 'page');
        Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/one/001.jpg',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.comics.chapters.update', [$comic, $chapter]), [
                'chapter_number' => 1,
                'title' => 'Chapter One',
                'slug' => 'chapter-one',
                'sort_order' => 1,
                'published_at' => now()->toDateString(),
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.comics.chapters.index', $comic))
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $chapter->fresh()->is_published);
    }

    public function test_public_content_cannot_be_deleted_until_it_is_unpublished(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        [$comic] = $this->makeCompleteDraftComic();
        $comic->update(['published_at' => now()]);
        $chapter = $comic->chapters()->where('is_published', true)->firstOrFail();
        $page = $chapter->pages()->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.comics.index'))
            ->delete(route('admin.comics.destroy', $comic))
            ->assertSessionHasErrors('comic');

        $this->actingAs($admin)
            ->from(route('admin.comics.chapters.index', $comic))
            ->delete(route('admin.comics.chapters.destroy', [$comic, $chapter]))
            ->assertSessionHasErrors('chapter');

        $this->actingAs($admin)
            ->from(route('admin.comics.chapters.pages.index', [$comic, $chapter]))
            ->delete(route('admin.comics.chapters.pages.destroy', [$comic, $chapter, $page]))
            ->assertSessionHasErrors('page');

        $this->assertDatabaseHas('comics', ['id' => $comic->id]);
        $this->assertDatabaseHas('chapters', ['id' => $chapter->id]);
        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    /**
     * @return array{0: Comic, 1: Genre}
     */
    private function makeCompleteDraftComic(): array
    {
        Storage::disk('public')->put('comics/covers/ready.jpg', 'cover');
        Storage::disk('public')->put('chapters/ready/001.jpg', 'page');

        $genre = Genre::factory()->create();
        $comic = Comic::factory()->create([
            'description' => 'A complete comic ready for readers.',
            'author' => 'Test Creator',
            'cover_image' => 'comics/covers/ready.jpg',
            'published_at' => null,
        ]);
        $comic->genres()->attach($genre);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);
        Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/ready/001.jpg',
        ]);

        return [$comic, $genre];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function comicPayload(Comic $comic, Genre $genre, array $overrides = []): array
    {
        return array_merge([
            'title' => $comic->title,
            'slug' => $comic->slug,
            'description' => $comic->description,
            'author' => $comic->author,
            'publisher' => $comic->publisher,
            'status' => $comic->status,
            'genres' => [$genre->id],
        ], $overrides);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guest_cannot_upload_comic_cover(): void
    {
        $this->post('/admin/comics', [
            'title' => 'Guest Comic',
            'slug' => 'guest-comic',
            'status' => 'ongoing',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertRedirect('/login');
    }

    public function test_regular_user_cannot_upload_comic_cover(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->post('/admin/comics', [
            'title' => 'User Comic',
            'slug' => 'user-comic',
            'status' => 'ongoing',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertForbidden();
    }

    public function test_admin_can_upload_comic_cover(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/comics', [
            'title' => 'Cover Comic',
            'slug' => 'cover-comic',
            'description' => 'Hello world',
            'status' => 'ongoing',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $response->assertRedirect(route('admin.comics.index'));
        $comic = Comic::query()->where('slug', 'cover-comic')->firstOrFail();
        $this->assertNotNull($comic->cover_image);
        $this->assertStringStartsWith('comics/covers/', $comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
    }

    public function test_invalid_comic_image_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/admin/comics/create')
            ->post('/admin/comics', [
                'title' => 'Bad Image Comic',
                'slug' => 'bad-image-comic',
                'status' => 'ongoing',
                'cover_image' => UploadedFile::fake()->create('bad.pdf', 50),
            ])
            ->assertSessionHasErrors(['cover_image']);
    }

    public function test_admin_can_replace_comic_cover_and_remove_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create([
            'cover_image' => 'comics/covers/old-cover.jpg',
        ]);
        Storage::disk('public')->put($comic->cover_image, 'old');

        $newImage = UploadedFile::fake()->image('new-cover.jpg');

        $this->actingAs($admin)
            ->put("/admin/comics/{$comic->id}", [
                'title' => $comic->title,
                'slug' => $comic->slug,
                'description' => 'Updated',
                'status' => 'ongoing',
                'cover_image' => $newImage,
            ])
            ->assertRedirect(route('admin.comics.index'));

        $comic->refresh();
        $this->assertNotSame('comics/covers/old-cover.jpg', $comic->cover_image);
        $this->assertStringStartsWith('comics/covers/', $comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
        Storage::disk('public')->assertMissing('comics/covers/old-cover.jpg');
    }

    public function test_admin_can_delete_comic_cover_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create([
            'cover_image' => 'comics/covers/delete-me.jpg',
            'published_at' => null,
        ]);
        Storage::disk('public')->put($comic->cover_image, 'delete me');

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}")
            ->assertRedirect(route('admin.comics.index'));

        $this->assertDatabaseMissing('comics', ['id' => $comic->id]);
        Storage::disk('public')->assertMissing('comics/covers/delete-me.jpg');
    }

    public function test_deleting_comic_removes_descendant_page_media_and_preserves_other_comic_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create([
            'cover_image' => 'comics/covers/delete-comic.jpg',
            'published_at' => null,
        ]);
        $otherComic = Comic::factory()->create([
            'cover_image' => 'comics/covers/keep-comic.jpg',
        ]);
        $chapterOne = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 1]);
        $chapterTwo = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 2]);
        $otherChapter = Chapter::factory()->create(['comic_id' => $otherComic->id, 'chapter_number' => 1]);
        $pageOne = Page::factory()->create([
            'chapter_id' => $chapterOne->id,
            'image_path' => 'chapters/pages/delete-comic-one.jpg',
        ]);
        $pageTwo = Page::factory()->create([
            'chapter_id' => $chapterTwo->id,
            'image_path' => 'chapters/pages/delete-comic-two.jpg',
        ]);
        $otherPage = Page::factory()->create([
            'chapter_id' => $otherChapter->id,
            'image_path' => 'chapters/pages/keep-comic.jpg',
        ]);

        Storage::disk('public')->put($comic->cover_image, 'comic cover');
        Storage::disk('public')->put($otherComic->cover_image, 'other cover');
        Storage::disk('public')->put($pageOne->image_path, 'page one');
        Storage::disk('public')->put($pageTwo->image_path, 'page two');
        Storage::disk('public')->put($otherPage->image_path, 'other page');

        $this->actingAs($admin)
            ->delete(route('admin.comics.destroy', $comic))
            ->assertRedirect(route('admin.comics.index'));

        $this->assertDatabaseMissing('comics', ['id' => $comic->id]);
        $this->assertDatabaseMissing('chapters', ['id' => $chapterOne->id]);
        $this->assertDatabaseMissing('chapters', ['id' => $chapterTwo->id]);
        $this->assertDatabaseMissing('pages', ['id' => $pageOne->id]);
        $this->assertDatabaseMissing('pages', ['id' => $pageTwo->id]);
        Storage::disk('public')->assertMissing($comic->cover_image);
        Storage::disk('public')->assertMissing($pageOne->image_path);
        Storage::disk('public')->assertMissing($pageTwo->image_path);
        Storage::disk('public')->assertExists($otherComic->cover_image);
        Storage::disk('public')->assertExists($otherPage->image_path);
    }

    public function test_deleting_comic_succeeds_when_cover_and_page_media_are_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create([
            'cover_image' => 'comics/covers/already-missing.jpg',
            'published_at' => null,
        ]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        $page = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'image_path' => 'chapters/pages/already-missing.jpg',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.comics.destroy', $comic))
            ->assertRedirect(route('admin.comics.index'));

        $this->assertDatabaseMissing('comics', ['id' => $comic->id]);
        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_guest_cannot_upload_page_image(): void
    {
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);

        $this->post("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages", [
            'page_number' => 1,
            'image_path' => UploadedFile::fake()->image('page-1.jpg'),
        ])->assertRedirect('/login');
    }

    public function test_regular_user_cannot_upload_page_image(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        $this->actingAs($user)->post("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages", [
            'page_number' => 1,
            'image_path' => UploadedFile::fake()->image('page-1.jpg'),
        ])->assertForbidden();
    }

    public function test_admin_can_upload_page_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'is_published' => false,
        ]);

        $response = $this->actingAs($admin)->post("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages", [
            'page_number' => 1,
            'title' => 'First Page',
            'image_path' => UploadedFile::fake()->image('page-1.jpg'),
        ]);

        $response->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]));
        $page = $chapter->fresh()->pages()->first();
        $this->assertNotNull($page);
        $this->assertStringStartsWith('chapters/pages/', $page->image_path);
        Storage::disk('public')->assertExists($page->image_path);
    }

    public function test_invalid_page_image_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        $this->actingAs($admin)
            ->from("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages/create")
            ->post("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages", [
                'page_number' => 2,
                'image_path' => UploadedFile::fake()->create('notes.txt', 20),
            ])
            ->assertSessionHasErrors(['image_path']);
    }

    public function test_admin_can_replace_page_image_and_remove_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $page = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/pages/old-page.jpg',
        ]);
        Storage::disk('public')->put($page->image_path, 'old page');

        $this->actingAs($admin)
            ->put("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages/{$page->id}", [
                'page_number' => 1,
                'title' => 'Updated Page',
                'image_path' => UploadedFile::fake()->image('new-page.jpg'),
            ])
            ->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]));

        $page->refresh();
        $this->assertNotSame('chapters/pages/old-page.jpg', $page->image_path);
        $this->assertStringStartsWith('chapters/pages/', $page->image_path);
        Storage::disk('public')->assertExists($page->image_path);
        Storage::disk('public')->assertMissing('chapters/pages/old-page.jpg');
    }

    public function test_deleting_page_removes_its_image_and_deleting_chapter_removes_related_page_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $page = Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/pages/delete-me.jpg',
        ]);
        Storage::disk('public')->put($page->image_path, 'page file');

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages/{$page->id}")
            ->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        Storage::disk('public')->assertMissing('chapters/pages/delete-me.jpg');

        $secondChapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'is_published' => false,
        ]);
        $secondPage = Page::factory()->create([
            'chapter_id' => $secondChapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/pages/chapter-delete.jpg',
        ]);
        Storage::disk('public')->put($secondPage->image_path, 'second page file');

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}/chapters/{$secondChapter->id}")
            ->assertRedirect(route('admin.comics.chapters.index', $comic));

        $this->assertDatabaseMissing('chapters', ['id' => $secondChapter->id]);
        $this->assertDatabaseMissing('pages', ['id' => $secondPage->id]);
        Storage::disk('public')->assertMissing('chapters/pages/chapter-delete.jpg');
    }

    public function test_public_page_displays_stored_page_image_and_unpublished_chapter_remains_inaccessible(): void
    {
        $comic = Comic::factory()->create(['published_at' => now(), 'status' => 'ongoing']);
        $publishedChapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $draftChapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'is_published' => false,
            'published_at' => now(),
        ]);

        $page = Page::factory()->create([
            'chapter_id' => $publishedChapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/pages/public-page.jpg',
        ]);
        Storage::disk('public')->put($page->image_path, 'public page');

        $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $publishedChapter]))
            ->assertOk()
            ->assertSee('storage/'.$page->image_path);

        $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $draftChapter]))
            ->assertNotFound();
    }

    public function test_storage_url_is_generated_correctly_for_media_paths(): void
    {
        $comic = Comic::factory()->create([
            'cover_image' => 'comics/covers/sample-cover.jpg',
        ]);

        $this->assertSame('/storage/comics/covers/sample-cover.jpg', Storage::url($comic->cover_image));
    }

    public function test_malicious_non_image_upload_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();

        $this->actingAs($admin)
            ->from('/admin/comics/create')
            ->post('/admin/comics', [
                'title' => 'Malicious File',
                'slug' => 'malicious-file',
                'status' => 'ongoing',
                'cover_image' => UploadedFile::fake()->create('story.php', 20),
            ])
            ->assertSessionHasErrors(['cover_image']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChapterPageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_chapter_management(): void
    {
        $comic = Comic::factory()->create(['published_at' => null]);

        $this->get("/admin/comics/{$comic->id}/chapters")->assertRedirect('/login');
    }

    public function test_regular_user_receives_forbidden_for_chapter_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $comic = Comic::factory()->create();

        $this->actingAs($user)
            ->get("/admin/comics/{$comic->id}/chapters")
            ->assertForbidden();
    }

    public function test_admin_can_access_chapter_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();

        $this->actingAs($admin)
            ->get("/admin/comics/{$comic->id}/chapters")
            ->assertOk()
            ->assertSee('Chapter Management');
    }

    public function test_admin_can_create_a_chapter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/comics/{$comic->id}/chapters", [
            'chapter_number' => 1,
            'title' => 'Chapter One',
            'slug' => 'chapter-one',
            'sort_order' => 1,
            'published_at' => '2026-08-15',
        ]);

        $response->assertRedirect(route('admin.comics.chapters.index', $comic));
        $this->assertDatabaseHas('chapters', [
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-one',
            'is_published' => false,
        ]);
    }

    public function test_chapter_validation_rejects_missing_and_duplicate_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'existing-chapter',
        ]);

        $this->actingAs($admin)
            ->from("/admin/comics/{$comic->id}/chapters/create")
            ->post("/admin/comics/{$comic->id}/chapters", [
                'chapter_number' => 1,
                'title' => '',
                'slug' => 'existing-chapter',
                'sort_order' => 'invalid',
                'is_published' => 'not-boolean',
                'published_at' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['title', 'slug', 'sort_order', 'is_published', 'published_at']);
    }

    public function test_admin_can_view_chapter_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        $this->actingAs($admin)
            ->get("/admin/comics/{$comic->id}/chapters/{$chapter->id}")
            ->assertOk()
            ->assertSee($chapter->title ?: $chapter->chapter_number);
    }

    public function test_admin_can_edit_and_update_a_chapter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'title' => 'Old title',
            'slug' => 'old-title',
            'sort_order' => 1,
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->put("/admin/comics/{$comic->id}/chapters/{$chapter->id}", [
                'chapter_number' => 2,
                'title' => 'Updated title',
                'slug' => 'updated-title',
                'sort_order' => 5,
                'published_at' => null,
            ])
            ->assertRedirect(route('admin.comics.chapters.index', $comic));

        $chapter->refresh();
        $this->assertSame(2, $chapter->chapter_number);
        $this->assertSame('Updated title', $chapter->title);
        $this->assertSame('updated-title', $chapter->slug);
        $this->assertFalse((bool) $chapter->is_published);
    }

    public function test_admin_can_delete_a_chapter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}/chapters/{$chapter->id}")
            ->assertRedirect(route('admin.comics.chapters.index', $comic));

        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
    }

    public function test_deleting_chapter_preserves_history_as_null(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $history = ReadingHistory::create([
            'user_id' => $admin->id,
            'comic_id' => $comic->id,
            'chapter_id' => $chapter->id,
            'page_number' => 4,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.comics.chapters.destroy', [$comic, $chapter]))
            ->assertRedirect(route('admin.comics.chapters.index', $comic));

        $history->refresh();
        $this->assertNull($history->chapter_id);
        $this->assertSame(4, $history->page_number);
    }

    public function test_deleting_chapter_merges_colliding_history_and_preserves_other_users_and_comics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();
        $comic = Comic::factory()->create(['published_at' => null]);
        $otherComic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $otherChapter = Chapter::factory()->create(['comic_id' => $otherComic->id]);
        $nullHistory = ReadingHistory::create([
            'user_id' => $admin->id,
            'comic_id' => $comic->id,
            'chapter_id' => null,
            'page_number' => 2,
            'last_read_at' => now()->subDay(),
        ]);
        $chapterHistory = ReadingHistory::create([
            'user_id' => $admin->id,
            'comic_id' => $comic->id,
            'chapter_id' => $chapter->id,
            'page_number' => 8,
            'last_read_at' => now(),
        ]);
        $otherUserHistory = ReadingHistory::create([
            'user_id' => $otherUser->id,
            'comic_id' => $comic->id,
            'chapter_id' => null,
            'page_number' => 3,
            'last_read_at' => now()->subHours(2),
        ]);
        $otherUserChapterHistory = ReadingHistory::create([
            'user_id' => $otherUser->id,
            'comic_id' => $comic->id,
            'chapter_id' => $chapter->id,
            'page_number' => 7,
            'last_read_at' => now()->subHour(),
        ]);
        $otherComicHistory = ReadingHistory::create([
            'user_id' => $admin->id,
            'comic_id' => $otherComic->id,
            'chapter_id' => $otherChapter->id,
            'page_number' => 6,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.comics.chapters.destroy', [$comic, $chapter]))
            ->assertRedirect(route('admin.comics.chapters.index', $comic));

        $this->assertDatabaseMissing('reading_history', ['id' => $nullHistory->id]);
        $this->assertDatabaseHas('reading_history', [
            'id' => $chapterHistory->id,
            'comic_id' => $comic->id,
            'chapter_id' => null,
            'page_number' => 8,
        ]);
        $this->assertDatabaseMissing('reading_history', ['id' => $otherUserHistory->id]);
        $this->assertDatabaseHas('reading_history', [
            'id' => $otherUserChapterHistory->id,
            'comic_id' => $comic->id,
            'chapter_id' => null,
            'page_number' => 7,
        ]);
        $this->assertDatabaseHas('reading_history', ['id' => $otherComicHistory->id]);
    }

    public function test_guest_cannot_access_page_management(): void
    {
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);

        $this->get("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages")->assertRedirect('/login');
    }

    public function test_regular_user_receives_forbidden_for_page_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);

        $this->actingAs($user)
            ->get("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages")
            ->assertForbidden();
    }

    public function test_admin_can_view_and_create_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);

        $this->actingAs($admin)
            ->get("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages")
            ->assertOk()
            ->assertSee('Page Management');

        $this->actingAs($admin)->post("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages", [
            'page_number' => 1,
            'title' => 'Opening Panel',
            'image_path' => 'pages/chapter-one/01.jpg',
        ])->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]));

        $this->assertDatabaseHas('pages', [
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'pages/chapter-one/01.jpg',
        ]);
    }

    public function test_page_validation_rejects_invalid_data_and_duplicate_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1, 'image_path' => 'pages/existing/01.jpg']);

        $this->actingAs($admin)
            ->from("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages/create")
            ->post("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages", [
                'page_number' => 1,
                'title' => '',
                'image_path' => '',
            ])
            ->assertSessionHasErrors(['page_number', 'image_path']);
    }

    public function test_admin_can_edit_and_update_a_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $page = Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1, 'image_path' => 'pages/old/01.jpg']);

        $this->actingAs($admin)
            ->put("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages/{$page->id}", [
                'page_number' => 2,
                'title' => 'Updated Page',
                'image_path' => 'pages/new/02.jpg',
            ])
            ->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]));

        $page->refresh();
        $this->assertSame(2, $page->page_number);
        $this->assertSame('Updated Page', $page->title);
        $this->assertSame('pages/new/02.jpg', $page->image_path);
    }

    public function test_admin_can_delete_a_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $page = Page::factory()->create(['chapter_id' => $chapter->id]);

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}/chapters/{$chapter->id}/pages/{$page->id}")
            ->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_chapter_cannot_be_attached_to_nonexistent_comic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/comics/999999/chapters')
            ->assertNotFound();
    }

    public function test_page_cannot_be_attached_to_nonexistent_chapter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();

        $this->actingAs($admin)
            ->get("/admin/comics/{$comic->id}/chapters/999999/pages")
            ->assertNotFound();
    }

    public function test_chapter_delete_handles_related_pages_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'is_published' => false]);
        $page = Page::factory()->create(['chapter_id' => $chapter->id]);

        $this->actingAs($admin)
            ->delete("/admin/comics/{$comic->id}/chapters/{$chapter->id}");

        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_page_ordering_is_deterministic_by_page_number(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 5, 'image_path' => 'pages/05.jpg']);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 2, 'image_path' => 'pages/02.jpg']);
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 3, 'image_path' => 'pages/03.jpg']);

        $ordered = $chapter->fresh()->pages()->orderBy('page_number')->pluck('page_number')->all();

        $this->assertSame([2, 3, 5], $ordered);
    }

    public function test_chapter_ordering_is_deterministic_by_sort_order_and_chapter_number(): void
    {
        $comic = Comic::factory()->create();
        Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 3, 'sort_order' => 10]);
        Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 1, 'sort_order' => 5]);
        Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 2, 'sort_order' => 7]);

        $ordered = $comic->fresh()->chapters()->orderBy('sort_order')->orderBy('chapter_number')->pluck('chapter_number')->all();

        $this->assertSame([1, 2, 3], $ordered);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComicReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_comic_detail(): void
    {
        $comic = Comic::factory()->create([
            'published_at' => now(),
        ]);

        $response = $this->get(route('comic.detail', $comic));

        $response->assertStatus(200);
        $response->assertSee($comic->title);
        $response->assertViewHas('comic', $comic);
    }

    public function test_comic_detail_displays_chapters(): void
    {
        $comic = Comic::factory()->has(
            Chapter::factory(3)->state(['is_published' => true])
        )->create(['published_at' => now()]);

        $response = $this->get(route('comic.detail', $comic));

        $response->assertStatus(200);
        $response->assertSee('Chapters');
        // Check that it contains some chapter reference (could be "Chapter 1", "Chapter 2", etc.)
        $response->assertSee('chapter-item');
    }

    public function test_comic_detail_only_shows_published_chapters(): void
    {
        $comic = Comic::factory()->has(
            Chapter::factory()->state(['is_published' => true])
        )->has(
            Chapter::factory()->state(['is_published' => false])
        )->create(['published_at' => now()]);

        $response = $this->get(route('comic.detail', $comic));

        $response->assertStatus(200);
        
        // The view receives chapters loaded by the controller which only loads published ones
        $viewComic = $response->viewData('comic');
        $publishedCount = $viewComic->chapters->count();
        $this->assertEquals(1, $publishedCount);
    }

    public function test_public_can_view_published_chapter(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(200);
        $response->assertSee($chapter->title ?: 'Chapter ' . $chapter->chapter_number);
        $response->assertViewHas('chapter', $chapter);
    }

    public function test_public_cannot_view_unpublished_chapter(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => false,
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(404);
    }

    public function test_chapter_displays_all_pages_in_order(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Create pages manually to ensure proper ordering
        for ($i = 1; $i <= 4; $i++) {
            Page::create([
                'chapter_id' => $chapter->id,
                'page_number' => $i,
                'image_path' => "pages/test-comic/chapter-{$chapter->chapter_number}/page-" . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.jpg',
            ]);
        }

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(200);
        $response->assertViewHas('pages');

        $pages = $response->viewData('pages');
        $this->assertCount(4, $pages);
        $this->assertEquals(1, $pages->first()->page_number);
        $this->assertEquals(4, $pages->last()->page_number);
    }

    public function test_chapter_with_no_pages_shows_empty_state(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(200);
        $response->assertSee('This chapter doesn');
    }

    public function test_chapter_previous_next_navigation(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);

        $chapter1 = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $chapter2 = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $chapter3 = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 3,
            'sort_order' => 3,
            'is_published' => true,
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter2]));

        $response->assertStatus(200);
        $response->assertViewHas('previousChapter');
        $response->assertViewHas('nextChapter');

        $previousChapter = $response->viewData('previousChapter');
        $nextChapter = $response->viewData('nextChapter');

        $this->assertEquals($chapter1->id, $previousChapter->id);
        $this->assertEquals($chapter3->id, $nextChapter->id);
    }

    public function test_first_chapter_has_no_previous(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('previousChapter'));
    }

    public function test_last_chapter_has_no_next(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);

        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $lastChapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $lastChapter]));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('nextChapter'));
    }

    public function test_invalid_comic_returns_404(): void
    {
        $response = $this->get(route('comic.detail', 999));

        $response->assertStatus(404);
    }

    public function test_invalid_chapter_returns_404(): void
    {
        $comic = Comic::factory()->create();

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => 999]));

        $response->assertStatus(404);
    }

    public function test_chapter_from_different_comic_returns_404(): void
    {
        $comic1 = Comic::factory()->create();
        $comic2 = Comic::factory()->create();

        $chapter = Chapter::factory()->create([
            'comic_id' => $comic2->id,
            'is_published' => true,
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic1, 'chapter' => $chapter]));

        $response->assertStatus(404);
    }

    public function test_page_image_paths_are_displayed(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
        ]);

        Page::create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'pages/test-comic/chapter-1/page-001.jpg',
        ]);

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(200);
        $response->assertSee('pages/test-comic/chapter-1/page-001.jpg');
    }

    public function test_search_results_include_comic_detail_links(): void
    {
        $comic = Comic::factory()->create([
            'title' => 'Neon City Runner',
            'description' => 'A story about motion and danger in the city.',
            'published_at' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'Neon']));

        $response->assertStatus(200);
        $response->assertSee(route('comic.detail', $comic));
    }

    public function test_homepage_latest_chapters_link_to_reader(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 7,
            'sort_order' => 7,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));
    }

    public function test_genre_page_shows_comics_for_the_selected_genre(): void
    {
        $genre = Genre::factory()->create(['slug' => 'action']);
        $comic = Comic::factory()->create(['published_at' => now()]);
        $comic->genres()->sync([$genre->id]);

        $response = $this->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee($comic->title);
        $response->assertSee(route('comic.detail', $comic));
    }
}

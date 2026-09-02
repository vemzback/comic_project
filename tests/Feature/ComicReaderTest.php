<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Page;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee('data-expandable-description', false);
        $response->assertSee('Selengkapnya');
        $response->assertViewHas('comic', $comic);
    }

    public function test_google_books_comic_detail_offers_a_lazy_embedded_preview(): void
    {
        $comic = Comic::factory()->create([
            'external_provider' => 'google_books',
            'external_id' => 'google-volume-1',
            'source_url' => 'https://books.google.com/books?id=google-volume-1',
            'source_metadata' => [
                'preview' => [
                    'embeddable' => true,
                    'web_reader_url' => 'https://play.google.com/books/reader?id=google-volume-1',
                ],
            ],
            'published_at' => now(),
        ]);

        $this->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertSee('Preview on Google Books')
            ->assertSee('data-google-books-preview', false)
            ->assertSee('data-volume-id="google-volume-1"', false)
            ->assertSee('https://www.google.com/books/jsapi.js', false)
            ->assertSee('window.zyxGoogleBooksReady', false)
            ->assertSee('href="https://play.google.com/books/reader?id=google-volume-1"', false);
    }

    public function test_legacy_google_books_import_without_preview_metadata_can_still_request_preview(): void
    {
        $comic = Comic::factory()->create([
            'external_provider' => 'google_books',
            'external_id' => 'legacy-google-volume',
            'source_metadata' => null,
            'published_at' => now(),
        ]);

        $this->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertSee('Preview on Google Books')
            ->assertSee('data-volume-id="legacy-google-volume"', false);
    }

    public function test_non_embeddable_google_books_comic_does_not_offer_embedded_preview(): void
    {
        $comic = Comic::factory()->create([
            'external_provider' => 'google_books',
            'external_id' => 'restricted-google-volume',
            'source_metadata' => ['preview' => ['embeddable' => false]],
            'published_at' => now(),
        ]);

        $this->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertDontSee('Preview on Google Books')
            ->assertDontSee('data-google-books-preview', false)
            ->assertDontSee('https://www.google.com/books/jsapi.js', false);
    }

    public function test_catalog_carousel_prioritizes_featured_then_recent_published_comics(): void
    {
        $featured = Comic::factory()->create([
            'title' => 'Featured Slider Comic',
            'slug' => 'featured-slider-comic',
            'is_featured' => true,
            'published_at' => now()->subMonth(),
        ]);

        $recent = Comic::factory()->create([
            'title' => 'Recent Slider Comic',
            'slug' => 'recent-slider-comic',
            'is_featured' => false,
            'published_at' => now(),
        ]);

        $future = Comic::factory()->create([
            'title' => 'Future Slider Comic',
            'slug' => 'future-slider-comic',
            'is_featured' => true,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get(route('comics'));

        $response
            ->assertOk()
            ->assertSee('data-catalog-slider', false)
            ->assertSee($featured->title)
            ->assertSee($recent->title)
            ->assertDontSee($future->title)
            ->assertViewHas('heroComics', function ($heroComics) use ($featured, $recent, $future) {
                return $heroComics->first()->is($featured)
                    && $heroComics->contains($recent)
                    && ! $heroComics->contains($future);
            });
    }

    public function test_unpublished_comic_detail_returns_404(): void
    {
        $comic = Comic::factory()->create(['published_at' => null]);

        $this->get(route('comic.detail', $comic))->assertNotFound();
    }

    public function test_future_dated_comic_detail_returns_404(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()->addDay()]);

        $this->get(route('comic.detail', $comic))->assertNotFound();
    }

    public function test_missing_comic_cover_uses_local_placeholder(): void
    {
        $comic = Comic::factory()->create([
            'published_at' => now(),
            'cover_image' => 'covers/missing-cover.jpg',
        ]);

        $this->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertSee(asset('images/media-placeholder.svg'), false)
            ->assertDontSee('https://placehold.co');
    }

    public function test_uploaded_cover_uses_a_host_independent_storage_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('comics/covers/test-cover.jpg', 'cover');

        $comic = Comic::factory()->create([
            'published_at' => now(),
            'cover_image' => 'comics/covers/test-cover.jpg',
        ]);

        $this->get(route('comic.detail', $comic))
            ->assertOk()
            ->assertSee('src="/storage/comics/covers/test-cover.jpg"', false)
            ->assertDontSee(config('app.url').'/storage/comics/covers/test-cover.jpg', false);
    }

    public function test_comic_detail_displays_chapters(): void
    {
        $comic = Comic::factory()->has(
            Chapter::factory(3)
                ->sequence(fn ($sequence) => [
                    'chapter_number' => $sequence->index + 1,
                ])
                ->state(['is_published' => true])
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
            Chapter::factory()->state(['chapter_number' => 1, 'is_published' => true])
        )->has(
            Chapter::factory()->state(['chapter_number' => 2, 'is_published' => false])
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
        $response->assertSee($chapter->title ?: 'Chapter '.$chapter->chapter_number);
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

    public function test_public_cannot_view_published_chapter_under_unpublished_comic(): void
    {
        $comic = Comic::factory()->create(['published_at' => null]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
        ]);

        $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]))
            ->assertNotFound();
    }

    public function test_public_cannot_view_published_chapter_under_future_dated_comic(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()->addDay()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
        ]);

        $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]))
            ->assertNotFound();
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
                'image_path' => "pages/test-comic/chapter-{$chapter->chapter_number}/page-".str_pad((string) $i, 3, '0', STR_PAD_LEFT).'.jpg',
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
        Storage::disk('public')->put('pages/test-comic/chapter-1/page-001.jpg', 'page image');

        $response = $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));

        $response->assertStatus(200);
        $response->assertSee('pages/test-comic/chapter-1/page-001.jpg');
    }

    public function test_missing_page_image_uses_local_placeholder(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
        ]);

        Page::create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'pages/missing/page-001.jpg',
        ]);

        $this->get(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]))
            ->assertOk()
            ->assertSee(asset('images/media-placeholder.svg'), false);
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

    public function test_empty_search_displays_top_rated_published_comics(): void
    {
        $topComic = Comic::factory()->create([
            'title' => 'Top Rated Search Comic',
            'published_at' => now()->subMonth(),
        ]);
        $lowerComic = Comic::factory()->create([
            'title' => 'Lower Rated Search Comic',
            'published_at' => now(),
        ]);
        $futureComic = Comic::factory()->create([
            'title' => 'Future Rated Search Comic',
            'published_at' => now()->addDay(),
        ]);

        Rating::create([
            'user_id' => User::factory()->create()->id,
            'comic_id' => $topComic->id,
            'score' => 5,
        ]);
        Rating::create([
            'user_id' => User::factory()->create()->id,
            'comic_id' => $lowerComic->id,
            'score' => 3,
        ]);
        Rating::create([
            'user_id' => User::factory()->create()->id,
            'comic_id' => $futureComic->id,
            'score' => 5,
        ]);

        $response = $this->get(route('search'));

        $response
            ->assertOk()
            ->assertSee('data-top-comics', false)
            ->assertSee($topComic->title)
            ->assertSee($lowerComic->title)
            ->assertDontSee($futureComic->title)
            ->assertViewHas('topComics', function ($topComics) use ($topComic, $futureComic) {
                return $topComics->first()->is($topComic)
                    && ! $topComics->contains($futureComic);
            });
    }

    public function test_public_search_rejects_oversized_queries(): void
    {
        $this->get(route('search', ['q' => str_repeat('x', 256)]))
            ->assertSessionHasErrors(['q']);
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

        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));
    }

    public function test_guest_homepage_hides_reader_specific_chapter_section(): void
    {
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]));
    }

    public function test_authenticated_homepage_continues_last_read_page(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['published_at' => now()]);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 4,
            'is_published' => true,
            'published_at' => now(),
        ]);
        ReadingHistory::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_id' => $chapter->id,
            'page_number' => 7,
            'last_read_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Continue reading')
            ->assertSee(route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]).'?page=7', false)
            ->assertViewHas('continueReading', function ($continueReading) use ($comic) {
                return $continueReading->first()->comic->is($comic);
            });
    }

    public function test_public_discovery_excludes_future_dated_comics(): void
    {
        $futureComic = Comic::factory()->create([
            'title' => 'Future Comic',
            'published_at' => now()->addDay(),
        ]);

        foreach ([route('home'), route('comics'), route('search', ['q' => 'Future'])] as $url) {
            $this->get($url)->assertOk()->assertDontSee($futureComic->title);
        }
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

    public function test_genre_page_excludes_future_dated_comics(): void
    {
        $genre = Genre::factory()->create(['slug' => 'future-action']);
        $futureComic = Comic::factory()->create([
            'title' => 'Future Action Comic',
            'published_at' => now()->addDay(),
        ]);
        $futureComic->genres()->sync([$genre->id]);

        $this->get(route('genres.show', $genre))
            ->assertOk()
            ->assertDontSee($futureComic->title);
    }
}

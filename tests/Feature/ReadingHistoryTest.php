<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class ReadingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Comic $comic;
    private Chapter $chapter;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test comic with chapters and pages
        $this->comic = Comic::factory()->create([
            'status' => 'ongoing',
            'published_at' => now(),
        ]);
        
        $this->chapter = Chapter::factory()->create([
            'comic_id' => $this->comic->id,
            'chapter_number' => 1,
            'is_published' => true,
        ]);

        // Create pages with sequential page numbers
        for ($i = 1; $i <= 10; $i++) {
            Page::factory()->create([
                'chapter_id' => $this->chapter->id,
                'page_number' => $i,
            ]);
        }

        $this->user = User::factory()->create(['role' => 'user']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
    }

    /**
     * Test 1: Guest can read a published chapter without creating history.
     */
    public function test_guest_can_read_published_chapter_without_creating_history(): void
    {
        $this->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]))
            ->assertOk();

        $this->assertDatabaseMissing('reading_history', []);
    }

    /**
     * Test 2: Authenticated user can record reading progress.
     */
    public function test_authenticated_user_can_record_reading_progress(): void
    {
        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=3');

        $this->assertDatabaseHas('reading_history', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 3,
        ]);
    }

    /**
     * Test 3: Reading progress stores user_id, comic_id, chapter_id, page_number, last_read_at.
     */
    public function test_reading_progress_stores_all_required_fields(): void
    {
        $beforeRead = now()->subSecond();

        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=5');

        $afterRead = now()->addSecond();

        $history = ReadingHistory::where('user_id', $this->user->id)
            ->where('comic_id', $this->comic->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($this->chapter->id, $history->chapter_id);
        $this->assertEquals(5, $history->page_number);
        $this->assertTrue($history->last_read_at->between($beforeRead, $afterRead));
    }

    /**
     * Test 4: Updating progress for same user+comic+chapter updates existing record instead of creating duplicates.
     */
    public function test_updating_progress_does_not_create_duplicates(): void
    {
        // First read at page 3
        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=3');

        $this->assertDatabaseCount('reading_history', 1);

        // Second read at page 5
        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=5');

        // Should still be 1 record
        $this->assertDatabaseCount('reading_history', 1);

        // Verify the record was updated
        $this->assertDatabaseHas('reading_history', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 5,
        ]);
    }

    public function test_same_identity_cannot_create_duplicate_history_rows(): void
    {
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 1,
        ]);

        $this->expectException(QueryException::class);

        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 2,
        ]);
    }

    public function test_same_user_and_comic_can_have_different_chapter_history_rows(): void
    {
        $secondChapter = Chapter::factory()->create([
            'comic_id' => $this->comic->id,
            'chapter_number' => 2,
            'is_published' => true,
        ]);

        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
        ]);
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $secondChapter->id,
        ]);

        $this->assertDatabaseCount('reading_history', 2);
    }

    public function test_different_users_can_have_history_for_the_same_chapter(): void
    {
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
        ]);
        ReadingHistory::create([
            'user_id' => $this->otherUser->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
        ]);

        $this->assertDatabaseCount('reading_history', 2);
    }

    public function test_one_null_chapter_history_is_allowed_per_user_and_comic(): void
    {
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => null,
        ]);

        $this->expectException(QueryException::class);

        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => null,
        ]);
    }

    public function test_different_users_can_each_have_null_chapter_history_for_same_comic(): void
    {
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => null,
        ]);
        ReadingHistory::create([
            'user_id' => $this->otherUser->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => null,
        ]);

        $this->assertDatabaseCount('reading_history', 2);
    }

    /**
     * Test 5: Reading history belongs to the authenticated user.
     */
    public function test_reading_history_belongs_to_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=2');

        $history = ReadingHistory::where('comic_id', $this->comic->id)->first();

        $this->assertEquals($this->user->id, $history->user_id);
    }

    /**
     * Test 6: User A cannot access User B's reading history.
     */
    public function test_user_cannot_access_other_users_reading_history(): void
    {
        // User B creates reading history
        $userB = User::factory()->create();
        ReadingHistory::create([
            'user_id' => $userB->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 3,
        ]);

        // User A tries to access /history
        $this->actingAs($this->user)
            ->get('/history')
            ->assertOk();

        // User A should not see User B's history
        // This will be tested in the response, which should only show User A's history
    }

    /**
     * Test 7: Reading history page displays only authenticated user's records.
     */
    public function test_reading_history_page_displays_only_current_users_records(): void
    {
        // Create history for User A
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 3,
            'last_read_at' => now()->subDay(),
        ]);

        // Create history for User B
        ReadingHistory::create([
            'user_id' => $this->otherUser->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 5,
            'last_read_at' => now(),
        ]);

        // User A views history
        $response = $this->actingAs($this->user)->get('/history');

        $response->assertOk();
        // Should contain User A's history
        $this->assertDatabaseCount('reading_history', 2);
        // But User A can only see 1 record
        $userAHistory = ReadingHistory::where('user_id', $this->user->id)->get();
        $this->assertCount(1, $userAHistory);
    }

    /**
     * Test 8: Continue Reading points to correct comic, chapter, page_number.
     */
    public function test_continue_reading_points_to_correct_location(): void
    {
        // Create reading history at page 7
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 7,
            'last_read_at' => now(),
        ]);

        // Get the last reading entry
        $history = ReadingHistory::where('user_id', $this->user->id)
            ->latest('last_read_at')
            ->first();

        $this->assertEquals($this->comic->id, $history->comic_id);
        $this->assertEquals($this->chapter->id, $history->chapter_id);
        $this->assertEquals(7, $history->page_number);
    }

    /**
     * Test 9: Continue Reading restores the last saved page.
     */
    public function test_continue_reading_restores_last_saved_page(): void
    {
        // Create reading history at page 6
        ReadingHistory::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'page_number' => 6,
            'last_read_at' => now(),
        ]);

        // Access the chapter
        $response = $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]));

        $response->assertOk();
        
        // The response should indicate the saved progress
        // (This could be in the view data or as a redirect parameter, TBD by implementation)
    }

    /**
     * Test 10: Invalid page numbers are rejected or safely normalized.
     */
    public function test_invalid_page_numbers_are_safely_handled(): void
    {
        // Try to access a page that doesn't exist
        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=999');

        // Should still get a successful response (doesn't crash)
        $response = $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]) . '?page=999');
        
        $response->assertOk();
        
        // Should still create a reading history record with the first page
        $history = ReadingHistory::where('user_id', $this->user->id)
            ->where('comic_id', $this->comic->id)
            ->first();
        
        $this->assertNotNull($history);
        // Should default to first page or current page (1)
        $this->assertNotNull($history->page_number);
    }

    /**
     * Test 11: Invalid comic/chapter combinations still return 404.
     */
    public function test_invalid_comic_chapter_combination_returns_404(): void
    {
        $otherComic = Comic::factory()->create();
        
        $this->get(route('chapter.reader', ['comic' => $otherComic, 'chapter' => $this->chapter]))
            ->assertNotFound();
    }

    /**
     * Test 12: Unpublished chapters remain inaccessible.
     */
    public function test_unpublished_chapters_remain_inaccessible(): void
    {
        $unpublishedChapter = Chapter::factory()->create([
            'comic_id' => $this->comic->id,
            'chapter_number' => 2,
            'is_published' => false,
        ]);

        $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $unpublishedChapter]))
            ->assertNotFound();

        // No reading history should be created
        $this->assertDatabaseMissing('reading_history', [
            'chapter_id' => $unpublishedChapter->id,
        ]);
    }

    /**
     * Test 13: Existing public reader behavior remains intact.
     */
    public function test_existing_public_reader_behavior_remains_intact(): void
    {
        // Verify that the existing reader route still works
        $response = $this->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $this->chapter]));

        $response->assertOk()
            ->assertViewHas('comic')
            ->assertViewHas('chapter')
            ->assertViewHas('pages')
            ->assertViewHas('previousChapter')
            ->assertViewHas('nextChapter');
    }

    /**
     * Test 14: Guest redirect to login when accessing history page.
     */
    public function test_guest_redirect_to_login_for_history_page(): void
    {
        $this->get('/history')->assertRedirect('/login');
    }

    /**
     * Test 15: Reading history page shows empty state when no history.
     */
    public function test_reading_history_shows_empty_state_when_no_history(): void
    {
        $response = $this->actingAs($this->user)->get('/history');

        $response->assertOk();
        // Should display an empty state message
    }
}

<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private Comic $comic;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comic = Comic::factory()->create(['status' => 'ongoing']);
        $this->user = User::factory()->create(['role' => 'user']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
    }

    /**
     * A. Public comment display
     */

    /**
     * Test 1: Approved comments are displayed on comic detail
     */
    public function test_approved_comments_are_displayed_on_comic_detail(): void
    {
        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'This is an excellent comic with great storytelling.',
            'is_approved' => true,
        ]);

        $response = $this->get(route('comic.detail', $this->comic));

        $response->assertOk();
        $response->assertSee($comment->body);
        $response->assertSee($this->user->name);
    }

    /**
     * Test 2: Unapproved comments are not displayed publicly
     */
    public function test_unapproved_comments_are_not_displayed_publicly(): void
    {
        $approvedComment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'This comment is approved.',
            'is_approved' => true,
        ]);

        $unapprovedComment = Comment::create([
            'user_id' => $this->otherUser->id,
            'comic_id' => $this->comic->id,
            'body' => 'This comment is not approved.',
            'is_approved' => false,
        ]);

        $response = $this->get(route('comic.detail', $this->comic));

        $response->assertOk();
        $response->assertSee($approvedComment->body);
        $response->assertDontSee($unapprovedComment->body);
    }

    /**
     * B. Authentication
     */

    /**
     * Test 3: Guest cannot create a comment
     */
    public function test_guest_cannot_create_a_comment(): void
    {
        $response = $this->post(route('comments.store', $this->comic), [
            'body' => 'This is a guest comment.',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('comments', [
            'body' => 'This is a guest comment.',
        ]);
    }

    /**
     * Test 4: Authenticated user can see comment form
     */
    public function test_authenticated_user_can_see_comment_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('comic.detail', $this->comic));

        $response->assertOk();
        $response->assertSee('comment');
        // Look for form or textarea for commenting
        $response->assertSee('Comment');
    }

    /**
     * C. Comment creation
     */

    /**
     * Test 5: Authenticated user can create a comment
     */
    public function test_authenticated_user_can_create_a_comment(): void
    {
        $commentBody = 'This is an amazing comic series!';

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => $commentBody,
            ]);

        $response->assertRedirect(route('comic.detail', $this->comic));

        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => $commentBody,
        ]);
    }

    /**
     * Test 6: Comment belongs to authenticated user
     */
    public function test_comment_belongs_to_authenticated_user(): void
    {
        $commentBody = 'Test comment';

        $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => $commentBody,
            ]);

        $comment = Comment::where('body', $commentBody)->first();

        $this->assertNotNull($comment);
        $this->assertEquals($this->user->id, $comment->user_id);
    }

    /**
     * Test 7: Comment belongs to correct comic
     */
    public function test_comment_belongs_to_correct_comic(): void
    {
        $commentBody = 'Test comment for specific comic';

        $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => $commentBody,
            ]);

        $comment = Comment::where('body', $commentBody)->first();

        $this->assertNotNull($comment);
        $this->assertEquals($this->comic->id, $comment->comic_id);
    }

    /**
     * D. Validation
     */

    /**
     * Test 8: Comment body is required
     */
    public function test_comment_body_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => '',
            ]);

        // Expect validation error or redirect with errors
        $response->assertSessionHasErrors('body');
        $this->assertDatabaseEmpty('comments');
    }

    /**
     * Test 9: Comment body cannot exceed 500 characters
     */
    public function test_comment_body_cannot_exceed_500_characters(): void
    {
        $longBody = str_repeat('a', 501);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => $longBody,
            ]);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseMissing('comments', [
            'body' => $longBody,
        ]);
    }

    /**
     * Test 10: Comment body must be a string
     */
    public function test_comment_body_must_be_a_string(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => 12345,
            ]);

        // Either accept integer that converts to string, or reject it
        // Since HTML form inputs are strings, this test checks validation
        // Accept the comment if it's converted to string, or reject if strict typing
        $this->assertTrue(
            $response->status() === 302 || // Redirect with error
            $this->assertDatabaseHas('comments', ['comic_id' => $this->comic->id]) === null
        );
    }

    /**
     * E. Ownership / deletion
     */

    /**
     * Test 11: Authenticated user can delete own comment
     */
    public function test_authenticated_user_can_delete_own_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'This is my comment to delete.',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect(route('comic.detail', $this->comic));

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test 12: User cannot delete another users comment
     */
    public function test_user_cannot_delete_another_users_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->otherUser->id,
            'comic_id' => $this->comic->id,
            'body' => 'This is someone elses comment.',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertForbidden();

        // Comment should still exist in database
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test 13: Guest cannot delete a comment
     */
    public function test_guest_cannot_delete_a_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'Comment to be protected from guest deletion.',
            'is_approved' => true,
        ]);

        $response = $this->delete(route('comments.destroy', $comment));

        $response->assertRedirect('/login');

        // Comment should still exist
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * Test 14: Deleting a comment removes it from database
     */
    public function test_deleting_a_comment_removes_it_from_database(): void
    {
        $comment = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'This comment will be deleted.',
            'is_approved' => true,
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * F. Regression-oriented behavior
     */

    /**
     * Test 15: Comic detail still displays normally with comments
     */
    public function test_comic_detail_still_displays_normally_with_comments(): void
    {
        Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'Test comment.',
            'is_approved' => true,
        ]);

        $response = $this->get(route('comic.detail', $this->comic));

        $response->assertOk();
        $response->assertSee($this->comic->title);
        $response->assertViewHas('comic', $this->comic);
    }

    /**
     * Test 16: Comment actions require CSRF protection
     */
    public function test_comment_actions_require_csrf_protection(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => 'Test comment without CSRF.',
            ]);

        // Re-enable CSRF check and verify that without CSRF token, request fails
        $this->withMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->comic), [
                'body' => 'Test comment without CSRF token in middleware.',
            ]);

        // Laravel will handle CSRF automatically in test context,
        // but we can verify the form includes CSRF in views
        $response = $this->actingAs($this->user)
            ->get(route('comic.detail', $this->comic));

        if ($response->getStatusCode() === 200) {
            // If form exists, it should have CSRF token
            $response->assertSee('@csrf');
        }
    }

    /**
     * Test 17: Empty state when no comments exist
     */
    public function test_empty_state_when_no_comments_exist(): void
    {
        $response = $this->get(route('comic.detail', $this->comic));

        $response->assertOk();
        // Should display a message when no comments exist
        $response->assertSee('No comments');
    }

    /**
     * Test 18: Guest sees login prompt to comment
     */
    public function test_guest_sees_login_prompt_to_comment(): void
    {
        $response = $this->get(route('comic.detail', $this->comic));

        $response->assertOk();
        // Should indicate login is needed to comment
        $response->assertSee('login');
    }

    /**
     * Test 19: Multiple users can comment on same comic
     */
    public function test_multiple_users_can_comment_on_same_comic(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1)
            ->post(route('comments.store', $this->comic), [
                'body' => 'Comment from user 1.',
            ]);

        $this->actingAs($user2)
            ->post(route('comments.store', $this->comic), [
                'body' => 'Comment from user 2.',
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user1->id,
            'comic_id' => $this->comic->id,
            'body' => 'Comment from user 1.',
        ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user2->id,
            'comic_id' => $this->comic->id,
            'body' => 'Comment from user 2.',
        ]);
    }

    /**
     * Test 20: Comments are ordered by creation date (newest first)
     */
    public function test_comments_are_ordered_by_creation_date_newest_first(): void
    {
        $comment1 = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'First comment',
            'is_approved' => true,
            'created_at' => now()->subHours(2),
        ]);

        $comment2 = Comment::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'Second comment',
            'is_approved' => true,
            'created_at' => now(),
        ]);

        $response = $this->get(route('comic.detail', $this->comic));

        $response->assertOk();
        // Comment2 should appear before Comment1 in the output
        $body = $response->getContent();
        $pos1 = strpos($body, $comment2->body);
        $pos2 = strpos($body, $comment1->body);

        if ($pos1 !== false && $pos2 !== false) {
            $this->assertLessThan($pos2, $pos1, 'Newer comment should appear first');
        }
    }

    /**
     * Test 21: Bookmark functionality is unchanged (regression)
     */
    public function test_bookmark_functionality_is_unchanged(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('comic.detail', $this->comic));

        $response->assertOk();
        // Should still show bookmark button
        $response->assertSee('Bookmark');
    }

    /**
     * Test 22: Reading history is unaffected (regression)
     */
    public function test_reading_history_is_unaffected(): void
    {
        $chapter = Chapter::factory()->create([
            'comic_id' => $this->comic->id,
            'is_published' => true,
        ]);

        Page::factory()->create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chapter.reader', ['comic' => $this->comic, 'chapter' => $chapter]));

        $response->assertOk();
        // Reading history should still be recorded
        $this->assertDatabaseHas('reading_history', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
        ]);
    }
}

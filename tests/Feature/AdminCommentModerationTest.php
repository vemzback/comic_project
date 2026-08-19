<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    private Comic $comic;
    private Comic $otherComic;
    private User $admin;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comic = Comic::factory()->create(['status' => 'ongoing']);
        $this->otherComic = Comic::factory()->create(['status' => 'ongoing']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
    }

    public function test_guest_cannot_access_comment_moderation_index(): void
    {
        $this->get('/admin/comments')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_comment_moderation_index(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/comments')
            ->assertForbidden();
    }

    public function test_guest_cannot_update_comment_approval(): void
    {
        $comment = $this->createComment();

        $this->patch("/admin/comments/{$comment->id}/approval", [
            'is_approved' => '1',
        ])->assertRedirect('/login');
    }

    public function test_regular_user_cannot_update_comment_approval(): void
    {
        $comment = $this->createComment();

        $this->actingAs($this->user)
            ->patch("/admin/comments/{$comment->id}/approval", [
                'is_approved' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => false,
        ]);
    }

    public function test_regular_user_cannot_use_admin_comment_delete_endpoint(): void
    {
        $comment = $this->createComment();

        $this->actingAs($this->user)
            ->delete("/admin/comments/{$comment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_access_comment_moderation_index(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/comments')
            ->assertOk();
    }

    public function test_comment_moderation_index_displays_author(): void
    {
        $comment = $this->createComment(['body' => 'Author-visible comment']);

        $this->actingAs($this->admin)
            ->get('/admin/comments')
            ->assertSee($this->user->name)
            ->assertSee($comment->body);
    }

    public function test_comment_moderation_index_displays_comic(): void
    {
        $this->createComment(['comic_id' => $this->comic->id]);

        $this->actingAs($this->admin)
            ->get('/admin/comments')
            ->assertSee($this->comic->title);
    }

    public function test_comment_moderation_index_displays_approval_state(): void
    {
        $this->createComment(['is_approved' => false]);
        $this->createComment([
            'user_id' => $this->otherUser->id,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/comments');

        $response->assertSee('Pending');
        $response->assertSee('Approved');
    }

    public function test_comment_moderation_index_is_paginated(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            $this->createComment([
                'body' => "Pagination comment {$index}",
                'created_at' => now()->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/admin/comments');

        $response->assertOk();
        $response->assertSee('Pagination comment 1');
        $response->assertDontSee('Pagination comment 13');
        $this->assertSame(12, $response->original['comments']->perPage());
        $this->assertTrue($response->original['comments']->hasPages());
    }

    public function test_comment_moderation_defaults_to_pending_comments(): void
    {
        $pending = $this->createComment(['body' => 'Pending moderation comment']);
        $approved = $this->createComment([
            'body' => 'Already approved comment',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/comments');

        $response->assertSee($pending->body);
        $response->assertDontSee($approved->body);
    }

    public function test_admin_can_filter_pending_comments(): void
    {
        $pending = $this->createComment(['body' => 'Filtered pending comment']);
        $approved = $this->createComment([
            'body' => 'Filtered approved comment',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/comments?status=pending');

        $response->assertSee($pending->body);
        $response->assertDontSee($approved->body);
    }

    public function test_admin_can_filter_approved_comments(): void
    {
        $pending = $this->createComment(['body' => 'Approved filter pending comment']);
        $approved = $this->createComment([
            'body' => 'Approved filter approved comment',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/comments?status=approved');

        $response->assertSee($approved->body);
        $response->assertDontSee($pending->body);
    }

    public function test_admin_can_filter_all_comments(): void
    {
        $pending = $this->createComment(['body' => 'All filter pending comment']);
        $approved = $this->createComment([
            'body' => 'All filter approved comment',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/comments?status=all');

        $response->assertSee($pending->body);
        $response->assertSee($approved->body);
    }

    public function test_status_filter_excludes_opposite_state(): void
    {
        $pending = $this->createComment(['body' => 'Opposite state pending comment']);
        $approved = $this->createComment([
            'body' => 'Opposite state approved comment',
            'is_approved' => true,
        ]);

        $pendingResponse = $this->actingAs($this->admin)->get('/admin/comments?status=pending');
        $approvedResponse = $this->actingAs($this->admin)->get('/admin/comments?status=approved');

        $pendingResponse->assertSee($pending->body)->assertDontSee($approved->body);
        $approvedResponse->assertSee($approved->body)->assertDontSee($pending->body);
    }

    public function test_invalid_comment_status_filter_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/comments?status=unknown')
            ->assertSessionHasErrors(['status']);
    }

    public function test_comment_status_filter_is_preserved_across_pagination(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            $this->createComment([
                'body' => "Filtered pagination comment {$index}",
                'created_at' => now()->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get('/admin/comments?status=pending');

        $response->assertOk();
        $this->assertStringContainsString('status=pending', $response->getContent());
    }

    public function test_comment_moderation_orders_newest_comments_first(): void
    {
        $older = $this->createComment([
            'body' => 'Older moderation comment',
            'created_at' => now()->subHours(2),
        ]);
        $newer = $this->createComment([
            'body' => 'Newer moderation comment',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/comments');
        $content = $response->getContent();
        $newerPosition = strpos($content, $newer->body);
        $olderPosition = strpos($content, $older->body);

        $this->assertNotFalse($newerPosition);
        $this->assertNotFalse($olderPosition);
        $this->assertLessThan($newerPosition, $olderPosition);
    }

    public function test_admin_can_approve_pending_comment(): void
    {
        $comment = $this->createComment(['is_approved' => false]);

        $this->actingAs($this->admin)
            ->patch("/admin/comments/{$comment->id}/approval", [
                'is_approved' => '1',
            ])
            ->assertRedirect('/admin/comments');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => true,
        ]);
    }

    public function test_approving_comment_makes_it_publicly_visible(): void
    {
        $comment = $this->createComment([
            'body' => 'Comment becomes public after approval',
            'is_approved' => false,
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/comments/{$comment->id}/approval", [
                'is_approved' => '1',
            ]);

        $this->get(route('comic.detail', $this->comic))
            ->assertSee($comment->body);
    }

    public function test_admin_can_unapprove_approved_comment(): void
    {
        $comment = $this->createComment(['is_approved' => true]);

        $this->actingAs($this->admin)
            ->patch("/admin/comments/{$comment->id}/approval", [
                'is_approved' => '0',
            ])
            ->assertRedirect('/admin/comments');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => false,
        ]);
    }

    public function test_unapproving_comment_hides_it_from_public_view(): void
    {
        $comment = $this->createComment([
            'body' => 'Comment becomes hidden after unapproval',
            'is_approved' => true,
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/comments/{$comment->id}/approval", [
                'is_approved' => '0',
            ]);

        $this->get(route('comic.detail', $this->comic))
            ->assertDontSee($comment->body);
    }

    public function test_comment_approval_value_is_required(): void
    {
        $comment = $this->createComment(['is_approved' => false]);

        $this->actingAs($this->admin)
            ->from('/admin/comments')
            ->patch("/admin/comments/{$comment->id}/approval")
            ->assertSessionHasErrors(['is_approved']);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => false,
        ]);
    }

    public function test_invalid_comment_approval_value_is_rejected(): void
    {
        $comment = $this->createComment(['is_approved' => false]);

        $this->actingAs($this->admin)
            ->from('/admin/comments')
            ->patch("/admin/comments/{$comment->id}/approval", [
                'is_approved' => 'approved',
            ])
            ->assertSessionHasErrors(['is_approved']);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => false,
        ]);
    }

    public function test_admin_can_delete_another_users_comment(): void
    {
        $comment = $this->createComment(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->admin)
            ->delete("/admin/comments/{$comment->id}")
            ->assertRedirect('/admin/comments');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_comment_delete_only_removes_target_comment(): void
    {
        $target = $this->createComment(['body' => 'Target comment to delete']);
        $unrelated = $this->createComment([
            'body' => 'Unrelated comment remains',
            'comic_id' => $this->otherComic->id,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/comments/{$target->id}");

        $this->assertDatabaseMissing('comments', ['id' => $target->id]);
        $this->assertDatabaseHas('comments', ['id' => $unrelated->id]);
    }

    public function test_admin_comment_mutation_returns_404_for_nonexistent_comment(): void
    {
        $route = $this->app->make(\Illuminate\Routing\Router::class)
            ->getRoutes()
            ->getByName('admin.comments.approval.update');

        $this->assertNotNull($route);

        $this->actingAs($this->admin)
            ->patch('/admin/comments/999999/approval', [
                'is_approved' => '1',
            ])
            ->assertNotFound();
    }

    public function test_existing_comment_owner_delete_behavior_remains_unchanged(): void
    {
        $comment = $this->createComment(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect(route('comic.detail', $this->comic));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    private function createComment(array $attributes = []): Comment
    {
        return Comment::create(array_merge([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'body' => 'Moderation comment',
            'is_approved' => false,
        ], $attributes));
    }
}

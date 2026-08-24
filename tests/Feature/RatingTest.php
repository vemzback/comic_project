<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    private Comic $comic;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comic = Comic::factory()->create([
            'status' => 'ongoing',
            'published_at' => now(),
        ]);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_guest_cannot_rate_a_comic(): void
    {
        $response = $this->post(route('ratings.store', $this->comic), [
            'score' => 5,
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('ratings', [
            'comic_id' => $this->comic->id,
            'score' => 5,
        ]);
    }

    public function test_authenticated_user_can_see_rating_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('comic.detail', $this->comic));

        $response->assertOk();
        $response->assertSee('rating');
        $response->assertSee('Rate');
    }

    public function test_authenticated_user_can_create_a_rating(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ratings.store', $this->comic), [
                'score' => 5,
            ]);

        $response->assertRedirect(route('comic.detail', $this->comic));

        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'score' => 5,
        ]);
    }

    public function test_rating_belongs_to_authenticated_user_and_comic(): void
    {
        $this->actingAs($this->user)
            ->post(route('ratings.store', $this->comic), [
                'score' => 4,
            ]);

        $rating = Rating::where('comic_id', $this->comic->id)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($rating);
        $this->assertSame($this->user->id, $rating->user_id);
        $this->assertSame($this->comic->id, $rating->comic_id);
    }

    public function test_rating_score_is_required_and_must_be_between_1_and_5(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ratings.store', $this->comic), [
                'score' => 0,
            ]);

        $response->assertSessionHasErrors('score');
        $this->assertDatabaseMissing('ratings', [
            'comic_id' => $this->comic->id,
            'user_id' => $this->user->id,
        ]);

        $invalidResponse = $this->actingAs($this->user)
            ->post(route('ratings.store', $this->comic), [
                'score' => 6,
            ]);

        $invalidResponse->assertSessionHasErrors('score');
    }

    public function test_user_can_update_their_existing_rating_for_the_same_comic(): void
    {
        Rating::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'score' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('ratings.store', $this->comic), [
                'score' => 5,
            ]);

        $response->assertRedirect(route('comic.detail', $this->comic));
        $this->assertSame(1, Rating::where('user_id', $this->user->id)
            ->where('comic_id', $this->comic->id)
            ->count());
        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'score' => 5,
        ]);
    }
}

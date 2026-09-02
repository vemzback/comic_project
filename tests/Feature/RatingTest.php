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
        $response->assertSee('star-rating');
        $response->assertSee('Save Rating');
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

    public function test_ajax_rating_returns_updated_average_and_count(): void
    {
        $otherUser = User::factory()->create(['role' => 'user']);

        Rating::create([
            'user_id' => $otherUser->id,
            'comic_id' => $this->comic->id,
            'score' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ratings.store', $this->comic), [
                'score' => 5,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Your rating has been saved.',
                'average_rating' => 4,
                'rating_count' => 2,
                'user_score' => 5,
            ]);
    }

    public function test_rating_is_rejected_for_unpublished_or_future_comic(): void
    {
        $unpublishedComic = Comic::factory()->create([
            'status' => 'ongoing',
            'published_at' => null,
        ]);
        $futureComic = Comic::factory()->create([
            'status' => 'ongoing',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($this->user)
            ->post(route('ratings.store', $unpublishedComic), ['score' => 5])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->post(route('ratings.store', $futureComic), ['score' => 5])
            ->assertNotFound();

        $this->assertDatabaseMissing('ratings', ['user_id' => $this->user->id]);
    }

    public function test_comic_detail_displays_calculated_average(): void
    {
        $otherUser = User::factory()->create(['role' => 'user']);

        Rating::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'score' => 3,
        ]);
        Rating::create([
            'user_id' => $otherUser->id,
            'comic_id' => $this->comic->id,
            'score' => 5,
        ]);

        $this->get(route('comic.detail', $this->comic))
            ->assertOk()
            ->assertSee('4.0')
            ->assertSee('2')
            ->assertSee('ratings');
    }

    public function test_catalog_card_displays_rating_summary(): void
    {
        Rating::create([
            'user_id' => $this->user->id,
            'comic_id' => $this->comic->id,
            'score' => 4,
        ]);

        $this->get(route('comics'))
            ->assertOk()
            ->assertSee('4.0')
            ->assertSee('(1)');
    }
}

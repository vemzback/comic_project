<?php

namespace Database\Factories;

use App\Models\Comic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chapter>
 */
class ChapterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comic_id' => Comic::factory(),
            'chapter_number' => $this->faker->numberBetween(1, 100),
            'title' => $this->faker->optional(0.7)->sentence(2),
            'slug' => $this->faker->unique()->slug(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_published' => $this->faker->boolean(80),
            'published_at' => $this->faker->optional(0.8)->dateTime(),
        ];
    }
}

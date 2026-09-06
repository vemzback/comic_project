<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'page_number' => $this->faker->unique()->numberBetween(1, 1000),
            'title' => $this->faker->optional(0.3)->sentence(2),
            'image_path' => 'pages/'.$this->faker->slug().'/page-'.str_pad((string) $this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT).'.jpg',
        ];
    }
}

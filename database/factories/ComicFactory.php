<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comic>
 */
class ComicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => str()->slug($title),
            'description' => $this->faker->paragraphs(3, asText: true),
            'cover_image' => null,
            'status' => $this->faker->randomElement(['ongoing', 'completed', 'hiatus']),
            'published_at' => $this->faker->optional()->dateTime(),
            'is_featured' => $this->faker->boolean(20),
            'seo_title' => null,
            'seo_description' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\GalleryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryItem>
 */
class GalleryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'image' => 'gallery/'.fake()->unique()->slug(3).'.jpg',
            'description' => fake()->sentence(12),
            'documented_at' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }
}

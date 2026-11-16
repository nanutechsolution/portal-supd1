<?php

namespace Database\Factories;

use App\Models\Bulletin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bulletin>
 */
class BulletinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Buletin SUPD I Edisi '.fake()->unique()->numberBetween(1, 999),
            'edition_number' => (string) fake()->numberBetween(1, 99),
            'year' => (int) now()->format('Y'),
            'description' => fake()->paragraph(),
            'cover' => null,
            'file_path' => null,
            'published_at' => null,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }
}

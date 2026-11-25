<?php

namespace Database\Factories;

use App\Enums\OrganizationalLevel;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationalUnit>
 */
class OrganizationalUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Unit '.fake()->unique()->words(3, true),
            'official_name' => null,
            'level' => OrganizationalLevel::Pimpinan,
            'photo' => null,
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(1, 50),
            'description' => null,
            'is_active' => true,
            'is_published' => true,
        ];
    }

    public function withLevel(OrganizationalLevel $level): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => $level,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }
}

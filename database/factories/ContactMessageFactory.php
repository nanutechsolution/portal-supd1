<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('08##########'),
            'subject' => fake()->sentence(5),
            'message' => fake()->paragraph(),
            'status' => ContactMessageStatus::Baru,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function replied(): static
    {
        return $this->state(fn (): array => [
            'status' => ContactMessageStatus::Dibalas,
        ]);
    }
}

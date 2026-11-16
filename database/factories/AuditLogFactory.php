<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'actor_name' => fake()->name(),
            'event' => 'created',
            'module' => 'posts',
            'subject_type' => null,
            'subject_id' => null,
            'subject_label' => fake()->sentence(4),
            'before' => null,
            'after' => ['title' => fake()->sentence(4)],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'status' => 'success',
            'created_at' => now(),
        ];
    }
}

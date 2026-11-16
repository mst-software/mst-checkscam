<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Watch>
 */
class WatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'phone',
            'value' => '09'.fake()->unique()->numerify('########'),
            'last_notified_at' => null,
        ];
    }

    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bank',
            'value' => fake()->unique()->numerify('##########'),
        ]);
    }
}
